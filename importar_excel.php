<?php
// importar_excel.php
require 'vendor/autoload.php';
require_once 'config/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

ini_set('memory_limit', '1024M');
set_time_limit(0);

$arquivo = 'Produtos.xlsx'; // Certifique-se que o nome do arquivo está correto

try {
    echo "Iniciando importação de Uso e Consumo...<br>";
    
    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($arquivo);
    $sheet = $spreadsheet->getActiveSheet();

    $conn->begin_transaction();

    // Query ajustada para a nova estrutura de 661 itens
    $stmt = $conn->prepare("INSERT INTO produtos 
        (codigo_referencia, descricao, unidade_medida, tipo_produto, categoria) 
        VALUES (?, ?, ?, ?, ?)");

    $count = 0;
    foreach ($sheet->getRowIterator() as $index => $row) {
        if ($index === 1) continue; // Pula o cabeçalho

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        
        $data = [];
        foreach ($cellIterator as $cell) {
            $data[] = $cell->getValue();
        }

        // NOVO MAPEAMENTO baseado na sua imagem
        // Coluna A (0): Produto (Código)
        // Coluna B (1): Descrição do produto
        // Coluna C (2): UN (Unidade)
        // Coluna D (3): Tipo Produto Produção (Ex: USO E CONSUMO)

        $ref  = trim($data[0] ?? '');  // Código do produto
        $desc = trim($data[1] ?? '');  // Descrição
        $un   = trim($data[2] ?? 'UN'); // Unidade
        $tipo = trim($data[3] ?? 'USO E CONSUMO'); // Tipo
        
        // Como a planilha não tem a coluna de categoria, vamos definir uma padrão 
        // ou você pode adicionar a coluna E no seu Excel e usar $data[4]
        $cat  = 'OPERACIONAL'; 

        if (!empty($ref) && !empty($desc)) {
            $stmt->bind_param("sssss", $ref, $desc, $un, $tipo, $cat);
            $stmt->execute();
            $count++;
        }

        if ($count % 100 === 0) {
            $conn->commit();
            $conn->begin_transaction();
            echo "Processados: $count itens...<br>";
            flush();
        }
    }

    $conn->commit();
    echo "✅ Sucesso! $count itens de Uso e Consumo importados.";

} catch (Exception $e) {
    if($conn->connect_error) die("Erro de conexão");
    $conn->rollback();
    echo "❌ Erro: " . $e->getMessage();
}