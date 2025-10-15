const fs = require('fs');
const path = require('path');

/**
 * Apply mappings to item names using regex patterns
 */
function substituir(texto) {
    // Garante string para evitar problemas com null/undefined
    texto = String(texto || '');

    // Normalização completa: primeira regra que casar define o rótulo canônico
    const rules = [
        // Problemas Masculinos -> Saúde do Homem
        { pattern: /problemas\s+masculinos/i, label: 'Saúde do Homem' },
        // Problemas de Sono -> Sono Regenerador
        { pattern: /problemas\s+de\s+sono/i, label: 'Sono Regenerador' },
        // Problemas Femininos -> Saúde da Mulher
        { pattern: /problemas\s+femininos/i, label: 'Saúde da Mulher' },
        // Problemas Articulares -> Alívio da Dor
        { pattern: /problemas\s+articulares/i, label: 'Alívio da Dor' },
        // Problemas Intestinais -> Saúde Intestinal
        { pattern: /problemas\s+intestinais/i, label: 'Saúde Intestinal' },
        // Ansiedade -> Saúde Mental e Emocional
        { pattern: /ansiedade/i, label: 'Saúde Mental e Emocional' },
        // Dermatites -> Dermatite
        { pattern: /dermatites/i, label: 'Dermatite' },
        // Funções Cognitivas -> Função Cognitiva
        { pattern: /funções\s+cognitivas/i, label: 'Função Cognitiva' },
        // Hipotiroidismo -> Hipotireoidismo
        { pattern: /hipotiroidismo/i, label: 'Hipotireoidismo' },
        // Imunidade Fraca -> Imunidade
        { pattern: /imunidade\s+fraca/i, label: 'Imunidade' },
        // Má digestão -> Função Digestiva
        { pattern: /má\s+digestão/i, label: 'Função Digestiva' },
        // Perda de cabelos -> Queda de Cabelo
        { pattern: /perda\s+de\s+cabelos/i, label: 'Queda de Cabelo' },
        // Perfil Glicêmico -> Perfil Glicêmico (keep as is)
        { pattern: /perfil\s+glicêmico/i, label: 'Perfil Glicêmico' },
        // Saúde cardiovascular -> Saúde Cardiovascular
        { pattern: /saúde\s+cardiovascular/i, label: 'Saúde Cardiovascular' },
        // Equilíbrio do Colesterol -> Equilíbrio do Colesterol (keep as is)
        { pattern: /equilíbrio\s+do\s+colesterol/i, label: 'Equilíbrio do Colesterol' },
        // Lipedema -> Lipedema (keep as is)
        { pattern: /lipedema/i, label: 'Lipedema' },
        // My Baby -> Saúde do Bebê
        { pattern: /my\s+baby/i, label: 'Saúde do Bebê' },
        // My Kids -> Saúde Infantil
        { pattern: /my\s+kids/i, label: 'Saúde Infantil' },
        // Puríssima Gestante -> Saúde da Gestante
        { pattern: /puríssima\s+gestante/i, label: 'Saúde da Gestante' },
        // Cápsula Longevity -> Longevity
        { pattern: /cápsula\s+longevity/i, label: 'Longevity' },
        // Pouch Longevity -> Longevity
        { pattern: /pouch\s+longevity/i, label: 'Longevity' },
        // Cápsula Vital -> Vital
        { pattern: /cápsula\s+vital/i, label: 'Vital' },
        // Pouch Vital -> Vital
        { pattern: /pouch\s+vital/i, label: 'Vital' },
    ];

    for (const rule of rules) {
        if (rule.pattern.test(texto)) {
            return rule.label;
        }
    }

    // Se nenhuma regra casar, retorna o texto como veio
    return texto;
}

/**
 * Apply mappings to item names (wrapper function for compatibility)
 */
function applyItemNameMappings(itemName) {
    return substituir(itemName);
}

/**
 * Apply mappings to item names from a text file
 */
function applyMappingsToFile(inputFile, outputFile) {
    if (!fs.existsSync(inputFile)) {
        console.error(`Error: Input file '${inputFile}' not found.`);
        return false;
    }
    
    const content = fs.readFileSync(inputFile, 'utf8');
    const lines = content.split('\n').filter(line => line.trim() !== '');
    const mappedLines = [];
    
    console.log(`Processing ${lines.length} items...`);
    
    lines.forEach((line, lineNumber) => {
        // The line is just the item name directly
        const originalName = line.trim();
        const mappedName = applyItemNameMappings(originalName);
        
        // Format the output line with line number
        mappedLines.push(`${String(lineNumber + 1).padStart(3, ' ')}|${mappedName}`);
        
        // Show progress for items that were actually changed
        if (originalName !== mappedName) {
            console.log(`Line ${lineNumber + 1}: '${originalName}' -> '${mappedName}'`);
        }
    });
    
    // Write the mapped results to the output file
    fs.writeFileSync(outputFile, mappedLines.join('\n'));
    
    console.log(`\nMapped items saved to: ${outputFile}`);
    console.log(`Total items processed: ${lines.length}`);
    
    return true;
}

// Main execution
const args = process.argv.slice(2);
if (args.length < 1) {
    console.log('Usage: node apply-item-mappings.js <input-file> [output-file]');
    console.log('Example: node apply-item-mappings.js unique-item-names_2025-10-15T21-16-29.txt');
    process.exit(1);
}

const inputFile = args[0];
const outputFile = args.length > 1 ? args[1] : inputFile.replace('.txt', '-mapped.txt');

applyMappingsToFile(inputFile, outputFile);
