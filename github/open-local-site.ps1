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

function Test-SiteProbe {
    try {
        $resp = Invoke-WebRequest -Uri $siteUrl -UseBasicParsing -TimeoutSec 2
        return @{
            ready = ($resp.StatusCode -eq 200)
            status = [string]$resp.StatusCode
            message = 'HTTP ' + [string]$resp.StatusCode
        }
    } catch {
        return @{
            ready = $false
            status = ''
            message = $_.Exception.Message
        }
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

function Get-LogText {
    param(
        [string]$Path
    )

    if (-not (Test-Path $Path)) {
        return ''
    }

    $content = Get-Content -Path $Path -Raw -ErrorAction SilentlyContinue
    if ($null -eq $content) {
        return ''
    }

    return [string]$content
}

function Get-MapValue {
    param(
        $Map,
        [string]$Key,
        [string]$Default = ''
    )

    if ($null -eq $Map) {
        return $Default
    }

    if ($Map.ContainsKey($Key) -and $null -ne $Map[$Key]) {
        return [string]$Map[$Key]
    }

    return $Default
}

function Start-LocalServer {
    $php = Get-PhpPath
    New-Item -ItemType Directory -Force -Path $stateDir | Out-Null
    Remove-StalePid
    Set-Content -Path $stdoutLog -Value '' -Encoding UTF8
    Set-Content -Path $stderrLog -Value '' -Encoding UTF8
    $publicRoot = Join-Path $projectRoot 'public'
    $routerPath = Join-Path $publicRoot 'router.php'
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $php
    $psi.WorkingDirectory = $projectRoot
    $psi.UseShellExecute = $false
    $psi.CreateNoWindow = $true
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $psi.Arguments = ('-S 127.0.0.1:5001 -t "{0}" "{1}"' -f $publicRoot, $routerPath)

    $proc = New-Object System.Diagnostics.Process
    $proc.StartInfo = $psi

    if (-not $proc.Start()) {
        throw 'Local PHP server process could not be started.'
    }

    $proc.BeginOutputReadLine()
    $proc.BeginErrorReadLine()

    Set-Content -Path $pidFile -Value $proc.Id
    return @{
        pid = $proc.Id
        php = $php
        public_root = $publicRoot
        router_path = $routerPath
    }
}

if (-not (Test-SiteReady)) {
    $listenerPids = @(Get-ListenerPids)
    if ($listenerPids.Count -gt 0) {
        throw (New-PortBlockedMessage)
    }

    $started = Start-LocalServer
    $startedPid = [int](Get-MapValue -Map $started -Key 'pid' -Default '0')

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
        $probe = Test-SiteProbe
        $listenerPids = @(Get-ListenerPids)
        if ($listenerPids.Count -gt 0 -and -not (Get-Process -Id $startedPid -ErrorAction SilentlyContinue)) {
            throw (New-PortBlockedMessage)
        }

        $running = $false
        if ($startedPid -gt 0 -and (Get-Process -Id $startedPid -ErrorAction SilentlyContinue)) {
            $running = $true
        }

        $stderrText = Get-LogText -Path $stderrLog
        $stdoutText = Get-LogText -Path $stdoutLog
        $portListening = (@(Get-ListenerPids).Count -gt 0)
        $failureDetails = @(
            'Local server did not start.'
            ('PHP process started: ' + ($startedPid -gt 0))
            ('PID: ' + $startedPid)
            ('PHP path: ' + (Get-MapValue -Map $started -Key 'php'))
            ('Document root: ' + (Get-MapValue -Map $started -Key 'public_root'))
            ('Router: ' + (Get-MapValue -Map $started -Key 'router_path'))
            ('Process still running: ' + $running)
            ('Port 5001 listening: ' + $portListening)
            ('Site probe: ' + (Get-MapValue -Map $probe -Key 'message'))
            ''
            'STDERR:'
            $stderrText
            ''
            'STDOUT:'
            $stdoutText
        ) -join "`n"

        if (-not [string]::IsNullOrWhiteSpace($stderrText)) {
            throw $failureDetails
        }

        throw $failureDetails
    }
}

try {
    Start-Process $siteUrl | Out-Null
} catch {
    Write-Warning "Local server is running, but browser could not be opened automatically."
}
