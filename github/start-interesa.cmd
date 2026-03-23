@echo off
setlocal

cd /d "%~dp0"
echo Interesa official local START
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0start-interesa.ps1"
echo.
echo Porovnaj marker v tomto okne s markerom vpravo dole na lokalnom webe a v admine.
pause
