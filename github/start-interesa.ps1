$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$stateDir = Join-Path $projectRoot '.codex-local'
$markerFile = Join-Path $stateDir 'local-build.json'
$runtimeFile = Join-Path $stateDir 'local-runtime.json'
$stopScript = Join-Path $projectRoot 'stop-interesa.ps1'
$openScript = Join-Path $projectRoot 'open-local-site.ps1'

function Get-GitShortHash {
    try {
        $hash = (& git -C $projectRoot rev-parse --short HEAD 2>$null | Select-Object -First 1)
        return ($hash | Out-String).Trim()
    } catch {
        return ''
    }
}

function Get-LocalRuntime {
    if (-not (Test-Path $runtimeFile)) {
        return @{
            port = 5001
            site_url = 'http://127.0.0.1:5001/'
            admin_url = 'http://127.0.0.1:5001/admin'
        }
    }

    $raw = Get-Content -Path $runtimeFile -Raw -ErrorAction SilentlyContinue
    $data = $null
    if ($raw) {
        $data = $raw | ConvertFrom-Json -ErrorAction SilentlyContinue
    }

    if ($null -eq $data) {
        return @{
            port = 5001
            site_url = 'http://127.0.0.1:5001/'
            admin_url = 'http://127.0.0.1:5001/admin'
        }
    }

    $port = 5001
    $site = 'http://127.0.0.1:5001/'
    $admin = 'http://127.0.0.1:5001/admin'
    if ($null -ne $data.port -and [string]$data.port -ne '') {
        $port = [int]$data.port
    }
    if ($null -ne $data.site_url -and [string]$data.site_url -ne '') {
        $site = [string]$data.site_url
    }
    if ($null -ne $data.admin_url -and [string]$data.admin_url -ne '') {
        $admin = [string]$data.admin_url
    }

    return @{
        port = $port
        site_url = $site
        admin_url = $admin
    }
}

function New-LocalBuildMarker {
    param(
        [hashtable]$Runtime
    )

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
        site_url = [string]$(if ($null -ne $Runtime.site_url -and [string]$Runtime.site_url -ne '') { $Runtime.site_url } else { 'http://127.0.0.1:5001/' })
        admin_url = [string]$(if ($null -ne $Runtime.admin_url -and [string]$Runtime.admin_url -ne '') { $Runtime.admin_url } else { 'http://127.0.0.1:5001/admin' })
        port = [int]$(if ($null -ne $Runtime.port -and [string]$Runtime.port -ne '') { $Runtime.port } else { 5001 })
    }

    $payload | ConvertTo-Json -Depth 3 | Set-Content -Path $markerFile -Encoding UTF8
    return $payload
}

if (Test-Path $stopScript) {
    & $stopScript
}
& $openScript
Start-Sleep -Milliseconds 500
$runtime = Get-LocalRuntime
$siteUrl = [string]$runtime.site_url
$adminUrl = [string]$runtime.admin_url
$buildMarker = New-LocalBuildMarker -Runtime $runtime

try {
    Start-Process $adminUrl | Out-Null
} catch {
    Write-Warning "Admin page could not be opened automatically."
}

Write-Host ''
Write-Host 'Interesa local server is running.' -ForegroundColor Green
Write-Host ('Marker: ' + $buildMarker.marker) -ForegroundColor Cyan
Write-Host ('Port:   ' + $buildMarker.port)
Write-Host ('Site:   ' + $siteUrl)
Write-Host ('Admin:  ' + $adminUrl)
if ($buildMarker.git_short -ne '') {
    Write-Host ('Git:    ' + $buildMarker.git_short)
}
Write-Host ('Start:  ' + $buildMarker.started_at_display)
