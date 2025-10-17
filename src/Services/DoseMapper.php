<?php

namespace App\Services;

class DoseMapper
{
    /**
     * Map of normalized item name => dose number
     * Keep keys lowercase and normalized; values are just the numbers.
     */
    private array $doseByItem = [
        // ===== VITAL MEN PRODUCTS =====
        // Vital Man Day Essencial
        'vital man day essencial - pouch' => 1,
        'vital man day essencial - caps' => 2,

        // Vital Man Night Essencial
        'vital man night essencial - pouch' => 1,
        'vital man night essencial - caps' => 1,

        // Vital Man Day Avançado
        'vital man day avançado - pouch' => 1,
        'vital man day avançado - caps' => 2,
        'vital man day avançada - pouch' => 1,
        'vital man day avançada - caps' => 2,

        // Vital Man Night Avançado
        'vital man night avançado - pouch' => 1,
        'vital man night avançado - caps' => 2,
        'vital man night avançada - pouch' => 1,
        'vital man night avançada - caps' => 2,

        // Vital Man Day Premium
        'vital man day premium - pouch' => 1,
        'vital man day premium - caps' => 2,

        // Vital Man Night Premium
        'vital man night premium - pouch' => 1,
        'vital man night premium - caps' => 2,

        // ===== VITAL WOMEN PRODUCTS =====
        // Vital Woman Day Essencial
        'vital woman day essencial - pouch' => 1,
        'vital woman day essencial - caps' => 2,

        // Vital Woman Night Essencial
        'vital woman night essencial - pouch' => 1,
        'vital woman night essencial - caps' => 2,

        // Vital Woman Day Avançada
        'vital woman day avançada - pouch' => 1,
        'vital woman day avançada - caps' => 2,
        'vital woman day avançado - pouch' => 1,
        'vital woman day avançado - caps' => 2,

        // Vital Woman Night Avançada
        'vital woman night avançada - pouch' => 1,
        'vital woman night avançada - caps' => 2,
        'vital woman night avançado - pouch' => 1,
        'vital woman night avançado - caps' => 2,

        // Vital Woman Day Premium
        'vital woman day premium - pouch' => 1,
        'vital woman day premium - caps' => 3,

        // Vital Woman Night Premium
        'vital woman night premium - pouch' => 1,
        'vital woman night premium - caps' => 3,

        // Vital Woman Special Day Essencial
        'vital woman special day essencial - pouch' => 1,
        'vital woman special day essencial - caps' => 2,

        // ===== LONGEVITY MEN PRODUCTS =====
        // Longevity Man Day Essencial
        'longevity man day essencial - pouch' => 1,
        'longevity man day essencial - caps' => 3,

        // Longevity Man Night Essencial
        'longevity man night essencial - pouch' => 1,
        'longevity man night essencial - caps' => 3,

        // Longevity Man Day Avançado
        'longevity man day avançado - pouch' => 1,
        'longevity man day avançado - caps' => 3,
        'longevity man day avançada - pouch' => 1,
        'longevity man day avançada - caps' => 3,

        // Longevity Man Night Avançado
        'longevity man night avançado - pouch' => 1,
        'longevity man night avançado - caps' => 3,
        'longevity man night avançada - pouch' => 1,
        'longevity man night avançada - caps' => 3,

        // Longevity Man Day Premium
        'longevity man day premium - pouch' => 1,
        'longevity man day premium - caps' => 3,

        // Longevity Man Night Premium
        'longevity man night premium - pouch' => 1,
        'longevity man night premium - caps' => 3,

        // ===== LONGEVITY WOMEN PRODUCTS =====
        // Longevity Woman Day Essencial
        'longevity woman day essencial - pouch' => 1,
        'longevity woman day essencial - caps' => 3,

        // Longevity Woman Night Essencial
        'longevity woman night essencial - pouch' => 1,
        'longevity woman night essencial - caps' => 3,

        // Longevity Woman Day Avançada
        'longevity woman day avançada - pouch' => 1,
        'longevity woman day avançada - caps' => 3,
        'longevity woman day avançado - pouch' => 1,
        'longevity woman day avançado - caps' => 3,

        // Longevity Woman Night Avançada
        'longevity woman night avançada - pouch' => 1,
        'longevity woman night avançada - caps' => 3,
        'longevity woman night avançado - pouch' => 1,
        'longevity woman night avançado - caps' => 3,

        // Longevity Woman Day Premium
        'longevity woman day premium - pouch' => 1,
        'longevity woman day premium - caps' => 4,

        // Longevity Woman Night Premium
        'longevity woman night premium - pouch' => 1,
        'longevity woman night premium - caps' => 4,

        // ===== PURÍSSIMA GESTANTE PRODUCTS =====
        // Puríssima Gestante Day
        'puríssima gestante day - pouch' => 1,
        'puríssima gestante day essencial - caps' => 2,
        'puríssima gestante day avançada - caps' => 2,
        'puríssima gestante day premium - caps' => 3,

        // Puríssima Gestante Night
        'puríssima gestante night - pouch' => 1,
        'puríssima gestante night essencial - caps' => 1,
        'puríssima gestante night avançada - caps' => 2,
        'puríssima gestante night premium - caps' => 2,

        // Puríssima Gestante with flavors (flavors are removed during normalization)
        'puríssima gestante day' => 1, // for "Puríssima Gestante Dia - Baunilha do Tahiti"
        'puríssima gestante night' => 1, // for "Puríssima Gestante Noite - Frutas Vermelhas"

        // ===== SPECIAL PRODUCTS =====
        'my baby' => 1,
        'my kids' => 1,

        // ===== SYMPTOM-BASED PRODUCTS =====
        // Ansiedade (Anxiety)
        'ansiedade - leve' => 1,
        'ansiedade - moderada' => 2,
        'ansiedade - severa' => 3,

        // Dermatites (Dermatitis)
        'dermatites - leve' => 1,
        'dermatites - moderado' => 2,
        'dermatites - severo' => 3,

        // Equilíbrio do Colesterol (Cholesterol Balance)
        'equilíbrio do colesterol - leve' => 1,
        'equilíbrio do colesterol - moderado' => 2,
        'equilíbrio do colesterol - grave' => 3,

        // Funções Cognitivas (Cognitive Functions)
        'funções cognitivas - leve' => 1,
        'funções cognitivas - moderado' => 2,
        'funções cognitivas - severo' => 3,

        // Hipotiroidismo (Hypothyroidism)
        'hipotiroidismo - leve' => 1,
        'hipotiroidismo - moderado' => 2,
        'hipotiroidismo - severo' => 3,

        // Imunidade Fraca (Weak Immunity)
        'imunidade fraca - leve' => 1,
        'imunidade fraca - moderada' => 2,
        'imunidade fraca - severa' => 3,

        'imunidade - leve' => 1,
        'imunidade - moderada' => 2,
        'imunidade - severa' => 3,

        // Lipedema
        'lipedema - leve' => 1,
        'lipedema - moderado' => 2,
        'lipedema - severo' => 3,

        // Má digestão (Poor Digestion)
        'má digestão - leve' => 1,
        'má digestão - moderado' => 2,
        'má digestão - severo' => 3,
        'má digestão 2 - leve' => 1,
        'má digestão 2 - moderado' => 2,
        'má digestão 2 - severo' => 3,

        // Perda de cabelos (Hair Loss)
        'perda de cabelos - leve' => 1,
        'perda de cabelos - moderado' => 2,
        'perda de cabelos - severo' => 3,

        // Perfil Glicêmico (Glycemic Profile)
        'perfil glicêmico - leve' => 1,
        'perfil glicêmico - moderado' => 2,

        // Problemas Articulares (Joint Problems)
        'problemas articulares - leve' => 1,
        'problemas articulares - moderado' => 2,
        'problemas articulares - severo' => 3,

        // Problemas Femininos (Women's Problems)
        'problemas femininos - leve' => 1,
        'problemas femininos - moderado' => 2,
        'problemas femininos - grave' => 3,

        // Problemas Intestinais (Intestinal Problems)
        'problemas intestinais - leve' => 1,
        'problemas intestinais - moderado' => 2,
        'problemas intestinais - severo' => 3,

        // Problemas Masculinos (Men's Problems)
        'problemas masculinos - leve' => 1,
        'problemas masculinos - moderado' => 2,
        'problemas masculinos - severo' => 3,

        // Problemas de Sono (Sleep Problems)
        'problemas de sono - leve' => 1,
        'problemas de sono - moderado' => 2,
        'problemas de sono - severo' => 3,

        // Saúde cardiovascular (Cardiovascular Health)
        'saúde cardiovascular - leve' => 1,
        'saúde cardiovascular - moderado' => 2
    ];

    public function setMapping(string $itemName, int $doseNumber): void
    {
        $this->doseByItem[$this->normalize($itemName)] = $doseNumber;
    }

    /**
     * Get the dose number for an item; returns null when not mapped.
     */
    public function getDoseNumber(string $itemName): ?int
    {
        return $this->doseByItem[$this->normalize($itemName)] ?? null;
    }

    /**
     * Get the unit type (pouch/caps) from the normalized item name.
     */
    public function getUnitType(string $itemName): string
    {
        $normalized = $this->normalize($itemName);

        if (strpos($normalized, ' - pouch') !== false) {
            return 'pouch';
        } elseif (strpos($normalized, ' - caps') !== false) {
            return 'caps';
        }

        // For symptom-based products and special products, default to caps
        return 'caps';
    }

    /**
     * Get the unit text (SCOOP/SCOOPS/CÁPSULA/CÁPSULAS) based on dose number and unit type.
     */
    public function getUnitText(string $itemName): string
    {
        $doseNumber = $this->getDoseNumber($itemName);
        $unitType = $this->getUnitType($itemName);

        if ($doseNumber === null) {
            return '';
        }

        if ($unitType === 'pouch') {
            return $doseNumber === 1 ? 'SCOOP' : 'SCOOPS';
        } else {
            return $doseNumber === 1 ? 'CÁPSULA' : 'CÁPSULAS';
        }
    }

    /**
     * Get the complete dosage text for an item; returns empty string when not mapped.
     */
    public function getDosageText(string $itemName): string
    {
        $doseNumber = $this->getDoseNumber($itemName);
        $unitText = $this->getUnitText($itemName);

        if ($doseNumber === null || $unitText === '') {
            return '';
        }

        return "1 DOSE = {$doseNumber} {$unitText}";
    }

    /**
     * Get the complete dosage text for an item; returns empty string when not mapped.
     */
    public function getDosageTexCompletet(string $itemName): string
    {
        $doseNumber = $this->getDoseNumber($itemName);
        $unitText = substr($this->getUnitText($itemName), 0, 5);

        if ($doseNumber === null || $unitText === '') {
            return '';
        }

        return "TOMAR {$doseNumber} {$unitText} JUNTO AS REFEIÇÕES";
    }

    /**
     * Get structured dose information for an item.
     */
    public function getDoseInfo(string $itemName): array
    {
        $doseNumber = $this->getDoseNumber($itemName);
        $unitType = $this->getUnitType($itemName);
        $unitText = $this->getUnitText($itemName);

        return [
            'number' => $doseNumber,
            'unit_type' => $unitType,
            'unit_text' => $unitText,
            'full_text' => $doseNumber !== null ? "1 DOSE = {$doseNumber} {$unitText}" : ''
        ];
    }

    private function normalize(string $name): string
    {
        $normalized = mb_strtolower(trim($name));

        // Handle backend patterns:
        // "Pouch Longevity Men Noite Essencial - Baunilha do Tahiti"
        // "Pouch Longevity Women Dia Avançada - Baunilha do Tahiti" 
        // "Cápsula Longevity Women Dia Premium"
        // "Puríssima Gestante Dia - Baunilha do Tahiti"
        // "Puríssima Gestante Dia Avançada Cápsula"
        // Convert to our mapping format: "longevity man night essencial - pouch"

        // Remove flavor/scent part after the last dash (but keep severity levels)
        // Handle both regular dash and en-dash
        $parts = preg_split('/\s*[-–]\s*/', $normalized);
        if (count($parts) > 1) {
            $lastPart = trim($parts[count($parts) - 1]);
            // Check if last part is a flavor (not a severity level)
            $flavors = ['baunilha do tahiti', 'frutas vermelhas', 'limonada suíça', 'mousse de maracujá'];
            if (in_array($lastPart, $flavors)) {
                $baseName = trim($parts[0]);
                $normalized = $baseName;
            }
        }

        // Handle Unicode characters (convert \u00e7 to ç, etc.)
        $normalized = str_replace([
            '\u00e7',
            '\u00e1',
            '\u00e9',
            '\u00ed',
            '\u00f3',
            '\u00fa',
            '\u00c7',
            '\u00c1',
            '\u00c9',
            '\u00cd',
            '\u00d3',
            '\u00da'
        ], [
            'ç',
            'á',
            'é',
            'í',
            'ó',
            'ú',
            'Ç',
            'Á',
            'É',
            'Í',
            'Ó',
            'Ú'
        ], $normalized);

        // Convert backend format to our mapping format
        $normalized = str_replace([
            'pouch longevity men',
            'pouch longevity women',
            'pouch longevity woman',
            'cápsula longevity men',
            'cápsula longevity women',
            'cápsula longevity woman',
            'pouch vital men',
            'pouch vital women',
            'pouch vital woman',
            'cápsula vital men',
            'cápsula vital women',
            'cápsula vital woman'
        ], [
            'longevity man',
            'longevity woman',
            'longevity woman',
            'longevity man',
            'longevity woman',
            'longevity woman',
            'vital man',
            'vital woman',
            'vital woman',
            'vital man',
            'vital woman',
            'vital woman'
        ], $normalized);

        // Handle Vital Woman Special Day Essencial
        $normalized = str_replace([
            'vital woman special day essencial'
        ], [
            'vital woman special day essencial'
        ], $normalized);

        // Handle Puríssima Gestante products
        $normalized = str_replace([
            'puríssima gestante dia - pouch',
            'puríssima gestante noite - pouch',
            'puríssima gestante dia essencial cápsula',
            'puríssima gestante dia avançada cápsula',
            'puríssima gestante dia premium cápsula',
            'puríssima gestante noite essencial cápsula',
            'puríssima gestante noite avançada cápsula',
            'puríssima gestante noite premium cápsula'
        ], [
            'puríssima gestante day - pouch',
            'puríssima gestante night - pouch',
            'puríssima gestante day essencial - caps',
            'puríssima gestante day avançada - caps',
            'puríssima gestante day premium - caps',
            'puríssima gestante night essencial - caps',
            'puríssima gestante night avançada - caps',
            'puríssima gestante night premium - caps'
        ], $normalized);

        // Convert Portuguese terms
        $normalized = str_replace([
            'dia',
            'noite',
            'essencial',
            'premium'
        ], [
            'day',
            'night',
            'essencial',
            'premium'
        ], $normalized);

        // Handle gender agreement: convert avançada to avançado for Men products
        if (strpos($normalized, 'man') !== false && strpos($normalized, 'avançada') !== false) {
            $normalized = str_replace('avançada', 'avançado', $normalized);
            error_log('[DoseMapper] Fixed gender agreement: converted "avançada" to "avançado" for Men product');
        }
        // Handle gender agreement: convert avançado to avançada for Woman products
        elseif (strpos($normalized, 'woman') !== false && strpos($normalized, 'avançado') !== false) {
            $normalized = str_replace('avançado', 'avançada', $normalized);
            error_log('[DoseMapper] Fixed gender agreement: converted "avançado" to "avançada" for Woman product');
        }

        // Add pouch/caps suffix based on original name
        if (strpos($name, 'Pouch') !== false) {
            $normalized .= ' - pouch';
            error_log('[DoseMapper] Detected Pouch type for "' . $name . '" -> suffix " - pouch"');
        } elseif (strpos($name, 'Cápsula') !== false || strpos($name, 'Caps') !== false) {
            $normalized .= ' - caps';
            error_log('[DoseMapper] Detected Caps type for "' . $name . '" -> suffix " - caps"');
        }

        // Handle special products (My Baby, My Kids)
        if (strpos($normalized, 'my baby') !== false) {
            $normalized = 'my baby';
        } elseif (strpos($normalized, 'my kids') !== false) {
            $normalized = 'my kids';
        }

        // Remove parenthetical content (everything between parentheses)
        $normalized = preg_replace('/\s*\([^)]*\)/', '', $normalized);

        // Remove trailing colons
        $normalized = rtrim($normalized, ':');

        // Handle symptom-based products - normalize severity indicators
        // Convert en-dashes to regular dashes for consistency
        $normalized = str_replace([
            ' – leve',
            ' – moderado',
            ' – moderada',
            ' – severo',
            ' – severa',
            ' – grave'
        ], [
            ' - leve',
            ' - moderado',
            ' - moderada',
            ' - severo',
            ' - severa',
            ' - grave'
        ], $normalized);

        // Collapse multiple spaces and normalize separators
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        error_log('[DoseMapper] Normalized item name: "' . $normalized . '"');
        return $normalized;
    }
}
