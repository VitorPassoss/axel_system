<?php
// É crucial iniciar a sessão ANTES de qualquer output para acessar $_SESSION
session_start();

/**
 * 1. INICIALIZAÇÃO E DEPENDÊNCIAS
 */
// Carrega a biblioteca mPDF via Composer
require_once __DIR__ . '/../vendor/autoload.php';
// Inclui o arquivo de conexão com o banco de dados MySQLi
require_once '../backend/dbconn.php';
// Inclui o arquivo de conexão com o banco de dados PDO (necessário para a tabela de auditoria)
require_once '../backend/db.php'; // Adicionado para a conexão PDO ($pdo)


/**
 * 2. AUTENTICAÇÃO E VALIDAÇÃO
 */
// Função para verificar se o usuário está logado
function verificarAutenticacao()
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        http_response_code(403); // Forbidden
        die("Acesso negado. Você precisa estar logado para gerar este documento.");
    }
}
verificarAutenticacao();

// Validação do ID da Cotação (que se tornará um Pedido de Compra)
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("ID da Cotação inválido ou não fornecido.");
}
$cotacao_id = intval($_GET['id']);

// Validação do ID da Empresa a partir da sessão
if (!isset($_SESSION['empresa_id'])) {
    die("ID da empresa não definido na sessão. Não é possível continuar.");
}
$empresa_id = $_SESSION['empresa_id'];

/**
 * 3. LÓGICA DE BUSCA DE DADOS DO BANCO
 */

// Função auxiliar para buscar uma cotação completa pelo ID
function getFullCotacaoById($conn, $id_cotacao) {
    $stmt = $conn->prepare("SELECT * FROM cotacao WHERE id = ?");
    $stmt->bind_param("i", $id_cotacao);
    $stmt->execute();
    $result = $stmt->get_result();
    $cotacao_data = $result->fetch_assoc();
    $stmt->close();

    if (!$cotacao_data) {
        return null;
    }

    $stmtItens = $conn->prepare("
        SELECT ci.*, i.id as insumo_id, i.nome as insumo_nome, f.nome_fantasia as fornecedor_nome
        FROM cotacao_item ci
        LEFT JOIN insumos i ON ci.insumo_id = i.id
        LEFT JOIN fornecedores f ON ci.fornecedor_id = f.id
        WHERE ci.cotacao_id = ?
    ");
    $stmtItens->bind_param("i", $cotacao_data['id']);
    $stmtItens->execute();
    $resultItens = $stmtItens->get_result();
    $cotacao_data['itens'] = [];
    $total = 0;
    while ($item = $resultItens->fetch_assoc()) {
        $cotacao_data['itens'][] = $item;
        $total += $item['valor_final'];
    }
    $cotacao_data['valor_total_calculado'] = $total; // Adiciona o total calculado
    $stmtItens->close();

    return $cotacao_data;
}


// A. Recuperar a cotação principal (a vencedora)
$cotacao = getFullCotacaoById($conn, $cotacao_id);

if (!$cotacao) {
    die("Cotação com ID {$cotacao_id} não encontrada.");
}
$valor_total_pedido = $cotacao['valor_total_calculado'];

// B. Buscar dados da OS, Obra e Contrato relacionados (se houver)
$os = null;
$obra = null;
$contrato = null;

if (!empty($cotacao['os_id'])) {
    $stmtOS = $conn->prepare("SELECT * FROM ordem_de_servico WHERE id = ?");
    $stmtOS->bind_param("i", $cotacao['os_id']);
    $stmtOS->execute();
    $os = $stmtOS->get_result()->fetch_assoc();
    $stmtOS->close();
}
if (!empty($cotacao['obra_id'])) {
    $stmtObra = $conn->prepare("SELECT * FROM obras WHERE id = ?");
    $stmtObra->bind_param("i", $cotacao['obra_id']);
    $stmtObra->execute();
    $obra = $stmtObra->get_result()->fetch_assoc();
    $stmtObra->close();
}
if ($obra && !empty($obra['contrato_id'])) {
    $stmtContrato = $conn->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmtContrato->bind_param("i", $obra['contrato_id']);
    $stmtContrato->execute();
    $contrato = $stmtContrato->get_result()->fetch_assoc();
    $stmtContrato->close();
}

// C. Buscar registro de auditoria de aprovação
$aprovacao_info = null;
if (isset($cotacao['id'])) {
    try {
        $stmt_aprovacao = $pdo->prepare("
            SELECT nome_aprovador, cargo_aprovador, data_hora_aprovacao, endereco_ip, id_auditoria, hash_cotacao
            FROM auditoria_aprovacao_cotacao
            WHERE id_cotacao = ?
            ORDER BY data_hora_aprovacao DESC
            LIMIT 1
        ");
        $stmt_aprovacao->execute([$cotacao['id']]);
        $aprovacao_info = $stmt_aprovacao->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar registro de auditoria para cotação " . $cotacao['id'] . ": " . $e->getMessage());
    }
}

// D. BUSCAR E ORGANIZAR TODAS AS COTAÇÕES PARA COMPARAÇÃO (LÓGICA REFINADA)
$todas_as_cotacoes = [$cotacao];
if (!empty($cotacao['sc_id'])) {
    $stmtParalelas = $conn->prepare("SELECT id FROM cotacao WHERE sc_id = ? AND id != ?");
    $stmtParalelas->bind_param("ii", $cotacao['sc_id'], $cotacao_id);
    $stmtParalelas->execute();
    $resultParalelas = $stmtParalelas->get_result();
    while ($row = $resultParalelas->fetch_assoc()) {
        $cotacao_paralela_completa = getFullCotacaoById($conn, $row['id']);
        if ($cotacao_paralela_completa) {
            $todas_as_cotacoes[] = $cotacao_paralela_completa;
        }
    }
    $stmtParalelas->close();
}

// Estrutura para a análise item a item
$analise_por_insumo = [];
$lista_insumos_unicos = [];

foreach ($todas_as_cotacoes as $c) {
    foreach ($c['itens'] as $item) {
        $insumo_id = $item['insumo_id'];
        if (!isset($lista_insumos_unicos[$insumo_id])) {
            $lista_insumos_unicos[$insumo_id] = $item['insumo_nome'];
        }
        $analise_por_insumo[$insumo_id][$c['id']] = $item;
    }
}


/**
 * 4. CONFIGURAÇÃO E GERAÇÃO DO PDF
 */

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L', // Paisagem para caber mais colunas
    'margin_top' => 45,
    'margin_bottom' => 25,
    'margin_header' => 8,
    'margin_footer' => 8,
]);

// Dados da empresa
$empresa_nome = "Axel Construcoes e Projetos";
$empresa_cnpj = "24.970.772/0002-03";
$empresa_endereco = "Av. das Torres, 1234 - Flores, Manaus - AM";
$empresa_contato = "compras@axelconstrucoes.com.br";
$caminho_logo_empresa = __DIR__ . '/../assets/logo/Imagem1.png';
$caminho_logo_sistema = __DIR__ . '/../assets/logo/il_fullxfull.2974258879_pxm3.webp';

// Formatação de dados
$numero_pedido = 'PC #' . str_pad($cotacao['id'], 6, '0', STR_PAD_LEFT);
$data_emissao = date('d/m/Y');

// HEADER
$header = '
<table width="100%" style="font-family: Arial, sans-serif; border-collapse: collapse;">
    <tr>
        <td width="25%" style="border: 1px solid #555; border-right:none; padding: 8px; text-align: left; vertical-align: middle;">
            <img src="' . $caminho_logo_empresa . '" style="height: 40px; width: 130px;" />
        </td>
        <td width="50%" style="border: 1px solid #555; border-left:none; border-right:none; padding: 8px; text-align: center; vertical-align: middle;">
            <div style="font-size: 13pt; font-weight: bold; color: #333;">Pedido de Compra</div>
        </td>
        <td width="25%" style="border: 1px solid #555; padding: 8px; border-left:none; text-align: center; vertical-align: middle;">
            <strong style="font-size: 6pt; vertical-align: middle;">Zion Corporative</strong>
            <img src="' . $caminho_logo_sistema . '" style="height: 45px; width: auto; vertical-align: middle; padding-left: 10px;" />
        </td>
    </tr>
    <tr>
        <td colspan="3" style="border: 1px solid #555; padding: 8px; font-size: 9pt; color: #333; text-align: center;">
            <strong>' . htmlspecialchars($empresa_nome) . '</strong> | CNPJ: ' . htmlspecialchars($empresa_cnpj) . ' | Endereço: ' . htmlspecialchars($empresa_endereco) . '
        </td>
    </tr>
    <tr>
        <td colspan="3" style="border: 1px solid #555; padding: 6px 10px; font-size: 10pt; color: #333; background-color: #f2f2f2;">
            <table width="100%" style="font-size: 10pt;">
                <tr>
                    <td style="text-align: left;"><strong>Nº do Pedido:</strong> ' . $numero_pedido . '</td>
                    <td style="text-align: right;"><strong>Data de Emissão:</strong> ' . $data_emissao . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>';

// FOOTER
$footer = '
<table width="100%" style="vertical-align: bottom; font-family: sans-serif; font-size: 8pt; color: #555555;">
    <tr>
        <td width="50%">Documento gerado em: ' . date('d/m/Y H:i:s') . '</td>
        <td width="50%" style="text-align: right;">Página {PAGENO} de {nbpg}</td>
    </tr>
</table>';

$mpdf->SetHTMLHeader($header);
$mpdf->SetHTMLFooter($footer);

// Montagem do HTML do corpo do PDF
$html = '
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: sans-serif; font-size: 9pt; color: #333; }
    table.layout { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    table.layout td, table.layout th { border: 1px solid #555; padding: 6px; vertical-align: top; text-align: left; }
    .th-header { background-color: #E0E0E0; font-weight: bold; text-align: center; font-size: 11pt; }
    .label { font-weight: bold; }
    table.items thead th { font-weight: bold; background-color: #F2F2F2; font-size: 8.5pt; }
    table.items tbody td { font-size: 8.5pt; }
    table.items tfoot td { font-weight: bold; background-color: #F2F2F2; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .no-border { border: none; }
    .page-break { page-break-before: always; }
    
    /* Estilos para a nova tabela de comparação */
    table.comparativo { font-size: 8pt; }
    table.comparativo th { background-color: #e9ecef; }
    table.comparativo .insumo-col { width: 25%; }
    .vencedor-cell { background-color: #d4edda !important; font-weight: bold; color: #155724; }
</style>
</head>
<body>
';

// Tabela de Informações Adicionais
if ($obra || $contrato || $os) {
    $html .= '
    <table class="layout">
        <thead><tr><th class="th-header" colspan="2">INFORMAÇÕES ADICIONAIS</th></tr></thead>
        <tbody>';
    if ($contrato) $html .= '<tr><td class="label" style="width: 20%;">Contrato:</td><td>#' . htmlspecialchars($contrato['numero_contrato']) . '</td></tr>';
    if ($obra) $html .= '<tr><td class="label">Obra:</td><td>' . htmlspecialchars($obra['nome']) . '</td></tr>';
    if ($os) $html .= '<tr><td class="label">O.S:</td><td>#' . htmlspecialchars($os['id']) . ' - ' . htmlspecialchars($os['descricao']) . '</td></tr>';
    $html .= '
        </tbody>
    </table>';
}

// Tabela de ITENS DO PEDIDO
$html .= '
    <table class="layout items">
        <thead>
            <tr><th class="th-header" colspan="7">ITENS DO PEDIDO (COTAÇÃO VENCEDORA: #' . $cotacao['id'] . ')</th></tr>
            <tr>
                <th>INSUMO / SERVIÇO</th>
                <th style="width: 20%;">FORNECEDOR</th>
                <th class="text-center" style="width: 8%;">UNID.</th>
                <th class="text-right" style="width: 8%;">QTD.</th>
                <th class="text-right" style="width: 12%;">VALOR UNIT.</th>
                <th class="text-center" style="width: 8%;">DESC. (R$)</th>
                <th class="text-right" style="width: 12%;">VALOR FINAL</th>
            </tr>
        </thead>
        <tbody>';

foreach ($cotacao['itens'] as $item) {
    $html .= '
            <tr>
                <td>' . htmlspecialchars($item['insumo_nome']) . '</td>
                <td>' . htmlspecialchars($item['fornecedor_nome']) . '</td>
                <td class="text-center">' . htmlspecialchars($item['und_medida']) . '</td>
                <td class="text-right">' . number_format($item['quantidade'], 2, ',', '.') . '</td>
                <td class="text-right">R$ ' . number_format($item['valor_item'], 2, ',', '.') . '</td>
                <td class="text-center">R$ ' . number_format($item['desconto'], 2, ',', '.') . '</td>
                <td class="text-right">R$ ' . number_format($item['valor_final'], 2, ',', '.') . '</td>
            </tr>';
}

$html .= '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><strong>VALOR TOTAL DO PEDIDO:</strong></td>
                <td class="text-right"><strong>R$ ' . number_format($valor_total_pedido, 2, ',', '.') . '</strong></td>
            </tr>
        </tfoot>
    </table>';

// SEÇÃO DE QUADRO COMPARATIVO DETALHADO (NOVO)
if (count($todas_as_cotacoes) > 1) {
    $html .= '<div class="page-break"></div>';
    $html .= '<h2 style="text-align:center; font-size: 14pt; color: #333;">Análise Comparativa de Preços por Item</h2>';

    $html .= '<table class="layout items comparativo"><thead><tr>';
    $html .= '<th class="insumo-col">INSUMO / SERVIÇO</th>';
    foreach ($todas_as_cotacoes as $c) {
        $html .= '<th class="text-center">COTAÇÃO #' . $c['id'] . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($lista_insumos_unicos as $insumo_id => $insumo_nome) {
        // Encontrar o menor valor para este insumo
        $min_valor = PHP_FLOAT_MAX;
        $vencedor_cotacao_id = null;
        if (isset($analise_por_insumo[$insumo_id])) {
            foreach ($analise_por_insumo[$insumo_id] as $cotacao_id_analise => $item_analise) {
                if ($item_analise['valor_final'] < $min_valor) {
                    $min_valor = $item_analise['valor_final'];
                    $vencedor_cotacao_id = $cotacao_id_analise;
                }
            }
        }
        
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($insumo_nome) . '</td>';

        foreach ($todas_as_cotacoes as $c) {
            $cell_class = '';
            $cell_content = 'N/A'; // Padrão se o item não existir na cotação

            if (isset($analise_por_insumo[$insumo_id][$c['id']])) {
                $item_atual = $analise_por_insumo[$insumo_id][$c['id']];
                if ($c['id'] == $vencedor_cotacao_id) {
                    $cell_class = 'vencedor-cell';
                }
                $cell_content = '<strong>R$ ' . number_format($item_atual['valor_final'], 2, ',', '.') . '</strong><br>';
                $cell_content .= '<small>(' . htmlspecialchars($item_atual['fornecedor_nome']) . ')</small>';
            }

            $html .= '<td class="text-center ' . $cell_class . '">' . $cell_content . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
}


$html .= '
    <table class="layout no-border" style="margin-top: 40px; page-break-inside: avoid;">
        <tbody>
            <tr>
                <td class="no-border" style="width: 50%; text-align: center; padding-top: 40px;">';

// Lógica para as assinaturas
if (isset($cotacao['status']) && $cotacao['status'] === 'aprovado') {
    $html .= '
                    <p style="margin-bottom: 5px;">' . htmlspecialchars($cotacao['cotante'] ?? '#') . '</p>
                    _______________________________________<br>
                    <br>
                    <strong>Solicitante</strong>
                </td>
                <td class="no-border" style="width: 50%; text-align: center; padding-top: 30px;">';

    if ($aprovacao_info) {
        $data_hora_formatada = (new DateTime($aprovacao_info['data_hora_aprovacao']))->format('d/m/Y H:i:s');
        $html .= '
                    <div style="width: fit-content; text-align: left; font-size: 7.5pt; line-height: 1.2; color: #333; font-family: Arial, sans-serif;">
                        <span style="font-weight: normal;">' . htmlspecialchars($aprovacao_info['nome_aprovador']) . '</span><br>
                        <span style="font-weight: normal;">' . $data_hora_formatada . '</span><br>
                        <span style="font-weight: normal; font-size: 5.5pt;">' . htmlspecialchars($aprovacao_info['hash_cotacao']) . '</span>
                    </div>
                    <p style="margin-bottom: 5px; margin-top: 10px;"></p>
                    _______________________________________<br>
                    <br>
                    <strong style="font-size: 9pt;">Autorizado Eletronicamente</strong>';
    } else {
        $html .= '
                    <p style="margin-bottom: 5px;">Aprovado eletronicamente</p> 
                    _______________________________________<br>
                    <br>
                    <strong>Autorizado Eletronicamente</strong>';
    }
} else {
    $html .= '
                    <p style="margin-bottom: 5px;">' . htmlspecialchars($cotacao['cotante'] ?? '#') . '</p>
                    _______________________________________<br>
                    <br>
                    <strong>Solicitante</strong>
                </td>
                <td class="no-border" style="width: 50%; text-align: center; padding-top: 40px;">
                    <p style="margin-bottom: 5px;"></p>
                    _______________________________________<br>
                    <br>
                    <strong>Autorizado Por</strong>';
}

$html .= '
                </td>
            </tr>
        </tbody>
    </table>';

if (isset($cotacao['status']) && $cotacao['status'] === 'aprovado') {
    $html .= '
    <p style="text-align: center; margin-top: 20px; font-weight: bold; color: green;">
        &#10003; Este pedido foi aprovado pelo Sistema.
    </p>';
}

$html .= '
</body>
</html>';

$mpdf->WriteHTML($html);
$mpdf->Output('Pedido_Compra_' . $cotacao['id'] . '.pdf', 'I'); // 'I' para abrir no navegador

exit;
