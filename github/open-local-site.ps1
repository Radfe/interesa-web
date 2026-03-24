param(
    [switch]$ElevatedRetry,
    [int]$PreferredPort = 5001
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$stateDir = Join-Path $projectRoot '.codex-local'
$stdoutLog = Join-Path $stateDir 'php-server.out.log'
$stderrLog = Join-Path $stateDir 'php-server.err.log'
$pidFile = Join-Path $stateDir 'php-server.pid'
$runtimeFile = Join-Path $stateDir 'local-runtime.json'
$script:ActivePort = 5001

function Set-ActivePort {
    param([int]$Port)
    $script:ActivePort = $Port
}

function Get-SiteUrl {
    param([int]$Port = 0)
    if ($Port -le 0) {
        $Port = $script:ActivePort
    }
    return 'http://127.0.0.1:' + $Port + '/'
}

function Get-AdminUrl {
    param([int]$Port = 0)
    if ($Port -le 0) {
        $Port = $script:ActivePort
    }
    return 'http://127.0.0.1:' + $Port + '/admin'
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
    param([int]$Port = 0)
    try {
        $resp = Invoke-WebRequest -Uri (Get-SiteUrl $Port) -UseBasicParsing -TimeoutSec 2
        return $resp.StatusCode -eq 200
    } catch {
        return $false
    }
}

function Test-AdminReady {
    param([int]$Port = 0)
    try {
        $resp = Invoke-WebRequest -Uri (Get-AdminUrl $Port) -UseBasicParsing -TimeoutSec 2
        return $resp.StatusCode -ge 200 -and $resp.StatusCode -lt 400
    } catch {
        return $false
    }
}

function Test-SiteProbe {
    param([int]$Port = 0)
    try {
        $resp = Invoke-WebRequest -Uri (Get-SiteUrl $Port) -UseBasicParsing -TimeoutSec 2
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

function Test-AdminProbe {
    param([int]$Port = 0)
    try {
        $resp = Invoke-WebRequest -Uri (Get-AdminUrl $Port) -UseBasicParsing -TimeoutSec 2
        return @{
            ready = ($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 400)
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
    param([int]$Port = 0)
    if ($Port -le 0) {
        $Port = $script:ActivePort
    }

    $pids = @(netstat -ano |
        Select-String ('^\s*TCP\s+\S+:' + [regex]::Escape([string]$Port) + '\s+\S+\s+LISTENING\s+(\d+)\s*$') |
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
    param([int]$Port = 0)
    if ($Port -le 0) {
        $Port = $script:ActivePort
    }

    $listenerPids = @(Get-ListenerPids $Port)
    $pidSuffix = ''
    if ($listenerPids.Count -gt 0) {
        $pidSuffix = ' PID: ' + ($listenerPids -join ', ')
    }

    return 'Port ' + $Port + ' je stale blokovany starym lokalnym serverom. Zavri stare okna servera alebo restartuj pocitac a spusti start-interesa znova.' + $pidSuffix
}

function Stop-ListenerPids {
    param([int]$Port = 0)
    if ($Port -le 0) {
        $Port = $script:ActivePort
    }

    $listenerPids = @(Get-ListenerPids $Port)
    foreach ($processId in $listenerPids) {
        if ([string]::IsNullOrWhiteSpace([string]$processId)) {
            continue
        }
        Stop-Process -Id ([int]$processId) -Force -ErrorAction SilentlyContinue
        Start-Sleep -Milliseconds 300
        if (Get-Process -Id ([int]$processId) -ErrorAction SilentlyContinue) {
            & taskkill /PID ([int]$processId) /F /T | Out-Null
            Start-Sleep -Milliseconds 300
        }
    }
}

function Invoke-ElevatedRetry {
    param([int]$Port = 0)
    if ($Port -le 0) {
        $Port = $script:ActivePort
    }

    $scriptPath = $MyInvocation.MyCommand.Path
    $args = @(
        '-NoProfile',
        '-ExecutionPolicy', 'Bypass',
        '-File', ('"' + $scriptPath + '"'),
        '-ElevatedRetry',
        '-PreferredPort', $Port
    )

    $process = Start-Process -FilePath 'powershell.exe' -ArgumentList $args -Verb RunAs -Wait -PassThru
    if ($null -eq $process) {
        throw 'Nepodarilo sa spustit elevated retry pre uvolnenie portu.'
    }
    if ($process.ExitCode -ne 0) {
        throw (New-PortBlockedMessage $Port)
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
    param([int]$Port = 0)
    if ($Port -le 0) {
        $Port = $script:ActivePort
    }

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
    $psi.Arguments = ('-S 127.0.0.1:{2} -t "{0}" "{1}"' -f $publicRoot, $routerPath, $Port)

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
        port = $Port
    }
}

function Save-RuntimeInfo {
    param([int]$Port)
    $payload = [ordered]@{
        port = $Port
        site_url = (Get-SiteUrl $Port)
        admin_url = (Get-AdminUrl $Port)
    }
    $payload | ConvertTo-Json -Depth 2 | Set-Content -Path $runtimeFile -Encoding UTF8
}

$port = 5001
Set-ActivePort $port
$lastFailureDetails = ''

$listenerPids = @(Get-ListenerPids $port)
if ($listenerPids.Count -gt 0) {
    Write-Output ('Nasiel som stary proces na ' + $port + ', ukoncujem...')
    Stop-ListenerPids $port
    $released = $false
    for ($attempt = 0; $attempt -lt 10; $attempt++) {
        Start-Sleep -Milliseconds 500
        if (@(Get-ListenerPids $port).Count -eq 0) {
            $released = $true
            break
        }
        Stop-ListenerPids $port
    }
    if (-not $released) {
        if (-not (Test-IsAdministrator) -and -not $ElevatedRetry) {
            Write-Output 'Potrebujem admin prava na uvolnenie portu, spustam znova ako admin...'
            Invoke-ElevatedRetry $port
            exit 0
        }
        throw (New-PortBlockedMessage $port)
    }
    Write-Output 'Stary server ukonceny, pokracujem...'
}

if (@(Get-ListenerPids $port).Count -gt 0) {
    throw (New-PortBlockedMessage $port)
}

Write-Output 'Spustam novy server...'
$started = Start-LocalServer $port
$startedPid = [int](Get-MapValue -Map $started -Key 'pid' -Default '0')
$selectedPort = 0

for ($attempt = 0; $attempt -lt 20; $attempt++) {
    Start-Sleep -Milliseconds 500
    if (Test-SiteReady $port -and Test-AdminReady $port) {
        $selectedPort = $port
        break
    }

    $running = Get-Process -Id $startedPid -ErrorAction SilentlyContinue
    if (-not $running) {
        break
    }
}

if ($selectedPort -le 0) {
    $probe = Test-SiteProbe $port
    $adminProbe = Test-AdminProbe $port
    $running = $false
    if ($startedPid -gt 0 -and (Get-Process -Id $startedPid -ErrorAction SilentlyContinue)) {
        $running = $true
        Stop-Process -Id $startedPid -Force -ErrorAction SilentlyContinue
    }

    $stderrText = Get-LogText -Path $stderrLog
    $stdoutText = Get-LogText -Path $stdoutLog
    $portListening = (@(Get-ListenerPids $port).Count -gt 0)
    $lastFailureDetails = @(
        'Local server did not start.'
        ('Port: ' + $port)
        ('PHP process started: ' + ($startedPid -gt 0))
        ('PID: ' + $startedPid)
        ('PHP path: ' + (Get-MapValue -Map $started -Key 'php'))
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
    throw $lastFailureDetails
}

Save-RuntimeInfo $selectedPort
Write-Output ('Server bezi na 127.0.0.1:' + $selectedPort)

try {
    Start-Process (Get-SiteUrl $selectedPort) | Out-Null
} catch {
    Write-Warning "Local server is running, but browser could not be opened automatically."
}
