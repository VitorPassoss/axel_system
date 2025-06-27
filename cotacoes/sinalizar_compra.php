<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

header('Content-Type: application/json');

// FUNÇÃO PARA SALVAR ANEXOS (sem alterações)
function salvarAnexos(mysqli $conn, $tabela_ref, $ref_id, $arquivos)
{
    $pasta_base =  "../uploads/$tabela_ref/$ref_id/"; // Ajuste o caminho para ser relativo ao script
    if (!is_dir($pasta_base)) {
        if (!mkdir($pasta_base, 0775, true)) {
            throw new Exception("Falha ao criar o diretório de uploads: $pasta_base");
        }
    }

    $total = count($arquivos['name']);
    for ($i = 0; $i < $total; $i++) {
        if ($arquivos['error'][$i] !== UPLOAD_ERR_OK) {
            continue; // Pula arquivos com erro
        }

        $tmp_name = $arquivos['tmp_name'][$i];
        if (!is_uploaded_file($tmp_name)) {
            continue; // Segurança
        }
        
        $nome_original = basename($arquivos['name'][$i]);
        $nome_seguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $nome_original);
        $caminho_final = $pasta_base . $nome_seguro;

        if (move_uploaded_file($tmp_name, $caminho_final)) {
            $stmt = $conn->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $tabela_ref, $ref_id, $nome_original, $caminho_final);
            if (!$stmt->execute()) {
                unlink($caminho_final); // Remove o arquivo se o DB falhar
                throw new Exception("Falha ao registrar o documento no banco de dados.");
            }
            $stmt->close();
        } else {
            throw new Exception("Falha ao mover o arquivo enviado.");
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// Inicia a transação do banco de dados
$conn->begin_transaction();

try {
    // 1. Coleta e validação dos dados do formulário
    $cotacao_id = intval($_POST['cotacao_id'] ?? 0);
    $fornecedor_id = intval($_POST['fornecedor_id'] ?? 0);
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $banco_id = intval($_POST['banco_id'] ?? 0);
    $dt_pagamento_inicio = $_POST['dt_pagamento'] ?? date('Y-m-d');
    $tipo_pagamento = $_POST['tipo_pagamento'] ?? 'unico';
    $is_reembolso = isset($_POST['is_reembolso']);
    $motivo_reembolso = $is_reembolso ? ($_POST['motivo_reembolso'] ?? null) : null;
    $chave_pix_reembolso = $is_reembolso ? ($_POST['chave_pix_reembolso'] ?? null) : null;
    
    if ($cotacao_id <= 0 || $categoria_id <= 0 || $fornecedor_id <= 0 || $banco_id <= 0) {
        throw new Exception('Dados essenciais (cotação, categoria, fornecedor, banco) não fornecidos.');
    }

    // 2. Busca de informações da cotação, incluindo o ID da empresa via Ordem de Serviço
    // <-- ALTERADO: A consulta agora une com 'ordem_de_servico' para obter o 'empresa_id'.
    $stmt = $conn->prepare(
        "SELECT 
            SUM(ci.valor_final) AS total, 
            c.descricao, 
            os.empresa_id
         FROM cotacao_item ci
         JOIN cotacao c ON ci.cotacao_id = c.id
         JOIN ordem_de_servico os ON c.os_id = os.id
         WHERE ci.cotacao_id = ? 
         GROUP BY c.id, os.empresa_id"
    );
    $stmt->bind_param("i", $cotacao_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        throw new Exception("Cotação com ID $cotacao_id não encontrada ou não vinculada a uma Ordem de Serviço válida.");
    }

    $total_compra = floatval($result['total'] ?? 0);
    $descricao_base = $result['descricao'] ?? "Compra da Cotação $cotacao_id";
    
    // O empresa_id agora vem da tabela 'ordem_de_servico' através do JOIN
    $empresa_id = intval($result['empresa_id'] ?? 0);

    if ($total_compra <= 0) {
        throw new Exception('O valor total da cotação não pode ser zero.');
    }

    // 3. Preparação dos dados para inserção na tabela `transacoes`
    // <-- ALTERADO: Mensagem de erro mais específica.
    if ($empresa_id <= 0) {
        throw new Exception('Empresa não pôde ser identificada a partir da Ordem de Serviço vinculada à cotação.');
    }
    
    $tipo_transacao = 'saida';
    $status = 'paga';
    $primeira_transacao_id = null;

    // 4. Lógica de Pagamento (Único ou Parcelado)
    $numero_parcelas = ($tipo_pagamento === 'parcelado') ? intval($_POST['numero_parcelas'] ?? 1) : 1;
    if ($numero_parcelas <= 0) $numero_parcelas = 1;
    $valor_parcela = round($total_compra / $numero_parcelas, 2);

    $stmt_insert = $conn->prepare("INSERT INTO transacoes (
        descricao, tipo_transacao, status, valor, dt_pagamento, dt_vencimento,
        categoria_id, empresa_id, cotacao_id, fornecedor_id, banco_id,
        reembolso, motivo_reembolso, chave_pix_reembolso
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    for ($i = 1; $i <= $numero_parcelas; $i++) {
        $descricao_parcela = ($numero_parcelas > 1) ? "$descricao_base (Parcela $i/$numero_parcelas)" : $descricao_base;
        
        $data_vencimento = new DateTime($dt_pagamento_inicio);
        if ($i > 1) {
            $data_vencimento->modify("+" . ($i - 1) . " months");
        }
        $dt_vencimento_str = $data_vencimento->format('Y-m-d');
        $dt_pagamento_str = ($i==1) ? $dt_pagamento_inicio : null;

        $reembolso_bool = $is_reembolso ? 1 : 0;

        // Nenhuma mudança necessária aqui, a variável $empresa_id já contém o valor correto.
        $stmt_insert->bind_param(
            "sssddssiiisiss",
            $descricao_parcela,
            $tipo_transacao,
            $status,
            $valor_parcela,
            $dt_pagamento_str,
            $dt_vencimento_str,
            $categoria_id,
            $empresa_id,
            $cotacao_id,
            $fornecedor_id,
            $banco_id,
            $reembolso_bool,
            $motivo_reembolso,
            $chave_pix_reembolso
        );
        
        if (!$stmt_insert->execute()) {
            throw new Exception("Erro ao inserir a transação (parcela $i): " . $stmt_insert->error);
        }

        if ($i === 1) {
            $primeira_transacao_id = $conn->insert_id;
        }
    }
    $stmt_insert->close();

    // 5. Salvar Anexos, se houver
    if ($primeira_transacao_id && isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
        salvarAnexos($conn, 'transacoes', $primeira_transacao_id, $_FILES['anexos']);
    }

    // Se tudo deu certo, comita a transação
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Compra salva e transações lançadas com sucesso!']);

} catch (Exception $e) {
    // Se algo deu errado, reverte tudo
    $conn->rollback();
    http_response_code(400); // Adiciona um código de erro HTTP para o cliente
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();

?>