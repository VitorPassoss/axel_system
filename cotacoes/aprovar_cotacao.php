<?php
include '../backend/auth.php';
include '../backend/dbconn.php'; // $conn (para MySQLi)
include '../backend/db.php'; // $pdo (para PDO)

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// Pega os dados do corpo da requisição JSON
$data = json_decode(file_get_contents('php://input'), true);

$id_cotacao = intval($data['id'] ?? 0);
$nome_aprovador = trim($data['aprovador'] ?? '');
$senha = $data['senha'] ?? '';

// Valida campos obrigatórios
if ($id_cotacao <= 0 || $nome_aprovador === '' || $senha === '') {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
    exit;
}

// --- Captura de dados para auditoria ---
$endereco_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'; // Pega o IP do cliente
$cargo_aprovador = 'Presidente'; // Assumindo que o aprovador é o Presidente para este fluxo

// --- NOVO: Lógica para buscar o endereço do usuário logado ---
$endereco_aprovador = null; // Inicializa como nulo

// Você precisará do ID do usuário logado para buscar o endereço.
// Assumindo que você tem o user_id na sessão:
if (isset($_SESSION['user_id'])) {
    try {
        // AJUSTE ESTA CONSULTA: Substitua 'usuarios' pela sua tabela de usuários e 'endereco' pela sua coluna de endereço.
        // O ideal é que esta tabela contenha o endereço do usuário que está aprovando (Presidente).
        $stmt_user_address = $pdo->prepare("SELECT endereco FROM usuarios WHERE id = ?");
        $stmt_user_address->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user_address->fetch(PDO::FETCH_ASSOC);

        if ($user_data && !empty($user_data['endereco'])) {
            $endereco_aprovador = $user_data['endereco'];
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar endereço do usuário " . $_SESSION['user_id'] . ": " . $e->getMessage());
        // Não é crítico para a aprovação falhar se o endereço não for encontrado, apenas logamos.
    }
}


$cotacao_conteudo_para_hash = ''; // Variável para armazenar o conteúdo que será "hashado"

try {
    // Busca a senha mestra mais recente
    $stmt_senha = $pdo->query("SELECT senha_hash FROM senha_mestra ORDER BY id DESC LIMIT 1");
    $row_senha = $stmt_senha->fetch(PDO::FETCH_ASSOC);

    if (!$row_senha || !password_verify($senha, $row_senha['senha_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Senha incorreta.']);
        exit;
    }

    // --- Buscar dados da cotação para o hash e para a auditoria ---
    $stmt_cotacao = $pdo->prepare("SELECT * FROM cotacao WHERE id = ?");
    $stmt_cotacao->execute([$id_cotacao]);
    $cotacao_dados = $stmt_cotacao->fetch(PDO::FETCH_ASSOC);

    if (!$cotacao_dados) {
        echo json_encode(['success' => false, 'error' => 'Cotação não encontrada.']);
        exit;
    }

    // Geração do hash da cotação
    $cotacao_conteudo_para_hash = json_encode($cotacao_dados);
    $hash_cotacao = hash('sha256', $cotacao_conteudo_para_hash);

    // --- Início da Transação (Garanta que ambas as operações sejam bem-sucedidas ou falhem juntas) ---
    $pdo->beginTransaction();

    // 1. Atualiza a cotação com status aprovado (usando PDO para consistência)
    $stmt_update_cotacao = $pdo->prepare("
        UPDATE cotacao
        SET status = 'aprovado',
            aprovado_por = ?,
            dt_aprovado = NOW()
        WHERE id = ?
    ");
    $stmt_update_cotacao->execute([$nome_aprovador, $id_cotacao]);

    // 2. Insere o registro na tabela de auditoria
    // AGORA INCLUÍMOS O CAMPO 'endereco' NA QUERY E NOS VALORES
    $stmt_auditoria = $pdo->prepare("
        INSERT INTO auditoria_aprovacao_cotacao (
            id_cotacao,
            nome_aprovador,
            cargo_aprovador,
            data_hora_aprovacao,
            endereco_ip,
            endereco,         -- NOVO CAMPO AQUI
            hash_cotacao
        ) VALUES (
            ?, ?, ?, NOW(), ?, ?, ? -- Agora 7 placeholders para 7 valores
        )
    ");
    $stmt_auditoria->execute([
        $id_cotacao,
        $nome_aprovador,
        $cargo_aprovador,
        $endereco_ip,
        $endereco_aprovador, // NOVO VALOR AQUI
        $hash_cotacao
    ]);

    // --- Finaliza a Transação ---
    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack(); // Desfaz todas as operações se algo der errado
    error_log("Erro no servidor ao aprovar cotação: " . $e->getMessage()); // Loga o erro para depuração
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erro inesperado: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro inesperado: ' . $e->getMessage()]);
}

// Fechando a conexão PDO se ainda estiver aberta (geralmente não é necessário com PDO, mas por segurança)
$pdo = null;
?>