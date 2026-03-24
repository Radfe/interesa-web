param(
    [switch]$ElevatedRetry
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$stateDir = Join-Path $projectRoot '.codex-local'
$stdoutLog = Join-Path $stateDir 'php-server.out.log'
$stderrLog = Join-Path $stateDir 'php-server.err.log'
$pidFile = Join-Path $stateDir 'php-server.pid'
$runtimeFile = Join-Path $stateDir 'local-runtime.json'
$script:ActivePort = 5001

function Get-SiteUrl {
    return 'http://127.0.0.1:5001/'
}

function Get-AdminUrl {
    return 'http://127.0.0.1:5001/admin'
}

function Test-IsAdministrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Get-PhpPath {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCommand) {
        return $phpCommand.Source
    }

    foreach ($candidate in @('C:\php\php.exe')) {
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    throw 'PHP executable was not found. Install PHP or add it to PATH.'
}

function Test-SiteReady {
    try {
        $resp = Invoke-WebRequest -Uri (Get-SiteUrl) -UseBasicParsing -TimeoutSec 2
        return $resp.StatusCode -eq 200
    } catch {
        return $false
    }
}

function Test-AdminReady {
    try {
        $resp = Invoke-WebRequest -Uri (Get-AdminUrl) -UseBasicParsing -TimeoutSec 2
        return $resp.StatusCode -ge 200 -and $resp.StatusCode -lt 400
    } catch {
        return $false
    }
}

function Test-SiteProbe {
    try {
        $resp = Invoke-WebRequest -Uri (Get-SiteUrl) -UseBasicParsing -TimeoutSec 2
        return @{
            ready = ($resp.StatusCode -eq 200)
            message = 'HTTP ' + [string]$resp.StatusCode
        }
    } catch {
        return @{
            ready = $false
            message = $_.Exception.Message
        }
    }
}

function Test-AdminProbe {
    try {
        $resp = Invoke-WebRequest -Uri (Get-AdminUrl) -UseBasicParsing -TimeoutSec 2
        return @{
            ready = ($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 400)
            message = 'HTTP ' + [string]$resp.StatusCode
        }
    } catch {
        return @{
            ready = $false
            message = $_.Exception.Message
        }
    }
}

function Get-ListenerPids {
    $pids = @()

    try {
        $connections = Get-NetTCPConnection -LocalPort 5001 -State Listen -ErrorAction SilentlyContinue
        foreach ($connection in $connections) {
            if ($connection.OwningProcess) {
                $pids += [string]([int]$connection.OwningProcess)
            }
        }
    } catch {
    }

    if ($pids.Count -eq 0) {
        $pids = @(netstat -ano |
            Select-String '^\s*TCP\s+\S+:5001\s+\S+\s+LISTENING\s+(\d+)\s*$' |
            ForEach-Object {
                $match = [regex]::Match($_.Line, 'LISTENING\s+(\d+)\s*$')
                if ($match.Success) {
                    [string]$match.Groups[1].Value
                }
            } |
            Where-Object { $_ -ne '' } |
            Sort-Object -Unique)
    }

    return @($pids | Sort-Object -Unique)
}

function New-PortBlockedMessage {
    $listenerPids = @(Get-ListenerPids)
    $pidSuffix = ''
    if ($listenerPids.Count -gt 0) {
        $pidSuffix = ' PID: ' + ($listenerPids -join ', ')
    }

    return 'Port 5001 je stale blokovany. Zavri stare okna servera alebo restartuj pocitac a spusti start-interesa znova.' + $pidSuffix
}

function Stop-ListenerPids {
    $listenerPids = @(Get-ListenerPids)
    foreach ($processId in $listenerPids) {
        if ([string]::IsNullOrWhiteSpace([string]$processId)) {
            continue
        }

        Stop-Process -Id ([int]$processId) -Force -ErrorAction SilentlyContinue
        Start-Sleep -Milliseconds 300

        if (Get-Process -Id ([int]$processId) -ErrorAction SilentlyContinue) {
            & taskkill /PID ([int]$processId) /F /T | Out-Null
            Start-Sleep -Milliseconds 400
        }
    }
}

function Wait-ForPortRelease {
    param(
        [int]$Attempts = 10,
        [int]$DelayMs = 500
    )

    for ($attempt = 0; $attempt -lt $Attempts; $attempt++) {
        if (@(Get-ListenerPids).Count -eq 0) {
            return $true
        }
        Stop-ListenerPids
        Start-Sleep -Milliseconds $DelayMs
    }

    return (@(Get-ListenerPids).Count -eq 0)
}

function Invoke-ElevatedRetry {
    $scriptPath = $MyInvocation.MyCommand.Path
    $args = @(
        '-NoProfile',
        '-ExecutionPolicy', 'Bypass',
        '-File', ('"' + $scriptPath + '"'),
        '-ElevatedRetry'
    )

    $process = Start-Process -FilePath 'powershell.exe' -ArgumentList $args -Verb RunAs -Wait -PassThru
    if ($null -eq $process) {
        throw 'Nepodarilo sa spustit elevated retry pre uvolnenie portu.'
    }
    if ($process.ExitCode -ne 0) {
        throw (New-PortBlockedMessage)
    }
}

function Remove-StalePid {
    if (-not (Test-Path $pidFile)) {
        return
    }

    $savedPid = Get-Content -Path $pidFile -ErrorAction SilentlyContinue | Select-Object -First 1
    if (-not $savedPid) {
        Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
        return
    }

    if (-not (Get-Process -Id ([int]$savedPid) -ErrorAction SilentlyContinue)) {
        Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
    }
}

function Get-LogText {
    param([string]$Path)

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
    $publicRoot = Join-Path $projectRoot 'public'
    $routerPath = Join-Path $publicRoot 'router.php'
    $argumentList = @('-S', '127.0.0.1:5001', '-t', $publicRoot, $routerPath)
    $escapedArgs = @($argumentList | ForEach-Object {
        if ([string]$_ -match '\s') {
            '"' + [string]$_ + '"'
        } else {
            [string]$_
        }
    })
    $commandLine = '"' + $php + '" ' + ($escapedArgs -join ' ')

    Set-Content -Path $stdoutLog -Value ("Spawn command: " + $commandLine + "`r`nWorking directory: " + $projectRoot + "`r`nDocument root: " + $publicRoot + "`r`nRouter path: " + $routerPath + "`r`n---`r`n") -Encoding UTF8
    Set-Content -Path $stderrLog -Value ("Spawn command: " + $commandLine + "`r`nWorking directory: " + $projectRoot + "`r`nDocument root: " + $publicRoot + "`r`nRouter path: " + $routerPath + "`r`n---`r`n") -Encoding UTF8

    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $php
    $psi.WorkingDirectory = $projectRoot
    $psi.UseShellExecute = $false
    $psi.CreateNoWindow = $true
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $psi.Arguments = ($argumentList -join ' ')

    $proc = New-Object System.Diagnostics.Process
    $proc.StartInfo = $psi
    $proc.EnableRaisingEvents = $true

    $stdoutPath = $stdoutLog
    $stderrPath = $stderrLog
    $proc.add_OutputDataReceived({
        param($sender, $eventArgs)
        if ($null -ne $eventArgs.Data) {
            Add-Content -Path $stdoutPath -Value $eventArgs.Data -Encoding UTF8
        }
    })
    $proc.add_ErrorDataReceived({
        param($sender, $eventArgs)
        if ($null -ne $eventArgs.Data) {
            Add-Content -Path $stderrPath -Value $eventArgs.Data -Encoding UTF8
        }
    })

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
        working_directory = $projectRoot
        command_line = $commandLine
        process = $proc
    }
}

function Save-RuntimeInfo {
    $payload = [ordered]@{
        port = 5001
        site_url = (Get-SiteUrl)
        admin_url = (Get-AdminUrl)
    }
    $payload | ConvertTo-Json -Depth 2 | Set-Content -Path $runtimeFile -Encoding UTF8
}

$listenerPids = @(Get-ListenerPids)
if ($listenerPids.Count -gt 0) {
    Write-Output 'Nasiel som stary proces na 5001, ukoncujem...'
    Stop-ListenerPids
    if (-not (Wait-ForPortRelease -Attempts 10 -DelayMs 500)) {
        if (-not (Test-IsAdministrator) -and -not $ElevatedRetry) {
            Write-Output 'Potrebujem admin prava na uvolnenie portu, spustam znova ako admin...'
            Invoke-ElevatedRetry
            exit 0
        }
        throw (New-PortBlockedMessage)
    }
    Write-Output 'Stary server ukonceny, pokracujem...'
}

if (@(Get-ListenerPids).Count -gt 0) {
    throw (New-PortBlockedMessage)
}

Write-Output 'Spustam novy server...'
$started = Start-LocalServer
$startedPid = [int](Get-MapValue -Map $started -Key 'pid' -Default '0')
$serverReady = $false

for ($attempt = 0; $attempt -lt 20; $attempt++) {
    Start-Sleep -Milliseconds 500
    if (Test-SiteReady -and Test-AdminReady) {
        $serverReady = $true
        break
    }

    if (-not (Get-Process -Id $startedPid -ErrorAction SilentlyContinue)) {
        break
    }
}

if (-not $serverReady) {
    $probe = Test-SiteProbe
    $adminProbe = Test-AdminProbe
    $running = $false
    if ($startedPid -gt 0 -and (Get-Process -Id $startedPid -ErrorAction SilentlyContinue)) {
        $running = $true
        Stop-Process -Id $startedPid -Force -ErrorAction SilentlyContinue
    }

    $stderrText = Get-LogText -Path $stderrLog
    $stdoutText = Get-LogText -Path $stdoutLog
    $portListening = (@(Get-ListenerPids).Count -gt 0)
    $failureDetails = @(
        'Local server did not start.'
        'Port: 5001'
        ('PHP process started: ' + ($startedPid -gt 0))
        ('PID: ' + $startedPid)
        ('PHP path: ' + (Get-MapValue -Map $started -Key 'php'))
        ('Spawn command: ' + (Get-MapValue -Map $started -Key 'command_line'))
        ('Working directory: ' + (Get-MapValue -Map $started -Key 'working_directory'))
        ('Document root: ' + (Get-MapValue -Map $started -Key 'public_root'))
        ('Router: ' + (Get-MapValue -Map $started -Key 'router_path'))
        ('Process still running: ' + $running)
        ('Port listening: ' + $portListening)
        ('Site probe: ' + (Get-MapValue -Map $probe -Key 'message'))
        ('Admin probe: ' + (Get-MapValue -Map $adminProbe -Key 'message'))
        ''
        'STDERR:'
        $stderrText
        ''
        'STDOUT:'
        $stdoutText
    ) -join "`n"

    throw $failureDetails
}

Save-RuntimeInfo
Write-Output 'Server bezi na 127.0.0.1:5001'

try {
    Start-Process (Get-SiteUrl) | Out-Null
} catch {
    Write-Warning 'Local server is running, but browser could not be opened automatically.'
}
