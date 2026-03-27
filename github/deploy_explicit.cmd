@echo off
setlocal

cd /d "%~dp0"
set "LISTFILE=%~1"
set "MODE=%~2"

if "%LISTFILE%"=="" set "LISTFILE=%~dp0.deploy_state\deploy_explicit_files.txt"

echo Interesa deploy: explicitne public subory
echo Zoznam: %LISTFILE%
echo.

if not exist "%LISTFILE%" (
  echo Chyba: zoznam suborov neexistuje.
  echo Vytvor textovy subor s riadkami ako:
  echo public/admin/index.php
  echo public/inc/admin-auth.php
  echo public/router.php
  pause
  exit /b 1
)

if /I "%MODE%"=="preview" (
  powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\deploy_live.ps1" -ExplicitListFile "%LISTFILE%" -Preview
) else (
  powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\deploy_live.ps1" -ExplicitListFile "%LISTFILE%"
)

set "EXITCODE=%ERRORLEVEL%"
echo.
if not "%EXITCODE%"=="0" (
  echo Explicit deploy zlyhal.
) else (
  echo Explicit deploy dokonceny.
)
pause
exit /b %EXITCODE%
