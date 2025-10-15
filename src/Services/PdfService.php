<?php

namespace App\Services;

use FPDF;
use setasign\Fpdi\Fpdi;
use App\Services\LoggerService;

class PdfService
{
    private LoggerService $logger;
    private string $uploadPath;
    private string $outputPath;
    private string $fontsPath;
    private int $maxFileSize;

    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        $this->uploadPath = __DIR__ . '/../../storage/uploads';
        $this->outputPath = __DIR__ . '/../../storage/output';
        $this->fontsPath = __DIR__ . '/../../storage/fonts';
        $this->maxFileSize = (int) ($_ENV['PDF_MAX_SIZE'] ?? 10485760); // 10MB default

        $this->ensureDirectoriesExist();
        $this->addCustomFonts();
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

    private function addCustomFonts(): void
    {
        // This method will be called to add custom fonts to the PDF
        // The actual font addition will be done in the PDF generation methods
    }

    public function createPrescriptionPdf(array $orderData, array $items): string
    {
        $filename = 'receituario_' . $orderData['ord_id'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $filepath = $this->outputPath . '/' . $filename;
        $basePdfPath = __DIR__ . '/../../storage/pdf/receituario-base.pdf';

        try {
            // Check if base PDF exists
            if (!file_exists($basePdfPath)) {
                throw new \Exception("Base PDF template not found: " . $basePdfPath);
            }

            // Start output buffering to catch any PHP errors
            ob_start();

            // Use FPDI to import the base PDF
            $pdf = new Fpdi();
            $pdf->setSourceFile($basePdfPath);
            $templateId = $pdf->importPage(1);
            
            // FPDF has limited font support, using built-in fonts for reliability
            $this->logger->info('Using FPDF built-in fonts for reliability');
            
            // Set page settings
            $pdf->SetAutoPageBreak(false); // Disable auto page break, we'll handle it manually
            
            // Add first page with base template
            $pdf->AddPage();
            $pdf->useTemplate($templateId);
            
            
            
            // Date and Order info
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(20, 50);
            $pdf->Cell(0, 8, 'Data: ' . date('d/m/Y H:i'), 0, 1);
            
            // Patient information (moved up a bit, less bottom)
            $pdf->SetFont('Arial', '', 10);
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
            
            // Group items by Dia/Noite and separate pouch from Cápsula content
            $groupedItems = $this->groupItemsByTimeAndType($items);
            
            // Prescriptions - Structured Layout
            $yPosition = 50;
            $itemNumber = 1;
            
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
            

            $pdf->Output('F', $filepath);

            // Clear any output buffer content
            $output = ob_get_clean();
            if (!empty($output)) {
                $this->logger->warning('Output buffer content during PDF creation', ['output' => $output]);
            }

            // Verify file was created
            if (!file_exists($filepath)) {
                throw new \Exception("PDF file was not created at: " . $filepath);
            }

            $this->logger->info('Prescription PDF created', [
                'order_id' => $orderData['ord_id'],
                'filename' => $filename,
                'filepath' => $filepath,
                'file_exists' => file_exists($filepath),
                'file_size' => filesize($filepath)
            ]);

            return $filename;

        } catch (\Exception $e) {
            // Clear any output buffer content
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $this->logger->error('Prescription PDF creation failed', [
                'order_id' => $orderData['ord_id'],
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
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

    public function getFontsPath(): string
    {
        return $this->fontsPath;
    }

    public function addFont(string $fontName, string $fontFile): bool
    {
        $fontPath = $this->fontsPath . '/' . $fontFile;
        if (file_exists($fontPath)) {
            return true;
        }
        return false;
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
        
        // Use iconv to convert UTF-8 to Windows-1252 (recommended for FPDF)
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'windows-1252', $text);
            if ($converted !== false) {
                return $converted;
            }
        }
        
        // Fallback: use utf8_decode() for basic UTF-8 to ISO-8859-1 conversion
        if (function_exists('utf8_decode')) {
            return utf8_decode($text);
        }
        
        // Last resort: return as-is (may cause display issues)
        return $text;
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
     * Get section header based on time group and type
     */
    private function getSectionHeader(string $timeGroup, string $type): string
    {
        if ($timeGroup === 'dia' && $type === 'pouch') {
            return 'MY FORMULA | DIA';
        }
        if ($timeGroup === 'dia' && $type === 'capsula') {
            return '+ CÁPSULAS';
        }
        if ($timeGroup === 'noite' && $type === 'pouch') {
            return 'MY FORMULA | NOITE';
        }
        if ($timeGroup === 'noite' && $type === 'capsula') {
            return '+ CÁPSULAS';
        }
        if ($timeGroup === 'other') {
            return 'FÓRMULA ' . rand(1, 999); // Random number for other items
        }
        
        return 'FÓRMULA';
    }
    
    /**
     * Get dosage text based on type
     */
    private function getDosageText(string $type): string
    {
        if ($type === 'pouch') {
            return '1 DOSE = 1 SCOOP XX G (COLHER-MEDIDA)';
        }
        if ($type === 'capsula') {
            return '1 DOSE = 3 CÁPSULAS';
        }
        return '1 DOSE = 1 SCOOP';
    }

    /**
     * Sort and group items by priority: MY FORMULA (pouch + Cápsula Dia), then others
     */
    private function sortItemsByPriority(array $items): array
    {
        // First, separate items into groups
        $myFormulaItems = [];
        $otherItems = [];
        
        foreach ($items as $item) {
            $itemName = strtolower($item['itm_name']);
            $isPouch = $this->isPouchItem($item);
            $isCapsulaDia = strpos($itemName, 'cápsula') !== false && strpos($itemName, 'dia') !== false;
            
            // Items that are pouch content OR Cápsula items containing Dia go to MY FORMULA
            if ($isPouch || $isCapsulaDia) {
                $myFormulaItems[] = $item;
            } else {
                $otherItems[] = $item;
            }
        }
        
        // Sort MY FORMULA items: pouch items first, then Cápsula Dia items
        usort($myFormulaItems, function($a, $b) {
            $aIsPouch = $this->isPouchItem($a);
            $bIsPouch = $this->isPouchItem($b);
            
            if ($aIsPouch && !$bIsPouch) return -1;
            if (!$aIsPouch && $bIsPouch) return 1;
            return 0;
        });
        
        // Combine: MY FORMULA items first, then other items
        return array_merge($myFormulaItems, $otherItems);
    }
    
    /**
     * Check if item is a pouch item (you may need to adjust this based on your data structure)
     */
    private function isPouchItem(array $item): bool
    {
        // Check various fields that might indicate pouch content
        $itemName = strtolower($item['itm_name']);
        
        // Check if item name contains pouch-related keywords
        $pouchKeywords = ['pouch', 'sachet', 'saco', 'envelope'];
        foreach ($pouchKeywords as $keyword) {
            if (strpos($itemName, $keyword) !== false) {
                return true;
            }
        }
        
        // You might also check other fields like subscription, composition, etc.
        // For example, if there's a field that indicates pouch content:
        // if (isset($item['type']) && $item['type'] === 'pouch') return true;
        
        return false;
    }

    /**
     * Determine formula name based on item name content and type
     */
    private function getFormulaName(string $itemName, int $itemNumber, array $item = null): string
    {
        $itemNameLower = strtolower($itemName);
        
        // Check if this is a pouch item or Cápsula Dia item (MY FORMULA items)
        if ($item && ($this->isPouchItem($item) || (strpos($itemNameLower, 'cápsula') !== false && strpos($itemNameLower, 'dia') !== false))) {
            // For MY FORMULA items, determine if it's Dia or Noite
            if (strpos($itemNameLower, 'dia') !== false) {
                return 'MY FORMULA | DIA';
            }
            if (strpos($itemNameLower, 'noite') !== false) {
                return 'MY FORMULA | NOITE';
            }
            // Default for MY FORMULA items
            return 'MY FORMULA';
        }
        
        // For other items, use the formula number
        return 'FÓRMULA ' . $itemNumber;
    }

    /**
     * Process pouch section (MY FORMULA | DIA/NOITE)
     */
    private function processPouchSection($pdf, string $timeGroup, array $items, int &$yPosition, $templateId, array $orderData): void
    {
        if (empty($items)) return;
        
        // Check if we need a new page
        if ($yPosition > 200) {
            $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
            $yPosition = 50;
        }
        
        // Main section header
        $sectionHeader = ($timeGroup === 'dia') ? 'MY FORMULA | DIA' : 'MY FORMULA | NOITE';
        
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetXY(20, $yPosition);
        $pdf->Cell(170, 8, $this->cleanText($sectionHeader), 1, 1, 'L', true);
        $yPosition += 10;
        
        // Dosage information
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(120, $yPosition - 8);
        $pdf->Cell(70, 6, $this->cleanText('1 DOSE = 1 SCOOP XX G (COLHER-MEDIDA)'), 0, 1, 'R');
        $yPosition += 5;
        
        // Process pouch items (hide item name and plan for MY FORMULA sections)
        foreach ($items as $item) {
            $this->processItemContent($pdf, $item, $yPosition, $templateId, $orderData, false);
        }
    }
    
    /**
     * Process Cápsula subsection (+ CÁPSULAS)
     */
    private function processCapsulaSubsection($pdf, array $items, int &$yPosition, $templateId, array $orderData): void
    {
        if (empty($items)) return;
        
        // Check if we need a new page
        if ($yPosition > 200) {
            $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
            $yPosition = 50;
        }
        
        // Subsection header (no background)
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetXY(20, $yPosition);
        $pdf->Cell(170, 8, $this->cleanText('+ CÁPSULAS'), 0, 1, 'L');
        $yPosition += 10;
        
        // Dosage information
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(120, $yPosition - 8);
        $pdf->Cell(70, 6, $this->cleanText('1 DOSE = 3 CÁPSULAS'), 0, 1, 'R');
        $yPosition += 5;
        
        // Process Cápsula items (hide item name and plan for subsections)
        foreach ($items as $item) {
            $this->processItemContent($pdf, $item, $yPosition, $templateId, $orderData, false);
        }
    }
    
    /**
     * Process other items with item name as header
     */
    private function processOtherItem($pdf, array $item, int &$yPosition, $templateId, array $orderData): void
    {
        // Check if we need a new page
        if ($yPosition > 200) {
            $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
            $yPosition = 50;
        }
        
        // Item name as header
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetXY(20, $yPosition);
        $pdf->Cell(170, 8, $this->cleanText($item['itm_name']), 1, 1, 'L', true);
        $yPosition += 10;
        
        // Dosage information
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(120, $yPosition - 8);
        $pdf->Cell(70, 6, $this->cleanText('1 DOSE = 1 SCOOP'), 0, 1, 'R');
        $yPosition += 5;
        
        // Process item content (hide item name and plan since it's already in the header)
        $this->processItemContent($pdf, $item, $yPosition, $templateId, $orderData, false);
    }
    
    /**
     * Process item content (composition, etc.)
     */
    private function processItemContent($pdf, array $item, int &$yPosition, $templateId, array $orderData, bool $showItemInfo = true): void
    {
        // Item name and plan (only show if requested)
        if ($showItemInfo) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetXY(20, $yPosition);
            $pdf->Cell(0, 6, $this->cleanText($item['itm_name']), 0, 1);
            $yPosition += 8;
            
            // Subscription plan
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(20, $yPosition);
            $pdf->Cell(0, 5, $this->cleanText('Plano: ' . $item['subscription']), 0, 1);
            $yPosition += 8;
        }
        
        // Parse composition JSON and create ingredient list with dotted lines
        $composition = json_decode($item['composition'], true);
        if ($composition && is_array($composition)) {
            
            foreach ($composition as $ingredient) {
                // Check if we need a new page for ingredients
                if ($yPosition > 250) {
                    $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
                    $yPosition = 50;
                }
                
                $ingredientName = $this->cleanText($ingredient['ingredient']);
                $dosage = $this->cleanText($ingredient['dosage']);
                
                // Create dotted line between ingredient name and dosage
                $pdf->SetFont('Arial', '', 10);
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

    /**
     * Add a new page with the same base template
     */
    private function addNewPageWithTemplate($pdf, $templateId, $orderData): void
    {
        $pdf->AddPage();
        $pdf->useTemplate($templateId);
        
        // Reset font and color for new page
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(0, 0, 0);
        
        // Add patient data (moved up a bit, less bottom)
        $pdf->SetFont('Arial', '', 10);
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
    }
}
