param(
    [string]$BackupTimestamp = ''
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

try {
    . (Join-Path $PSScriptRoot 'deploy_common.ps1')

    $config = Get-DeployConfig
    $preflight = Get-DeployPreflightReport -Config $config
    Show-DeployPreflight -Report $preflight
    $paths = Resolve-WinScpPaths -Config $config
    Import-WinScpAssembly -AssemblyPath $paths.AssemblyPath

    $manifestPath = if ([string]::IsNullOrWhiteSpace($BackupTimestamp)) {
        Get-LatestBackupManifest -Config $config
    } else {
        $candidate = Join-Path (Join-Path $config.DeployBackupRoot $BackupTimestamp) 'manifest.json'
        if (Test-Path $candidate) { $candidate } else { $null }
    }

    if (-not $manifestPath) {
        throw 'No rollback manifest found.'
    }

    $manifest = Get-Content -Path $manifestPath -Raw | ConvertFrom-Json
    $entries = @($manifest.files)
    if ($entries.Count -eq 0) {
        throw "Rollback manifest is empty: $manifestPath"
    }

    $logLines = @(
        ('time=' + (Get-Date).ToString('s')),
        ('mode=rollback'),
        ('manifest=' + [string]$manifestPath),
        ('remote_root=' + [string]$config.RemoteRoot)
    )

    $session = $null
    try {
        $session = New-WinScpSession -Config $config -ExecutablePath $paths.ExecutablePath
        $transferOptions = New-BinaryTransferOptions

        foreach ($entry in $entries) {
            $relativePath = [string]$entry.relative_path
            $remotePath = [string]$entry.remote_path
            $remoteExisted = [bool]$entry.remote_existed
            $backupPath = [string]$entry.backup_path

            if ($remoteExisted -and $backupPath -and (Test-Path $backupPath)) {
                $remoteDirectory = Get-RemoteDirectoryPath -RemotePath $remotePath
                Ensure-RemoteDirectory -Session $session -RemoteDirectory $remoteDirectory
                $putResult = $session.PutFiles($backupPath, $remotePath, $false, $transferOptions)
                $putResult.Check()
                Write-Host ("ROLLED BACK  {0}" -f $relativePath)
                $logLines += ('rolled_back=' + $relativePath + ' | remote=' + $remotePath)
                continue
            }

            if (-not $remoteExisted -and $session.FileExists($remotePath)) {
                $session.RemoveFiles($remotePath).Check()
                Write-Host ("REMOVED      {0}" -f $relativePath)
                $logLines += ('removed=' + $relativePath + ' | remote=' + $remotePath)
                continue
            }

            Write-Host ("SKIPPED      {0}" -f $relativePath)
            $logLines += ('skipped=' + $relativePath + ' | remote=' + $remotePath)
        }
    }
    finally {
        if ($session -ne $null) {
            $session.Dispose()
        }
    }

    $logPath = New-DeployLogPath -Config $config -Prefix 'rollback'
    $logLines += 'result=success'
    Write-DeployLog -LogPath $logPath -Lines $logLines

    Write-Host ''
    Write-Host ("Rollback complete from backup: {0}" -f (Split-Path $manifestPath -Parent))
    Write-Host ("Rollback log: {0}" -f $logPath)
    exit 0
}
catch {
    try {
        if ($config) {
            $logPath = New-DeployLogPath -Config $config -Prefix 'rollback-error'
            Write-DeployLog -LogPath $logPath -Lines @(
                ('time=' + (Get-Date).ToString('s')),
                ('result=error'),
                ('message=' + $_.Exception.Message)
            )
            Write-Host ("Rollback log: {0}" -f $logPath)
        }
    } catch {
    }
    Write-Host ''
    Write-Host ("Rollback failed: {0}" -f $_.Exception.Message) -ForegroundColor Red
    exit 1
}
