<?php
require_once '../backend/auth.php';
require_once '../backend/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

try {
    $obra_id = !empty($_POST['obra_id']) ? $_POST['obra_id'] : null;
    $projeto_id = !empty($_POST['projeto_id']) ? $_POST['projeto_id'] : null;
    $valor = $_POST['valor'] ?? 0;
    $fornecedor = $_POST['fornecedor'] ?? '';
    $status = $_POST['status'] ?? 'PENDENTE';
    $descricao = $_POST['descricao'] ?? '';
    $empresa_id = $usuario['empresa_id'];

    $stmt = $conn->prepare("INSERT INTO solicitacao_compras (
        obra_id, projeto_id, empresa_id, valor, fornecedor, status, descricao
    ) VALUES (
        :obra_id, :projeto_id, :empresa_id, :valor, :fornecedor, :status, :descricao
    )");

    $stmt->execute([
        ':obra_id' => $obra_id,
        ':projeto_id' => $projeto_id,
        ':empresa_id' => $empresa_id,
        ':valor' => $valor,
        ':fornecedor' => $fornecedor,
        ':status' => $status,
        ':descricao' => $descricao
    ]);

    $sc_id = $conn->lastInsertId();

    if (!empty($_FILES['anexos']['name'][0])) {
        salvarAnexos($conn, 'solicitacao_compras', $sc_id, $_FILES['anexos']);
    }

    echo json_encode(['success' => true, 'message' => 'Solicitação salva com sucesso.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}


// Função para salvar anexos
function salvarAnexos(PDO $conn, $tabela_ref, $ref_id, $arquivos)
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
            $stmt = $conn->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (:tabela_ref, :ref_id, :nome, :caminho)");
            $stmt->execute([
                ':tabela_ref' => $tabela_ref,
                ':ref_id' => $ref_id,
                ':nome' => $nome_original,
                ':caminho' => $caminho_final
            ]);
        }
    }
}
