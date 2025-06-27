<?php
session_start();
require_once '../backend/dbconn.php'; // Ajuste o caminho se necessário

// Valida a conexão com o banco
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha na conexão com o banco de dados: " . $conn->connect_error]);
    exit;
}

// Pega os dados enviados pelo JavaScript
$data = json_decode(file_get_contents('php://input'), true);

// Validação dos dados recebidos (incluindo o novo campo sc_id)
if (
    !$data || !isset($data['produtos']) || empty($data['produtos']) ||
    !isset($data['cotacoes_base_ids']) || empty($data['cotacoes_base_ids']) ||
    !isset($data['cotante']) ||
    !isset($data['sc_id']) // Valida a existência do campo sc_id (pode ser null)
) {
    http_response_code(400);
    echo json_encode(["erro" => "Dados da requisição incompletos."]);
    exit;
}

// ... (A lógica de cálculo de hash para verificação de duplicata permanece a mesma) ...
$produtosSugeridos = $data['produtos'];
$valorTotalSugerido = 0;
$assinaturaItens = [];
foreach ($produtosSugeridos as $produto) {
    $valorTotalSugerido += $produto['valor_final'];
    $assinaturaItens[] = ['insumo_id' => $produto['insumo_id'], 'fornecedor_id' => $produto['fornecedor_id']];
}
sort($assinaturaItens);
$sugestaoHash = md5(json_encode($assinaturaItens));

// ... (A lógica de verificação de duplicata com o SELECT permanece a mesma) ...
$stmtCheck = $conn->prepare("SELECT id FROM cotacao WHERE sugestao_hash = ?");
$stmtCheck->bind_param("s", $sugestaoHash);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
if ($resultCheck->num_rows > 0) {
    $cotacaoExistente = $resultCheck->fetch_assoc();
    echo json_encode(["erro" => "DUPLICADO", "existente_id" => $cotacaoExistente['id']]);
    exit;
}
$stmtCheck->close();

// ---- Inicia a transação para garantir que tudo seja salvo ou nada ----
$conn->begin_transaction();

try {
    // 1. Prepara os dados para inserir a cotação principal
    $cotante = $data['cotante'];
    $descricao = "Cotação gerada automaticamente a partir da sugestão otimizada.";
    
    // Converte o sc_id para inteiro ou null se estiver vazio/inválido
    $scId = (isset($data['sc_id']) && $data['sc_id'] !== '') ? intval($data['sc_id']) : null;

    // Query de inserção ATUALIZADA para incluir sc_id
    $stmt = $conn->prepare(
        "INSERT INTO cotacao (sc_id, cotante, status, valor_total, descricao, sugestao_hash) 
         VALUES (?, ?, 'Pendente', ?, ?, ?)"
    );
    // bind_param ATUALIZADO com o tipo 'i' para o sc_id
    $stmt->bind_param("isdss", $scId, $cotante, $valorTotalSugerido, $descricao, $sugestaoHash);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao criar cotação principal: " . $stmt->error);
    }
    $cotacao_winner_id = $stmt->insert_id;
    $stmt->close();

    // 2. Registra a relação na tabela cotacoes_base (sem alterações aqui)
    $cotacoes_base_ids = $data['cotacoes_base_ids'];
    $stmtLink = $conn->prepare("INSERT INTO cotacoes_base (cotacao_winner_id, cotacao_base_id) VALUES (?, ?)");
    foreach ($cotacoes_base_ids as $base_id) {
        $stmtLink->bind_param("ii", $cotacao_winner_id, $base_id);
        if (!$stmtLink->execute() && $conn->errno !== 1062) {
            throw new Exception("Erro ao vincular cotação base: " . $stmtLink->error);
        }
    }
    $stmtLink->close();

    // 3. Insere os itens da cotação (sem alterações aqui)
    $insertItem = $conn->prepare("INSERT INTO cotacao_item (cotacao_id, insumo_id, fornecedor_id, descricao_tecnica, und_medida, quantidade, valor_item, desconto, valor_final) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($produtosSugeridos as $item) {
        $insertItem->bind_param("iiisssddd", $cotacao_winner_id, $item['insumo_id'], $item['fornecedor_id'], $item['descricao_tecnica'], $item['und_medida'], $item['quantidade'], $item['valor_item'], $item['desconto'], $item['valor_final']);
        if (!$insertItem->execute()) {
            throw new Exception("Erro ao inserir item da cotação: " . $insertItem->error);
        }
    }
    $insertItem->close();

    // Se tudo deu certo, confirma as operações no banco
    $conn->commit();

    echo json_encode([
        "sucesso" => true,
        "cotacao_id" => $cotacao_winner_id,
        "mensagem" => "Cotação sugerida criada com sucesso e vinculada às origens."
    ]);

} catch (Exception $e) {
    // Se algo deu errado, desfaz tudo
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["erro" => $e->getMessage()]);
}

$conn->close();
?>