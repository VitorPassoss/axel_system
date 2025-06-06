<?php
require_once '../backend/auth.php';
require_once '../backend/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception('ID do contrato não fornecido.');
    }

    $empresa_id = $usuario['empresa_id'];

    // Prepara a consulta DELETE usando PDO
    $stmt = $pdo->prepare("DELETE FROM projetos WHERE id = :id AND empresa_id = :empresa_id");

    // Bind dos parâmetros
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);

    // Executa a consulta
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
