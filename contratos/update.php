<?php

require_once '../backend/auth.php';
require_once '../backend/db.php'; // cria $pdo

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception('ID do contrato não fornecido.');
    }

    $empresa_id = $usuario['empresa_id'];

    // Converter datas
    $dt_inicio = new DateTime($_POST['dt_inicio']);
    $dt_fim = new DateTime($_POST['dt_fim']);

    // Calcular número de meses completos entre as datas
    $interval = $dt_inicio->diff($dt_fim);
    $meses = ($interval->y * 12) + $interval->m;

    if ($meses <= 0) {
        throw new Exception('A data final deve ser posterior à data inicial.');
    }

    // Garantir que valor_anual está em formato float
    $valor_anual = floatval($_POST['valor_anual']);

    // Calcular valor mensal
    $valor_mensal = $valor_anual / $meses;

    $stmt = $pdo->prepare("UPDATE contratos SET
        numero_contrato = :numero_contrato,
        numero_empenho = :numero_empenho,
        cnpj_cliente = :cnpj_cliente,
        nome_cliente = :nome_cliente,
        endereco_cliente = :endereco_cliente,
        telefone_cliente = :telefone_cliente,
        email_cliente = :email_cliente,
        valor_mensal = :valor_mensal,
        valor_anual = :valor_anual,
        observacoes = :observacoes,
        situacao = :situacao,
        dt_inicio = :dt_inicio,
        dt_fim = :dt_fim,
        seguro_contrato = :seguro_contrato,
        art = :art
        WHERE id = :id AND empresa_id = :empresa_id
    ");

    $stmt->bindParam(':numero_contrato', $_POST['numero_contrato']);
    $stmt->bindParam(':numero_empenho', $_POST['numero_empenho']);
    $stmt->bindParam(':cnpj_cliente', $_POST['cnpj_cliente']);
    $stmt->bindParam(':nome_cliente', $_POST['nome_cliente']);
    $stmt->bindParam(':endereco_cliente', $_POST['endereco_cliente']);
    $stmt->bindParam(':telefone_cliente', $_POST['telefone_cliente']);
    $stmt->bindParam(':email_cliente', $_POST['email_cliente']);
    $stmt->bindParam(':valor_mensal', $valor_mensal);
    $stmt->bindParam(':valor_anual', $valor_anual);
    $stmt->bindParam(':observacoes', $_POST['observacoes']);
    $stmt->bindParam(':situacao', $_POST['situacao']);
    $stmt->bindParam(':dt_inicio', $_POST['dt_inicio']);
    $stmt->bindParam(':dt_fim', $_POST['dt_fim']);
    $stmt->bindParam(':seguro_contrato', $_POST['seguro_contrato']);
    $stmt->bindParam(':art', $_POST['art']);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);

    $stmt->execute();

    if (!empty($_FILES['anexos']['name'][0])) {
        salvarAnexos($pdo, 'contratos', $id, $_FILES['anexos']);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

function salvarAnexos(PDO $pdo, $tabela_ref, $ref_id, $arquivos)
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
            $stmt = $pdo->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (:tabela_ref, :ref_id, :nome, :caminho)");
            $stmt->execute([
                ':tabela_ref' => $tabela_ref,
                ':ref_id' => $ref_id,
                ':nome' => $nome_original,
                ':caminho' => $caminho_final
            ]);
        }
    }
}
