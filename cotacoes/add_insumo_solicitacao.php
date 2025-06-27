<?php
// add_insumo_solicitacao.php (VERSÃO ATUALIZADA)

include '../backend/auth.php';
include '../backend/dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$solicitacao_id = intval($data['solicitacao_id'] ?? 0);
$itens = $data['itens'] ?? [];

if ($solicitacao_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID da solicitação inválido.']);
    exit;
}
if (empty($itens) || !is_array($itens)) {
    echo json_encode(['success' => false, 'error' => 'Nenhum item válido foi enviado.']);
    exit;
}

$conn->begin_transaction();

try {
    // Prepara a query para inserir na tabela de itens da solicitação
    $stmtItemSolicitacao = $conn->prepare("INSERT INTO sc_item (solicitacao_id, insumo_id, und_medida, quantidade, grau) VALUES (?, ?, ?, ?, ?)");

    // Prepara as queries para lidar com os insumos
    $stmtSelectInsumo = $conn->prepare("SELECT id FROM insumos WHERE nome = ?");
    $stmtInsertInsumo = $conn->prepare("INSERT INTO insumos (nome) VALUES (?)");

    foreach ($itens as $item) {
        $insumo_id_final = 0;

        // ********* LÓGICA PRINCIPAL AQUI *********
        if (!empty($item['insumo_id'])) {
            // CASO 1: O insumo já existe, usamos o ID enviado
            $insumo_id_final = intval($item['insumo_id']);
        } elseif (!empty($item['insumo_nome'])) {
            // CASO 2: O insumo é novo e precisa ser criado
            $nome_insumo_novo = trim($item['insumo_nome']);

            // 2.1 - Verifica se o insumo já não foi criado por outra pessoa (segurança)
            $stmtSelectInsumo->bind_param("s", $nome_insumo_novo);
            $stmtSelectInsumo->execute();
            $result = $stmtSelectInsumo->get_result();
            
            if ($result->num_rows > 0) {
                // Já existe, pega o ID
                $insumo_existente = $result->fetch_assoc();
                $insumo_id_final = $insumo_existente['id'];
            } else {
                // 2.2 - Não existe, então insere na tabela `insumos`
                $stmtInsertInsumo->bind_param("s", $nome_insumo_novo);
                if (!$stmtInsertInsumo->execute()) {
                    throw new Exception("Erro ao criar o novo insumo: " . $stmtInsertInsumo->error);
                }
                // 2.3 - Pega o ID do insumo que acabamos de criar
                $insumo_id_final = $conn->insert_id;
            }
        }

        // Validação final antes de inserir o item na solicitação
        if ($insumo_id_final <= 0) {
            throw new Exception("Não foi possível determinar o ID do insumo para o item.");
        }

        $und_medida = trim($item['und_medida'] ?? '');
        $quantidade = floatval($item['quantidade'] ?? 0);
        $grau = trim($item['grau'] ?? '');

        // Insere o item na solicitação com o ID do insumo (seja ele antigo ou novo)
        $stmtItemSolicitacao->bind_param("iisds", $solicitacao_id, $insumo_id_final, $und_medida, $quantidade, $grau);
        
        if (!$stmtItemSolicitacao->execute()) {
            throw new Exception("Erro ao inserir item na solicitação: " . $stmtItemSolicitacao->error);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    // Fecha todos os statements
    if (isset($stmtItemSolicitacao)) $stmtItemSolicitacao->close();
    if (isset($stmtSelectInsumo)) $stmtSelectInsumo->close();
    if (isset($stmtInsertInsumo)) $stmtInsertInsumo->close();
    $conn->close();
}