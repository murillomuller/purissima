<?php

/**
 * Item Name Mappings using Regex Patterns
 * Maps internal item names to user-friendly display names using flexible regex patterns
 */

/**
 * Apply mappings to item names using regex patterns
 * 
 * @param string $itemName The original item name
 * @return string The mapped item name
 */
function substituir($texto) {
    // Garante string para evitar deprecated ao passar null para preg_replace
    $texto = (string)($texto ?? '');

    // Normalização completa: primeira regra que casar define o rótulo canônico
    $rules = [
        // Problemas Masculinos -> Saúde do Homem
        '/problemas\s+masculinos/i' => 'Saúde do Homem',
        // Problemas de Sono -> Sono Regenerador
        '/problemas\s+de\s+sono/i' => 'Sono Regenerador',
        // Problemas Femininos -> Saúde da Mulher
        '/problemas\s+femininos/i' => 'Saúde da Mulher',
        // Problemas Articulares -> Alívio da Dor
        '/problemas\s+articulares/i' => 'Alívio da Dor',
        // Problemas Intestinais -> Saúde Intestinal
        '/problemas\s+intestinais/i' => 'Saúde Intestinal',
        // Ansiedade -> Saúde Mental e Emocional
        '/ansiedade/i' => 'Saúde Mental e Emocional',
        // Dermatites -> Dermatite
        '/dermatites/i' => 'Dermatite',
        // Funções Cognitivas -> Função Cognitiva
        '/funções\s+cognitivas/i' => 'Função Cognitiva',
        // Hipotiroidismo -> Hipotireoidismo
        '/hipotiroidismo/i' => 'Hipotireoidismo',
        // Imunidade Fraca -> Imunidade
        '/imunidade\s+fraca/i' => 'Imunidade',
        // Má digestão -> Função Digestiva
        '/má\s+digestão/i' => 'Função Digestiva',
        // Perda de cabelos -> Queda de Cabelo
        '/perda\s+de\s+cabelos/i' => 'Queda de Cabelo',
        // Perfil Glicêmico -> Perfil Glicêmico (keep as is)
        '/perfil\s+glicêmico/i' => 'Perfil Glicêmico',
        // Saúde cardiovascular -> Saúde Cardiovascular
        '/saúde\s+cardiovascular/i' => 'Saúde Cardiovascular',
        // Equilíbrio do Colesterol -> Equilíbrio do Colesterol (keep as is)
        '/equilíbrio\s+do\s+colesterol/i' => 'Equilíbrio do Colesterol',
        // Lipedema -> Lipedema (keep as is)
        '/lipedema/i' => 'Lipedema',
        // My Baby -> Saúde do Bebê
        '/my\s+baby/i' => 'Saúde do Bebê',
        // My Kids -> Saúde Infantil
        '/my\s+kids/i' => 'Saúde Infantil',
        // Puríssima Gestante -> Saúde da Gestante
        '/puríssima\s+gestante/i' => 'Saúde da Gestante',
        // Cápsula Longevity -> Longevity
        '/cápsula\s+longevity/i' => 'Longevity',
        // Pouch Longevity -> Longevity
        '/pouch\s+longevity/i' => 'Longevity',
        // Cápsula Vital -> Vital
        '/cápsula\s+vital/i' => 'Vital',
        // Pouch Vital -> Vital
        '/pouch\s+vital/i' => 'Vital',
    ];

    foreach ($rules as $pattern => $label) {
        if (preg_match($pattern, $texto)) {
            return $label;
        }
    }

    // Se nenhuma regra casar, retorna o texto como veio
    return $texto;
}

/**
 * Apply mappings to item names (wrapper function for compatibility)
 * 
 * @param string $itemName The original item name
 * @return string The mapped item name
 */
function applyItemNameMappings($itemName) {
    return substituir($itemName);
}

/**
 * Get all unique base names from the item list
 * 
 * @param array $items Array of item names
 * @return array Array of unique base names
 */
function getUniqueBaseNames($items) {
    $baseNames = [];
    
    foreach ($items as $item) {
        // Remove severity levels, flavors, and parenthetical info
        $base = preg_replace('/\s*–?\s*(Leve|Moderado|Moderada|Severo|Severa|Grave)\s*/', '', $item);
        $base = preg_replace('/\s*\([^)]*\)/', '', $base);
        $base = preg_replace('/\s*-\s*(Baunilha do Tahiti|Frutas Vermelhas|Limonada Suíça|Mousse de maracujá)$/', '', $base);
        $base = trim($base);
        
        if (!in_array($base, $baseNames)) {
            $baseNames[] = $base;
        }
    }
    
    return $baseNames;
}
