#!/usr/bin/env python3
"""
Font conversion script to convert OTF to TTF
Requires: pip install fonttools
"""

import os
import sys
import shutil

def convert_otf_to_ttf():
    """Convert OTF font to TTF format"""
    
    otf_path = "storage/fonts/Brandon_blk.otf"
    ttf_path = "storage/fonts/Brandon_blk.ttf"
    
    print("Converting Brandon Black OTF to TTF...")
    
    # Check if OTF file exists
    if not os.path.exists(otf_path):
        print(f"Error: {otf_path} not found!")
        return False
    
    try:
        # Try to import fontTools
        from fontTools.ttLib import TTFont
        
        print("Loading OTF font...")
        font = TTFont(otf_path)
        
        print("Saving as TTF...")
        font.save(ttf_path)
        
        print(f"SUCCESS: Font converted to {ttf_path}")
        return True
        
    except ImportError:
        print("fontTools not installed. Installing...")
        try:
            import subprocess
            subprocess.check_call([sys.executable, "-m", "pip", "install", "fonttools"])
            print("fontTools installed. Please run the script again.")
            return False
        except subprocess.CalledProcessError:
            print("Failed to install fontTools. Please install manually:")
            print("pip install fonttools")
            return False
            
    except Exception as e:
        print(f"Conversion failed: {e}")
        return False

def main():
    """Main function"""
    print("Font Conversion Script")
    print("=" * 30)
    
    # Check if we're in the right directory
    if not os.path.exists("storage/fonts"):
        print("Error: storage/fonts directory not found!")
        print("Please run this script from the project root directory.")
        return
    
    success = convert_otf_to_ttf()
    
    if success:
        print("\nFont conversion completed successfully!")
        print("You can now update the PDF service to use the TTF font.")
        
        # Show file info
        if os.path.exists("storage/fonts/Brandon_blk.ttf"):
            size = os.path.getsize("storage/fonts/Brandon_blk.ttf")
            print(f"TTF file size: {size:,} bytes")
    else:
        print("\nConversion failed. Manual options:")
        print("1. Online converter: https://convertio.co/otf-ttf/")
        print("2. FontForge: https://fontforge.org/")
        print("3. Install fontTools: pip install fonttools")

if __name__ == "__main__":
    main()
