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
    $stmtSC = $conn->prepare("SELECT * FROM solicitacao_compras WHERE id = ? AND empresa_id = ?");
    $stmtSC->bind_param("ii", $sc_id, $empresa_id);
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
$stmtFornecedores = $conn->prepare("SELECT nome_fantasia FROM fornecedores WHERE empresa_id = ? ORDER BY nome_fantasia");
$stmtFornecedores->bind_param("i", $empresa_id);
$stmtFornecedores->execute();
$fornecedores = $stmtFornecedores->get_result();
$stmtFornecedores->close();


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

                <h4 class="text-lg font-semibold text-primary mb-3 mt-6">📦 Itens da Solicitação</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-gray-800 border border-gray-300 rounded-md overflow-hidden">
                        <thead class="bg-gray-100 text-gray-700 font-semibold">
                            <tr>
                                <th class="border border-gray-200 px-4 py-2 text-left">ID</th>
                                <th class="border border-gray-200 px-4 py-2 text-left">Insumo</th>
                                <th class="border border-gray-200 px-4 py-2 text-left">Unidade</th>
                                <th class="border border-gray-200 px-4 py-2 text-left">Quantidade</th>
                                <th class="border border-gray-200 px-4 py-2 text-left">Grau</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sc['itens'] as $item): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="border border-gray-200 px-4 py-2"><?= htmlspecialchars($item['id']) ?></td>
                                    <td class="border border-gray-200 px-4 py-2">
                                        <?= htmlspecialchars($insumos_nomes[$item['insumo_id']] ?? 'Insumo não encontrado') ?>
                                    </td>
                                    <td class="border border-gray-200 px-4 py-2"><?= htmlspecialchars($item['und_medida']) ?></td>
                                    <td class="border border-gray-200 px-4 py-2"><?= htmlspecialchars($item['quantidade']) ?></td>
                                    <td class="border border-gray-200 px-4 py-2"><?= htmlspecialchars($item['grau']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>

            <?php foreach ($cotacoes as $cotacao): ?>
                <div class="bg-white border border-gray-300 rounded-2xl shadow-sm p-6 mb-8">

                    <h3 class="text-xl font-bold text-primary mb-6">🧾 Resumo da Cotação #<?= htmlspecialchars($cotacao['id']) ?></h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 gap-x-6 text-sm text-neutral-800 mb-8">
                        <div><span class="font-medium text-gray-500">Cotante:</span> <?= htmlspecialchars($cotacao['cotante']) ?></div>
                        <div><span class="font-medium text-gray-500">Status:</span>
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold <?= $cotacao['status'] === 'aprovado' ? 'bg-green-100 text-green-700' : ($cotacao['status'] === 'rejeitado' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                <?= htmlspecialchars(ucfirst($cotacao['status'])) ?>
                            </span>
                        </div>
                        <div><span class="font-medium text-gray-500">Valor Total:</span> R$ <?= number_format($cotacao['valor_total'], 2, ',', '.') ?></div>
                        <!-- <div><span class="font-medium text-gray-500">Data Início:</span> <?= date('d/m/Y', strtotime($cotacao['data_inicio'])) ?></div> -->
                        <!-- <div><span class="font-medium text-gray-500">Data Final:</span> <?= date('d/m/Y', strtotime($cotacao['data_final'])) ?></div> -->
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

                    <h4 class="text-lg font-semibold text-primary mb-3 mt-6">🛒 Itens da Cotação</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-gray-800 border border-gray-300 rounded-md overflow-hidden">
                            <thead class="bg-gray-100 text-gray-700 font-semibold">
                                <tr>
                                    <th class="border border-gray-200 px-4 py-2 text-left">ID</th>
                                    <th class="border border-gray-200 px-4 py-2 text-left">Insumo</th>
                                    <th class="border border-gray-200 px-4 py-2 text-left">Fornecedor</th>
                                    <th class="border border-gray-200 px-4 py-2 text-left">Unidade</th>
                                    <th class="border border-gray-200 px-4 py-2 text-left">Quantidade</th>
                                    <th class="border border-gray-200 px-4 py-2 text-left">Valor Unit.</th>
                                    <th class="border border-gray-200 px-4 py-2 text-left">Desconto</th>
                                    <th class="border border-gray-200 px-4 py-2 text-left">Valor Final</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cotacao['itens'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['id']) ?></td>
                                        <td><?= htmlspecialchars($item['insumo_nome']) ?></td>
                                        <td><?= htmlspecialchars($item['fornecedor_nome']) ?></td>
                                        <td><?= htmlspecialchars($item['und_medida']) ?></td>
                                        <td><?= htmlspecialchars($item['quantidade']) ?></td>
                                        <td>R$ <?= number_format($item['valor_item'], 2, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($item['desconto']) ?>%</td>
                                        <td>R$ <?= number_format($item['valor_final'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>


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
                                            <!-- <form method="get" action="delete_document.php" onsubmit="return confirm('Tem certeza que deseja excluir este documento?')">
                                            <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Excluir</button>
                                        </form> -->

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


                    <?php if (isset($cotacao['status']) && isset($usuario['setor_nome'])): ?>
                        <?php if ($cotacao['status'] == 'aprovado'): ?>
                            <div class="flex justify-end gap-4 mt-8">
                                <button
                                    onclick="abrirModalCotacao(<?= $cotacao['id'] ?>)"
                                    class="bg-green-600 text-white px-4 py-2  rounded-lg hover:bg-green-700 transition">
                                    Sinalizar Compra
                                </button>

                            </div>
                        <?php elseif ($cotacao['status'] === 'rejeitado'): ?>
                        <?php endif; ?>
                    <?php endif; ?>


                </div>
            <?php endforeach; ?>



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



            <!-- Modal Confirmar Compra -->
            <div id="modal-confirmar-compra" class="modal" style="display: none;">
                <form id="form-confirmar-compra" enctype="multipart/form-data">
                    <h2>Confirmar Compra</h2>

                    <input type="hidden" name="cotacao_id" id="input-cotacao-id-compra">

                    <label for="input-recebedor">Recebedor:</label>
                    <input type="text" name="recebedor" id="input-recebedor" required>

                    <label for="input-recebido">Recebido?</label>
                    <select name="recebido" id="input-recebido">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>

                    <label for="input-recebido-por">Recebido por:</label>
                    <input type="text" name="recebido_por" id="input-recebido-por">

                    <label for="input-anexos">Anexar documentos:</label>
                    <input type="file" name="anexos[]" id="input-anexos" multiple>

                    <button type="submit">Salvar Compra</button>
                    <button type="button" onclick="fecharModalCompra()">Cancelar</button>
                </form>
            </div>

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
        function abrirModalConfirmarCompra(cotacaoId) {
            document.getElementById('input-cotacao-id-compra').value = cotacaoId;
            document.getElementById('form-confirmar-compra').reset();
            document.getElementById('modal-confirmar-compra').style.display = 'block';
        }

        function fecharModalCompra() {
            document.getElementById('modal-confirmar-compra').style.display = 'none';
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