<?php
include '../backend/auth.php';

$empresa_id = $_SESSION['empresa_id']; // ainda necessário se você quiser filtrar algo, mas não será inserido em servicos_os

// Dados do formulário
$sc_id         = $_POST['os_id'];
$nome          = trim($_POST['nome']);
$und           = $_POST['und_do_servico'];
$quantidade    = $_POST['quantidade'];
$tipo_servico  = $_POST['tipo_servico'];
$executor      = $_POST['executor'];
$dt_inicio     = $_POST['dt_inicio'];
$dt_final      = $_POST['dt_final'];

// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verifica se o serviço já existe
$stmt = $conn->prepare("SELECT id FROM servicos WHERE nome = ?");
$stmt->bind_param("s", $nome);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $servico_id = $row['id'];
} else {
    // Cria novo serviço se não existir
    $stmtInsert = $conn->prepare("INSERT INTO servicos (nome) VALUES (?)");
    $stmtInsert->bind_param("s", $nome);
    $stmtInsert->execute();
    $servico_id = $stmtInsert->insert_id;
    $stmtInsert->close();
}

// Agora insere na tabela servicos_os com os nomes corretos das colunas
$stmtOS = $conn->prepare("
    INSERT INTO servicos_os (
        os_id,
        servico_id,
        und_do_servico,
        quantidade,
        tipo_servico,
        executor,
        dt_inicio,
        dt_final
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmtOS->bind_param("iisissss", $sc_id, $servico_id, $und, $quantidade, $tipo_servico, $executor, $dt_inicio, $dt_final);
$stmtOS->execute();

if ($stmtOS->affected_rows > 0) {
    echo json_encode(['success' => true, 'servico_id' => $servico_id]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$stmtOS->close();
$conn->close();
