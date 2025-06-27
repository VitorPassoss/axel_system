<?php
// Define o cabeçalho da resposta como JSON. Essencial para APIs.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (ajuste se necessário)
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Lida com a requisição preflight OPTIONS do CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../backend/dbconn.php';

// Conecta ao banco de dados
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["erro" => "Conexão com o banco de dados falhou: " . $conn->connect_error]);
    exit;
}

// 1. LER O CORPO (BODY) DA REQUISIÇÃO E DECODIFICAR O JSON
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

// Verifica se o JSON é válido
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(["erro" => "JSON inválido no corpo da requisição."]);
    exit;
}

// 2. VALIDAÇÃO DOS DADOS DE ENTRADA
if (!$data || !isset($data['cotante']) || !isset($data['produtos']) || !is_array($data['produtos'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["erro" => "Dados incompletos ou mal formatados. 'cotante' e 'produtos' (array) são obrigatórios."]);
    exit;
}

// Inicia uma transação para garantir a integridade dos dados
$conn->begin_transaction();

try {
    // 3. EXTRAI E SANITIZA OS DADOS DA COTAÇÃO PRINCIPAL
    $cotante = $conn->real_escape_string($data['cotante']);
    $descricao = isset($data['descricao']) ? $conn->real_escape_string($data['descricao']) : '';
    $osId = isset($data['osId']) ? intval($data['osId']) : null;
    $scId = isset($data['scId']) ? intval($data['scId']) : null;
    $obraId = isset($data['obraId']) ? intval($data['obraId']) : null;
    $valorTotal = isset($data['valorTotal']) ? floatval($data['valorTotal']) : 0.0;

    // 4. INSERE A COTAÇÃO NA TABELA PRINCIPAL
    $stmt = $conn->prepare("INSERT INTO cotacao (os_id, obra_id, sc_id, cotante, status, valor_total, descricao) VALUES (?, ?, ?, ?, 'Pendente', ?, ?)");
    $stmt->bind_param("iiisds", $osId, $obraId, $scId, $cotante, $valorTotal, $descricao);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao criar a cotação: " . $stmt->error);
    }

    $cotacao_id = $stmt->insert_id;
    $stmt->close();

    // 5. PROCESSA OS PRODUTOS (ITENS DA COTAÇÃO)
    foreach ($data['produtos'] as $insumo) {
        if (empty($insumo['insumo_nome'])) continue;

        // Extrai e sanitiza dados do item
        $nome = $conn->real_escape_string($insumo['insumo_nome']);
        $quantidade = floatval($insumo['insumo_quantidade']);
        $unidade = $conn->real_escape_string($insumo['insumo_unidade'] ?? '');
        $valor_item = floatval($insumo['valorUnt']);
        $desconto = floatval($insumo['desconto'] ?? 0);
        $descricao_tecnica = $conn->real_escape_string($insumo['descricao_tecnica'] ?? '');
        $fornecedor_nome = $conn->real_escape_string($insumo['fornecedor_id'] ?? ''); // Recebe o nome do fornecedor

        // Busca o ID do fornecedor
        $fornecedor_id = 0;
        if (!empty($fornecedor_nome)) {
            $stmtFornecedor = $conn->prepare("SELECT id FROM fornecedores WHERE nome_fantasia = ?");
            $stmtFornecedor->bind_param("s", $fornecedor_nome);
            $stmtFornecedor->execute();
            $stmtFornecedor->bind_result($fornecedor_id);
            if (!$stmtFornecedor->fetch()) {
                throw new Exception("Fornecedor não encontrado: " . $fornecedor_nome);
            }
            $stmtFornecedor->close();
        }
        
        // Verifica se o insumo já existe, senão, cria
        $insumo_id = null;
        $checkInsumo = $conn->prepare("SELECT id FROM insumos WHERE nome = ?");
        $checkInsumo->bind_param("s", $nome);
        $checkInsumo->execute();
        $checkInsumo->bind_result($insumo_id);
        $checkInsumo->fetch();
        $checkInsumo->close();

        if (is_null($insumo_id)) {
            $insertInsumo = $conn->prepare("INSERT INTO insumos (nome) VALUES (?)");
            $insertInsumo->bind_param("s", $nome);
            if (!$insertInsumo->execute()) {
                throw new Exception("Erro ao inserir novo insumo: " . $insertInsumo->error);
            }
            $insumo_id = $insertInsumo->insert_id;
            $insertInsumo->close();
        }
        
        // O valor final deve ser calculado por item, e não usar o valor total da cotação
        $valor_final_item = ($valor_item * $quantidade) - $desconto;

        // Insere o item da cotação
        $insertItem = $conn->prepare("INSERT INTO cotacao_item (cotacao_id, insumo_id, fornecedor_id, descricao_tecnica, und_medida, quantidade, valor_item, desconto, valor_final) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertItem->bind_param("iiisssddd", $cotacao_id, $insumo_id, $fornecedor_id, $descricao_tecnica, $unidade, $quantidade, $valor_item, $desconto, $valor_final_item);
        
        if (!$insertItem->execute()) {
            throw new Exception("Erro ao inserir item da cotação: " . $insertItem->error);
        }
        $insertItem->close();
    }

    // Se tudo deu certo, confirma as alterações no banco
    $conn->commit();
    http_response_code(201); // 201 Created
    echo json_encode(["sucesso" => true, "mensagem" => "Cotação criada com sucesso.", "cotacao_id" => $cotacao_id]);

} catch (Exception $e) {
    // Se algo deu errado, desfaz todas as alterações
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["erro" => $e->getMessage()]);
} finally {
    // Fecha a conexão
    $conn->close();
}

/*
NOTA SOBRE ANEXOS:
O envio de arquivos (como em `$_FILES`) não é padrão ao usar o `Content-Type: application/json`.
A abordagem recomendada é:
1.  Primeiro, chamar esta API para criar a cotação. A API retorna o `cotacao_id`.
2.  Depois, usar um segundo endpoint (que aceita `multipart/form-data`) para fazer o upload dos arquivos, associando-os ao `cotacao_id` recebido.
*/
?>