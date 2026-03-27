@echo off
setlocal

cd /d "%~dp0"
set "MODE=%~1"

echo Interesa deploy: zmenene public subory
echo.

if /I "%MODE%"=="preview" (
  powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\deploy_live.ps1" -Preview
) else (
  powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\deploy_live.ps1"
)

set "EXITCODE=%ERRORLEVEL%"
echo.
if not "%EXITCODE%"=="0" (
  echo Deploy zlyhal.
) else (
  echo Deploy dokonceny.
)
pause
exit /b %EXITCODE%
