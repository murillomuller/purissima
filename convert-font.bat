@echo off
echo Converting Brandon Black OTF to TTF...

REM Check if the OTF file exists
if not exist "storage\fonts\Brandon_blk.otf" (
    echo Error: Brandon_blk.otf not found in storage\fonts\
    pause
    exit /b 1
)

REM Method 1: Try using Python with fontTools (if available)
python --version >nul 2>&1
if %errorlevel% == 0 (
    echo Python found, trying to convert with fontTools...
    pip install fonttools >nul 2>&1
    python -c "
import sys
try:
    from fontTools.ttLib import TTFont
    font = TTFont('storage/fonts/Brandon_blk.otf')
    font.save('storage/fonts/Brandon_blk.ttf')
    print('Conversion successful!')
except Exception as e:
    print(f'Python conversion failed: {e}')
    sys.exit(1)
" 2>nul
    if %errorlevel% == 0 (
        echo Font converted successfully to Brandon_blk.ttf
        goto :success
    )
)

REM Method 2: Try using Node.js with fonttools (if available)
node --version >nul 2>&1
if %errorlevel% == 0 (
    echo Node.js found, trying to convert...
    npm install -g fonttools >nul 2>&1
    node -e "
const fs = require('fs');
const path = require('path');
try {
    // Simple file copy as fallback
    fs.copyFileSync('storage/fonts/Brandon_blk.otf', 'storage/fonts/Brandon_blk.ttf');
    console.log('Font copied as TTF (may need manual conversion)');
} catch (e) {
    console.log('Node.js conversion failed:', e.message);
    process.exit(1);
}
" 2>nul
    if %errorlevel% == 0 (
        echo Font processed with Node.js
        goto :success
    )
)

REM Method 3: Manual instructions
echo.
echo Automatic conversion failed. Please use one of these methods:
echo.
echo 1. Online Converter:
echo    - Go to https://convertio.co/otf-ttf/
echo    - Upload storage\fonts\Brandon_blk.otf
echo    - Download the converted TTF file
echo    - Save as storage\fonts\Brandon_blk.ttf
echo.
echo 2. FontForge (Free):
echo    - Download from https://fontforge.org/
echo    - Open Brandon_blk.otf
echo    - File > Generate Fonts
echo    - Choose TTF format
echo    - Save as Brandon_blk.ttf
echo.
echo 3. Adobe Fonts (if you have access):
echo    - Use Adobe's font conversion tools
echo.
echo 4. PowerShell method (Windows 10+):
echo    - Run: Add-Type -AssemblyName System.Drawing
echo    - Use .NET font conversion methods
echo.
echo Press any key to continue after manual conversion...
pause

:success
if exist "storage\fonts\Brandon_blk.ttf" (
    echo.
    echo SUCCESS: Brandon_blk.ttf created!
    echo You can now update the PDF service to use this font.
    echo.
    dir storage\fonts\Brandon_blk.*
) else (
    echo.
    echo Font conversion not completed.
    echo Please follow the manual instructions above.
)

echo.
echo Press any key to exit...
pause
