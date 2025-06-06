<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Conexão com o banco
include './dbconn.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "erro", "mensagem" => "Erro de conexão com o banco de dados."]);
    exit;
}

// Lê o JSON enviado via POST
$input = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['email']) && isset($input['senha'])) {
    $email = trim($input['email']);
    $senha = $input['senha'];

    // Consulta usuário (com mais dados se quiser)
    $stmt = $conn->prepare("SELECT id, nome, email, senha FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $nome, $email_db, $senha_hash);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($senha, $senha_hash)) {
        // Geração de token simples (hash do ID + tempo + email + salt opcional)
        $raw_token = $id . $email_db . time() . "chave_secreta";
        $token = hash('sha256', $raw_token);

        echo json_encode([
            "status" => "sucesso",
            "mensagem" => "Login realizado com sucesso.",
            "token" => $token,
            "usuario" => [
                "id" => $id,
                "nome" => $nome,
                "email" => $email_db
            ]
        ]);
    } else {
        http_response_code(401); // não autorizado
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Email ou senha inválidos."
        ]);
    }

    $stmt->close();
} else {
    http_response_code(400); // requisição malformada
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Requisição inválida. Envie email e senha em JSON via POST."
    ]);
}

$conn->close();
?>
