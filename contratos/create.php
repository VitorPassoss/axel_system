<?php
require_once '../backend/auth.php';
require_once '../backend/db.php'; // cria $pdo

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
    $valor_anual = $_POST['valor_anual'] ?? null;
    $observacoes = $_POST['observacoes'] ?? '';
    $situacao = $_POST['situacao'] ?? '';
    $dt_inicio = $_POST['dt_inicio'] ?? null;
    $dt_fim = $_POST['dt_fim'] ?? null;
    $seguro_contrato = $_POST['seguro_contrato'] ?? '';
    $art = $_POST['art'] ?? '';

    if (!$valor_anual || !$dt_inicio || !$dt_fim) {
        throw new Exception("Valor anual, data de início e data de fim são obrigatórios.");
    }

    // Calcular número de meses entre as datas
    $inicio = new DateTime($dt_inicio);
    $fim = new DateTime($dt_fim);
    if ($fim < $inicio) {
        throw new Exception("Data final deve ser maior que a data de início.");
    }

    $interval = $inicio->diff($fim);
    $meses = ($interval->y * 12) + $interval->m + ($interval->d > 0 ? 1 : 0); // adiciona +1 se houver dias extras

    if ($meses === 0) {
        throw new Exception("Período entre datas é inferior a um mês.");
    }

    $valor_mensal = $valor_anual / $meses;

    // ID da empresa vinculada ao usuário logado
    $empresa_id = $usuario['empresa_id'];

    // Insere o contrato usando PDO
    $stmt = $pdo->prepare("INSERT INTO contratos (
        numero_contrato, numero_empenho, cnpj_cliente, nome_cliente, endereco_cliente,
        telefone_cliente, email_cliente, valor_mensal, valor_anual, observacoes,
        empresa_id, situacao, dt_inicio, dt_fim, seguro_contrato, art
    ) VALUES (
        :numero_contrato, :numero_empenho, :cnpj_cliente, :nome_cliente, :endereco_cliente,
        :telefone_cliente, :email_cliente, :valor_mensal, :valor_anual, :observacoes,
        :empresa_id, :situacao, :dt_inicio, :dt_fim, :seguro_contrato, :art
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
        ':situacao' => $situacao,
        ':dt_inicio' => $dt_inicio,
        ':dt_fim' => $dt_fim,
        ':seguro_contrato' => $seguro_contrato,
        ':art' => $art
    ]);

    $contrato_id = $pdo->lastInsertId(); // pega o ID do contrato recém inserido

    // Salva os arquivos anexos
    if (!empty($_FILES['anexos']['name'][0])) {
        salvarAnexos($pdo, 'contratos', $contrato_id, $_FILES['anexos']);
    }

    echo json_encode(['success' => true, 'message' => 'Contrato salvo com sucesso.']);
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
                ':ref_id' => $ref_id,
                ':nome' => $nome_original,
                ':caminho' => $caminho_final
            ]);
        }
    }
}
