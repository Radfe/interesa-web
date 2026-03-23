$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
& (Join-Path $projectRoot 'stop-local-site.ps1')
Start-Sleep -Milliseconds 500
& (Join-Path $projectRoot 'open-local-site.ps1')