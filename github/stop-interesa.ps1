$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$stateDir = Join-Path $projectRoot '.codex-local'
$pidFile = Join-Path $stateDir 'php-server.pid'

function Stop-ByPidFile {
    if (-not (Test-Path $pidFile)) {
        return $false
    }

    $savedPid = Get-Content -Path $pidFile -ErrorAction SilentlyContinue | Select-Object -First 1
    if (-not $savedPid) {
        Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
        return $false
    }

    $proc = Get-Process -Id ([int] $savedPid) -ErrorAction SilentlyContinue
    if ($proc) {
        Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
        Start-Sleep -Milliseconds 500
    }

    Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
    return [bool] $proc
}

function Stop-ByPortFallback {
    $stopped = $false
    $listenerIds = @()

    try {
        $connections = Get-NetTCPConnection -LocalPort 5001 -State Listen -ErrorAction SilentlyContinue
        foreach ($connection in $connections) {
            if ($connection.OwningProcess) {
                $listenerIds += [int]$connection.OwningProcess
            }
        }
    } catch {
    }

    if ($listenerIds.Count -eq 0) {
        $listenerIds = @(netstat -ano |
            Select-String '^\s*TCP\s+127\.0\.0\.1:5001\s+\S+\s+LISTENING\s+(\d+)\s*$' |
            ForEach-Object {
                $match = [regex]::Match($_.Line, 'LISTENING\s+(\d+)\s*$')
                if ($match.Success) {
                    [int]$match.Groups[1].Value
                }
            } |
            Sort-Object -Unique)
    }

    foreach ($processId in $listenerIds) {
        $proc = Get-Process -Id $processId -ErrorAction SilentlyContinue
        if ($proc) {
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
            if (Get-Process -Id $processId -ErrorAction SilentlyContinue) {
                & taskkill /PID $processId /F | Out-Null
            }
            $stopped = $true
        }
    }

    return $stopped
}

$stopped = Stop-ByPidFile
if (-not $stopped) {
    $stopped = Stop-ByPortFallback
}

if (Test-Path $pidFile) {
    Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
}
