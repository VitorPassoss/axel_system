<?php
// Conexão com o banco de dados


include '../backend/dbconn.php';
if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

$nome = $_POST['nome'];
$cor = $_POST['cor'];

$stmt = $conn->prepare("INSERT INTO status (nome, cor) VALUES (?, ?)");
$stmt->bind_param("ss", $nome, $cor);

if ($stmt->execute()) {
  header("Location: ./index.php");
} else {
  echo "Erro ao criar o status.";
}

$stmt->close();
$conn->close();
?>
