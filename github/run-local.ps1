$ErrorActionPreference = 'Stop'

$php = $null
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if ($phpCommand) {
    $php = $phpCommand.Source
}

if (-not $php) {
    $fallbacks = @(
        'C:\php\php.exe'
    )

    foreach ($candidate in $fallbacks) {
        if (Test-Path $candidate) {
            $php = $candidate
            break
        }
    }
}

if (-not $php) {
    Write-Error 'PHP executable was not found. Install PHP or add it to PATH.'
}

Push-Location $PSScriptRoot
try {
    & $php -S 127.0.0.1:5000 -t public public/router.php
} finally {
    Pop-Location
}