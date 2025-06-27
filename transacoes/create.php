<?php
session_start(); // necessário para acessar $_SESSION

include '../backend/dbconn.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na conexão com o banco.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo = 'entrada'; // tipo fixo
    $status = 'paga'; // valor padrão de status
    $valor = floatval(str_replace(',', '.', $_POST['valor'] ?? 0));
    $banco_id = intval($_POST['banco_id'] ?? 0);
    $categoria_id = intval($_POST['categoria_id'] ?? 1); // padrão ou passado via POST
    $empresa_id = intval($_SESSION['empresa_id'] ?? 0);

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

    // Tipos: s = string, d = double/float
    $stmt->bind_param("sssdddd", $descricao, $tipo, $status, $valor, $banco_id, $categoria_id, $empresa_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao salvar no banco.']);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
