<?php
session_start();
include '../../backend/dbconn.php';

// Define o cabeçalho como JSON para todas as respostas
header('Content-Type: application/json');

// Verifica a sessão da empresa
if (!isset($_SESSION['empresa_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["success" => false, "error" => "Empresa não identificada. Faça login novamente."]);
    exit;
}

// Garante que a requisição seja do tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Método inválido.']);
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

// Recebe e decodifica os dados JSON enviados pelo JavaScript
$data = json_decode(file_get_contents("php://input"), true);

// --- MUDANÇA 1: Validação adaptada para a nova estrutura de dados ---
if (
    !$data || 
    !isset($data['solicitante']) || 
    !isset($data['descricao']) || 
    !isset($data['itens']) || // Espera 'itens', não 'insumos'
    !is_array($data['itens']) || 
    empty($data['itens'])
) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "error" => "Dados incompletos ou malformados."]);
    exit;
}

// Inicia uma transação para garantir a consistência dos dados
$conn->begin_transaction();

try {
    // --- MUDANÇA 2: Não existe mais um 'grau' geral, ele é por item ---
    $solicitante = $data['solicitante'];
    $descricao = $data['descricao'];
    $osId = isset($data['osId']) ? intval($data['osId']) : null;
    
    // Insere a solicitação principal (removido o campo 'grau' daqui)
    $stmt_sc = $conn->prepare(
        "INSERT INTO solicitacao_compras (os_id, solicitante, empresa_id, status, descricao, criado_em) 
         VALUES (?, ?, ?, 'Pendente', ?, NOW())"
    );
    $stmt_sc->bind_param("isss", $osId, $solicitante, $empresa_id, $descricao);

    if (!$stmt_sc->execute()) {
        throw new Exception("Erro ao criar a solicitação: " . $stmt_sc->error);
    }
    $solicitacao_id = $stmt_sc->insert_id;
    $stmt_sc->close();

    // Prepara as queries para os itens
    $stmt_item = $conn->prepare("INSERT INTO sc_item (solicitacao_id, insumo_id, quantidade, grau, und_medida) VALUES (?, ?, ?, ?, ?)");
    $stmt_select_insumo = $conn->prepare("SELECT id FROM insumos WHERE nome = ?");
    $stmt_insert_insumo = $conn->prepare("INSERT INTO insumos (nome) VALUES (?)");

    // --- MUDANÇA 3: Loop corrigido para 'itens' e chaves corretas ---
    foreach ($data['itens'] as $item) {
        $insumo_id_final = 0;

        // Lógica para obter o ID do insumo (seja novo ou existente)
        if (!empty($item['insumo_id'])) {
            $insumo_id_final = intval($item['insumo_id']);
        } elseif (!empty($item['insumo_nome'])) {
            $nome_insumo_novo = trim($item['insumo_nome']);

            $stmt_select_insumo->bind_param("s", $nome_insumo_novo);
            $stmt_select_insumo->execute();
            $result_insumo = $stmt_select_insumo->get_result();

            if ($result_insumo->num_rows > 0) {
                $insumo_existente = $result_insumo->fetch_assoc();
                $insumo_id_final = $insumo_existente['id'];
            } else {
                $stmt_insert_insumo->bind_param("s", $nome_insumo_novo);
                if (!$stmt_insert_insumo->execute()) {
                    throw new Exception("Erro ao criar novo insumo: " . $stmt_insert_insumo->error);
                }
                $insumo_id_final = $conn->insert_id;
            }
        }

        if ($insumo_id_final <= 0) {
            throw new Exception("ID de insumo inválido para o item '" . ($item['insumo_nome'] ?? '') . "'.");
        }
        
        // Coleta os dados do item com as chaves corretas
        $quantidade = floatval($item['quantidade']);
        $grau_item = $item['grau'];
        $unidade = $item['und_medida'];

        // Insere o item na tabela `sc_item`
        $stmt_item->bind_param("isdss", $solicitacao_id, $insumo_id_final, $quantidade, $grau_item, $unidade);
        if (!$stmt_item->execute()) {
            throw new Exception("Erro ao inserir item na solicitação: " . $stmt_item->error);
        }
    }

    // Se tudo deu certo, confirma as operações
    $conn->commit();
    echo json_encode(["success" => true, "message" => "Solicitação criada com sucesso."]);

} catch (Exception $e) {
    // Se algo deu errado, desfaz tudo
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "error" => $e->getMessage()]);

} finally {
    // Fecha todos os statements abertos
    if (isset($stmt_item)) $stmt_item->close();
    if (isset($stmt_select_insumo)) $stmt_select_insumo->close();
    if (isset($stmt_insert_insumo)) $stmt_insert_insumo->close();
    $conn->close();
}
?>