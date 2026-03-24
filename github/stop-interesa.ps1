param(
    [switch]$Aggressive,
    [switch]$Quiet,
    [switch]$ElevatedRetry
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

function Test-IsAdministrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
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

function Get-BlockingProcessInfo {
    param([int]$ProcessId)

    $info = [ordered]@{
        ProcessId = $ProcessId
        ProcessName = ''
        Path = ''
        CommandLine = ''
        ParentProcessId = 0
        Access = 'ok'
        IsPhp = $false
        IsInteresa = $false
    }

    try {
        $proc = Get-Process -Id $ProcessId -ErrorAction Stop
        $info.ProcessName = [string]$proc.ProcessName
        if ($null -ne $proc.Path) {
            $info.Path = [string]$proc.Path
        }
        $info.IsPhp = ($info.ProcessName -ieq 'php')
    } catch {
        $info.Access = 'process-access-denied'
    }

    try {
        $cim = Get-CimInstance Win32_Process -Filter "ProcessId = $ProcessId" -ErrorAction Stop
        if ($null -ne $cim) {
            $info.ProcessName = if ([string]::IsNullOrWhiteSpace($info.ProcessName)) { [string]$cim.Name } else { $info.ProcessName }
            $info.Path = if ([string]::IsNullOrWhiteSpace($info.Path)) { [string]$cim.ExecutablePath } else { $info.Path }
            $info.CommandLine = [string]$cim.CommandLine
            $info.ParentProcessId = [int]$cim.ParentProcessId
            if (-not $info.IsPhp) {
                $info.IsPhp = (([string]$cim.Name) -ieq 'php.exe')
            }
        }
    } catch {
        if ($info.Access -eq 'ok') {
            $info.Access = 'cim-access-denied'
        }
    }

    $haystack = (($info.Path + ' ' + $info.CommandLine) | Out-String).ToLowerInvariant()
    if ($haystack.Contains($projectRoot.ToLowerInvariant()) -or $haystack.Contains('router.php') -or $haystack.Contains('\public')) {
        $info.IsInteresa = $true
    }

    return $info
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

    $processInfo = Get-BlockingProcessInfo -ProcessId $ProcessId

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

    if ((Test-ProcessExists -ProcessId $ProcessId) -and $processInfo.IsInteresa -and $processInfo.ParentProcessId -gt 0) {
        Stop-Process -Id $processInfo.ParentProcessId -Force -ErrorAction SilentlyContinue
        Start-Sleep -Milliseconds 300
        if (Test-ProcessExists -ProcessId $processInfo.ParentProcessId) {
            & taskkill /PID $processInfo.ParentProcessId /F /T | Out-Null
            Start-Sleep -Milliseconds 500
        }
    }

    return -not (Test-ProcessExists -ProcessId $ProcessId)
}

function Invoke-ElevatedRetry {
    $scriptPath = $MyInvocation.MyCommand.Path
    $args = @(
        '-NoProfile',
        '-ExecutionPolicy', 'Bypass',
        '-File', ('"' + $scriptPath + '"'),
        '-Aggressive',
        '-ElevatedRetry'
    )

    if ($Quiet) {
        $args += '-Quiet'
    }

    $process = Start-Process -FilePath 'powershell.exe' -ArgumentList $args -Verb RunAs -Wait -PassThru
    if ($null -eq $process) {
        throw 'Nepodarilo sa spustit repair stop ako admin.'
    }

    return ($process.ExitCode -eq 0)
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

if (-not $released -and $Aggressive -and -not (Test-IsAdministrator) -and -not $ElevatedRetry) {
    Write-Status 'Potrebujem admin prava na uvolnenie portu, spustam znova ako admin...'
    $released = Invoke-ElevatedRetry
}

Remove-Item -Path $pidFile -Force -ErrorAction SilentlyContinue
Remove-Item -Path $runtimeFile -Force -ErrorAction SilentlyContinue

if (-not $released) {
    $listenerPids = @(Get-ListenerPids)
    $suffix = ''
    if ($listenerPids.Count -gt 0) {
        $details = @()
        foreach ($listenerPid in $listenerPids) {
            $info = Get-BlockingProcessInfo -ProcessId $listenerPid
            $processName = if ($info.ProcessName -ne '') { $info.ProcessName } else { 'nedostupne' }
            $path = if ($info.Path -ne '') { $info.Path } else { 'nedostupne' }
            $commandLine = if ($info.CommandLine -ne '') { $info.CommandLine } else { 'nedostupne' }
            $parentPid = if ($info.ParentProcessId -gt 0) { [string]$info.ParentProcessId } else { 'nedostupne' }
            $details += "PID: $listenerPid ProcessName: $processName Path: $path CommandLine: $commandLine ParentPID: $parentPid Access: $($info.Access)"
        }
        $suffix = "`n" + ($details -join "`n")
    }
    throw ('Port 5001 je stale blokovany.' + $suffix)
}

Write-Status 'Port 5001 je volny.'
