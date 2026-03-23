$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$openScript = Join-Path $projectRoot 'open-local-site.ps1'

& $openScript
Start-Sleep -Milliseconds 500
Start-Process 'http://127.0.0.1:5000/dognet-helper' | Out-Null