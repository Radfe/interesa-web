@echo off
setlocal

cd /d "%~dp0"
echo Starting Interesa deploy...
echo Target mapping: public\* -> /www/*
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\deploy_live.ps1" %*
set "EXITCODE=%ERRORLEVEL%"

echo.
if not "%EXITCODE%"=="0" (
  echo Deploy failed.
) else (
  echo Deploy finished successfully.
)
pause
exit /b %EXITCODE%
