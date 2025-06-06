<?php
session_start();
require_once '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$data = $_POST;

if (isset($data['produtos'])) {
    $data['produtos'] = json_decode($data['produtos'], true);
}

if (!$data || !isset($data['cotante']) || !isset($data['descricao']) || !isset($data['produtos'])) {
    http_response_code(400);
    echo json_encode(["erro" => "Dados incompletos."]);
    exit;
}

$cotante = $conn->real_escape_string($data['cotante']);
$descricao = $conn->real_escape_string($data['descricao']);
$osId = isset($data['osId']) ? intval($data['osId']) : null;
$scId = isset($data['scId']) ? intval($data['scId']) : null;
$obraId = isset($data['obraId']) ? intval($data['obraId']) : null;
$valorTotal = isset($data['valorTotal']) ? floatval($data['valorTotal']) : 0.0;

$stmt = $conn->prepare("INSERT INTO cotacao (
    os_id, obra_id, sc_id, cotante, status, valor_total, descricao
) VALUES (?, ?, ?, ?, 'Pendente', ?, ?)");

$stmt->bind_param("iiisds", $osId, $obraId, $scId, $cotante, $valorTotal, $descricao);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao criar solicitação: " . $stmt->error]);
    exit;
}

$cotacao_id = $stmt->insert_id;

if (!empty($_FILES['anexos']['name'][0])) {
    salvarAnexos($conn, 'cotacoes', $cotacao_id, $_FILES['anexos']);
}


$stmt->close();

foreach ($data['produtos'] as $insumo) {
    if (!$insumo || !isset($insumo['insumo_nome'])) continue;

    $nome = $conn->real_escape_string($insumo['insumo_nome']);
    $quantidade = floatval($insumo['insumo_quantidade']);
    $unidade = $conn->real_escape_string($insumo['insumo_unidade'] ?? '');
    $valor_item = floatval($insumo['valorUnt']);
    $desconto = floatval($insumo['desconto'] ?? 0);
    $descricao_tecnica = $conn->real_escape_string($insumo['descricao_tecnica'] ?? '');
    $razao_social = $conn->real_escape_string($insumo['fornecedor_id'] ?? ''); // ainda é o nome, não o ID

    $fornecedor_id = 0;
    if (!empty($razao_social)) {
        $stmtFornecedor = $conn->prepare("SELECT id FROM fornecedores WHERE nome_fantasia = ?");
        $stmtFornecedor->bind_param("s", $razao_social);
        $stmtFornecedor->execute();
        $stmtFornecedor->bind_result($fornecedor_id);

        if (!$stmtFornecedor->fetch()) {
            http_response_code(400);
            echo json_encode(["erro" => "Fornecedor não encontrado: " . $razao_social]);
            exit;
        }

        $stmtFornecedor->close();
    }

    $valor_final = $valor_item - $desconto;

    $checkInsumo = $conn->prepare("SELECT id FROM insumos WHERE nome = ?");
    $checkInsumo->bind_param("s", $nome);
    $checkInsumo->execute();
    $checkInsumo->bind_result($insumo_id);

    if ($checkInsumo->fetch()) {
        $checkInsumo->close();
    } else {
        $checkInsumo->close();
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

    $insertItem = $conn->prepare("
        INSERT INTO cotacao_item (
            cotacao_id, insumo_id, fornecedor_id, descricao_tecnica, und_medida, 
            quantidade, valor_item, desconto, valor_final
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertItem->bind_param(
        "iisssdddd",
        $cotacao_id,
        $insumo_id,
        $fornecedor_id,
        $descricao_tecnica,
        $unidade,
        $quantidade,
        $valor_item,
        $desconto,
        $valor_final
    );

    if (!$insertItem->execute()) {
        echo json_encode(["erro" => "Erro ao inserir item da cotação: " . $insertItem->error]);
        exit;
    }



    $insertItem->close();
}

echo json_encode(["sucesso" => true, "mensagem" => "Cotação criada com sucesso."]);



function salvarAnexos(mysqli $conn, $tabela_ref, $ref_id, $arquivos)
{
    $pasta_base = "uploads/$tabela_ref/$ref_id/";
    if (!is_dir($pasta_base)) {
        mkdir($pasta_base, 0777, true);
    }

    $total = count($arquivos['name']);
    for ($i = 0; $i < $total; $i++) {
        $nome_original = basename($arquivos['name'][$i]);
        $tmp_name = $arquivos['tmp_name'][$i];
        $erro = $arquivos['error'][$i];

        if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($tmp_name)) {
            continue;
        }

        $nome_seguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $nome_original);
        $caminho_final = $pasta_base . $nome_seguro;

        if (move_uploaded_file($tmp_name, $caminho_final)) {
            $stmt = $conn->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $tabela_ref, $ref_id, $nome_original, $caminho_final);
            $stmt->execute();
            $stmt->close();
        }
    }
}
