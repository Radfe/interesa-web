$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Get-DeployProjectRoot {
    return (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
}

function Join-RemotePath {
    param(
        [Parameter(Mandatory = $true)][string]$RemoteRoot,
        [Parameter(Mandatory = $true)][string]$RelativePath
    )

    $root = '/' + ($RemoteRoot.Trim().Trim('/'))
    $relative = ($RelativePath -replace '\\', '/').TrimStart('/')
    if ($relative -eq '') {
        return $root
    }

    return "$root/$relative"
}

function Get-RemoteDirectoryPath {
    param(
        [Parameter(Mandatory = $true)][string]$RemotePath
    )

    $normalized = ($RemotePath -replace '\\', '/')
    $lastSlash = $normalized.LastIndexOf('/')
    if ($lastSlash -lt 1) {
        return '/'
    }

    return $normalized.Substring(0, $lastSlash)
}

function Ensure-LocalDirectory {
    param(
        [Parameter(Mandatory = $true)][string]$Path
    )

    if (-not (Test-Path $Path)) {
        New-Item -ItemType Directory -Path $Path -Force | Out-Null
    }
}

function Normalize-ComparePath {
    param(
        [Parameter(Mandatory = $true)][string]$Path
    )

    $fullPath = [System.IO.Path]::GetFullPath($Path)
    return (($fullPath -replace '\\', '/').TrimEnd('/')).ToLowerInvariant()
}

function Get-DeployConfig {
    $projectRoot = Get-DeployProjectRoot
    $configPath = Join-Path $projectRoot 'scripts\deploy_config.ps1'
    if (-not (Test-Path $configPath)) {
        throw "Missing local deploy config: $configPath`nCreate it from scripts\deploy_config.example.ps1."
    }

    $config = & $configPath
    if (-not ($config -is [hashtable])) {
        throw 'deploy_config.ps1 must return a PowerShell hashtable.'
    }

    $defaults = @{
        Protocol = 'ftps'
        PortNumber = 21
        RemoteRoot = '/www'
        ProjectRoot = $projectRoot
        LocalPublicRoot = Join-Path $projectRoot 'public'
        DeployBackupRoot = Join-Path $projectRoot '.deploy_backups'
        DeployStateRoot = Join-Path $projectRoot '.deploy_state'
        DeployLogRoot = Join-Path $projectRoot '.deploy_logs'
        WinScpAssemblyPath = ''
        WinScpExecutablePath = ''
        TimeoutInSeconds = 30
    }

    foreach ($key in $defaults.Keys) {
        if (-not $config.ContainsKey($key) -or [string]::IsNullOrWhiteSpace([string]$config[$key])) {
            $config[$key] = $defaults[$key]
        }
    }

    $required = @('HostName', 'UserName', 'Password', 'RemoteRoot', 'ProjectRoot', 'LocalPublicRoot')
    foreach ($key in $required) {
        if ([string]::IsNullOrWhiteSpace([string]$config[$key])) {
            throw "Missing required deploy config value: $key"
        }
    }

    $config.ProjectRoot = (Resolve-Path $config.ProjectRoot).Path
    $config.LocalPublicRoot = (Resolve-Path $config.LocalPublicRoot).Path
    if (-not (Test-Path $config.LocalPublicRoot)) {
        throw "Local public root does not exist: $($config.LocalPublicRoot)"
    }

    Ensure-LocalDirectory -Path $config.DeployBackupRoot
    Ensure-LocalDirectory -Path $config.DeployStateRoot
    Ensure-LocalDirectory -Path $config.DeployLogRoot

    return $config
}

function Test-DeployConfigValue {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config,
        [Parameter(Mandatory = $true)][string]$Key
    )

    return -not [string]::IsNullOrWhiteSpace([string]$Config[$Key])
}

function Resolve-WinScpPaths {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config
    )

    $assemblyCandidates = @()
    $executableCandidates = @()

    if (-not [string]::IsNullOrWhiteSpace([string]$Config.WinScpAssemblyPath)) {
        $assemblyCandidates += [string]$Config.WinScpAssemblyPath
    }
    if (-not [string]::IsNullOrWhiteSpace([string]$Config.WinScpExecutablePath)) {
        $executableCandidates += [string]$Config.WinScpExecutablePath
    }

    $commonRoots = @(
        "${env:ProgramFiles(x86)}\WinSCP",
        "${env:ProgramFiles}\WinSCP"
    ) | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }

    foreach ($root in $commonRoots) {
        $assemblyCandidates += (Join-Path $root 'WinSCPnet.dll')
        $executableCandidates += (Join-Path $root 'WinSCP.com')
        $executableCandidates += (Join-Path $root 'WinSCP.exe')
    }

    $assemblyPath = $assemblyCandidates | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
    if (-not $assemblyPath) {
        throw 'WinSCP .NET assembly was not found. Install WinSCP or set WinScpAssemblyPath in deploy_config.ps1.'
    }

    $assemblyDirectory = Split-Path $assemblyPath -Parent
    $executableCandidates += (Join-Path $assemblyDirectory 'WinSCP.com')
    $executableCandidates += (Join-Path $assemblyDirectory 'WinSCP.exe')
    $executablePath = $executableCandidates | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
    if (-not $executablePath) {
        throw 'WinSCP executable was not found. Set WinScpExecutablePath in deploy_config.ps1.'
    }

    return @{
        AssemblyPath = $assemblyPath
        ExecutablePath = $executablePath
    }
}

function Get-DeployPreflightReport {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config
    )

    $paths = Resolve-WinScpPaths -Config $Config

    return [pscustomobject]@{
        ConfigPath = Join-Path (Get-DeployProjectRoot) 'scripts\deploy_config.ps1'
        ConfigExists = Test-Path (Join-Path (Get-DeployProjectRoot) 'scripts\deploy_config.ps1')
        WinScpAssemblyPath = [string]$paths.AssemblyPath
        WinScpAssemblyExists = Test-Path ([string]$paths.AssemblyPath)
        WinScpExecutablePath = [string]$paths.ExecutablePath
        WinScpExecutableExists = Test-Path ([string]$paths.ExecutablePath)
        LocalPublicRoot = [string]$Config.LocalPublicRoot
        LocalPublicRootExists = Test-Path ([string]$Config.LocalPublicRoot)
        RemoteRoot = [string]$Config.RemoteRoot
        RemoteRootConfigured = Test-DeployConfigValue -Config $Config -Key 'RemoteRoot'
        FtpHostConfigured = Test-DeployConfigValue -Config $Config -Key 'HostName'
        FtpUserConfigured = Test-DeployConfigValue -Config $Config -Key 'UserName'
        FtpPasswordConfigured = Test-DeployConfigValue -Config $Config -Key 'Password'
        Protocol = [string]$Config.Protocol
    }
}

function Show-DeployPreflight {
    param(
        [Parameter(Mandatory = $true)]$Report
    )

    Write-Host 'Deploy self-check:'
    Write-Host ("- deploy_config.ps1: {0}" -f ($(if ($Report.ConfigExists) { 'OK' } else { 'CHYBA' })))
    Write-Host ("- WinSCP DLL: {0}" -f ($(if ($Report.WinScpAssemblyExists) { $Report.WinScpAssemblyPath } else { 'CHYBA' })))
    Write-Host ("- WinSCP EXE/COM: {0}" -f ($(if ($Report.WinScpExecutableExists) { $Report.WinScpExecutablePath } else { 'CHYBA' })))
    Write-Host ("- local public root: {0}" -f ($(if ($Report.LocalPublicRootExists) { $Report.LocalPublicRoot } else { 'CHYBA' })))
    Write-Host ("- remote root: {0}" -f ($(if ($Report.RemoteRootConfigured) { $Report.RemoteRoot } else { 'CHYBA' })))
    Write-Host ("- FTP host: {0}" -f ($(if ($Report.FtpHostConfigured) { 'OK' } else { 'CHYBA' })))
    Write-Host ("- FTP user: {0}" -f ($(if ($Report.FtpUserConfigured) { 'OK' } else { 'CHYBA' })))
    Write-Host ("- FTP password: {0}" -f ($(if ($Report.FtpPasswordConfigured) { 'OK' } else { 'CHYBA' })))
    Write-Host ("- protocol: {0}" -f $Report.Protocol)
    Write-Host ''
}

function Import-WinScpAssembly {
    param(
        [Parameter(Mandatory = $true)][string]$AssemblyPath
    )

    if (-not ('WinSCP.Session' -as [type])) {
        Add-Type -Path $AssemblyPath
    }
}

function New-WinScpSessionOptions {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config
    )

    $protocol = ([string]$Config.Protocol).ToLowerInvariant()
    $options = New-Object WinSCP.SessionOptions
    switch ($protocol) {
        'ftp' {
            $options.Protocol = [WinSCP.Protocol]::Ftp
            $options.FtpSecure = [WinSCP.FtpSecure]::None
        }
        'ftps' {
            $options.Protocol = [WinSCP.Protocol]::Ftp
            $options.FtpSecure = [WinSCP.FtpSecure]::Explicit
        }
        'sftp' {
            $options.Protocol = [WinSCP.Protocol]::Sftp
        }
        default {
            throw "Unsupported deploy protocol: $protocol"
        }
    }

    $options.HostName = [string]$Config.HostName
    $options.UserName = [string]$Config.UserName
    $options.Password = [string]$Config.Password
    $options.PortNumber = [int]$Config.PortNumber

    if ($protocol -eq 'sftp' -and -not [string]::IsNullOrWhiteSpace([string]$Config.SshHostKeyFingerprint)) {
        $options.SshHostKeyFingerprint = [string]$Config.SshHostKeyFingerprint
    }

    if (($protocol -eq 'ftp' -or $protocol -eq 'ftps') -and -not [string]::IsNullOrWhiteSpace([string]$Config.TlsHostCertificateFingerprint)) {
        $options.TlsHostCertificateFingerprint = [string]$Config.TlsHostCertificateFingerprint
    }

    return $options
}

function New-WinScpSession {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config,
        [Parameter(Mandatory = $true)][string]$ExecutablePath
    )

    $session = New-Object WinSCP.Session
    $session.ExecutablePath = $ExecutablePath
    $session.SessionLogPath = Join-Path $Config.DeployStateRoot 'winscp-session.log'
    $session.Timeout = New-TimeSpan -Seconds ([int]$Config.TimeoutInSeconds)
    $session.Open((New-WinScpSessionOptions -Config $Config))
    return $session
}

function New-BinaryTransferOptions {
    $transferOptions = New-Object WinSCP.TransferOptions
    $transferOptions.TransferMode = [WinSCP.TransferMode]::Binary
    return $transferOptions
}

function Ensure-RemoteDirectory {
    param(
        [Parameter(Mandatory = $true)][WinSCP.Session]$Session,
        [Parameter(Mandatory = $true)][string]$RemoteDirectory
    )

    $normalized = ($RemoteDirectory -replace '\\', '/').Trim()
    if ($normalized -eq '' -or $normalized -eq '/') {
        return
    }

    $segments = $normalized.Trim('/').Split('/', [System.StringSplitOptions]::RemoveEmptyEntries)
    $current = ''
    foreach ($segment in $segments) {
        $current += '/' + $segment
        if (-not $Session.FileExists($current)) {
            $null = $Session.CreateDirectory($current)
        }
    }
}

function Get-LastDeployStateFile {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config
    )

    return Join-Path $Config.DeployStateRoot 'last_deploy_commit.txt'
}

function Get-DefaultDeployBaseRef {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config
    )

    $stateFile = Get-LastDeployStateFile -Config $Config
    if (Test-Path $stateFile) {
        $saved = (Get-Content -Path $stateFile -ErrorAction SilentlyContinue | Select-Object -First 1)
        if (-not [string]::IsNullOrWhiteSpace([string]$saved)) {
            return [string]$saved
        }
    }

    $headParent = git -C $Config.ProjectRoot rev-parse --verify HEAD~1 2>$null
    if ($LASTEXITCODE -eq 0 -and -not [string]::IsNullOrWhiteSpace([string]$headParent)) {
        return 'HEAD~1'
    }

    return 'HEAD'
}

function Get-HeadCommit {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config
    )

    $head = git -C $Config.ProjectRoot rev-parse --verify HEAD 2>$null
    if ($LASTEXITCODE -ne 0) {
        return $null
    }

    return ($head | Select-Object -First 1)
}

function Convert-ToRelativePublicPath {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config,
        [Parameter(Mandatory = $true)][string]$InputPath
    )

    $rawPath = ([string]$InputPath).Trim().Trim('"')
    if ([string]::IsNullOrWhiteSpace($rawPath)) {
        throw 'Changed file path is empty.'
    }

    $projectRoot = [System.IO.Path]::GetFullPath($Config.ProjectRoot)
    $publicRoot = [System.IO.Path]::GetFullPath($Config.LocalPublicRoot)
    $projectRootCompare = Normalize-ComparePath -Path $projectRoot
    $publicRootCompare = Normalize-ComparePath -Path $publicRoot
    $projectFolderName = [System.IO.Path]::GetFileName($projectRoot.TrimEnd('\', '/'))

    $normalizedProjectRelative = $null
    $candidate = ($rawPath -replace '\\', '/').Trim()
    if ($candidate.StartsWith('./')) {
        $candidate = $candidate.Substring(2)
    }

    if ([System.IO.Path]::IsPathRooted($rawPath)) {
        $absoluteCandidate = [System.IO.Path]::GetFullPath($rawPath)
        $absoluteCompare = Normalize-ComparePath -Path $absoluteCandidate

        if ($absoluteCompare -eq $publicRootCompare) {
            $normalizedProjectRelative = 'public'
        } elseif ($absoluteCompare.StartsWith($publicRootCompare + '/')) {
            $subPath = $absoluteCandidate.Substring($publicRoot.Length).TrimStart('\', '/')
            $normalizedProjectRelative = 'public/' + ($subPath -replace '\\', '/')
        } elseif ($absoluteCompare.StartsWith($projectRootCompare + '/')) {
            $normalizedProjectRelative = $absoluteCandidate.Substring($projectRoot.Length).TrimStart('\', '/') -replace '\\', '/'
        } else {
            throw "Path is outside project root: $rawPath"
        }
    }

    if (-not $normalizedProjectRelative) {
        $segments = @(($candidate -split '/')) | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }

        if ($segments.Count -ge 2 -and $segments[0].ToLowerInvariant() -eq $projectFolderName.ToLowerInvariant()) {
            $segments = @($segments[1..($segments.Count - 1)])
        }

        $publicIndex = -1
        for ($i = 0; $i -lt $segments.Count; $i++) {
            if ($segments[$i].ToLowerInvariant() -eq 'public') {
                $publicIndex = $i
                break
            }
        }

        if ($publicIndex -ge 0) {
            $normalizedProjectRelative = (($segments[$publicIndex..($segments.Count - 1)]) -join '/')
        } else {
            $normalizedProjectRelative = ($segments -join '/')
        }
    }

    $normalizedProjectRelative = ($normalizedProjectRelative -replace '\\', '/').TrimStart('/')
    if ($normalizedProjectRelative -eq 'public') {
        throw "Path points to public root, not to a file: $rawPath"
    }

    $localCandidate = [System.IO.Path]::GetFullPath(
        (Join-Path $projectRoot ($normalizedProjectRelative -replace '/', '\'))
    )
    $localCandidateCompare = Normalize-ComparePath -Path $localCandidate

    $isInsidePublic = $localCandidateCompare.StartsWith($publicRootCompare + '/') -or $localCandidateCompare -eq $publicRootCompare
    if (-not $isInsidePublic) {
        throw "Path is outside public/: $rawPath"
    }

    if (-not (Test-Path $localCandidate -PathType Leaf)) {
        throw "Changed file does not exist locally: $localCandidate"
    }

    $relativePublicPath = $localCandidate.Substring($publicRoot.Length).TrimStart('\', '/')
    if ([string]::IsNullOrWhiteSpace($relativePublicPath)) {
        throw "Path points to public root, not to a file: $rawPath"
    }

    return [pscustomobject]@{
        RawPath = $rawPath
        ProjectRelativePath = $normalizedProjectRelative
        RelativePublicPath = ($relativePublicPath -replace '\\', '/')
        LocalPath = $localCandidate
        RemotePath = Join-RemotePath -RemoteRoot $Config.RemoteRoot -RelativePath $relativePublicPath
    }
}

function Get-ChangedPublicFiles {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config,
        [string]$SinceRef,
        [string[]]$ExplicitFiles
    )

    $allFiles = @()
    $baseRef = $null

    if ($ExplicitFiles -and $ExplicitFiles.Count -gt 0) {
        $baseRef = 'explicit-files'
        $allFiles = @($ExplicitFiles)
    } else {
        $baseRef = if ([string]::IsNullOrWhiteSpace($SinceRef)) {
            Get-DefaultDeployBaseRef -Config $Config
        } else {
            $SinceRef
        }

        $diffFiles = @(git -C $Config.ProjectRoot diff --name-only --diff-filter=ACMR $baseRef -- public 2>$null)
        if ($LASTEXITCODE -ne 0) {
            throw "Git diff failed for base ref '$baseRef'."
        }

        $untrackedFiles = @(git -C $Config.ProjectRoot ls-files --others --exclude-standard -- public 2>$null)
        if ($LASTEXITCODE -ne 0) {
            throw 'Git ls-files failed while collecting untracked public files.'
        }

        $allFiles = @($diffFiles + $untrackedFiles)
    }

    $allFiles = @($allFiles) |
        Where-Object { -not [string]::IsNullOrWhiteSpace($_) } |
        ForEach-Object { ($_ -replace '\\', '/').Trim() } |
        Sort-Object -Unique

    $items = @()
    foreach ($repoRelative in $allFiles) {
        $normalizedItem = Convert-ToRelativePublicPath -Config $Config -InputPath $repoRelative
        $items += [pscustomobject]@{
            RawPath = [string]$normalizedItem.RawPath
            RepoRelativePath = [string]$normalizedItem.ProjectRelativePath
            RelativePublicPath = [string]$normalizedItem.RelativePublicPath
            LocalPath = (Resolve-Path $normalizedItem.LocalPath).Path
            RemotePath = [string]$normalizedItem.RemotePath
        }
    }

    return [pscustomobject]@{
        BaseRef = $baseRef
        Files = $items
    }
}

function Get-ExplicitFilesFromList {
    param(
        [Parameter(Mandatory = $true)][string]$ListPath
    )

    if (-not (Test-Path $ListPath -PathType Leaf)) {
        throw "Explicit files list does not exist: $ListPath"
    }

    return @(Get-Content -Path $ListPath -ErrorAction Stop |
        ForEach-Object { ([string]$_).Trim() } |
        Where-Object { $_ -ne '' -and -not $_.StartsWith('#') })
}

function Get-LatestBackupManifest {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config
    )

    $candidates = Get-ChildItem -Path $Config.DeployBackupRoot -Directory -ErrorAction SilentlyContinue |
        Sort-Object Name -Descending

    foreach ($candidate in $candidates) {
        $manifestPath = Join-Path $candidate.FullName 'manifest.json'
        if (Test-Path $manifestPath) {
            return $manifestPath
        }
    }

    return $null
}

function Save-DeployManifest {
    param(
        [Parameter(Mandatory = $true)][string]$ManifestPath,
        [Parameter(Mandatory = $true)]$Data
    )

    Ensure-LocalDirectory -Path (Split-Path $ManifestPath -Parent)
    $json = $Data | ConvertTo-Json -Depth 8
    Set-Content -Path $ManifestPath -Value $json -Encoding UTF8
}

function New-DeployLogPath {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Config,
        [Parameter(Mandatory = $true)][string]$Prefix
    )

    $timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    return Join-Path $Config.DeployLogRoot ("{0}-{1}.log" -f $Prefix, $timestamp)
}

function Write-DeployLog {
    param(
        [Parameter(Mandatory = $true)][string]$LogPath,
        [Parameter(Mandatory = $true)][string[]]$Lines
    )

    Ensure-LocalDirectory -Path (Split-Path $LogPath -Parent)
    Set-Content -Path $LogPath -Value ($Lines -join [Environment]::NewLine) -Encoding UTF8
}
