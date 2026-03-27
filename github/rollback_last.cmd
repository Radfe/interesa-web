@echo off
setlocal

cd /d "%~dp0"
echo Starting Interesa rollback...
echo Restoring the latest backup from .deploy_backups
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\rollback_last.ps1" %*
set "EXITCODE=%ERRORLEVEL%"

echo.
if not "%EXITCODE%"=="0" (
  echo Rollback failed.
) else (
  echo Rollback finished successfully.
)
pause
exit /b %EXITCODE%
