<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_empenho = $_POST['numero_empenho'] ?? '';
    $contrato_id = intval($_POST['contrato_id'] ?? 0);
    $data_empenho = $_POST['data_empenho'] ?? null;
    $valor_empenhado = floatval($_POST['valor_empenhado'] ?? 0);
    $descricao = $_POST['descricao'] ?? '';
    $fonte_empenho = $_POST['fonte_empenho'] ?? null;

    // Validação mínima
    if (!$numero_empenho || !$contrato_id || !$data_empenho || $valor_empenhado <= 0) {
        http_response_code(400);
        echo "Dados inválidos.";
        exit;
    }

    // Inserção
    $stmt = $conn->prepare("
        INSERT INTO empenhos (numero_empenho, contrato_id, data_empenho, valor_empenhado, descricao, fonte_empenho)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("sissss", $numero_empenho, $contrato_id, $data_empenho, $valor_empenhado, $descricao, $fonte_empenho);

    if ($stmt->execute()) {
        // Redirecionar para a página de detalhes do contrato
        header("Location: detalhes.php?contrato_id=" . $contrato_id);
        exit;
    } else {
        http_response_code(500);
        echo "Erro ao salvar o empenho: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo "Método não permitido.";
}
