param(
    [string]$SinceRef = '',
    [string[]]$ExplicitFiles,
    [string]$ExplicitListFile = '',
    [switch]$Preview
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

    $resolvedExplicitFiles = @()
    if (-not [string]::IsNullOrWhiteSpace($ExplicitListFile)) {
        $resolvedExplicitFiles = Get-ExplicitFilesFromList -ListPath $ExplicitListFile
    } elseif ($ExplicitFiles) {
        $resolvedExplicitFiles = @($ExplicitFiles)
    }

    $changeSet = Get-ChangedPublicFiles -Config $config -SinceRef $SinceRef -ExplicitFiles $ExplicitFiles
    if ($resolvedExplicitFiles.Count -gt 0) {
        $changeSet = Get-ChangedPublicFiles -Config $config -SinceRef '' -ExplicitFiles $resolvedExplicitFiles
    }
    $files = @($changeSet.Files)
    if ($files.Count -eq 0) {
        Write-Host 'Deploy skipped: nenasli sa ziadne public subory na deploy.'
        exit 0
    }

    $logLines = @(
        ('time=' + (Get-Date).ToString('s')),
        ('mode=' + ($(if ($Preview) { 'preview' } else { 'deploy' }))),
        ('base_ref=' + [string]$changeSet.BaseRef),
        ('local_public_root=' + [string]$config.LocalPublicRoot),
        ('remote_root=' + [string]$config.RemoteRoot),
        ('winscp_executable=' + [string]$paths.ExecutablePath)
    )

    Write-Host ("Changed files source: {0}" -f $changeSet.BaseRef)
    foreach ($file in $files) {
        Write-Host ("FILE  raw={0}" -f [string]$file.RawPath)
        Write-Host ("      project-relative={0}" -f [string]$file.RepoRelativePath)
        Write-Host ("      local={0}" -f [string]$file.LocalPath)
        Write-Host ("      remote={0}" -f [string]$file.RemotePath)
        $logLines += ('file=' + [string]$file.RelativePublicPath + ' | local=' + [string]$file.LocalPath + ' | remote=' + [string]$file.RemotePath)
    }
    Write-Host ''

    if ($Preview) {
        $logPath = New-DeployLogPath -Config $config -Prefix 'deploy-preview'
        $logLines += 'result=preview-only'
        Write-DeployLog -LogPath $logPath -Lines $logLines
        Write-Host ("Preview hotovy: {0} subor(ov) by sa uploadlo." -f $files.Count)
        Write-Host ("Deploy log: {0}" -f $logPath)
        exit 0
    }

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

    $logPath = New-DeployLogPath -Config $config -Prefix 'deploy'
    $logLines += @(
        ('result=success'),
        ('uploaded_files=' + [string]$files.Count),
        ('backup_root=' + [string]$backupRoot),
        ('manifest=' + [string]$manifestPath),
        ('head_commit=' + [string]$headCommit)
    )
    Write-DeployLog -LogPath $logPath -Lines $logLines

    Write-Host ''
    Write-Host ("Deploy complete: {0} file(s) uploaded." -f $files.Count)
    Write-Host ("Backup saved to: {0}" -f $backupRoot)
    Write-Host ("Base ref used: {0}" -f $changeSet.BaseRef)
    Write-Host ("Deploy log: {0}" -f $logPath)
    exit 0
}
catch {
    try {
        if ($config) {
            $logPath = New-DeployLogPath -Config $config -Prefix 'deploy-error'
            Write-DeployLog -LogPath $logPath -Lines @(
                ('time=' + (Get-Date).ToString('s')),
                ('result=error'),
                ('message=' + $_.Exception.Message)
            )
            Write-Host ("Deploy log: {0}" -f $logPath)
        }
    } catch {
    }
    Write-Host ''
    Write-Host ("Deploy failed: {0}" -f $_.Exception.Message) -ForegroundColor Red
    exit 1
}
