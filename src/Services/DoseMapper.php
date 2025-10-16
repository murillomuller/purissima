<?php

namespace App\Services;

class DoseMapper
{
    /**
     * Map of normalized item name => dosage text
     * Keep keys lowercase and normalized; values are full dosage strings.
     */
    private array $doseByItem = [
        // ===== VITAL MEN PRODUCTS =====
        // Vital Man Day Essencial
        'vital man day essencial - pouch' => '1 DOSE = 1 SCOOP',
        'vital man day essencial - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Man Night Essencial
        'vital man night essencial - pouch' => '1 DOSE = 1 SCOOP',
        'vital man night essencial - caps' => '1 DOSE = 1 CÁPSULA',
        
        // Vital Man Day Avançado
        'vital man day avançado - pouch' => '1 DOSE = 1 SCOOP',
        'vital man day avançado - caps' => '1 DOSE = 2 CÁPSULAS',
        'vital man day avançada - pouch' => '1 DOSE = 1 SCOOP',
        'vital man day avançada - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Man Night Avançado
        'vital man night avançado - pouch' => '1 DOSE = 1 SCOOP',
        'vital man night avançado - caps' => '1 DOSE = 2 CÁPSULAS',
        'vital man night avançada - pouch' => '1 DOSE = 1 SCOOP',
        'vital man night avançada - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Man Day Premium
        'vital man day premium - pouch' => '1 DOSE = 1 SCOOP',
        'vital man day premium - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Man Night Premium
        'vital man night premium - pouch' => '1 DOSE = 1 SCOOP',
        'vital man night premium - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // ===== VITAL WOMEN PRODUCTS =====
        // Vital Woman Day Essencial
        'vital woman day essencial - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman day essencial - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Woman Night Essencial
        'vital woman night essencial - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman night essencial - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Woman Day Avançada
        'vital woman day avançada - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman day avançada - caps' => '1 DOSE = 2 CÁPSULAS',
        'vital woman day avançado - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman day avançado - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Woman Night Avançada
        'vital woman night avançada - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman night avançada - caps' => '1 DOSE = 2 CÁPSULAS',
        'vital woman night avançado - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman night avançado - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Woman Day Premium
        'vital woman day premium - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman day premium - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Vital Woman Night Premium
        'vital woman night premium - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman night premium - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Vital Woman Special Day Essencial
        'vital woman special day essencial - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman special day essencial - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // ===== LONGEVITY MEN PRODUCTS =====
        // Longevity Man Day Essencial
        'longevity man day essencial - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man day essencial - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Night Essencial
        'longevity man night essencial - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man night essencial - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Day Avançado
        'longevity man day avançado - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man day avançado - caps' => '1 DOSE = 3 CÁPSULAS',
        'longevity man day avançada - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man day avançada - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Night Avançado
        'longevity man night avançado - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man night avançado - caps' => '1 DOSE = 3 CÁPSULAS',
        'longevity man night avançada - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man night avançada - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Day Premium
        'longevity man day premium - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man day premium - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Night Premium
        'longevity man night premium - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man night premium - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // ===== LONGEVITY WOMEN PRODUCTS =====
        // Longevity Woman Day Essencial
        'longevity woman day essencial - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman day essencial - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Woman Night Essencial
        'longevity woman night essencial - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman night essencial - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Woman Day Avançada
        'longevity woman day avançada - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman day avançada - caps' => '1 DOSE = 3 CÁPSULAS',
        'longevity woman day avançado - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman day avançado - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Woman Night Avançada
        'longevity woman night avançada - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman night avançada - caps' => '1 DOSE = 3 CÁPSULAS',
        'longevity woman night avançado - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman night avançado - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Woman Day Premium
        'longevity woman day premium - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman day premium - caps' => '1 DOSE = 4 CÁPSULAS',
        
        // Longevity Woman Night Premium
        'longevity woman night premium - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman night premium - caps' => '1 DOSE = 4 CÁPSULAS',

        // ===== PURÍSSIMA GESTANTE PRODUCTS =====
        // Puríssima Gestante Day
        'puríssima gestante day - pouch' => '1 DOSE = 1 SCOOP',
        'puríssima gestante day essencial - caps' => '1 DOSE = 2 CÁPSULAS',
        'puríssima gestante day avançada - caps' => '1 DOSE = 2 CÁPSULAS',
        'puríssima gestante day premium - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Puríssima Gestante Night
        'puríssima gestante night - pouch' => '1 DOSE = 1 SCOOP',
        'puríssima gestante night essencial - caps' => '1 DOSE = 1 CÁPSULA',
        'puríssima gestante night avançada - caps' => '1 DOSE = 2 CÁPSULAS',
        'puríssima gestante night premium - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Puríssima Gestante with flavors (flavors are removed during normalization)
        'puríssima gestante day' => '1 DOSE = 1 SCOOP', // for "Puríssima Gestante Dia - Baunilha do Tahiti"
        'puríssima gestante night' => '1 DOSE = 1 SCOOP', // for "Puríssima Gestante Noite - Frutas Vermelhas"

        // ===== SPECIAL PRODUCTS =====
        'my baby' => '1 DOSE = 1 SCOOP',
        'my kids' => '1 DOSE = 1 SCOOP',

        // ===== SYMPTOM-BASED PRODUCTS =====
        // Ansiedade (Anxiety)
        'ansiedade – leve' => '1 DOSE = 1 CÁPSULA',
        'ansiedade – moderada' => '1 DOSE = 2 CÁPSULAS',
        'ansiedade – severa' => '1 DOSE = 3 CÁPSULAS',
        
        // Dermatites (Dermatitis)
        'dermatites – leve' => '1 DOSE = 1 CÁPSULA',
        'dermatites – moderado' => '1 DOSE = 2 CÁPSULAS',
        'dermatites – severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Equilíbrio do Colesterol (Cholesterol Balance)
        'equilíbrio do colesterol - leve' => '1 DOSE = 1 CÁPSULA',
        'equilíbrio do colesterol - moderado' => '1 DOSE = 2 CÁPSULAS',
        'equilíbrio do colesterol - grave' => '1 DOSE = 3 CÁPSULAS',
        
        // Funções Cognitivas (Cognitive Functions)
        'funções cognitivas – leve' => '1 DOSE = 1 CÁPSULA',
        'funções cognitivas – moderado' => '1 DOSE = 2 CÁPSULAS',
        'funções cognitivas – severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Hipotiroidismo (Hypothyroidism)
        'hipotiroidismo - leve' => '1 DOSE = 1 CÁPSULA',
        'hipotiroidismo - moderado' => '1 DOSE = 2 CÁPSULAS',
        'hipotiroidismo - severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Imunidade Fraca (Weak Immunity)
        'imunidade fraca - leve' => '1 DOSE = 1 CÁPSULA',
        'imunidade fraca - moderada' => '1 DOSE = 2 CÁPSULAS',
        'imunidade fraca - severa' => '1 DOSE = 3 CÁPSULAS',
        
        // Lipedema
        'lipedema - leve' => '1 DOSE = 1 CÁPSULA',
        'lipedema - moderado' => '1 DOSE = 2 CÁPSULAS',
        'lipedema - severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Má digestão (Poor Digestion)
        'má digestão – leve' => '1 DOSE = 1 CÁPSULA',
        'má digestão – moderado' => '1 DOSE = 2 CÁPSULAS',
        'má digestão – severo' => '1 DOSE = 3 CÁPSULAS',
        'má digestão 2 – leve' => '1 DOSE = 1 CÁPSULA',
        'má digestão 2 – moderado' => '1 DOSE = 2 CÁPSULAS',
        'má digestão 2 – severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Perda de cabelos (Hair Loss)
        'perda de cabelos - leve' => '1 DOSE = 1 CÁPSULA',
        'perda de cabelos - moderado' => '1 DOSE = 2 CÁPSULAS',
        'perda de cabelos - severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Perfil Glicêmico (Glycemic Profile)
        'perfil glicêmico - leve' => '1 DOSE = 1 CÁPSULA',
        'perfil glicêmico - moderado' => '1 DOSE = 2 CÁPSULAS',
        
        // Problemas Articulares (Joint Problems)
        'problemas articulares – leve' => '1 DOSE = 1 CÁPSULA',
        'problemas articulares – moderado' => '1 DOSE = 2 CÁPSULAS',
        'problemas articulares – severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Problemas Femininos (Women's Problems)
        'problemas femininos – leve' => '1 DOSE = 1 CÁPSULA',
        'problemas femininos – moderado' => '1 DOSE = 2 CÁPSULAS',
        'problemas femininos – grave' => '1 DOSE = 3 CÁPSULAS',
        
        // Problemas Intestinais (Intestinal Problems)
        'problemas intestinais – leve' => '1 DOSE = 1 CÁPSULA',
        'problemas intestinais – moderado' => '1 DOSE = 2 CÁPSULAS',
        'problemas intestinais – severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Problemas Masculinos (Men's Problems)
        'problemas masculinos – leve' => '1 DOSE = 1 CÁPSULA',
        'problemas masculinos – moderado' => '1 DOSE = 2 CÁPSULAS',
        'problemas masculinos – severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Problemas de Sono (Sleep Problems)
        'problemas de sono – leve' => '1 DOSE = 1 CÁPSULA',
        'problemas de sono – moderado' => '1 DOSE = 2 CÁPSULAS',
        'problemas de sono – severo' => '1 DOSE = 3 CÁPSULAS',
        
        // Saúde cardiovascular (Cardiovascular Health)
        'saúde cardiovascular - leve' => '1 DOSE = 1 CÁPSULA',
        'saúde cardiovascular - moderado' => '1 DOSE = 2 CÁPSULAS'
    ];

    public function setMapping(string $itemName, string $dosageText): void
    {
        $this->doseByItem[$this->normalize($itemName)] = $dosageText;
    }

    /**
     * Get the dosage text for an item; returns empty string when not mapped.
     */
    public function getDosageText(string $itemName): string
    {
        $mapped = $this->doseByItem[$this->normalize($itemName)] ?? null;
        if (!is_string($mapped)) {
            return '';
        }
        return $mapped;
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
            '\u00e7', '\u00e1', '\u00e9', '\u00ed', '\u00f3', '\u00fa',
            '\u00c7', '\u00c1', '\u00c9', '\u00cd', '\u00d3', '\u00da'
        ], [
            'ç', 'á', 'é', 'í', 'ó', 'ú',
            'Ç', 'Á', 'É', 'Í', 'Ó', 'Ú'
        ], $normalized);
        
        // Convert backend format to our mapping format
        $normalized = str_replace([
            'pouch longevity men', 'pouch longevity women', 'pouch longevity woman',
            'cápsula longevity men', 'cápsula longevity women', 'cápsula longevity woman',
            'pouch vital men', 'pouch vital women', 'pouch vital woman', 
            'cápsula vital men', 'cápsula vital women', 'cápsula vital woman'
        ], [
            'longevity man', 'longevity woman', 'longevity woman',
            'longevity man', 'longevity woman', 'longevity woman',
            'vital man', 'vital woman', 'vital woman',
            'vital man', 'vital woman', 'vital woman'
        ], $normalized);
        
        // Handle Vital Woman Special Day Essencial
        $normalized = str_replace([
            'vital woman special day essencial'
        ], [
            'vital woman special day essencial'
        ], $normalized);
        
        // Handle Puríssima Gestante products
        $normalized = str_replace([
            'puríssima gestante dia - pouch', 'puríssima gestante noite - pouch',
            'puríssima gestante dia essencial cápsula', 'puríssima gestante dia avançada cápsula', 'puríssima gestante dia premium cápsula',
            'puríssima gestante noite essencial cápsula', 'puríssima gestante noite avançada cápsula', 'puríssima gestante noite premium cápsula'
        ], [
            'puríssima gestante day - pouch', 'puríssima gestante night - pouch',
            'puríssima gestante day essencial - caps', 'puríssima gestante day avançada - caps', 'puríssima gestante day premium - caps',
            'puríssima gestante night essencial - caps', 'puríssima gestante night avançada - caps', 'puríssima gestante night premium - caps'
        ], $normalized);
        
        // Convert Portuguese terms
        $normalized = str_replace([
            'dia', 'noite', 'essencial', 'premium'
        ], [
            'day', 'night', 'essencial', 'premium'
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
        $normalized = str_replace([
            ' – leve', ' – moderado', ' – moderada', ' – severo', ' – severa', ' – grave',
            ' - leve', ' - moderado', ' - moderada', ' - severo', ' - severa', ' - grave'
        ], [
            ' – leve', ' – moderado', ' – moderada', ' – severo', ' – severa', ' – grave',
            ' – leve', ' – moderado', ' – moderada', ' – severo', ' – severa', ' – grave'
        ], $normalized);
        
        // Collapse multiple spaces and normalize separators
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        error_log('[DoseMapper] Normalized item name: "' . $normalized . '"');
        return $normalized;
    }
}


