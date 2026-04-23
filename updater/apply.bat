@echo off
REM Laundry Shop Management — external updater launcher.
REM
REM Invoked by the Laravel webapp after a release has been staged. Self-copies
REM this directory to %TEMP% so the running updater is never the one being
REM swapped, then hands off to apply.php.
REM
REM Usage: apply.bat <config-json-path>
REM   <config-json-path> — absolute path to the stage-time config file written
REM                        by the webapp (php paths, apache service, pending dir).

setlocal enableextensions enabledelayedexpansion

if "%~1"=="" (
    echo [apply.bat] ERROR: missing config path argument.
    exit /b 2
)

set "CONFIG_PATH=%~1"

if not exist "%CONFIG_PATH%" (
    echo [apply.bat] ERROR: config file not found: %CONFIG_PATH%
    exit /b 2
)

REM If we are already running from TEMP, skip the self-copy and go straight
REM to the PHP orchestrator. The marker env var is set on the re-invocation.
if "%LAUNDRY_UPDATER_BOOTSTRAPPED%"=="1" goto run_php

REM Build a unique temp directory name using date/time + random suffix.
REM Note: wmic is deprecated/removed on Windows 11, so we use built-in vars.
set "STAMP=%DATE:~-4,4%%DATE:~-7,2%%DATE:~-10,2%-%TIME:~0,2%%TIME:~3,2%%TIME:~6,2%-%RANDOM%"
set "STAMP=%STAMP: =0%"
set "TEMP_UPDATER=%TEMP%\laundry-updater-%STAMP%"

echo [apply.bat] Copying updater to %TEMP_UPDATER%
if exist "%TEMP_UPDATER%" rmdir /s /q "%TEMP_UPDATER%" 1>nul 2>nul
mkdir "%TEMP_UPDATER%" 1>nul 2>nul
xcopy "%~dp0*" "%TEMP_UPDATER%\" /E /I /Q /Y 1>nul
if errorlevel 1 (
    echo [apply.bat] ERROR: failed to copy updater to temp.
    exit /b 3
)

set "LAUNDRY_UPDATER_BOOTSTRAPPED=1"
start "" /B cmd /c "%TEMP_UPDATER%\apply.bat" "%CONFIG_PATH%"
exit /b 0

:run_php
REM Read PHP path from config using a tiny inline PHP that just echoes it.
REM We bootstrap with whatever php is first on PATH; the real paths come from config.
for /f "usebackq delims=" %%P in (`php -r "echo json_decode(file_get_contents('%CONFIG_PATH:\=\\%'), true)['php_binary'] ?? 'php';"`) do set "PHP_BIN=%%P"

if "%PHP_BIN%"=="" set "PHP_BIN=php"

echo [apply.bat] Launching PHP orchestrator: %PHP_BIN%
"%PHP_BIN%" "%~dp0apply.php" "%CONFIG_PATH%"
set "EXIT=%ERRORLEVEL%"

echo [apply.bat] apply.php exited with %EXIT%
exit /b %EXIT%
