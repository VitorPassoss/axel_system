<?php
session_start();

// Verifica se a variável de sessão 'empresa_id' existe
if (!isset($_SESSION['empresa_id'])) {
    die("Erro: Empresa não identificada.");
}

$empresa_id = $_SESSION['empresa_id']; // Obtém o empresa_id da sessão

// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verifica se o status existe


// Funções de CRUD de Obras
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $tipo_obra = $_POST['tipo_obra'] ?? '';
    $status = $_POST['status_id'] ?? '';
    $data_inicio = $_POST['data_inicio'] ?? null;
    $data_previsao_fim = $_POST['data_previsao_fim'] ?? null;
    $data_fim = $_POST['data_fim'] ?? null;
    $custo_real = $_POST['custo_real'] ?? null;
    $endereco = $_POST['endereco'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $cliente = $_POST['cliente'] ?? '';
    $responsavel_tecnico = $_POST['responsavel_tecnico'] ?? '';
    $contrato_id = $_POST['contrato_id'] ?? null;
    $projeto_id = $_POST['projeto_id'] ?? null;

    // Escapar valores para evitar SQL Injection (idealmente usar prepared statements)
    $nome = $conn->real_escape_string($nome);
    $descricao = $conn->real_escape_string($descricao);
    $tipo_obra = $conn->real_escape_string($tipo_obra);
    $endereco = $conn->real_escape_string($endereco);
    $cidade = $conn->real_escape_string($cidade);
    $estado = $conn->real_escape_string($estado);
    $cep = $conn->real_escape_string($cep);
    $cliente = $conn->real_escape_string($cliente);
    $responsavel_tecnico = $conn->real_escape_string($responsavel_tecnico);

    $sql = "INSERT INTO obras (
        nome, descricao, tipo_obra, status_id, data_inicio, data_previsao_fim, data_fim,
        custo_real, endereco, cidade, estado, cep, latitude, longitude,
        cliente, responsavel_tecnico, empresa_id, contrato_id, projeto_id
    ) VALUES (
        '$nome', '$descricao', '$tipo_obra', '$status', 
        " . ($data_inicio ? "'$data_inicio'" : "NULL") . ",
        " . ($data_previsao_fim ? "'$data_previsao_fim'" : "NULL") . ",
        " . ($data_fim ? "'$data_fim'" : "NULL") . ",
        " . ($custo_real ? "'$custo_real'" : "NULL") . ",
        '$endereco', '$cidade', '$estado', '$cep',
        " . ($latitude ? "'$latitude'" : "NULL") . ",
        " . ($longitude ? "'$longitude'" : "NULL") . ",
        '$cliente', '$responsavel_tecnico', '$empresa_id',
        " . ($contrato_id ? "'$contrato_id'" : "NULL") . ",
        " . ($projeto_id ? "'$projeto_id'" : "NULL") . "
    )";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}
