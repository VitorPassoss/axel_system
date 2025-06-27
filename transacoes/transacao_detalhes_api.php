<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

header('Content-Type: application/json');

// Valida o ID da transação
$transacao_id = intval($_GET['transacao_id'] ?? 0);
if ($transacao_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de transação inválido.']);
    exit;
}

$response = [
    'success' => false
];

try {
    // 1. Busca os detalhes principais da transação.
    // O "t.*" garante que todos os campos da tabela 'transacoes',
    // incluindo is_reembolso, motivo_reembolso e chave_pix_reembolso, sejam buscados.
    $query = "
        SELECT
            t.*,
            f.nome_fantasia AS nome_fornecedor,
            c.nome AS nome_categoria,
            b.nome AS nome_banco
        FROM transacoes t
        LEFT JOIN fornecedores f ON t.fornecedor_id = f.id
        LEFT JOIN categorias c ON t.categoria_id = c.id
        LEFT JOIN bancos b ON t.banco_id = b.id
        WHERE t.id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $transacao_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($transacao = $result->fetch_assoc()) {
        $response['success'] = true;
        // O objeto 'transacao' conterá todos os dados necessários
        $response['transacao'] = $transacao;

        // 2. Busca os documentos vinculados
        $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'transacoes' AND ref_id = ?");
        $stmt_docs->bind_param("i", $transacao_id);
        $stmt_docs->execute();
        $response['documentos'] = $stmt_docs->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_docs->close();

        // 3. Se houver uma cotação, busca os detalhes dela
        if (!empty($transacao['cotacao_id'])) {
            $cotacao_id = $transacao['cotacao_id'];
            
            // Detalhes
            $stmt_cot = $conn->prepare("SELECT * FROM cotacao WHERE id = ?");
            $stmt_cot->bind_param("i", $cotacao_id);
            $stmt_cot->execute();
            $response['cotacao']['detalhes'] = $stmt_cot->get_result()->fetch_assoc();
            $stmt_cot->close();

            // Itens
            $stmt_items = $conn->prepare("SELECT * FROM cotacao_item WHERE cotacao_id = ?");
            $stmt_items->bind_param("i", $cotacao_id);
            $stmt_items->execute();
            $response['cotacao']['itens'] = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_items->close();
        }

    } else {
        $response['error'] = 'Transação não encontrada.';
    }

    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500); // Adiciona um código de erro HTTP
    $response['error'] = 'Erro no servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);

?>