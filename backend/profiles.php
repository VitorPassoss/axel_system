<?php
// Verifica se o usuário está logado
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Conectar ao banco de dados
include '../backend/dbconn.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Buscar os dados do usuário
$stmt = $conn->prepare("
    SELECT 
        u.id, u.email, u.setor_id, u.empresa_id,
        s.nome AS setor_nome,
        e.nome AS empresa_nome
    FROM users u
    LEFT JOIN setores s ON u.setor_id = s.id
    LEFT JOIN empresas e ON u.empresa_id = e.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    echo json_encode(['error' => 'Usuário inválido']);
    exit;
}

$usuario = $result->fetch_assoc();

// Buscar todos os setores
$setores_query = $conn->prepare("SELECT * FROM setores");
$setores_query->execute();
$setores_result = $setores_query->get_result();

$setores = [];
while ($setor = $setores_result->fetch_assoc()) {
    $setores[] = $setor;
}

// Fechar a conexão
$conn->close();

// Preparar os dados para retorno
$data = [
    'usuario' => $usuario,
    'setores' => $setores
];

// Retornar os dados em formato JSON
echo json_encode($data);
?>
