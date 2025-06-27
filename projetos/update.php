<?php
require_once '../backend/auth.php';

header('Content-Type: application/json');

// Configuração da conexão com o banco de dados usando PDO


try {
    // Conexão com o banco usando PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Definindo o modo de erro
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

try {
    // Recebe os dados do formulário
    $projeto_id = $_POST['projeto_id'] ?? null;
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $valor = $_POST['valor'] ?? null;
    $data_inicio = $_POST['data_inicio'] ?? '';
    $data_fim = $_POST['data_fim'] ?? '';
    $responsavel = $_POST['responsavel'] ?? '';
    $cliente_nome = $_POST['cliente_nome'] ?? '';
    $status = $_POST['status'];
    $empresa_id = $usuario['empresa_id']; // Assumindo que $usuario está sendo carregado corretamente

    if (!$projeto_id) {
        echo json_encode(['success' => false, 'message' => 'ID do projeto não fornecido']);
        exit;
    }

    // Atualiza no banco de dados usando PDO
    // Atualiza no banco de dados usando PDO
    $stmt = $conn->prepare("UPDATE projetos SET
    nome = :nome,
    descricao = :descricao,
    valor = :valor,
    data_inicio = :data_inicio,
    data_fim = :data_fim,
    status_fk = :status_fk,
    responsavel = :responsavel,
    cliente_nome = :cliente_nome
    WHERE id = :projeto_id AND empresa_id = :empresa_id");


    // Bind dos parâmetros para a consulta
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':descricao', $descricao);
    $stmt->bindParam(':valor', $valor);
    $stmt->bindParam(':data_inicio', $data_inicio);
    $stmt->bindParam(':data_fim', $data_fim);
    $stmt->bindParam(':status_fk', $status);
    $stmt->bindParam(':responsavel', $responsavel);
    $stmt->bindParam(':cliente_nome', $cliente_nome);
    $stmt->bindParam(':projeto_id', $projeto_id, PDO::PARAM_INT);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Projeto atualizado com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o projeto.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
