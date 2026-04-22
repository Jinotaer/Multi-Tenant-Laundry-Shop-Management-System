@echo off
REM Laundry Shop Management — manual rollback launcher.
REM
REM Runs the same orchestration as apply.bat but invokes rollback.php, which
REM restores the most recent backup without staging a new release. Used when
REM an update left the system in a bad state and automatic rollback did not
REM recover.
REM
REM Usage: rollback.bat <config-json-path>

setlocal enableextensions enabledelayedexpansion

if "%~1"=="" (
    echo [rollback.bat] ERROR: missing config path argument.
    exit /b 2
)

set "CONFIG_PATH=%~1"

if not exist "%CONFIG_PATH%" (
    echo [rollback.bat] ERROR: config file not found: %CONFIG_PATH%
    exit /b 2
)

if "%LAUNDRY_UPDATER_BOOTSTRAPPED%"=="1" goto run_php

for /f "tokens=2 delims==" %%A in ('wmic os get localdatetime /value ^| find "="') do set "DT=%%A"
set "STAMP=%DT:~0,14%"
set "TEMP_UPDATER=%TEMP%\laundry-rollback-%STAMP%"

echo [rollback.bat] Copying updater to %TEMP_UPDATER%
if exist "%TEMP_UPDATER%" rmdir /s /q "%TEMP_UPDATER%" 1>nul 2>nul
mkdir "%TEMP_UPDATER%" 1>nul 2>nul
xcopy "%~dp0*" "%TEMP_UPDATER%\" /E /I /Q /Y 1>nul
if errorlevel 1 (
    echo [rollback.bat] ERROR: failed to copy updater to temp.
    exit /b 3
)

set "LAUNDRY_UPDATER_BOOTSTRAPPED=1"
start "" /B cmd /c "%TEMP_UPDATER%\rollback.bat" "%CONFIG_PATH%"
exit /b 0

:run_php
for /f "usebackq delims=" %%P in (`php -r "echo json_decode(file_get_contents('%CONFIG_PATH:\=\\%'), true)['php_binary'] ?? 'php';"`) do set "PHP_BIN=%%P"
if "%PHP_BIN%"=="" set "PHP_BIN=php"

echo [rollback.bat] Launching rollback.php: %PHP_BIN%
"%PHP_BIN%" "%~dp0rollback.php" "%CONFIG_PATH%"
exit /b %ERRORLEVEL%
