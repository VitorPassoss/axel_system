<?php
// conexão com o banco
$conn = new mysqli('localhost', 'root', '', 'axel_db');

// verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// se o form foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_BCRYPT);

    // Verifica se o setor_id e empresa_id foram passados e define NULL caso contrário
    $setor_id = isset($_POST['setor_id']) && !empty($_POST['setor_id']) ? trim($_POST['setor_id']) : NULL;
    $empresa_id = isset($_POST['empresa_id']) && !empty($_POST['empresa_id']) ? trim($_POST['empresa_id']) : NULL;

    // verifica se o email está aprovado
    $stmt = $conn->prepare("SELECT id FROM users_approveds WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo "<p style='color:red;text-align:center;'>Email não autorizado para cadastro.</p>";
    } else {
        // salva o usuário
        $stmt_insert = $conn->prepare("INSERT INTO users (email, senha, setor_id, empresa_id) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssii", $email, $senha, $setor_id, $empresa_id);

        if ($stmt_insert->execute()) {
            header("Location: ../onboard/login.php");  // Redireciona para home após login bem-sucedido
        } else {
            echo "<p style='color:red;text-align:center;'>Erro ao criar conta.</p>";
        }
        $stmt_insert->close();
    }
    $stmt->close();
}

$conn->close();
