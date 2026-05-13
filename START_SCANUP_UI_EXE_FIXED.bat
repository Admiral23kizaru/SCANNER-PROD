@echo off
title ScanUp
color 0A
setlocal

set "SCANUP_PS1=%~dp0START_SCANUP_UI_EXE_FIXED.ps1"
if not exist "%SCANUP_PS1%" (
    echo [ScanUp] Missing PowerShell launcher:
    echo %SCANUP_PS1%
    echo.
    pause
    endlocal & exit /b 1
)

powershell -STA -NoProfile -ExecutionPolicy Bypass -File "%SCANUP_PS1%"
set "EXITCODE=%ERRORLEVEL%"
if not "%EXITCODE%"=="0" pause
endlocal & exit /b %EXITCODE%
