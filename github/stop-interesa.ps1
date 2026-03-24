param(
    [switch]$Aggressive,
    [switch]$Quiet
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$stateDir = Join-Path $projectRoot '.codex-local'
$pidFile = Join-Path $stateDir 'php-server.pid'
$runtimeFile = Join-Path $stateDir 'local-runtime.json'

function Write-Status {
    param([string]$Message)
    if (-not $Quiet) {
        Write-Output $Message
    }
}

function Get-ListenerPids {
    $pids = @()

    try {
        $connections = Get-NetTCPConnection -LocalPort 5001 -State Listen -ErrorAction SilentlyContinue
        foreach ($connection in $connections) {
            if ($connection.OwningProcess) {
                $pids += [int]$connection.OwningProcess
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
                    [int]$match.Groups[1].Value
                }
            } |
            Sort-Object -Unique)
    }

    return @($pids | Sort-Object -Unique)
}

function Get-PidFilePid {
    if (-not (Test-Path $pidFile)) {
        return $null
    }

    $savedPid = Get-Content -Path $pidFile -ErrorAction SilentlyContinue | Select-Object -First 1
    if (-not $savedPid) {
        return $null
    }

    return [int]$savedPid
}

function Test-ProcessExists {
    param([int]$ProcessId)

    if ($ProcessId -le 0) {
        return $false
    }

    return [bool](Get-Process -Id $ProcessId -ErrorAction SilentlyContinue)
}

function Stop-TargetProcess {
    param([int]$ProcessId)

    if ($ProcessId -le 0) {
        return $false
    }

    $proc = Get-Process -Id $ProcessId -ErrorAction SilentlyContinue
    if (-not $proc) {
        return $false
    }

    Stop-Process -Id $ProcessId -Force -ErrorAction SilentlyContinue
    Start-Sleep -Milliseconds 300

    if (Test-ProcessExists -ProcessId $ProcessId) {
        & taskkill /PID $ProcessId /F /T | Out-Null
        Start-Sleep -Milliseconds 400
    }

    if (Test-ProcessExists -ProcessId $ProcessId) {
        cmd.exe /c "taskkill /PID $ProcessId /F /T" | Out-Null
        Start-Sleep -Milliseconds 500
    }

    return -not (Test-ProcessExists -ProcessId $ProcessId)
}

function Wait-ForPortRelease {
    param(
        [int]$Attempts,
        [int]$DelayMs
    )

    for ($attempt = 0; $attempt -lt $Attempts; $attempt++) {
        $listenerPids = @(Get-ListenerPids)
        if ($listenerPids.Count -eq 0) {
            return $true
        }

        foreach ($processId in $listenerPids) {
            [void](Stop-TargetProcess -ProcessId $processId)
        }

        $freshListenerPids = @(Get-ListenerPids)
        foreach ($processId in $freshListenerPids) {
            [void](Stop-TargetProcess -ProcessId $processId)
        }

        Start-Sleep -Milliseconds $DelayMs
    }

    return (@(Get-ListenerPids).Count -eq 0)
}

$targets = @()
$pidFilePid = Get-PidFilePid
if ($null -ne $pidFilePid) {
    $targets += $pidFilePid
}
$targets += @(Get-ListenerPids)
$targets = @($targets | Where-Object { $_ -gt 0 } | Sort-Object -Unique)

if ($targets.Count -eq 0) {
    Write-Status 'Port 5001 je uz volny.'
    Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
    Remove-Item -Path $runtimeFile -Force -ErrorAction SilentlyContinue
    exit 0
}

Write-Status 'Ukoncujem stary lokalny server na porte 5001...'
foreach ($processId in $targets) {
    [void](Stop-TargetProcess -ProcessId $processId)
}

$attempts = if ($Aggressive) { 16 } else { 8 }
$released = Wait-ForPortRelease -Attempts $attempts -DelayMs 500

Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
Remove-Item -Path $runtimeFile -Force -ErrorAction SilentlyContinue

if (-not $released) {
    $listenerPids = @(Get-ListenerPids)
    $suffix = if ($listenerPids.Count -gt 0) { ' PID: ' + ($listenerPids -join ', ') } else { '' }
    throw ('Port 5001 je stale blokovany.' + $suffix)
}

Write-Status 'Port 5001 je volny.'
