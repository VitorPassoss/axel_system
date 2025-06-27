<?php
include '../layout/imports.php';

session_start();

// Verifica se o usuário está logado
function verificarAutenticacao()
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: ../onboard/login.php");
        exit();
    }
}
verificarAutenticacao();

// Conexão com o banco de dados
include '../backend/dbconn.php';
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Certifique-se de que empresa_id está definido++
if (!isset($_SESSION['empresa_id'])) {
    die("empresa_id não está definida na sessão.");
}
$empresa_id = $_SESSION['empresa_id'];



if (isset($_GET['sc_id'])) {
    $sc_id = intval($_GET['sc_id']);

    // Recuperar a solicitação de compras específica
    $stmtSC = $conn->prepare("SELECT * FROM solicitacao_compras WHERE id = ? ");
    $stmtSC->bind_param("i", $sc_id);
    $stmtSC->execute();
    $resultSC = $stmtSC->get_result();
    $sc = $resultSC->fetch_assoc();
    $stmtSC->close();

    if ($sc) {
        // Buscar itens da solicitação
        $stmtItens = $conn->prepare("SELECT * FROM sc_item WHERE solicitacao_id = ?");
        $stmtItens->bind_param("i", $sc['id']);
        $stmtItens->execute();
        $resultItens = $stmtItens->get_result();
        $sc['itens'] = [];

        while ($item = $resultItens->fetch_assoc()) {
            $sc['itens'][] = $item;
        }
        $stmtItens->close();

        // Buscar dados da OS relacionada
        $stmtOS = $conn->prepare("SELECT * FROM ordem_de_servico WHERE id = ? AND empresa_id = ?");
        $stmtOS->bind_param("ii", $sc['os_id'], $empresa_id);
        $stmtOS->execute();
        $resultOS = $stmtOS->get_result();
        $os = $resultOS->fetch_assoc();
        $stmtOS->close();

        if ($os) {
            // Buscar obra
            $stmtObra = $conn->prepare("SELECT * FROM obras WHERE id = ? AND empresa_id = ?");
            $stmtObra->bind_param("ii", $os['obra_id'], $empresa_id);
            $stmtObra->execute();
            $resultObra = $stmtObra->get_result();
            $obra = $resultObra->fetch_assoc();
            $stmtObra->close();

            // Buscar contrato
            if ($obra) {
                $stmtContrato = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
                $stmtContrato->bind_param("ii", $obra['contrato_id'], $empresa_id);
                $stmtContrato->execute();
                $resultContrato = $stmtContrato->get_result();
                $contrato = $resultContrato->fetch_assoc();
                $stmtContrato->close();
            }
        }
    }
}

if (isset($_GET['sc_id'])) {
    $sc_id = intval($_GET['sc_id']);

    // Busca cotação vinculada à solicitação
    $stmtCotacao = $conn->prepare("SELECT * FROM cotacao WHERE sc_id = ?");
    $stmtCotacao->bind_param("i", $sc_id);
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
        $stmtItens->bind_param("i", $cotacao['id']);
        $stmtItens->execute();
        $resultItens = $stmtItens->get_result();
        $cotacao['itens'] = [];

        while ($item = $resultItens->fetch_assoc()) {
            $cotacao['itens'][] = $item;
        }
        $stmtItens->close();

        // 👉 Adiciona a cotação com os itens completos
        $cotacoes[] = $cotacao;
    }

    $stmtCotacao->close();
}



// Busca fornecedores da empresa
$stmtFornecedores = $conn->prepare("SELECT id, nome_fantasia FROM fornecedores;");
$stmtFornecedores->execute();
$fornecedores = $stmtFornecedores->get_result();

// Buscar nomes dos insumos usando mysqli
$insumos_nomes = [];
$insumo_ids = array_column($sc['itens'], 'insumo_id');
$insumo_ids = array_unique($insumo_ids);

if (!empty($insumo_ids)) {
    // Monta os placeholders (?, ?, ?...)
    $placeholders = implode(',', array_fill(0, count($insumo_ids), '?'));
    $sql = "SELECT id, nome FROM insumos WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);

    // Monta os tipos dinamicamente
    $types = str_repeat('i', count($insumo_ids));

    // Faz o bind dinâmico
    $stmt->bind_param($types, ...$insumo_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    // Associa id => nome
    while ($row = $result->fetch_assoc()) {
        $insumos_nomes[$row['id']] = $row['nome'];
    }

    $stmt->close();
}



$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        u.id, u.email, u.setor_id, u.empresa_id,
        s.nome AS setor_nome,
        e.nome AS empresa_nome
    FROM users u
    LEFT JOIN setores s ON u.setor_id = s.id
    LEFT JOIN empresas e ON u.empresa_id = e.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rest = $stmt->get_result();
$usuario = $rest->fetch_assoc();

$bancos = $conn->query("SELECT id, nome FROM bancos");
$categorias = $conn->query("SELECT id, nome FROM categorias");


?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Área de Cotação</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"><!-- Font Awesome via CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <link rel="icon" type="image/png" href="../assets/logo/il_fullxfull.2974258879_pxm3.webp">


    <style>
        * {
            font-family: "Poppins", sans-serif;
            font-style: normal;
        }
    </style>


    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#171717', // blue-500
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
    <!-- Side Menu -->
    <?php include '../layout/sidemenu.php'; ?>
    <div class="w-full ">
        <div class="relative  p-8 animate-fadeIn">




            <header class="bg-white rounded-2xl shadow-lg p-6 mb-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Título e botão voltar -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <button onclick="window.location.href='../contratos'" class="text-gray-600 hover:text-primary transition self-start sm:self-auto">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">
                        Área de Cotação
                    </h1>
                </div>

                <!-- Botões de ações -->
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">

                    <?php
                    if (isset($_GET['sc_id'])) {
                        $sc_id = $_GET['sc_id'];

                        // Preparar a consulta
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cotacao WHERE sc_id = ? AND status != 'rejeitado'");
                        $stmt->bind_param("i", $sc_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();

                        // Se não há nenhuma cotação com status diferente de 'rejeitado', mostrar botão
                        if ($row['total'] == 0 && $usuario['setor_nome'] === 'Compras'):
                    ?>


                            <button
                                onclick="window.location.href = './form.php?sc_id=<?= $sc_id ?>'"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                                Realizar nova cotação
                            </button>
                    <?php
                        endif;
                    }
                    ?>



                </div>
            </header>

            <div class="bg-white border border-gray-300 rounded-2xl shadow-sm p-6 mb-8">
                <h3 class="text-xl font-bold text-primary mb-6">📋 Resumo da Solicitação #<?= htmlspecialchars($sc['id']) ?></h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-neutral-800 mb-6">
                    <div>
                        <span class="font-medium text-gray-500">ID:</span>
                        <a class="underline ml-1">
                            <?= htmlspecialchars($sc['id']) ?>
                        </a>
                    </div>

                    <div>
                        <span class="font-medium text-gray-500">OS ID:</span>
                        <a href="../os/detalhes.php?sc_id=<?= htmlspecialchars($sc['os_id']) ?>" class="text-blue-600 underline ml-1">
                            <?= htmlspecialchars($sc['os_id']) ?>
                        </a>
                    </div>

                    <div>
                        <span class="font-medium text-gray-500">Solicitante:</span>
                        <span class="ml-1"><?= htmlspecialchars($sc['solicitante']) ?></span>
                    </div>

                    <div>
                        <span class="font-medium text-gray-500">Status:</span>
                        <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold ml-1 <?= $sc['status'] === 'aprovado' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                            <?= htmlspecialchars(ucfirst($sc['status'])) ?>
                        </span>
                    </div>



                    <div>
                        <span class="font-medium text-gray-500">Aprovado por:</span>
                        <span class="ml-1"><?= htmlspecialchars($sc['aprovado_por']) ?></span>
                    </div>

                    <div>
                        <span class="font-medium text-gray-500">Aprovado em:</span>
                        <span class="ml-1"><?= date('d/m/Y H:i', strtotime($sc['aprovado_em'])) ?></span>
                    </div>


                    <div class="md:col-span-3">
                        <span class="font-medium text-gray-500">Descrição:</span>
                        <span class="ml-1"><?= htmlspecialchars($sc['descricao']) ?></span>
                    </div>
                </div>

                <?php if (!empty($os)): ?>
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <h4 class="text-lg font-semibold text-primary mb-4">🔧 Ordem de Serviço</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-neutral-800">
                            <div><span class="font-medium text-gray-500">Número OS:</span> <?= htmlspecialchars($os['numero_os']) ?></div>
                            <div><span class="font-medium text-gray-500">Descrição:</span> <?= htmlspecialchars($os['descricao']) ?></div>
                            <div><span class="font-medium text-gray-500">Local:</span> <?= htmlspecialchars($os['local']) ?></div>
                            <div><span class="font-medium text-gray-500">Responsável:</span> <?= htmlspecialchars($os['responsavel_os']) ?></div>
                            <div><span class="font-medium text-gray-500">Data Início:</span> <?= date('d/m/Y', strtotime($os['data_inicio'])) ?></div>
                            <div><span class="font-medium text-gray-500">Data Final:</span> <?= date('d/m/Y', strtotime($os['data_final'])) ?></div>
                            <div>
                                <span class="font-medium text-gray-500">Status:</span>
                                <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold ml-1 <?= $os['status'] === 'concluída' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
                                    <?= htmlspecialchars(ucfirst($os['status'])) ?>
                                </span>
                            </div>
                            <div><span class="font-medium text-gray-500">Criado em:</span> <?= date('d/m/Y H:i', strtotime($os['criado_em'])) ?></div>

                            <?php if (!empty($contrato)): ?>
                                <div>
                                    <span class="font-medium text-gray-500">Contrato:</span>
                                    <a href="../contratos/detalhes.php?contrato_id=<?= htmlspecialchars($contrato['id']) ?>" class="text-blue-600 underline ml-1">
                                        #<?= htmlspecialchars($contrato['numero_contrato']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($obra)): ?>
                                <div>
                                    <span class="font-medium text-gray-500">Obra:</span>
                                    <a href="../Obras/detalhes.php?obra_id=<?= htmlspecialchars($obra['id']) ?>" class="text-blue-600 underline ml-1">
                                        <?= htmlspecialchars($obra['nome']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endif; ?>
                <div class="mt-8 text-right">
                    <a href="gerar_pdf_sc.php?id=<?= htmlspecialchars($sc['id']) ?>"
                        target="_blank"
                        class="inline-block bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors duration-300 shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="inline-block h-5 w-5 mr-2 -mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Emitir Solicitação de Compra (PDF)
                    </a>
                </div>
                <div id="dados-solicitacao" data-sc-id="<?= htmlspecialchars($sc['id']) ?>">

                    <h4 class="text-lg font-semibold text-primary mb-3 mt-6">📦 Itens da Solicitação</h4>

                    <div class="mb-4">
                        <button id="btn-iniciar-adicao" type="button" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-300 shadow-sm">
                            + Adicionar Novos Itens
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <form id="form-novos-itens">
                            <table class="min-w-full text-sm text-gray-800 border border-gray-300 rounded-md overflow-hidden">
                                <thead class="bg-gray-100 text-gray-700 font-semibold">
                                    <tr>
                                        <th class="border border-gray-200 px-4 py-2 text-left">Insumo</th>
                                        <th class="border border-gray-200 px-4 py-2 text-left">Unidade</th>
                                        <th class="border border-gray-200 px-4 py-2 text-left">Quantidade</th>
                                        <th class="border border-gray-200 px-4 py-2 text-left">Grau</th>
                                        <th class="border border-gray-200 px-6 py-2 text-left" style="width: 100px;">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sc['itens'] as $item): ?>
                                        <tr class="sc-item-row hover:bg-gray-50 transition-colors" data-item-id="<?= htmlspecialchars($item['id']) ?>">

                                            <td class="border-b px-4 py-2">
                                                <?= htmlspecialchars($insumos_nomes[$item['insumo_id']] ?? 'Insumo não encontrado') ?>
                                                <input type="hidden" name="insumo_id" value="<?= htmlspecialchars($item['insumo_id']) ?>">
                                            </td>

                                            <td class="border-b p-2">
                                                <input type="text" name="und_medida"
                                                    class="w-full rounded-md border-gray-300 p-2"
                                                    value="<?= htmlspecialchars($item['und_medida']) ?>"
                                                    oninput="mostrarBotaoSalvar(this)">
                                            </td>

                                            <td class="border-b p-2">
                                                <input type="number" name="quantidade"
                                                    class="w-full rounded-md border-gray-300 p-2"
                                                    value="<?= htmlspecialchars($item['quantidade']) ?>"
                                                    min="0.01" step="0.01"
                                                    oninput="mostrarBotaoSalvar(this)">
                                            </td>

                                            <td class="border-b p-2">
                                                <select name="grau" class="w-full rounded-md border-gray-300 p-2" oninput="mostrarBotaoSalvar(this)">
                                                    <?php
                                                    $graus = ["Sinistro", "Urgencia", "Alta", "Media", "Baixa", "Pouca"];
                                                    foreach ($graus as $grau_opcao) {
                                                        $selected = ($item['grau'] == $grau_opcao) ? 'selected' : '';
                                                        echo "<option value='{$grau_opcao}' {$selected}>{$grau_opcao}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </td>

                                            <td class="border-b   text-center flex p-2">
                                                <button type="button"
                                                    onclick="salvarAlteracoesItem(this, <?= htmlspecialchars($item['id']) ?>)"
                                                    class="btn-salvar-item bg-green-500 text-white px-8 py-2 rounded-md text-xs font-bold hover:bg-green-600 hidden"
                                                    title="Salvar alterações deste item">
                                                    Salvar
                                                </button>
                                                <button type="button"
                                                    onclick="excluirItem(this, <?= htmlspecialchars($item['id']) ?>)"
                                                    class="btn-excluir-item text-red-500 hover:text-red-700 text-lg ml-2"
                                                    title="Excluir este item">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tbody id="tbody-novos-itens">
                                </tbody>
                                <tfoot class="hidden">
                                    <tr id="template-novo-item">
                                        <td class="border border-gray-200 p-2">
                                            <input name="insumo_nome" list="insumos" class="w-full rounded-md border-gray-300 p-2" placeholder="Digite ou selecione" required disabled>
                                        </td>
                                        <td class="border border-gray-200 p-2">
                                            <input type="text" name="und_medida" class="w-full rounded-md border-gray-300 p-2" placeholder="Ex: kg, un" required disabled>
                                        </td>
                                        <td class="border border-gray-200 p-2">
                                            <input type="number" name="quantidade" class="w-full rounded-md border-gray-300 p-2" placeholder="Qtd" required min="0.01" step="0.01" disabled>
                                        </td>
                                        <td class="border border-gray-200 p-2">
                                            <select name="grau" required class="w-full rounded-md border-gray-300 p-2" disabled>
                                                <option value="" disabled selected>Selecione</option>
                                                <option value="Sinistro">🚨 Sinistro</option>
                                                <option value="Urgencia">🚨 Urgência</option>
                                                <option value="Alta">🔴 Alta</option>
                                                <option value="Media">🟠 Média</option>
                                                <option value="Baixa">🟡 Baixa</option>
                                                <option value="Pouca">🟢 Pouca</option>
                                            </select>
                                        </td>
                                        <td class="border border-gray-200 p-2 text-center">
                                            <button type="button" onclick="removerLinha(this)" class="text-red-500 hover:text-red-700 font-bold">X</button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                            <div id="acoes-novos-itens-<?= $cotacao['id'] ?>" class="mt-4 text-right hidden space-x-2">
                                <button onclick="adicionarLinhaCotacao(<?= $cotacao['id'] ?>)" type="button" class="bg-gray-200 text-gray-800 font-semibold py-2 px-4 rounded-lg hover:bg-gray-300">Adicionar Outra Linha</button>
                                <button onclick="cancelarAdicaoItemCotacao(<?= $cotacao['id'] ?>)" type="button" class="bg-red-100 text-red-700 font-semibold py-2 px-4 rounded-lg hover:bg-red-200">Cancelar</button>
                                <button onclick="salvarNovosItensCotacao(<?= $cotacao['id'] ?>)" type="button" class="bg-green-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-700">Salvar Itens</button>
                            </div>
                        </form>

                        <datalist id="insumos">
                            <?php
                            // **IMPORTANTE**: Modifique para incluir o ID do insumo no data-attribute
                            $sql = "SELECT id, nome FROM insumos ORDER BY nome";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['nome'], ENT_QUOTES) . "' data-id='" . $row['id'] . "'>";
                                }
                            }
                            ?>
                        </datalist>
                    </div>

                    <div id="acoes-novos-itens" class="mt-4 text-right hidden space-x-2">
                        <button id="btn-adicionar-linha" type="button" class="bg-gray-200 text-gray-800 font-semibold py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors">Adicionar Outro Item</button>
                        <button id="btn-cancelar-adicao" type="button" class="bg-red-100 text-red-700 font-semibold py-2 px-4 rounded-lg hover:bg-red-200 transition-colors">Cancelar</button>
                        <button id="btn-salvar-itens" type="button" class="bg-green-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">Salvar Novos Itens</button>
                    </div>

                </div>
            </div>


            <?php foreach ($cotacoes as $cotacao): ?>
                <?php
                // Verifica se a cotação tem ao menos uma transação
                $stmt_trans = $conn->prepare("SELECT COUNT(*) as total FROM transacoes WHERE cotacao_id = ?");
                $stmt_trans->bind_param("i", $cotacao['id']);
                $stmt_trans->execute();
                $result_trans = $stmt_trans->get_result();
                $temTransacao = false;
                if ($row = $result_trans->fetch_assoc()) {
                    $temTransacao = $row['total'] > 0;
                }
                $stmt_trans->close();
                ?>

                <div class="bg-white border border-gray-300 rounded-2xl shadow-sm p-6 mb-8">

                    <h3 class="text-xl font-bold text-primary mb-6">🧾 Resumo da Cotação #<?= htmlspecialchars($cotacao['id']) ?></h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 gap-x-6 text-sm text-neutral-800 mb-8">
                        <div><span class="font-medium text-gray-500">Cotante:</span> <?= htmlspecialchars($cotacao['cotante']) ?></div>
                        <div><span class="font-medium text-gray-500">Status:</span>
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold <?= $cotacao['status'] === 'aprovado' ? 'bg-green-100 text-green-700' : ($cotacao['status'] === 'rejeitado' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                <?= $temTransacao ? 'Pago' : htmlspecialchars(ucfirst($cotacao['status'])) ?>
                            </span>
                        </div>
                        <div><span class="font-medium text-gray-500">Criado em:</span> <?= date('d/m/Y H:i', strtotime($cotacao['dt_criado'])) ?></div>
                        <?php if ($cotacao['dt_aprovado']): ?>
                            <div><span class="font-medium text-gray-500">Aprovado em:</span> <?= date('d/m/Y H:i', strtotime($cotacao['dt_aprovado'])) ?></div>
                        <?php endif; ?>
                        <?php if ($cotacao['dt_rejeitado']): ?>
                            <div><span class="font-medium text-gray-500">Rejeitado em:</span> <?= date('d/m/Y H:i', strtotime($cotacao['dt_rejeitado'])) ?></div>
                            <div class="md:col-span-3"><span class="font-medium text-gray-500">Motivo da Rejeição:</span> <?= htmlspecialchars($cotacao['retorno_rej']) ?></div>
                        <?php endif; ?>
                        <div class="md:col-span-3"><span class="font-medium text-gray-500">Descrição:</span> <?= htmlspecialchars($cotacao['descricao']) ?></div>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="cotacao-container" data-cotacao-id="<?= htmlspecialchars($cotacao['id']) ?>">
                            <h4 class="text-lg font-semibold text-primary mb-3 mt-6">🛒 Itens da Cotação</h4>

                            <div class="mb-4">
                                <button onclick="iniciarAdicaoItemCotacao(<?= $cotacao['id'] ?>)"
                                    id="btn-iniciar-adicao-<?= $cotacao['id'] ?>"
                                    type="button"
                                    class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-300 shadow-sm">
                                    + Adicionar Item à Cotação
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm text-gray-800 border border-gray-300 rounded-md overflow-hidden">
                                    <thead class="bg-gray-100 text-gray-700 font-semibold">
                                        <tr>
                                            <th class="px-4 py-2 text-left">Insumo</th>
                                            <th class="px-4 py-2 text-left">Fornecedor</th>
                                            <th class="px-4 py-2 text-left">Qtd</th>
                                            <th class="px-4 py-2 text-left">Vlr. Unit.</th>
                                            <th class="px-4 py-2 text-left">Desconto</th>
                                            <th class="px-4 py-2 text-left">Vlr. Final</th>
                                            <th class="px-6 py-2 text-center" style="width: 100px;">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cotacao['itens'] as $item): ?>
                                            <tr class="cotacao-item-row hover:bg-gray-50 transition-colors" data-item-id="<?= htmlspecialchars($item['id']) ?>">
                                                <td class="border-b p-2">
                                                    <?= htmlspecialchars($item['insumo_nome']) ?>
                                                    <input type="hidden" name="insumo_id" value="<?= htmlspecialchars($item['insumo_id']) ?>">
                                                    <input type="hidden" name="fornecedor_id" value="<?= htmlspecialchars($item['fornecedor_id']) ?>">
                                                </td>
                                                <td class="border-b p-2">
                                                    <?= htmlspecialchars($item['fornecedor_nome']) ?>
                                                </td>
                                                <td class="border-b p-2">
                                                    <input type="number" name="quantidade"
                                                        class="w-full rounded-md border-gray-300 p-2"
                                                        value="<?= htmlspecialchars($item['quantidade']) ?>"
                                                        min="0.01" step="0.01"
                                                        oninput="mostrarBotaoSalvarCotacao(this)">
                                                </td>
                                                <td class="border-b p-2">
                                                    <input type="number" name="valor_item"
                                                        class="w-full rounded-md border-gray-300 p-2"
                                                        value="<?= htmlspecialchars($item['valor_item']) ?>"
                                                        min="0.01" step="0.01"
                                                        oninput="mostrarBotaoSalvarCotacao(this)">
                                                </td>
                                                <td class="border-b p-2">
                                                    <input type="number" name="desconto"
                                                        class="w-full rounded-md border-gray-300 p-2"
                                                        value="<?= htmlspecialchars($item['desconto']) ?>"
                                                        min="0" step="0.01"
                                                        oninput="mostrarBotaoSalvarCotacao(this)">
                                                </td>
                                                <td class="border-b p-2 valor-final">
                                                    R$ <?= number_format($item['valor_final'], 2, ',', '.') ?>
                                                </td>
                                                <td class="border-b text-center flex p-2">
                                                    <button type="button"
                                                        onclick="salvarAlteracoesItemCotacao(this, <?= htmlspecialchars($item['id']) ?>)"
                                                        class="btn-salvar-item-cotacao bg-green-500 text-white px-8 py-2 rounded-md text-xs font-bold hover:bg-green-600 hidden"
                                                        title="Salvar alterações deste item">
                                                        Salvar
                                                    </button>
                                                    <button type="button"
                                                        onclick="excluirItemCotacao(this, <?= htmlspecialchars($item['id']) ?>)"
                                                        class="btn-excluir-item text-red-500 hover:text-red-700 text-lg ml-2" title="Excluir Item">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>

                                    <tbody id="tbody-novos-itens-<?= $cotacao['id'] ?>"></tbody>
                                    <tfoot class="hidden">
                                        <tr id="template-novo-item-<?= $cotacao['id'] ?>">
                                            <td class="p-2">
                                                <input name="insumo_id" list="insumos-datalist" placeholder="Selecione o Insumo" class="w-full p-2 border rounded" required disabled>
                                            </td>
                                            <td class="p-2">
                                                <input name="fornecedor_id" list="fornecedores-datalist" placeholder="Selecione o Fornecedor" class="w-full p-2 border rounded" required disabled>
                                            </td>
                                            <td class="p-2">
                                                <input type="number" name="quantidade" placeholder="Qtd" class="w-20 p-2 border rounded" min="0.01" step="0.01" required disabled>
                                            </td>
                                            <td class="p-2">
                                                <input type="number" name="valor_item" placeholder="Valor" class="w-24 p-2 border rounded" min="0.01" step="0.01" required disabled>
                                            </td>
                                            <td class="p-2">
                                                <input type="number" name="desconto" placeholder="Desc." value="0" class="w-24 p-2 border rounded" min="0" step="0.01" required disabled>
                                            </td>
                                            <td class="p-2">
                                            </td>
                                            <td class="p-2 text-center">
                                                <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 font-bold" title="Remover Linha">X</button>
                                            </td>
                                        </tr>
                                    </tfoot>

                                </table>

                                <div id="acoes-novos-itens-<?= $cotacao['id'] ?>" class="mt-4 text-right hidden space-x-2">
                                    <button onclick="adicionarLinhaCotacao(<?= $cotacao['id'] ?>)" type="button" class="bg-gray-200 text-gray-800 font-semibold py-2 px-4 rounded-lg hover:bg-gray-300">Adicionar Outra Linha</button>
                                    <button onclick="cancelarAdicaoItemCotacao(<?= $cotacao['id'] ?>)" type="button" class="bg-red-100 text-red-700 font-semibold py-2 px-4 rounded-lg hover:bg-red-200">Cancelar</button>
                                    <button onclick="salvarNovosItensCotacao(<?= $cotacao['id'] ?>)" type="button" class="bg-green-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-700">Salvar Itens</button>
                                </div>
                            </div>

                            <div class="mt-8 text-right">
                                <a href="gerar_pedido_pdf.php?id=<?= htmlspecialchars($cotacao['id']) ?>"
                                    target="_blank"
                                    class="inline-block bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors duration-300 shadow-md">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="inline-block h-5 w-5 mr-2 -mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Emitir Pedido de Compra (PDF)
                                </a>
                            </div>
                        </div>

                    </div>


                    <datalist id="insumos-datalist">
                        <?php
                        $result_insumos = $conn->query("SELECT id, nome FROM insumos ORDER BY nome");
                        while ($row = $result_insumos->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['nome']) . "' data-id='" . $row['id'] . "'>";
                        }
                        ?>
                    </datalist>

                    <datalist id="fornecedores-datalist">
                        <?php
                        // Usando a query que você já tinha para fornecedores
                        $result_fornecedores = $conn->query("SELECT id, nome_fantasia FROM fornecedores WHERE empresa_id = {$_SESSION['empresa_id']} ORDER BY nome_fantasia");
                        while ($row = $result_fornecedores->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['nome_fantasia']) . "' data-id='" . $row['id'] . "'>";
                        }
                        ?>
                    </datalist>


                    <?php if (isset($cotacao['id'])): ?>

                        <div class="mt-4 p-6 bg-white rounded-lg  ">


                            <h3 class="text-xl font-semibold mb-4 text-gray-800">Documentos Vinculados</h3>
                            <?php
                            $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'cotacoes' AND ref_id = ?");
                            $stmt_docs->bind_param("i", $cotacao['id']);
                            $stmt_docs->execute();
                            $result_docs = $stmt_docs->get_result();
                            if ($result_docs->num_rows > 0):
                            ?>
                                <ul class="divide-y divide-gray-200">
                                    <?php while ($doc = $result_docs->fetch_assoc()): ?>
                                        <li class="flex items-center justify-between py-2">
                                            <div>
                                                <a href="<?= htmlspecialchars($doc['caminho_arquivo']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                                    <?= htmlspecialchars(string: $doc['nome']) ?>
                                                </a>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-gray-500 ">Nenhum documento encontrado.</p>
                            <?php endif; ?>
                            <?php $stmt_docs->close(); ?>
                        </div>
                    <?php endif; ?>


                    <?php if (isset($cotacao['status']) && isset($usuario['setor_nome'])): ?>
                        <?php if ($cotacao['status'] !== 'rejeitado' && strtolower($usuario['setor_nome']) === 'gestão' && $cotacao['status'] !== 'aprovado'): ?>
                            <div class="flex justify-end gap-4 mt-8">
                                <button
                                    onclick="abrirModalCotacao(<?= $cotacao['id'] ?>)"
                                    class="bg-green-600 text-white px-4 py-2  rounded-lg hover:bg-green-700 transition">
                                    Aprovar
                                </button>
                                <button
                                    onclick="rejeitarCotacao(<?= $cotacao['id'] ?>)"
                                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                                    Rejeitar
                                </button>
                            </div>
                        <?php elseif ($cotacao['status'] === 'rejeitado'): ?>
                            <p class="mt-4 text-red-600 font-semibold">Cotação Rejeitada</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (isset($cotacao['status'], $usuario['setor_nome']) && strtolower($usuario['setor_nome']) == 'financeiro'): ?>

                        <?php // O conteúdo interno só será processado se o usuário for do financeiro, ignorando maiúsculas/minúsculas. 
                        ?>

                        <?php if ($cotacao['status'] == 'aprovado'): ?>
                            <div class="flex justify-end gap-4 mt-8">
                                <?php if (!$temTransacao): ?>
                                    <button
                                        onclick="abrirModalCompra(<?= $cotacao['id'] ?>)"
                                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                                        Sinalizar Compra
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($cotacao['status'] === 'rejeitado'): ?>
                            <?php // Bloco para status rejeitado, se necessário no futuro. 
                            ?>
                        <?php endif; ?>

                    <?php endif; ?>


                </div>


            <?php endforeach; ?>







            <div class="flex justify-center align-items items-center mb-20">
                <button
                    onclick="window.location.href = './form.php?sc_id=<?= $sc_id ?>'"
                    class="bg-blue-600 text-white w-[30%]  py-4 rounded-lg hover:bg-blue-700 transition">
                    + Adicionar Cotação Concorrente
                </button>
            </div>



            <?php
            // --- BLOCO DE ANÁLISE DAS COTAÇÕES ---

            // 1. Inicializa variáveis para análise
            $cotacao_vencedora_id = null;
            $menor_valor_total = PHP_FLOAT_MAX;
            $itens_mais_baratos = [];
            $cotacoes_com_totais = [];
            $ids_cotacoes_base = array_column($cotacoes, 'id');
            // Pega o nome do cotante da última cotação da lista como padrão
            $ultimo_cotante = !empty($cotacoes) ? end($cotacoes)['cotante'] : ($_SESSION['usuario_nome'] ?? 'Sistema');
            $sc_id_vinculado = $sc_id;

            // 2. Pré-processa todas as cotações para encontrar o menor preço e os itens mais baratos
            foreach ($cotacoes as $cotacao) {
                $valor_total_atual = 0;
                foreach ($cotacao['itens'] as $item) {
                    $valor_total_atual += $item['valor_final'];
                    $nome_insumo = $item['insumo_nome'];

                    // Verifica se este é o item mais barato para este insumo até agora
                    if (!isset($itens_mais_baratos[$nome_insumo]) || $item['valor_final'] < $itens_mais_baratos[$nome_insumo]['valor_final']) {
                        $itens_mais_baratos[$nome_insumo] = $item;
                    }
                }

                // Armazena a cotação com seu total calculado
                $cotacoes_com_totais[] = [
                    'id' => $cotacao['id'],
                    'valor_total' => $valor_total_atual
                ];

                // Verifica se esta cotação tem o menor preço total até agora
                if ($valor_total_atual < $menor_valor_total) {
                    $menor_valor_total = $valor_total_atual;
                    $cotacao_vencedora_id = $cotacao['id'];
                }
            }

            // 3. Calcula o valor total da cotação otimizada (sugestão)
            $valor_total_sugestao = 0;
            foreach ($itens_mais_baratos as $item) {
                $valor_total_sugestao += $item['valor_final'];
            }

            // 4. LÓGICA PARA EXIBIR A SUGESTÃO (NOVO)
            // A sugestão só faz sentido se o valor otimizado for menor que o da melhor cotação individual.
            // Usamos uma pequena tolerância (ex: 1 centavo) para evitar mostrar por diferenças de arredondamento.
            $mostrar_sugestao = ($valor_total_sugestao < ($menor_valor_total - 0.01)) && !empty($itens_mais_baratos);
            ?>

            <div class="bg-blue-50 border border-blue-200 rounded-2xl shadow-sm p-6 mb-8">
                <h2 class="text-2xl font-bold text-blue-800 mb-4">🏆 Análise de Cotações</h2>

                <?php if ($cotacao_vencedora_id !== null): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">🥇 Cotação Vencedora (Menor Preço Total)</h3>
                        <p class="text-gray-700">
                            A cotação com o menor valor total é a <strong>#<?= htmlspecialchars($cotacao_vencedora_id) ?></strong>, somando
                            <strong class="text-green-700">R$ <?= number_format($menor_valor_total, 2, ',', '.') ?></strong>.
                        </p>
                    </div>
                <?php endif; ?>

                <?php // CONDIÇÃO ATUALIZADA AQUI 
                ?>
                <?php if ($mostrar_sugestao): ?>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">💡 Sugestão de Compra Otimizada</h3>
                        <p class="text-gray-700 mb-4">
                            Combinando os itens mais baratos de cada fornecedor, você pode montar uma compra no valor total de
                            <strong class="text-green-700">R$ <?= number_format($valor_total_sugestao, 2, ',', '.') ?></strong>.
                        </p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-gray-800 border border-gray-300 rounded-md overflow-hidden">
                                <thead class="bg-gray-200 text-gray-700 font-semibold">
                                    <tr>
                                        <th class="border border-gray-300 px-4 py-2 text-left">Insumo</th>
                                        <th class="border border-gray-300 px-4 py-2 text-left">Fornecedor (Melhor Preço)</th>
                                        <th class="border border-gray-300 px-4 py-2 text-left">Valor Final</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itens_mais_baratos as $item): ?>
                                        <tr class="bg-white">
                                            <td class="border border-gray-200 px-4 py-2"><?= htmlspecialchars($item['insumo_nome']) ?></td>
                                            <td class="border border-gray-200 px-4 py-2"><?= htmlspecialchars($item['fornecedor_nome']) ?></td>
                                            <td class="border border-gray-200 px-4 py-2">R$ <?= number_format($item['valor_final'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-8 text-right">
                            <button
                                id="btn-usar-sugestao"
                                onclick="criarCotacaoSugerida()"
                                class="inline-flex items-center bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors duration-300 shadow-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Emitir Pedido de Compra
                            </button>
                        </div>
                    </div>
                <?php endif; ?>


            </div>

            <div id="modal-aprovar-cotacao" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
                <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
                    <h2 class="text-lg font-semibold mb-4">Aprovar Cotação</h2>
                    <input type="hidden" id="modal-id-cotacao">

                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome de quem está aprovando:</label>
                    <input type="text" id="input-aprovador-cotacao"
                        class="w-full border border-gray-300 rounded px-3 py-2 mb-4"
                        placeholder="Digite seu nome">

                    <label class="block text-sm font-medium text-gray-700 mb-1">PIN (senha):</label>
                    <input type="password" id="input-senha-cotacao"
                        class="w-full border border-gray-300 rounded px-3 py-2 mb-4"
                        placeholder="Digite seu PIN">

                    <div class="flex justify-end gap-2">
                        <button onclick="fecharModalCotacao()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Cancelar</button>
                        <button onclick="enviarAprovacaoCotacao()"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Aprovar</button>
                    </div>
                </div>
            </div>


            <div id="modal-confirmar-compra" class="fixed hidden inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
                    <form id="form-confirmar-compra" enctype="multipart/form-data">
                        <h2 class="text-2xl font-bold mb-6 text-gray-800">Confirmar Compra e Lançar Transação</h2>

                        <input type="hidden" name="cotacao_id" id="cotacao_id_compra">

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="fornecedor_id" class="block text-sm font-medium text-gray-700 mb-1">Fornecedor:</label>
                                <select name="fornecedor_id" id="fornecedor_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-green-500 focus:border-green-500">
                                    <option value="">Selecione um fornecedor</option>
                                    <?php
                                    // Supondo que $fornecedores foi buscado na página principal
                                    if (isset($fornecedores)) {
                                        mysqli_data_seek($fornecedores, 0);
                                        while ($fornecedor = $fornecedores->fetch_assoc()) : ?>
                                            <option value="<?= $fornecedor['id'] ?>"><?= htmlspecialchars($fornecedor['nome_fantasia']) ?></option>
                                    <?php endwhile;
                                    } ?>
                                </select>
                            </div>

                            <div>
                                <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria Financeira:</label>
                                <select name="categoria_id" id="categoria_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-green-500 focus:border-green-500">
                                    <option value="">Selecione uma categoria</option>
                                    <?php
                                    // Supondo que $categorias foi buscado na página principal
                                    if (isset($categorias)) {
                                        mysqli_data_seek($categorias, 0);
                                        while ($categoria = $categorias->fetch_assoc()) : ?>
                                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                                    <?php endwhile;
                                    } ?>
                                </select>
                            </div>

                            <div>
                                <label for="banco_id" class="block text-sm font-medium text-gray-700 mb-1">Banco (Conta de Saída):</label>
                                <select name="banco_id" id="banco_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-green-500 focus:border-green-500">
                                    <option value="">Selecione um banco</option>
                                    <?php
                                    // Supondo que $bancos foi buscado na página principal
                                    if (isset($bancos)) {
                                        mysqli_data_seek($bancos, 0);
                                        while ($banco = $bancos->fetch_assoc()) : ?>
                                            <option value="<?= $banco['id'] ?>"><?= htmlspecialchars($banco['nome']) ?></option>
                                    <?php endwhile;
                                    } ?>
                                </select>
                            </div>

                            <div>
                                <label for="dt_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Data de Início do Pagamento:</label>
                                <input type="date" name="dt_pagamento" id="dt_pagamento" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-green-500 focus:border-green-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Pagamento:</label>
                                <div class="flex items-center space-x-6">
                                    <label for="pagamento_unico" class="flex items-center cursor-pointer">
                                        <input type="radio" id="pagamento_unico" name="tipo_pagamento" value="unico" checked class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                        <span class="ml-2 text-sm text-gray-900">Único</span>
                                    </label>
                                    <label for="pagamento_parcelado" class="flex items-center cursor-pointer">
                                        <input type="radio" id="pagamento_parcelado" name="tipo_pagamento" value="parcelado" class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                        <span class="ml-2 text-sm text-gray-900">Parcelado</span>
                                    </label>
                                </div>
                            </div>

                            <div id="campo_parcelas" class="hidden">
                                <label for="numero_parcelas" class="block text-sm font-medium text-gray-700 mb-1">Quantidade de Parcelas:</label>
                                <input type="number" name="numero_parcelas" id="numero_parcelas" min="2" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-green-500 focus:border-green-500">
                            </div>

                            <div>
                                <label for="anexos" class="block text-sm font-medium text-gray-700 mb-1">Anexar Comprovantes/Notas:</label>
                                <input type="file" name="anexos[]" id="anexos" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            </div>

                            <div class="border-t pt-4">
                                <div class="flex items-center">
                                    <input type="checkbox" id="is_reembolso" name="is_reembolso" class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                    <label for="is_reembolso" class="ml-2 block text-sm font-medium text-gray-900">Marcar como Reembolso</label>
                                </div>

                                <div id="campos_reembolso" class="hidden mt-4 space-y-4">
                                    <div>
                                        <label for="motivo_reembolso" class="block text-sm font-medium text-gray-700 mb-1">Motivo do Reembolso:</label>
                                        <input type="text" name="motivo_reembolso" id="motivo_reembolso" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label for="chave_pix_reembolso" class="block text-sm font-medium text-gray-700 mb-1">Chave PIX para Reembolso:</label>
                                        <input type="text" name="chave_pix_reembolso" id="chave_pix_reembolso" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-green-500 focus:border-green-500">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4 mt-6">
                            <button type="button" onclick="fecharModalCompra()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md font-semibold">Cancelar</button>
                            <button type="submit" id="btn-salvar-compra" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-semibold flex items-center">
                                <span id="btn-salvar-compra-text">Salvar Compra</span>
                                <svg id="btn-salvar-compra-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                // Lógica para mostrar/ocultar campos de parcelamento
                document.querySelectorAll('input[name="tipo_pagamento"]').forEach(elem => {
                    elem.addEventListener('change', function() {
                        document.getElementById('campo_parcelas').classList.toggle('hidden', this.value !== 'parcelado');
                    });
                });

                // Lógica para mostrar/ocultar campos de reembolso
                const checkboxReembolso = document.getElementById('is_reembolso');
                const camposReembolso = document.getElementById('campos_reembolso');
                const motivoInput = document.getElementById('motivo_reembolso');
                const chavePixInput = document.getElementById('chave_pix_reembolso');

                checkboxReembolso.addEventListener('change', function() {
                    const isChecked = this.checked;
                    camposReembolso.classList.toggle('hidden', !isChecked);
                    motivoInput.required = isChecked;
                    chavePixInput.required = isChecked;
                });

                // Função para fechar o modal (exemplo)
                function fecharModalCompra() {
                    document.getElementById('modal-confirmar-compra').classList.add('hidden');
                }
            </script>


            <div id="modal-rejeitar-cotacao" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
                <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
                    <h2 class="text-lg font-semibold mb-4">Rejeitar Cotação</h2>
                    <input type="hidden" id="modal-id-cotacao-rejeitar">

                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome de quem está Rejeitando:</label>
                    <input type="text" id="input-rejeitador-cotacao"
                        class="w-full border border-gray-300 rounded px-3 py-2 mb-4"
                        placeholder="Digite seu nome">

                    <label class="block text-sm font-medium text-gray-700 mb-1">PIN (senha):</label>
                    <input type="password" id="input-senha-rejeitar-cotacao"
                        class="w-full border border-gray-300 rounded px-3 py-2 mb-4"
                        placeholder="Digite seu PIN">

                    <div class="flex justify-end gap-2">
                        <button onclick="fecharRejeito()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Cancelar</button>
                        <button onclick="enviarRejeicaoCotacao()"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Rejeitar</button>
                    </div>
                </div>
            </div>




        </div>
    </div>


    <script>
        /**
         * Mostra o botão 'Salvar' de uma linha da cotação quando um campo é alterado.
         * @param {HTMLElement} element O input que foi alterado.
         */
        function mostrarBotaoSalvarCotacao(element) {
            const linha = element.closest('.cotacao-item-row');
            const botaoSalvar = linha.querySelector('.btn-salvar-item-cotacao');
            if (botaoSalvar) {
                botaoSalvar.classList.remove('hidden');
            }

            // Opcional: Calcular valor final em tempo real
            const quantidade = parseFloat(linha.querySelector('[name="quantidade"]').value) || 0;
            const valorUnitario = parseFloat(linha.querySelector('[name="valor_item"]').value) || 0;
            const desconto = parseFloat(linha.querySelector('[name="desconto"]').value) || 0;
            const valorFinal = (quantidade * valorUnitario) - desconto;

            const celulaValorFinal = linha.querySelector('.valor-final');
            celulaValorFinal.textContent = `R$ ${valorFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }


        /**
         * Coleta os dados de uma linha da cotação e os envia para o backend para atualização.
         * @param {HTMLElement} buttonElement O botão 'Salvar' que foi clicado.
         * @param {number} itemId O ID do item da cotação a ser atualizado.
         */
        function salvarAlteracoesItemCotacao(buttonElement, itemId) {
            const linha = buttonElement.closest('.cotacao-item-row');

            // Coleta os novos dados da linha
            const quantidade = linha.querySelector('[name="quantidade"]').value;
            const valor_item = linha.querySelector('[name="valor_item"]').value;
            const desconto = linha.querySelector('[name="desconto"]').value;

            if (!quantidade || !valor_item || desconto === '') {
                alert('Os campos de quantidade, valor e desconto devem ser preenchidos.');
                return;
            }

            // Monta o objeto de dados para envio
            const dadosParaAtualizar = {
                id: itemId,
                quantidade: quantidade,
                valor_item: valor_item,
                desconto: desconto
            };

            // Envia para um novo script PHP de atualização de item de cotação
            fetch('./update_cotacao_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dadosParaAtualizar)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Toastify({
                            text: "Item da cotação atualizado!",
                            backgroundColor: "#10b981"
                        }).showToast();

                        // Esconde o botão de salvar novamente
                        buttonElement.classList.add('hidden');

                        // Atualiza o valor final com o valor calculado no backend (mais seguro)
                        const celulaValorFinal = linha.querySelector('.valor-final');
                        const valorFinalFormatado = parseFloat(data.valor_final).toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        celulaValorFinal.textContent = `R$ ${valorFinalFormatado}`;

                        // Feedback visual na linha
                        linha.classList.add('bg-green-100');
                        setTimeout(() => {
                            linha.classList.remove('bg-green-100');
                        }, 1500);
                    } else {
                        Toastify({
                            text: "Erro: " + data.error,
                            backgroundColor: "#ef4444"
                        }).showToast();
                    }
                })
                .catch(error => {
                    console.error("Erro na requisição de atualização:", error);
                    alert('Ocorreu um erro de comunicação ao tentar salvar o item da cotação.');
                });
        }
    </script>


    <script>
        // Adicione estas novas funções ao seu script existente

        /**
         * Mostra o botão 'Salvar' de uma linha específica quando um de seus campos é alterado.
         * @param {HTMLElement} element O input ou select que foi alterado.
         */
        function mostrarBotaoSalvar(element) {
            const linha = element.closest('.sc-item-row');
            const botaoSalvar = linha.querySelector('.btn-salvar-item');
            if (botaoSalvar) {
                botaoSalvar.classList.remove('hidden');
            }
        }

        /**
         * Coleta os dados de uma linha e os envia para o backend para atualização.
         * @param {HTMLElement} buttonElement O botão 'Salvar' que foi clicado.
         * @param {number} itemId O ID do item a ser atualizado.
         */
        function salvarAlteracoesItem(buttonElement, itemId) {
            const linha = buttonElement.closest('.sc-item-row');

            // Coleta os novos dados da linha
            const quantidade = linha.querySelector('[name="quantidade"]').value;
            const und_medida = linha.querySelector('[name="und_medida"]').value;
            const grau = linha.querySelector('[name="grau"]').value;

            if (!quantidade || !und_medida || !grau) {
                alert('Todos os campos do item devem ser preenchidos.');
                return;
            }

            // Monta o objeto de dados para envio
            const dadosParaAtualizar = {
                id: itemId,
                quantidade: quantidade,
                und_medida: und_medida,
                grau: grau
            };

            // Envia para o novo script de atualização
            fetch('./update_sc_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dadosParaAtualizar)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Toastify({
                            text: "Item atualizado com sucesso!",
                            backgroundColor: "#10b981"
                        }).showToast();
                        // Esconde o botão de salvar novamente
                        buttonElement.classList.add('hidden');
                        // Opcional: Adiciona um destaque rápido na linha para feedback visual
                        linha.classList.add('bg-green-100');
                        setTimeout(() => {
                            linha.classList.remove('bg-green-100');
                        }, 1500);
                    } else {
                        Toastify({
                            text: "Erro: " + data.error,
                            backgroundColor: "#ef4444"
                        }).showToast();
                    }
                })
                .catch(error => {
                    console.error("Erro na requisição de atualização:", error);
                    alert('Ocorreu um erro de comunicação ao tentar salvar as alterações.');
                });
        }

        // Sua função excluirItem(this, itemId) já existente continua funcionando normalmente.
    </script>

    <script>
        // --- FUNÇÕES PARA ADICIONAR ITENS ---

        function iniciarAdicaoItemCotacao(cotacaoId) {
            document.getElementById(`btn-iniciar-adicao-${cotacaoId}`).classList.add('hidden');
            document.getElementById(`acoes-novos-itens-${cotacaoId}`).classList.remove('hidden');
            adicionarLinhaCotacao(cotacaoId);
        }

        function cancelarAdicaoItemCotacao(cotacaoId) {
            document.getElementById(`btn-iniciar-adicao-${cotacaoId}`).classList.remove('hidden');
            document.getElementById(`acoes-novos-itens-${cotacaoId}`).classList.add('hidden');
            document.getElementById(`tbody-novos-itens-${cotacaoId}`).innerHTML = '';
        }

        function adicionarLinhaCotacao(cotacaoId) {
            const template = document.getElementById(`template-novo-item-${cotacaoId}`);
            const tbody = document.getElementById(`tbody-novos-itens-${cotacaoId}`);
            const novaLinha = template.cloneNode(true);
            novaLinha.removeAttribute('id');

            novaLinha.querySelectorAll('input, select').forEach(campo => {
                campo.removeAttribute('disabled');
            });

            tbody.appendChild(novaLinha);
        }

        function salvarNovosItensCotacao(cotacaoId) {
            const tbody = document.getElementById(`tbody-novos-itens-${cotacaoId}`);
            const linhas = tbody.querySelectorAll('tr');
            const itensParaSalvar = [];

            let hasError = false;
            linhas.forEach(linha => {
                const insumoNome = linha.querySelector('[name="insumo_id"]').value;
                const fornecedorNome = linha.querySelector('[name="fornecedor_id"]').value;

                const insumoOpt = document.querySelector(`#insumos-datalist option[value="${insumoNome}"]`);
                const fornecedorOpt = document.querySelector(`#fornecedores-datalist option[value="${fornecedorNome}"]`);

                if (!insumoOpt || !fornecedorOpt) {
                    alert('Por favor, selecione um Insumo e um Fornecedor válidos da lista.');
                    hasError = true;
                    return;
                }

                itensParaSalvar.push({
                    insumo_id: insumoOpt.dataset.id,
                    fornecedor_id: fornecedorOpt.dataset.id,
                    quantidade: linha.querySelector('[name="quantidade"]').value,
                    valor_item: linha.querySelector('[name="valor_item"]').value,
                    desconto: linha.querySelector('[name="desconto"]').value,
                });
            });

            if (hasError || itensParaSalvar.length === 0) return;

            fetch('adicionar_item_cotacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        cotacao_id: cotacaoId,
                        itens: itensParaSalvar
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Itens adicionados com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro: ' + data.error);
                    }
                })
                .catch(err => console.error(err));
        }


        // --- FUNÇÃO PARA EXCLUIR ITENS ---

        function excluirItemCotacao(buttonElement, itemId) {
            if (!confirm('Tem certeza que deseja excluir este item da cotação?')) return;

            fetch('excluir_item_cotacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: itemId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        buttonElement.closest('tr').remove();
                        alert('Item excluído com sucesso.');
                    } else {
                        alert('Erro ao excluir: ' + data.error);
                    }
                })
                .catch(err => console.error(err));
        }
    </script>
    <script>
        // Elementos do DOM
        const btnIniciarAdicao = document.getElementById('btn-iniciar-adicao');
        const acoesNovosItens = document.getElementById('acoes-novos-itens');
        const btnAdicionarLinha = document.getElementById('btn-adicionar-linha');
        const btnCancelarAdicao = document.getElementById('btn-cancelar-adicao');
        const btnSalvarItens = document.getElementById('btn-salvar-itens');
        const tbodyNovosItens = document.getElementById('tbody-novos-itens');
        const templateRow = document.getElementById('template-novo-item');
        const formNovosItens = document.getElementById('form-novos-itens');
        const insumosDatalist = document.getElementById('insumos');
        const solicitacaoContainer = document.getElementById('dados-solicitacao');
        const solicitacaoId = solicitacaoContainer.dataset.scId;

        // Função para adicionar uma nova linha de formulário
        // DENTRO DA SUA TAG <script>

        // Função para adicionar uma nova linha de formulário (VERSÃO CORRIGIDA)
        function adicionarNovaLinha() {
            const novaLinha = templateRow.cloneNode(true);
            novaLinha.removeAttribute('id');

            // **A MÁGICA ACONTECE AQUI**
            // Pega todos os inputs e selects dentro da nova linha e reativa eles.
            const campos = novaLinha.querySelectorAll('input, select');
            campos.forEach(campo => {
                campo.removeAttribute('disabled');
            });

            tbodyNovosItens.appendChild(novaLinha);
        }

        // Função para remover uma linha
        function removerLinha(button) {
            button.closest('tr').remove();
        }

        // Iniciar o processo de adição
        btnIniciarAdicao.addEventListener('click', () => {
            btnIniciarAdicao.classList.add('hidden');
            acoesNovosItens.classList.remove('hidden');
            adicionarNovaLinha(); // Adiciona a primeira linha automaticamente
        });

        // Cancelar o processo
        btnCancelarAdicao.addEventListener('click', () => {
            btnIniciarAdicao.classList.remove('hidden');
            acoesNovosItens.classList.add('hidden');
            tbodyNovosItens.innerHTML = ''; // Limpa as linhas adicionadas
        });

        // Adicionar outra linha
        btnAdicionarLinha.addEventListener('click', adicionarNovaLinha);

        // Salvar os novos itens
        btnSalvarItens.addEventListener('click', () => {
            if (!formNovosItens.checkValidity()) {
                alert('Por favor, preencha todos os campos obrigatórios em todas as linhas.');
                formNovosItens.reportValidity();
                return;
            }

            const linhasNovas = tbodyNovosItens.querySelectorAll('tr');
            const itensParaSalvar = [];

            linhasNovas.forEach(linha => {
                const nomeInsumo = linha.querySelector('[name="insumo_nome"]').value.trim();
                const undMedida = linha.querySelector('[name="und_medida"]').value;
                const quantidade = linha.querySelector('[name="quantidade"]').value;
                const grau = linha.querySelector('[name="grau"]').value;

                // Se o nome do insumo estiver vazio, ignora a linha
                if (nomeInsumo === '') return;

                // Encontrar o ID do insumo a partir do nome no datalist
                const option = Array.from(insumosDatalist.options).find(opt => opt.value.toLowerCase() === nomeInsumo.toLowerCase());

                // ********* MUDANÇA PRINCIPAL AQUI *********
                if (option) {
                    // Insumo existente: envia o ID
                    itensParaSalvar.push({
                        insumo_id: option.dataset.id, // Usa o ID existente
                        und_medida: undMedida,
                        quantidade: quantidade,
                        grau: grau
                    });
                } else {
                    // Insumo NOVO: envia o nome para ser criado no backend
                    itensParaSalvar.push({
                        insumo_nome: nomeInsumo, // Envia o nome do novo insumo
                        und_medida: undMedida,
                        quantidade: quantidade,
                        grau: grau
                    });
                }
            });

            if (itensParaSalvar.length === 0) {
                alert('Adicione pelo menos um item válido para salvar.');
                return;
            }

            // O restante do fetch continua igual...
            fetch('./add_insumo_solicitacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        solicitacao_id: solicitacaoId,
                        itens: itensParaSalvar
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Itens adicionados com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao salvar: ' + (data.error || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error("Erro na requisição:", error);
                    alert('Ocorreu um erro de comunicação.');
                });
        });




        // ... (suas funções existentes) ...

        // **** NOVA FUNÇÃO AQUI ****
        function excluirItem(buttonElement, itemId) {
            // Pede confirmação ao usuário antes de prosseguir
            if (!confirm('Tem certeza que deseja excluir este item da solicitação? Esta ação não pode ser desfeita.')) {
                return;
            }

            fetch('./excluir_item_solicitacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: itemId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Remove a linha da tabela da interface
                        const linhaParaRemover = buttonElement.closest('tr');
                        linhaParaRemover.remove();

                        alert('Item excluído com sucesso!');
                    } else {
                        alert('Erro ao excluir o item: ' + (data.error || 'Erro desconhecido.'));
                    }
                })
                .catch(error => {
                    console.error("Erro na requisição de exclusão:", error);
                    alert('Ocorreu um erro de comunicação ao tentar excluir o item.');
                });
        }
    </script>

    <script>
        let cotacaoSelecionada = null;

        function abrirModalCompra(id) {
            cotacaoSelecionada = id;
            document.getElementById('modal-confirmar-compra').style.display = 'flex';
        }

        function fecharModalCompra() {
            document.getElementById('modal-confirmar-compra').style.display = 'none';
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Seletores dos elementos do formulário ---
            const form = document.getElementById('form-confirmar-compra');
            const tipoPagamentoRadios = document.querySelectorAll('input[name="tipo_pagamento"]');
            const campoParcelas = document.getElementById('campo_parcelas');
            const inputParcelas = document.getElementById('numero_parcelas');
            const isReembolsoCheckbox = document.getElementById('is_reembolso');
            const camposReembolso = document.getElementById('campos_reembolso');
            const inputMotivoReembolso = document.getElementById('motivo_reembolso');
            const inputChavePixReembolso = document.getElementById('chave_pix_reembolso');

            // --- Lógica para mostrar/ocultar campos dinamicamente ---
            function toggleDynamicFields() {
                // Lógica para parcelas
                const parceladoChecked = document.getElementById('pagamento_parcelado').checked;
                campoParcelas.classList.toggle('hidden', !parceladoChecked);
                inputParcelas.required = parceladoChecked;
                if (!parceladoChecked) inputParcelas.value = '';

                // Lógica para reembolso
                const reembolsoChecked = isReembolsoCheckbox.checked;
                camposReembolso.classList.toggle('hidden', !reembolsoChecked);
                inputMotivoReembolso.required = reembolsoChecked;
                inputChavePixReembolso.required = reembolsoChecked;
                if (!reembolsoChecked) {
                    inputMotivoReembolso.value = '';
                    inputChavePixReembolso.value = '';
                }
            }

            // Adiciona os listeners para os campos que controlam a visibilidade de outros
            tipoPagamentoRadios.forEach(radio => radio.addEventListener('change', toggleDynamicFields));
            isReembolsoCheckbox.addEventListener('change', toggleDynamicFields);

            // Garante que o estado inicial do formulário esteja correto
            toggleDynamicFields();

            // --- Lógica de submissão do formulário ---
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Pega o ID da cotação do campo oculto (preenchido ao abrir o modal)
                const cotacaoId = cotacaoSelecionada
                if (!cotacaoId) {
                    alert("Erro: ID da Cotação não encontrado. Tente abrir o modal novamente.");
                    return;
                }

                // **MUDANÇA PRINCIPAL: Usar FormData para incluir todos os campos e arquivos**
                // O FormData coleta automaticamente todos os campos do formulário com o atributo 'name'.
                const formData = new FormData(form);

                formData.append('cotacao_id', cotacaoId)

                // O fetch agora envia o objeto FormData diretamente.
                // **NÃO** definimos o 'Content-Type' no header; o navegador faz isso automaticamente
                // para formulários com arquivos, o que é essencial.

                // Exibe um feedback visual para o usuário
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = 'Salvando...';

                fetch('./sinalizar_compra.php', { // Verifique se o caminho para o script PHP está correto
                        method: 'POST',
                        body: formData // Envia o formulário completo, incluindo os arquivos
                    })
                    .then(res => {
                        // Primeiro, verifica se a resposta é mesmo JSON antes de tentar processá-la
                        const contentType = res.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return res.json();
                        } else {
                            // Se não for JSON, pode ser um erro de PHP não capturado.
                            return res.text().then(text => {
                                throw new Error("Resposta inesperada do servidor:\n" + text);
                            });
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            alert("Compra salva com sucesso!");
                            // fecharModalCompra(); // Supondo que você tenha essa função
                            location.reload();
                        } else {
                            alert("Erro ao salvar: " + (data.error || "Erro desconhecido."));
                        }
                    })
                    .catch(error => {
                        console.error("Erro na requisição:", error);
                        alert("Erro de comunicação: " + error.message);
                    })
                    .finally(() => {
                        // Restaura o botão de submit em qualquer caso (sucesso ou erro)
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Salvar Compra';
                    });
            });
        });
    </script>
    <script>
        // Passa TODOS os dados PHP necessários para o JavaScript
        const itensSugeridos = <?= json_encode(array_values($itens_mais_baratos)); ?>;
        const idsCotacoesBase = <?= json_encode($ids_cotacoes_base); ?>;
        const cotanteSugerido = <?= json_encode($ultimo_cotante); ?>;
        const scIdVinculado = <?= json_encode($sc_id_vinculado); ?>; // NOVO

        async function criarCotacaoSugerida() {
            const botao = document.getElementById('btn-usar-sugestao');
            const textoOriginal = botao.innerHTML;
            botao.disabled = true;
            botao.innerHTML = 'Processando...';

            try {
                const response = await fetch('criar_cotacao_sugerida.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    // Corpo da requisição agora inclui também o sc_id
                    body: JSON.stringify({
                        produtos: itensSugeridos,
                        cotacoes_base_ids: idsCotacoesBase,
                        cotante: cotanteSugerido,
                        sc_id: scIdVinculado // NOVO
                    })
                });

                const resultado = await response.json();

                // A lógica de tratamento da resposta permanece a mesma
                if (resultado.sucesso) {
                    alert('Cotação sugerida criada com sucesso! ID: ' + resultado.cotacao_id + '. Redirecionando para o PDF.');
                    window.open('gerar_pedido_pdf.php?id=' + resultado.cotacao_id, '_blank');
                } else if (resultado.erro && resultado.erro === 'DUPLICADO') {
                    alert('Atenção: Uma cotação idêntica já existe (ID: ' + resultado.existente_id + '). Redirecionando para o PDF existente.');
                    window.open('gerar_pedido_pdf.php?id=' + resultado.existente_id, '_blank');
                } else {
                    alert('Erro: ' + resultado.erro);
                }

                window.location.href = './'

            } catch (error) {
                console.error('Erro na requisição:', error);
                alert('Ocorreu um erro de comunicação. Verifique o console para mais detalhes.');
            } finally {
                botao.disabled = false;
                botao.innerHTML = textoOriginal;
            }
        }
    </script>
    <script>
        function abrirModalCotacao(id) {
            document.getElementById('modal-id-cotacao').value = id;
            document.getElementById('modal-aprovar-cotacao').classList.remove('hidden');
        }

        function fecharModalCotacao() {
            document.getElementById('modal-aprovar-cotacao').classList.add('hidden');
        }

        function rejeitarCotacao(id) {
            document.getElementById('modal-id-cotacao-rejeitar').value = id;
            document.getElementById('modal-rejeitar-cotacao').classList.remove('hidden');
        }

        function fecharRejeito() {
            document.getElementById('modal-rejeitar-cotacao').classList.add('hidden');
        }

        async function enviarAprovacaoCotacao() {
            const id = parseInt(document.getElementById('modal-id-cotacao').value, 10);
            const aprovador = document.getElementById('input-aprovador-cotacao').value.trim();
            const senha = document.getElementById('input-senha-cotacao').value;

            if (!id || aprovador === '' || senha === '') {
                alert('Por favor, preencha todos os campos.');
                return;
            }

            try {
                const response = await fetch('./aprovar_cotacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id,
                        aprovador,
                        senha
                    })
                });

                const data = await response.json();

                if (data.success) {
                    fecharModalCotacao();
                    window.location.reload();
                } else {
                    alert('Erro ao aprovar: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (error) {
                alert('Erro ao enviar aprovação: ' + error.message);
            }
        }

        async function enviarRejeicaoCotacao() {
            const id = parseInt(document.getElementById('modal-id-cotacao-rejeitar').value, 10);
            const aprovador = document.getElementById('input-rejeitador-cotacao').value.trim();
            const senha = document.getElementById('input-senha-rejeitar-cotacao').value;

            if (!id || aprovador === '' || senha === '') {
                alert('Por favor, preencha todos os campos.');
                return;
            }

            try {
                const response = await fetch('./cancelar_cotacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id,
                        aprovador, // aqui o nome tem que ser rejeitador
                        senha
                    })
                });

                const data = await response.json();

                if (data.success) {
                    fecharRejeito();
                    window.location.reload();
                } else {
                    alert('Erro ao rejeitar: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (error) {
                alert('Erro ao enviar rejeição: ' + error.message);
            }
        }
    </script>


    <script>
        const insumosSelecionados = [];

        function adicionarInsumo() {
            const insumoInput = document.getElementById('insumo-input');
            const quantidadeInput = document.getElementById('quantidade-input');
            const valorUntInput = document.getElementById('valor-input');
            const unidadeInput = document.getElementById('unidade-input');
            const fornecedorInput = document.getElementById('fornecedor');
            const descricaoTecnicaInput = document.getElementById('descricao-tecnica');

            const insumo = insumoInput.value.trim();
            const quantidade = quantidadeInput.value.trim();
            const valorUnt = valorUntInput.value.trim();
            const unidade = unidadeInput.value.trim();
            const fornecedorId = fornecedorInput.value.trim();
            const descricaoTecnica = descricaoTecnicaInput?.value.trim() ?? '';

            if (!insumo || !quantidade || !unidade || !valorUnt || !fornecedorId) {
                alert('Preencha todos os campos!');
                return;
            }

            const index = insumosSelecionados.length;

            insumosSelecionados.push({
                insumo_nome: insumo,
                insumo_quantidade: quantidade,
                insumo_unidade: unidade,
                valorUnt: parseFloat(valorUnt),
                fornecedor_id: fornecedorId,
                descricao_tecnica: descricaoTecnica
            });

            const item = document.createElement('div');
            item.className = 'bg-white border border-gray-300 rounded shadow p-4 flex justify-between items-start';
            item.dataset.index = index;

            item.innerHTML = `
            <div>
                <p class="font-semibold text-gray-800">${insumo}</p>
                <p class="text-gray-600 text-sm">Quantidade: ${quantidade} ${unidade}</p>
                <p class="text-gray-600 text-sm">Valor Unitário: R$ ${valorUnt}</p>
            </div>
            <div class="flex items-center">
                <button type="button" class="text-red-600 hover:text-red-800 text-xl font-bold ml-4" onclick="removerInsumo(this)">×</button>
            </div>
        `;

            document.getElementById('insumos-container').appendChild(item);

            // Limpa campos
            insumoInput.value = '';
            quantidadeInput.value = '';
            valorUntInput.value = '';
            unidadeInput.value = '';
            fornecedorInput.value = '';
            if (descricaoTecnicaInput) descricaoTecnicaInput.value = '';
        }

        function removerInsumo(button) {
            const item = button.closest('div[data-index]');
            const index = parseInt(item.dataset.index);
            insumosSelecionados[index] = null;
            item.remove();
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();

            const cotante = document.getElementById('solicitante').value;
            const descricao = document.getElementById('descricao').value;
            const osId = document.getElementById('os-id').value;
            const obraId = document.getElementById('obra-id').value;
            const scId = document.getElementById('sc-id').value;

            const produtos = insumosSelecionados.filter(i => i !== null);

            const body = {
                cotante,
                descricao,
                osId,
                obraId,
                scId,
                produtos
            };

            fetch('./create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                })
                .then(res => res.json())
                .then(res => {
                    if (res.sucesso) {
                        Toastify({
                            text: "Operação realizada com sucesso!",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#10b981",
                            close: true
                        }).showToast();

                        document.querySelector('form').reset();
                        insumosSelecionados.length = 0;
                        document.getElementById('insumos-container').innerHTML = '';

                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        throw new Error(res.erro || "Erro desconhecido");
                    }
                })
                .catch(err => {
                    console.error("Erro no envio:", err);
                    Toastify({
                        text: "Erro ao enviar a solicitação.",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#ef4444",
                        close: true
                    }).showToast();
                });
        });
    </script>


</body>