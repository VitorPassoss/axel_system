<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cotacao_id = intval($data['cotacao_id'] ?? 0);
$itens = $data['itens'] ?? [];

if ($cotacao_id <= 0 || empty($itens)) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

$conn->begin_transaction();
try {
    // ATENÇÃO: A coluna `descricao_tecnica` não está sendo inserida.
    // Se ela for NOT NULL e não tiver um valor padrão, a query vai falhar.
    $stmt = $conn->prepare(
        "INSERT INTO cotacao_item (cotacao_id, insumo_id, fornecedor_id, und_medida, quantidade, valor_item, desconto, valor_final) 
         VALUES (?, ?, ?, (SELECT und_medida FROM sc_item WHERE insumo_id = ? LIMIT 1), ?, ?, ?, ?)"
    );

    foreach ($itens as $item) {
        $quantidade = floatval($item['quantidade']);
        $valor_item = floatval($item['valor_item']);
        $desconto = floatval($item['desconto']);
        
        // Este cálculo parece correto: (quantidade * valor) - desconto
        $valor_final = ($quantidade * $valor_item) - $desconto;

        $insumo_id = intval($item['insumo_id']);
        $fornecedor_id = intval($item['fornecedor_id']);

        // CORREÇÃO AQUI: A string de tipos agora tem 8 caracteres ("iiiidddd")
        // para corresponder às 8 variáveis sendo passadas.
        $stmt->bind_param("iiiidddd", $cotacao_id, $insumo_id, $fornecedor_id, $insumo_id, $quantidade, $valor_item, $desconto, $valor_final);
        
        if (!$stmt->execute()) {
            // Lança uma exceção mais detalhada para facilitar a depuração
            throw new Exception("Erro ao inserir o item (Insumo ID: $insumo_id): " . $stmt->error);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    // Retorna a mensagem de erro específica da exceção
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>