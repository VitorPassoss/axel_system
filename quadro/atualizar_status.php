<?php
// Conexão com o banco de dados


include '../backend/dbconn.php';
if ($conn->connect_error) {
  die(json_encode(['sucesso' => false, 'mensagem' => 'Conexão falhou.']));
}

$data = json_decode(file_get_contents('php://input'), true);
$projeto_id = intval($data['projeto_id']);
$status_id = intval($data['status_id']);

$stmt = $conn->prepare("UPDATE projetos SET status_fk = ? WHERE id = ?");
$stmt->bind_param("ii", $status_id, $projeto_id);

if ($stmt->execute()) {
  echo json_encode(['sucesso' => true]);
} else {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar o status.']);
}

$stmt->close();
$conn->close();
?>
