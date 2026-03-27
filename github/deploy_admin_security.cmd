@echo off
setlocal

cd /d "%~dp0"
set "MODE=%~1"
set "LISTFILE=%~dp0scripts\presets\deploy_admin_security.txt"

echo Interesa deploy: admin/security preset
echo.

if /I "%MODE%"=="preview" (
  powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\deploy_live.ps1" -ExplicitListFile "%LISTFILE%" -Preview
) else (
  powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\deploy_live.ps1" -ExplicitListFile "%LISTFILE%"
)

set "EXITCODE=%ERRORLEVEL%"
echo.
if not "%EXITCODE%"=="0" (
  echo Admin/security deploy zlyhal.
) else (
  echo Admin/security deploy dokonceny.
)
pause
exit /b %EXITCODE%
