<?php

namespace App\Services;

require_once __DIR__ . '/item-name-mappings.php';

use TCPDF;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Services\LoggerService;
use App\Services\DoseMapper;
use App\Services\ColorSchemeService;

class TcpdfService
{
    private LoggerService $logger;
    private string $uploadPath;
    private string $outputPath;
    private string $fontsPath;
    private int $maxFileSize;
    private DoseMapper $doseMapper;
    private ColorSchemeService $colorSchemeService;
    private int $headerLeftPosition;
    private int $headerTextLeftPosition; // Separate position for header text (independent from background)
    private int $headerTextYOffset; // Y offset for header text (independent from background)
    private int $headerBackgroundWidth;
    private int $headerBackgroundHeight;
    private int $headerBackgroundXOffset;
    private int $headerBackgroundYOffset;
    private int $headerSpacing;
    private int $patientInfoXPosition; // X position for patient information (right side)
    private int $patientInfoWidth; // Width for patient information cells
    private int $patientInfoHeight; // Height for patient information cells
    private int $patientInfoStartY; // Starting Y position for patient information
    private int $patientInfoSpacing; // Spacing between patient info lines
    private int $patientNameSpacing; // Spacing after patient name
    private int $doseInfoXPosition; // X position for dosage information
    private int $doseInfoWidth; // Width for dosage information cells
    private int $doseInfoHeight; // Height for dosage information cells
    private int $doseInfoYOffset; // Y offset for dosage information relative to yPosition
    private int $dateXPosition; // X position for date
    private int $dateYPosition; // Y position for date
    private int $pageBreakThreshold; // Y position threshold for page breaks
    private int $newPageYPosition; // Y position when starting new page
    private int $ingredientLineYOffset; // Y offset for ingredient dotted lines
    private int $ingredientSpacing; // Spacing after ingredient sections
    private int $dottedLineDashLength; // Length of dotted line dashes
    private int $dottedLineGapLength; // Length of dotted line gaps

    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        $this->uploadPath = __DIR__ . '/../../storage/uploads';
        $this->outputPath = __DIR__ . '/../../storage/output';
        $this->fontsPath = __DIR__ . '/../../storage/fonts';
        $this->maxFileSize = (int) ($_ENV['PDF_MAX_SIZE'] ?? 10485760); // 10MB default
        $this->doseMapper = new DoseMapper();
        $this->colorSchemeService = new ColorSchemeService();
        $this->headerLeftPosition = 20; // Global left position for all headers
        $this->headerTextLeftPosition = 16; // Separate position for header text (independent from background)
        $this->headerTextYOffset = -1; // Y offset for header text (negative moves text up, positive moves down)
        $this->headerBackgroundWidth = 180; // Global width for header background boxes
        $this->headerBackgroundHeight = 6; // Global height for header background boxes
        $this->headerBackgroundXOffset = -5; // Global X offset for header background boxes (relative to headerLeftPosition)
        $this->headerBackgroundYOffset = 0; // Global Y offset for header background boxes (relative to yPosition)
        $this->headerSpacing = 4; // Global spacing after headers
        $this->patientInfoXPosition = 125; // X position for patient information (right side)
        $this->patientInfoWidth = 70; // Width for patient information cells
        $this->patientInfoHeight = 5; // Height for patient information cells
        $this->patientInfoStartY = 15; // Starting Y position for patient information
        $this->patientInfoSpacing = 4; // Spacing between patient info lines
        $this->patientNameSpacing = 6; // Spacing after patient name
        $this->doseInfoXPosition = 130; // X position for dosage information
        $this->doseInfoWidth = 70; // Width for dosage information cells
        $this->doseInfoHeight = 6; // Height for dosage information cells
        $this->doseInfoYOffset = -4; // Y offset for dosage information relative to yPosition
        $this->dateXPosition = 134; // X position for date
        $this->dateYPosition = 255; // Y position for date
        $this->pageBreakThreshold = 250; // Y position threshold for page breaks
        $this->newPageYPosition = 45; // Y position when starting new page
        $this->ingredientLineYOffset = 2; // Y offset for ingredient dotted lines
        $this->ingredientSpacing = 2; // Spacing after ingredient sections
        $this->dottedLineDashLength = 2; // Length of dotted line dashes
        $this->dottedLineGapLength = 2; // Length of dotted line gaps

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

    /**
     * Log PDF download to a log file
     */
    private function logPdfDownload(string $type, string $orderId, string $filename): void
    {
        $logPath = __DIR__ . '/../../storage/logs/pdf-downloads.log';
        $logDir = dirname($logPath);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $type - Order: $orderId - File: $filename" . PHP_EOL;

        file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);

        $this->logger->info('PDF download logged', [
            'type' => $type,
            'order_id' => $orderId,
            'filename' => $filename,
            'log_path' => $logPath
        ]);
    }

    public function createPrescriptionPdf(array $orderData, array $items, bool $previewMode = false): string
    {
        $filename = 'receituario_' . $orderData['ord_id'] . '_' . date('Y-m-d_H-i-s') . '.pdf';

        $this->logger->info('Using TCPDF service for PDF generation');

        try {
            // Use FPDI to import the base PDF template
            $basePdfPath = __DIR__ . '/../../storage/pdf/receituario-base.pdf';
            if (!file_exists($basePdfPath)) {
                throw new \Exception('Base PDF template not found: ' . $basePdfPath);
            }

            $pdf = new Fpdi();
            $pdf->setSourceFile($basePdfPath);
            $templateId = $pdf->importPage(1);

            $this->preparePdf($pdf);

            // Render a single order
            $this->renderOrderPrescription($pdf, $templateId, $orderData, $items);

            // Log the download
            $this->logPdfDownload('receituario', $orderData['ord_id'], $filename);

            // Output the PDF to browser (preview or download)
            $outputMode = $previewMode ? 'I' : 'D';
            $pdf->Output($filename, $outputMode);

            $this->logger->info('Prescription PDF generated and sent to browser', [
                'order_id' => $orderData['ord_id'],
                'filename' => $filename
            ]);

            return $filename;
        } catch (\Exception $e) {
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
     * Generate one combined PDF for multiple orders
     */
    public function createBatchPrescriptionPdf(array $orders, bool $previewMode = false): string
    {
        $filename = 'receituario_batch_' . date('Y-m-d_H-i-s') . '.pdf';

        $this->logger->info('Generating batch prescription PDF', [
            'orders_count' => count($orders)
        ]);

        try {
            $basePdfPath = __DIR__ . '/../../storage/pdf/receituario-base.pdf';
            if (!file_exists($basePdfPath)) {
                throw new \Exception('Base PDF template not found: ' . $basePdfPath);
            }

            $pdf = new Fpdi();
            $pdf->setSourceFile($basePdfPath);
            $templateId = $pdf->importPage(1);

            $this->preparePdf($pdf);

            $orderIds = [];
            foreach ($orders as $o) {
                if (!isset($o['order']) || !isset($o['items'])) {
                    continue;
                }
                $orderIds[] = $o['order']['ord_id'];
                $this->renderOrderPrescription($pdf, $templateId, $o['order'], $o['items']);
            }

            // Log the batch download
            $this->logPdfDownload('receituario_batch', implode(',', $orderIds), $filename);

            // Output the PDF to browser (preview or download)
            $outputMode = $previewMode ? 'I' : 'D';
            $pdf->Output($filename, $outputMode);

            $this->logger->info('Batch prescription PDF generated and sent to browser', [
                'filename' => $filename,
                'orders_count' => count($orders)
            ]);

            return $filename;
        } catch (\Exception $e) {
            $this->logger->error('Batch prescription PDF creation failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Common PDF preparation (fonts, colors, settings)
     */
    private function preparePdf($pdf): void
    {
        // Disable default TCPDF header/footer behavior
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Add fonts when available
        $brandonBlackPhpPath = $this->fontsPath . '/brandontextblack.php';
        if (file_exists($brandonBlackPhpPath)) {
            try {
                $pdf->AddFont('brandontextblack', '', $brandonBlackPhpPath, true);
                $this->logger->info('Brandon Black font loaded successfully from brandontextblack.php');
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load Brandon Black font from PHP file: ' . $e->getMessage());
            }
        }
        $openSansPhpPath = $this->fontsPath . '/opensans.php';
        if (file_exists($openSansPhpPath)) {
            try {
                $pdf->AddFont('opensans', '', $openSansPhpPath, true);
                $this->logger->info('OpenSans font loaded successfully from opensans.php');
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load OpenSans font from PHP file: ' . $e->getMessage());
            }
        }
        $openSansBoldPhpPath = $this->fontsPath . '/opensansb.php';
        if (file_exists($openSansBoldPhpPath)) {
            try {
                $pdf->AddFont('opensansb', '', $openSansBoldPhpPath, true);
                $this->logger->info('OpenSans Bold font loaded successfully from opensansb.php');
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load OpenSans Bold font from PHP file: ' . $e->getMessage());
            }
        }
        $openSansLightPhpPath = $this->fontsPath . '/opensansl.php';
        if (file_exists($openSansLightPhpPath)) {
            try {
                $pdf->AddFont('opensansl', '', $openSansLightPhpPath, true);
                $this->logger->info('OpenSans Light font loaded successfully from opensansl.php');
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load OpenSans Light font from PHP file: ' . $e->getMessage());
            }
        }

        // Add Brandon Regular font
        $brandonRegPhpPath = $this->fontsPath . '/brandon_reg.php';
        if (file_exists($brandonRegPhpPath)) {
            try {
                $pdf->AddFont('brandon_reg', '', $brandonRegPhpPath, true);
                $this->logger->info('Brandon Regular font loaded successfully from brandon_reg.php');
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load Brandon Regular font from PHP file: ' . $e->getMessage());
            }
        }

        // Add Brandon Medium font
        $brandonMedPhpPath = $this->fontsPath . '/brandon_med.php';
        if (file_exists($brandonMedPhpPath)) {
            try {
                $pdf->AddFont('brandon_med', '', $brandonMedPhpPath, true);
                $this->logger->info('Brandon Medium font loaded successfully from brandon_med.php');
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load Brandon Medium font from PHP file: ' . $e->getMessage());
            }
        }

        // Set page settings and defaults
        $pdf->SetAutoPageBreak(false, 0);
        $this->setStandardTextColor($pdf);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetLineWidth(0);
    }

    /**
     * Render a full prescription for a single order into the given PDF
     */
    private function renderOrderPrescription($pdf, $templateId, array $orderData, array $items): void
    {
        // Add first page with base template for this order
        $pdf->AddPage();
        $pdf->useTemplate($templateId);

        // Patient information
        try {
            $pdf->SetFont('opensansb', '', 11);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 11);
        }
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY);
        $patientName = $orderData['Nome'] ?? ($orderData['usr_name'] ?? '');
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText($this->capitalizePatientName($patientName)), 0, 1, 'R');

        try {
            $pdf->SetFont('opensans', '', 7.7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7.7);
        }
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing);
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Documento: ' . $orderData['usr_cpf']), 0, 1, 'R');
        $genero = $orderData['Genero'] ?? null;
        $sexoLabel = '[Não informado]';
        if ($genero === 1 || $genero === '1') {
            $sexoLabel = 'Masculino';
        } elseif ($genero === 2 || $genero === '2') {
            $sexoLabel = 'Feminino';
        }

        // Calculate position offset based on whether sexo line is shown
        $positionOffset = ($sexoLabel !== '[Não informado]') ? 1 : 0;

        // Only show sexo line if it's not "[Não informado]"
        if ($sexoLabel !== '[Não informado]') {
            $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + $this->patientInfoSpacing);
            $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Sexo: ' . $sexoLabel), 0, 1, 'R');
        }
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + ($this->patientInfoSpacing * (1 + $positionOffset)));
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Telefone: ' . $this->formatPhoneNumber($orderData['usr_phone'])), 0, 1, 'R');
        $pdf->SetFont('helvetica', 'B', 7.7);
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + ($this->patientInfoSpacing * (2 + $positionOffset)));
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Prescrição: ' . $orderData['ord_id']), 0, 1, 'R');

        try {
            $pdf->SetFont('opensans', '', 7.7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7.7);
        }
        $pdf->SetXY($this->dateXPosition, $this->dateYPosition);
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->formatDatePortuguese($orderData['created_at']), 0, 1, 'C');

        // Enrich items with precomputed dose text from mapper
        foreach ($items as &$itemRef) {
            if (isset($itemRef['itm_name'])) {
                $doseText = $this->doseMapper->getDosageText($itemRef['itm_name']);
                $itemRef['dose_text'] = is_string($doseText) ? $doseText : '';
            }
        }
        unset($itemRef);

        $groupedItems = $this->groupItemsByTimeAndType($items);
        $yPosition = $this->newPageYPosition;

        foreach ($groupedItems as $timeGroup => $typeGroups) {
            if (($timeGroup === 'dia' || $timeGroup === 'noite') && (!empty($typeGroups['pouch']) || !empty($typeGroups['capsula']))) {
                $pouchItems = $typeGroups['pouch'] ?? [];
                $capsulaItems = $typeGroups['capsula'] ?? [];
                $this->processPouchSection($pdf, $timeGroup, $pouchItems, $capsulaItems, $yPosition, $templateId, $orderData);
            }
            if ($timeGroup === 'other') {
                foreach ($typeGroups as $item) {
                    $this->processOtherItem($pdf, $item, $yPosition, $templateId, $orderData);
                }
            }
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
            'other' => []
        ];

        foreach ($items as $item) {
            $itemName = strtolower($item['itm_name']);
            $isPouch = $this->isPouchItem($item);
            $isCapsula = strpos($itemName, 'cápsula') !== false;

            // Determine time group - use word boundaries to avoid false matches
            $timeGroup = 'other';
            $diaMatch = preg_match('/\bdia\b/', $itemName);
            $noiteMatch = preg_match('/\bnoite\b/', $itemName);

            if ($diaMatch) {
                $timeGroup = 'dia';
            } elseif ($noiteMatch) {
                $timeGroup = 'noite';
            }

            // Debug logging for grouping decisions
            $this->logger->info('Item grouping debug', [
                'original_name' => $item['itm_name'],
                'lowercase_name' => $itemName,
                'dia_match' => $diaMatch,
                'noite_match' => $noiteMatch,
                'is_pouch' => $isPouch,
                'is_capsula' => $isCapsula,
                'final_time_group' => $timeGroup,
                'item_id' => $item['itm_id'] ?? 'unknown'
            ]);

            // Add item to appropriate group
            if ($timeGroup === 'dia' || $timeGroup === 'noite') {
                // For dia/noite items, separate pouch and capsula
                if ($isCapsula) {
                    $grouped[$timeGroup]['capsula'][] = $item;
                } else {
                    $grouped[$timeGroup]['pouch'][] = $item;
                }
            } else {
                // For other items, just add directly to the array
                $grouped[$timeGroup][] = $item;
            }
        }

        // Debug logging for final grouped structure
        $this->logger->info('Final grouped structure', [
            'dia_pouch_count' => count($grouped['dia']['pouch']),
            'dia_capsula_count' => count($grouped['dia']['capsula']),
            'noite_pouch_count' => count($grouped['noite']['pouch']),
            'noite_capsula_count' => count($grouped['noite']['capsula']),
            'other_count' => count($grouped['other']),
            'dia_pouch_items' => array_map(function ($item) {
                return $item['itm_name'];
            }, $grouped['dia']['pouch']),
            'dia_capsula_items' => array_map(function ($item) {
                return $item['itm_name'];
            }, $grouped['dia']['capsula']),
            'noite_pouch_items' => array_map(function ($item) {
                return $item['itm_name'];
            }, $grouped['noite']['pouch']),
            'noite_capsula_items' => array_map(function ($item) {
                return $item['itm_name'];
            }, $grouped['noite']['capsula']),
            'other_items' => array_map(function ($item) {
                return $item['itm_name'];
            }, $grouped['other'])
        ]);

        return $grouped;
    }

    /**
     * Generate Sticker (Rótulo) PDF for an order
     */
    /**
     * Create a single rotulo on the PDF at specified position
     * 
     * @param Fpdi $pdf The PDF object
     * @param array $rotuloData Data for this specific rotulo
     * @param int $x X position on the page
     * @param int $y Y position on the page
     * @param int $width Width of the rotulo area
     * @param int $height Height of the rotulo area
     */
    /**
     * Abbreviate patient name if it's too long for the available width
     * 
     * @param object $pdf The PDF object
     * @param string $name The patient name to abbreviate
     * @param float $maxWidth Maximum width available for the name
     * @param string $font Font name
     * @param float $fontSize Font size
     * @return string Abbreviated name if needed
     */
    private function abbreviatePatientName($pdf, string $name, float $maxWidth, string $font, float $fontSize): string
    {
        // Set font temporarily to measure text width
        try {
            $pdf->SetFont($font, '', $fontSize);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', $fontSize);
        }

        // Get text width
        $textWidth = $pdf->GetStringWidth($name);

        // If text fits, return as is
        if ($textWidth <= $maxWidth) {
            return $name;
        }

        // Split name into words
        $words = explode(' ', trim($name));

        // If single word, truncate it
        if (count($words) === 1) {
            $word = $words[0];
            while ($pdf->GetStringWidth($word . '...') > $maxWidth && strlen($word) > 3) {
                $word = substr($word, 0, -1);
            }
            return $word . '...';
        }

        // For multiple words, try different abbreviation strategies
        $strategies = [
            // Strategy 1: First name + last name (current pouch logic)
            function ($words) {
                if (count($words) >= 2) {
                    return $words[0] . ' ' . end($words);
                }
                return $words[0];
            },
            // Strategy 2: First name + last name initial
            function ($words) {
                if (count($words) >= 2) {
                    return $words[0] . ' ' . substr(end($words), 0, 1) . '.';
                }
                return $words[0];
            },
            // Strategy 3: First name only
            function ($words) {
                return $words[0];
            },
            // Strategy 4: First initial + last name
            function ($words) {
                if (count($words) >= 2) {
                    return substr($words[0], 0, 1) . '. ' . end($words);
                }
                return substr($words[0], 0, 1) . '.';
            },
            // Strategy 5: First initial + last initial
            function ($words) {
                if (count($words) >= 2) {
                    return substr($words[0], 0, 1) . '. ' . substr(end($words), 0, 1) . '.';
                }
                return substr($words[0], 0, 1) . '.';
            }
        ];

        // Try each strategy until one fits
        foreach ($strategies as $strategy) {
            $abbreviated = $strategy($words);
            if ($pdf->GetStringWidth($abbreviated) <= $maxWidth) {
                return $abbreviated;
            }
        }

        // If all strategies fail, return first word truncated
        $firstWord = $words[0];
        while ($pdf->GetStringWidth($firstWord . '...') > $maxWidth && strlen($firstWord) > 3) {
            $firstWord = substr($firstWord, 0, -1);
        }
        return $firstWord . '...';
    }

    /**
     * Get text color for rotulo based on product type and layout type
     * 
     * @param string $productType Product type (DIA, NOITE, CAPSULAS, or default)
     * @param string $layoutType Layout type (pouch, capsula, horizontal)
     * @return array Text color as [C, M, Y, K] array
     */
    private function getRotuloTextColor(string $productType = 'default', string $layoutType = 'capsula'): array
    {
        // If layout type is pouch, return pouch text color
        if ($layoutType === 'pouch') {
            // Text: Dark for contrast on light background
            return [0, 0, 0, 100];
        }

        switch (mb_strtoupper($productType, 'UTF-8')) {
            case 'DIA':
            case 'NOITE':
            case 'PREMIO':
            case 'POUCH':
                // Text: Dark for contrast on light background
                return [0, 0, 0, 100];

            case 'CAPSULAS':
                // Text: White for contrast
                return [0, 0, 0, 0];

            case 'CAPSULA':
                // Text: White for contrast
                return [0, 0, 0, 0];

            case 'MY FORMULA':
                // Text: White for contrast
                return [0, 0, 0, 0];

            case 'OTHER':
                // Text: White for contrast
                return [0, 0, 0, 0];

            case 'AVANÇADA':
                // Text: White for contrast
                return [0, 0, 0, 0];

            case 'ESSENCIAL':
                // Text: White for contrast
                return [0, 0, 0, 0];

            case 'PREMIUM':
                // Text: Custom color for premium
                return [5, 10, 31, 0];

            default:
                // Text: Black
                return [0, 0, 0, 100];
        }
    }

    /**
     * Set color scheme for rotulo based on product type and layout type
     * 
     * @param Fpdi $pdf The PDF object
     * @param string $productType Product type (DIA, NOITE, CAPSULAS, or default)
     * @param string $layoutType Layout type (pouch, capsula, horizontal)
     */
    private function setRotuloColorScheme($pdf, string $productType = 'default', string $layoutType = 'capsula')
    {
        // Debug logging to see what product type and layout type are being received
        $this->logger->debug('setRotuloColorScheme called', [
            'productType' => $productType,
            'productType_upper' => mb_strtoupper($productType, 'UTF-8'),
            'layoutType' => $layoutType
        ]);

        // Get text color from the dedicated function
        $textColor = $this->getRotuloTextColor($productType, $layoutType);

        // If layout type is pouch, set pouch color scheme and return
        if ($layoutType === 'pouch') {
            // Standard pouch color scheme for all pouch products
            // Background: C=5, M=10, Y=28, K=6
            $pdf->SetFillColor(5, 10, 28, 6);
            // Border: C=5, M=10, Y=28, K=6 (same as background)
            $pdf->SetDrawColor(5, 10, 28, 6);
            // Text: Use color from getRotuloTextColor function
            $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
            return;
        }

        switch (mb_strtoupper($productType, 'UTF-8')) {
            case 'DIA':
            case 'NOITE':
            case 'PREMIO':
            case 'POUCH':
                // Standard pouch color scheme for all pouch products
                // Background: C=5, M=10, Y=28, K=6
                $pdf->SetFillColor(5, 10, 28, 6);
                // Border: C=5, M=10, Y=28, K=6 (same as background)
                $pdf->SetDrawColor(5, 10, 28, 6);
                // Text: Use color from getRotuloTextColor function
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                break;

            case 'CAPSULAS':
                // Green color scheme for capsules
                // Background: C=60, M=0, Y=60, K=20 (dark green)
                $pdf->SetFillColor(60, 0, 60, 20);
                // Border: C=70, M=0, Y=70, K=30 (darker green)
                $pdf->SetDrawColor(70, 0, 70, 30);
                // Text: Use color from getRotuloTextColor function
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                break;

            case 'CAPSULA':
                // Color scheme for capsula products - ESSENCIAL tier
                // Background: C=30, M=0, Y=0, K=100 (dark blue/black)
                $pdf->SetFillColor(30, 0, 0, 100);
                // Border: C=30, M=0, Y=0, K=100 (same as background)
                $pdf->SetDrawColor(30, 0, 0, 100);
                // Text: Use color from getRotuloTextColor function
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                break;

            case 'MY FORMULA':
                // Color scheme for "my formula" products - Avançada tier
                // Background: C=28, M=70, Y=100, K=40 (dark orange/brown)
                $pdf->SetFillColor(28, 70, 100, 40);
                // Border: C=28, M=70, Y=100, K=40 (same as background)
                $pdf->SetDrawColor(28, 70, 100, 40);
                // Text: Use color from getRotuloTextColor function
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                break;

            case 'OTHER':
                // Color scheme for other products - Premium tier
                // Background: C=60, M=10, Y=60, K=35 (dark green with slight magenta)
                $pdf->SetFillColor(60, 10, 60, 35);
                // Border: C=60, M=10, Y=60, K=35 (same as background)
                $pdf->SetDrawColor(60, 10, 60, 35);
                // Text: Use color from getRotuloTextColor function
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                break;

            case 'AVANÇADA':
                // Specific color scheme for AVANÇADA tier
                // Background: C=28, M=70, Y=100, K=40 (dark orange/brown)
                $pdf->SetFillColor(28, 70, 100, 40);
                // Border: C=28, M=70, Y=100, K=40 (same as background)
                $pdf->SetDrawColor(28, 70, 100, 40);
                // Text: Use color from getRotuloTextColor function
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                break;

            case 'ESSENCIAL':
                // Specific color scheme for PREMIUM tier
                // Background: C=60, M=10, Y=60, K=35 (dark green with slight magenta)
                $pdf->SetFillColor(60, 10, 60, 35);
                // Border: C=60, M=10, Y=60, K=35 (same as background)
                $pdf->SetDrawColor(60, 10, 60, 35);
                // Text: Use color from getRotuloTextColor function
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                break;

            case 'PREMIUM':
                // Specific color scheme for ESSENCIAL tier
                // Background: C=30, M=0, Y=0, K=100 (dark blue/black)
                $pdf->SetFillColor(30, 0, 0, 100);
                // Border: C=30, M=0, Y=0, K=100 (same as background)
                $pdf->SetDrawColor(30, 0, 0, 100);
                // Text: Use color from getRotuloTextColor function
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                break;

            default:
                // Default light gray scheme
                // Background: C=0, M=0, Y=0, K=6
                $pdf->SetFillColor(0, 0, 0, 6);
                // Border: C=0, M=0, Y=0, K=20
                $pdf->SetDrawColor(0, 0, 0, 20);
                // Text: Use color from getRotuloTextColor function
                $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                break;
        }
    }

    /**
     * Calculate the required height for a pouch rotulo based on content
     */
    private function calculatePouchHeight($pdf, array $rotuloData, float $width, float $defaultHeight): float
    {
        $contentWidth = $width - 2;

        // Base height components (fixed elements)
        $baseHeight = 3; // Top margin
        $baseHeight += 6; // Nome section
        $baseHeight += 2; // Line after nome
        $baseHeight += 6; // Main product name section
        $baseHeight += 2; // Line after main product name
        $baseHeight += 40; // Bottom section (dosage, RT, company info, dates)

        // Calculate ingredients height
        $ingredients = $this->getIngredientsList($rotuloData);
        $ingredientsText = implode('; ', $ingredients);

        try {
            $pdf->SetFont('opensans', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7);
        }

        $ingredientsHeight = $pdf->getStringHeight($contentWidth, $this->cleanText($ingredientsText));
        $baseHeight += $ingredientsHeight + 10; // Ingredients height plus spacing

        // Add some padding
        $baseHeight += 10;

        // Return the larger of calculated height or default height
        return max($baseHeight, $defaultHeight);
    }

    /**
     * Add a vertical rotulo for pouches (like NOITE design)
     */
    private function addPouchRotulo($pdf, array $rotuloData, float $x, float $y, float $width = 100, float $height = 180)
    {
        // Create variable for repeated width calculation
        $contentWidth = $width - 2;

        // Determine color scheme type
        $colorSchemeType = $rotuloData['color_scheme_type'] ?? $rotuloData['product_type'] ?? 'default';

        // Debug logging
        $this->logger->debug('addPouchRotulo color scheme selection', [
            'color_scheme_type' => $rotuloData['color_scheme_type'] ?? 'NOT_SET',
            'product_type' => $rotuloData['product_type'] ?? 'NOT_SET',
            'layout_type' => $rotuloData['layout_type'] ?? 'NOT_SET',
            'final_colorSchemeType' => $colorSchemeType
        ]);

        $layoutType = $rotuloData['layout_type'] ?? 'pouch';
        $this->setRotuloColorScheme($pdf, $colorSchemeType, $layoutType);

        // Calculate dynamic height based on ingredients
        $dynamicHeight = $this->calculatePouchHeight($pdf, $rotuloData, $width, $height);

        // Use the larger of the calculated height or the provided height
        $finalHeight = max($dynamicHeight, $height);

        // Apply background and border with rounded corners
        $pdf->RoundedRect($x, $y, $width, $finalHeight, 2, '1111', 'F');

        // Set border color and width
        $pdf->SetDrawColor(0, 100, 0, 0); // Bright green CMYK
        $pdf->SetLineWidth(0.200); // 1pt border (0.353mm at 300 DPI)
        $pdf->RoundedRect($x, $y, $width, $finalHeight, 2, '1111');

        $currentY = $y + 3;

        // 1. Nome (top) - using Brandon Black font at 13pt
        $nome = $rotuloData['nome'] ?? $rotuloData['patient_name'] ?? 'NOME';

        // Check if name is too long and abbreviate if necessary
        $nomeUpper = mb_strtoupper($nome, 'UTF-8');
        $abbreviatedNome = $this->abbreviatePatientName($pdf, $nomeUpper, $contentWidth, 'brandontextblack', 13);

        // Set text color to CMYK: C=55, M=61, Y=86, K=60
        $pdf->SetTextColor(55, 61, 86, 60);
        $pdf->SetXY($x + 1, $currentY);
        $pdf->Cell($contentWidth, 4, $this->cleanText($abbreviatedNome), 0, 1, 'L');
        $currentY += 6;

        // Add horizontal line after nome: 196pt wide, 0.25pt thick, same color
        $pdf->SetDrawColor(55, 61, 86, 60);
        $pdf->SetLineWidth(0.15);
        $pdf->Line($x + 1, $currentY, $x + $width - 1, $currentY);
        $currentY += 0;

        // 2. Nome Dra Fran
        $mainProductName = $rotuloData['product_type'] ?? 'NOITE';
        try {
            $pdf->SetFont('brandon_reg', '', 8);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 8);
        }
        $pdf->SetXY($x + 1, $currentY);
        $pdf->Cell($contentWidth, 4, "DRA. FRAN CASTRO", 0, 1, 'L');
        $currentY += 6;

        // 2. Main Product Name (NOITE, DIA, etc.) - large and centered
        $productType = $rotuloData['product_type'] ?? '';
        $colorSchemeType = $rotuloData['color_scheme_type'] ?? 'ESSENCIAL';

        // First part: "MY FORMULA [product_type] |" with brandontextblack font
        try {
            $pdf->SetFont('brandontextblack', '', 13);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 13);
        }
        $pdf->SetFontSpacing(-0.30); // Decrease letter spacing
        $pdf->SetXY($x + 1, $currentY);
        // Only show "MY FORMULA [product_type] |" if product_type is not empty
        if (!empty($productType)) {
            $firstPartText = "MY FORMULA " . $productType . " | ";
            $firstPartWidth = $pdf->GetStringWidth($firstPartText);
            $pdf->Cell($firstPartWidth, 4, $firstPartText, 0, 0, 'L');
        } else {
            $firstPartText = "MY FORMULA | ";
            $firstPartWidth = $pdf->GetStringWidth($firstPartText);
            $pdf->Cell($firstPartWidth, 4, $firstPartText, 0, 0, 'L');
        }

        // Second part: color scheme type with brandon_reg font
        try {
            $pdf->SetFont('brandon_reg', '', 13);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 13);
        }
        $pdf->SetFontSpacing(-0.3); // Decrease letter spacing
        $pdf->Cell(0, 4, $this->cleanText(mb_strtoupper($colorSchemeType, 'UTF-8')), 0, 1, 'L');
        $pdf->SetFontSpacing(0); // Reset letter spacing to normal
        $currentY += 6;

        // Display flavor if available
        $flavor = $rotuloData['flavor'] ?? '';

        if (!empty($flavor)) {
            try {
                $pdf->SetFont('brandontextblack', '', 9);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', '', 9);
            }
            $pdf->SetFontSpacing(-0.2); // Decrease letter spacing
            $pdf->SetXY($x + 1, $currentY);
            $pdf->Cell($contentWidth, 3, "SABOR " . $this->cleanText(mb_strtoupper($flavor, 'UTF-8')), 0, 1, 'L');
            $pdf->SetFontSpacing(0); // Reset letter spacing to normal
            $currentY += 6;
        } else {
            $currentY += 2;
        }

        // 3. Horizontal line separator
        //$pdf->SetDrawColor(55, 61, 86, 60);
        //$pdf->Line($x + 1, $currentY, $x + $width - 1, $currentY);
        //$currentY += 2;

        // 4. Single line ingredients list
        $ingredients = $this->getIngredientsList($rotuloData);
        $ingredientsText = implode('; ', $ingredients);

        try {
            $pdf->SetFont('opensans', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7);
        }

        // Calculate the height needed for the ingredients text
        $ingredientsHeight = $pdf->getStringHeight($contentWidth, $this->cleanText($ingredientsText));

        $pdf->SetXY($x + 1, $currentY);
        $pdf->MultiCell($contentWidth, 2, $this->cleanText($ingredientsText), 0, 'L', 0, 1);
        $currentY += $ingredientsHeight + 10; // Add calculated height plus 1mm spacing

        $bottomYGroup = $y + $finalHeight - 40;
        // Horizontal line separator
        $pdf->SetDrawColor(55, 61, 86, 60);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($x + 1, $bottomYGroup, $x + $width - 1, $bottomYGroup);
        $bottomYGroup += 0.8;

        // 2. ZERO
        $mainProductName = $rotuloData['product_type'] ?? 'NOITE';
        try {
            $pdf->SetFont('brandontextblack', '', 9);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 9);
        }
        $pdf->SetXY($x + 1, $bottomYGroup);
        $pdf->Cell($contentWidth, 4, "ZERO LACTOSE | ZERO CASEÍNA | ZERO GLÚTEN", 0, 1, 'C');
        $bottomYGroup += 5;

        // Horizontal line separator
        $pdf->SetDrawColor(55, 61, 86, 60);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($x + 1, $bottomYGroup, $x + $width - 1, $bottomYGroup);
        $bottomYGroup += 2;

        // 5. Capsule information
        try {
            $pdf->SetFont('opensans', '', 8);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 8);
        }
        $pdf->SetXY($x + 1, $bottomYGroup);

        // Dynamic dosage text based on product type
        $productType = $rotuloData['product_type'] ?? 'NOITE';
        $productName = $rotuloData['product_name'] ?? '';

        // Check if it's a kids product
        $isKidsProduct = $this->isKidsProduct($productName);
        $waterAmount = $isKidsProduct ? '150ml' : '300ml';

        $dosageText = '';
        if (mb_strtoupper($productType, 'UTF-8') === 'DIA') {
            $dosageText = "Consumir 1 dose diluída em {$waterAmount} de água no desjejum. Seis dias da semana.";
        } else {
            $dosageText = "Consumir 1 dose diluído em {$waterAmount} de água. Seis dias da semana.";
        }

        // Calculate max width as 60% of pouch width
        $maxDosageWidth = $width * 0.65;

        $dosageHeight = $pdf->getStringHeight($maxDosageWidth, $this->cleanText($dosageText));
        $pdf->MultiCell($maxDosageWidth, 2, $this->cleanText($dosageText), 0, 1, 'L');

        // Add separator "|" between dosage text and "26 DOSES"
        // First, add the large "|" separator in the middle
        try {
            $pdf->SetFont('opensansl', '', 18);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 12);
        }
        $separatorText = "|";
        $separatorWidth = $pdf->GetStringWidth($separatorText);
        $separatorX = $x - 3 + $maxDosageWidth + 5; // Position after dosage text with 5mm gap
        $pdf->SetXY($separatorX, $bottomYGroup - 2);
        $pdf->Cell($separatorWidth, 3, $separatorText, 0, 1, 'L');

        // Then add "26 DOSES" to the right of the separator
        try {
            $pdf->SetFont('opensans', '', 9);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 9);
        }
        $dosesText = "26 DOSES";
        $dosesWidth = $pdf->GetStringWidth($dosesText);
        $dosesX = $separatorX + $separatorWidth + 1; // Position after the separator with 2mm gap
        $pdf->SetXY($dosesX, $bottomYGroup + 1);
        $pdf->Cell($dosesWidth, 2, $dosesText, 0, 1, 'L');

        $bottomYGroup += $dosageHeight + 1; // Space for ingredients and capsule info

        // 6. Horizontal line separator
        $pdf->Line($x + 1, $bottomYGroup, $x + $width - 1, $bottomYGroup);
        $bottomYGroup += 2;

        // 7. Usage Instructions (centered)
        try {
            $pdf->SetFont('opensans', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7);
        }
        $pdf->SetXY($x + 1, $bottomYGroup);
        $pdf->Cell($contentWidth, 2, "Conservar em refrigerador por até 90 dias.", 0, 1, 'L');
        $bottomYGroup += 3;

        // 8. Patient Name (centered)
        try {
            $pdf->SetFont('opensans', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7);
        }
        $pdf->SetXY($x + 1, $bottomYGroup);
        $pdf->Cell($contentWidth, 2, "Após aberto, consumir em 30 dias.", 0, 1, '"L');
        $bottomYGroup += 7;

        // Add Purissima logo (SVG) - positioned at right bottom
        $logoPath = $this->fontsPath . '/../images/purissima-logo.svg';
        if (file_exists($logoPath)) {
            try {
                // Calculate logo position and size for right bottom corner
                $logoWidth = $contentWidth * 0.5; // 30% of content width
                $logoHeight = 10; // Smaller height for bottom positioning
                $logoX = $x + $width - $logoWidth + 8; // Right side with 1mm margin
                $logoY = $y + $dynamicHeight - $logoHeight - 2; // Bottom with 2mm margin

                // Add SVG logo
                $pdf->ImageSVG($logoPath, $logoX, $logoY, $logoWidth, $logoHeight);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to add SVG logo: ' . $e->getMessage());
                // Fallback to text if SVG fails
                try {
                    $pdf->SetFont('opensansb', '', 4);
                } catch (\Exception $e) {
                    $pdf->SetFont('helvetica', 'B', 4);
                }
                $pdf->SetXY($x + $width - 20, $y + $dynamicHeight - 6);
                $pdf->Cell(18, 2, "PURISSIMA", 0, 1, 'R');
            }
        } else {
            // Fallback to text if no SVG file found
            try {
                $pdf->SetFont('opensansb', '', 4);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', 'B', 4);
            }
            $pdf->SetXY($x + $width - 20, $y + $height - 6);
            $pdf->Cell(18, 2, "PURISSIMA", 0, 1, 'R');
        }

        // Bottom information - positioned at bottom left
        $bottomY = $y + $dynamicHeight - 12; // 12mm from bottom

        // REQ information - extract from rotuloData
        $reqValues = [];
        if (isset($rotuloData['req_values']) && is_array($rotuloData['req_values'])) {
            $reqValues = array_filter($rotuloData['req_values'], function ($req) {
                return !empty(trim((string)$req));
            });
        } elseif (isset($rotuloData['req']) && !empty($rotuloData['req'])) {
            $reqValues = [trim((string)$rotuloData['req'])];
        }

        if (!empty($reqValues)) {
            try {
                $pdf->SetFont('opensansb', '', 7);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', '', 7);
            }
            $pdf->SetXY($x + 1, $bottomY);
            $reqText = 'REQ ' . implode(', ', $reqValues);
            $pdf->Cell($contentWidth, 1.5, $this->cleanText($reqText), 0, 1, 'L');
        }

        // RT information
        try {
            $pdf->SetFont('opensans', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7);
        }
        $pdf->SetXY($x + 1, $bottomY + 3.5);
        $pdf->Cell($contentWidth, 1.5, "RT: Paula Souza de Sales | CRF 51370 MG", 0, 1, 'L');

        // Fab/Val information
        try {
            $pdf->SetFont('opensans', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7);
        }
        $pdf->SetXY($x + 1, $bottomY + 8);

        // Generate dynamic dates
        $fabDate = date('d/m/y', strtotime($rotuloData['created_at']));
        $valDate = date('d/m/y', strtotime($rotuloData['created_at'] . '+90 days'));
        $pdf->Cell($contentWidth, 1.5, "Fab: {$fabDate} | Val: {$valDate}", 0, 1, 'L');

        // Reset text color
        $colorSchemeType = $rotuloData['color_scheme_type'] ?? $rotuloData['product_type'] ?? 'default';
        $layoutType = $rotuloData['layout_type'] ?? 'horizontal';
        $this->setRotuloColorScheme($pdf, $colorSchemeType, $layoutType);
    }

    /**
     * Add a horizontal sticker for capsules and other products
     */
    private function addHorizontalSticker($pdf, array $rotuloData, float $x, float $y, float $width = 100, float $height = 180)
    {
        // Determine color scheme based on product_name using ColorSchemeService
        $productName = substituir($rotuloData['product_name']);
        $colorSchemeName = $this->colorSchemeService->getColorSchemeName($productName);

        // Cool debug logging with color scheme details
        $colorScheme = $this->colorSchemeService->getColorSchemeForProduct($productName);
        $debugInfo = $this->colorSchemeService->debugProductName($productName);

        $this->logger->debug('🎨 addHorizontalSticker color scheme selection', [
            'product_name' => $productName,
            'color_scheme_name' => $colorSchemeName,
            'matched_pattern' => $debugInfo['matched_pattern'],
            'color_scheme_details' => $debugInfo['formatted_colors'],
            'legacy_fields' => [
                'color_scheme_type' => $rotuloData['color_scheme_type'] ?? 'NOT_SET',
                'product_type' => $rotuloData['product_type'] ?? 'NOT_SET'
            ],
            'sticker_position' => [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height
            ],
            'emoji_status' => $debugInfo['matched_pattern'] ? '🎯 MATCHED' : '❌ NO MATCH'
        ]);

        // Apply color scheme directly using ColorSchemeService
        $this->colorSchemeService->applyColorSchemeToPdf($pdf, $productName);

        // Apply background and border with rounded corners
        $pdf->RoundedRect($x, $y, $width, $height, 2, '1111', 'F');

        // Set border color and width
        $pdf->SetDrawColor(0, 100, 0, 0); // Bright green CMYK
        $pdf->SetLineWidth(0.200); // 1pt border (0.353mm at 300 DPI)
        $pdf->RoundedRect($x, $y, $width, $height, 2, '1111');

        $currentY = $y + 2;

        // Calculate layout dimensions for three-column design (equal split)
        $leftColumnWidth = $width / 3 - 10; // 1/3 of total width for ingredients
        $middleColumnWidth = $width / 3 + 20; // 1/3 of total width for main content
        $rightColumnWidth = $width / 3 - 10; // 1/3 of total width for content after DRA. FRAN CASTRO
        $middleColumnX = $x + $leftColumnWidth + 2; // Start position for middle column
        $rightColumnX = $x + $leftColumnWidth + $middleColumnWidth + 4; // Start position for right column




        // Calculate maximum height available for ingredients (leave space for SVG at bottom)
        $maxIngredientsHeight = $height - 5; // Reserve 20 units for SVG and padding

        // Use the new function to display ingredients with tabulated/list fallback
        $ingredientY = $this->displayIngredientsWithFallback(
            $pdf,
            $rotuloData['composition'],
            $x,
            $currentY + 1,
            $leftColumnWidth - 3,
            $maxIngredientsHeight,
            $colorScheme['text'],
            'opensans',
            4
        );


        $isWhiteText = ($colorScheme['text'][0] == 0 && $colorScheme['text'][1] == 0 &&
            $colorScheme['text'][2] == 0 && $colorScheme['text'][3] == 0);
        $svgFileName = $isWhiteText ? 'purissima-social-white.svg' : 'purissima-social-beige.svg';
        $svgPath = $this->fontsPath . '/../images/' . $svgFileName;

        if (file_exists($svgPath)) {
            $svgY = $y + $height - 8; // Position near bottom of rotulo
            $svgX = $x + 3; // Center in left column
            $pdf->ImageSVG($svgPath, $svgX, $svgY, 30, 10);
        }

        // Middle column - Header - Product name (large, centered)
        $productName = "FÓRMULA NUTRACÊUTICA";
        try {
            $pdf->SetFont('brandon_reg', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 7);
        }
        $pdf->SetXY($middleColumnX, $currentY);
        $pdf->Cell($middleColumnWidth - 2, 4, $this->cleanText(mb_strtoupper($productName, 'UTF-8')), 0, 1, 'C');
        $currentY += 3;

        // Product type
        $productType = substituir($rotuloData['product_name']);
        try {
            $pdf->SetFont('brandontextblack', '', 16);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 16);
        }

        // Wrap text to fit within the available width
        $maxWidth = $middleColumnWidth - 4;
        $wrappedLines = $this->wrapTextToLines($this->cleanText(mb_strtoupper($productType, 'UTF-8')), $maxWidth, $pdf);

        // Display each line
        foreach ($wrappedLines as $line) {
            $pdf->SetXY($middleColumnX + 1, $currentY);
            $pdf->Cell($maxWidth, 3, $line, 0, 1, 'C');
            $currentY += 6; // Move down for next line
        } // 3 units spacing from top of product type text
        $currentY += 1.5;


        //Add Patient Name
        $patientName = $rotuloData['patient_name'] ?? 'SEU NOME';
        try {
            $pdf->SetFont('brandontextblack', '', 9);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 9);
        }
        $pdf->SetXY($middleColumnX, $y + $height - 12);
        $pdf->Cell($middleColumnWidth - 2, 3, $this->cleanText(mb_strtoupper($patientName, 'UTF-8')), 0, 1, 'C');
        $currentY += 4.5;

        // Horizontal line separator
        $pdf->SetDrawColor($colorScheme['text'][0], $colorScheme['text'][1], $colorScheme['text'][2], $colorScheme['text'][3]);
        $lineWidth = $width * 0.2; // 30% of width
        $lineStartX = $x + ($width - $lineWidth) / 2; // Center the line
        $pdf->Line($lineStartX, $y + $height - 7.5, $lineStartX + $lineWidth, $y + $height - 7.5);
        $currentY += 0;

        //Add Dr Fran Castro name (middle column)
        try {
            $pdf->SetFont('brandon_reg', '', 6.5);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 6.5);
        }
        $pdf->SetXY($middleColumnX, $y + $height - 7);
        $pdf->Cell($middleColumnWidth - 2, 3, $this->cleanText('POR DRA. FRAN CASTRO'), 0, 1, 'C');
        $currentY += 3.5;

        //Add Dr Fran Castro name (middle column)
        try {
            $pdf->SetFont('opensans', '', 5);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 5);
        }
        $pdf->SetXY($middleColumnX, $y + $height - 4);
        $pdf->Cell($middleColumnWidth - 2, 3, $this->cleanText('CRF 32678362836Q83'), 0, 1, 'C');
        $currentY += 4;

        // Right column - Content after DRA. FRAN CASTRO
        $rightColumnY = $y + 2; // Start at same level as other columns (at the beginning)

        $isWhiteText = ($colorScheme['text'][0] == 0 && $colorScheme['text'][1] == 0 &&
            $colorScheme['text'][2] == 0 && $colorScheme['text'][3] == 0);
        if ($productType === 'DIA') {
            $svgFileName = $isWhiteText ? 'icon-day-white.svg' : 'icon-day.svg';
        } else {
            $svgFileName = $isWhiteText ? 'icon-night-white.svg' : 'icon-night-beige.svg';
        }
        $svgPath = $this->fontsPath . '/../images/' . $svgFileName;
        if (file_exists($svgPath)) {
            $svgY = $rightColumnY; // Position near bottom of rotulo
            $svgX = $rightColumnX - 3; // Center in right column
            $pdf->ImageSVG($svgPath, $svgX, $svgY, 6, 6);
        }

        // Capsule information (60 CAPSULAS | 30 DOSES)
        try {
            $pdf->SetFont('brandontextblack', '', 5.5);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 5.5);
        }
        $pdf->SetXY($rightColumnX + 4, $rightColumnY);
        $pdf->Cell($rightColumnWidth - 2, 3, $this->cleanText('26 DOSES'), 0, 1, 'L');
        $rightColumnY += 3;

        // Dosage information from item (like receituario) - only if not empty

        if ($rotuloData['dosage']) {
            $dosage = $rotuloData['dosage'];
            try {
                $pdf->SetFont('opensans', '', 5);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', '', 5);
            }
            $pdf->SetXY($rightColumnX + 4, $rightColumnY);
            $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText($dosage), 0, 1, 'L');
            $rightColumnY += 2;
        }

        // Usage information (Uso interno.)
        try {
            $pdf->SetFont('opensans', '', 4.8);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 4.8);
        }
        $pdf->SetXY($rightColumnX + 4, $rightColumnY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('Uso interno.'), 0, 1, 'L');
        $rightColumnY += 4;

        // Line divider under "Uso interno."
        $pdf->SetDrawColor($colorScheme['text'][0], $colorScheme['text'][1], $colorScheme['text'][2], $colorScheme['text'][3]);
        $pdf->Line($rightColumnX - 3, $rightColumnY, $rightColumnX + $rightColumnWidth - 10, $rightColumnY);
        $rightColumnY += 2;

        // Doctor name
        try {
            $pdf->SetFont('opensans', '', 4.9);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 4.9);
        }
        $pdf->SetXY($rightColumnX - 4, $rightColumnY);

        // Get dosage text from DoseMapper and transform format
        $productDirt = $rotuloData['product_name'] ?? '';
        $dosageText = $this->doseMapper->getDosageTexCompletet($productDirt);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText($dosageText), 0, 1, 'L');
        $rightColumnY += 5;


        // RT and Company Information - positioned at bottom
        $bottomY = $y + $height - 15; // Position near bottom of rotulo

        // REQ values
        $reqValues = $rotuloData['req_values'] ?? [];
        if (!empty($reqValues)) {
            try {
                $pdf->SetFont('opensans', '', 4.5);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', '', 5);
            }
            $pdf->SetXY($rightColumnX - 4, $bottomY);
            $reqText = 'REQ ' . implode(', ', $reqValues);
            $pdf->Cell($rightColumnWidth - 2, 3, $this->cleanText($reqText), 0, 1, 'L');
            $bottomY += 2.5;
        }

        // RT (Responsável Técnico)
        try {
            $pdf->SetFont('opensans', '', 4);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 4);
        }
        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('RT: Paula Souza de Sales. CRF 51370 MG'), 0, 1, 'L');
        $bottomY += 4;

        // Company Information
        try {
            $pdf->SetFont('opensans', '', 4);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 3);
        }
        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('PURÍSSIMA FARMÁCIA DE MANIPULAÇÃO S.A.'), 0, 1, 'L');
        $bottomY += 2;

        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 4, 2, $this->cleanText('Rua Halfeld, n° 1156, Centro, Juiz de Fora | MG'), 0, 1, 'L');
        $bottomY += 2;

        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('CEP: 36016-000 | CNPJ: 58.771.439/0001-06'), 0, 1, 'L');

        // Manufacturing and Validity dates (vertical text) - positioned at maximum right
        $rightColumnCenterX = $x + $width - 2; // Maximum right position
        $rightColumnCenterY = $y + ($height / 2 - 3);

        // Manufacturing and validity dates
        $fabDate = date('d/m/y', strtotime($rotuloData['created_at']));
        $valDate = date('d/m/y', strtotime($rotuloData['created_at'] . '+90 days'));
        $dateText = "Fab: {$fabDate} | Val: {$valDate}";

        // Set font for vertical date text
        try {
            $pdf->SetFont('opensans', '', 4);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 4);
        }

        // Write date vertically (rotated 90 degrees)
        $pdf->StartTransform();
        $pdf->Rotate(270, $rightColumnCenterX, $rightColumnCenterY);
        $pdf->SetXY($rightColumnCenterX, $rightColumnCenterY);
        $pdf->Cell($height - 20, 2, $this->cleanText($dateText), 0, 1, 'C');
        $pdf->StopTransform();
    }

    /**
     * Add a capsula layout rotulo (horizontal design for capsulas)
     */
    private function addCapsulaRotulo($pdf, array $rotuloData, float $x, float $y, float $width = 100, float $height = 180)
    {
        // Determine color scheme type
        $colorSchemeType = $rotuloData['color_scheme_type'] ?? $rotuloData['product_type'] ?? 'default';

        // Debug logging
        $this->logger->debug('addCapsulaRotulo color scheme selection', [
            'color_scheme_type' => $rotuloData['color_scheme_type'] ?? 'NOT_SET',
            'product_type' => $rotuloData['product_type'] ?? 'NOT_SET',
            'final_colorSchemeType' => $colorSchemeType
        ]);

        $layoutType = $rotuloData['layout_type'] ?? 'horizontal';
        $this->setRotuloColorScheme($pdf, $colorSchemeType, $layoutType);
        $textColor = $this->getRotuloTextColor($colorSchemeType, $layoutType);
        // Apply background and border with rounded corners
        $pdf->RoundedRect($x, $y, $width, $height, 2, '1111', 'F');

        // Set border color and width
        $pdf->SetDrawColor(0, 100, 0, 0); // Bright green CMYK
        $pdf->SetLineWidth(0.200); // 1pt border (0.353mm at 300 DPI)
        $pdf->RoundedRect($x, $y, $width, $height, 2, '1111');

        $currentY = $y + 2;

        // Calculate layout dimensions for three-column design (equal split)
        $leftColumnWidth = $width / 3 - 10; // 1/3 of total width for ingredients
        $middleColumnWidth = $width / 3 + 20; // 1/3 of total width for main content
        $rightColumnWidth = $width / 3 - 10; // 1/3 of total width for content after DRA. FRAN CASTRO
        $middleColumnX = $x + $leftColumnWidth + 2; // Start position for middle column
        $rightColumnX = $x + $leftColumnWidth + $middleColumnWidth + 4; // Start position for right column

        // Left column - Ingredients list
        $ingredientY = $currentY;

        // Calculate maximum height available for ingredients (leave space for SVG at bottom)
        $maxIngredientsHeight = $height - 5; // Reserve 20 units for SVG and padding

        // Use the new function to display ingredients with tabulated/list fallback
        $ingredientY = $this->displayIngredientsWithFallback(
            $pdf,
            $rotuloData['composition'],
            $x,
            $ingredientY + 1,
            $leftColumnWidth - 3,
            $maxIngredientsHeight,
            $textColor,
            'opensans',
            4
        );

        // Add Purissima social SVG at bottom of left column
        // Use white version when text color is white (dark backgrounds)
        $productName = $rotuloData['product_name'] ?? '';
        $isWhiteText = ($textColor[0] == 0 && $textColor[1] == 0 &&
            $textColor[2] == 0 && $textColor[3] == 0);
        $svgFileName = $isWhiteText ? 'purissima-social-white.svg' : 'purissima-social.svg';
        $svgPath = $this->fontsPath . '/../images/' . $svgFileName;

        if (file_exists($svgPath)) {
            $svgY = $y + $height - 8; // Position near bottom of rotulo
            $svgX = $x + 3; // Center in left column
            $pdf->ImageSVG($svgPath, $svgX, $svgY, 30, 10);
        }

        // Middle column - Header - Product name (large, centered)
        $productName = "SUPLEMENTO NUTRICIONAL";
        try {
            $pdf->SetFont('brandon_reg', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 7);
        }
        $pdf->SetXY($middleColumnX, $currentY);
        $pdf->Cell($middleColumnWidth - 2, 4, $this->cleanText(mb_strtoupper($productName, 'UTF-8')), 0, 1, 'C');
        $currentY += 3;

        // Product type
        $productType = substituir($rotuloData['product_type']);
        try {
            $pdf->SetFont('brandontextblack', '', 29);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 29);
        }

        // Wrap text to fit within the available width
        $maxWidth = $middleColumnWidth - 2;
        $wrappedLines = $this->wrapTextToLines($this->cleanText(mb_strtoupper($productType, 'UTF-8')), $maxWidth, $pdf);

        // Display each line
        foreach ($wrappedLines as $line) {
            $pdf->SetXY($middleColumnX, $currentY);
            $pdf->Cell($maxWidth, 3, $line, 0, 1, 'C');
            $currentY += 3; // Move down for next line
        }
        $currentY +=  10; // 3 units spacing from top of product type text

        // Doctor name
        try {
            $pdf->SetFont('opensans', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7);
        }
        $pdf->SetXY($middleColumnX, $currentY);

        // Get dosage text from DoseMapper and transform format
        $productName = $rotuloData['product_name'] ?? '';
        $dosageText = $this->doseMapper->getDosageTexCompletet($productName);
        $pdf->Cell($middleColumnWidth - 2, 2, $this->cleanText($dosageText), 0, 1, 'C');
        $currentY += 5;

        //Add Patient Name
        $patientName = $rotuloData['patient_name'] ?? 'SEU NOME';
        $patientNameUpper = mb_strtoupper($patientName, 'UTF-8');

        // Check if name is too long and abbreviate if necessary
        $maxNameWidth = $middleColumnWidth - 2;
        $abbreviatedName = $this->abbreviatePatientName($pdf, $patientNameUpper, $maxNameWidth, 'brandontextblack', 10);

        try {
            $pdf->SetFont('brandontextblack', '', 10);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 10);
        }
        $pdf->SetXY($middleColumnX, $currentY - 0.2);
        $pdf->Cell($middleColumnWidth - 2, 3, $this->cleanText($abbreviatedName), 0, 1, 'C');
        $currentY += 4.5;

        // Horizontal line separator
        $pdf->SetDrawColor(0, 0, 0, 0);
        $lineWidth = $width * 0.2; // 30% of width
        $lineStartX = $x + ($width - $lineWidth) / 2; // Center the line
        $pdf->Line($lineStartX, $currentY, $lineStartX + $lineWidth, $currentY);
        $currentY += 0.3;

        //Add Dr Fran Castro name (middle column)
        try {
            $pdf->SetFont('brandon_reg', '', 7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7);
        }
        $pdf->SetXY($middleColumnX, $currentY);
        $pdf->Cell($middleColumnWidth - 2, 3, $this->cleanText('POR DRA. FRAN CASTRO'), 0, 1, 'C');
        $currentY += 4;

        // Right column - Content after DRA. FRAN CASTRO
        $rightColumnY = $y + 2; // Start at same level as other columns (at the beginning)

        if ($productType === 'DIA') {
            $svgFileName = $isWhiteText ? 'icon-day-white.svg' : 'icon-day.svg';
        } else {
            $svgFileName = $isWhiteText ? 'icon-night-white.svg' : 'icon-night.svg';
        }
        $svgPath = $this->fontsPath . '/../images/' . $svgFileName;
        if (file_exists($svgPath)) {
            $svgY = $rightColumnY; // Position near bottom of rotulo
            $svgX = $rightColumnX - 3; // Center in right column
            $pdf->ImageSVG($svgPath, $svgX, $svgY, 6, 6);
        }

        // Capsule information (60 CAPSULAS | 30 DOSES)
        try {
            $pdf->SetFont('brandontextblack', '', 5.5);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 5.5);
        }
        $pdf->SetXY($rightColumnX + 4, $rightColumnY);
        $pdf->Cell($rightColumnWidth - 2, 3, $this->cleanText('26 DOSES'), 0, 1, 'L');
        $rightColumnY += 4;

        // Dosage information from item (like receituario) - only if not empty

        if ($rotuloData['dosage']) {
            $dosage = $rotuloData['dosage'];
            try {
                $pdf->SetFont('opensans', '', 5);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', '', 5);
            }
            $pdf->SetXY($rightColumnX + 4, $rightColumnY);
            $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText($dosage), 0, 1, 'L');
            $rightColumnY += 3;
        }

        // Usage information (Uso interno.)
        try {
            $pdf->SetFont('opensans', '', 4.8);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 4.8);
        }
        $pdf->SetXY($rightColumnX + 4, $rightColumnY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('Uso interno.'), 0, 1, 'L');
        $rightColumnY += 4;

        // Line divider under "Uso interno."
        $pdf->SetDrawColor(0, 0, 0, 20);
        $pdf->Line($rightColumnX - 3, $rightColumnY, $rightColumnX + $rightColumnWidth - 10, $rightColumnY);
        $rightColumnY += 2;

        // RT and Company Information - positioned at bottom
        $bottomY = $y + $height - 15; // Position near bottom of rotulo

        // REQ values
        $reqValues = $rotuloData['req_values'] ?? [];
        if (!empty($reqValues)) {
            try {
                $pdf->SetFont('opensans', '', 4.5);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', '', 5);
            }
            $pdf->SetXY($rightColumnX - 4, $bottomY);
            $reqText = 'REQ ' . implode(', ', $reqValues);
            $pdf->Cell($rightColumnWidth - 2, 3, $this->cleanText($reqText), 0, 1, 'L');
            $bottomY += 2.5;
        }

        // RT (Responsável Técnico)
        try {
            $pdf->SetFont('opensans', '', 4);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 4);
        }
        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('RT: Paula Souza de Sales. CRF 51370 MG'), 0, 1, 'L');
        $bottomY += 4;

        // Company Information
        try {
            $pdf->SetFont('opensans', '', 4);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 3);
        }
        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('PURÍSSIMA FARMÁCIA DE MANIPULAÇÃO S.A.'), 0, 1, 'L');
        $bottomY += 2;

        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 4, 2, $this->cleanText('Rua Halfeld, n° 1156, Centro, Juiz de Fora | MG'), 0, 1, 'L');
        $bottomY += 2;

        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('CEP: 36016-000 | CNPJ: 58.771.439/0001-06'), 0, 1, 'L');

        // Manufacturing and Validity dates (vertical text) - positioned at maximum right
        $rightColumnCenterX = $x + $width - 2; // Maximum right position
        $rightColumnCenterY = $y + ($height / 2 - 2);

        // Manufacturing and validity dates
        $fabDate = date('d/m/y', strtotime($rotuloData['created_at']));
        // Debug logging
        $this->logger->debug("ROTULO DATA VALUES: " . json_encode($rotuloData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $valDate = date('d/m/y', strtotime($rotuloData['created_at'] . '+90 days'));
        $dateText = "Fab: {$fabDate} | Val: {$valDate}";

        // Set font for vertical date text
        try {
            $pdf->SetFont('opensans', '', 4);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 4);
        }

        // Write date vertically (rotated 90 degrees)
        $pdf->StartTransform();
        $pdf->Rotate(270, $rightColumnCenterX, $rightColumnCenterY);
        $pdf->SetXY($rightColumnCenterX, $rightColumnCenterY);
        $pdf->Cell($height - 20, 2, $this->cleanText($dateText), 0, 1, 'C');
        $pdf->StopTransform();
    }

    /**
     * Add a single rotulo (chooses between pouch, capsula, or horizontal layout)
     */
    private function addSingleRotulo($pdf, array $rotuloData, float $x, float $y, float $width = 100, float $height = 180)
    {
        $productType = $rotuloData['product_type'] ?? 'default';
        $productName = $rotuloData['product_name'] ?? '';
        $layoutType = $this->getProductLayoutType($productType, $productName);

        // Determine color scheme based on product name and type
        $colorSchemeType = $this->getColorSchemeType($productType, $productName);

        // Debug logging
        $this->logger->debug('addSingleRotulo color scheme determination', [
            'productType' => $productType,
            'productName' => $productName,
            'colorSchemeType' => $colorSchemeType,
            'rotated' => $rotuloData['rotated'] ?? false
        ]);

        // Update the rotulo data with the color scheme type
        $rotuloData['color_scheme_type'] = $colorSchemeType;

        // Add layout type to rotulo data
        $rotuloData['layout_type'] = $layoutType;

        // Handle rotation for single pouches
        if (($rotuloData['rotated'] ?? false) && $layoutType === 'pouch') {
            // For rotated pouches, we need to rotate the PDF content
            // Use original dimensions for the actual pouch content
            $originalWidth = $rotuloData['label_width'];
            $originalHeight = $rotuloData['label_height'];

            // Apply positioning offset for rotated pouches
            $adjustedX = $x + 20;  // +10x offset
            $adjustedY = $y - 20;  // -10y offset

            // Calculate center point for rotation using adjusted position
            $centerX = $adjustedX + $width / 2;
            $centerY = $adjustedY + $height / 2;

            $this->logger->debug('Rendering rotated pouch', [
                'original_position' => ['x' => $x, 'y' => $y],
                'adjusted_position' => ['x' => $adjustedX, 'y' => $adjustedY],
                'position_offset' => ['x' => '+10', 'y' => '-10'],
                'layout_dimensions' => ['width' => $width, 'height' => $height],
                'original_dimensions' => ['width' => $originalWidth, 'height' => $originalHeight],
                'rotation_center' => ['x' => $centerX, 'y' => $centerY]
            ]);

            $pdf->StartTransform();
            $pdf->Rotate(90, $centerX, $centerY); // Rotate around center

            // Render the pouch with its original dimensions at adjusted position
            $this->addPouchRotulo($pdf, $rotuloData, $adjustedX, $adjustedY, $originalWidth, $originalHeight);
            $pdf->StopTransform();
        } else {
            // Normal rendering without rotation
            switch ($layoutType) {
                case 'pouch':
                    $this->addPouchRotulo($pdf, $rotuloData, $x, $y, $width, $height);
                    break;
                case 'capsula':
                    $this->addCapsulaRotulo($pdf, $rotuloData, $x, $y, $width, $height);
                    break;
                case 'horizontal':
                default:
                    $this->addHorizontalSticker($pdf, $rotuloData, $x, $y, $width, $height);
                    break;
            }
        }
    }

    /**
     * Determine the color scheme type for a product based on tier (ESSENCIAL, Avançada, Premium)
     * Returns: 'AVANÇADA', 'PREMIUM', 'ESSENCIAL', 'MY FORMULA', 'OTHER', or 'POUCH'
     */
    private function getColorSchemeType(string $productType, string $productName = ''): string
    {
        $productType = mb_strtoupper($productType, 'UTF-8');
        $productName = mb_strtoupper($productName, 'UTF-8');

        // Check if it's a pouch product (DIA, NOITE, PREMIO)
        $isPouch = ($productType === 'DIA' || $productType === 'NOITE' || $productType === 'PREMIO');

        if ($isPouch) {
            // For pouch products, check for tier in the name
            // Priority: Avançada > Premium > ESSENCIAL > default pouch

            // Check for Avançada tier (highest priority)
            if (strpos($productName, 'AVANÇADA') !== false || strpos($productName, 'AVANCADA') !== false) {
                return 'AVANÇADA';
            }

            // Check for Premium tier
            if (strpos($productName, 'PREMIUM') !== false) {
                return 'PREMIUM';
            }

            // Check for ESSENCIAL tier
            if (strpos($productName, 'ESSENCIAL') !== false) {
                return 'ESSENCIAL';
            }

            // Default pouch color for DIA/NOITE/PREMIO without tier
            return 'POUCH';
        }

        // For non-pouch products, check for tier-based color schemes
        // Priority: Avançada > Premium > ESSENCIAL > default

        // Check for Avançada tier (highest priority)
        if (strpos($productName, 'AVANÇADA') !== false || strpos($productName, 'AVANCADA') !== false) {
            return 'AVANÇADA';
        }

        // Check for Premium tier
        if (strpos($productName, 'PREMIUM') !== false) {
            return 'PREMIUM';
        }

        // Check for ESSENCIAL tier
        if (strpos($productName, 'ESSENCIAL') !== false) {
            return 'ESSENCIAL';
        }

        // Check for MY FORMULA products
        if (strpos($productName, 'MY FORMULA') !== false) {
            return 'MY FORMULA';
        }

        // Default to OTHER for non-pouch products without specific tier
        return 'KIDS';
    }

    /**
     * Determine the layout type for a product
     * Returns: 'pouch', 'capsula', or 'horizontal'
     */
    private function getProductLayoutType(string $productType, string $productName = ''): string
    {
        $productType = mb_strtoupper($productType, 'UTF-8');
        $productName = mb_strtoupper($productName, 'UTF-8');

        // Check if it's a capsula product (but not "capsulas from my formula" which should be horizontal)
        // Check for both accented and non-accented versions
        $capsulaPatterns = ['CAPSULA', 'CÁPSULA', 'CAPSULAS', 'CÁPSULAS'];
        $hasCapsula = false;

        foreach ($capsulaPatterns as $pattern) {
            if (strpos($productName, $pattern) !== false) {
                $hasCapsula = true;
                break;
            }
        }

        if ($hasCapsula && strpos($productName, 'MY FORMULA') === false) {
            return 'capsula';
        }

        // Check if it's a sticker product
        if (strpos($productName, 'STICKER') !== false || strpos($productName, 'ETIQUETA') !== false) {
            return 'pouch';
        }

        // My Kids and My Baby products use pouch layout
        if (strpos($productName, 'MY KIDS') !== false || strpos($productName, 'MY BABY') !== false) {
            return 'pouch';
        }

        // DIA and NOITE products default to pouch layout (unless they contain capsula, which is handled above)
        if ($productType === 'DIA' || $productType === 'NOITE') {
            return 'pouch';
        }

        // PREMIO products use pouch layout
        if ($productType === 'PREMIO') {
            return 'pouch';
        }

        // Default to horizontal layout for other products
        return 'horizontal';
    }

    /**
     * Format current date in Portuguese
     * @param bool $capitalizeMonths Whether to capitalize month names (default: true)
     * @param bool $leadingZeros Whether to include leading zeros for day (default: true)
     * @return string
     */
    private function formatDatePortuguese(string $date, bool $capitalizeMonths = true, bool $leadingZeros = true): string
    {
        if ($capitalizeMonths) {
            $months = [
                1 => 'Janeiro',
                2 => 'Fevereiro',
                3 => 'Março',
                4 => 'Abril',
                5 => 'Maio',
                6 => 'Junho',
                7 => 'Julho',
                8 => 'Agosto',
                9 => 'Setembro',
                10 => 'Outubro',
                11 => 'Novembro',
                12 => 'Dezembro'
            ];
        } else {
            $months = [
                1 => 'janeiro',
                2 => 'fevereiro',
                3 => 'março',
                4 => 'abril',
                5 => 'maio',
                6 => 'junho',
                7 => 'julho',
                8 => 'agosto',
                9 => 'setembro',
                10 => 'outubro',
                11 => 'novembro',
                12 => 'dezembro'
            ];
        }

        $day = $leadingZeros ? date('d', strtotime($date)) : date('j', strtotime($date));
        $month = $months[(int)date('n', strtotime($date))];
        $year = date('Y', strtotime($date));

        return "{$day} de {$month} de {$year}";
    }

    /**
     * Get ingredients list for the rotulo
     */
    /**
     * Display ingredients in tabulated format with fallback to list if content doesn't fit
     * 
     * @param object $pdf TCPDF instance
     * @param array $composition Array of composition objects with 'ingredient' and 'dosage' properties
     * @param float $x X position
     * @param float $y Y position
     * @param float $width Available width
     * @param float $maxHeight Maximum height for the ingredients section
     * @param string $font Font name to use
     * @param float $fontSize Font size
     * @return float The Y position after the ingredients section
     */
    private function displayIngredientsWithFallback($pdf, array $composition, float $x, float $y, float $width, float $maxHeight, $textColor, string $font = 'opensans', float $fontSize = 4): float
    {
        $currentY = $y;
        $lineHeight = 1.5;
        $padding = 2;

        // Set font
        try {
            $pdf->SetFont($font, '', $fontSize);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', $fontSize);
        }

        // Calculate available width for content
        $contentWidth = $width;

        // Force tabulated format for small lists (2-3 ingredients)
        $ingredientCount = count($composition);

        // DEBUG: Log all the parameters and composition data
        $this->logger->debug('displayIngredientsWithFallback called', [
            'ingredient_count' => $ingredientCount,
            'composition' => $composition,
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'maxHeight' => $maxHeight,
            'contentWidth' => $contentWidth,
            'lineHeight' => $lineHeight,
            'font' => $font,
            'fontSize' => $fontSize
        ]);


        // For larger lists, try tabulated format first
        $tabulatedHeight = $this->calculateTabulatedIngredientsHeight($pdf, $composition, $contentWidth, $lineHeight);

        $this->logger->debug('Height calculation for larger list', [
            'ingredient_count' => $ingredientCount,
            'tabulatedHeight' => $tabulatedHeight,
            'maxHeight' => $maxHeight,
            'will_fit' => $tabulatedHeight <= $maxHeight
        ]);

        if ($tabulatedHeight <= $maxHeight) {
            // Use tabulated format
            $this->logger->debug('Using tabulated format for larger list', [
                'reason' => 'height_fits'
            ]);
            $currentY = $this->displayTabulatedIngredients($pdf, $composition, $x + $padding, $currentY, $contentWidth, $lineHeight, $textColor);
        } else {
            // Fallback to list format
            $this->logger->debug('Using list format fallback', [
                'reason' => 'height_too_large',
                'tabulatedHeight' => $tabulatedHeight,
                'maxHeight' => $maxHeight
            ]);
            $currentY = $this->displayListIngredients($pdf, $composition, $x + $padding, $currentY, $contentWidth, $lineHeight);
        }


        $this->logger->debug('displayIngredientsWithFallback completed', [
            'final_y' => $currentY,
            'format_used' => $ingredientCount <= 3 ? 'tabulated_forced' : ($tabulatedHeight <= $maxHeight ? 'tabulated' : 'list')
        ]);

        return $currentY;
    }

    /**
     * Calculate the height needed for tabulated ingredients format
     */
    private function calculateTabulatedIngredientsHeight($pdf, array $composition, float $width, float $lineHeight): float
    {
        $totalHeight = 0;

        foreach ($composition as $index => $ingredientData) {
            $ingredientName = $this->cleanText($ingredientData['ingredient']);
            $dosage = $this->cleanText($ingredientData['dosage']);

            $leftColumnWidth = $width * 0.6;

            // Check if ingredient name fits in left column
            $nameWidth = $pdf->GetStringWidth($ingredientName);
            $availableWidth = $leftColumnWidth - 2;

            if ($nameWidth > $availableWidth) {
                // If ingredient name is too long, calculate actual lines needed
                $lines = $this->breakTextIntoLines($ingredientName, $availableWidth, $pdf);
                $totalHeight += count($lines) * $lineHeight;
            } else {
                $totalHeight += $lineHeight;
            }

            // Add space for dashed line (except for the last item)
            if ($index < count($composition) - 1) {
                $totalHeight += 0.5; // Reduced space for dashed line
            }
        }

        return $totalHeight;
    }

    /**
     * Display ingredients in tabulated format (two-column table with dashed separators)
     */
    private function displayTabulatedIngredients($pdf, array $composition, float $x, float $y, float $width, float $lineHeight, $textColor): float
    {
        $this->logger->debug('displayTabulatedIngredients called', [
            'composition_count' => count($composition),
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'lineHeight' => $lineHeight
        ]);

        $currentY = $y;
        $leftColumnWidth = $width * 0.6; // 60% for ingredient name
        $rightColumnWidth = $width * 0.4; // 40% for dosage
        $rightColumnX = $x + $leftColumnWidth;

        foreach ($composition as $index => $ingredientData) {
            $ingredientName = $this->cleanText($ingredientData['ingredient']);
            $dosage = $this->cleanText($ingredientData['dosage']);

            $this->logger->debug('Displaying tabulated ingredient', [
                'index' => $index,
                'ingredient' => $ingredientName,
                'dosage' => $dosage,
                'y_position' => $currentY,
                'raw_ingredient' => $ingredientData['ingredient'],
                'raw_dosage' => $ingredientData['dosage']
            ]);

            // Check if ingredient name fits in the left column
            $ingredientWidth = $pdf->GetStringWidth($ingredientName);
            $availableWidth = $leftColumnWidth - 2;

            if ($ingredientWidth <= $availableWidth) {
                // Ingredient name fits on one line
                $this->logger->debug('Single-line ingredient', [
                    'ingredient' => $ingredientName,
                    'line_height' => $lineHeight,
                    'y_position' => $currentY
                ]);

                // Calculate dynamic width based on text size
                $dynamicWidth = min($ingredientWidth, $availableWidth - 2);

                $pdf->SetXY($x, $currentY);
                $pdf->Cell($dynamicWidth, $lineHeight, $ingredientName, 0, 0, 'L');

                // Display dosage (right column) with precise positioning
                if ($dosage) {
                    $dosageWidth = $pdf->GetStringWidth($dosage);
                    $dosageStartX = $rightColumnX + ($rightColumnWidth - 2) - $dosageWidth;

                    $pdf->SetXY($dosageStartX, $currentY);
                    $pdf->Cell($dosageWidth, $lineHeight, $dosage, 0, 0, 'L');
                }

                // Calculate line position to connect ingredient to dosage
                $ingredientEndX = $x + $ingredientWidth + 2;
                $dosageStartX = $rightColumnX + ($rightColumnWidth - 2) - $pdf->GetStringWidth($dosage);
                $lineY = $currentY + ($lineHeight / 2);

                $pdf->SetDrawColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                $pdf->SetLineWidth(0.050);
                $pdf->Line($ingredientEndX, $lineY + 0.2, $dosageStartX, $lineY + 0.2);
                $pdf->SetLineWidth(0.2);

                // Move to next line
                $currentY += $lineHeight + 0.5;
            } else {
                // Ingredient name is too wide, break into multiple lines manually
                $this->logger->debug('Multi-line ingredient detected', [
                    'ingredient' => $ingredientName,
                    'available_width' => $availableWidth,
                    'ingredient_width' => $ingredientWidth
                ]);

                // Break text into lines manually
                $lines = $this->breakTextIntoLines($ingredientName, $availableWidth, $pdf);
                $ingredientHeight = count($lines) * $lineHeight;

                $this->logger->debug('Manual line breaking results', [
                    'lines' => $lines,
                    'line_count' => count($lines),
                    'ingredient_height' => $ingredientHeight,
                    'line_height' => $lineHeight,
                    'calculated_height' => count($lines) * $lineHeight
                ]);

                // Display each line separately
                $lineY = $currentY;
                $totalLines = count($lines);

                foreach ($lines as $lineIndex => $lineText) {
                    // Calculate dynamic width based on text size
                    $textWidth = $pdf->GetStringWidth($lineText);
                    $dynamicWidth = min($textWidth, $availableWidth);

                    $pdf->SetXY($x, $lineY);
                    $pdf->Cell($dynamicWidth, $lineHeight, $lineText, 0, 0, 'L');

                    // Position dosage and draw line only on the last line
                    if ($lineIndex === ($totalLines - 1) && $dosage) {
                        // Calculate dosage width for precise positioning
                        $dosageWidth = $pdf->GetStringWidth($dosage);
                        $dosageStartX = $rightColumnX + ($rightColumnWidth - 2) - $dosageWidth;

                        $pdf->SetXY($dosageStartX, $lineY);
                        $pdf->Cell($dosageWidth, $lineHeight, $dosage, 0, 0, 'L');

                        // Draw line from end of last line text to start of dosage
                        $ingredientEndX = $x + $textWidth + 2;
                        $lineYForLine = $lineY + ($lineHeight / 2);

                        $pdf->SetDrawColor($textColor[0], $textColor[1], $textColor[2], $textColor[3]);
                        $pdf->SetLineWidth(0.050);
                        $pdf->Line($ingredientEndX, $lineYForLine + 0.2, $dosageStartX, $lineYForLine + 0.2);
                        $pdf->SetLineWidth(0.2);
                    }

                    $lineY += $lineHeight + 0.3;
                }

                // Move to next ingredient (accounting for multi-line ingredient)
                $currentY += $ingredientHeight + 0.8;
            }
        }

        $this->logger->debug('displayTabulatedIngredients completed', [
            'final_y' => $currentY
        ]);

        // Return the Y position after the last line
        return $currentY;
    }

    /**
     * Draw a dashed horizontal line
     */
    private function drawDashedLine($pdf, float $x1, float $y1, float $x2, float $y2): void
    {
        $dashLength = 2;
        $gapLength = 1;
        $currentX = $x1;

        while ($currentX < $x2) {
            $endX = min($currentX + $dashLength, $x2);
            $pdf->Line($currentX, $y1, $endX, $y1);
            $currentX += $dashLength + $gapLength;
        }
    }

    /**
     * Get the first line of text when wrapped to a specific width
     */
    private function getFirstLineOfText(string $text, float $width, $pdf): string
    {
        $words = explode(' ', $text);
        $firstLine = '';

        foreach ($words as $word) {
            $testLine = $firstLine . ($firstLine ? ' ' : '') . $word;
            $testWidth = $pdf->GetStringWidth($testLine);

            if ($testWidth <= $width) {
                $firstLine = $testLine;
            } else {
                // If this is the first word and it's too long, return it anyway
                if (empty($firstLine)) {
                    $firstLine = $word;
                }
                break;
            }
        }

        return $firstLine;
    }

    /**
     * Break text into multiple lines that fit within the specified width
     */
    private function breakTextIntoLines(string $text, float $width, $pdf): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine . ($currentLine ? ' ' : '') . $word;
            $testWidth = $pdf->GetStringWidth($testLine);

            if ($testWidth <= $width) {
                $currentLine = $testLine;
            } else {
                // If current line is not empty, add it to lines and start new line
                if (!empty($currentLine)) {
                    $lines[] = $currentLine;
                    $currentLine = $word;
                } else {
                    // If even a single word is too long, add it anyway
                    $lines[] = $word;
                    $currentLine = '';
                }
            }
        }

        // Add the last line if it's not empty
        if (!empty($currentLine)) {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Display ingredients in list format (one ingredient per line)
     */
    private function displayListIngredients($pdf, array $composition, float $x, float $y, float $width, float $lineHeight): float
    {
        $this->logger->debug('displayListIngredients called', [
            'composition_count' => count($composition),
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'lineHeight' => $lineHeight
        ]);

        $currentY = $y;

        // Format ingredients as a list with semicolons
        $ingredientStrings = [];
        foreach ($composition as $ingredientData) {
            $ingredientName = $this->cleanText($ingredientData['ingredient']);
            $dosage = $this->cleanText($ingredientData['dosage']);
            $ingredientStrings[] = $ingredientName . ' - ' . $dosage;
        }

        $formattedIngredients = implode('; ', $ingredientStrings) . '.';

        $this->logger->debug('Displaying list ingredients', [
            'formatted_text' => $formattedIngredients,
            'ingredient_strings' => $ingredientStrings
        ]);

        // Use MultiCell to handle text wrapping
        $pdf->SetXY($x, $currentY);
        $pdf->MultiCell($width, $lineHeight, $formattedIngredients, 0, 'L');

        // Calculate the height used by MultiCell
        $lines = $pdf->getNumLines($formattedIngredients, $width);
        $usedHeight = $lines * $lineHeight;

        $this->logger->debug('displayListIngredients completed', [
            'final_y' => $currentY + $usedHeight,
            'lines_used' => $lines,
            'used_height' => $usedHeight
        ]);

        return $currentY + $usedHeight;
    }

    private function getIngredientsList(array $rotuloData): array
    {
        // First, try to use actual composition data from the item
        if (isset($rotuloData['composition']) && !empty($rotuloData['composition'])) {
            $ingredients = [];
            foreach ($rotuloData['composition'] as $ingredient) {
                $ingredients[] = $ingredient['ingredient'] . ' - ' . $ingredient['dosage'];
            }

            $this->logger->debug('Using actual composition data for ingredients', [
                'ingredients_count' => count($ingredients),
                'product_name' => $rotuloData['product_name'] ?? 'Unknown'
            ]);

            return $ingredients;
        }

        // Fallback to default ingredients list based on product type
        $productType = $rotuloData['product_type'] ?? 'NOITE';

        // Return empty array as fallback
        return [];
    }

    /**
     * Create batch labels (rotulos em lote) with optimal space usage
     * Optimized for printing multiple labels with minimal paper waste
     * 
     * @param array $itemsWithOrderData Array of items with their associated order data
     *                                  Each item should have structure: ['item' => $item, 'order_data' => $orderData]
     * @param array $options Configuration options for batch printing
     * @return string Filename of the generated PDF
     */
    public function createBatchLabelsPdf(array $itemsWithOrderData, array $options = []): string
    {
        if (empty($itemsWithOrderData)) {
            throw new \InvalidArgumentException('No items provided for batch label printing');
        }

        // Default options - OPTIMIZED FOR MAXIMUM DENSITY
        $defaultOptions = [
            'page_format' => 'A4', // A4, A3, A2, or custom
            'orientation' => 'P', // P for portrait, L for landscape
            'margin' => 1, // Minimal page margin in mm (reduced from 5)
            'spacing' => 0.5, // Minimal spacing between labels in mm (reduced from 2)
            'max_labels_per_page' => null, // Auto-calculate if null
            'label_rotation' => false, // Whether to rotate labels for better fit
            'group_by_type' => true, // Group similar label types together
            'optimize_layout' => true, // Use smart layout optimization
            'tetris_packing' => true, // Use Tetris-like packing for maximum density
            'preview_mode' => false, // Whether to preview in browser (I) or download (D)
        ];

        $options = array_merge($defaultOptions, $options);

        $filename = 'batch_labels_' . date('Y-m-d_H-i-s') . '.pdf';

        $this->logger->info('Generating Batch Labels PDF', [
            'items_count' => count($itemsWithOrderData),
            'options' => $options,
            'filename' => $filename
        ]);

        // Suppress error output to prevent "headers already sent" error
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);

        try {
            // Initialize PDF with specified format
            $pdf = $this->initializeBatchPdf($options);
            $this->preparePdf($pdf);

            // Use the same page dimensions as individual rotulos for consistency
            $pageWidth = 502;  // Same as individual rotulos
            $pageHeight = 500; // Same as individual rotulos

            // Prepare label data
            $labelData = $this->prepareBatchLabelData($itemsWithOrderData, $options);

            // Calculate optimal layout
            $layout = $this->calculateOptimalLayout($labelData, $pageWidth, $pageHeight, $options);

            // Validate layout
            if (empty($layout['pages']) || $layout['total_pages'] === 0) {
                throw new \Exception('No pages generated in layout calculation');
            }

            $this->logger->debug('Batch labels layout calculated', [
                'total_pages' => $layout['total_pages'],
                'total_labels' => $layout['total_labels'],
                'pages_count' => count($layout['pages'])
            ]);

            // Ensure we have at least one page before generating labels
            if ($layout['total_pages'] > 0) {
                // Add the first page
                $pdf->AddPage();

                $this->logger->debug('First page added to PDF', [
                    'page_count_before' => $pdf->getPage(),
                    'layout_pages' => count($layout['pages'])
                ]);

                // Generate labels using optimized layout
                $this->generateBatchLabels($pdf, $labelData, $layout, $options);
            } else {
                throw new \Exception('No pages to generate - layout calculation failed');
            }

            // Log the download
            $this->logPdfDownload('batch_labels', 'multiple_orders', $filename);

            // Output the PDF to browser (preview or download)
            $outputMode = $options['preview_mode'] ? 'I' : 'D';
            $pdf->Output($filename, $outputMode);

            $this->logger->info('Batch labels PDF generated and sent to browser', [
                'filename' => $filename,
                'total_labels' => count($labelData)
            ]);

            // This line should never be reached as the PDF is output directly
            return $filename;
        } catch (\Exception $e) {
            $this->logger->error('Error generating batch labels PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } finally {
            // Restore error reporting
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }

    /**
     * Initialize PDF for batch printing with specified options
     */
    private function initializeBatchPdf(array $options): Fpdi
    {
        // Use the same custom dimensions as individual rotulos for consistency
        // Individual rotulos use [502, 500] dimensions
        $pdf = new Fpdi('P', 'mm', [502, 500]);
        return $pdf;
    }

    /**
     * Get page dimensions for specified format and orientation
     */
    private function getPageDimensions(string $format, string $orientation): array
    {
        $dimensions = [
            'A4' => [210, 297],
            'A3' => [297, 420],
            'A2' => [420, 594],
            'A1' => [594, 841],
            'A0' => [841, 1189],
            'Letter' => [216, 279],
            'Legal' => [216, 356],
        ];

        $size = $dimensions[$format] ?? $dimensions['A4'];

        if ($orientation === 'L') {
            // Landscape: swap width and height
            return ['width' => $size[1], 'height' => $size[0]];
        }

        return ['width' => $size[0], 'height' => $size[1]];
    }

    /**
     * Prepare label data for batch printing with grouping and optimization
     */
    private function prepareBatchLabelData(array $itemsWithOrderData, array $options): array
    {
        $labelData = [];

        $this->logger->debug('Preparing batch label data', [
            'items_count' => count($itemsWithOrderData),
            'group_by_type' => $options['group_by_type'] ?? false
        ]);

        foreach ($itemsWithOrderData as $itemIndex => $itemWithOrder) {
            $item = $itemWithOrder['item'];
            $orderData = $itemWithOrder['order_data'];

            $rotuloData = $this->prepareRotuloDataFromItem($orderData, $item, $itemIndex);
            $productType = $rotuloData['product_type'] ?? '';
            $productName = $rotuloData['product_name'] ?? '';
            $layoutType = $this->getProductLayoutType($productType, $productName);

            // Determine color scheme
            $colorSchemeType = $this->getColorSchemeType($productType, $productName);
            $rotuloData['color_scheme_type'] = $colorSchemeType;
            $rotuloData['layout_type'] = $layoutType;

            // Get label dimensions
            $dimensions = $this->getLabelDimensions($layoutType);
            $rotuloData['label_width'] = $dimensions['width'];
            $rotuloData['label_height'] = $dimensions['height'];
            $rotuloData['label_type'] = $layoutType;

            $labelData[] = $rotuloData;
        }

        // Group by order first, then by type within each order
        if ($options['group_by_type']) {
            $this->logger->debug('Grouping labels by order and type', [
                'labels_before_grouping' => count($labelData)
            ]);

            $labelData = $this->groupLabelsByOrderAndType($labelData);

            $this->logger->debug('Labels grouped by order and type', [
                'labels_after_grouping' => count($labelData)
            ]);
        }

        return $labelData;
    }

    /**
     * Get standard dimensions for different label types
     */
    private function getLabelDimensions(string $layoutType): array
    {
        $dimensions = [
            'pouch' => ['width' => 76, 'height' => 117],
            'capsula' => ['width' => 141, 'height' => 31],
            'horizontal' => ['width' => 141, 'height' => 31],
            'default' => ['width' => 100, 'height' => 50],
        ];

        return $dimensions[$layoutType] ?? $dimensions['default'];
    }

    /**
     * Group labels by type for better layout optimization
     */
    private function groupLabelsByType(array $labelData): array
    {
        $grouped = [
            'pouch' => [],
            'capsula' => [],
            'horizontal' => [],
            'other' => []
        ];

        foreach ($labelData as $label) {
            $type = $label['label_type'];
            if (isset($grouped[$type])) {
                $grouped[$type][] = $label;
            } else {
                $grouped['other'][] = $label;
            }
        }

        // Flatten back to array maintaining type grouping
        $result = [];
        foreach (['pouch', 'capsula', 'horizontal', 'other'] as $type) {
            $result = array_merge($result, $grouped[$type]);
        }

        return $result;
    }

    /**
     * Group labels by order first, then by type within each order (pouches first, then rest)
     */
    private function groupLabelsByOrderAndType(array $labelData): array
    {
        // First group by order
        $orders = [];
        foreach ($labelData as $label) {
            $orderId = $label['order_id'] ?? 'unknown';
            if (!isset($orders[$orderId])) {
                $orders[$orderId] = [];
            }
            $orders[$orderId][] = $label;
        }

        // Then within each order, group by type (pouches first, then rest)
        $result = [];
        foreach ($orders as $orderId => $orderLabels) {
            $this->logger->debug('Grouping labels for order', [
                'order_id' => $orderId,
                'labels_count' => count($orderLabels)
            ]);

            // Separate pouches from other types
            $pouches = [];
            $other = [];

            foreach ($orderLabels as $label) {
                if ($label['label_type'] === 'pouch') {
                    $pouches[] = $label;
                } else {
                    $other[] = $label;
                }
            }

            // Add pouches first, then other types
            $result = array_merge($result, $pouches, $other);

            $this->logger->debug('Order labels grouped', [
                'order_id' => $orderId,
                'pouches_count' => count($pouches),
                'other_count' => count($other)
            ]);
        }

        return $result;
    }

    /**
     * Calculate optimal layout with SIMPLE COLUMN-BASED approach
     * ORDER 1: All items in first column, continue to second column if needed
     * ORDER 2: Continue in second column, then third column if needed
     * Much more organized and space-saving
     */
    private function calculateOptimalLayout(array $labelData, float $pageWidth, float $pageHeight, array $options): array
    {
        $margin = $options['margin'];
        $spacing = $options['spacing'];

        // Available space for labels
        $availableWidth = $pageWidth - (2 * $margin);
        $availableHeight = $pageHeight - (2 * $margin);

        $layout = [
            'pages' => [],
            'total_pages' => 0,
            'total_labels' => count($labelData),
            'waste_percentage' => 0
        ];

        // Group labels by order to maintain order grouping
        $labelsByOrder = $this->groupLabelsByOrderForLayout($labelData);

        $this->logger->debug('Starting Simple Column-Based Layout', [
            'available_space' => ['width' => $availableWidth, 'height' => $availableHeight],
            'margin' => $margin,
            'spacing' => $spacing,
            'total_labels' => count($labelData),
            'orders_count' => count($labelsByOrder)
        ]);

        $currentPage = $this->createNewPage();
        $columnCount = $this->calculateOptimalColumnCount($availableWidth, $spacing);
        $columnWidth = ($availableWidth - ($columnCount - 1) * $spacing) / $columnCount;

        $this->logger->debug('Column configuration', [
            'column_count' => $columnCount,
            'column_width' => $columnWidth
        ]);

        $currentColumn = 0;
        $columnYPositions = array_fill(0, $columnCount, $margin);

        foreach ($labelsByOrder as $orderId => $orderLabels) {
            $this->logger->debug('Processing order in column layout', [
                'order_id' => $orderId,
                'labels_count' => count($orderLabels),
                'starting_column' => $currentColumn
            ]);

            $labelIndex = 0;
            while ($labelIndex < count($orderLabels)) {
                $currentLabel = $orderLabels[$labelIndex];
                $nextLabel = $labelIndex + 1 < count($orderLabels) ? $orderLabels[$labelIndex + 1] : null;

                // Check if current and next are both pouches
                $isCurrentPouch = $currentLabel['label_type'] === 'pouch';
                $isNextPouch = $nextLabel && $nextLabel['label_type'] === 'pouch';

                if ($isCurrentPouch && $isNextPouch) {
                    // Place both pouches side by side
                    $this->placePouchesSideBySide(
                        $currentLabel,
                        $nextLabel,
                        $currentColumn,
                        $columnYPositions,
                        $columnWidth,
                        $margin,
                        $spacing,
                        $availableHeight,
                        $currentPage,
                        $layout,
                        $columnCount
                    );

                    $labelIndex += 2; // Skip both pouches
                } else {
                    // Place single label normally
                    $this->placeSingleLabel(
                        $currentLabel,
                        $currentColumn,
                        $columnYPositions,
                        $columnWidth,
                        $margin,
                        $spacing,
                        $availableHeight,
                        $currentPage,
                        $layout,
                        $columnCount
                    );

                    $labelIndex++;
                }
            }
        }

        // Add final page if it has content
        if (!empty($currentPage['labels'])) {
            $layout['pages'][] = $currentPage;
            $layout['total_pages']++;
        }

        // Calculate waste percentage
        $layout['waste_percentage'] = $this->calculateWastePercentage($layout, $availableWidth, $availableHeight);

        $this->logger->debug('Simple Column-Based Layout completed', [
            'total_pages' => $layout['total_pages'],
            'total_labels' => $layout['total_labels'],
            'waste_percentage' => $layout['waste_percentage'],
            'columns_used' => $columnCount
        ]);

        return $layout;
    }

    /**
     * Calculate optimal number of columns based on available width and label types
     * Considers side-by-side pouches and rotated single pouches
     */
    private function calculateOptimalColumnCount(float $availableWidth, float $spacing): int
    {
        // Consider different label types for optimal column calculation
        $pouchWidth = 76;   // Pouch width (original)
        $pouchHeight = 117; // Pouch height (original)
        $capsulaWidth = 141; // Capsula width

        // For side-by-side pouches, we need space for 2 pouches + spacing
        $sideBySidePouchWidth = (2 * $pouchWidth) + $spacing;

        // For rotated single pouches, we need space for the rotated width (original height)
        $rotatedPouchWidth = $pouchHeight; // Original height becomes width when rotated

        // Calculate how many columns can fit for different scenarios
        $maxSideBySidePouchColumns = floor(($availableWidth + $spacing) / ($sideBySidePouchWidth + $spacing));
        $maxRotatedPouchColumns = floor(($availableWidth + $spacing) / ($rotatedPouchWidth + $spacing));
        $maxCapsulaColumns = floor(($availableWidth + $spacing) / ($capsulaWidth + $spacing));

        // Use the most restrictive constraint (smallest number of columns)
        $optimalColumns = min(3, max(2, min($maxSideBySidePouchColumns, $maxRotatedPouchColumns)));

        $this->logger->debug('Column count calculation for pouches and capsulas', [
            'available_width' => $availableWidth,
            'side_by_side_pouch_width' => $sideBySidePouchWidth,
            'rotated_pouch_width' => $rotatedPouchWidth,
            'max_side_by_side_pouch_columns' => $maxSideBySidePouchColumns,
            'max_rotated_pouch_columns' => $maxRotatedPouchColumns,
            'max_capsula_columns' => $maxCapsulaColumns,
            'optimal_columns' => $optimalColumns
        ]);

        return $optimalColumns;
    }

    /**
     * Place two pouches side by side in the same column
     */
    private function placePouchesSideBySide(
        array $pouch1,
        array $pouch2,
        int &$currentColumn,
        array &$columnYPositions,
        float $columnWidth,
        float $margin,
        float $spacing,
        float $availableHeight,
        array &$currentPage,
        array &$layout,
        int $columnCount
    ): void {
        $pouchWidth = $pouch1['label_width'];
        $pouchHeight = max($pouch1['label_height'], $pouch2['label_height']);

        // Check if pouches fit in current column
        if ($columnYPositions[$currentColumn] + $pouchHeight > $availableHeight + $margin) {
            // Current column is full, try next column
            $currentColumn++;

            if ($currentColumn >= $columnCount) {
                // All columns full, start new page
                $layout['pages'][] = $currentPage;
                $layout['total_pages']++;

                $currentPage = $this->createNewPage();
                $currentColumn = 0;
                $columnYPositions = array_fill(0, $columnCount, $margin);
            }
        }

        // Calculate positions for side-by-side pouches
        $baseX = $margin + $currentColumn * ($columnWidth + $spacing);
        $y = $columnYPositions[$currentColumn];

        // Place first pouch
        $pouch1['x'] = $baseX;
        $pouch1['y'] = $y;
        $currentPage['labels'][] = $pouch1;

        // Place second pouch side by side
        $pouch2['x'] = $baseX + $pouchWidth + $spacing;
        $pouch2['y'] = $y;
        $currentPage['labels'][] = $pouch2;

        // Update column Y position
        $columnYPositions[$currentColumn] += $pouchHeight + $spacing;

        $this->logger->debug('Pouches placed side by side', [
            'order_id' => $pouch1['order_id'] ?? 'unknown',
            'column' => $currentColumn,
            'positions' => [
                'pouch1' => ['x' => $pouch1['x'], 'y' => $pouch1['y']],
                'pouch2' => ['x' => $pouch2['x'], 'y' => $pouch2['y']]
            ]
        ]);
    }

    /**
     * Place a single label in the current column
     * Rotates single pouches to horizontal orientation for better space usage
     */
    private function placeSingleLabel(
        array $label,
        int &$currentColumn,
        array &$columnYPositions,
        float $columnWidth,
        float $margin,
        float $spacing,
        float $availableHeight,
        array &$currentPage,
        array &$layout,
        int $columnCount
    ): void {
        $labelWidth = $label['label_width'];
        $labelHeight = $label['label_height'];

        // Check if this is a single pouch that should be rotated
        if ($label['label_type'] === 'pouch') {
            // Check if this pouch should be rotated based on layout rules
            $shouldRotate = $this->shouldRotatePouch($label, $currentPage, $layout);

            if ($shouldRotate) {
                // Mark as rotated for rendering, but keep original dimensions for layout
                $label['rotated'] = true;

                // For layout purposes, we need to account for the rotated space
                // The pouch will take up more horizontal space when rotated
                $rotatedLayoutWidth = $labelHeight;  // Original height becomes layout width
                $rotatedLayoutHeight = $labelWidth;  // Original width becomes layout height

                // Update layout dimensions for space calculation
                $labelWidth = $rotatedLayoutWidth;
                $labelHeight = $rotatedLayoutHeight;

                $this->logger->debug('Single pouch marked for rotation', [
                    'order_id' => $label['order_id'] ?? 'unknown',
                    'original_dimensions' => ['width' => $label['label_width'], 'height' => $label['label_height']],
                    'layout_dimensions' => ['width' => $rotatedLayoutWidth, 'height' => $rotatedLayoutHeight]
                ]);
            } else {
                $this->logger->debug('Single pouch NOT rotated due to layout rules', [
                    'order_id' => $label['order_id'] ?? 'unknown',
                    'reason' => 'First on page or only item'
                ]);
            }
        }

        // Check if label fits in current column
        if ($columnYPositions[$currentColumn] + $labelHeight > $availableHeight + $margin) {
            // Current column is full, try next column
            $currentColumn++;

            if ($currentColumn >= $columnCount) {
                // All columns full, start new page
                $layout['pages'][] = $currentPage;
                $layout['total_pages']++;

                $currentPage = $this->createNewPage();
                $currentColumn = 0;
                $columnYPositions = array_fill(0, $columnCount, $margin);
            }
        }

        // Calculate position in current column
        $x = $margin + $currentColumn * ($columnWidth + $spacing);
        $y = $columnYPositions[$currentColumn];

        // Place the label
        $label['x'] = $x;
        $label['y'] = $y;
        $currentPage['labels'][] = $label;

        // Update column Y position
        $columnYPositions[$currentColumn] += $labelHeight + $spacing;

        $this->logger->debug('Single label placed in column', [
            'order_id' => $label['order_id'] ?? 'unknown',
            'label_type' => $label['label_type'],
            'column' => $currentColumn,
            'position' => ['x' => $x, 'y' => $y],
            'rotated' => $label['rotated'] ?? false,
            'dimensions' => ['width' => $labelWidth, 'height' => $labelHeight]
        ]);
    }

    /**
     * Determine if a single pouch should be rotated based on layout rules
     */
    private function shouldRotatePouch(array $label, array $currentPage, array $layout): bool
    {
        // Rule 1: Don't rotate if this would be the first item on a new page
        $isFirstOnPage = empty($currentPage['labels']);

        // Rule 2: Don't rotate if this is the only item in the entire layout
        $totalLabelsInLayout = $layout['total_labels'];
        $isOnlyItem = $totalLabelsInLayout === 1;

        // Rule 3: Don't rotate if this is the only item on the current page
        $isOnlyItemOnPage = count($currentPage['labels']) === 0 && $totalLabelsInLayout === 1;

        // Rule 4: Don't rotate if this is the only item in the current order
        $currentOrderId = $label['order_id'] ?? 'unknown';
        $orderLabelsCount = $this->countLabelsInOrder($currentOrderId, $layout);
        $isOnlyItemInOrder = $orderLabelsCount === 1;

        // Don't rotate if any of these conditions are true
        if ($isFirstOnPage || $isOnlyItem || $isOnlyItemOnPage || $isOnlyItemInOrder) {
            $this->logger->debug('Pouch rotation prevented by layout rules', [
                'order_id' => $label['order_id'] ?? 'unknown',
                'is_first_on_page' => $isFirstOnPage,
                'is_only_item' => $isOnlyItem,
                'is_only_item_on_page' => $isOnlyItemOnPage,
                'is_only_item_in_order' => $isOnlyItemInOrder,
                'current_page_labels' => count($currentPage['labels']),
                'total_labels' => $totalLabelsInLayout,
                'order_labels_count' => $orderLabelsCount
            ]);
            return false;
        }

        $this->logger->debug('Pouch rotation allowed', [
            'order_id' => $label['order_id'] ?? 'unknown',
            'current_page_labels' => count($currentPage['labels']),
            'total_labels' => $totalLabelsInLayout,
            'order_labels_count' => $orderLabelsCount
        ]);

        return true;
    }

    /**
     * Count labels in a specific order across all pages
     */
    private function countLabelsInOrder(string $orderId, array $layout): int
    {
        $count = 0;
        foreach ($layout['pages'] as $page) {
            foreach ($page['labels'] as $label) {
                if (($label['order_id'] ?? 'unknown') === $orderId) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Group labels by order while maintaining order sequence
     */
    private function groupLabelsByOrderForLayout(array $labelData): array
    {
        $orders = [];
        foreach ($labelData as $label) {
            $orderId = $label['order_id'] ?? 'unknown';
            if (!isset($orders[$orderId])) {
                $orders[$orderId] = [];
            }
            $orders[$orderId][] = $label;
        }

        // Sort by order ID to maintain sequence
        ksort($orders);
        return $orders;
    }

    /**
     * Find the best placement for an entire order as a group
     * Tries to pack all order labels together in the most compact way
     */
    private function findBestOrderPlacement(array $orderLabels, array $placedLabels, float $availableWidth, float $availableHeight, float $margin, float $spacing): ?array
    {
        if (empty($orderLabels)) {
            return null;
        }

        // Calculate the bounding box for the entire order
        $orderBoundingBox = $this->calculateOrderBoundingBox($orderLabels, $spacing);

        $this->logger->debug('Calculating order placement', [
            'order_labels_count' => count($orderLabels),
            'bounding_box' => $orderBoundingBox
        ]);

        // Generate candidate positions for the entire order group
        $candidates = [];

        // Try positions starting from top-left, scanning row by row
        for ($y = $margin; $y <= $availableHeight - $orderBoundingBox['height']; $y += 2) { // 2mm precision for groups
            for ($x = $margin; $x <= $availableWidth - $orderBoundingBox['width']; $x += 2) { // 2mm precision for groups
                $candidates[] = ['x' => $x, 'y' => $y];
            }
        }

        // Sort candidates by Y first (top priority), then X (left priority)
        usort($candidates, function ($a, $b) {
            if ($a['y'] == $b['y']) {
                return $a['x'] <=> $b['x'];
            }
            return $a['y'] <=> $b['y'];
        });

        // Test each candidate position for the entire order
        foreach ($candidates as $candidate) {
            $orderPositions = $this->calculateOrderPositions($orderLabels, $candidate, $spacing);

            if ($this->canPlaceOrderGroup($orderLabels, $orderPositions, $placedLabels, $spacing)) {
                return [
                    'positions' => $orderPositions,
                    'area' => $orderBoundingBox,
                    'anchor' => $candidate
                ];
            }
        }

        return null; // No valid position found for the entire order
    }

    /**
     * Calculate the bounding box for an entire order
     */
    private function calculateOrderBoundingBox(array $orderLabels, float $spacing): array
    {
        if (empty($orderLabels)) {
            return ['width' => 0, 'height' => 0];
        }

        // Try different packing strategies for the order
        $strategies = [
            'horizontal' => $this->packOrderHorizontally($orderLabels, $spacing),
            'vertical' => $this->packOrderVertically($orderLabels, $spacing),
            'grid' => $this->packOrderInGrid($orderLabels, $spacing)
        ];

        // Choose the most compact strategy
        $bestStrategy = null;
        $minArea = PHP_FLOAT_MAX;

        foreach ($strategies as $strategyName => $strategy) {
            $area = $strategy['width'] * $strategy['height'];
            if ($area < $minArea) {
                $minArea = $area;
                $bestStrategy = $strategy;
            }
        }

        return $bestStrategy ?? ['width' => 0, 'height' => 0];
    }

    /**
     * Pack order labels horizontally (side by side)
     */
    private function packOrderHorizontally(array $orderLabels, float $spacing): array
    {
        $totalWidth = 0;
        $maxHeight = 0;

        foreach ($orderLabels as $label) {
            $totalWidth += $label['label_width'];
            $maxHeight = max($maxHeight, $label['label_height']);
        }

        // Add spacing between labels
        if (count($orderLabels) > 1) {
            $totalWidth += (count($orderLabels) - 1) * $spacing;
        }

        return ['width' => $totalWidth, 'height' => $maxHeight];
    }

    /**
     * Pack order labels vertically (stacked)
     */
    private function packOrderVertically(array $orderLabels, float $spacing): array
    {
        $maxWidth = 0;
        $totalHeight = 0;

        foreach ($orderLabels as $label) {
            $maxWidth = max($maxWidth, $label['label_width']);
            $totalHeight += $label['label_height'];
        }

        // Add spacing between labels
        if (count($orderLabels) > 1) {
            $totalHeight += (count($orderLabels) - 1) * $spacing;
        }

        return ['width' => $maxWidth, 'height' => $totalHeight];
    }

    /**
     * Pack order labels in a grid (2D arrangement)
     */
    private function packOrderInGrid(array $orderLabels, float $spacing): array
    {
        $labelCount = count($orderLabels);
        if ($labelCount <= 1) {
            return $this->packOrderHorizontally($orderLabels, $spacing);
        }

        // Calculate optimal grid dimensions
        $cols = ceil(sqrt($labelCount));
        $rows = ceil($labelCount / $cols);

        $maxWidth = 0;
        $maxHeight = 0;

        // Calculate column widths and row heights
        $colWidths = array_fill(0, $cols, 0);
        $rowHeights = array_fill(0, $rows, 0);

        foreach ($orderLabels as $index => $label) {
            $col = $index % $cols;
            $row = floor($index / $cols);

            $colWidths[$col] = max($colWidths[$col], $label['label_width']);
            $rowHeights[$row] = max($rowHeights[$row], $label['label_height']);
        }

        $totalWidth = array_sum($colWidths) + ($cols - 1) * $spacing;
        $totalHeight = array_sum($rowHeights) + ($rows - 1) * $spacing;

        return ['width' => $totalWidth, 'height' => $totalHeight];
    }

    /**
     * Calculate positions for all labels in an order group
     */
    private function calculateOrderPositions(array $orderLabels, array $anchor, float $spacing): array
    {
        $positions = [];
        $currentX = $anchor['x'];
        $currentY = $anchor['y'];
        $maxHeightInRow = 0;

        // Simple horizontal packing for now (can be improved with grid packing)
        foreach ($orderLabels as $index => $label) {
            $positions[$index] = [
                'x' => $currentX,
                'y' => $currentY
            ];

            $currentX += $label['label_width'] + $spacing;
            $maxHeightInRow = max($maxHeightInRow, $label['label_height']);

            // If we run out of horizontal space, move to next row
            if ($currentX > $anchor['x'] + 400) { // Assume max width of 400mm
                $currentX = $anchor['x'];
                $currentY += $maxHeightInRow + $spacing;
                $maxHeightInRow = 0;
            }
        }

        return $positions;
    }

    /**
     * Check if an entire order group can be placed without overlapping
     */
    private function canPlaceOrderGroup(array $orderLabels, array $orderPositions, array $placedLabels, float $spacing): bool
    {
        foreach ($orderLabels as $index => $label) {
            $position = $orderPositions[$index];
            if (!$this->canPlaceLabel($label, $position, $placedLabels, $spacing)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Find the best position for a label using Tetris-like packing
     * Tries to place the label as high and as left as possible
     */
    private function findBestPosition(array $label, array $placedLabels, float $availableWidth, float $availableHeight, float $margin, float $spacing): ?array
    {
        $labelWidth = $label['label_width'];
        $labelHeight = $label['label_height'];

        // Generate candidate positions from top-left to bottom-right
        $candidates = [];

        // Try positions starting from top-left, scanning row by row
        for ($y = $margin; $y <= $availableHeight - $labelHeight; $y += 1) { // 1mm precision
            for ($x = $margin; $x <= $availableWidth - $labelWidth; $x += 1) { // 1mm precision
                $candidates[] = ['x' => $x, 'y' => $y];
            }
        }

        // Sort candidates by Y first (top priority), then X (left priority)
        usort($candidates, function ($a, $b) {
            if ($a['y'] == $b['y']) {
                return $a['x'] <=> $b['x'];
            }
            return $a['y'] <=> $b['y'];
        });

        // Test each candidate position
        foreach ($candidates as $candidate) {
            if ($this->canPlaceLabel($label, $candidate, $placedLabels, $spacing)) {
                return $candidate;
            }
        }

        return null; // No valid position found
    }

    /**
     * Check if a label can be placed at a specific position without overlapping
     */
    private function canPlaceLabel(array $label, array $position, array $placedLabels, float $spacing): bool
    {
        $labelLeft = $position['x'];
        $labelRight = $position['x'] + $label['label_width'];
        $labelTop = $position['y'];
        $labelBottom = $position['y'] + $label['label_height'];

        foreach ($placedLabels as $placedLabel) {
            $placedLeft = $placedLabel['x'];
            $placedRight = $placedLabel['x'] + $placedLabel['label_width'];
            $placedTop = $placedLabel['y'];
            $placedBottom = $placedLabel['y'] + $placedLabel['label_height'];

            // Check for overlap with spacing
            if (!($labelRight + $spacing <= $placedLeft ||
                $labelLeft >= $placedRight + $spacing ||
                $labelBottom + $spacing <= $placedTop ||
                $labelTop >= $placedBottom + $spacing)) {
                return false; // Overlap detected
            }
        }

        return true; // No overlap
    }

    /**
     * Finalize current row by calculating dimensions and adding to page
     */
    private function finalizeCurrentRow(array &$currentRow, array &$currentPage, callable $calculateRowHeight, callable $calculateColumnWidth, float $spacing): void
    {
        // Calculate dynamic row height and width
        $currentRow['height'] = $calculateRowHeight($currentRow);
        $currentRow['width'] = 0;
        for ($i = 0; $i < 3; $i++) {
            $currentRow['width'] += $calculateColumnWidth($currentRow['columns'][$i]);
            if ($i < 2) { // Add spacing between columns
                $currentRow['width'] += $spacing;
            }
        }
        $currentPage['rows'][] = $currentRow;
        $currentPage['used_height'] += $currentRow['height'] + $spacing;
    }

    /**
     * Check if we should start a new page
     */
    private function shouldStartNewPage(array $currentPage, float $availableHeight, float $spacing): bool
    {
        $minRowHeight = 50; // Minimum estimated height
        return $currentPage['used_height'] + $minRowHeight + $spacing > $availableHeight;
    }

    /**
     * Create a new page structure for Tetris-like packing
     */
    private function createNewPage(): array
    {
        return [
            'labels' => []
        ];
    }

    /**
     * Create a new row structure
     */
    private function createNewRow(): array
    {
        return [
            'columns' => [
                ['labels' => [], 'width' => 0, 'height' => 0, 'pouches_count' => 0, 'non_pouches_count' => 0], // Column 1
                ['labels' => [], 'width' => 0, 'height' => 0, 'pouches_count' => 0, 'non_pouches_count' => 0], // Column 2
                ['labels' => [], 'width' => 0, 'height' => 0, 'pouches_count' => 0, 'non_pouches_count' => 0]  // Column 3
            ],
            'height' => 0,
            'width' => 0
        ];
    }

    /**
     * Calculate waste percentage for layout optimization
     */
    private function calculateWastePercentage(array $layout, float $availableWidth, float $availableHeight): float
    {
        $totalUsedArea = 0;
        $totalAvailableArea = $availableWidth * $availableHeight * $layout['total_pages'];

        foreach ($layout['pages'] as $page) {
            foreach ($page['rows'] as $row) {
                foreach ($row['columns'] as $column) {
                    $columnArea = $column['width'] * $column['height'];
                    $totalUsedArea += $columnArea;
                }
            }
        }

        if ($totalAvailableArea == 0) return 0;

        return (($totalAvailableArea - $totalUsedArea) / $totalAvailableArea) * 100;
    }

    /**
     * Generate batch labels using calculated layout
     */
    private function generateBatchLabels(Fpdi $pdf, array $labelData, array $layout, array $options): void
    {
        $margin = $options['margin'];
        $spacing = $options['spacing'];

        if (empty($layout['pages'])) {
            throw new \Exception('No pages to generate labels for');
        }

        // Debug: Log batch layout overview
        $this->logger->debug('Starting Simple Column-Based rotulo batch generation', [
            'total_pages' => $layout['total_pages'],
            'total_labels' => $layout['total_labels'],
            'pages_count' => count($layout['pages']),
            'waste_percentage' => $layout['waste_percentage'],
            'options' => [
                'margin' => $margin,
                'spacing' => $spacing
            ]
        ]);

        foreach ($layout['pages'] as $pageIndex => $page) {
            // Skip the first page (index 0) since it's already added
            if ($pageIndex > 0) {
                $pdf->AddPage();
            }

            // Debug: Log page information
            $this->logger->debug('Processing Simple Column-Based rotulo batch page', [
                'page_index' => $pageIndex,
                'labels_count' => count($page['labels']),
                'density' => $this->calculatePageDensity($page)
            ]);

            if (empty($page['labels'])) {
                $this->logger->warning('Empty page in batch layout', ['page_index' => $pageIndex]);
                continue;
            }

            // Sort labels by Y position first, then X position for proper rendering order
            $sortedLabels = $page['labels'];
            usort($sortedLabels, function ($a, $b) {
                if ($a['y'] == $b['y']) {
                    return $a['x'] <=> $b['x'];
                }
                return $a['y'] <=> $b['y'];
            });

            foreach ($sortedLabels as $labelIndex => $label) {
                try {
                    $labelX = $label['x'];
                    $labelY = $label['y'];

                    // Debug: Log label position
                    $this->logger->debug('Adding Simple Column-Based rotulo batch label', [
                        'label_type' => $label['label_type'],
                        'position' => ['x' => $labelX, 'y' => $labelY],
                        'dimensions' => ['width' => $label['label_width'], 'height' => $label['label_height']],
                        'order_id' => $label['order_id'] ?? 'unknown'
                    ]);

                    // Generate the individual rotulo
                    $this->addSingleRotulo(
                        $pdf,
                        $label,
                        $labelX,
                        $labelY,
                        $label['label_width'],
                        $label['label_height']
                    );
                } catch (\Exception $e) {
                    $this->logger->error('Error generating individual rotulo in Simple Column-Based batch', [
                        'error' => $e->getMessage(),
                        'label' => $label,
                        'position' => ['x' => $labelX ?? 0, 'y' => $labelY ?? 0]
                    ]);
                    throw $e;
                }
            }
        }

        // Debug: Log completion
        $this->logger->debug('Simple Column-Based rotulo batch generation completed', [
            'total_pages_processed' => count($layout['pages']),
            'total_labels_processed' => $layout['total_labels'],
            'waste_percentage' => $layout['waste_percentage']
        ]);
    }

    /**
     * Calculate page density for debugging
     */
    private function calculatePageDensity(array $page): float
    {
        if (empty($page['labels'])) {
            return 0.0;
        }

        $totalLabelArea = 0;
        foreach ($page['labels'] as $label) {
            $totalLabelArea += $label['label_width'] * $label['label_height'];
        }

        // Assume page area (this should be passed as parameter in real implementation)
        $pageArea = 502 * 500; // Default page dimensions

        return ($totalLabelArea / $pageArea) * 100;
    }

    /**
     * Convenience method for quick batch label printing with default settings
     * 
     * @param array $itemsWithOrderData Array of items with their associated order data
     * @param string $pageFormat Page format (A4, A3, A2, etc.)
     * @param string $orientation Page orientation (P for portrait, L for landscape)
     * @return string Filename of the generated PDF
     */
    public function printBatchLabels(array $itemsWithOrderData, string $pageFormat = 'A4', string $orientation = 'P'): string
    {
        $options = [
            'page_format' => $pageFormat,
            'orientation' => $orientation,
            'margin' => 5,
            'spacing' => 2,
            'group_by_type' => true,
            'optimize_layout' => true,
        ];

        return $this->createBatchLabelsPdf($itemsWithOrderData, $options);
    }

    /**
     * Preview methods for browser viewing
     */

    /**
     * Preview prescription PDF in browser
     */
    public function previewPrescriptionPdf(array $orderData, array $items): string
    {
        return $this->createPrescriptionPdf($orderData, $items, true);
    }

    /**
     * Preview batch prescription PDF in browser
     */
    public function previewBatchPrescriptionPdf(array $orders): string
    {
        return $this->createBatchPrescriptionPdf($orders, true);
    }

    /**
     * Preview batch labels PDF in browser
     */
    public function previewBatchLabelsPdf(array $itemsWithOrderData, array $options = []): string
    {
        $options['preview_mode'] = true;
        return $this->createBatchLabelsPdf($itemsWithOrderData, $options);
    }

    /**
     * Preview multiple rotulos PDF in browser
     */
    public function previewMultipleRotulosPdf(array $orderData, array $items): string
    {
        return $this->createMultipleRotulosPdf($orderData, $items, true);
    }

    /**
     * Get batch printing statistics and layout preview
     * 
     * @param array $itemsWithOrderData Array of items with their associated order data
     * @param array $options Configuration options for batch printing
     * @return array Statistics about the batch layout
     */
    public function getBatchLayoutPreview(array $itemsWithOrderData, array $options = []): array
    {
        $defaultOptions = [
            'page_format' => 'A4',
            'orientation' => 'P',
            'margin' => 5,
            'spacing' => 2,
            'group_by_type' => true,
            'optimize_layout' => true,
        ];

        $options = array_merge($defaultOptions, $options);

        // Prepare label data
        $labelData = $this->prepareBatchLabelData($itemsWithOrderData, $options);

        // Use the same page dimensions as individual rotulos
        $pageWidth = 502;
        $pageHeight = 500;

        // Calculate layout
        $layout = $this->calculateOptimalLayout($labelData, $pageWidth, $pageHeight, $options);

        // Add additional statistics
        $layout['page_format'] = 'custom';
        $layout['orientation'] = 'P';
        $layout['page_dimensions'] = ['width' => $pageWidth, 'height' => $pageHeight];
        $layout['label_types'] = $this->getLabelTypeStatistics($labelData);

        return $layout;
    }

    /**
     * Get statistics about label types in the batch
     */
    private function getLabelTypeStatistics(array $labelData): array
    {
        $stats = [];

        foreach ($labelData as $label) {
            $type = $label['label_type'];
            if (!isset($stats[$type])) {
                $stats[$type] = [
                    'count' => 0,
                    'total_width' => 0,
                    'total_height' => 0,
                    'dimensions' => [
                        'width' => $label['label_width'],
                        'height' => $label['label_height']
                    ]
                ];
            }

            $stats[$type]['count']++;
            $stats[$type]['total_width'] += $label['label_width'];
            $stats[$type]['total_height'] += $label['label_height'];
        }

        return $stats;
    }

    /**
     * Create multiple rotulos on a single A2 page from order data
     * Creates one rotulo per item in the order
     * 
     * Layout approach: Design everything vertically in portrait A2 (420x594mm),
     * then rotate the entire page container 90° at the end for A2 horizontal printing.
     * This provides intuitive vertical layout control with final horizontal output.
     * 
     * @param array $orderData Order data containing patient info
     * @param array $items Array of items from the order
     * @return string Filename of the generated PDF
     */
    public function createMultipleRotulosPdf(array $orderData, array $items, bool $previewMode = false): string
    {
        if (empty($items)) {
            throw new \Exception('No items provided for rotulo generation');
        }

        $filename = 'rotulos_' . ($orderData['ord_id'] ?? 'multiple') . '_' . date('Y-m-d_H-i-s') . '.pdf';

        $this->logger->info('Generating Multiple Rotulos PDF from Order', [
            'order_id' => $orderData['ord_id'] ?? null,
            'items_count' => count($items),
            'filename' => $filename
        ]);

        // Suppress error output to prevent "headers already sent" error
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);

        try {
            // Start with custom dimensions (1417 x 3228px = 120mm x 273mm at 300 DPI) - work vertically throughout
            $pdf = new Fpdi('P', 'mm', [502, 500]); // Custom page size
            $this->preparePdf($pdf);
            $pdf->AddPage();

            // Work with portrait dimensions throughout the design
            $pageWidth = 502;  // Portrait width (1424pt = 502mm at 300 DPI)
            $pageHeight = 500; // Portrait height (1417pt = 500mm at 300 DPI)

            $margin = 12.95; // Left margin 36.7pt = 12.95mm at 300 DPI
            $spacing = 1.87; // Spacing between items 5.3pt = 1.87mm at 300 DPI

            // Calculate dimensions for each item type
            $pouchWidth = 76;   // Vertical pouches (331.7pt = 117mm at 300 DPI)
            $pouchHeight = 117;   // Vertical pouches (214.3pt = 76mm at 300 DPI)
            $stickerWidth = 141; // Horizontal stickers (88pt = 31mm at 300 DPI)
            $stickerHeight = 31; // Horizontal stickers (400pt = 141mm at 300 DPI)

            // Fixed 2-column pattern (column 1 first, column 2 only if needed)
            $columns = 2;
            $columnWidth = ($pageWidth - 2 * $margin - $spacing) / $columns;

            // Calculate how many items fit per column for each type
            $pouchsPerColumn = floor(($pageHeight - 2 * $margin) / ($pouchHeight + $spacing));
            $stickersPerColumn = floor(($pageHeight - 2 * $margin) / ($stickerHeight + $spacing));

            // Maximum items per page (3 columns)
            $maxPouchsPerPage = $columns * $pouchsPerColumn;
            $maxStickersPerPage = $columns * $stickersPerColumn;

            // Layout pattern: Fill columns first, then move to next column
            // Example with 3 columns x 4 rows:
            // [1] [4] [7]
            // [2] [5] [8] 
            // [3] [6] [9]
            // [ ] [ ] [10]

            $currentPage = 1;

            // Separate items by type - pouches first, then capsulas, then others
            $pouches = [];
            $capsulas = [];
            $otherItems = [];

            foreach ($items as $itemIndex => $item) {
                $rotuloData = $this->prepareRotuloDataFromItem($orderData, $item, $itemIndex);
                $productType = $rotuloData['product_type'] ?? '';
                $productName = $rotuloData['product_name'] ?? '';
                $layoutType = $this->getProductLayoutType($productType, $productName);

                if ($layoutType === 'pouch') {
                    $pouches[] = $rotuloData;
                } elseif ($layoutType === 'capsula') {
                    $capsulas[] = $rotuloData;
                } else {
                    $otherItems[] = $rotuloData;
                }
            }

            // Process all items in order: pouches first (side by side), then capsules, then others
            $currentY = $margin; // Start at top of page
            $currentCol = 0; // Start with column 1
            $columnWidth = ($pageWidth - 2 * $margin - $spacing) / 2 - 40; // 2 columns only

            // Process pouches first with side-by-side layout
            if (!empty($pouches)) {
                $pouchX = $margin;
                $pouchY = $currentY;
                $pouchesPerRow = 2; // Place 2 pouches side by side
                $pouchSpacing = 10; // Spacing between side-by-side pouches

                foreach ($pouches as $index => $pouchData) {
                    $pouchIndex = $index % $pouchesPerRow;

                    // Calculate position for side-by-side placement
                    $x = $pouchX + $pouchIndex * ($pouchWidth + $pouchSpacing);
                    $y = $pouchY;

                    // Check if we need a new row
                    if ($pouchIndex == 0 && $index > 0) {
                        $pouchY += $pouchHeight + $spacing;
                        $y = $pouchY;

                        // Check if we need a new page
                        if ($pouchY + $pouchHeight > $pageHeight - $margin) {
                            $pdf->AddPage();
                            $currentPage++;
                            $pouchY = $margin;
                            $y = $pouchY;
                        }
                    }

                    // Add the pouch rotulo
                    $this->addSingleRotulo($pdf, $pouchData, $x, $y, $pouchWidth, $pouchHeight);
                }

                // Update current Y position after processing pouches
                $currentY = $pouchY + $pouchHeight + $spacing;
            }

            // Process capsules and other items with column layout
            $otherItems = array_merge($capsulas, $otherItems);

            foreach ($otherItems as $itemData) {
                $productType = $itemData['product_type'] ?? '';
                $productName = $itemData['product_name'] ?? '';
                $layoutType = $this->getProductLayoutType($productType, $productName);

                // Determine dimensions based on layout type
                if ($layoutType === 'capsula') {
                    $width = $stickerWidth;
                    $height = $stickerHeight;
                } else {
                    $width = $stickerWidth;
                    $height = $stickerHeight;
                }

                // Check if item fits in current column
                if ($currentY + $height > $pageHeight - $margin) {
                    // Item doesn't fit, move to next column
                    if ($currentCol == 0) {
                        // Move to column 2
                        $currentCol = 1;
                        $currentY = $margin; // Reset Y position
                    } else {
                        // Both columns full, start new page
                        $pdf->AddPage();
                        $currentPage++;
                        $currentCol = 0;
                        $currentY = $margin;
                    }
                }

                // Position calculation
                $x = $margin + $currentCol * ($columnWidth + $spacing);
                $y = $currentY;

                // Items are left-aligned within their column

                // Add the rotulo
                $this->addSingleRotulo($pdf, $itemData, $x, $y, $width, $height);

                // Move to next position in same column
                $currentY += $height + $spacing;
            }

            // Rotate the entire page container 90 degrees at the end for horizontal printing
            $pdf->Rotate(90, 60, 136.5); // Rotate around center point (120/2, 273/2)

            // Log the download
            $this->logPdfDownload('rotulos', $orderData['ord_id'] ?? 'multiple', $filename);

            // Output the PDF to browser (preview or download)
            $outputMode = $previewMode ? 'I' : 'D';
            $pdf->Output($filename, $outputMode);

            $this->logger->info('Multiple Rotulos PDF generated and sent to browser', [
                'filename' => $filename,
                'total_rotulos' => count($items),
                'pages' => $currentPage
            ]);
            return $filename;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create multiple rotulos PDF from Order', [
                'error' => $e->getMessage(),
                'order_id' => $orderData['ord_id'] ?? null,
                'items_count' => count($items)
            ]);
            throw $e;
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }

    /**
     * Prepare rotulo data from order and item information
     * 
     * @param array $orderData Order data
     * @param array $item Item data
     * @param int $itemIndex Item index in the order
     * @return array Rotulo data array
     */
    private function prepareRotuloDataFromItem(array $orderData, array $item, int $itemIndex): array
    {
        $this->logger->debug('Preparing rotulo data for item', [
            'item_index' => $itemIndex,
            'item_name' => $item['itm_name'] ?? $item['name'] ?? 'Unknown',
            'order_id' => $orderData['ord_id'] ?? 'N/A',
            'item_data' => $item
        ]);

        // Extract patient name
        $patientName = $orderData['Nome'] ?? $orderData['usr_name'] ?? 'Paciente';

        // Extract order ID
        $orderId = $orderData['ord_id'] ?? 'N/A';

        // Extract REQ values from item
        $reqValues = [];
        if (isset($item['req']) && !empty($item['req'])) {
            $reqValues = [trim((string)$item['req'])];
            $this->logger->debug('REQ value found for item', [
                'item_index' => $itemIndex,
                'req_value' => $reqValues[0]
            ]);
        } else {
            $this->logger->warning('No REQ value found for item', [
                'item_index' => $itemIndex,
                'item_name' => $item['itm_name'] ?? $item['name'] ?? 'Unknown'
            ]);
        }

        // Determine product type based on item name or composition
        $productType = $this->determineProductType($item);

        // Extract product name
        $productName = $item['itm_name'] ?? $item['name'] ?? 'Produto';

        // Extract flavor from product name (part after " - ")
        $flavor = '';
        if (!empty($productName) && strpos($productName, ' - ') !== false) {
            $parts = explode(' - ', $productName);
            if (count($parts) > 1) {
                $flavor = trim($parts[1]); // Get the part after " - "
            }
        }

        // Extract dosage information
        $dosage = $this->extractDosageFromItem($item);

        // Extract and parse composition data
        $composition = $this->extractCompositionFromItem($item);

        // Determine layout type
        $layoutType = $this->getProductLayoutType($productType, $productName);

        $rotuloData = [
            'patient_name' => $patientName,
            'created_at' => $orderData['created_at'],
            'order_id' => $orderId,
            'req_values' => $reqValues,
            'product_name' => $productName,
            'product_type' => $productType,
            'dosage' => $dosage,
            'composition' => $composition,
            'item_index' => $itemIndex + 1,
            'flavor' => $flavor
        ];

        $this->logger->info('Rotulo data prepared successfully', [
            'item_index' => $itemIndex,
            'product_name' => $productName,
            'product_type' => $productType,
            'layout_type' => $layoutType,
            'patient_name' => $patientName,
            'created_at' => $orderData['created_at'],
            'order_id' => $orderId,
            'has_req' => !empty($reqValues),
            'dosage' => $dosage,
            'composition_count' => count($composition),
            'has_composition' => !empty($composition)
        ]);

        return $rotuloData;
    }

    /**
     * Extract and parse composition data from item
     * 
     * @param array $item Item data
     * @return array Parsed composition data
     */
    private function extractCompositionFromItem(array $item): array
    {
        $composition = [];

        if (isset($item['composition']) && !empty($item['composition'])) {
            try {
                // Parse JSON composition string
                $compositionData = json_decode($item['composition'], true);

                if (is_array($compositionData)) {
                    foreach ($compositionData as $ingredient) {
                        if (isset($ingredient['ingredient']) && isset($ingredient['dosage'])) {
                            $composition[] = [
                                'ingredient' => trim($ingredient['ingredient']),
                                'dosage' => trim($ingredient['dosage'])
                            ];
                        }
                    }

                    $this->logger->debug('Composition parsed successfully', [
                        'composition_count' => count($composition),
                        'composition_data' => $composition
                    ]);
                } else {
                    $this->logger->warning('Invalid composition JSON format', [
                        'composition_raw' => $item['composition']
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to parse composition JSON', [
                    'composition_raw' => $item['composition'],
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            $this->logger->debug('No composition data found for item', [
                'item_name' => $item['itm_name'] ?? $item['name'] ?? 'Unknown'
            ]);
        }

        return $composition;
    }

    /**
     * Determine product type based on item information
     * 
     * @param array $item Item data
     * @return string Product type (DIA, NOITE, CAPSULAS, PREMIO, or default)
     */
    private function determineProductType(array $item): string
    {
        $itemName = strtolower($item['itm_name'] ?? $item['name'] ?? '');
        $composition = strtolower($item['composition'] ?? '');

        // Check for DIA indicators
        if (
            strpos($itemName, 'dia') !== false ||
            strpos($composition, 'dia') !== false ||
            strpos($itemName, 'manhã') !== false ||
            strpos($itemName, 'manha') !== false
        ) {
            return 'DIA';
        }

        // Check for NOITE indicators - only if explicitly contains "noite" or is a specific NOITE product
        if (
            strpos($itemName, 'noite') !== false ||
            strpos($composition, 'noite') !== false
        ) {
            return 'NOITE';
        }

        // Check for CAPSULAS indicators
        if (
            strpos($itemName, 'capsula') !== false ||
            strpos($itemName, 'cápsula') !== false ||
            strpos($itemName, 'capsule') !== false
        ) {
            return 'CAPSULAS';
        }

        // Check for PREMIO/PREMIUM indicators
        if (
            strpos($itemName, 'premio') !== false ||
            strpos($itemName, 'premium') !== false ||
            strpos($itemName, 'premium') !== false
        ) {
            return 'PREMIO';
        }

        return '';
    }

    /**
     * Extract dosage information from item
     * 
     * @param array $item Item data
     * @return string Dosage information
     */
    private function extractDosageFromItem(array $item): string
    {
        // First, try to get dosage from DoseMapper
        $itemName = $item['itm_name'] ?? $item['name'] ?? '';
        if (!empty($itemName)) {
            $doseText = $this->doseMapper->getDosageText($itemName);
            $this->logger->debug('DoseMapper lookup attempt', [
                'item_name' => $itemName,
                'dose_text' => $doseText,
                'is_empty' => empty($doseText)
            ]);
            if (!empty($doseText)) {
                $this->logger->debug('Dosage found via DoseMapper', [
                    'item_name' => $itemName,
                    'dose_text' => $doseText
                ]);
                return $doseText;
            }
        }

        // Check for dosage in various fields
        $dosage = $item['dosage'] ?? $item['dose'] ?? $item['posologia'] ?? '';

        if (!empty($dosage)) {
            $this->logger->debug('Dosage found in item fields', [
                'item_name' => $itemName,
                'dosage' => $dosage
            ]);
            return $dosage;
        }

        // Check for subscription field
        $subscription = $item['subscription'] ?? '';
        if (!empty($subscription)) {
            $this->logger->debug('Dosage found in subscription field', [
                'item_name' => $itemName,
                'subscription' => $subscription
            ]);
            return $subscription;
        }

        $this->logger->debug('No dosage found for item', [
            'item_name' => $itemName
        ]);

        // Default dosage
        return '';
    }

    /**
     * Create sample rotulo data for testing multiple rotulos
     * 
     * @param int $count Number of rotulos to generate
     * @return array Array of rotulo data
     */
    public function createSampleRotulosData(int $count = 6): array
    {
        $rotulosData = [];
        $productTypes = ['DIA', 'NOITE', 'default'];

        for ($i = 1; $i <= $count; $i++) {
            $productType = $productTypes[($i - 1) % count($productTypes)];

            $rotulosData[] = [
                'patient_name' => 'Paciente Teste ' . $i,
                'order_id' => 'ORD' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'req_values' => [], // Empty REQ values for test data - will be populated from actual items
                'product_name' => $productType === 'DIA' ? 'Suplemento DIA' : ($productType === 'NOITE' ? 'Suplemento NOITE' : 'Suplemento Padrão'),
                'product_type' => $productType,
                'dosage' => '2 cápsulas com as refeições'
            ];
        }

        return $rotulosData;
    }

    public function createStickerPdf(array $orderData, array $items): string
    {
        // Suppress error output to prevent "headers already sent" error
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);

        try {
            // Check if all items have req field and add temporary mock if missing
            $allItemsHaveReq = true;
            $reqValues = [];
            foreach ($items as $index => $item) {
                if (!isset($item['req']) || trim((string)$item['req']) === '') {
                    // TEMPORARY MOCK: Add mock req field for testing
                    $items[$index]['req'] = '3213213';
                    $this->logger->info('Added temporary mock req field for testing', [
                        'item_index' => $index,
                        'product_name' => $item['product_name'] ?? 'unknown'
                    ]);
                }
                $reqValues[] = trim((string)$items[$index]['req']);
            }

            // Log that we're using mock data
            $this->logger->info('Using temporary mock req fields for testing', [
                'total_items' => count($items),
                'mock_req_value' => '3213213'
            ]);

            // Use multiple rotulos function to create one rotulo per item
            return $this->createMultipleRotulosPdf($orderData, $items);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
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
     * Process pouch section (MY FORMULA | DIA/NOITE) and capsula subsection
     */
    private function processPouchSection($pdf, string $timeGroup, array $pouchItems, array $capsulaItems, int &$yPosition, $templateId, array $orderData): void
    {
        if (empty($pouchItems) && empty($capsulaItems)) return;

        // Check if we need a new page
        if ($yPosition > $this->pageBreakThreshold) {
            $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
            $yPosition = $this->newPageYPosition;
        }

        // Main section header
        $sectionHeader = ($timeGroup === 'dia') ? 'MY FORMULA | DIA' : 'MY FORMULA | NOITE';

        // Use Brandon Black font for headers
        try {
            $pdf->SetFont('brandontextblack', '', 10.6);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 10.6);
        }

        // Draw background rectangle with transparency
        $this->drawHeaderBackground($pdf, $this->headerLeftPosition + $this->headerBackgroundXOffset, $yPosition + $this->headerBackgroundYOffset, $this->headerBackgroundWidth, $this->headerBackgroundHeight);

        // Draw text on top (without background)
        $pdf->SetXY($this->headerTextLeftPosition, $yPosition + $this->headerBackgroundYOffset + $this->headerTextYOffset);
        $pdf->Cell(170, 8, $this->cleanText($sectionHeader), 0, 1, 'L', false);
        $yPosition += $this->headerSpacing;

        // Dosage information
        try {
            $pdf->SetFont('brandontextblack', '', 8);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 8);
        }
        $pdf->SetXY($this->doseInfoXPosition, $yPosition + $this->doseInfoYOffset);
        $doseTextPouch = '';
        if (!empty($pouchItems)) {
            $first = $pouchItems[0];
            if (isset($first['dose_text']) && $first['dose_text'] !== '') {
                $doseTextPouch = $first['dose_text'];
            }
        }
        if ($doseTextPouch === '') {
            $doseTextPouch = $this->doseMapper->getDosageText($sectionHeader);
        }
        if ($doseTextPouch !== '') {
            $pdf->Cell($this->doseInfoWidth, $this->doseInfoHeight, $this->cleanText($doseTextPouch), 0, 1, 'L');
        }
        $yPosition += 3;

        // Process pouch items
        foreach ($pouchItems as $item) {
            // Check if we need a new page before processing each item
            if ($yPosition > $this->pageBreakThreshold) {
                $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
                $yPosition = $this->newPageYPosition;
                // Don't add header again - this is a continuation
            }
            $this->processItemContent($pdf, $item, $yPosition, $templateId, $orderData);
        }

        // Process capsula items if they exist
        if (!empty($capsulaItems)) {
            // Check if we need a new page before capsula subsection
            if ($yPosition > $this->pageBreakThreshold) {
                $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
                $yPosition = $this->newPageYPosition;
            }

            // Reduce spacing between pouch items and capsula subheader
            $yPosition += 1; // Minimal spacing instead of the default ingredientSpacing (10)

            // Subsection header (no background)
            // Use Brandon Black font for headers
            try {
                $pdf->SetFont('brandontextblack', '', 8.6);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', 'B', 8.6);
            }
            $pdf->SetXY($this->headerTextLeftPosition, $yPosition + $this->headerTextYOffset);
            $pdf->Cell(170, 8, $this->cleanText('+ CÁPSULAS'), 0, 1, 'L');

            // Add line separator under capsula subheader
            $lineY = $yPosition + $this->headerTextYOffset + 6; // Position line below text
            $pdf->SetDrawColor(0, 0, 0); // Black line
            $pdf->Line($this->headerTextLeftPosition + 4, $lineY, $this->headerTextLeftPosition + 4 + 175, $lineY);

            $yPosition += 2; // Reduced spacing between line separator and first ingredient

            // Dosage information for capsula
            try {
                $pdf->SetFont('brandontextblack', '', 8);
            } catch (\Exception $e) {
                $pdf->SetFont('helvetica', 'B', 8);
            }
            $pdf->SetXY($this->doseInfoXPosition, $yPosition + $this->doseInfoYOffset + 2);
            $doseTextCaps = '';
            if (!empty($capsulaItems)) {
                $first = $capsulaItems[0];
                if (isset($first['dose_text']) && $first['dose_text'] !== '') {
                    $doseTextCaps = $first['dose_text'];
                }
            }
            if ($doseTextCaps === '') {
                $doseTextCaps = $this->doseMapper->getDosageText('+ CÁPSULAS');
            }
            if ($doseTextCaps !== '') {
                $pdf->Cell($this->doseInfoWidth, $this->doseInfoHeight, $this->cleanText($doseTextCaps), 0, 1, 'L');
            }
            $yPosition += 4;

            // Process Cápsula items
            foreach ($capsulaItems as $item) {
                $this->processItemContent($pdf, $item, $yPosition, $templateId, $orderData);
            }

            // Add extra spacing after last capsula ingredient before next header
            $yPosition += 4;
        }
    }


    /**
     * Process other items with item name as header
     */
    private function processOtherItem($pdf, array $item, int &$yPosition, $templateId, array $orderData): void
    {
        // Check if we need a new page
        if ($yPosition > $this->pageBreakThreshold) {
            $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
            $yPosition = $this->newPageYPosition;
        }

        // Item name as header
        // Use Brandon Black font for headers
        try {
            $pdf->SetFont('brandontextblack', '', 10.6);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 10.6);
        }
        // Draw background rectangle with transparency
        $this->drawHeaderBackground($pdf, $this->headerLeftPosition + $this->headerBackgroundXOffset, $yPosition + $this->headerBackgroundYOffset, $this->headerBackgroundWidth, $this->headerBackgroundHeight);

        // Draw text on top (without background)
        $pdf->SetXY($this->headerTextLeftPosition, $yPosition + $this->headerBackgroundYOffset + $this->headerTextYOffset);
        $pdf->Cell(170, 8, $this->cleanText($this->cleanItemName($item['itm_name'])), 0, 1, 'L', false);
        $yPosition += $this->headerSpacing;

        // Dosage information
        try {
            $pdf->SetFont('brandontextblack', '', 8);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 8);
        }
        $pdf->SetXY($this->doseInfoXPosition, $yPosition + $this->doseInfoYOffset);
        $doseTextOther = isset($item['dose_text']) ? $item['dose_text'] : $this->doseMapper->getDosageText($item['itm_name']);
        if ($doseTextOther !== '') {
            $pdf->Cell($this->doseInfoWidth, $this->doseInfoHeight, $this->cleanText($doseTextOther), 0, 1, 'L');
        }
        $yPosition += 5;

        // Process item content
        $this->processItemContent($pdf, $item, $yPosition, $templateId, $orderData);

        // Add extra spacing after last other ingredient before next header (same as capsula)
        $yPosition += 4;
    }

    /**
     * Process item content (composition, etc.)
     */
    private function processItemContent($pdf, array $item, int &$yPosition, $templateId = null, $orderData = null): void
    {
        // Parse composition JSON and create ingredient list with dotted lines
        $composition = json_decode($item['composition'], true);
        if ($composition && is_array($composition)) {
            foreach ($composition as $ingredient) {
                // Check if we need a new page before each ingredient
                if ($yPosition > $this->pageBreakThreshold) {
                    if ($templateId !== null && $orderData !== null) {
                        $this->addNewPageWithTemplate($pdf, $templateId, $orderData);
                    } else {
                        $this->addSimpleNewPage($pdf);
                    }
                    $yPosition = $this->newPageYPosition;
                }

                $ingredientName = $this->cleanText($ingredient['ingredient']);
                $dosage = $this->cleanText($ingredient['dosage']);

                // Create dotted line between ingredient name and dosage
                try {
                    $pdf->SetFont('opensans', '', 7.7);
                } catch (\Exception $e) {
                    $pdf->SetFont('helvetica', '', 7.7);
                }
                $pdf->SetXY($this->headerLeftPosition, $yPosition);
                $pdf->Cell(0, 4, $ingredientName, 0, 0, 'L');

                // Calculate positions for dotted line and dosage
                $nameWidth = $pdf->GetStringWidth($ingredientName);
                $dosageWidth = $pdf->GetStringWidth($dosage);
                $lineStart = $this->headerLeftPosition + $nameWidth + 2;
                $dosageStart = max(130, $nameWidth + 10) - $dosageWidth - 2; // Position dosage 2 units from right edge, but use nameWidth + 2 if larger than 120
                $lineEnd = $dosageStart - 2; // End line 2 units before dosage
                $lineY = $yPosition + $this->ingredientLineYOffset;

                // Draw dotted line that fills exactly the space between name and dosage
                $pdf->SetDrawColor(150, 150, 150);
                $dashLength = $this->dottedLineDashLength;
                $gapLength = $this->dottedLineGapLength;
                $currentX = $lineStart;

                while ($currentX < $lineEnd) {
                    $pdf->Line($currentX, $lineY, $currentX + $dashLength, $lineY);
                    $currentX += $dashLength + $gapLength;
                }

                // Add dosage at the end
                $pdf->SetXY($dosageStart, $yPosition);
                $pdf->Cell(0, 4, $dosage, 0, 1, 'L');

                $yPosition += $this->headerSpacing;
            }
        }

        $yPosition += $this->ingredientSpacing;
    }


    private function cleanText(string $text): string
    {
        // First, replace problematic characters that don't convert well
        $text = str_replace([
            '•',
            '–',
            '—',
            '…',
            '"',
            '"',
            "'",
            "'",
            '€',
            '°',
            'º',
            'ª',
            'â€¢',
            'â€"',
            'â€"',
            'â€¦',
            'â‚¬',
            'Â°',
            'Âº',
            'Âª'
        ], [
            '-',
            '-',
            '-',
            '...',
            '"',
            '"',
            "'",
            "'",
            'EUR',
            'o',
            'o',
            'a',
            '-',
            '-',
            '-',
            '...',
            'EUR',
            'o',
            'o',
            'a'
        ], $text);

        return $text;
    }

    /**
     * Wrap text to fit within specified width
     */
    private function wrapTextToLines(string $text, float $maxWidth, $pdf): array
    {
        $lines = [];
        $words = explode(' ', $text);
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine . ($currentLine ? ' ' : '') . $word;
            $testWidth = $pdf->GetStringWidth($testLine);

            if ($testWidth <= $maxWidth) {
                $currentLine = $testLine;
            } else {
                if ($currentLine) {
                    $lines[] = $currentLine;
                    $currentLine = $word;
                } else {
                    // Single word is too long, add it anyway
                    $lines[] = $word;
                }
            }
        }

        if ($currentLine) {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Format phone number to Brazilian format (XX) X XXXX-XXXX
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If phone has 11 digits (with area code and 9th digit)
        if (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 1) . ' ' . substr($phone, 3, 4) . '-' . substr($phone, 7, 4);
        }

        // If phone has 10 digits (with area code but no 9th digit)
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
        }

        // If phone has 9 digits (without area code)
        if (strlen($phone) === 9) {
            return substr($phone, 0, 1) . ' ' . substr($phone, 1, 4) . '-' . substr($phone, 5, 4);
        }

        // If phone has 8 digits (without area code and 9th digit)
        if (strlen($phone) === 8) {
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 4);
        }

        // Return original if doesn't match expected patterns
        return $phone;
    }

    /**
     * Capitalize patient name properly
     */
    private function capitalizePatientName(string $name): string
    {
        // Convert to lowercase first, then capitalize each word
        $name = strtolower($name);

        // Split by spaces and capitalize each word
        $words = explode(' ', $name);
        $capitalizedWords = array_map('ucfirst', $words);

        // Join back with spaces
        return implode(' ', $capitalizedWords);
    }

    /**
     * Clean item name by removing severity suffixes and parenthetical content for "other" items
     */
    private function cleanItemName(string $itemName): string
    {
        // Remove severity suffixes (both with regular dash and en dash)
        $severityPatterns = [
            ' - Leve',
            ' - Moderado',
            ' - Moderada',
            ' - Severo',
            ' - Severa',
            ' - Grave',
            ' – Leve',
            ' – Moderado',
            ' – Moderada',
            ' – Severo',
            ' – Severa',
            ' – Grave'
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

        // Set alpha transparency to 7% (0.07)
        $pdf->SetAlpha(0.2);

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
        // Patient name with 11pt font
        try {
            $pdf->SetFont('opensansb', '', 11);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', 'B', 11);
        }
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY);
        $patientName = $orderData['Nome'] ?? ($orderData['usr_name'] ?? '');
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText($this->capitalizePatientName($patientName)), 0, 1, 'R');

        // Other patient info with 7.7pt font
        try {
            $pdf->SetFont('opensans', '', 7.7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7.7);
        }
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing);
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Documento: ' . $orderData['usr_cpf']), 0, 1, 'R');
        $genero = $orderData['Genero'] ?? null;
        $sexoLabel = '[Não informado]';
        if ($genero === 1 || $genero === '1') {
            $sexoLabel = 'Masculino';
        } elseif ($genero === 2 || $genero === '2') {
            $sexoLabel = 'Feminino';
        }

        // Calculate position offset based on whether sexo line is shown
        $positionOffset = ($sexoLabel !== '[Não informado]') ? 1 : 0;

        // Only show sexo line if it's not "[Não informado]"
        if ($sexoLabel !== '[Não informado]') {
            $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + $this->patientInfoSpacing);
            $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Sexo: ' . $sexoLabel), 0, 1, 'R');
        }
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + ($this->patientInfoSpacing * (1 + $positionOffset)));
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Telefone: ' . $this->formatPhoneNumber($orderData['usr_phone'])), 0, 1, 'R');
        // Set font to helvetica bold for Prescrição (more reliable than opensansb)
        $pdf->SetFont('helvetica', 'B', 7.7);
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + ($this->patientInfoSpacing * (2 + $positionOffset)));
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Prescrição: ' . $orderData['ord_id']), 0, 1, 'R');

        // Date at bottom right
        try {
            $pdf->SetFont('opensans', '', 7.7);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 7.7);
        }
        $pdf->SetXY($this->dateXPosition, $this->dateYPosition);
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->formatDatePortuguese($orderData['created_at']), 0, 1, 'C');
    }

    /**
     * Add a simple new page without template (for ingredient page breaks)
     */
    private function addSimpleNewPage($pdf): void
    {
        $pdf->AddPage();

        // Set standard text color
        $this->setStandardTextColor($pdf);

        // Set default font
        try {
            $pdf->SetFont('opensans', '', 12);
        } catch (\Exception $e) {
            $pdf->SetFont('helvetica', '', 12);
        }
    }

    /**
     * Cool debug method to test color schemes for product names
     * 
     * @param array $productNames Array of product names to test
     * @return array Debug information for each product
     */
    public function debugColorSchemes(array $productNames): array
    {
        $results = [];

        foreach ($productNames as $productName) {
            $results[$productName] = $this->colorSchemeService->debugProductName($productName);
        }

        return $results;
    }

    /**
     * Get complete debug information about the ColorSchemeService
     * 
     * @return string Formatted debug information
     */
    public function getColorSchemeDebugInfo(): string
    {
        return $this->colorSchemeService->getDebugInfo();
    }

    /**
     * Check if a product is for kids based on product name
     * 
     * @param string $productName The product name to check
     * @return bool True if it's a kids product, false otherwise
     */
    private function isKidsProduct(string $productName): bool
    {
        $kidsKeywords = [
            'kid',
            'child',
            'infantil',
            'menor',
            'jovem',
            'baby',
            'criança',
            'kids',
            'children',
            'infant',
            'young',
            'pequeno',
            'pequena'
        ];

        $productNameLower = mb_strtolower($productName, 'UTF-8');

        foreach ($kidsKeywords as $keyword) {
            if (strpos($productNameLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
