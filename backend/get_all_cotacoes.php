<?php
include 'auth.php'; // Ensure this establishes your $conn connection


include 'dbconn.php'; 

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => []];

// Check if $conn is valid before using it
if (!isset($conn) || $conn->connect_error) { // Improved check for $conn existence
    $response['message'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Connection object not set.');
    echo json_encode($response);
    exit;
}

try {
    // Busca APENAS as cotações com status 'pendente' e faz JOINs com outras tabelas
    $stmtCotacao = $conn->prepare("
        SELECT
            c.*,
            -- Da solicitacao_compras (através de sc_id na cotacao)
            sc.empresa_id,
            e.nome AS empresa_nome,

            -- Da tabela obras (via obra_id da cotacao)
            o.nome AS obra_nome, -- Assumindo que a coluna na tabela 'obras' é 'nome' para o nome da obra

            -- Da tabela contratos (via contrato_id da obras, que é ligada à cotacao)
            co.numero_contrato AS contrato_numero
        FROM
            cotacao c
        LEFT JOIN
            solicitacao_compras sc ON c.sc_id = sc.id
        LEFT JOIN
            empresas e ON sc.empresa_id = e.id
        LEFT JOIN
            obras o ON c.obra_id = o.id -- Usando obra_id DIRETAMENTE da tabela cotacao
        LEFT JOIN
            contratos co ON o.contrato_id = co.id -- Usando contrato_id da tabela obras (que vem da cotacao)
        WHERE
            c.status = 'pendente'
    ");
    if (!$stmtCotacao) {
        throw new Exception("Failed to prepare statement for cotacao: " . $conn->error);
    }
    $stmtCotacao->execute();
    $resultCotacao = $stmtCotacao->get_result();

    $cotacoes = [];
    while ($cotacao = $resultCotacao->fetch_assoc()) {
        // Buscar itens da cotação com nome do fornecedor e nome do insumo
        $stmtItens = $conn->prepare("
            SELECT
                ci.id,
                ci.cotacao_id,
                ci.insumo_id,
                ci.fornecedor_id,
                ci.descricao_tecnica,
                ci.valor_final,
                ci.und_medida,
                ci.quantidade,
                ci.valor_item,
                ci.desconto,
                f.nome_fantasia AS fornecedor_nome,
                i.nome AS insumo_nome
            FROM cotacao_item ci
            LEFT JOIN fornecedores f ON ci.fornecedor_id = f.id
            LEFT JOIN insumos i ON ci.insumo_id = i.id
            WHERE ci.cotacao_id = ?
        ");
        if (!$stmtItens) {
            // This error indicates a serious problem with the query or DB connection for items
            error_log("Failed to prepare statement for cotacao_item: " . $conn->error);
            // You might want to skip this cotacao or return an error for the entire request
            continue; // Skip processing items for this problematic cotacao
        }
        $stmtItens->bind_param("i", $cotacao['id']);
        $stmtItens->execute();
        $resultItens = $stmtItens->get_result();
        $cotacao['itens'] = [];

        while ($item = $resultItens->fetch_assoc()) {
            $cotacao['itens'][] = $item;
        }
        $stmtItens->close();

        $cotacoes[] = $cotacao;
    }

    $stmtCotacao->close();
    $conn->close(); // Close connection after all operations are done for this script.

    $response['success'] = true;
    $response['data'] = $cotacoes;
    echo json_encode($response);

} catch (Exception $e) {
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
    echo json_encode($response);
    // Only attempt to close if $conn is an object and not already closed or failed.
    // The previous fatal error indicates a version mismatch for is_closed().
    // Simply calling close() might suffice if the connection was indeed established.
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>