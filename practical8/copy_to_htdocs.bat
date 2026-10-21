@echo off
echo ============================================================
echo StudentHub - Copy Practical 8 to C:\xampp\htdocs\studenthub_practical8
echo ============================================================

set SOURCE=%~dp0
set TARGET=C:\xampp\htdocs\studenthub_practical8

echo Source: %SOURCE%
echo Target: %TARGET%
echo.

if not exist "%TARGET%" (
    mkdir "%TARGET%"
    echo Created directory: %TARGET%
)

xcopy "%SOURCE%*" "%TARGET%\" /E /I /Y /Q

echo.
echo ============================================================
echo Copy Complete!
echo You can now access Practical 8 directly via either URL:
echo 1. http://localhost/studenthub_practical7/practical8/
echo 2. http://localhost/studenthub_practical8/
echo ============================================================
pause
