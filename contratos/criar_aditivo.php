<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contrato_principal_id = intval($_POST['contrato_principal_id']);
    $valor_anual = floatval($_POST['valor_anual']); // <- já vem o valor anual diretamente
    $dt_inicio = $_POST['dt_inicio'];
    $dt_fim = $_POST['dt_fim'];
    $empresa_id = $_SESSION['empresa_id'];
    $data_criacao = date('Y-m-d H:i:s');

    // Buscar dados do contrato principal
    $stmt = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $contrato_principal_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: ./detalhes.php?contrato_id={$contrato_principal_id}&erro=Contrato+principal+não+encontrado");
        exit;
    }

    $contrato = $result->fetch_assoc();
    $stmt->close();

    // Contar quantos aditivos já existem
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM aditivos WHERE contrato_principal_id = ?");
    $stmt->bind_param("i", $contrato_principal_id);
    $stmt->execute();
    $stmt->bind_result($total_aditivos);
    $stmt->fetch();
    $stmt->close();

    $numero_termo = $total_aditivos + 1;
    $novo_numero_contrato = "{$numero_termo}º Termo - " . $contrato['numero_contrato'];

    // Calcular valor mensal automaticamente com base em dt_inicio e dt_fim
    $inicio = new DateTime($dt_inicio);
    $fim = new DateTime($dt_fim);

    $intervalo = $inicio->diff($fim);
    $total_meses = ($intervalo->y * 12) + $intervalo->m + 1; // soma 1 para incluir o mês de início

    if ($total_meses > 0) {
        $valor_mensal = round($valor_anual / $total_meses, 2);
    } else {
        $valor_mensal = 0.0;
    }

    // Inserir novo contrato (aditivo)
    $stmt = $conn->prepare("
        INSERT INTO contratos (
            numero_contrato, numero_empenho, cnpj_cliente, nome_cliente, endereco_cliente,
            telefone_cliente, email_cliente, valor_mensal, valor_anual, observacoes,
            empresa_id, criado_em, situacao, dt_inicio, dt_fim, aditivo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");

    $stmt->bind_param(
        "ssssssssdsssiss",
        $novo_numero_contrato,
        $contrato['numero_empenho'],
        $contrato['cnpj_cliente'],
        $contrato['nome_cliente'],
        $contrato['endereco_cliente'],
        $contrato['telefone_cliente'],
        $contrato['email_cliente'],
        $valor_mensal,
        $valor_anual,
        $contrato['observacoes'],
        $empresa_id,
        $data_criacao,
        $contrato['situacao'],
        $dt_inicio,
        $dt_fim
    );

    if ($stmt->execute()) {
        $novo_id = $stmt->insert_id;
        $stmt->close();

        // Criar vínculo na tabela aditivos
        $stmt = $conn->prepare("
            INSERT INTO aditivos (contrato_principal_id, contrato_aditivo_id, criado_em)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $contrato_principal_id, $novo_id, $data_criacao);
        $stmt->execute();
        $stmt->close();

        header("Location: ./detalhes.php?contrato_id={$contrato_principal_id}&sucesso=Aditivo+criado+com+sucesso");
        exit;
    } else {
        $erro = $stmt->error;
        $stmt->close();
        header("Location: ./detalhes.php?contrato_id={$contrato_principal_id}&erro=" . urlencode("Erro ao criar aditivo: $erro"));
        exit;
    }
} else {
    header("Location: ./detalhes.php?erro=Método+não+permitido");
    exit;
}
