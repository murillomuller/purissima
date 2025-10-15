<?php

namespace App\Services;

use TCPDF;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Services\LoggerService;
use App\Services\DoseMapper;

class TcpdfService
{
    private LoggerService $logger;
    private string $uploadPath;
    private string $outputPath;
    private string $fontsPath;
    private int $maxFileSize;
    private DoseMapper $doseMapper;

    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        $this->uploadPath = __DIR__ . '/../../storage/uploads';
        $this->outputPath = __DIR__ . '/../../storage/output';
        $this->fontsPath = __DIR__ . '/../../storage/fonts';
        $this->maxFileSize = (int) ($_ENV['PDF_MAX_SIZE'] ?? 10485760); // 10MB default
        $this->doseMapper = new DoseMapper();

        $this->ensureDirectoriesExist();
    }

    private function ensureDirectoriesExist(): void
    {
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
        if (!is_dir($this->fontsPath)) {
            mkdir($this->fontsPath, 0755, true);
        }
    }

    public function createPrescriptionPdf(array $orderData, array $items): string
    {
        $filename = 'receituario_' . $orderData['ord_id'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $filepath = $this->outputPath . '/' . $filename;

        $this->logger->info('Using TCPDF service for PDF generation');
        
        // Suppress any output that might interfere with JSON response
        ob_start();
        
        try {
            // Use FPDI to import the base PDF template
            $basePdfPath = __DIR__ . '/../../storage/pdf/receituario-base.pdf';
            if (!file_exists($basePdfPath)) {
                throw new \Exception('Base PDF template not found: ' . $basePdfPath);
            }
            
            $pdf = new Fpdi();
            $pdf->setSourceFile($basePdfPath);
            $templateId = $pdf->importPage(1);
            
            // Disable default TCPDF header/footer behavior
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set the fonts directory for TCPDF
            // Note: FPDI with TCPDF might not support setFontsPath, so we'll handle fonts differently
            
            // Add Brandon Black font for headers using the generated PHP font file
            $brandonBlackPhpPath = $this->fontsPath . '/brandontextblack.php';
            if (file_exists($brandonBlackPhpPath)) {
                try {
                    $pdf->AddFont('brandontextblack', '', $brandonBlackPhpPath, true);
                    $this->logger->info('Brandon Black font loaded successfully from brandontextblack.php');
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to load Brandon Black font from PHP file: ' . $e->getMessage());
                    // Don't throw exception, just log and continue
                }
            } else {
                $this->logger->warning('Brandon Black PHP font file not found, using default fonts');
            }
            
            // Add OpenSans font for body text
            $openSansPhpPath = $this->fontsPath . '/opensans.php';
            if (file_exists($openSansPhpPath)) {
                try {
                    $pdf->AddFont('opensans', '', $openSansPhpPath, true);
                    $this->logger->info('OpenSans font loaded successfully from opensans.php');
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to load OpenSans font from PHP file: ' . $e->getMessage());
                    // Don't throw exception, just log and continue
                }
            } else {
                $this->logger->warning('OpenSans PHP font file not found, using default fonts');
            }
            
            // Use custom fonts for headers and body text
            $this->logger->info('Using TCPDF with custom fonts (Brandon Black for headers, OpenSans for body text)');
            
            // Set page settings
            $pdf->SetAutoPageBreak(false, 0); // Disable auto page break, we'll handle it manually
            
            // Set text color to #3F1F20
            $this->setStandardTextColor($pdf);
            
            // Disable default drawing behavior
            $pdf->SetDrawColor(255, 255, 255); // Set to white/transparent
            $pdf->SetLineWidth(0);
            
            // Add first page with base template
            $pdf->AddPage();
            $pdf->useTemplate($templateId);
            
            // Patient information
            try {
                $pdf->SetFont('opensans', '', 10);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', '', 10);
            }
            $pdf->SetXY(125, 15);
            $pdf->Cell(70, 5, $this->cleanText($orderData['usr_name']), 0, 1, 'R');
            $pdf->SetXY(125, 20);
            $pdf->Cell(70, 5, $this->cleanText('Documento: ' . $orderData['usr_cpf']), 0, 1, 'R');
            $pdf->SetXY(125, 25);
            $pdf->Cell(70, 5, $this->cleanText('Sexo: [Não informado]'), 0, 1, 'R');
            $pdf->SetXY(125, 30);
            $pdf->Cell(70, 5, $this->cleanText('Telefone: ' . $orderData['usr_phone']), 0, 1, 'R');
            $pdf->SetXY(125, 35);
            $pdf->Cell(70, 5, $this->cleanText('Prescrição: ' . $orderData['ord_id']), 0, 1, 'R');
            
            // Date at bottom right
            try {
                $pdf->SetFont('opensans', '', 12);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', '', 12);
            }
            $pdf->SetXY(125, 250);
            $pdf->Cell(70, 5, 'Data: ' . date('d/m/Y H:i'), 0, 1, 'R');
            
            // Enrich items with precomputed dose text from mapper
            foreach ($items as &$itemRef) {
                if (isset($itemRef['itm_name'])) {
                    $doseText = $this->doseMapper->getDosageText($itemRef['itm_name']);
                    $itemRef['dose_text'] = is_string($doseText) ? $doseText : '';
                }
            }
            unset($itemRef);

            // Group items by time (Dia/Noite) and type (pouch/Cápsula)
            $groupedItems = $this->groupItemsByTimeAndType($items);
            
            // Prescriptions - Structured Layout
            $yPosition = 50;
            
            // Process each time group (Dia, Noite, Others)
            foreach ($groupedItems as $timeGroup => $typeGroups) {
                // First, process pouch items (MY FORMULA sections)
                if (!empty($typeGroups['pouch'])) {
                    $this->processPouchSection($pdf, $timeGroup, $typeGroups['pouch'], $yPosition, $templateId, $orderData);
                }
                
                // Then, process Cápsula items as subsections (only for Dia and Noite)
                if (!empty($typeGroups['capsula']) && ($timeGroup === 'dia' || $timeGroup === 'noite')) {
                    $this->processCapsulaSubsection($pdf, $typeGroups['capsula'], $yPosition, $templateId, $orderData);
                }
                
                // Process other items with their names as headers
                if ($timeGroup === 'other') {
                    foreach ($typeGroups as $type => $items) {
                        if (empty($items)) continue;
                        foreach ($items as $item) {
                            $this->processOtherItem($pdf, $item, $yPosition, $templateId, $orderData);
                        }
                    }
                }
            }
            
            // Output the PDF
            $pdf->Output($filepath, 'F');
            
            // Clean any output buffer
            ob_end_clean();

            $this->logger->info('Prescription PDF created with TCPDF', [
                'order_id' => $orderData['ord_id'],
                'filename' => $filename,
                'filepath' => $filepath,
                'file_exists' => file_exists($filepath),
                'file_size' => filesize($filepath)
            ]);

            return $filename;

        } catch (\Exception $e) {
            // Clean output buffer and log error
            ob_end_clean();
            
            $this->logger->error('Prescription PDF creation failed', [
                'order_id' => $orderData['ord_id'],
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Group items by time (Dia/Noite) and type (pouch/Cápsula)
     */
    private function groupItemsByTimeAndType(array $items): array
    {
        $grouped = [
            'dia' => ['pouch' => [], 'capsula' => []],
            'noite' => ['pouch' => [], 'capsula' => []],
            'other' => ['pouch' => [], 'capsula' => []]
        ];
        
        foreach ($items as $item) {
            $itemName = strtolower($item['itm_name']);
            $isPouch = $this->isPouchItem($item);
            $isCapsula = strpos($itemName, 'cápsula') !== false;
            
            // Determine time group
            $timeGroup = 'other';
            if (strpos($itemName, 'dia') !== false) {
                $timeGroup = 'dia';
            } elseif (strpos($itemName, 'noite') !== false) {
                $timeGroup = 'noite';
            }
            
            // Determine type
            $type = 'capsula';
            if ($isPouch) {
                $type = 'pouch';
            } elseif ($isCapsula) {
                $type = 'capsula';
            }
            
            $grouped[$timeGroup][$type][] = $item;
        }
        
        return $grouped;
    }

    /**
     * Check if item is a pouch item
     */
    private function isPouchItem(array $item): bool
    {
        $itemName = strtolower($item['itm_name']);
        $pouchKeywords = ['pouch', 'sachet', 'saco', 'envelope'];
        foreach ($pouchKeywords as $keyword) {
            if (strpos($itemName, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Process pouch section (MY FORMULA | DIA/NOITE)
     */
    private function processPouchSection($pdf, string $timeGroup, array $items, int &$yPosition, $templateId, array $orderData): void
    {
        if (empty($items)) return;
        
        // Check if we need a new page
        if ($yPosition > 150) {
            $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
            $yPosition = 50;
        }
        
        // Main section header
        $sectionHeader = ($timeGroup === 'dia') ? 'MY FORMULA | DIA' : 'MY FORMULA | NOITE';
        
            // Use Brandon Black font for headers
            try {
                $pdf->SetFont('brandontextblack', '', 14);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', 'B', 14);
            }
        
        // Draw background rectangle with transparency
        $this->drawHeaderBackground($pdf, 15, $yPosition, 180, 8);
        
        // Draw text on top (without background)
        $pdf->SetXY(20, $yPosition);
        $pdf->Cell(170, 8, $this->cleanText($sectionHeader), 0, 1, 'L', false);
        $yPosition += 6;
        
        // Dosage information
        try {
            $pdf->SetFont('opensans', '', 10);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 10);
        }
        $pdf->SetXY(120, $yPosition - 4);
        $doseTextPouch = '';
        if (!empty($items)) {
            $first = $items[0];
            if (isset($first['dose_text']) && $first['dose_text'] !== '') {
                $doseTextPouch = $first['dose_text'];
            }
        }
        if ($doseTextPouch === '') {
            $doseTextPouch = $this->doseMapper->getDosageText($sectionHeader);
        }
        if ($doseTextPouch !== '') {
            $pdf->Cell(70, 6, $this->cleanText($doseTextPouch), 0, 1, 'R');
        }
        $yPosition += 5;
        
        // Process pouch items
        foreach ($items as $item) {
            $this->processItemContent($pdf, $item, $yPosition);
        }
    }
    
    /**
     * Process Cápsula subsection (+ CÁPSULAS)
     */
    private function processCapsulaSubsection($pdf, array $items, int &$yPosition, $templateId, array $orderData): void
    {
        if (empty($items)) return;
        
        // Check if we need a new page
        if ($yPosition > 150) {
            $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
            $yPosition = 50;
        }
        
        // Subsection header (no background)
        // Use Brandon Black font for headers
        try {
            $pdf->SetFont('brandontextblack', '', 14);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 14);
        }
        $pdf->SetXY(20, $yPosition);
        $pdf->Cell(170, 8, $this->cleanText('+ CÁPSULAS'), 0, 1, 'L');
        $yPosition += 6;
        
        // Dosage information
        try {
            $pdf->SetFont('opensans', '', 10);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 10);
        }
        $pdf->SetXY(120, $yPosition - 4);
        $doseTextCaps = '';
        if (!empty($items)) {
            $first = $items[0];
            if (isset($first['dose_text']) && $first['dose_text'] !== '') {
                $doseTextCaps = $first['dose_text'];
            }
        }
        if ($doseTextCaps === '') {
            $doseTextCaps = $this->doseMapper->getDosageText('+ CÁPSULAS');
        }
        if ($doseTextCaps !== '') {
            $pdf->Cell(70, 6, $this->cleanText($doseTextCaps), 0, 1, 'R');
        }
        $yPosition += 5;
        
        // Process Cápsula items
        foreach ($items as $item) {
            $this->processItemContent($pdf, $item, $yPosition);
        }
    }
    
    /**
     * Process other items with item name as header
     */
    private function processOtherItem($pdf, array $item, int &$yPosition, $templateId, array $orderData): void
    {
        // Check if we need a new page
        if ($yPosition > 150) {
            $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
            $yPosition = 50;
        }
        
        // Item name as header
        // Use Brandon Black font for headers
        try {
            $pdf->SetFont('brandontextblack', '', 14);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 14);
        }
        // Draw background rectangle with transparency
        $this->drawHeaderBackground($pdf, 20, $yPosition, 170, 8);
        
        // Draw text on top (without background)
        $pdf->SetXY(20, $yPosition);
        $pdf->Cell(170, 8, $this->cleanText($this->cleanItemName($item['itm_name'])), 0, 1, 'L', false);
        $yPosition += 6;
        
        // Dosage information
        try {
            $pdf->SetFont('opensans', '', 10);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 10);
        }
        $pdf->SetXY(120, $yPosition - 4);
        $doseTextOther = isset($item['dose_text']) ? $item['dose_text'] : $this->doseMapper->getDosageText($item['itm_name']);
        if ($doseTextOther !== '') {
            $pdf->Cell(70, 6, $this->cleanText($doseTextOther), 0, 1, 'R');
        }
        $yPosition += 5;
        
        // Process item content
        $this->processItemContent($pdf, $item, $yPosition);
    }
    
    /**
     * Process item content (composition, etc.)
     */
    private function processItemContent($pdf, array $item, int &$yPosition): void
    {
        // Parse composition JSON and create ingredient list with dotted lines
        $composition = json_decode($item['composition'], true);
        if ($composition && is_array($composition)) {
            foreach ($composition as $ingredient) {
                $ingredientName = $this->cleanText($ingredient['ingredient']);
                $dosage = $this->cleanText($ingredient['dosage']);
                
                // Create dotted line between ingredient name and dosage
                try {
                    $pdf->SetFont('opensans', '', 10);
                } catch (\Exception $e) {
                    $pdf->SetFont('helvetica', '', 10);
                }
                $pdf->SetXY(20, $yPosition);
                $pdf->Cell(0, 4, $ingredientName, 0, 0, 'L');
                
                // Calculate position for dotted line
                $nameWidth = $pdf->GetStringWidth($ingredientName);
                $lineStart = 20 + $nameWidth + 2;
                $lineEnd = 170;
                $lineY = $yPosition + 2;
                
                // Draw dotted line manually
                $pdf->SetDrawColor(150, 150, 150);
                $dashLength = 2;
                $gapLength = 2;
                $currentX = $lineStart;
                
                while ($currentX < $lineEnd) {
                    $pdf->Line($currentX, $lineY, $currentX + $dashLength, $lineY);
                    $currentX += $dashLength + $gapLength;
                }
                
                // Add dosage at the end
                $pdf->SetXY($lineEnd - 5, $yPosition);
                $pdf->Cell(0, 4, $dosage, 0, 1, 'R');
                
                $yPosition += 6;
            }
        }
        
        $yPosition += 10;
    }

    private function cleanText(string $text): string
    {
        // First, replace problematic characters that don't convert well
        $text = str_replace([
            '•', '–', '—', '…', '"', '"', "'", "'", '€', '°', 'º', 'ª', 
            'â€¢', 'â€"', 'â€"', 'â€¦', 'â‚¬', 'Â°', 'Âº', 'Âª'
        ], [
            '-', '-', '-', '...', '"', '"', "'", "'", 'EUR', 'o', 'o', 'a',
            '-', '-', '-', '...', 'EUR', 'o', 'o', 'a'
        ], $text);
        
        return $text;
    }

    /**
     * Clean item name by removing severity suffixes and parenthetical content for "other" items
     */
    private function cleanItemName(string $itemName): string
    {
        // Remove severity suffixes (both with regular dash and en dash)
        $severityPatterns = [
            ' - Leve', ' - Moderado', ' - Moderada', ' - Severo', ' - Severa', ' - Grave',
            ' – Leve', ' – Moderado', ' – Moderada', ' – Severo', ' – Severa', ' – Grave'
        ];
        
        $cleanedName = $itemName;
        foreach ($severityPatterns as $pattern) {
            $cleanedName = str_replace($pattern, '', $cleanedName);
        }
        
        // Remove parenthetical content (everything between parentheses)
        $cleanedName = preg_replace('/\s*\([^)]*\)/', '', $cleanedName);
        
        // Remove trailing colons
        $cleanedName = rtrim($cleanedName, ':');
        
        return trim($cleanedName);
    }

    /**
     * Set the standard text color for the PDF
     */
    private function setStandardTextColor($pdf): void
    {
        $pdf->SetTextColor(63, 31, 32); // #3F1F20
    }

    /**
     * Draw header background rectangle with 10% opacity and rounded corners
     */
    private function drawHeaderBackground($pdf, float $x, float $y, float $width, float $height): void
    {
        // Set the fill color to #3F1F20 (RGB: 63, 31, 32)
        $pdf->SetFillColor(63, 31, 32);
        
        // Set alpha transparency to 10% (0.1)
        $pdf->SetAlpha(0.1);
        
        // Draw the background rectangle with rounded corners
        // Parameters: x, y, width, height, radius, corners (all rounded), style (fill only)
        $pdf->RoundedRect($x, $y, $width, $height, 1, '1111', 'F');
        
        // Reset alpha transparency to fully opaque
        $pdf->SetAlpha(1);
    }


    public function getPdfPath(string $filename): string
    {
        $path = $this->outputPath . '/' . $filename;
        if (!file_exists($path)) {
            throw new \Exception('PDF file not found: ' . $filename);
        }
        return $path;
    }

    public function deletePdf(string $filename): bool
    {
        $path = $this->outputPath . '/' . $filename;
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Add a new page with the same base template
     */
    private function addNewPageWithTemplate($pdf, $templateId, $orderData): void
    {
        $pdf->AddPage();
        $pdf->useTemplate($templateId);
        
        // Disable any default drawing behavior on new pages
        $pdf->SetDrawColor(255, 255, 255); // Set to white/transparent
        $pdf->SetLineWidth(0);
        
        // Reset font and color for new page
        try {
            $pdf->SetFont('opensans', '', 12);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 12);
        }
        $this->setStandardTextColor($pdf);
        
        // Add patient data
        try {
            $pdf->SetFont('opensans', '', 10);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 10);
        }
        $pdf->SetXY(125, 15);
        $pdf->Cell(70, 5, $this->cleanText($orderData['usr_name']), 0, 1, 'R');
        $pdf->SetXY(125, 20);
        $pdf->Cell(70, 5, $this->cleanText('Documento: ' . $orderData['usr_cpf']), 0, 1, 'R');
        $pdf->SetXY(125, 25);
        $pdf->Cell(70, 5, $this->cleanText('Sexo: [Não informado]'), 0, 1, 'R');
        $pdf->SetXY(125, 30);
        $pdf->Cell(70, 5, $this->cleanText('Telefone: ' . $orderData['usr_phone']), 0, 1, 'R');
        $pdf->SetXY(125, 35);
        $pdf->Cell(70, 5, $this->cleanText('Prescrição: ' . $orderData['ord_id']), 0, 1, 'R');
        
        // Date at bottom right
        try {
            $pdf->SetFont('opensans', '', 12);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 12);
        }
        $pdf->SetXY(125, 250);
        $pdf->Cell(70, 5, 'Data: ' . date('d/m/Y H:i'), 0, 1, 'R');
    }
}
