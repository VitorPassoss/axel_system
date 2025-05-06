<?php
session_start();

// Verifica se a variável de sessão 'empresa_id' existe
if (!isset($_SESSION['empresa_id'])) {
    die("Erro: Empresa não identificada.");
}

$empresa_id = $_SESSION['empresa_id']; // Obtém o empresa_id da sessão

// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$obra_id = intval($_GET['obra_id']);
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("
    SELECT id, descricao, status, fornecedor, valor
    FROM solicitacao_compras 
    WHERE obra_id = ? AND empresa_id = ?
");
$stmt->bind_param('ii', $obra_id, $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
