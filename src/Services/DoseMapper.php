<?php

namespace App\Services;

class DoseMapper
{
    /**
     * Map of normalized item name => dosage text
     * Keep keys lowercase and normalized; values are full dosage strings.
     */
    private array $doseByItem = [
        // Vital Man Day Essencial
        'vital man day essencial - pouch' => '1 DOSE = 1 SCOOP',
        'vital man day essencial - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Man Night Essencial
        'vital man night essencial - pouch' => '1 DOSE = 1 SCOOP',
        'vital man night essencial - caps' => '1 DOSE = 1 CÁPSULAS',
        
        // Vital Man Day Avançado
        'vital man day avançado - pouch' => '1 DOSE = 1 SCOOP',
        'vital man day avançado - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Man Night Avançado
        'vital man night avançado - pouch' => '1 DOSE = 1 SCOOP',
        'vital man night avançado - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Man Day Premium
        'vital man day premium - pouch' => '1 DOSE = 1 SCOOP',
        'vital man day premium - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Man Night Premium
        'vital man night premium - pouch' => '1 DOSE = 1 SCOOP',
        'vital man night premium - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Woman Day Essencial
        'vital woman day essencial - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman day essencial - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Woman Night Essencial
        'vital woman night essencial - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman night essencial - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Woman Day Avançada
        'vital woman day avançada - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman day avançada - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Woman Night Avançada
        'vital woman night avançada - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman night avançada - caps' => '1 DOSE = 2 CÁPSULAS',
        
        // Vital Woman Day Premium
        'vital woman day premium - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman day premium - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Vital Woman Night Premium
        'vital woman night premium - pouch' => '1 DOSE = 1 SCOOP',
        'vital woman night premium - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Day Essencial
        'longevity man day essencial - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man day essencial - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Night Essencial
        'longevity man night essencial - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man night essencial - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Day Avançado
        'longevity man day avançado - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man day avançado - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Night Avançado
        'longevity man night avançado - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man night avançado - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Day Premium
        'longevity man day premium - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man day premium - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Man Night Premium
        'longevity man night premium - pouch' => '1 DOSE = 1 SCOOP',
        'longevity man night premium - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Woman Day Essencial
        'longevity woman day essencial - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman day essencial - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Woman Night Essencial
        'longevity woman night essencial - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman night essencial - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Woman Day Avançada
        'longevity woman day avançada - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman day avançada - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Woman Night Avançada
        'longevity woman night avançada - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman night avançada - caps' => '1 DOSE = 3 CÁPSULAS',
        
        // Longevity Woman Day Premium
        'longevity woman day premium - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman day premium - caps' => '1 DOSE = 4 CÁPSULAS',
        
        // Longevity Woman Night Premium
        'longevity woman night premium - pouch' => '1 DOSE = 1 SCOOP',
        'longevity woman night premium - caps' => '1 DOSE = 4 CÁPSULAS',
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
        // Convert to our mapping format: "longevity man night essencial - pouch"
        
        // Remove flavor/scent part after the last dash
        $parts = explode(' - ', $normalized);
        if (count($parts) > 1) {
            $baseName = trim($parts[0]);
            $normalized = $baseName;
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
        
        // Convert Portuguese terms
        $normalized = str_replace([
            'dia', 'noite', 'essencial', 'avançado', 'avançada', 'premium'
        ], [
            'day', 'night', 'essencial', 'avançado', 'avançada', 'premium'
        ], $normalized);
        
        // Add pouch/caps suffix based on original name
        if (strpos($name, 'Pouch') !== false) {
            $normalized .= ' - pouch';
            error_log('[DoseMapper] Detected Pouch type for "' . $name . '" -> suffix " - pouch"');
        } elseif (strpos($name, 'Cápsula') !== false || strpos($name, 'Caps') !== false) {
            $normalized .= ' - caps';
            error_log('[DoseMapper] Detected Caps type for "' . $name . '" -> suffix " - caps"');
        }
        
        // Collapse multiple spaces and normalize separators
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        error_log('[DoseMapper] Normalized item name: "' . $normalized . '"');
        return $normalized;
    }
}


