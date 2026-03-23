$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$siteUrl = 'http://127.0.0.1:5001/'
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

function Get-ListenerPids {
    $pids = @(netstat -ano |
        Select-String '^\s*TCP\s+127\.0\.0\.1:5001\s+\S+\s+LISTENING\s+(\d+)\s*$' |
        ForEach-Object {
            $match = [regex]::Match($_.Line, 'LISTENING\s+(\d+)\s*$')
            if ($match.Success) {
                [string]$match.Groups[1].Value
            }
        } |
        Where-Object { $_ -ne '' } |
        Sort-Object -Unique)

    return $pids
}

function New-PortBlockedMessage {
    $listenerPids = @(Get-ListenerPids)
    $pidSuffix = ''
    if ($listenerPids.Count -gt 0) {
        $pidSuffix = ' PID: ' + ($listenerPids -join ', ')
    }

    return 'Port 5001 je stale blokovany starym lokalnym serverom. Zavri stare okna servera alebo restartuj pocitac a spusti start-interesa znova.' + $pidSuffix
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
    $publicRoot = Join-Path $projectRoot 'public'
    $routerPath = Join-Path $publicRoot 'router.php'
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $php
    $psi.WorkingDirectory = $projectRoot
    $psi.UseShellExecute = $false
    $psi.CreateNoWindow = $true
    $psi.Arguments = ('-S 127.0.0.1:5001 -t "{0}" "{1}"' -f $publicRoot, $routerPath)

    $proc = New-Object System.Diagnostics.Process
    $proc.StartInfo = $psi

    if (-not $proc.Start()) {
        throw 'Local PHP server process could not be started.'
    }

    Set-Content -Path $pidFile -Value $proc.Id
    return $proc.Id
}

if (-not (Test-SiteReady)) {
    $listenerPids = @(Get-ListenerPids)
    if ($listenerPids.Count -gt 0) {
        throw (New-PortBlockedMessage)
    }

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
        $listenerPids = @(Get-ListenerPids)
        if ($listenerPids.Count -gt 0 -and -not (Get-Process -Id $startedPid -ErrorAction SilentlyContinue)) {
            throw (New-PortBlockedMessage)
        }

        $stderr = ''
        if (Test-Path $stderrLog) {
            $stderr = Get-Content -Path $stderrLog -Raw
        }
        $stderrText = ''
        if ($null -ne $stderr) {
            $stderrText = [string]$stderr
        }

        if (-not [string]::IsNullOrWhiteSpace($stderrText)) {
            throw "Local server did not start.`n`n$stderrText"
        }

        throw 'Local server did not start.'
    }
}

try {
    Start-Process $siteUrl | Out-Null
} catch {
    Write-Warning "Local server is running, but browser could not be opened automatically."
}
