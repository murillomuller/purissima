<?php

namespace App\Services;

require_once __DIR__ . '/../../item-name-mappings.php';

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

    public function createPrescriptionPdf(array $orderData, array $items): string
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
			
			// Output the PDF directly to browser
			$pdf->Output($filename, 'D');
			
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
	public function createBatchPrescriptionPdf(array $orders): string
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
			
			// Output the PDF directly to browser
			$pdf->Output($filename, 'D');
			
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
		$pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->formatDatePortuguese(), 0, 1, 'C');
		
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
            'dia_pouch_items' => array_map(function($item) { return $item['itm_name']; }, $grouped['dia']['pouch']),
            'dia_capsula_items' => array_map(function($item) { return $item['itm_name']; }, $grouped['dia']['capsula']),
            'noite_pouch_items' => array_map(function($item) { return $item['itm_name']; }, $grouped['noite']['pouch']),
            'noite_capsula_items' => array_map(function($item) { return $item['itm_name']; }, $grouped['noite']['capsula']),
            'other_items' => array_map(function($item) { return $item['itm_name']; }, $grouped['other'])
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
        
        // If layout type is pouch, set pouch color scheme and return
        if ($layoutType === 'pouch') {
            // Standard pouch color scheme for all pouch products
            // Background: C=5, M=10, Y=28, K=6
            $pdf->SetFillColor(5, 10, 28, 6);
            // Border: C=5, M=10, Y=28, K=6 (same as background)
            $pdf->SetDrawColor(5, 10, 28, 6);
            // Text: Dark for contrast on light background
            $pdf->SetTextColor(0, 0, 0, 100);
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
                // Text: Dark for contrast on light background
                $pdf->SetTextColor(0, 0, 0, 100);
                break;
                
            case 'CAPSULAS':
                // Green color scheme for capsules
                // Background: C=60, M=0, Y=60, K=20 (dark green)
                $pdf->SetFillColor(60, 0, 60, 20);
                // Border: C=70, M=0, Y=70, K=30 (darker green)
                $pdf->SetDrawColor(70, 0, 70, 30);
                // Text: White for contrast
                $pdf->SetTextColor(0, 0, 0, 0);
                break;
                
            case 'CAPSULA':
                // Color scheme for capsula products - ESSENCIAL tier
                // Background: C=30, M=0, Y=0, K=100 (dark blue/black)
                $pdf->SetFillColor(30, 0, 0, 100);
                // Border: C=30, M=0, Y=0, K=100 (same as background)
                $pdf->SetDrawColor(30, 0, 0, 100);
                // Text: White for contrast
                $pdf->SetTextColor(0, 0, 0, 0);
                break;
                
            case 'MY FORMULA':
                // Color scheme for "my formula" products - Avançada tier
                // Background: C=28, M=70, Y=100, K=40 (dark orange/brown)
                $pdf->SetFillColor(28, 70, 100, 40);
                // Border: C=28, M=70, Y=100, K=40 (same as background)
                $pdf->SetDrawColor(28, 70, 100, 40);
                // Text: White for contrast
                $pdf->SetTextColor(0, 0, 0, 0);
                break;
                
            case 'OTHER':
                // Color scheme for other products - Premium tier
                // Background: C=60, M=10, Y=60, K=35 (dark green with slight magenta)
                $pdf->SetFillColor(60, 10, 60, 35);
                // Border: C=60, M=10, Y=60, K=35 (same as background)
                $pdf->SetDrawColor(60, 10, 60, 35);
                // Text: White for contrast
                $pdf->SetTextColor(0, 0, 0, 0);
                break;
                
            case 'AVANÇADA':
                // Specific color scheme for AVANÇADA tier
                // Background: C=28, M=70, Y=100, K=40 (dark orange/brown)
                $pdf->SetFillColor(28, 70, 100, 40);
                // Border: C=28, M=70, Y=100, K=40 (same as background)
                $pdf->SetDrawColor(28, 70, 100, 40);
                // Text: White for contrast
                $pdf->SetTextColor(0, 0, 0, 0);
                break;
                
            case 'PREMIUM':
                // Specific color scheme for PREMIUM tier
                // Background: C=60, M=10, Y=60, K=35 (dark green with slight magenta)
                $pdf->SetFillColor(60, 10, 60, 35);
                // Border: C=60, M=10, Y=60, K=35 (same as background)
                $pdf->SetDrawColor(60, 10, 60, 35);
                // Text: White for contrast
                $pdf->SetTextColor(0, 0, 0, 0);
                break;
                
            case 'ESSENCIAL':
                // Specific color scheme for ESSENCIAL tier
                // Background: C=30, M=0, Y=0, K=100 (dark blue/black)
                $pdf->SetFillColor(30, 0, 0, 100);
                // Border: C=30, M=0, Y=0, K=100 (same as background)
                $pdf->SetDrawColor(30, 0, 0, 100);
                // Text: White for contrast
                $pdf->SetTextColor(0, 0, 0, 0);
                break;
                
                
            default:
                // Default light gray scheme
                // Background: C=0, M=0, Y=0, K=6
                $pdf->SetFillColor(0, 0, 0, 6);
                // Border: C=0, M=0, Y=0, K=20
                $pdf->SetDrawColor(0, 0, 0, 20);
                // Text: Black
                $pdf->SetTextColor(0, 0, 0, 100);
                break;
            }
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
        
        // Apply background and border with rounded corners
        $pdf->RoundedRect($x, $y, $width, $height, 2, '1111', 'F');
        
        // Set border color and width
        $pdf->SetDrawColor(0, 100, 0, 0); // Bright green CMYK
        $pdf->SetLineWidth(0.200); // 1pt border (0.353mm at 300 DPI)
        $pdf->RoundedRect($x, $y, $width, $height, 2, '1111');
        
        $currentY = $y + 3;
        
        // 1. Nome (top) - using Brandon Black font at 13pt
        $nome = $rotuloData['nome'] ?? $rotuloData['patient_name'] ?? 'NOME';
        
        // Check if name is too long and abbreviate if necessary
        try { $pdf->SetFont('brandontextblack', '', 13); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 13); }
        $nomeUpper = mb_strtoupper($nome, 'UTF-8');
        $nameWidth = $pdf->GetStringWidth($nomeUpper);
        
        if ($nameWidth > $contentWidth) {
            // Abbreviate name: keep first name and last name
            $nameParts = explode(' ', trim($nome));
            if (count($nameParts) >= 2) {
                $firstName = $nameParts[0];
                $lastName = end($nameParts);
                $nome = $firstName . ' ' . $lastName;
            }
        }
        
        // Set text color to CMYK: C=55, M=61, Y=86, K=60
        $pdf->SetTextColor(55, 61, 86, 60);
        $pdf->SetXY($x + 1, $currentY);
        $pdf->Cell($contentWidth, 4, $this->cleanText(mb_strtoupper($nome, 'UTF-8')), 0, 1, 'L');
        $currentY += 6;
        
        // Add horizontal line after nome: 196pt wide, 0.25pt thick, same color
        $pdf->SetDrawColor(55, 61, 86, 60);
        $pdf->SetLineWidth(0.15);
        $pdf->Line($x + 1, $currentY, $x + $width - 1, $currentY);
        $currentY += 0;
        
        // 2. Nome Dra Fran
        $mainProductName = $rotuloData['product_type'] ?? 'NOITE';
        try { $pdf->SetFont('brandon_reg', '', 8); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 8); }
        $pdf->SetXY($x + 1, $currentY);
        $pdf->Cell($contentWidth, 4, "DRA. FRAN CASTRO", 0, 1, 'L');
        $currentY += 6;

        // 2. Main Product Name (NOITE, DIA, etc.) - large and centered
        $productType = $rotuloData['product_type'] ?? 'NOITE';
        $colorSchemeType = $rotuloData['color_scheme_type'] ?? 'ESSENCIAL';
        
        // First part: "MY FORMULA [product_type] |" with brandontextblack font
        try { $pdf->SetFont('brandontextblack', '', 13); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 13); }
        $pdf->SetFontSpacing(-0.30); // Decrease letter spacing
        $pdf->SetXY($x + 1, $currentY);
        $firstPartText = "MY FORMULA ".$productType." | ";
        $firstPartWidth = $pdf->GetStringWidth($firstPartText);
        $pdf->Cell($firstPartWidth, 4, $firstPartText, 0, 0, 'L');
        
        // Second part: color scheme type with brandon_reg font
        try { $pdf->SetFont('brandon_reg', '', 13); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 13); }
        $pdf->SetFontSpacing(-0.3); // Decrease letter spacing
        $pdf->Cell(0, 4, $this->cleanText(mb_strtoupper($colorSchemeType, 'UTF-8')), 0, 1, 'L');
        $pdf->SetFontSpacing(0); // Reset letter spacing to normal
        $currentY += 6;
        
        // Display flavor if available
        $flavor = $rotuloData['flavor'] ?? '';
        
        if (!empty($flavor)) {
            try { $pdf->SetFont('brandontextblack', '', 9); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 9); }
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
        
        try { $pdf->SetFont('opensans', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
        
        // Calculate the height needed for the ingredients text
        $ingredientsHeight = $pdf->getStringHeight($contentWidth, $this->cleanText($ingredientsText));
        
        $pdf->SetXY($x + 1, $currentY);
        $pdf->MultiCell($contentWidth, 2, $this->cleanText($ingredientsText), 0, 'L', 0, 1);
        $currentY += $ingredientsHeight + 10; // Add calculated height plus 1mm spacing
        
         // Horizontal line separator
        $pdf->SetDrawColor(55, 61, 86, 60);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($x + 1, $currentY, $x + $width - 1, $currentY);
        $currentY += 0.8;

        // 2. ZERO
        $mainProductName = $rotuloData['product_type'] ?? 'NOITE';
        try { $pdf->SetFont('brandontextblack', '', 9); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 9); }
        $pdf->SetXY($x + 1, $currentY);
        $pdf->Cell($contentWidth, 4, "ZERO LACTOSE | ZERO CASEÍNA | ZERO GLÚTEN", 0, 1, 'C');
        $currentY += 5;

        // Horizontal line separator
        $pdf->SetDrawColor(55, 61, 86, 60);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($x + 1, $currentY, $x + $width - 1, $currentY);
        $currentY += 2;

        // 5. Capsule information
        try { $pdf->SetFont('opensans', '', 8); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 8); }
        $pdf->SetXY($x + 1, $currentY);
        
        // Dynamic dosage text based on product type
        $productType = $rotuloData['product_type'] ?? 'NOITE';
        $dosageText = '';
        if (mb_strtoupper($productType, 'UTF-8') === 'DIA') {
            $dosageText = 'Consumir 1 dose diluída em água no desjejum. Seis dias da semana.';
        } else {
            $dosageText = 'Consumir 1 dose diluído em água. Seis dias da semana.';
        }
        
        // Calculate max width as 60% of pouch width
        $maxDosageWidth = $width * 0.65;
        
        $dosageHeight = $pdf->getStringHeight($maxDosageWidth, $this->cleanText($dosageText));
        $pdf->MultiCell($maxDosageWidth, 2, $this->cleanText($dosageText), 0, 1, 'L');
        
        // Add separator "|" between dosage text and "26 DOSES"
        // First, add the large "|" separator in the middle
        try { $pdf->SetFont('opensans', '', 18); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 12); }
        $separatorText = "|";
        $separatorWidth = $pdf->GetStringWidth($separatorText);
        $separatorX = $x - 3 + $maxDosageWidth + 5; // Position after dosage text with 5mm gap
        $pdf->SetXY($separatorX, $currentY - 2);
        $pdf->Cell($separatorWidth, 3, $separatorText, 0, 1, 'L');
        
        // Then add "26 DOSES" to the right of the separator
        try { $pdf->SetFont('opensans', '', 9); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 9); }
        $dosesText = "26 DOSES";
        $dosesWidth = $pdf->GetStringWidth($dosesText);
        $dosesX = $separatorX + $separatorWidth + 1; // Position after the separator with 2mm gap
        $pdf->SetXY($dosesX, $currentY + 1);
        $pdf->Cell($dosesWidth, 2, $dosesText, 0, 1, 'L');
        
        $currentY += $dosageHeight + 1; // Space for ingredients and capsule info
        
        // 6. Horizontal line separator
        $pdf->Line($x + 1, $currentY, $x + $width - 1, $currentY);
        $currentY += 2;
        
        // 7. Usage Instructions (centered)
        try { $pdf->SetFont('opensans', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
        $pdf->SetXY($x + 1, $currentY);
        $pdf->Cell($contentWidth, 2, "Conservar em refrigerador por até 90 dias.", 0, 1, 'L');
        $currentY += 3;
        
        // 8. Patient Name (centered)
        try { $pdf->SetFont('opensans', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
        $pdf->SetXY($x + 1, $currentY);
        $pdf->Cell($contentWidth, 2, "Após aberto, consumir em 30 dias.", 0, 1, '"L');
        $currentY += 7;
        
        // Add Purissima logo (SVG) - positioned at right bottom
        $logoPath = $this->fontsPath . '/../images/purissima-logo.svg';
        if (file_exists($logoPath)) {
            try {
                // Calculate logo position and size for right bottom corner
                $logoWidth = $contentWidth * 0.5; // 30% of content width
                $logoHeight = 10; // Smaller height for bottom positioning
                $logoX = $x + $width - $logoWidth + 8; // Right side with 1mm margin
                $logoY = $y + $height - $logoHeight - 2; // Bottom with 2mm margin
                
                // Add SVG logo
                $pdf->ImageSVG($logoPath, $logoX, $logoY, $logoWidth, $logoHeight);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to add SVG logo: ' . $e->getMessage());
                // Fallback to text if SVG fails
                try { $pdf->SetFont('opensansb', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 4); }
                $pdf->SetXY($x + $width - 20, $y + $height - 6);
                $pdf->Cell(18, 2, "PURISSIMA", 0, 1, 'R');
            }
        } else {
            // Fallback to text if no SVG file found
            try { $pdf->SetFont('opensansb', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 4); }
            $pdf->SetXY($x + $width - 20, $y + $height - 6);
            $pdf->Cell(18, 2, "PURISSIMA", 0, 1, 'R');
        }
        
        // Bottom information - positioned at bottom left
        $bottomY = $y + $height - 12; // 12mm from bottom
        
        // REQ information - extract from rotuloData
        $reqValues = [];
        if (isset($rotuloData['req_values']) && is_array($rotuloData['req_values'])) {
            $reqValues = array_filter($rotuloData['req_values'], function($req) {
                return !empty(trim((string)$req));
            });
        } elseif (isset($rotuloData['req']) && !empty($rotuloData['req'])) {
            $reqValues = [trim((string)$rotuloData['req'])];
        }
        
        if (!empty($reqValues)) {
            try { $pdf->SetFont('opensans', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
            $pdf->SetXY($x + 1, $bottomY);
            $reqText = 'REQ ' . implode(', ', $reqValues);
            $pdf->Cell($contentWidth, 1.5, $this->cleanText($reqText), 0, 1, 'L');
        }
        
        // RT information
        try { $pdf->SetFont('opensans', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
        $pdf->SetXY($x + 1, $bottomY + 3.5);
        $pdf->Cell($contentWidth, 1.5, "RT: Paula Souza de Sales | CRF 51370", 0, 1, 'L');
        
        // Fab/Val information
        try { $pdf->SetFont('opensans', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
        $pdf->SetXY($x + 1, $bottomY + 7);
        
        // Generate dynamic dates
        $fabDate = date('d/m/y');
        $valDate = date('d/m/y', strtotime('+90 days'));
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
        $productName = substituir($rotuloData['product_name']) ;
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
        
        // Left column - Ingredients list
        $ingredients = $this->getIngredientsList($rotuloData);
        $ingredientY = $currentY;
        
        
        // Ingredients list (formatted with semicolons and line breaks)
        $formattedIngredients = implode('; ', $ingredients) . '.';
        
        // Use one MultiCell for all ingredients (like pouch rotulo)
        try { $pdf->SetFont('opensans', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 4); }
        $pdf->SetXY($x + 2, $ingredientY+1);
        $pdf->MultiCell($leftColumnWidth-3, 1.5, $this->cleanText($formattedIngredients), 0, 'L');
        

        $isWhiteText = ($colorScheme['text'][0] == 0 && $colorScheme['text'][1] == 0 && 
                       $colorScheme['text'][2] == 0 && $colorScheme['text'][3] == 0);
        $svgFileName = $isWhiteText ? 'purissima-social-white.svg' : 'purissima-social-beige.svg';
        $svgPath = $this->fontsPath . '/../images/' . $svgFileName;
        
        if (file_exists($svgPath)) {
            $svgY = $y + $height - 8; // Position near bottom of rotulo
            $svgX = $x +3 ; // Center in left column
            $pdf->ImageSVG($svgPath, $svgX, $svgY, 30, 10);
        }
    
        // Middle column - Header - Product name (large, centered)
        $productName = "FÓRMULA NUTRACÊUTICA";
        try { $pdf->SetFont('brandon_reg', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 7); }
        $pdf->SetXY($middleColumnX, $currentY);
        $pdf->Cell($middleColumnWidth - 2, 4, $this->cleanText(mb_strtoupper($productName, 'UTF-8')), 0, 1, 'C');
        $currentY += 3;
        
        // Product type
        $productType = substituir($rotuloData['product_name']);
        try { $pdf->SetFont('brandontextblack', '', 16); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 16); }
        
        // Wrap text to fit within the available width
        $maxWidth = $middleColumnWidth - 2;
        $wrappedLines = $this->wrapTextToLines($this->cleanText(mb_strtoupper($productType, 'UTF-8')), $maxWidth, $pdf);
        
        // Display each line
        foreach ($wrappedLines as $line) {
            $pdf->SetXY($middleColumnX, $currentY);
            $pdf->Cell($maxWidth, 3, $line, 0, 1, 'C');
            $currentY += 6; // Move down for next line
        } // 3 units spacing from top of product type text
        $currentY += 1.5;
        // Doctor name
        try { $pdf->SetFont('opensans', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
        $pdf->SetXY($middleColumnX, $currentY );
        $pdf->Cell($middleColumnWidth - 2, 2, $this->cleanText('TOMAR 2 CÁPS JUNTO AS REFEIÇÕES'), 0, 1, 'C');
        $currentY += 5;
        
        //Add Patient Name
        $patientName = $rotuloData['patient_name'] ?? 'SEU NOME';
        try { $pdf->SetFont('brandontextblack', '', 10); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 10); }
        $pdf->SetXY($middleColumnX, $y + $height - 9);
        $pdf->Cell($middleColumnWidth - 2, 3, $this->cleanText(mb_strtoupper($patientName, 'UTF-8')), 0, 1, 'C');
        $currentY += 4.5;

        // Horizontal line separator
        $pdf->SetDrawColor(0, 0, 0, 0);
        $lineWidth = $width * 0.2; // 30% of width
        $lineStartX = $x + ($width - $lineWidth) / 2; // Center the line
        $pdf->Line($lineStartX, $y + $height - 4.5, $lineStartX + $lineWidth, $y + $height - 4.5);
        $currentY += 0;

        //Add Dr Fran Castro name (middle column)
        try { $pdf->SetFont('brandon_reg', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
        $pdf->SetXY($middleColumnX, $y + $height - 4);
        $pdf->Cell($middleColumnWidth - 2, 3, $this->cleanText('DRA. FRAN CASTRO'), 0, 1, 'C');
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
            $svgX = $rightColumnX - 3 ; // Center in right column
            $pdf->ImageSVG($svgPath, $svgX, $svgY, 6, 6);
        }
        
        // Capsule information (60 CAPSULAS | 30 DOSES)
        try { $pdf->SetFont('brandontextblack', '', 5.5); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 5.5); }
        $pdf->SetXY($rightColumnX+4, $rightColumnY);
        $pdf->Cell($rightColumnWidth - 2, 3, $this->cleanText('60 CÁPSULAS | 30 DOSES'), 0, 1, 'L');
        $rightColumnY += 3;
        
        // Dosage information from item (like receituario) - only if not empty
        
        if ($rotuloData['dosage']) {
            $dosage = $rotuloData['dosage'];
            try { $pdf->SetFont('opensans', '', 5); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 5); }
            $pdf->SetXY($rightColumnX+4, $rightColumnY);
            $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText($dosage), 0, 1, 'L');
            $rightColumnY += 3;
        }
        
        // Usage information (Uso interno.)
        try { $pdf->SetFont('opensans', '', 4.8); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 4.8); }
        $pdf->SetXY($rightColumnX+ 4, $rightColumnY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('Uso interno.'), 0, 1, 'L');
        $rightColumnY += 6;
        
        // Line divider under "Uso interno."
        $pdf->SetDrawColor(0, 0, 0, 20);
        $pdf->Line($rightColumnX -3, $rightColumnY, $rightColumnX + $rightColumnWidth - 10, $rightColumnY);
        $rightColumnY += 2;
        
        // RT and Company Information - positioned at bottom
        $bottomY = $y + $height - 15; // Position near bottom of rotulo
        
                // REQ values
                $reqValues = $rotuloData['req_values'] ?? [];
                if (!empty($reqValues)) {
                    try { $pdf->SetFont('opensans', '', 4.5); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 5); }
                    $pdf->SetXY($rightColumnX- 4, $bottomY);
                    $reqText = 'REQ ' . implode(', ', $reqValues);
                    $pdf->Cell($rightColumnWidth - 2, 3, $this->cleanText($reqText), 0, 1, 'L');
                    $bottomY += 2.5;
                }

        // RT (Responsável Técnico)
        try { $pdf->SetFont('opensans', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 4); }
        $pdf->SetXY($rightColumnX- 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('RT: Paula Souza de Sales. CRF 51370'), 0, 1, 'L');
        $bottomY += 4;
        
        // Company Information
        try { $pdf->SetFont('opensans', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 3); }
        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('PURISSIMA | Alameda Araguaia, 933 | conj 81,'), 0, 1, 'L');
        $bottomY += 2;
        
        $pdf->SetXY($rightColumnX- 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 4, 2, $this->cleanText('Alphaville Industrial | Barueri/SP | CEP:'), 0, 1, 'L');
        $bottomY += 2;
        
        $pdf->SetXY($rightColumnX- 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('06455-000.CNPJ: 57.511.386/0001-13.'), 0, 1, 'L');
        
        // Manufacturing and Validity dates (vertical text) - positioned at maximum right
        $rightColumnCenterX = $x + $width - 2; // Maximum right position
        $rightColumnCenterY = $y + ($height / 2 - 2);
        
        // Manufacturing and validity dates
        $fabDate = date('d/m/y');
        $valDate = date('d/m/y', strtotime('+90 days'));
        $dateText = "Fab: {$fabDate} | Val: {$valDate}";
        
        // Set font for vertical date text
        try { $pdf->SetFont('opensans', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 4); }
        
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
        $ingredients = $this->getIngredientsList($rotuloData);
        $ingredientY = $currentY;
        
        
        // Ingredients list (formatted with semicolons and line breaks)
        $formattedIngredients = implode('; ', $ingredients) . '.';
        
        // Use one MultiCell for all ingredients (like pouch rotulo)
        try { $pdf->SetFont('opensans', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 4); }
        $pdf->SetXY($x + 2, $ingredientY+1);
        $pdf->MultiCell($leftColumnWidth-3, 1.5, $this->cleanText($formattedIngredients), 0, 'L');
        
        // Add Purissima social SVG at bottom of left column
        // Use white version when text color is white (dark backgrounds)
        $productName = $rotuloData['product_name'] ?? '';
        $colorScheme = $this->colorSchemeService->getColorSchemeForProduct($productName);
        $isWhiteText = ($colorScheme['text'][0] == 0 && $colorScheme['text'][1] == 0 && 
                       $colorScheme['text'][2] == 0 && $colorScheme['text'][3] == 0);
        $svgFileName = $isWhiteText ? 'purissima-social-white.svg' : 'purissima-social.svg';
        $svgPath = $this->fontsPath . '/../images/' . $svgFileName;
        
        if (file_exists($svgPath)) {
            $svgY = $y + $height - 8; // Position near bottom of rotulo
            $svgX = $x +3 ; // Center in left column
            $pdf->ImageSVG($svgPath, $svgX, $svgY, 30, 10);
        }
    
        // Middle column - Header - Product name (large, centered)
        $productName = "SUPLEMENTO NUTRICIONAL";
        try { $pdf->SetFont('brandon_reg', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 7); }
        $pdf->SetXY($middleColumnX, $currentY);
        $pdf->Cell($middleColumnWidth - 2, 4, $this->cleanText(mb_strtoupper($productName, 'UTF-8')), 0, 1, 'C');
        $currentY += 3;
        
        // Product type
        $productType = substituir($rotuloData['product_type']);
        try { $pdf->SetFont('brandontextblack', '', 29); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 29); }
        
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
        try { $pdf->SetFont('opensans', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
        $pdf->SetXY($middleColumnX, $currentY);
        $pdf->Cell($middleColumnWidth - 2, 2, $this->cleanText('TOMAR 2 CÁPS JUNTO AS REFEIÇÕES'), 0, 1, 'C');
        $currentY += 5;
        
        //Add Patient Name
        $patientName = $rotuloData['patient_name'] ?? 'SEU NOME';
        try { $pdf->SetFont('brandontextblack', '', 10); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 10); }
        $pdf->SetXY($middleColumnX, $currentY);
        $pdf->Cell($middleColumnWidth - 2, 3, $this->cleanText(mb_strtoupper($patientName, 'UTF-8')), 0, 1, 'C');
        $currentY += 4.5;

        // Horizontal line separator
        $pdf->SetDrawColor(0, 0, 0, 0);
        $lineWidth = $width * 0.2; // 30% of width
        $lineStartX = $x + ($width - $lineWidth) / 2; // Center the line
        $pdf->Line($lineStartX, $currentY, $lineStartX + $lineWidth, $currentY);
        $currentY += 0;

        //Add Dr Fran Castro name (middle column)
        try { $pdf->SetFont('brandon_reg', '', 7); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 7); }
        $pdf->SetXY($middleColumnX, $currentY);
        $pdf->Cell($middleColumnWidth - 2, 3, $this->cleanText('DRA. FRAN CASTRO'), 0, 1, 'C');
        $currentY += 4;
        
        // Right column - Content after DRA. FRAN CASTRO
        $rightColumnY = $y + 2; // Start at same level as other columns (at the beginning)
        
        $colorScheme = $this->colorSchemeService->getColorSchemeForProduct($productName);
        $isWhiteText = ($colorScheme['text'][0] == 0 && $colorScheme['text'][1] == 0 && 
                       $colorScheme['text'][2] == 0 && $colorScheme['text'][3] == 0);
        if ($productType === 'DIA') {
            $svgFileName = $isWhiteText ? 'icon-day-white.svg' : 'icon-day.svg';
        } else {
            $svgFileName = $isWhiteText ? 'icon-night-white.svg' : 'icon-night.svg';
        }
        $svgPath = $this->fontsPath . '/../images/' . $svgFileName;
        if (file_exists($svgPath)) {
            $svgY = $rightColumnY; // Position near bottom of rotulo
            $svgX = $rightColumnX - 3 ; // Center in right column
            $pdf->ImageSVG($svgPath, $svgX, $svgY, 6, 6);
        }
        
        // Capsule information (60 CAPSULAS | 30 DOSES)
        try { $pdf->SetFont('brandontextblack', '', 5.5); } catch (\Exception $e) { $pdf->SetFont('helvetica', 'B', 5.5); }
        $pdf->SetXY($rightColumnX+4, $rightColumnY);
        $pdf->Cell($rightColumnWidth - 2, 3, $this->cleanText('26 DOSES'), 0, 1, 'L');
        $rightColumnY += 4;
        
        // Dosage information from item (like receituario) - only if not empty
        
        if ($rotuloData['dosage']) {
            $dosage = $rotuloData['dosage'];
            try { $pdf->SetFont('opensans', '', 5); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 5); }
            $pdf->SetXY($rightColumnX+4, $rightColumnY);
            $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText($dosage), 0, 1, 'L');
            $rightColumnY += 3;
        }
        
        // Usage information (Uso interno.)
        try { $pdf->SetFont('opensans', '', 4.8); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 4.8); }
        $pdf->SetXY($rightColumnX+ 4, $rightColumnY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('Uso interno.'), 0, 1, 'L');
        $rightColumnY += 6;
        
        // Line divider under "Uso interno."
        $pdf->SetDrawColor(0, 0, 0, 20);
        $pdf->Line($rightColumnX -3, $rightColumnY, $rightColumnX + $rightColumnWidth - 10, $rightColumnY);
        $rightColumnY += 2;
        
        // RT and Company Information - positioned at bottom
        $bottomY = $y + $height - 15; // Position near bottom of rotulo
        
                // REQ values
                $reqValues = $rotuloData['req_values'] ?? [];
                if (!empty($reqValues)) {
                    try { $pdf->SetFont('opensans', '', 4.5); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 5); }
                    $pdf->SetXY($rightColumnX- 4, $bottomY);
                    $reqText = 'REQ ' . implode(', ', $reqValues);
                    $pdf->Cell($rightColumnWidth - 2, 3, $this->cleanText($reqText), 0, 1, 'L');
                    $bottomY += 2.5;
                }

        // RT (Responsável Técnico)
        try { $pdf->SetFont('opensans', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 4); }
        $pdf->SetXY($rightColumnX- 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('RT: Paula Souza de Sales. CRF 51370'), 0, 1, 'L');
        $bottomY += 4;
        
        // Company Information
        try { $pdf->SetFont('opensans', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 3); }
        $pdf->SetXY($rightColumnX - 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('PURISSIMA | Alameda Araguaia, 933 | conj 81,'), 0, 1, 'L');
        $bottomY += 2;
        
        $pdf->SetXY($rightColumnX- 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 4, 2, $this->cleanText('Alphaville Industrial | Barueri/SP | CEP:'), 0, 1, 'L');
        $bottomY += 2;
        
        $pdf->SetXY($rightColumnX- 4, $bottomY);
        $pdf->Cell($rightColumnWidth - 2, 2, $this->cleanText('06455-000.CNPJ: 57.511.386/0001-13.'), 0, 1, 'L');
        
        // Manufacturing and Validity dates (vertical text) - positioned at maximum right
        $rightColumnCenterX = $x + $width - 2; // Maximum right position
        $rightColumnCenterY = $y + ($height / 2 - 2);
        
        // Manufacturing and validity dates
        $fabDate = date('d/m/y');
        $valDate = date('d/m/y', strtotime('+90 days'));
        $dateText = "Fab: {$fabDate} | Val: {$valDate}";
        
        // Set font for vertical date text
        try { $pdf->SetFont('opensans', '', 4); } catch (\Exception $e) { $pdf->SetFont('helvetica', '', 4); }
        
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
            'colorSchemeType' => $colorSchemeType
        ]);
        
        // Update the rotulo data with the color scheme type
        $rotuloData['color_scheme_type'] = $colorSchemeType;
        
        // Add layout type to rotulo data
        $rotuloData['layout_type'] = $layoutType;
        
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
        return 'OTHER';
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
    private function formatDatePortuguese(bool $capitalizeMonths = true, bool $leadingZeros = true): string
    {
        if ($capitalizeMonths) {
            $months = [
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
            ];
        } else {
            $months = [
                1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
                5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
                9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
            ];
        }
        
        $day = $leadingZeros ? date('d') : date('j');
        $month = $months[(int)date('n')];
        $year = date('Y');
        
        return "{$day} de {$month} de {$year}";
    }
    
    /**
     * Get ingredients list for the rotulo
     */
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
        
        $this->logger->debug('Using default ingredients for product type', [
            'product_type' => $productType,
            'product_name' => $rotuloData['product_name'] ?? 'Unknown'
        ]);
        
        switch (mb_strtoupper($productType, 'UTF-8')) {
            case 'DIA':
                return [
                    'Proteína Total 15 g (Genu-in®10 g Whey Protein Isolado 5 g);',
                    'Magnésio bisglicinato - 300mg;',
                    'Taurina - 2g;',
                    'Verisol - 5g;',
                    'Fortibone - 5g;',
                    'Seleniometionina - 100mcg;',
                    'Manganês Quelado - 1 mg;',
                    'Cromo GTF - 200 mcg;',
                    'Molibdênio Quelado 100 - mcg;',
                    'Genu-in - 20g;',
                    'Potássio quelado - 99mg;',
                    'Inositol - 500mg;',
                    'Wellmune - 500mg;',
                    'UC-II - 40mg;',
                    'Ácido hialurônico - 100mg;',
                    'Própolis Mais - 200mg;',
                    'Exsynutriment - 300mg;',
                    'Lactoferrina - 600mg.'
                ];
                
            case 'NOITE':
                return [
                    'Própolis Mais 200mg',
                    'Zinco Quelado 15 MG',
                    'Cobre Quelado 0,5 MG',
                    'Exsynutriment 100mg',
                    'Boro quelado 2mg',
                    'MSM 300mg',
                    'Tiamina 10 MG',
                    'Riboflavina 10 MG',
                    'Ácido Pantotênico 100 MG'
                ];
                
            case 'CAPSULAS':
                return [
                    'Cápsula de gelatina',
                    'Magnésio bisglicinato 200mg',
                    'Vitamina D3 1000 UI',
                    'Zinco quelado 15mg',
                    'Selênio 100mcg',
                    'Vitamina C 500mg',
                    'Vitamina E 400 UI',
                    'Coenzima Q10 100mg'
                ];
                
            case 'PREMIO':
                return [
                    'Proteína isolada 25g',
                    'BCAA 5g',
                    'Glutamina 3g',
                    'Creatina 3g',
                    'Beta-alanina 2g',
                    'L-carnitina 1g',
                    'Taurina 1g',
                    'Vitaminas B complexo',
                    'Minerais quelados'
                ];
                
            default:
                return [
                    'Ingrediente A 100mg',
                    'Ingrediente B 50mg',
                    'Ingrediente C 20mg',
                    'Ingrediente D 10mg'
                ];
        }
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
    public function createMultipleRotulosPdf(array $orderData, array $items): string
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
                $productType = $rotuloData['product_type'] ?? 'default';
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
            
            // Process all items in order: pouches first, then capsules, then others
            // Use column 1 first, then column 2 only if needed
            $currentY = $margin; // Start at top of page
            $currentCol = 0; // Start with column 1
            $columnWidth = ($pageWidth - 2 * $margin - $spacing) / 2; // 2 columns only
            
            // Combine all items in the correct order
            $allItems = array_merge($pouches, $capsulas, $otherItems);
            
            foreach ($allItems as $itemData) {
                $productType = $itemData['product_type'] ?? 'default';
                $productName = $itemData['product_name'] ?? '';
                $layoutType = $this->getProductLayoutType($productType, $productName);
                
                // Determine dimensions based on layout type
                if ($layoutType === 'pouch') {
                    $width = $pouchWidth;
                    $height = $pouchHeight;
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

            // Output the PDF directly to browser
            $pdf->Output($filename, 'D');

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
        if (strpos($itemName, 'dia') !== false || 
            strpos($composition, 'dia') !== false ||
            strpos($itemName, 'manhã') !== false ||
            strpos($itemName, 'manha') !== false) {
            return 'DIA';
        }
        
        // Check for NOITE indicators - only if explicitly contains "noite" or is a specific NOITE product
        if (strpos($itemName, 'noite') !== false || 
            strpos($composition, 'noite') !== false) {
            return 'NOITE';
        }
        
        // Check for CAPSULAS indicators
        if (strpos($itemName, 'capsula') !== false || 
            strpos($itemName, 'cápsula') !== false ||
            strpos($itemName, 'capsule') !== false) {
            return 'CAPSULAS';
        }
        
        // Check for PREMIO/PREMIUM indicators
        if (strpos($itemName, 'premio') !== false || 
            strpos($itemName, 'premium') !== false ||
            strpos($itemName, 'premium') !== false) {
            return 'PREMIO';
        }
        
        return 'default';
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
            // Check if all items have req field
            $allItemsHaveReq = true;
            $reqValues = [];
            foreach ($items as $item) {
                if (!isset($item['req']) || trim((string)$item['req']) === '') {
                    $allItemsHaveReq = false;
                    break;
                }
                $reqValues[] = trim((string)$item['req']);
            }
            
            if (!$allItemsHaveReq) {
                throw new \Exception('All items must have req field to generate Sticker');
            }

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
            '•', '–', '—', '…', '"', '"', "'", "'", '€', '°', 'º', 'ª', 
            'â€¢', 'â€"', 'â€"', 'â€¦', 'â‚¬', 'Â°', 'Âº', 'Âª'
        ], [
            '-', '-', '-', '...', '"', '"', "'", "'", 'EUR', 'o', 'o', 'a',
            '-', '-', '-', '...', 'EUR', 'o', 'o', 'a'
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
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->formatDatePortuguese(), 0, 1, 'C');
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

}
