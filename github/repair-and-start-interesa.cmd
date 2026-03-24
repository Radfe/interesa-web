@echo off
setlocal

set "REPO_ROOT=C:\data\praca\webova_stranka\github"
set "START_PS1=%REPO_ROOT%\start-interesa.ps1"

if not exist "%START_PS1%" (
  echo Chyba: nenasiel sa repair start skript:
  echo %START_PS1%
  pause
  exit /b 1
)

cd /d "%REPO_ROOT%"
echo Interesa REPAIR AND START
echo Spusta sa:
echo %START_PS1% -Repair
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%START_PS1%" -Repair
echo.
pause
