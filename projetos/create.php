<?php
require_once '../backend/auth.php';
require_once '../backend/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

try {
    // Recebe os dados do formulário
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $valor = $_POST['valor'] ?? null;
    $data_inicio = $_POST['data_inicio'] ?? '';
    $data_fim = $_POST['data_fim'] ?? '';
    $responsavel = $_POST['responsavel'] ?? '';
    $cliente_nome = $_POST['cliente_nome'] ?? '';
    $status = $_POST['status'] ?? null;
    $empresa_id = $usuario['empresa_id'];

    // Prepara a query usando o objeto PDO $pdo
    $stmt = $pdo->prepare("INSERT INTO projetos (
        nome, descricao, valor, data_inicio, data_fim, status_fk, responsavel, cliente_nome, empresa_id
    ) VALUES (
        :nome, :descricao, :valor, :data_inicio, :data_fim, :status, :responsavel, :cliente_nome, :empresa_id
    )");

    // Executa a query com os valores
    $stmt->execute([
        ':nome' => $nome,
        ':descricao' => $descricao,
        ':valor' => $valor,
        ':data_inicio' => $data_inicio,
        ':data_fim' => $data_fim,
        ':status' => $status,
        ':responsavel' => $responsavel,
        ':cliente_nome' => $cliente_nome,
        ':empresa_id' => $empresa_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Projeto criado com sucesso.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
