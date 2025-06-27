<?php
session_start(); // necessário para acessar $_SESSION

include '../backend/dbconn.php';

header('Content-Type: application/json');

$cotacao_id = intval($_GET['cotacao_id'] ?? 0);
$response = [];

if ($cotacao_id) {
  // Cotação
  $stmt = $conn->prepare("SELECT * FROM cotacao WHERE id = ?");
  $stmt->bind_param("i", $cotacao_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $cotacao = $result->fetch_assoc();
  $stmt->close();

  // Itens da cotação
  $stmt = $conn->prepare("SELECT * FROM cotacao_item WHERE cotacao_id = ?");
  $stmt->bind_param("i", $cotacao_id);
  $stmt->execute();
  $itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  $response['cotacao'] = $cotacao;
  $response['itens'] = $itens;
}

echo json_encode($response);
?>
