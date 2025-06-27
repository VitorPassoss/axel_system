
<?php
// É crucial iniciar a sessão ANTES de qualquer output para acessar $_SESSION
session_start();

/**
 * 1. INICIALIZAÇÃO E DEPENDÊNCIAS
 */
// Carrega a biblioteca mPDF via Composer
require_once __DIR__ . '/../vendor/autoload.php';
// Inclui o arquivo de conexão com o banco de dados
include '../backend/dbconn.php';


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

// Validação do ID da Solicitação de Compra
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("ID da Solicitação de Compra inválido ou não fornecido.");
}
$solicitacao_id = intval($_GET['id']);

// Validação do ID da Empresa a partir da sessão
if (!isset($_SESSION['empresa_id'])) {
    die("ID da empresa não definido na sessão. Não é possível continuar.");
}
$empresa_id = $_SESSION['empresa_id'];

/**
 * 3. LÓGICA DE BUSCA DE DADOS DO BANCO (BACKEND REAL)
 */

// A. Recuperar a solicitação de compras principal
// ALTERADO: A consulta foi simplificada para remover os LEFT JOINs com a tabela 'usuarios'.
// Agora, selecionamos todas as colunas diretamente de 'solicitacao_compras'.
$stmtSC = $conn->prepare("SELECT * FROM solicitacao_compras WHERE id = ? ");
$stmtSC->bind_param("i", $solicitacao_id);
$stmtSC->execute();
$resultSC = $stmtSC->get_result();
$sc = $resultSC->fetch_assoc();
$stmtSC->close();

// Se a solicitação não for encontrada, encerra a execução
if (!$sc) {
    die("Solicitação de Compra com ID {$solicitacao_id} não encontrada ou não pertence à sua empresa.");
}

// O restante da lógica de busca de dados (itens, insumos, OS, etc.) permanece o mesmo.

// B. Buscar itens da solicitação
$stmtItens = $conn->prepare("SELECT * FROM sc_item WHERE solicitacao_id = ?");
$stmtItens->bind_param("i", $sc['id']);
$stmtItens->execute();
$resultItens = $stmtItens->get_result();
$sc['itens'] = [];
while ($item = $resultItens->fetch_assoc()) {
    $sc['itens'][] = $item;
}
$stmtItens->close();

// C. Buscar nomes dos insumos
$insumos_nomes = [];
$insumo_ids = array_column($sc['itens'], 'insumo_id');
if (!empty($insumo_ids)) {
    $placeholders = implode(',', array_fill(0, count($insumo_ids), '?'));
    $types = str_repeat('i', count($insumo_ids));
    $stmtInsumos = $conn->prepare("SELECT id, nome FROM insumos WHERE id IN ($placeholders)");
    $stmtInsumos->bind_param($types, ...$insumo_ids);
    $stmtInsumos->execute();
    $resultInsumos = $stmtInsumos->get_result();
    while ($row = $resultInsumos->fetch_assoc()) {
        $insumos_nomes[$row['id']] = $row['nome'];
    }
    $stmtInsumos->close();
}

// D. Buscar dados da OS, Obra e Contrato relacionados
$os = null;
$obra = null;
$contrato = null;

if (!empty($sc['os_id'])) {
    $stmtOS = $conn->prepare("SELECT * FROM ordem_de_servico WHERE id = ? AND empresa_id = ?");
    $stmtOS->bind_param("ii", $sc['os_id'], $empresa_id);
    $stmtOS->execute();
    $os = $stmtOS->get_result()->fetch_assoc();
    $stmtOS->close();

    if ($os && !empty($os['obra_id'])) {
        $stmtObra = $conn->prepare("SELECT * FROM obras WHERE id = ? AND empresa_id = ?");
        $stmtObra->bind_param("ii", $os['obra_id'], $empresa_id);
        $stmtObra->execute();
        $obra = $stmtObra->get_result()->fetch_assoc();
        $stmtObra->close();
    }
    if ($obra && !empty($obra['contrato_id'])) {
        $stmtContrato = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
        $stmtContrato->bind_param("ii", $obra['contrato_id'], $empresa_id);
        $stmtContrato->execute();
        $contrato = $stmtContrato->get_result()->fetch_assoc();
        $stmtContrato->close();
    }
}

/**
 * 4. CONFIGURAÇÃO E GERAÇÃO DO PDF
 */

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'margin_top' => 45,
    'margin_bottom' => 25,
    'margin_header' => 8,
    'margin_footer' => 8,
]);

// Dados da empresa (podem vir do banco ou serem fixos)
$empresa_nome = "Axel Construcoes e Projetos";
$empresa_cnpj = "24.970.772/0002-03";
$empresa_endereco = "Av. das Torres, 1234 - Flores, Manaus - AM";
$empresa_contato = "compras@axelconstrucoes.com.br";

// Caminhos para as logos
$caminho_logo_empresa = __DIR__ . '/../assets/logo/Imagem1.png'; // Logo principal
// ATENÇÃO: Verifique se este caminho para a logo do sistema está correto.
$caminho_logo_sistema = __DIR__ . '/../assets/logo/il_fullxfull.2974258879_pxm3.webp';

// Formatação de dados para o header
$numero_sc = 'SC #' . str_pad($sc['id'], 6, '0', STR_PAD_LEFT);
$data_emissao = date('d/m/Y');


// NOVO HEADER - ESTRUTURA DE TABELA COM BORDAS CONECTADAS
$header = '
<table width="100%" style="font-family: Arial, sans-serif; border-collapse: collapse;">
    <tr>
        <td width="25%" style="border: 1px solid #555; border-right:none; padding: 8px; text-align: left; vertical-align: middle;">
            <img src="' . $caminho_logo_empresa . '" style="height: 40px; width: 130px;" />
        </td>

        <td width="50%" style="border: 1px solid #555; border-left:none; border-right:none; padding: 8px; text-align: center; vertical-align: middle;">
            <div style="font-size: 13pt; font-weight: bold; color: #333;">Solicitação de Compra</div>
        </td>



       <td width="25%" style="border: 1px solid #555; padding: 8px; border-left:none; text-align: center; vertical-align: middle;">
        <strong style="font-size: 6pt; vertical-align: middle;">Zion Corporative</strong>
        <img src="' . $caminho_logo_sistema . '" style="height: 45px; width: auto; vertical-align: middle; padding-left: 10px;" />
    </td>
    </tr>

    <tr>
        <td colspan="3" style="border: 1px solid #555; padding: 8px; font-size: 9pt; color: #333; text-align: center;">
            <strong>' . htmlspecialchars($empresa_nome) . '</strong> | 
            CNPJ: ' . htmlspecialchars($empresa_cnpj) . ' | 
            Endereço: ' . htmlspecialchars($empresa_endereco) . ' | 
            Contato: ' . htmlspecialchars($empresa_contato) . '
        </td>
    </tr>

     <tr>
        <td colspan="3" style="border: 1px solid #555; padding: 6px 10px; font-size: 10pt; color: #333; background-color: #f2f2f2;">
            <table width="100%" style="font-size: 10pt;">
                <tr>
                    <td style="text-align: left;"><strong>Nº da Solicitação:</strong> ' . $numero_sc . '</td>
                    <td style="text-align: right;"><strong>Data de Emissão:</strong> ' . $data_emissao . '</td>
                </tr>
            </table>
        </td>
    </tr>
</table>';

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
    body { font-family: sans-serif; font-size: 10pt; color: #333; }
    table.layout { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    table.layout td, table.layout th { border: 1px solid #333; padding: 8px; vertical-align: top; text-align: left; }
    .th-header { background-color: #E0E0E0; font-weight: bold; text-align: center; font-size: 11pt; }
    .label { font-weight: bold; width: 1%; white-space: nowrap; }
    h1 { text-align: center; font-size: 16pt; margin-bottom: 8px; text-transform: uppercase; border-bottom: 2px solid #555; padding-bottom: 5px; }
    table.items thead th { font-weight: bold; background-color: #F2F2F2; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .no-border { border: none; }
</style>
</head>
<body>

    ';

if ($obra || $contrato || $os) {
    $html .= '
    <table class="layout">
        <thead><tr><th class="th-header" colspan="2">INFORMAÇÕES ADICIONAIS</th></tr></thead>
        <tbody>';
    if ($os) $html .= '<tr><td class="label" style="width: 150px;">Ordem de Serviço:</td><td>#' . htmlspecialchars($os['id']) . ' - ' . htmlspecialchars($os['descricao']) . '</td></tr>';
    if ($obra) $html .= '<tr><td class="label">Obra:</td><td>' . htmlspecialchars($obra['nome']) . '</td></tr>';
    if ($contrato) $html .= '<tr><td class="label">Contrato:</td><td>#' . htmlspecialchars($contrato['numero_contrato']) . '</td></tr>';
    $html .= '
        </tbody>
    </table>';
}

$html .= '
    <table class="layout items">
        <thead>
            <tr><th class="th-header" colspan="5">ITENS DA SOLICITAÇÃO</th></tr>
            <tr>
                <th class="text-center" style="width: 10%;">CÓD.</th>
                <th>INSUMO / SERVIÇO</th>
                <th class="text-center" style="width: 10%;">UNID.</th>
                <th class="text-right" style="width: 15%;">QTD.</th>
                <th style="width: 20%;">GRAU</th>
            </tr>
        </thead>
        <tbody>';

foreach ($sc['itens'] as $item) {
    $nome_insumo = $insumos_nomes[$item['insumo_id']] ?? 'Insumo não encontrado';
    $html .= '
            <tr>
                <td class="text-center">' . htmlspecialchars($item['insumo_id']) . '</td>
                <td>' . htmlspecialchars($nome_insumo) . '</td>
                <td class="text-center">' . htmlspecialchars($item['und_medida']) . '</td>
                <td class="text-right">' . htmlspecialchars($item['quantidade']) . '</td>
                <td>' . htmlspecialchars($item['grau']) . '</td>
            </tr>';
}
// Montagem do HTML do corpo do PDF
$html = '
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: sans-serif; font-size: 10pt; color: #333; }
    table.layout { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.layout td, table.layout th { border: 1px solid #333; padding: 8px; vertical-align: top; text-align: left; }
    .th-header { background-color: #E0E0E0; font-weight: bold; text-align: center; font-size: 11pt; }
    
    /* ALTERADO: Simplificamos a classe .label. A largura agora será definida diretamente na célula (td). */
    .label { font-weight: bold; }
    
    h1 { text-align: center; font-size: 16pt; margin-bottom: 0px; text-transform: uppercase; border-bottom: 2px solid #555; padding-bottom: 0px; }
    table.items thead th { font-weight: bold; background-color: #F2F2F2; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .no-border { border: none; }
</style>
</head>
<body>
';

// Adiciona a tabela de Obra/Contrato apenas se os dados existirem
if ($obra || $contrato || $os) {
    $html .= '
    <table class="layout">
        <thead><tr><th class="th-header" colspan="2">INFORMAÇÕES ADICIONAIS</th></tr></thead>
        <tbody>';

    // ALTERADO: Definimos larguras fixas para as colunas para garantir o alinhamento. (20% + 80% = 100%)
    if ($contrato) $html .= '<tr><td class="label">Contrato:</td><td>#' . htmlspecialchars($contrato['numero_contrato']) . '</td></tr>';
    if ($obra) $html .= '<tr><td class="label">Obra:</td><td>' . htmlspecialchars($obra['nome']) . '</td></tr>';
    if ($os) $html .= '<tr><td class="label" style="width: 20%;">O.S:</td><td style="width: 80%;">#' . htmlspecialchars($os['id']) . ' - ' . htmlspecialchars($os['descricao']) . '</td></tr>';

    $html .= '
        </tbody>
    </table>';
}

// A tabela de itens já estava perfeita, então permanece igual.
$html .= '
    <table class="layout items">
        <thead>
            <tr><th class="th-header" colspan="5">ITENS DA SOLICITAÇÃO</th></tr>
            <tr>
                <th class="text-center" style="width: 10%;">CÓD.</th>
                <th>INSUMO / SERVIÇO</th>
                <th class="text-center" style="width: 10%;">UNID.</th>
                <th class="text-right" style="width: 15%;">QTD.</th>
                <th style="width: 20%;">GRAU</th>
            </tr>
        </thead>
        <tbody>';

foreach ($sc['itens'] as $item) {
    $nome_insumo = $insumos_nomes[$item['insumo_id']] ?? 'Insumo não encontrado';
    $html .= '
            <tr>
                <td class="text-center">' . htmlspecialchars($item['insumo_id']) . '</td>
                <td>' . htmlspecialchars($nome_insumo) . '</td>
                <td class="text-center">' . htmlspecialchars($item['und_medida']) . '</td>
                <td class="text-right">' . htmlspecialchars($item['quantidade']) . '</td>
                <td>' . htmlspecialchars($item['grau']) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <table class="layout no-border" style="margin-top: 0px;">
        <tbody>
             <tr>
                <td class="no-border" style="width: 50%; text-align: center; padding-top: 40px;">
                    _______________________________________<br>
                    ' . htmlspecialchars($sc['solicitante'] ?? 'Não informado') . '<br>
                    <strong>Solicitante</strong>
                </td>
                <td class="no-border" style="width: 50%; text-align: center; padding-top: 40px;">
                    _______________________________________<br>
                    ' . (!empty($sc['aprovado_por']) ? htmlspecialchars($sc['aprovado_por']) : '(Aprovação Pendente)') . '<br>
                    <strong>Aprovação</strong>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>';
$mpdf->WriteHTML($html);
$mpdf->Output('Solicitacao_Compra_' . $sc['id'] . '.pdf', 'I');

exit;
