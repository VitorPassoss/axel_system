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

// Certifique-se de que empresa_id está definido
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




            <header class="bg-white rounded-2xl shadow-lg p-6 mb-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="window.location.href='../cotacoes/'" class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-800"> Área de Cotação </h1>

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
                                        <th class="border border-gray-200 px-4 py-2 text-left flex" style="width: 50px;">Ação</th>
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

                                            <td class="border-b   text-center">
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

/**
 * Exclui um item existente da solicitação de compra.
 * É chamada pelo botão de lixeira em cada linha da tabela de itens.
 * @param {HTMLElement} buttonElement - O próprio elemento do botão que foi clicado.
 * @param {number} itemId - O ID do item (sc_item) a ser excluído do banco de dados.
 */
function excluirItem(buttonElement, itemId) {
    
    // 1. Pede confirmação ao usuário, uma etapa crucial para ações destrutivas.
    if (!confirm('Tem certeza que deseja excluir este item da solicitação? Esta ação não pode ser desfeita.')) {
        return; // Se o usuário clicar em "Cancelar", a função para aqui.
    }

    // 2. Envia a requisição para o script PHP de exclusão.
    fetch('./excluir_item_solicitacao.php', { // Verifique se o caminho para o arquivo está correto.
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        // Envia o ID do item a ser excluído no corpo da requisição.
        body: JSON.stringify({ id: itemId })
    })
    .then(res => res.json())
    .then(data => {
        // 3. Processa a resposta do servidor.
        if (data.success) {
            // Se a exclusão no banco de dados foi bem-sucedida:
            
            // a. Remove a linha (<tr>) da tabela na interface do usuário.
            const linhaParaRemover = buttonElement.closest('tr');
            linhaParaRemover.style.transition = 'opacity 0.5s';
            linhaParaRemover.style.opacity = '0';
            setTimeout(() => {
                linhaParaRemover.remove();
            }, 500); // Remove o elemento após a animação de fade-out.

            // b. Mostra uma notificação de sucesso.
            Toastify({ 
                text: "Item excluído com sucesso!", 
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#10b981"
            }).showToast();

        } else {
            // Se o servidor retornou um erro, mostra a mensagem.
            alert('Erro ao excluir o item: ' + (data.error || 'Erro desconhecido.'));
        }
    })
    .catch(error => {
        // 4. Trata erros de comunicação com o servidor.
        console.error("Erro na requisição de exclusão:", error);
        alert('Ocorreu um erro de comunicação ao tentar excluir o item.');
    });
}            </script>
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
            </script>




            <div class="w-full">
                <div class="relative animate-fadeIn">


                    <!-- INÍCIO DO NOVO LAYOUT UNIFICADO -->
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-lg w-full">

                        <form id="form-criar-cotacao" class="w-full">

                            <!-- Campos Gerais da Cotação -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="flex flex-col">
                                    <label for="cotante" class="text-gray-700 mb-1 text-sm font-medium">Cotante Responsável</label>
                                    <input type="text" id="cotante" name="cotante" required class="w-full rounded-lg border border-gray-300 p-3 bg-gray-100" value="" />
                                </div>
                                <div class="flex flex-col">
                                    <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Anexar Propostas/Cotações (PDF, Imagens)</label>
                                    <input type="file" id="anexos" name="anexos[]" multiple class="w-full bg-white text-gray-800 rounded-lg border border-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                </div>
                                <div class="flex flex-col md:col-span-2">
                                    <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Observações Gerais da Cotação</label>
                                    <textarea id="descricao" name="descricao" class="w-full rounded-lg border border-gray-300 p-3 resize-y min-h-[100px]" placeholder=""></textarea>
                                </div>

                                <!-- CORREÇÃO: Campos ocultos DENTRO do formulário -->
                                <input type="hidden" name="scId" value="<?= htmlspecialchars($sc_id) ?>">
                                <input type="hidden" name="osId" value="<?= htmlspecialchars($os['id'] ?? 0) ?>">
                                <input type="hidden" name="obraId" value="<?= htmlspecialchars($obra['id'] ?? 0) ?>">
                            </div>

                            <!-- Tabela Dinâmica de Itens -->
                            <h3 class="text-xl font-bold text-primary mb-4">Itens da Cotação</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-3 text-left font-semibold text-gray-700">Produto/Insumo</th>
                                            <th class="p-3 text-left font-semibold text-gray-700">Fornecedor</th>
                                            <th class="p-3 text-left font-semibold text-gray-700">Qtd</th>
                                            <th class="p-3 text-left font-semibold text-gray-700">Un.</th>
                                            <th class="p-3 text-left font-semibold text-gray-700">Vlr. Unit. (R$)</th>
                                            <th class="p-3 text-left font-semibold text-gray-700">Desconto (R$)</th>
                                            <th class="p-3 text-left font-semibold text-gray-700">Vlr. Total (R$)</th>
                                            <th class="p-3 text-center font-semibold text-gray-700 w-16">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cotacao-table-body"></tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                <button type="button" id="btn-adicionar-item-cotacao" class="bg-blue-100 text-blue-700 font-semibold py-2 px-4 rounded-lg hover:bg-blue-200 transition-colors">
                                    + Adicionar Item à Cotação
                                </button>
                            </div>

                            <div class="border-t mt-8 pt-6 flex justify-end">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                                    Salvar Cotação
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <datalist id="insumos-datalist">
                <?php
                $result_insumos = $conn->query("SELECT id, nome FROM insumos ORDER BY nome");
                if ($result_insumos) {
                    while ($row = $result_insumos->fetch_assoc()) {
                        echo "<option value='" . htmlspecialchars($row['nome'], ENT_QUOTES) . "' data-id='" . $row['id'] . "'>";
                    }
                }
                ?>
            </datalist>
            <datalist id="fornecedores-datalist">
                <?php
                $result_fornecedores = $conn->query("SELECT id, nome_fantasia FROM fornecedores WHERE empresa_id = $empresa_id ORDER BY nome_fantasia");
                if ($result_fornecedores) {
                    while ($row = $result_fornecedores->fetch_assoc()) {
                        echo "<option value='" . htmlspecialchars($row['nome_fantasia'], ENT_QUOTES) . "' data-id='" . $row['id'] . "'>";
                    }
                }
                ?>
            </datalist>

            <template id="template-cotacao-item">
                <tr class="border-b">
                    <td class="p-2 align-top"><input name="insumo_nome" list="insumos-datalist" class="w-full rounded-md border-gray-300 p-2" placeholder="Selecione" required></td>
                    <td class="p-2 align-top">
                        <div class="relative flex items-center">
                            <input name="fornecedor_nome" list="fornecedores-datalist" class="w-full rounded-md border-gray-300 p-2 pr-8" placeholder="Selecione" required>
                            <button type="button"
                                onclick="abrirModalFornecedor(this)"
                                class="absolute right-0 h-full px-2 text-gray-500 hover:text-blue-600"
                                title="Adicionar Novo Fornecedor">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </td>
                    </td>
                    <td class="p-2 align-top"><input type="number" name="quantidade" placeholder="Qtd" class="w-24 p-2 border rounded" min="0.01" step="0.01" required oninput="calcularTotalLinha(this)"></td>
                    <td class="p-2 align-top"><input type="text" name="und_medida" placeholder="un" class="w-16 p-2 border rounded" required></td>
                    <td class="p-2 align-top"><input type="number" name="valor_item" placeholder="0.00" class="w-28 p-2 border rounded" min="0.01" step="0.01" required oninput="calcularTotalLinha(this)"></td>
                    <td class="p-2 align-top"><input type="number" name="desconto" value="0" placeholder="0.00" class="w-28 p-2 border rounded" min="0" step="0.01" required oninput="calcularTotalLinha(this)"></td>
                    <td class="p-2 align-top font-semibold text-gray-800"><span class="valor-total-linha">R$ 0,00</span></td>
                    <td class="p-2 text-center align-top"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 text-2xl font-bold" title="Remover Item">&times;</button></td>
                </tr>
            </template>




        </div>
    </div>

    <dialog id="modalFornecedor" class="w-[90%] max-w-2xl rounded-lg p-6 bg-white shadow-xl">
        <h2 class="text-xl font-semibold mb-4">Cadastrar Fornecedor</h2>
        <form id="formFornecedor">
            <div class="grid grid-cols-2 gap-4">
                <input name="razao_social" placeholder="Razão Social" class="border p-2 rounded" required>
                <input name="nome_fantasia" placeholder="Nome Fantasia" class="border p-2 rounded" required>
                <input name="cnpj" placeholder="CNPJ" class="border p-2 rounded" required>
                <input name="inscricao_estadual" placeholder="Inscrição Estadual" class="border p-2 rounded">
                <input name="inscricao_municipal" placeholder="Inscrição Municipal" class="border p-2 rounded">
                <input name="email" placeholder="Email" class="border p-2 rounded">
                <input name="telefone" placeholder="Telefone" class="border p-2 rounded">
                <input name="celular" placeholder="Celular" class="border p-2 rounded">
                <input name="site" placeholder="Site" class="border p-2 rounded">
                <input name="contato_responsavel" placeholder="Contato Responsável" class="border p-2 rounded">
                <input name="endereco" placeholder="Endereço" class="border p-2 rounded">
                <input name="numero" placeholder="Número" class="border p-2 rounded">
                <input name="complemento" placeholder="Complemento" class="border p-2 rounded">
                <input name="bairro" placeholder="Bairro" class="border p-2 rounded">
                <input name="cidade" placeholder="Cidade" class="border p-2 rounded">
                <input name="estado" placeholder="Estado" class="border p-2 rounded">
                <input name="cep" placeholder="CEP" class="border p-2 rounded">
            </div>
            <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">
            <div class="flex justify-end mt-4 gap-2">
                <button type="button" onclick="document.getElementById('modalFornecedor').close()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </dialog>
    <script>
        // =================================================================================
        // SCRIPT ÚNICO E COMPLETO PARA GERENCIAMENTO DA PÁGINA DE COTAÇÃO
        // =================================================================================

        // Bloco 1: Ponte de Dados entre PHP e JavaScript
        // "Imprimimos" os arrays do PHP diretamente em variáveis JavaScript.
        // Usamos json_encode com flags de segurança para evitar erros de sintaxe e XSS.
            const itensDaSolicitacao = <?= json_encode($sc["itens"] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const nomesDosInsumos = <?= json_encode($insumos_nomes ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;


        // Garante que o script só execute após o HTML estar completamente carregado.
        document.addEventListener('DOMContentLoaded', function() {

            // Bloco 2: Seleção dos elementos principais do DOM
            const formPrincipal = document.getElementById('form-criar-cotacao');
            const tabelaBody = document.getElementById('cotacao-table-body');
            const templateLinha = document.getElementById('template-cotacao-item');
            const btnAdicionarLinhaPrincipal = document.getElementById('btn-adicionar-item-cotacao');
            const modalFornecedor = document.getElementById('modalFornecedor');
            const formFornecedor = document.getElementById('formFornecedor');
            const datalistFornecedores = document.getElementById('fornecedores-datalist');
            let campoFornecedorAtivo = null;

            // Bloco 3: Funções principais

            /**
             * Função que cria uma nova linha na tabela a partir do template.
             * @returns {HTMLElement} - Retorna o elemento <tr> recém-criado.
             */
            const adicionarLinha = () => {
                const clone = templateLinha.content.cloneNode(true);
                tabelaBody.appendChild(clone);
                return tabelaBody.lastElementChild;
            };

            /**
             * ESTA É A FUNÇÃO QUE FAZ O PREENCHIMENTO AUTOMÁTICO
             * Ela é chamada uma única vez quando a página carrega.
             */
            const popularTabelaComItensIniciais = () => {
                // Limpa qualquer conteúdo pré-existente para garantir que a tabela comece limpa.
                tabelaBody.innerHTML = '';

                // Verifica se o PHP encontrou itens na solicitação.
                if (!itensDaSolicitacao || itensDaSolicitacao.length === 0) {
                    // Se não encontrou, adiciona uma linha em branco para o usuário começar do zero.
                    adicionarLinha();
                    console.warn("Nenhum item encontrado na solicitação de compra para pré-carregar.");
                    return;
                }

                // Se encontrou itens, percorre cada um deles.
                itensDaSolicitacao.forEach(item => {
                    const novaLinha = adicionarLinha(); // Cria uma nova linha na tabela

                    // Preenche os campos da linha com os dados da solicitação
                    const inputInsumo = novaLinha.querySelector('[name="insumo_nome"]');
                    const inputQuantidade = novaLinha.querySelector('[name="quantidade"]');
                    const inputUnidade = novaLinha.querySelector('[name="und_medida"]');

                    // Usa o mapa 'nomesDosInsumos' para obter o nome a partir do 'insumo_id'
                    inputInsumo.value = nomesDosInsumos[item.insumo_id] || `Insumo ID ${item.insumo_id} não encontrado`;
                    inputInsumo.readOnly = true; // Bloqueia a edição do nome do insumo
                    inputInsumo.classList.add('bg-gray-100', 'cursor-not-allowed', 'font-medium');

                    inputQuantidade.value = item.quantidade;
                    inputUnidade.value = item.und_medida;
                });
            };

            // Bloco 4: Event Listeners para interatividade

            // Botão "+ Adicionar Item à Cotação"
            btnAdicionarLinhaPrincipal.addEventListener('click', adicionarLinha);

            // Event Delegation para cliques e inputs dentro da tabela (mais eficiente)
            tabelaBody.addEventListener('click', function(event) {
                const target = event.target;
                const btnRemover = target.closest('.btn-remover-item');
                const btnAddFornecedor = target.closest('.btn-add-fornecedor');

                if (btnRemover) {
                    btnRemover.closest('tr').remove();
                }

                if (btnAddFornecedor) {
                    campoFornecedorAtivo = btnAddFornecedor.closest('.relative').querySelector('input[name="fornecedor_nome"]');
                    modalFornecedor.showModal();
                }
            });

            tabelaBody.addEventListener('input', function(event) {
                if (['quantidade', 'valor_item', 'desconto'].includes(event.target.name)) {
                    calcularTotalLinha(event.target.closest('tr'));
                }
            });

            // Listener para o formulário do modal de fornecedor
            formFornecedor.addEventListener('submit', async function(e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                const nomeFantasiaNovo = formData.get('nome_fantasia');

                try {
                    const response = await fetch("salvar_fornecedor.php", {
                        method: "POST",
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        Toastify({
                            text: "Fornecedor salvo!",
                            backgroundColor: "#10b981"
                        }).showToast();

                        // Adiciona a nova opção na lista de fornecedores
                        const novaOption = document.createElement("option");
                        novaOption.value = nomeFantasiaNovo;
                        novaOption.dataset.id = result.id; // Supondo que o PHP retorna o novo ID
                        datalistFornecedores.appendChild(novaOption);

                        // Preenche o campo que abriu o modal e limpa a referência
                        if (campoFornecedorAtivo) {
                            campoFornecedorAtivo.value = nomeFantasiaNovo;
                            campoFornecedorAtivo = null;
                        }

                        form.reset();
                        modalFornecedor.close();
                    } else {
                        alert("Erro: " + result.error);
                    }
                } catch (error) {
                    console.error(error);
                    alert("Erro de comunicação ao salvar fornecedor.");
                }
            });

            // Listener para o formulário principal da cotação
            formPrincipal.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(formPrincipal);
                const produtos = [];
                const linhas = tabelaBody.querySelectorAll('tr');

                if (linhas.length === 0) {
                    alert('Adicione pelo menos um item à cotação.');
                    return;
                }

                let hasError = false;
                linhas.forEach(linha => {
                    const insumoNome = linha.querySelector('[name="insumo_nome"]').value;
                    const fornecedorNome = linha.querySelector('[name="fornecedor_nome"]').value;

                    if (!insumoNome || !fornecedorNome) {
                        hasError = true;
                    }

                    // Monta o objeto com as chaves que o seu create.php espera
                    produtos.push({
                        insumo_nome: insumoNome,
                        fornecedor_nome: fornecedorNome,
                        insumo_quantidade: linha.querySelector('[name="quantidade"]').value,
                        insumo_unidade: linha.querySelector('[name="und_medida"]').value,
                        valorUnt: linha.querySelector('[name="valor_item"]').value,
                        desconto: linha.querySelector('[name="desconto"]').value,
                        descricao_tecnica: ''
                    });
                });

                if (hasError) {
                    alert('O campo Insumo e Fornecedor são obrigatórios em todas as linhas.');
                    return;
                }

                // Adiciona a lista de itens como JSON ao FormData
                formData.append('produtos', JSON.stringify(produtos));

                // Envia para o backend
                fetch('./create.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.sucesso) {
                            Toastify({
                                text: data.mensagem || "Cotação criada com sucesso!",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#10b981"
                            }).showToast();
                            setTimeout(() => window.location.href = '../cotacoes/', 2000);
                        } else {
                            Toastify({
                                text: "Erro: " + (data.erro || "Não foi possível salvar."),
                                duration: 4000,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#ef4444"
                            }).showToast();
                        }
                    })
                    .catch(err => {
                        console.error("Erro na requisição:", err);
                        Toastify({
                            text: "Erro de comunicação com o servidor.",
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#ef4444"
                        }).showToast();
                    });
            });

            // Bloco 5: Execução Inicial
            // Finalmente, chama a função para popular a tabela assim que o script é carregado.
            popularTabelaComItensIniciais();

        }); // Fim do DOMContentLoaded


        // Funções globais que precisam ser acessadas pelo HTML (onclick) ou por simplicidade
        function calcularTotalLinha(input) {
            const linha = input.closest('tr');
            if (!linha) return;
            const quantidade = parseFloat(linha.querySelector('[name="quantidade"]').value) || 0;
            const valorItem = parseFloat(linha.querySelector('[name="valor_item"]').value) || 0;
            const desconto = parseFloat(linha.querySelector('[name="desconto"]').value) || 0;
            const total = (quantidade * valorItem) - desconto;
            const spanTotal = linha.querySelector('.valor-total-linha');
            if (spanTotal) {
                spanTotal.textContent = total.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
            }
        }
    </script>
    <script>
        let campoFornecedorAtivo = null;

        function abrirModalFornecedor(buttonElement) {
            // Guarda o input de fornecedor que está ao lado do botão clicado
            campoFornecedorAtivo = buttonElement.previousElementSibling;
            document.getElementById('modalFornecedor').showModal();
        }
        document.getElementById("formFornecedor").addEventListener("submit", async function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            try {
                const response = await fetch("salvar_fornecedor.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert("Fornecedor salvo com sucesso!");

                    // Adiciona ao select
                    const select = document.getElementById("fornecedor");
                    const option = document.createElement("option");
                    option.value = result.nome_fantasia;
                    option.textContent = result.nome_fantasia;
                    option.selected = true;
                    select.appendChild(option);

                    form.reset();
                    document.getElementById("modalFornecedor").close();
                } else {
                    alert("Erro: " + result.message);
                }
            } catch (error) {
                alert("Erro ao salvar fornecedor.");
                console.error(error);
            }
        });
    </script>




</body>