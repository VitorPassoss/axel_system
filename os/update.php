<?php
session_start();

// Verifica se a variável de sessão 'empresa_id' existe
if (!isset($_SESSION['empresa_id'])) {
    die("Erro: Empresa não identificada.");
}

$empresa_id = $_SESSION['empresa_id']; // Obtém o empresa_id da sessão

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Função de atualização da ordem de serviço
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $os_id           = intval($_POST['id'] ?? 0); // ID da OS a ser atualizada
    $descricao       = $conn->real_escape_string($_POST['descricao'] ?? '');
    $local           = $conn->real_escape_string($_POST['local'] ?? '');
    $numero_os       = $conn->real_escape_string($_POST['numero_os'] ?? '');
    $responsavel_os  = $conn->real_escape_string($_POST['responsavel_os'] ?? '');
    $data_inicio     = isset($_POST['data_inicio']) ? date('Y-m-d', strtotime($_POST['data_inicio'])) : null;
    $data_final      = isset($_POST['data_final']) ? date('Y-m-d', strtotime($_POST['data_final'])) : null;
    $equipe          = $conn->real_escape_string($_POST['equipe'] ?? '');
    $status          = $conn->real_escape_string($_POST['status'] ?? '');

    // Verificar se o ID da OS foi fornecido
    if (!$os_id) {
        echo json_encode(['success' => false, 'error' => 'ID da ordem de serviço não informado.']);
        exit;
    }

    // Atualizar os dados da OS (exceto obra_id e cep)
    $sql = "UPDATE ordem_de_servico SET
        descricao = '$descricao',
        local = '$local',
        numero_os = '$numero_os',
        responsavel_os = '$responsavel_os',
        data_inicio = " . ($data_inicio ? "'$data_inicio'" : "NULL") . ",
        data_final = " . ($data_final ? "'$data_final'" : "NULL") . ",
        equipe = '$equipe',
        status = '$status'
    WHERE id = $os_id AND empresa_id = $empresa_id";

    if ($conn->query($sql) === TRUE) {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!empty($_FILES['anexos']['name'][0])) {
            salvarAnexos($pdo, 'ordem_de_servico', $os_id, $_FILES['anexos']);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}


function salvarAnexos(PDO $conn, $tabela_ref, $ref_id, $arquivos)
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
            $stmt = $conn->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (:tabela_ref, :ref_id, :nome, :caminho)");
            $stmt->execute([
                ':tabela_ref' => $tabela_ref,
                ':ref_id' => $ref_id,
                ':nome' => $nome_original,
                ':caminho' => $caminho_final
            ]);
        }
    }
}
