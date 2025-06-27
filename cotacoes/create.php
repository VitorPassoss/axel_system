<?php
session_start();
require_once '../backend/dbconn.php'; // Garanta que este caminho está correto

// Define o cabeçalho para retornar JSON em todas as respostas
header('Content-Type: application/json');

// --- Verificações Básicas de Segurança e Entrada ---

// Permite apenas requisições do tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["erro" => "Método não permitido."]);
    exit;
}

// Verifica um problema comum: payload muito grande para as configurações do php.ini
// Se $_POST estiver vazio, mas o cliente enviou dados, provavelmente é um problema de configuração.
if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    http_response_code(400);
    echo json_encode([
        "erro" => "Dados não recebidos no servidor. Verifique se os valores de 'post_max_size' e 'upload_max_filesize' no seu php.ini são grandes o suficiente para o seu formulário e anexos."
    ]);
    exit;
}

$data = $_POST;

// --- Validação dos Dados ---

// Verifica se os dados essenciais existem
if (!isset($data['cotante']) || !isset($data['produtos'])) {
    http_response_code(400);
    echo json_encode(["erro" => "Dados incompletos. Os campos 'cotante' e 'produtos' são obrigatórios."]);
    exit;
}

// Decodifica a string JSON de produtos para um array PHP
$produtos = json_decode($data['produtos'], true);

// Verifica se a decodificação JSON foi bem-sucedida e se é um array
if (json_last_error() !== JSON_ERROR_NONE || !is_array($produtos)) {
    http_response_code(400);
    echo json_encode(["erro" => "O formato dos produtos é inválido."]);
    exit;
}

if (empty($produtos)) {
    http_response_code(400);
    echo json_encode(["erro" => "Nenhum produto foi adicionado à cotação."]);
    exit;
}


// --- Processamento de Dados e Inserção no Banco ---

// Inicia uma transação para garantir a integridade dos dados. Todas as queries devem ter sucesso, ou nenhuma será executada.
$conn->begin_transaction();

try {
    // Sanitiza as entradas principais
    $cotante = $conn->real_escape_string($data['cotante']);
    $descricao = isset($data['descricao']) ? $conn->real_escape_string($data['descricao']) : '';
    $osId = isset($data['osId']) && !empty($data['osId']) ? intval($data['osId']) : null;
    $scId = isset($data['scId']) && !empty($data['scId']) ? intval($data['scId']) : null;
    $obraId = isset($data['obraId']) && !empty($data['obraId']) ? intval($data['obraId']) : null;

    // --- Calcula o Valor Total no Servidor ---
    // Isto é mais seguro do que confiar em um valor vindo do cliente.
    $valorTotalCalculado = 0.0;
    foreach ($produtos as $insumo) {
        $quantidade = floatval($insumo['insumo_quantidade'] ?? 0);
        $valor_item = floatval($insumo['valorUnt'] ?? 0);
        $desconto = floatval($insumo['desconto'] ?? 0);
        $valorTotalCalculado += ($quantidade * $valor_item) - $desconto;
    }

    // --- Cria a Cotação Principal (cotacao) ---
    $stmt = $conn->prepare("INSERT INTO cotacao (os_id, obra_id, sc_id, cotante, status, valor_total, descricao) VALUES (?, ?, ?, ?, 'Pendente', ?, ?)");
    if ($stmt === false) {
        throw new Exception("Erro na preparação da query de cotação: " . $conn->error);
    }
    
    // Binda os parâmetros. Use "d" para o valor double de valor_total.
    $stmt->bind_param("iiisds", $osId, $obraId, $scId, $cotante, $valorTotalCalculado, $descricao);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao criar cotação: " . $stmt->error);
    }

    $cotacao_id = $stmt->insert_id;
    $stmt->close();

    // --- Processa e Insere cada Item da Cotação (cotacao_item) ---
    foreach ($produtos as $insumo) {
        if (empty($insumo['insumo_nome'])) continue;

        // Sanitiza as entradas do item
        $nome_insumo = $conn->real_escape_string($insumo['insumo_nome']);
        $quantidade = floatval($insumo['insumo_quantidade']);
        $unidade = $conn->real_escape_string($insumo['insumo_unidade'] ?? '');
        $valor_item = floatval($insumo['valorUnt']);
        $desconto = floatval($insumo['desconto'] ?? 0);
        $descricao_tecnica = $conn->real_escape_string($insumo['descricao_tecnica'] ?? '');
        
        // --- Encontra ou Cria o Insumo ---
        $stmtCheckInsumo = $conn->prepare("SELECT id FROM insumos WHERE nome = ?");
        $stmtCheckInsumo->bind_param("s", $nome_insumo);
        $stmtCheckInsumo->execute();
        $resultInsumo = $stmtCheckInsumo->get_result();
        $insumo_id = null;
        if ($row = $resultInsumo->fetch_assoc()) {
            $insumo_id = $row['id'];
        } else {
            $stmtInsertInsumo = $conn->prepare("INSERT INTO insumos (nome) VALUES (?)");
            $stmtInsertInsumo->bind_param("s", $nome_insumo);
            if (!$stmtInsertInsumo->execute()) {
                throw new Exception("Erro ao inserir novo insumo: " . $stmtInsertInsumo->error);
            }
            $insumo_id = $stmtInsertInsumo->insert_id;
            $stmtInsertInsumo->close();
        }
        $stmtCheckInsumo->close();


        // --- Encontra o ID do Fornecedor a partir do Nome ---
        // **CORREÇÃO:** Adicionado fallback para a chave 'fornecedor_id' para compatibilidade.
        $fornecedor_nome_value = $insumo['fornecedor_nome'] ?? $insumo['fornecedor_id'] ?? '';
        $fornecedor_nome = $conn->real_escape_string($fornecedor_nome_value);

        $fornecedor_id = null;
        if (!empty($fornecedor_nome)) {
            $stmtFornecedor = $conn->prepare("SELECT id FROM fornecedores WHERE nome_fantasia = ?");
            $stmtFornecedor->bind_param("s", $fornecedor_nome);
            $stmtFornecedor->execute();
            $resultFornecedor = $stmtFornecedor->get_result();
            if ($rowFornecedor = $resultFornecedor->fetch_assoc()) {
                $fornecedor_id = $rowFornecedor['id'];
            } else {
                // Lança um erro se o nome do fornecedor foi dado mas não encontrado no DB.
                throw new Exception("Fornecedor não encontrado no banco de dados: '" . $fornecedor_nome . "'");
            }
            $stmtFornecedor->close();
        }
        
        // **CORREÇÃO:** Adiciona validação para garantir que fornecedor_id não seja nulo, conforme a regra do banco de dados.
        if ($fornecedor_id === null) {
            throw new Exception("O fornecedor é obrigatório para o item '" . htmlspecialchars($nome_insumo) . "' mas não foi fornecido ou encontrado.");
        }
        
        // --- Cálculo Correto do Valor Final para este item ---
        $valor_final_item = ($quantidade * $valor_item) - $desconto;

        // --- Insere o Item da Cotação ---
        $insertItem = $conn->prepare("INSERT INTO cotacao_item (cotacao_id, insumo_id, fornecedor_id, descricao_tecnica, und_medida, quantidade, valor_item, desconto, valor_final) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Tipos corretos no bind_param: i, i, i, s, s, d, d, d, d
        $insertItem->bind_param(
            "iiissdddd",
            $cotacao_id,
            $insumo_id,
            $fornecedor_id,
            $descricao_tecnica,
            $unidade,
            $quantidade,
            $valor_item,
            $desconto,
            $valor_final_item
        );

        if (!$insertItem->execute()) {
            throw new Exception("Erro ao inserir item da cotação: " . $insertItem->error);
        }
        $insertItem->close();
    }

    // --- Manipula o Upload de Arquivos ---
    if (!empty($_FILES['anexos']['name'][0])) {
        salvarAnexos($conn, 'cotacoes', $cotacao_id, $_FILES['anexos']);
    }

    // Se tudo deu certo, efetiva a transação
    $conn->commit();
    echo json_encode(["sucesso" => true, "mensagem" => "Cotação criada com sucesso."]);

} catch (Exception $e) {
    // Se qualquer erro ocorreu, desfaz a transação
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["erro" => $e->getMessage()]);
} finally {
    // Fecha a conexão com o banco de dados
    $conn->close();
}


// Função para salvar anexos (com tratamento de erro aprimorado)
function salvarAnexos(mysqli $conn, $tabela_ref, $ref_id, $arquivos)
{
    $pasta_base =  "/uploads/$tabela_ref/$ref_id/";
    if (!is_dir($pasta_base)) {
        if (!mkdir($pasta_base, 0775, true)) { // O `true` torna o mkdir recursivo
            throw new Exception(message: "Falha ao criar o diretório de uploads.");
        }
    }

    $total = count($arquivos['name']);
    for ($i = 0; $i < $total; $i++) {
        if ($arquivos['error'][$i] !== UPLOAD_ERR_OK) {
            continue; // Pula arquivos com erro de upload
        }

        $tmp_name = $arquivos['tmp_name'][$i];
        if (!is_uploaded_file($tmp_name)) {
            continue; // Verificação de segurança crucial
        }
        
        $nome_original = basename($arquivos['name'][$i]);
        $nome_seguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $nome_original);
        $caminho_final = $pasta_base . $nome_seguro;

        if (move_uploaded_file($tmp_name, $caminho_final)) {
            $stmt = $conn->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $tabela_ref, $ref_id, $nome_original, $caminho_final);
            if (!$stmt->execute()) {
                unlink($caminho_final); // Se falhar ao salvar no DB, remove o arquivo
                throw new Exception("Falha ao registrar o documento no banco de dados.");
            }
            $stmt->close();
        } else {
            throw new Exception("Falha ao mover o arquivo enviado para o destino final.");
        }
    }
}
