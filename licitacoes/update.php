<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $descricao = $_POST['descricao'] ?? '';
    $orgao_cidade = $_POST['orgao_cidade'] ?? '';
    $data = $_POST['data'] ?? null;
    $dt_fim = $_POST['dt_fim'] ?? null;
    $objeto = $_POST['objeto'] ?? '';
    $valor_lote = floatval($_POST['valor_lote'] ?? 0);
    $status = $_POST['status'] ?? '';
    $valor_total = floatval($_POST['valor_total'] ?? 0);
    $empresa_id = $_SESSION['empresa_id'];
    $updated_at = date('Y-m-d H:i:s');

    $check = $conn->prepare("SELECT id FROM licitacoes WHERE id = ? AND empresa_id = ?");
    $check->bind_param("ii", $id, $empresa_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo "Licitação não encontrada ou acesso negado.";
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE licitacoes
        SET descricao = ?, orgao_cidade = ?, data = ?, dt_fim = ?, objeto = ?, valor_lote = ?, status = ?, valor_total = ?, updated_at = ?
        WHERE id = ? AND empresa_id = ?
    ");

    $stmt->bind_param(
        "sssssdsdsii",
        $descricao,
        $orgao_cidade,
        $data,
        $dt_fim,
        $objeto,
        $valor_lote,
        $status,
        $valor_total,
        $updated_at,
        $id,
        $empresa_id
    );

    if ($stmt->execute()) {
        // Salvar anexos, se houver
        if (!empty($_FILES['anexos']['name'][0])) {
            salvarAnexos($conn, 'licitacoes', $id, $_FILES['anexos']);
        }

        http_response_code(200);
        echo "Atualizado com sucesso!";
    } else {
        http_response_code(500);
        echo "Erro ao atualizar: " . $stmt->error;
    }

    $stmt->close();
    $check->close();
    $conn->close();
} else {
    http_response_code(405);
    echo "Método não permitido ou ID não informado.";
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
