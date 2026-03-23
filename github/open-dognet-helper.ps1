$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$startScript = Join-Path $projectRoot 'start-interesa.ps1'

& $startScript
Start-Sleep -Milliseconds 500
Start-Process 'http://127.0.0.1:5001/dognet-helper' | Out-Null
