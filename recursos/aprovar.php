<?php
require_once '../backend/auth.php';
require_once '../backend/db.php'; // $pdo

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id = intval($data['id'] ?? 0);
$aprovador = trim($data['aprovador'] ?? '');
$senha = $data['senha'] ?? '';

if ($id <= 0 || $aprovador === '' || $senha === '') {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
    exit;
}

try {
    // Buscar senha mestra (hash) no banco
    $stmt = $pdo->prepare("SELECT senha_hash FROM senha_mestra ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($senha, $row['senha_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Senha incorreta.']);
        exit;
    }

    // Senha correta, aprova a solicitação
    $sql = "
        UPDATE solicitacao_compras 
        SET status = 'APROVADO', aprovado_por = :aprovador, aprovado_em = NOW() 
        WHERE id = :id
    ";
    $stmt = $pdo->prepare($sql);
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
