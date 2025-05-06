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

    // Prepare a query para atualizar o contrato utilizando PDO
    $stmt = $conn->prepare("UPDATE contratos SET
        numero_contrato=:numero_contrato, numero_empenho=:numero_empenho, cnpj_cliente=:cnpj_cliente,
        nome_cliente=:nome_cliente, endereco_cliente=:endereco_cliente, telefone_cliente=:telefone_cliente,
        email_cliente=:email_cliente, valor_mensal=:valor_mensal, valor_anual=:valor_anual, observacoes=:observacoes
        WHERE id=:id AND empresa_id=:empresa_id");

    // Bind dos parâmetros
    $stmt->bindParam(':numero_contrato', $_POST['numero_contrato']);
    $stmt->bindParam(':numero_empenho', $_POST['numero_empenho']);
    $stmt->bindParam(':cnpj_cliente', $_POST['cnpj_cliente']);
    $stmt->bindParam(':nome_cliente', $_POST['nome_cliente']);
    $stmt->bindParam(':endereco_cliente', $_POST['endereco_cliente']);
    $stmt->bindParam(':telefone_cliente', $_POST['telefone_cliente']);
    $stmt->bindParam(':email_cliente', $_POST['email_cliente']);
    $stmt->bindParam(':valor_mensal', $_POST['valor_mensal']);
    $stmt->bindParam(':valor_anual', $_POST['valor_anual']);
    $stmt->bindParam(':observacoes', $_POST['observacoes']);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);

    // Executa a query
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
