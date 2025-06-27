<?php
session_start();
require_once '../backend/dbconn.php';
header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Erro de conexão."]);
    exit;
}

// Verifica obrigatórios
if (empty($_POST['razao_social']) || empty($_POST['cnpj'])) {
    echo json_encode(["success" => false, "message" => "Razão Social e CNPJ são obrigatórios."]);
    exit;
}

// Campos possíveis no banco
$camposPermitidos = [
    'razao_social', 'nome_fantasia', 'cnpj', 'inscricao_estadual', 'inscricao_municipal',
    'email', 'telefone', 'celular', 'site', 'contato_responsavel', 'endereco', 'numero',
    'complemento', 'bairro', 'cidade', 'estado', 'cep', 'empresa_id'
];

// Campos fixos adicionais
$camposFixos = [
    'data_cadastro' => 'NOW()', // função SQL, não vai no bind
    'observacoes' => '',        // default
    'ativo' => 1                // default
];

// Início das colunas e valores
$colunas = [];
$placeholders = [];
$valores = [];
$tipos = "";

// Adiciona campos enviados
foreach ($camposPermitidos as $campo) {
    if (isset($_POST[$campo]) && $_POST[$campo] !== '') {
        $colunas[] = $campo;
        $placeholders[] = '?';
        $valores[] = $_POST[$campo];
        $tipos .= is_numeric($_POST[$campo]) ? 'i' : 's';
    }
}

// Adiciona fixos
$colunas[] = 'data_cadastro';
$placeholders[] = 'NOW()';

$colunas[] = 'observacoes';
$placeholders[] = '?';
$valores[] = $camposFixos['observacoes'];
$tipos .= 's';

$colunas[] = 'ativo';
$placeholders[] = '?';
$valores[] = $camposFixos['ativo'];
$tipos .= 'i';

// Monta SQL
$sql = "INSERT INTO fornecedores (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $placeholders) . ")";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Erro na preparação: " . $conn->error]);
    exit;
}

if (!empty($valores)) {
    $stmt->bind_param($tipos, ...$valores);
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "nome_fantasia" => $_POST['nome_fantasia'] ?? $_POST['razao_social']
    ]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}
?>
