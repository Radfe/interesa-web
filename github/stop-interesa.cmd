@echo off
setlocal

set "REPO_ROOT=C:\data\praca\webova_stranka\github"
set "STOP_PS1=%REPO_ROOT%\stop-interesa.ps1"

if not exist "%STOP_PS1%" (
  echo Chyba: nenasiel sa oficialny stop skript:
  echo %STOP_PS1%
  pause
  exit /b 1
)

cd /d "%REPO_ROOT%"
echo Interesa official local STOP
echo Spusta sa:
echo %STOP_PS1%
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%STOP_PS1%"
echo Lokalny server bol zastaveny.
pause
