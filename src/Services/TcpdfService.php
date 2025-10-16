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
			
			$this->preparePdf($pdf);
			
			// Render a single order
			$this->renderOrderPrescription($pdf, $templateId, $orderData, $items);
			
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
	 * Generate one combined PDF for multiple orders
	 */
	public function createBatchPrescriptionPdf(array $orders): string
	{
		$filename = 'receituario_batch_' . date('Y-m-d_H-i-s') . '.pdf';
		$filepath = $this->outputPath . '/' . $filename;
		
		$this->logger->info('Generating batch prescription PDF', [
			'orders_count' => count($orders)
		]);
		
		ob_start();
		
		try {
			$basePdfPath = __DIR__ . '/../../storage/pdf/receituario-base.pdf';
			if (!file_exists($basePdfPath)) {
				throw new \Exception('Base PDF template not found: ' . $basePdfPath);
			}
			
			$pdf = new Fpdi();
			$pdf->setSourceFile($basePdfPath);
			$templateId = $pdf->importPage(1);
			
			$this->preparePdf($pdf);
			
			foreach ($orders as $o) {
				if (!isset($o['order']) || !isset($o['items'])) {
					continue;
				}
				$this->renderOrderPrescription($pdf, $templateId, $o['order'], $o['items']);
			}
			
			$pdf->Output($filepath, 'F');
			ob_end_clean();
			
			$this->logger->info('Batch prescription PDF created', [
				'filename' => $filename,
				'filepath' => $filepath,
				'file_exists' => file_exists($filepath),
				'file_size' => file_exists($filepath) ? filesize($filepath) : 0
			]);
			
			return $filename;
		} catch (\Exception $e) {
			ob_end_clean();
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
		$pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + $this->patientInfoSpacing);
		$genero = $orderData['Genero'] ?? null;
		$sexoLabel = '[Não informado]';
		if ($genero === 1 || $genero === '1') {
			$sexoLabel = 'Masculino';
		} elseif ($genero === 2 || $genero === '2') {
			$sexoLabel = 'Feminino';
		}
		$pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Sexo: ' . $sexoLabel), 0, 1, 'R');
		$pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + ($this->patientInfoSpacing * 2));
		$pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Telefone: ' . $this->formatPhoneNumber($orderData['usr_phone'])), 0, 1, 'R');
		$pdf->SetFont('helvetica', 'B', 7.7);
		$pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + ($this->patientInfoSpacing * 3));
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
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + $this->patientInfoSpacing);
        $genero = $orderData['Genero'] ?? null;
        $sexoLabel = '[Não informado]';
        if ($genero === 1 || $genero === '1') {
            $sexoLabel = 'Masculino';
        } elseif ($genero === 2 || $genero === '2') {
            $sexoLabel = 'Feminino';
        }
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Sexo: ' . $sexoLabel), 0, 1, 'R');
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + ($this->patientInfoSpacing * 2));
        $pdf->Cell($this->patientInfoWidth, $this->patientInfoHeight, $this->cleanText('Telefone: ' . $this->formatPhoneNumber($orderData['usr_phone'])), 0, 1, 'R');
        // Set font to helvetica bold for Prescrição (more reliable than opensansb)
        $pdf->SetFont('helvetica', 'B', 7.7);
        $pdf->SetXY($this->patientInfoXPosition, $this->patientInfoStartY + $this->patientNameSpacing + ($this->patientInfoSpacing * 3));
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
     * Format current date in Portuguese format
     * @return string
     */
    private function formatDatePortuguese(): string
    {
        $months = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
            5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
            9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
        ];
        
        $day = date('j'); // Day without leading zeros
        $month = $months[(int)date('n')]; // Month name in Portuguese
        $year = date('Y');
        
        return $day . ' de ' . $month . ' de ' . $year;
    }
}
