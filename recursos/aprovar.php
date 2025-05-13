<?php
require_once '../backend/auth.php';
require_once '../backend/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id = intval($data['id'] ?? 0);
$aprovador = trim($data['aprovador'] ?? '');

if ($id <= 0 || $aprovador === '') {
    echo json_encode(['success' => false, 'error' => 'ID ou aprovador inválido.']);
    exit;
}

try {
    $sql = "
        UPDATE solicitacao_compras 
        SET status = 'APROVADO', 
            aprovado_por = :aprovador, 
            aprovado_em = NOW() 
        WHERE id = :id
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':aprovador', $aprovador);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao executar a query.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: ' . $e->getMessage()]);
}
