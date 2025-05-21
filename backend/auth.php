<?php
session_start();
include(__DIR__ . '/dbconn.php');

// Função para verificar se o usuário está autenticado
function verificarAutenticacao()
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: ../onboard/login.php");
        exit();
    }
}

// Chama a função automaticamente
verificarAutenticacao();

// Conexão com o banco


if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
// Armazenar o empresa_id na sessão


// Busca os dados completos do usuário logado
$stmt = $conn->prepare("
   SELECT 
    u.id, u.email,  u.is_superuser,
    u.setor_id, u.empresa_id,
    COALESCE(s.nome, '') AS setor_nome,
    COALESCE(e.localizacao, '') AS empresa_nome
FROM users u
LEFT JOIN setores s ON u.setor_id = s.id
LEFT JOIN empresas e ON u.empresa_id = e.id
WHERE u.id = ?

");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Usuário não encontrado.");
}


$usuario = $result->fetch_assoc();
$GLOBALS['usuario'] = $usuario;

$_SESSION['empresa_id'] = $usuario['empresa_id'];

$stmt->close();
$conn->close();
