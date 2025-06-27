<?php
include '../backend/auth.php';
include '../backend/dbconn.php'; // $conn

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// Pega os dados do corpo da requisição JSON
$data = json_decode(file_get_contents('php://input'), true);

$id = intval($data['id'] ?? 0);
$aprovador = trim($data['aprovador'] ?? '');
$senha = $data['senha'] ?? '';

// Valida campos obrigatórios
if ($id <= 0 || $aprovador === '' || $senha === '') {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
    exit;
}

try {
    // Conecta com PDO para validar a senha
    include '../backend/db.php'; // $
    // Busca a senha mestra mais recente
    $stmt = $pdo->query("SELECT senha_hash FROM senha_mestra ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($senha, $row['senha_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Senha incorreta.']);
        exit;
    }

    // Atualiza a cotação com status aprovado
    $query = "
        UPDATE cotacao 
        SET status = 'cancelado',
            aprovado_por = ?,
            dt_aprovado = NOW()
        WHERE id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $aprovador, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao aprovar cotação.']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: ' . $e->getMessage()]);
}
