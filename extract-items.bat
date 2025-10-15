@echo off
echo ========================================
echo   PURISSIMA ITEM NAMES EXTRACTOR
echo ========================================
echo.

REM Check if PHP is available
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: PHP is not installed or not in PATH
    echo Please install PHP and try again
    pause
    exit /b 1
)

echo PHP found. Running item extraction script...
echo.

REM Run the simple extractor
php simple-item-extractor.php

echo.
echo ========================================
echo   EXTRACTION COMPLETED
echo ========================================
echo.
echo Check the generated files for results:
echo - unique-item-names_*.txt (list of unique names)
echo - item-frequency_*.csv (frequency data)
echo - raw-orders-data_*.json (raw API data)
echo.
pause
