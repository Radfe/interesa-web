@echo off
setlocal

cd /d "%~dp0"
echo Interesa official local STOP
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0stop-local-site.ps1"
echo Lokalny server bol zastaveny.
pause
