<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = $_POST['descricao'] ?? '';
    $orgao_cidade = $_POST['orgao_cidade'] ?? '';
    $data = $_POST['data'] ?? null;
    $objeto = $_POST['objeto'] ?? '';
    $valor_lote = floatval($_POST['valor_lote'] ?? 0);
    $status = $_POST['status'] ?? '';
    $valor_total = floatval($_POST['valor_total'] ?? 0);
    $empresa_id = $_SESSION['empresa_id'];
    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;

    $stmt = $conn->prepare("
        INSERT INTO licitacoes (descricao, orgao_cidade, data, objeto, valor_lote, status, valor_total, empresa_id, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssdsdiss",
        $descricao,
        $orgao_cidade,
        $data,
        $objeto,
        $valor_lote,
        $status,
        $valor_total,
        $empresa_id,
        $created_at,
        $updated_at
    );

    if ($stmt->execute()) {
        $licitacao_id = $stmt->insert_id; // ID da licitação recém-criada
        http_response_code(200);

        // Salvar anexos, se houver
        if (!empty($_FILES['anexos']['name'][0])) {
            salvarAnexos($conn, 'licitacoes', $licitacao_id, $_FILES['anexos']);
        }

        echo "Criado com sucesso!";
    } else {
        http_response_code(500);
        echo "Erro ao salvar: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo "Método não permitido";
}


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
