$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$siteUrl = 'http://127.0.0.1:5000/'
$stateDir = Join-Path $projectRoot '.codex-local'
$stdoutLog = Join-Path $stateDir 'php-server.out.log'
$stderrLog = Join-Path $stateDir 'php-server.err.log'
$pidFile = Join-Path $stateDir 'php-server.pid'

function Get-PhpPath {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCommand) {
        return $phpCommand.Source
    }

    $fallbacks = @(
        'C:\php\php.exe'
    )

    foreach ($candidate in $fallbacks) {
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    throw 'PHP executable was not found. Install PHP or add it to PATH.'
}

function Test-SiteReady {
    try {
        $resp = Invoke-WebRequest -Uri $siteUrl -UseBasicParsing -TimeoutSec 2
        return $resp.StatusCode -eq 200
    } catch {
        return $false
    }
}

function Get-SavedServerPid {
    if (-not (Test-Path $pidFile)) {
        return $null
    }

    $savedPid = Get-Content -Path $pidFile -ErrorAction SilentlyContinue | Select-Object -First 1
    if (-not $savedPid) {
        return $null
    }

    return [int] $savedPid
}

function Remove-StalePid {
    $savedPid = Get-SavedServerPid
    if (-not $savedPid) {
        Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
        return
    }

    $existing = Get-Process -Id $savedPid -ErrorAction SilentlyContinue
    if (-not $existing) {
        Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
    }
}

function Start-LocalServer {
    $php = Get-PhpPath
    New-Item -ItemType Directory -Force -Path $stateDir | Out-Null
    Remove-StalePid

    [System.Environment]::SetEnvironmentVariable('PATH', $null, 'Process')

    $startParams = @{
        FilePath = $php
        ArgumentList = @('-S', '127.0.0.1:5000', '-t', 'public', 'public/router.php')
        WorkingDirectory = $projectRoot
        RedirectStandardOutput = $stdoutLog
        RedirectStandardError = $stderrLog
        PassThru = $true
    }

    $proc = Start-Process @startParams
    Set-Content -Path $pidFile -Value $proc.Id
    return $proc.Id
}

if (-not (Test-SiteReady)) {
    $startedPid = Start-LocalServer

    for ($attempt = 0; $attempt -lt 20; $attempt++) {
        Start-Sleep -Milliseconds 500
        if (Test-SiteReady) {
            break
        }

        $running = Get-Process -Id $startedPid -ErrorAction SilentlyContinue
        if (-not $running) {
            break
        }
    }

    if (-not (Test-SiteReady)) {
        $stderr = ''
        if (Test-Path $stderrLog) {
            $stderr = Get-Content -Path $stderrLog -Raw
        }

        if ($stderr.Trim() -ne '') {
            throw "Local server did not start.`n`n$stderr"
        }

        throw 'Local server did not start.'
    }
}

Start-Process $siteUrl | Out-Null