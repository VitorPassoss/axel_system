<?php
header('Content-Type: application/json');



include '../backend/dbconn.php';
if ($conn->connect_error) {
    die(json_encode(['sucesso' => false, 'mensagem' => 'Conexão falhou.']));
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificação segura
if (!isset($data['obra_id']) || !isset($data['status_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Parâmetros ausentes.']);
    exit;
}

$obra_id = intval($data['obra_id']);
$status_id = intval($data['status_id']);

$stmt = $conn->prepare("UPDATE obras SET status_id = ? WHERE id = ?");
$stmt->bind_param("ii", $status_id, $obra_id);

if ($stmt->execute()) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar o status.']);
}

$stmt->close();
$conn->close();
?>
