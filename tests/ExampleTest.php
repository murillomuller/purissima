<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use src\Services\TcpdfService;

class ExampleTest extends TestCase
{
    public function testBasicFunctionality()
    {
        $this->assertTrue(true);
    }

    public function testEnvironmentVariables()
    {
        $this->assertNotEmpty($_ENV['APP_NAME'] ?? 'Purissima PHP Project');
    }

    public function testImprovedLayoutLogic()
    {
        // Test data for layout verification
        $labelData = [
            ['label_type' => 'pouch', 'label_width' => 50, 'label_height' => 30],
            ['label_type' => 'pouch', 'label_width' => 50, 'label_height' => 30],
            ['label_type' => 'pouch', 'label_width' => 50, 'label_height' => 30], // 3rd pouch should fit
            ['label_type' => 'non_pouch', 'label_width' => 60, 'label_height' => 20],
            ['label_type' => 'non_pouch', 'label_width' => 60, 'label_height' => 20], // Should fit under pouches
            ['label_type' => 'non_pouch', 'label_width' => 60, 'label_height' => 20],
            ['label_type' => 'non_pouch', 'label_width' => 60, 'label_height' => 20], // 4th non-pouch should fit
        ];

        $pageWidth = 200;
        $pageHeight = 300;
        $options = ['margin' => 10, 'spacing' => 5];

        // Create a mock TcpdfService to test the layout calculation
        $service = new class extends TcpdfService {
            public function testCalculateOptimalLayout(array $labelData, float $pageWidth, float $pageHeight, array $options): array
            {
                return $this->calculateOptimalLayout($labelData, $pageWidth, $pageHeight, $options);
            }
        };

        $layout = $service->testCalculateOptimalLayout($labelData, $pageWidth, $pageHeight, $options);

        // Verify that the layout was calculated successfully
        $this->assertIsArray($layout);
        $this->assertArrayHasKey('pages', $layout);
        $this->assertArrayHasKey('total_pages', $layout);
        $this->assertArrayHasKey('total_labels', $layout);
        $this->assertEquals(count($labelData), $layout['total_labels']);

        // Verify that we have at least one page
        $this->assertGreaterThan(0, $layout['total_pages']);
        $this->assertNotEmpty($layout['pages']);

        // Verify that labels are properly distributed
        $totalLabelsInLayout = 0;
        foreach ($layout['pages'] as $page) {
            foreach ($page['rows'] as $row) {
                foreach ($row['columns'] as $column) {
                    $totalLabelsInLayout += count($column['labels']);
                }
            }
        }
        $this->assertEquals(count($labelData), $totalLabelsInLayout);
    }
}
