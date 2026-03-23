@echo off
setlocal

cd /d "%~dp0"
echo Starting Interesa...
echo Server running on http://127.0.0.1:5001
echo.

powershell -NoExit -ExecutionPolicy Bypass -File "%~dp0start-interesa.ps1"
