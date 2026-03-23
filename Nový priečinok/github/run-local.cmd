@echo off
set "PHP_BIN=php"
where php >nul 2>nul
if errorlevel 1 (
    if exist C:\php\php.exe (
        set "PHP_BIN=C:\php\php.exe"
    ) else (
        echo PHP executable was not found. Install PHP or add it to PATH.
        exit /b 1
    )
)
cd /d "%~dp0"
set "PUBLIC_ROOT=%~dp0public"
set "ROUTER_PATH=%PUBLIC_ROOT%\router.php"
"%PHP_BIN%" -S 127.0.0.1:5001 -t "%PUBLIC_ROOT%" "%ROUTER_PATH%"
