# Fonts Directory

This directory contains font files for PDF generation.

## Supported Font Formats
- TTF (TrueType Fonts)
- OTF (OpenType Fonts)

## Adding Fonts
1. Place your font files in this directory
2. Use the `addFont()` method in PdfService to register fonts
3. Reference fonts by their filename (without extension)

## Example Usage
```php
$pdfService = new PdfService($logger);
$pdfService->addFont('custom-font', 'MyCustomFont.ttf');
```

## Default Fonts
The system uses Arial as the default font for PDF generation.
