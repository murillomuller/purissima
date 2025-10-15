@echo off
echo ========================================
echo   PURISSIMA ITEM NAMES EXTRACTOR
echo   Node.js Version
echo ========================================
echo.

REM Check if Node.js is available
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: Node.js is not installed or not in PATH
    echo Please install Node.js from https://nodejs.org/ and try again
    pause
    exit /b 1
)

echo Node.js found. Running item extraction script...
echo.

REM Run the Node.js extractor
node extract-item-names.js

echo.
echo ========================================
echo   EXTRACTION COMPLETED
echo ========================================
echo.
echo Check the generated files for results:
echo - unique-item-names_*.txt (list of unique names)
echo - item-frequency_*.csv (frequency data with percentages)
echo - item-details_*.json (detailed analysis)
echo - raw-orders-data_*.json (raw API data)
echo.
pause
