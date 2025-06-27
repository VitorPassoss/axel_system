<?php
// conexão com o banco
include './dbconn.php';

// verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // busca o usuário
    $stmt = $conn->prepare("SELECT id, senha FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $senha_hash);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($senha, $senha_hash)) {
        // Cria a sessão com o ID do usuário
        $_SESSION['user_id'] = $id;
        header("Location: ../home/index.php");  // Redireciona para home após login bem-sucedido
        exit;
    } else {
        echo "<p style='color:red;text-align:center;'>Email ou senha inválidos.</p>";
    }

    $stmt->close();
}

$conn->close();
?>
