<?php
header("Content-Type: application/json");
include '../backend/dbconn.php';

// Filtros via GET
$filtro_empresa = $_GET['empresa_id'] ?? '';
$filtro_obra = $_GET['obra_id'] ?? '';
$filtro_contrato = $_GET['contrato_id'] ?? '';
$filtro_periodo = $_GET['periodo'] ?? '';

// WHERE base
$where = "WHERE 1=1"; // Começa com true para facilitar concatenação
$params = [];
$types = "";

// Aplica filtros
if (!empty($filtro_empresa)) {
    $where .= " AND os.empresa_id = ?";
    $params[] = intval($filtro_empresa);
    $types .= "i";
}

if (!empty($filtro_obra)) {
    $where .= " AND os.obra_id = ?";
    $params[] = intval($filtro_obra);
    $types .= "i";
}

if (!empty($filtro_contrato)) {
    $where .= " AND os.contrato_id = ?";
    $params[] = intval($filtro_contrato);
    $types .= "i";
}

if (!empty($filtro_periodo)) {
    if ($filtro_periodo === 'hoje') {
        $where .= " AND DATE(os.data_inicio) = CURDATE()";
    } elseif ($filtro_periodo === 'mes') {
        $where .= " AND MONTH(os.data_inicio) = MONTH(CURDATE()) AND YEAR(os.data_inicio) = YEAR(CURDATE())";
    }
}

// Query final
$sql = "
SELECT 
    os.*, 
    o.nome AS nome_obra,
    c.numero_contrato AS numero_contrato
FROM 
    ordem_de_servico os
LEFT JOIN 
    obras o ON os.obra_id = o.id
LEFT JOIN 
    contratos c ON os.contrato_id = c.id
$where
ORDER BY os.id DESC
";

$stmt = $conn->prepare($sql);

// Bind apenas se tiver parâmetros
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Monta resposta
$ordens = [];
while ($row = $result->fetch_assoc()) {
    $ordens[] = $row;
}

echo json_encode(['ordens' => $ordens]);
