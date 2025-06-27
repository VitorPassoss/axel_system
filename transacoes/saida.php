<?php
session_start();
include '../backend/dbconn.php';
header('Content-Type: application/json');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na conexão com o banco.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo = 'saida';
    $status = $_POST['status'] ?? '';
    $valor = str_replace(',', '.', $_POST['valor'] ?? '0');
    $valor = floatval($valor);

    $banco_id     = $_POST['banco_id'] ?? null;
    $categoria_id = $_POST['categoria_id'] ?? null;
    $setor_id     = $_POST['setor_id'] ?? null;
    $contrato_id  = $_POST['contrato_id'] ?? null;
    $os_id        = $_POST['os_id'] ?? null;
    $projeto_id   = $_POST['projeto_id'] ?? null;

    $dt_pagamento  = $_POST['dt_pagamento'] ?? null;
    $dt_vencimento = $_POST['dt_vencimento'] ?? null;

    $empresa_id = $_SESSION['empresa_id'] ?? null;

    // Validação
    if (empty($descricao) || empty($status) || !$valor || empty($banco_id) || empty($categoria_id) || empty($empresa_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Preencha todos os campos obrigatórios.']);
        exit;
    }

    $sql = "INSERT INTO transacoes 
        (descricao, tipo_transacao, status, valor, banco_id, dt_pagamento, dt_vencimento, categoria_id, contrato_id, os_id, empresa_id, setor_id, projeto_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao preparar a query.']);
        exit;
    }

    $stmt->bind_param(
        "sssdsssiiiiii",
        $descricao,
        $tipo,
        $status,
        $valor,
        $banco_id,
        $dt_pagamento,
        $dt_vencimento,
        $categoria_id,
        $contrato_id,
        $os_id,
        $empresa_id,
        $setor_id,
        $projeto_id
    );

    if ($stmt->execute()) {
        $transacao_id = $stmt->insert_id;

        // Salvar anexos, se existirem
        if (isset($_FILES['anexos'])) {
            salvarAnexos($conn, 'transacoes', $transacao_id, $_FILES['anexos']);
        }

        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao salvar no banco.']);
    }

    exit;
}


// Função para salvar os arquivos
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
