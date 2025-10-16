<?php

namespace App\Services;

/**
 * ColorSchemeService
 * 
 * Maps product names to color schemes based on Purissima supplement categories
 * Each category corresponds to a specific color scheme for horizontal stickers
 */
class ColorSchemeService
{
    /**
     * Color schemes based on the 5 supplement categories from the image
     */
    private const COLOR_SCHEMES = [
        // Beige/Cream Capsule - Intestinal Balance, Probiotics, Lipedema, Before Lunch
        'beige' => [
            'background' => [5, 10, 28, 6],   // Light beige/cream
            'border' => [20, 15, 30, 10],      // Slightly darker beige
            'text' => [28, 62, 100, 23]           // Black text
        ],
        
        // Blue Capsule - Regenerative Sleep, Post-Workout, Men's Health, Joint Health
        'blue' => [
            'background' => [100, 80, 25, 10],   // Dark blue
            'border' => [30, 0, 0, 100],       // Same as background
            'text' => [0, 0, 0, 0]             // White text
        ],
        
        // Maroon/Dark Red Capsule - Digestive Enzymes, Efficient Immunity, Glycemic Control, Cardiovascular Health
        'maroon' => [
            'background' => [15, 90, 45, 49],   // Dark maroon/red
            'border' => [0, 80, 60, 70],       // Same as background
            'text' => [0, 0, 0, 0]             // White text
        ],
        
        // Purple Capsule - Hair Loss Prevention, Women's Health, Memory and Cognition
        'purple' => [
            'background' => [60, 100, 31, 32],   // Dark purple
            'border' => [60, 100, 31, 32],       // Same as background
            'text' => [0, 0, 0, 0]             // White text
        ],
        
        // Orange/Yellow Capsule - Emotional Balance, Cholesterol Balance, Hypothyroidism, Dermatitis Control
        'orange' => [
            'background' => [4, 30, 60, 0],   // Orange/yellow
            'border' => [0, 40, 80, 20],       // Same as background
            'text' => [0, 0, 0, 0]           // Black text
        ],
        
        // Default scheme for unmatched products
        'default' => [
            'background' => [60, 0, 60, 20],   // Dark green (existing default)
            'border' => [70, 0, 70, 30],       // Darker green
            'text' => [0, 0, 0, 0]             // White text
        ]
    ];

    /**
     * Product name patterns mapped to color schemes
     * Based on the supplement categories from the image
     */
    private const PRODUCT_PATTERNS = [
        // Beige/Cream category patterns
        'beige' => [
            '/lipedema/i',
            '/equilíbrio\s+intestinal/i',
            '/probióticos/i',
            '/antes\s+do\s+almoço/i',
            '/saúde\s+intestinal/i',
            '/função\s+digestiva/i',
            '/má\s+digestão/i'
        ],
        
        // Blue category patterns
        'blue' => [
            '/sono\s+regenerativo/i',
            '/sono\s+regenerador/i',
            '/pós-treino/i',
            '/saúde\s+do\s+homem/i',
            '/problemas\s+masculinos/i',
            '/saúde\s+articular/i',
            '/alívio\s+da\s+dor/i',
            '/problemas\s+articulares/i'
        ],
        
        // Maroon category patterns
        'maroon' => [
            '/enzimas\s+digestivas/i',
            '/imunidade\s+eficiente/i',
            '/imunidade/i',
            '/controle\s+glicêmico/i',
            '/perfil\s+glicêmico/i',
            '/saúde\s+cardiovascular/i'
        ],
        
        // Purple category patterns
        'purple' => [
            '/antiqueda\s+capilar/i',
            '/queda\s+de\s+cabelo/i',
            '/perda\s+de\s+cabelos/i',
            '/saúde\s+da\s+mulher/i',
            '/problemas\s+femininos/i',
            '/memória\s+e\s+cognição/i',
            '/função\s+cognitiva/i',
            '/funções\s+cognitivas/i'
        ],
        
        // Orange category patterns
        'orange' => [
            '/equilíbrio\s+emocional/i',
            '/saúde\s+mental\s+e\s+emocional/i',
            '/ansiedade/i',
            '/equilíbrio\s+do\s+colesterol/i',
            '/auxílio\s+ao\s+hipotireoidismo/i',
            '/hipotireoidismo/i',
            '/controle\s+da\s+dermatite/i',
            '/dermatite/i',
            '/dermatites/i'
        ]
    ];

    /**
     * Get color scheme for a product name
     * 
     * @param string $productName The product name to analyze
     * @return array Color scheme array with background, border, and text colors
     */
    public function getColorSchemeForProduct(?string $productName): array
    {
        $productName = trim($productName ?? '');
        
        if (empty($productName)) {
            return self::COLOR_SCHEMES['default'];
        }
        
        // Check each color category for matching patterns
        foreach (self::PRODUCT_PATTERNS as $colorScheme => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $productName)) {
                    // Cool debug: Log which pattern matched
                    error_log(sprintf(
                        "🎯 ColorSchemeService: Product '%s' matched pattern '%s' -> %s scheme",
                        $productName,
                        $pattern,
                        $colorScheme
                    ));
                    return self::COLOR_SCHEMES[$colorScheme];
                }
            }
        }
        
        // Cool debug: Log when no pattern matches
        error_log(sprintf(
            "❌ ColorSchemeService: Product '%s' matched no patterns -> default scheme",
            $productName
        ));
        
        // If no pattern matches, return default scheme
        return self::COLOR_SCHEMES['default'];
    }

    /**
     * Get the color scheme name (beige, blue, maroon, purple, orange, default)
     * 
     * @param string $productName The product name to analyze
     * @return string The color scheme name
     */
    public function getColorSchemeName(?string $productName): string
    {
        $productName = trim($productName ?? '');
        
        if (empty($productName)) {
            return 'default';
        }
        
        // Check each color category for matching patterns
        foreach (self::PRODUCT_PATTERNS as $colorScheme => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $productName)) {
                    return $colorScheme;
                }
            }
        }
        
        return 'default';
    }

    /**
     * Apply color scheme to PDF object
     * 
     * @param object $pdf TCPDF object
     * @param string $productName The product name to determine color scheme
     * @return void
     */
    public function applyColorSchemeToPdf($pdf, ?string $productName): void
    {
        $colorScheme = $this->getColorSchemeForProduct($productName);
        
        // Set background color
        $pdf->SetFillColor(
            $colorScheme['background'][0],
            $colorScheme['background'][1],
            $colorScheme['background'][2],
            $colorScheme['background'][3]
        );
        
        // Set border color
        $pdf->SetDrawColor(
            $colorScheme['border'][0],
            $colorScheme['border'][1],
            $colorScheme['border'][2],
            $colorScheme['border'][3]
        );
        
        // Set text color
        $pdf->SetTextColor(
            $colorScheme['text'][0],
            $colorScheme['text'][1],
            $colorScheme['text'][2],
            $colorScheme['text'][3]
        );
    }

    /**
     * Get all available color schemes
     * 
     * @return array All color schemes
     */
    public function getAllColorSchemes(): array
    {
        return self::COLOR_SCHEMES;
    }

    /**
     * Get all product patterns
     * 
     * @return array All product patterns organized by color scheme
     */
    public function getAllProductPatterns(): array
    {
        return self::PRODUCT_PATTERNS;
    }

    /**
     * Cool debug method to display all color schemes and patterns
     * 
     * @return string Formatted debug information
     */
    public function getDebugInfo(): string
    {
        $debug = "🎨 ColorSchemeService Debug Info\n";
        $debug .= "================================\n\n";
        
        foreach (self::PRODUCT_PATTERNS as $colorScheme => $patterns) {
            $colors = self::COLOR_SCHEMES[$colorScheme];
            $debug .= sprintf(
                "🔸 %s Scheme:\n   Background: C=%d, M=%d, Y=%d, K=%d\n   Border:     C=%d, M=%d, Y=%d, K=%d\n   Text:       C=%d, M=%d, Y=%d, K=%d\n   Patterns:\n",
                strtoupper($colorScheme),
                $colors['background'][0], $colors['background'][1], $colors['background'][2], $colors['background'][3],
                $colors['border'][0], $colors['border'][1], $colors['border'][2], $colors['border'][3],
                $colors['text'][0], $colors['text'][1], $colors['text'][2], $colors['text'][3]
            );
            
            foreach ($patterns as $pattern) {
                $debug .= sprintf("     • %s\n", $pattern);
            }
            $debug .= "\n";
        }
        
        return $debug;
    }

    /**
     * Test a product name and return detailed debug information
     * 
     * @param string $productName The product name to test
     * @return array Detailed debug information
     */
    public function debugProductName(?string $productName): array
    {
        $productName = trim($productName ?? '');
        $matchedPattern = null;
        $matchedScheme = 'default';
        
        if (!empty($productName)) {
            foreach (self::PRODUCT_PATTERNS as $colorScheme => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $productName)) {
                        $matchedPattern = $pattern;
                        $matchedScheme = $colorScheme;
                        break 2;
                    }
                }
            }
        }
        
        $colorScheme = self::COLOR_SCHEMES[$matchedScheme];
        
        return [
            'product_name' => $productName,
            'matched_pattern' => $matchedPattern,
            'matched_scheme' => $matchedScheme,
            'color_scheme' => $colorScheme,
            'formatted_colors' => [
                'background' => sprintf('C=%d, M=%d, Y=%d, K=%d', 
                    $colorScheme['background'][0], $colorScheme['background'][1], 
                    $colorScheme['background'][2], $colorScheme['background'][3]),
                'border' => sprintf('C=%d, M=%d, Y=%d, K=%d', 
                    $colorScheme['border'][0], $colorScheme['border'][1], 
                    $colorScheme['border'][2], $colorScheme['border'][3]),
                'text' => sprintf('C=%d, M=%d, Y=%d, K=%d', 
                    $colorScheme['text'][0], $colorScheme['text'][1], 
                    $colorScheme['text'][2], $colorScheme['text'][3])
            ]
        ];
    }
}
