<?php
// Inicia o cabeçalho como JSON
header('Content-Type: application/json');

// Inclui seu arquivo de conexão com o banco de dados e de sessão
include '../backend/auth.php';
include '../backend/dbconn.php';


// Pega o corpo da requisição JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validação básica dos dados recebidos
if (!$data || !isset($data['id']) || !isset($data['quantidade']) || !isset($data['valor_item']) || !isset($data['desconto'])) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos ou incompletos.']);
    exit;
}

// Atribui os dados a variáveis
$id = (int)$data['id'];
$quantidade = (float)$data['quantidade'];
$valor_item = (float)$data['valor_item'];
$desconto = (float)$data['desconto'];

// Calcula o valor final no lado do servidor para garantir a consistência
$valor_final = ($quantidade * $valor_item) - $desconto;

// Prepara a query de atualização para evitar SQL Injection
$sql = "UPDATE cotacao_item SET quantidade = ?, valor_item = ?, desconto = ?, valor_final = ? WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'error' => 'Erro ao preparar a query: ' . $conn->error]);
    exit;
}

// Binda os parâmetros
$stmt->bind_param("ddddi", $quantidade, $valor_item, $desconto, $valor_final, $id);

// Executa a query e verifica o resultado
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Retorna sucesso e o novo valor final calculado
        echo json_encode([
            'success' => true,
            'valor_final' => $valor_final
        ]);
    } else {
        // Nenhum erro, mas nenhuma linha foi alterada (talvez os dados eram os mesmos)
        echo json_encode(['success' => true, 'valor_final' => $valor_final, 'message' => 'Nenhuma alteração detectada.']);
    }
} else {
    // Erro na execução
    echo json_encode(['success' => false, 'error' => 'Erro ao executar a atualização: ' . $stmt->error]);
}

// Fecha o statement e a conexão
$stmt->close();
$conn->close();
?>