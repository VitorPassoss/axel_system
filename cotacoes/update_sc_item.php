<?php
// update_sc_item.php

include '../backend/auth.php';
include '../backend/dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validação dos dados recebidos
$item_id = intval($data['id'] ?? 0);
$quantidade = floatval($data['quantidade'] ?? 0);
$und_medida = trim($data['und_medida'] ?? '');
$grau = trim($data['grau'] ?? '');

if ($item_id <= 0 || $quantidade <= 0 || empty($und_medida) || empty($grau)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos ou inválidos.']);
    exit;
}

try {
    // Prepara a query de UPDATE
    // IMPORTANTE: Verifique o nome da sua tabela de itens!
    $sql = "UPDATE sc_item SET quantidade = ?, und_medida = ?, grau = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Erro ao preparar a query: " . $conn->error);
    }
    
    // 'd' para double (quantidade), 's' para string, 'i' para integer (id)
    $stmt->bind_param("dssi", $quantidade, $und_medida, $grau, $item_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            // Nenhum erro, mas nenhuma linha foi alterada (talvez os dados já fossem os mesmos)
            echo json_encode(['success' => true, 'message' => 'Nenhuma alteração detectada.']);
        }
    } else {
        throw new Exception("Erro ao executar a atualização: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>