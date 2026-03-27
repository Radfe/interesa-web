param(
    [string]$BackupTimestamp = ''
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

try {
    . (Join-Path $PSScriptRoot 'deploy_common.ps1')

    $config = Get-DeployConfig
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
                continue
            }

            if (-not $remoteExisted -and $session.FileExists($remotePath)) {
                $session.RemoveFiles($remotePath).Check()
                Write-Host ("REMOVED      {0}" -f $relativePath)
                continue
            }

            Write-Host ("SKIPPED      {0}" -f $relativePath)
        }
    }
    finally {
        if ($session -ne $null) {
            $session.Dispose()
        }
    }

    Write-Host ''
    Write-Host ("Rollback complete from backup: {0}" -f (Split-Path $manifestPath -Parent))
    exit 0
}
catch {
    Write-Host ''
    Write-Host ("Rollback failed: {0}" -f $_.Exception.Message) -ForegroundColor Red
    exit 1
}
