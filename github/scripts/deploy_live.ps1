param(
    [string]$SinceRef = '',
    [string[]]$ExplicitFiles
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

try {
    . (Join-Path $PSScriptRoot 'deploy_common.ps1')

    $config = Get-DeployConfig
    $paths = Resolve-WinScpPaths -Config $config
    Import-WinScpAssembly -AssemblyPath $paths.AssemblyPath

    $changeSet = Get-ChangedPublicFiles -Config $config -SinceRef $SinceRef -ExplicitFiles $ExplicitFiles
    $files = @($changeSet.Files)
    if ($files.Count -eq 0) {
        Write-Host 'Deploy skipped: no changed files in public/.'
        exit 0
    }

    Write-Host ("Changed files source: {0}" -f $changeSet.BaseRef)
    foreach ($file in $files) {
        Write-Host ("FILE  raw={0}" -f [string]$file.RawPath)
        Write-Host ("      project-relative={0}" -f [string]$file.RepoRelativePath)
        Write-Host ("      local={0}" -f [string]$file.LocalPath)
        Write-Host ("      remote={0}" -f [string]$file.RemotePath)
    }
    Write-Host ''

    $timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $backupRoot = Join-Path $config.DeployBackupRoot $timestamp
    $backupFilesRoot = Join-Path $backupRoot 'files'
    Ensure-LocalDirectory -Path $backupFilesRoot

    $manifest = [ordered]@{
        timestamp = $timestamp
        created_at = (Get-Date).ToString('s')
        base_ref = $changeSet.BaseRef
        remote_root = $config.RemoteRoot
        local_public_root = $config.LocalPublicRoot
        files = @()
    }

    $session = $null
    try {
        $session = New-WinScpSession -Config $config -ExecutablePath $paths.ExecutablePath
        $transferOptions = New-BinaryTransferOptions

        foreach ($file in $files) {
            $relativePath = [string]$file.RelativePublicPath
            $remotePath = [string]$file.RemotePath
            $localPath = [string]$file.LocalPath
            $backupPath = Join-Path $backupFilesRoot ($relativePath -replace '/', '\')
            $backupDirectory = Split-Path $backupPath -Parent
            Ensure-LocalDirectory -Path $backupDirectory

            $remoteExists = $session.FileExists($remotePath)
            if ($remoteExists) {
                $getResult = $session.GetFiles($remotePath, $backupPath, $false, $transferOptions)
                $getResult.Check()
            }

            $remoteDirectory = Get-RemoteDirectoryPath -RemotePath $remotePath
            Ensure-RemoteDirectory -Session $session -RemoteDirectory $remoteDirectory
            $putResult = $session.PutFiles($localPath, $remotePath, $false, $transferOptions)
            $putResult.Check()

            $manifest.files += [ordered]@{
                relative_path = $relativePath
                local_path = $localPath
                remote_path = $remotePath
                backup_path = if ($remoteExists) { $backupPath } else { $null }
                remote_existed = $remoteExists
            }

            Write-Host ("DEPLOYED  {0}" -f $relativePath)
        }
    }
    finally {
        if ($session -ne $null) {
            $session.Dispose()
        }
    }

    $manifestPath = Join-Path $backupRoot 'manifest.json'
    Save-DeployManifest -ManifestPath $manifestPath -Data $manifest

    $headCommit = Get-HeadCommit -Config $config
    if ($headCommit) {
        Set-Content -Path (Get-LastDeployStateFile -Config $config) -Value $headCommit -Encoding UTF8
    }

    Write-Host ''
    Write-Host ("Deploy complete: {0} file(s) uploaded." -f $files.Count)
    Write-Host ("Backup saved to: {0}" -f $backupRoot)
    Write-Host ("Base ref used: {0}" -f $changeSet.BaseRef)
    exit 0
}
catch {
    Write-Host ''
    Write-Host ("Deploy failed: {0}" -f $_.Exception.Message) -ForegroundColor Red
    exit 1
}
