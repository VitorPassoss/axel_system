<?php
require_once '../backend/auth.php';
require_once '../backend/db.php'; // $pdo

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

try {
    // Dados principais do formulário
    $contrato_id        = $_POST['contrato_id'] ?? null;
    $numero_nota        = $_POST['numero_nota'] ?? null;
    $valor_total        = $_POST['valor_total'] ?? null;
    $data_recebimento   = $_POST['data_recebimento'] ?? null;
    $competencia_mes    = $_POST['competencia_mes'] ?? null;
    $competencia_ano    = $_POST['competencia_ano'] ?? null;
    $observacoes        = $_POST['observacoes'] ?? null;
    $data_emissao       = $_POST['data_emissao'] ?? null;
    $tipo_nota          = 'entrada';

    // Validação mínima
    if (!$contrato_id || !$numero_nota || !$valor_total || !$data_recebimento) {
        throw new Exception('Preencha todos os campos obrigatórios.');
    }

    $empresa_id = $usuario['empresa_id'];

    // Insere a nota fiscal
    $stmt = $pdo->prepare("INSERT INTO notas_fiscais (
        contrato_id, numero_nota, valor_total, data_emissao, data_recebimento,
        competencia_mes, competencia_ano, tipo_nota, observacoes,
        empresa_id, criado_em, atualizado_em
    ) VALUES (
        :contrato_id, :numero_nota, :valor_total, :data_emissao, :data_recebimento,
        :competencia_mes, :competencia_ano, :tipo_nota, :observacoes,
        :empresa_id, NOW(), NOW()
    )");

    $stmt->execute([
        ':contrato_id'      => $contrato_id,
        ':numero_nota'      => $numero_nota,
        ':valor_total'      => $valor_total,
        ':data_emissao'     => $data_emissao,
        ':data_recebimento' => $data_recebimento,
        ':competencia_mes'  => $competencia_mes,
        ':competencia_ano'  => $competencia_ano,
        ':tipo_nota'        => $tipo_nota,
        ':observacoes'      => $observacoes,
        ':empresa_id'       => $empresa_id
    ]);

    $nota_id = $pdo->lastInsertId();

    // Salva os anexos (se houver)
    if (!empty($_FILES['anexos']['name'][0])) {
        salvarAnexos($pdo, 'notas_fiscais', $nota_id, $_FILES['anexos']);
    }

    echo json_encode(['success' => true, 'message' => 'Nota fiscal cadastrada com sucesso.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

// Função para salvar anexos
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
                ':ref_id'     => $ref_id,
                ':nome'       => $nome_original,
                ':caminho'    => $caminho_final
            ]);
        }
    }
}
