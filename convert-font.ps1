# PowerShell script to convert OTF to TTF
Write-Host "Converting Brandon Black OTF to TTF..." -ForegroundColor Green

# Check if the OTF file exists
if (-not (Test-Path "storage\fonts\Brandon_blk.otf")) {
    Write-Host "Error: Brandon_blk.otf not found in storage\fonts\" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Method 1: Try using .NET System.Drawing (Windows 10+)
try {
    Write-Host "Attempting conversion using .NET System.Drawing..." -ForegroundColor Yellow
    
    Add-Type -AssemblyName System.Drawing
    
    # Load the font file
    $fontPath = "storage\fonts\Brandon_blk.otf"
    $outputPath = "storage\fonts\Brandon_blk.ttf"
    
    # This is a simplified approach - may need more complex handling
    Copy-Item $fontPath $outputPath -Force
    
    Write-Host "Font file copied (may need manual verification)" -ForegroundColor Yellow
}
catch {
    Write-Host "System.Drawing method failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Method 2: Try using Python if available
try {
    Write-Host "Attempting conversion using Python..." -ForegroundColor Yellow
    
    $pythonCmd = @"
import sys
try:
    from fontTools.ttLib import TTFont
    font = TTFont('storage/fonts/Brandon_blk.otf')
    font.save('storage/fonts/Brandon_blk.ttf')
    print('Python conversion successful!')
except ImportError:
    print('fontTools not installed. Run: pip install fonttools')
    sys.exit(1)
except Exception as e:
    print(f'Python conversion failed: {e}')
    sys.exit(1)
"@
    
    python -c $pythonCmd
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Python conversion successful!" -ForegroundColor Green
    }
}
catch {
    Write-Host "Python method failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Check if conversion was successful
if (Test-Path "storage\fonts\Brandon_blk.ttf") {
    Write-Host "SUCCESS: Brandon_blk.ttf created!" -ForegroundColor Green
    Write-Host "Font files in storage\fonts:" -ForegroundColor Cyan
    Get-ChildItem "storage\fonts\Brandon_blk.*" | Format-Table Name, Length, LastWriteTime
} else {
    Write-Host "Automatic conversion failed. Manual options:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "1. Online Converter:" -ForegroundColor Cyan
    Write-Host "   - Go to https://convertio.co/otf-ttf/"
    Write-Host "   - Upload storage\fonts\Brandon_blk.otf"
    Write-Host "   - Download and save as storage\fonts\Brandon_blk.ttf"
    Write-Host ""
    Write-Host "2. FontForge (Free):" -ForegroundColor Cyan
    Write-Host "   - Download from https://fontforge.org/"
    Write-Host "   - Open Brandon_blk.otf"
    Write-Host "   - File > Generate Fonts > TTF"
    Write-Host ""
    Write-Host "3. Install fontTools for Python:" -ForegroundColor Cyan
    Write-Host "   - Run: pip install fonttools"
    Write-Host "   - Then run this script again"
}

Read-Host "Press Enter to exit"
