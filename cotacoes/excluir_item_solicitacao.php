<?php
// excluir_item_solicitacao.php

// Inclua seus arquivos de autenticação e conexão
include '../backend/auth.php'; 
include '../backend/dbconn.php'; // $conn

header('Content-Type: application/json');

// Garante que o método da requisição seja POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido.']);
    exit;
}

// Pega os dados enviados via JSON
$data = json_decode(file_get_contents('php://input'), true);

$item_id = intval($data['id'] ?? 0);

// Validação simples
if ($item_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID do item inválido.']);
    exit;
}

try {

    $sql = "DELETE FROM sc_item WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar a query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        // Verifica se alguma linha foi realmente afetada/excluída
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            // O comando executou, mas nenhuma linha foi deletada (talvez o ID não existisse)
            echo json_encode(['success' => false, 'error' => 'Nenhum item encontrado com este ID.']);
        }
    } else {
        throw new Exception("Erro ao executar a exclusão: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Resposta em caso de erro no bloco try
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}