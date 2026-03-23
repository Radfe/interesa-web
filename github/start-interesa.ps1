$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$stateDir = Join-Path $projectRoot '.codex-local'
$markerFile = Join-Path $stateDir 'local-build.json'
$stopScript = Join-Path $projectRoot 'stop-interesa.ps1'
$openScript = Join-Path $projectRoot 'open-local-site.ps1'
$siteUrl = 'http://127.0.0.1:5001/'
$adminUrl = 'http://127.0.0.1:5001/admin'

function Get-GitShortHash {
    try {
        $hash = (& git -C $projectRoot rev-parse --short HEAD 2>$null | Select-Object -First 1)
        return ($hash | Out-String).Trim()
    } catch {
        return ''
    }
}

function New-LocalBuildMarker {
    New-Item -ItemType Directory -Force -Path $stateDir | Out-Null

    $startedAt = Get-Date
    $gitShort = Get-GitShortHash
    $marker = 'LOCAL-' + $startedAt.ToString('yyyyMMdd-HHmmss')
    if ($gitShort -ne '') {
        $marker += '-' + $gitShort
    }

    $payload = [ordered]@{
        marker = $marker
        started_at_iso = $startedAt.ToString('o')
        started_at_display = $startedAt.ToString('dd.MM.yyyy HH:mm:ss')
        git_short = $gitShort
        site_url = $siteUrl
        admin_url = $adminUrl
    }

    $payload | ConvertTo-Json -Depth 3 | Set-Content -Path $markerFile -Encoding UTF8
    return $payload
}

if (Test-Path $stopScript) {
    & $stopScript
}
$buildMarker = New-LocalBuildMarker
& $openScript
Start-Sleep -Milliseconds 500

try {
    Start-Process $adminUrl | Out-Null
} catch {
    Write-Warning "Admin page could not be opened automatically."
}

Write-Host ''
Write-Host 'Interesa local server is running.' -ForegroundColor Green
Write-Host ('Marker: ' + $buildMarker.marker) -ForegroundColor Cyan
Write-Host ('Site:   ' + $siteUrl)
Write-Host ('Admin:  ' + $adminUrl)
if ($buildMarker.git_short -ne '') {
    Write-Host ('Git:    ' + $buildMarker.git_short)
}
Write-Host ('Start:  ' + $buildMarker.started_at_display)
