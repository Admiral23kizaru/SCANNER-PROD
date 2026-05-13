@echo off
title ScanUp EXE Wrapper
color 0A
setlocal EnableExtensions

rem ============================================================
rem EXE-safe wrapper for BAT-to-EXE tools.
rem
rem Why this exists:
rem - Your original START_SCANUP_UI_LOCAL.bat reads its own file
rem   content to extract embedded PowerShell.
rem - After BAT-to-EXE conversion, %~f0 points to an EXE binary,
rem   so self-read logic fails.
rem
rem This wrapper avoids self-reading and simply calls the original
rem working BAT file from a real .bat path.
rem ============================================================

set "TARGET_BAT=START_SCANUP_UI_LOCAL.bat"
set "LAUNCH_DIR="

rem 1) If the converter passes the original folder as arg1, use it.
if not "%~1"=="" (
    if exist "%~1\%TARGET_BAT%" set "LAUNCH_DIR=%~1"
)

rem 2) Explorer double-click usually sets CWD to the EXE/BAT folder.
if not defined LAUNCH_DIR (
    if exist "%CD%\%TARGET_BAT%" set "LAUNCH_DIR=%CD%"
)

rem 3) Normal BAT run fallback.
if not defined LAUNCH_DIR (
    if exist "%~dp0%TARGET_BAT%" set "LAUNCH_DIR=%~dp0"
)

if not defined LAUNCH_DIR (
    echo [ScanUp] Could not find "%TARGET_BAT%".
    echo Place this wrapper in the same folder as "%TARGET_BAT%".
    echo.
    pause
    endlocal & exit /b 1
)

pushd "%LAUNCH_DIR%" >nul 2>&1
if errorlevel 1 (
    echo [ScanUp] Cannot access launch folder:
    echo %LAUNCH_DIR%
    echo.
    pause
    endlocal & exit /b 1
)

call "%TARGET_BAT%"
set "EXITCODE=%ERRORLEVEL%"
popd >nul 2>&1

if not "%EXITCODE%"=="0" pause
endlocal & exit /b %EXITCODE%
