<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$item_id = intval($data['id'] ?? 0);

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID do item inválido.']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM cotacao_item WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Item não encontrado.']);
        }
    } else {
        throw new Exception("Erro ao excluir: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>