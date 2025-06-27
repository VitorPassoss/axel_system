<?php
session_start();

// Verifica se a variável de sessão 'empresa_id' existe
if (!isset($_SESSION['empresa_id'])) {
    http_response_code(401); // Unauthorized
    die(json_encode(["erro" => "Erro: Empresa não identificada. Acesso não autorizado."]));
}

$empresa_id = $_SESSION['empresa_id']; // Obtém o empresa_id da sessão

// Conexão com o banco de dados
include '../backend/dbconn.php';

if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    die(json_encode(["erro" => "Conexão falhou: " . $conn->connect_error]));
}

// Recebe os dados JSON
$data = json_decode(file_get_contents("php://input"), true);

// Verifica se os dados obrigatórios existem
if (!$data || !isset($data['solicitante']) || !isset($data['grau']) || !isset($data['descricao']) || !isset($data['insumos'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["erro" => "Dados incompletos."]);
    exit;
}

$solicitante = $conn->real_escape_string($data['solicitante']);
$grau = $conn->real_escape_string($data['grau']);
$descricao = $conn->real_escape_string($data['descricao']);
// A linha que pegava o osId foi removida daqui.

// ALTERAÇÃO 1: A query SQL agora insere NULL diretamente no campo os_id.
$stmt = $conn->prepare("INSERT INTO solicitacao_compras (os_id, solicitante, empresa_id, valor, status, grau, descricao, criado_em) VALUES (NULL, ?, ?, 0, 'Pendente', ?, ?, NOW())");

// ALTERAÇÃO 2: O bind_param foi ajustado. Removemos o "i" (de $osId) e a variável $osId.
$stmt->bind_param("siss", $solicitante, $empresa_id, $grau, $descricao);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao criar solicitação: " . $stmt->error]);
    exit;
}

$solicitacao_id = $stmt->insert_id;
$stmt->close();

// Processa os insumos (nenhuma alteração necessária nesta parte)
foreach ($data['insumos'] as $insumo) {
    $nome = $conn->real_escape_string($insumo['insumo_nome']);
    $quantidade = $conn->real_escape_string($insumo['insumo_quantidade']);
    $grau_insumo = $conn->real_escape_string($insumo['insumo_grau']);
    $unidade = isset($insumo['insumo_unidade']) ? $conn->real_escape_string($insumo['insumo_unidade']) : '';

    // Verifica se o insumo já existe
    $checkInsumo = $conn->prepare("SELECT id FROM insumos WHERE nome = ?");
    $checkInsumo->bind_param("s", $nome);
    $checkInsumo->execute();
    $checkInsumo->bind_result($insumo_id);
    if ($checkInsumo->fetch()) {
        // insumo_id já preenchido
        $checkInsumo->close();
    } else {
        $checkInsumo->close();
        // Cria novo insumo
        $insertInsumo = $conn->prepare("INSERT INTO insumos (nome) VALUES (?)");
        $insertInsumo->bind_param("s", $nome);
        if ($insertInsumo->execute()) {
            $insumo_id = $insertInsumo->insert_id;
        } else {
            echo json_encode(["erro" => "Erro ao inserir insumo: " . $insertInsumo->error]);
            exit;
        }
        $insertInsumo->close();
    }

    // Insere item na sc_item
    $insertItem = $conn->prepare("INSERT INTO sc_item (solicitacao_id, insumo_id, quantidade, fornecedor, grau, und_medida) VALUES (?, ?, ?, '', ?, ?)");
    $insertItem->bind_param("iisss", $solicitacao_id, $insumo_id, $quantidade, $grau_insumo, $unidade);

    if (!$insertItem->execute()) {
        echo json_encode(["erro" => "Erro ao inserir item da solicitação: " . $insertItem->error]);
        exit;
    }

    $insertItem->close();
}

echo json_encode(["sucesso" => true, "mensagem" => "Solicitação criada com sucesso."]);

$conn->close();
?>