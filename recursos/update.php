<?php
require_once '../backend/auth.php';
require_once '../backend/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

try {
    $sc_id = $_POST['sc_id'] ?? null;

    if (!$sc_id) {
        throw new Exception('ID da solicitação de compra não fornecido.');
    }

    $empresa_id = $usuario['empresa_id'];

    // Armazena os dados em variáveis antes de passar para bindParam
    $descricao = $_POST['descricao'];
    $fornecedor = $_POST['fornecedor'];
    $status = $_POST['status'];
    $projeto_id = $_POST['projeto_id'] ?? null;
    $obra_id = $_POST['obra_id'] ?? null;

    $stmt = $conn->prepare("UPDATE solicitacao_compras SET
        descricao = :descricao,
        fornecedor = :fornecedor,
        status = :status,
        projeto_id = :projeto_id,
        obra_id = :obra_id
        WHERE id = :sc_id AND empresa_id = :empresa_id");

    $stmt->bindParam(':descricao', $descricao);
    $stmt->bindParam(':fornecedor', $fornecedor);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':projeto_id', $projeto_id);
    $stmt->bindParam(':obra_id', $obra_id);
    $stmt->bindParam(':sc_id', $sc_id, PDO::PARAM_INT);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);

    $stmt->execute();

    // Tratamento dos anexos (se existirem)
    if (!empty($_FILES['anexos']['name'][0])) {
        $upload_dir = '../uploads/';
        foreach ($_FILES['anexos']['tmp_name'] as $index => $tmp_name) {
            $file_name = $_FILES['anexos']['name'][$index];
            $file_path = $upload_dir . basename($file_name);

            if (move_uploaded_file($tmp_name, $file_path)) {
                $stmt = $conn->prepare("INSERT INTO anexos (solicitacao_id, caminho_arquivo) VALUES (:sc_id, :file_path)");
                $stmt->bindParam(':sc_id', $sc_id, PDO::PARAM_INT);
                $stmt->bindParam(':file_path', $file_path);
                $stmt->execute();
            }
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
