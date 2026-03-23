$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$openScript = Join-Path $projectRoot 'open-local-site.ps1'
$siteUrl = 'http://127.0.0.1:5001/'
$adminUrl = 'http://127.0.0.1:5001/admin'

& $openScript
Start-Sleep -Milliseconds 500

try {
    Start-Process $adminUrl | Out-Null
} catch {
    Write-Warning "Admin page could not be opened automatically."
}
