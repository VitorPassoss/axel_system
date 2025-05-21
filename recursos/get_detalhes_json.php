<?php 
header('Content-Type: application/json');
include '../backend/auth.php';


include '../backend/dbconn.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Erro na conexão com o banco de dados."]);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "ID inválido."]);
    exit;
}

// Consulta para pegar os dados da solicitação, ordem de serviço, obra e contrato
$sql = "
SELECT 
    sc.*,
    e.id AS empresa_id, e.nome AS empresa_nome, e.cnpj AS empresa_cnpj,
    os.id AS os_id, os.status AS os_status, os.numero_os,
    ob.id AS obra_id, ob.nome AS obra_nome, ob.cep AS obra_cep, ob.contrato_id,
    ct.numero_contrato AS contrato_numero
FROM 
    solicitacao_compras sc
LEFT JOIN 
    empresas e ON e.id = sc.empresa_id
LEFT JOIN 
    ordem_de_servico os ON os.id = sc.os_id
LEFT JOIN 
    obras ob ON ob.id = os.obra_id
LEFT JOIN 
    contratos ct ON ct.id = ob.contrato_id
WHERE 
    sc.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Solicitação não encontrada."]);
    exit;
}

$row = $result->fetch_assoc();

// Agora buscamos os serviços atrelados à OS
$servicos = [];
if ($row["os_id"]) {
    $sqlServicos = "
        SELECT sos.*, s.nome AS servico_nome
        FROM servicos_os sos
        LEFT JOIN servicos s ON s.id = sos.servico_id
        WHERE sos.os_id = ?
    ";
    $stmtServ = $conn->prepare($sqlServicos);
    $stmtServ->bind_param("i", $row["os_id"]);
    $stmtServ->execute();
    $resultServ = $stmtServ->get_result();

    while ($serv = $resultServ->fetch_assoc()) {
        $servicos[] = [
            "id" => $serv["id"],
            "servico_id" => $serv["servico_id"],
            "nome" => $serv["servico_nome"],
            "und" => $serv["und_do_servico"],
            "quantidade" => $serv["quantidade"],
            "tipo" => $serv["tipo_servico"],
            "executor" => $serv["executor"],
            "inicio" => $serv["dt_inicio"],
            "final" => $serv["dt_final"]
        ];
    }
}

// Buscar os itens da solicitação
$itens = [];
$sqlItens = "
    SELECT 
        si.id,
        si.solicitacao_id,
        si.insumo_id,
        i.nome AS insumo_nome,
        si.quantidade,
        si.fornecedor,
        si.grau
    FROM 
        sc_item si
    LEFT JOIN 
        insumos i ON i.id = si.insumo_id
    WHERE 
        si.solicitacao_id = ?
";
$stmtItens = $conn->prepare($sqlItens);
$stmtItens->bind_param("i", $id);
$stmtItens->execute();
$resultItens = $stmtItens->get_result();

while ($item = $resultItens->fetch_assoc()) {
    $itens[] = [
        "id" => $item["id"],
        "solicitacao_id" => $item["solicitacao_id"],
        "insumo_id" => $item["insumo_id"],
        "insumo_nome" => $item["insumo_nome"],
        "quantidade" => $item["quantidade"],
        "fornecedor" => $item["fornecedor"],
        "grau" => $item["grau"]
    ];
}


// Monta o array de resposta
$response = [
    "id" => $row["id"],
    "solicitante" => $row["solicitante"],
    "descricao" => $row["descricao"],
    "valor" => $row["valor"],
    "status" => $row["status"],
    "grau" => $row["grau"],
    "criado_em" => $row["criado_em"],
    "aprovado_por" => $row["aprovado_por"],
    "aprovado_em" => $row["aprovado_em"],
    "empresa" => [
        "id" => $row["empresa_id"],
        "nome" => $row["empresa_nome"],
        "cnpj" => $row["empresa_cnpj"]
    ],
    "ordem_de_servico" => [
        "id" => $row["os_id"],
        "numero_os" => $row["numero_os"],
        "status" => $row["os_status"],
        "servicos" => $servicos
    ],
    "obra" => [
        "id" => $row["obra_id"],
        "nome" => $row["obra_nome"],
        "cep" => $row["obra_cep"],
        "contrato_id" => $row["contrato_id"],
        "numero_contrato" => $row["contrato_numero"]
    ],
    "itens" => $itens
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
