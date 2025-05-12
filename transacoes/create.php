<?php
session_start(); // necessário para acessar $_SESSION

$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na conexão com o banco.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo = 'entrada';
    $status = $_POST['status'] ?? '';
    $banco_id = $_POST['banco_id'] ?? null;
    $categoria_id = $_POST['categoria_id'] ?? null;
    $empresa_id = $_SESSION['empresa_id'] ?? null;

    // Garantir que o valor esteja no formato correto (substitui a vírgula por ponto e converte para float)
    $valor = $_POST['valor'] ?? 0;
   

    // Validação
    if (
        empty($descricao) || empty($tipo) || empty($status) || !$valor ||
        empty($banco_id) || empty($categoria_id) || empty($empresa_id)
    ) {
        http_response_code(400);
        echo json_encode(['error' => 'Preencha todos os campos obrigatórios.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO transacoes (descricao, tipo_transacao, status, valor, banco_id, categoria_id, empresa_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao preparar a query.']);
        exit;
    }

    $stmt->bind_param("sssddii", $descricao, $tipo, $status, $valor, $banco_id, $categoria_id, $empresa_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao salvar no banco.']);
    }

    exit;
}
