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
    $connections = Get-NetTCPConnection -LocalPort 5001 -State Listen -ErrorAction SilentlyContinue
    foreach ($connection in $connections) {
        $proc = Get-Process -Id $connection.OwningProcess -ErrorAction SilentlyContinue
        if ($proc) {
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
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
