<?php

/**
 * Item Name Mappings using Regex Patterns
 * Maps internal item names to user-friendly display names using flexible regex patterns
 */

/**
 * Apply mappings to item names using regex patterns
 * 
 * @param string $itemName The original item name
 * @return array Array with 'name' and 'time' keys
 */
function substituir($texto)
{
    // Garante string para evitar deprecated ao passar null para preg_replace
    $texto = (string)($texto ?? '');

    // Normalização completa: primeira regra que casar define o rótulo canônico
    $rules = [
        // ===== HEALTH CATEGORIES BASED ON DOSE INFORMATION =====

        // EQUILÍBRIO EMOCIONAL (Emotional Balance)
        '/ansiedade/i' => ['name' => 'Equilíbrio Emocional', 'time' => 'DIA'],
        '/equilíbrio\s+emocional/i' => ['name' => 'Equilíbrio Emocional', 'time' => 'DIA'],

        // IMUNIDADE EFICIENTE (Efficient Immunity)
        '/imunidade\s+fraca/i' => ['name' => 'Imunidade Eficiente', 'time' => 'DIA'],
        '/imunidade\s+eficiente/i' => ['name' => 'Imunidade Eficiente', 'time' => 'DIA'],

        // SONO REGENERATIVO (Regenerative Sleep)
        '/problemas\s+de\s+sono/i' => ['name' => 'Sono Regenerativo', 'time' => 'NOITE'],
        '/sono\s+regenerativo/i' => ['name' => 'Sono Regenerativo', 'time' => 'NOITE'],

        // SAÚDE ARTICULAR (Joint Health)
        '/problemas\s+articulares/i' => ['name' => 'Saúde Articular', 'time' => 'DIA'],
        '/saúde\s+articular/i' => ['name' => 'Saúde Articular', 'time' => 'DIA'],

        // SAÚDE DA MULHER (Women's Health)
        '/problemas\s+femininos/i' => ['name' => 'Saúde da Mulher', 'time' => 'DIA'],

        // SAÚDE DO HOMEM (Men's Health)
        '/problemas\s+masculinos/i' => ['name' => 'Saúde do Homem', 'time' => 'DIA'],

        // ENZIMAS DIGESTIVAS (Digestive Enzymes)
        '/má\s+digestão/i' => ['name' => 'Enzimas Digestivas', 'time' => 'DIA'],
        '/enzimas\s+digestivas/i' => ['name' => 'Enzimas Digestivas', 'time' => 'DIA'],

        // EQUILÍBRIO INTESTINAL (Intestinal Balance)
        '/problemas\s+intestinais/i' => ['name' => 'Equilíbrio Intestinal', 'time' => 'NOITE'],
        '/equilíbrio\s+intestinal/i' => ['name' => 'Equilíbrio Intestinal', 'time' => 'NOITE'],

        // AUXÍLIO PARA LIPEDEMA (Lipedema Support)
        '/lipedema/i' => ['name' => 'Auxílio para Lipedema', 'time' => 'DIA'],
        '/auxílio\s+para\s+lipedema/i' => ['name' => 'Auxílio para Lipedema', 'time' => 'DIA'],

        // CONTROLE DA DERMATITE (Dermatitis Control)
        '/dermatites/i' => ['name' => 'Controle da Dermatite', 'time' => 'DIA'],
        '/controle\s+da\s+dermatite/i' => ['name' => 'Controle da Dermatite', 'time' => 'DIA'],

        // MEMÓRIA E COGNIÇÃO (Memory and Cognition)
        '/funções\s+cognitivas/i' => ['name' => 'Memória e Cognição', 'time' => 'DIA'],
        '/memória\s+e\s+cognição/i' => ['name' => 'Memória e Cognição', 'time' => 'DIA'],

        // CONTROLE GLICÊMICO (Glycemic Control)
        '/perfil\s+glicêmico/i' => ['name' => 'Controle Glicêmico', 'time' => 'DIA'],
        '/controle\s+glicêmico/i' => ['name' => 'Controle Glicêmico', 'time' => 'DIA'],

        // SAÚDE CARDIOVASCULAR (Cardiovascular Health)
        '/saúde\s+cardiovascular/i' => ['name' => 'Saúde Cardiovascular', 'time' => 'DIA'],

        // EQUILÍBRIO DO COLESTEROL (Cholesterol Balance)
        '/equilíbrio\s+do\s+colesterol/i' => ['name' => 'Equilíbrio do Colesterol', 'time' => 'DIA'],

        // ANTIQUEDA CAPILAR (Anti-Hair Loss)
        '/perda\s+de\s+cabelos/i' => ['name' => 'Antiqueda Capilar', 'time' => 'DIA'],
        '/antiqueda\s+capilar/i' => ['name' => 'Antiqueda Capilar', 'time' => 'DIA'],

        // AUXÍLIO AO HIPOTIREOIDISMO (Hypothyroidism Support)
        '/hipotiroidismo/i' => ['name' => 'Auxílio ao Hipotireoidismo', 'time' => 'DIA'],
        '/auxílio\s+ao\s+hipotireoidismo/i' => ['name' => 'Auxílio ao Hipotireoidismo', 'time' => 'DIA'],

        // ===== SPECIAL PRODUCTS =====
        // My Baby -> Saúde do Bebê
        '/my\s+baby/i' => ['name' => 'Saúde do Bebê', 'time' => 'DIA'],
        // My Kids -> Saúde Infantil
        '/my\s+kids/i' => ['name' => 'Saúde Infantil', 'time' => 'DIA'],
        // Puríssima Gestante -> Saúde da Gestante
        '/puríssima\s+gestante/i' => ['name' => 'Saúde da Gestante', 'time' => 'DIA'],
        // Cápsula Longevity -> Longevity
        '/cápsula\s+longevity/i' => ['name' => 'Longevity', 'time' => 'DIA'],
        // Pouch Longevity -> Longevity
        '/pouch\s+longevity/i' => ['name' => 'Longevity', 'time' => 'DIA'],
        // Cápsula Vital -> Vital
        '/cápsula\s+vital/i' => ['name' => 'Vital', 'time' => 'DIA'],
        // Pouch Vital -> Vital
        '/pouch\s+vital/i' => ['name' => 'Vital', 'time' => 'DIA'],
    ];

    foreach ($rules as $pattern => $mapping) {
        if (preg_match($pattern, $texto)) {
            return $mapping;
        }
    }

    // Se nenhuma regra casar, retorna o texto como veio com DIA como padrão
    return ['name' => $texto, 'time' => 'DIA'];
}

/**
 * Apply mappings to item names (wrapper function for compatibility)
 * 
 * @param string $itemName The original item name
 * @return string The mapped item name
 */
function applyItemNameMappings($itemName)
{
    $result = substituir($itemName);
    return is_array($result) ? $result['name'] : $result;
}

/**
 * Get item name with DIA/NOITE information
 * 
 * @param string $itemName The original item name
 * @return array Array with 'name' and 'time' keys
 */
function getItemNameWithTime($itemName)
{
    return substituir($itemName);
}

/**
 * Get just the mapped name (for backward compatibility)
 * 
 * @param string $itemName The original item name
 * @return string The mapped item name
 */
function getMappedName($itemName)
{
    $result = substituir($itemName);
    return is_array($result) ? $result['name'] : $result;
}

/**
 * Get all unique base names from the item list
 * 
 * @param array $items Array of item names
 * @return array Array of unique base names
 */
function getUniqueBaseNames($items)
{
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
