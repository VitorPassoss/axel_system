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





            <div class="flex gap-6">
                <!-- Lado esquerdo: Formulário -->
                <div class="flex flex-col justify-between bg-white p-8 w-[50%] rounded shadow h-fit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col col-span-2">
                            <label for="insumo-input" class="text-gray-700 mb-1 text-sm font-medium">Descrição do Produto</label>
                            <input id="insumo-input" list="insumos" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" placeholder="Digite ou selecione um produto">
                            <datalist id="insumos">
                                <?php
                                $sql = "SELECT nome FROM insumos";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['nome'], ENT_QUOTES) . "'>";
                                    }
                                }
                                ?>
                            </datalist>
                        </div>

                        <div class="flex flex-col">
                            <label for="unidade-input" class="text-gray-700 mb-1 text-sm font-medium">Unidade de Medida</label>
                            <input type="text" id="unidade-input" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" placeholder="Ex: kg, litros, unidades">
                        </div>
                        <div class="flex flex-col">
                            <label for="quantidade-input" class="text-gray-700 mb-1 text-sm font-medium">Quantidade</label>
                            <input type="number" id="quantidade-input" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" placeholder="Quantidade">
                        </div>

                        <div class="flex flex-col">
                            <label for="valor-input" class="text-gray-700 mb-1 text-sm font-medium">Valor Unitário</label>
                            <input type="number" id="valor-input" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" placeholder="Valor Unitário">
                        </div>

                        <div class="flex flex-col">
                            <label for="fornecedor" class="text-gray-700 mb-1 text-sm font-medium">Fornecedor</label>
                            <div class="flex ">
                                <select id="fornecedor" name="fornecedor" class="w-[80%] rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100">
                                    <option value="">Selecione um fornecedor</option>
                                    <?php
                                    $stmtFornecedores = $conn->prepare("SELECT nome_fantasia FROM fornecedores WHERE empresa_id = ? ORDER BY nome_fantasia");
                                    $stmtFornecedores->bind_param("i", $empresa_id);
                                    $stmtFornecedores->execute();
                                    $fornecedores = $stmtFornecedores->get_result();

                                    while ($fornecedor = $fornecedores->fetch_assoc()) {
                                        $nomeFantasia = htmlspecialchars($fornecedor['nome_fantasia']);
                                        echo "<option value=\"{$nomeFantasia}\">{$nomeFantasia}</option>";
                                    }

                                    $stmtFornecedores->close();
                                    ?>
                                </select>
                                <button type="button" onclick="document.getElementById('modalFornecedor').showModal()" class="w-[20%] bg-blue-600 text-white  rounded-lg hover:bg-green-600">
                                    + 
                                </button>
                            </div>
                        </div>




                    </div>

                    <!-- Botão -->
                    <button onclick="adicionarInsumo()" class="mt-6 bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 self-start">
                        Adicionar ao Carrinho
                    </button>
                </div>

                <!-- Lado direito: Carrinho -->
                <div id="insumos-container" class="bg-white p-8 w-[50%] rounded shadow max-h-[400px] overflow-y-auto space-y-4">
                    <h1 class="text-xl font-bold mb-4">Carrinho de Compras</h1>
                    <!-- Cards vão aparecer aqui -->
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-6 mt-6">
                <!-- Hidden inputs -->
                <input type="hidden" id="os-id" value="<?= htmlspecialchars($os['id'] ?? '', ENT_QUOTES) ?>">
                <input type="hidden" id="obra-id" value="<?= htmlspecialchars($obra['id'] ?? '', ENT_QUOTES) ?>">
                <input type="hidden" id="sc-id" value="<?= htmlspecialchars($sc_id ?? '', ENT_QUOTES) ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (isset($os['id'])): ?>
                        <input id="osId" type="hidden" name="id" value="<?= htmlspecialchars($os['id']) ?>">
                    <?php endif; ?>

                    <div class="flex flex-col col-span-2">
                        <label for="solicitante" class="text-gray-700 mb-1 text-sm font-medium">Cotante</label>
                        <input type="text" id="solicitante" name="solicitante" required
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col col-span-2">
                        <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Observação</label>
                        <textarea id="descricao" name="descricao" required
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 resize-y min-h-[100px]"></textarea>
                    </div>

                    <div class="flex flex-col">
                        <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Cotações Recebidas</label>
                        <input type="file" id="anexos" name="anexos[]" multiple
                            class="w-full bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 p-2" />
                    </div>
                </div>

                <div id="insumos-container" class="space-y-4 mt-4"></div>

                <div class="flex justify-end gap-6">
                    <button type="submit" name="salvar"
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        Salvar Alterações
                    </button>
                </div>
            </form>

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

            const form = e.target;
            const formData = new FormData(form); // captura todos os campos do form, inclusive arquivos

            // adicionar campos adicionais (não estão no form diretamente)
            const cotante = document.getElementById('solicitante').value;
            const descricao = document.getElementById('descricao').value;
            const osId = document.getElementById('os-id').value;
            const obraId = document.getElementById('obra-id').value;
            const scId = document.getElementById('sc-id').value;

            const produtos = insumosSelecionados.filter(i => i !== null);

            // JSON.stringify nos campos complexos
            formData.append('cotante', cotante);
            formData.append('descricao', descricao);
            formData.append('osId', osId);
            formData.append('obraId', obraId);
            formData.append('scId', scId);
            formData.append('produtos', JSON.stringify(produtos));

            fetch('./create.php', {
                    method: 'POST',
                    body: formData // sem definir headers!
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

                        setTimeout(() => window.location.href = './detalhes.php?sc_id=' + scId, 1000);
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