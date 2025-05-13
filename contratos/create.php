<?php
require_once '../backend/auth.php';
require_once '../backend/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

try {
    // Recebe os dados do formulário
    $numero_contrato = $_POST['numero_contrato'] ?? '';
    $numero_empenho = $_POST['numero_empenho'] ?? '';
    $cnpj_cliente = $_POST['cnpj_cliente'] ?? '';
    $nome_cliente = $_POST['nome_cliente'] ?? '';
    $endereco_cliente = $_POST['endereco_cliente'] ?? '';
    $telefone_cliente = $_POST['telefone_cliente'] ?? '';
    $email_cliente = $_POST['email_cliente'] ?? '';
    $valor_mensal = $_POST['valor_mensal'] ?? null;
    $valor_anual = $_POST['valor_anual'] ?? null;
    $observacoes = $_POST['observacoes'] ?? '';
    $situacao = $_POST['situacao'] ?? '';

    // ID da empresa vinculada ao usuário logado
    $empresa_id = $usuario['empresa_id'];

    // Insere o contrato
    $stmt = $conn->prepare("INSERT INTO contratos (
        numero_contrato, numero_empenho, cnpj_cliente, nome_cliente, endereco_cliente,
        telefone_cliente, email_cliente, valor_mensal, valor_anual, observacoes, empresa_id, situacao
    ) VALUES (
        :numero_contrato, :numero_empenho, :cnpj_cliente, :nome_cliente, :endereco_cliente,
        :telefone_cliente, :email_cliente, :valor_mensal, :valor_anual, :observacoes, :empresa_id, :situacao
    )");

    $stmt->execute([
        ':numero_contrato' => $numero_contrato,
        ':numero_empenho' => $numero_empenho,
        ':cnpj_cliente' => $cnpj_cliente,
        ':nome_cliente' => $nome_cliente,
        ':endereco_cliente' => $endereco_cliente,
        ':telefone_cliente' => $telefone_cliente,
        ':email_cliente' => $email_cliente,
        ':valor_mensal' => $valor_mensal,
        ':valor_anual' => $valor_anual,
        ':observacoes' => $observacoes,
        ':empresa_id' => $empresa_id,
        ':situacao' => $situacao

    ]);

    $contrato_id = $conn->lastInsertId(); // pega o ID do contrato recém inserido

    // Salva os arquivos anexos
    if (!empty($_FILES['anexos']['name'][0])) {
        salvarAnexos($conn, 'contratos', $contrato_id, $_FILES['anexos']);
    }

    echo json_encode(['success' => true, 'message' => 'Contrato salvo com sucesso.']);
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
