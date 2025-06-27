<?php
// Cabeçalhos CORS e JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Responde a preflight OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Lê o JSON enviado no corpo da requisição
$rawData = file_get_contents("php://input");
$input = json_decode($rawData, true);

// Verifica se é POST e tem email e senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['email']) && isset($input['senha'])) {
    $email = trim($input['email']);
    $senha = $input['senha'];

    // Inclui a conexão com o banco
    include '../backend/dbconn.php';

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao conectar com o banco de dados."]);
        exit;
    }

    // Consulta agora também retorna empresa_id
    $stmt = $conn->prepare("SELECT id, email, senha, empresa_id FROM users WHERE email = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["status" => "erro", "mensagem" => "Erro na consulta ao banco."]);
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $email_db, $senha_hash, $empresa_id);
        $stmt->fetch();

        if (password_verify($senha, $senha_hash)) {
            $raw_token = $id . $email_db . time() . "chave_secreta";
            $token = hash('sha256', $raw_token);

            echo json_encode([
                "status" => "sucesso",
                "mensagem" => "Login realizado com sucesso.",
                "token" => $token,
                "usuario" => [
                    "id" => $id,
                    "email" => $email_db,
                    "empresa_id" => $empresa_id
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["status" => "erro", "mensagem" => "Email ou senha inválidos."]);
        }
    } else {
        http_response_code(401);
        echo json_encode(["status" => "erro", "mensagem" => "Email ou senha inválidos."]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo json_encode(["status" => "erro", "mensagem" => "Requisição inválida. Envie email e senha em JSON via POST."]);
}
