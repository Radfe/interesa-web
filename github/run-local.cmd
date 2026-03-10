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
"%PHP_BIN%" -S 127.0.0.1:5000 -t public public/router.php