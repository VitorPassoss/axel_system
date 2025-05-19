<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];
$os = null;
$obra = null;
$contrato = null;

if (isset($_GET['sc_id'])) {
    $sc_id = intval($_GET['sc_id']);

    // Recuperar dados da OS
    $stmt = $conn->prepare("SELECT * FROM ordem_de_servico WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $sc_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $os = $result->fetch_assoc();

    // Verificar se a OS foi encontrada
    if ($os) {
        $obra_id = $os['obra_id'];

        // Recuperar dados da obra relacionada à OS
        $stmtObra = $conn->prepare("SELECT * FROM obras WHERE id = ? AND empresa_id = ?");
        $stmtObra->bind_param("ii", $obra_id, $empresa_id);
        $stmtObra->execute();
        $resultObra = $stmtObra->get_result();
        $obra = $resultObra->fetch_assoc();

        // Se encontrar a obra, buscar o contrato relacionado
        if ($obra) {
            $contrato_id = $obra['contrato_id'];

            // Recuperar dados do contrato relacionado à obra
            $stmtContrato = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
            $stmtContrato->bind_param("ii", $contrato_id, $empresa_id);
            $stmtContrato->execute();
            $resultContrato = $stmtContrato->get_result();
            $contrato = $resultContrato->fetch_assoc();
        }


        $solicitacoes = [];

        $stmtSC = $conn->prepare("SELECT * FROM solicitacao_compras WHERE os_id = ? AND empresa_id = ?");
        $stmtSC->bind_param("ii", $sc_id, $empresa_id);
        $stmtSC->execute();
        $resultSC = $stmtSC->get_result();

        while ($row = $resultSC->fetch_assoc()) {
            // Buscar itens da solicitação
            $stmtItens = $conn->prepare("SELECT * FROM sc_item WHERE solicitacao_id = ?");
            $stmtItens->bind_param("i", $row['id']);
            $stmtItens->execute();
            $resultItens = $stmtItens->get_result();
            $itens = [];

            while ($item = $resultItens->fetch_assoc()) {
                $itens[] = $item;
            }

            $row['itens'] = $itens; // Anexa os itens à solicitação
            $solicitacoes[] = $row;

            $stmtItens->close();
        }

        $stmtSC->close();
    }
}

// Carregar obras da empresa
$obras = [];
$stmtObras = $conn->prepare("SELECT id, nome FROM obras WHERE empresa_id = ?");
$stmtObras->bind_param("i", $empresa_id);
$stmtObras->execute();
$resultObras = $stmtObras->get_result();
while ($row = $resultObras->fetch_assoc()) {
    $obras[] = $row;
}

// Carregar projetos da empresa
$projetos = [];
$stmtProjetos = $conn->prepare("SELECT id, nome FROM projetos WHERE empresa_id = ?");
$stmtProjetos->bind_param("i", $empresa_id);
$stmtProjetos->execute();
$resultProjetos = $stmtProjetos->get_result();
while ($row = $resultProjetos->fetch_assoc()) {
    $projetos[] = $row;
}

$servicos = [];
$stmtServicos = $conn->prepare("
    SELECT 
        servicos_os.id,
        servicos.nome,
        servicos_os.und_do_servico,
        servicos_os.quantidade,
        servicos_os.tipo_servico,
        servicos_os.executor,
        servicos_os.dt_inicio,
        servicos_os.dt_final
    FROM servicos_os
    INNER JOIN servicos ON servicos.id = servicos_os.servico_id
    WHERE servicos_os.os_id = ?
");
$stmtServicos->bind_param("i", $sc_id);
$stmtServicos->execute();
$resultServicos = $stmtServicos->get_result();
while ($row = $resultServicos->fetch_assoc()) {
    $servicos[] = $row;
}

?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ordem de Serviço - Detalhes</title>

    <script src="https://cdn.tailwindcss.com"></script>



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
                    <button onclick="window.location.href='../os'" class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-800">Detalhes da O.S - N <?= htmlspecialchars($os['id']) ?></h1>

                </div>
                <div class="flex gap-3">
                    <button onclick="window.location.href='./relatorio_fotografico?sc_id=<?php echo htmlspecialchars($os['id']); ?>'"" class=" bg-blue-800 text-white px-5 py-2.5 rounded-xl shadow hover:bg-blue-700 transition duration-200 flex items-center gap-2">
                        <i class="fas fa-camera-retro"></i> <!-- Ícone de câmera -->
                        Levantamento
                    </button>
                    <button onclick="window.location.href='./sc_compra?sc_id=<?php echo htmlspecialchars($os['id']); ?>'" class="bg-blue-800 text-white px-5 py-2.5 rounded-xl shadow hover:bg-blue-700 transition duration-200 flex items-center gap-2">
                        <i class="fas fa-cart-plus"></i> <!-- Ícone de carrinho de compras -->
                        Solicitar Compra
                    </button>

                </div>

            </header>


            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (isset($os['id'])): ?>
                        <input id="osId" type="hidden" name="id" value="<?= htmlspecialchars($os['id']) ?>">
                    <?php endif; ?>
                    <div class="flex flex-col">
                        <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Descrição da Os</label>
                        <input type="text" id="descricao" name="descricao"
                            value="<?= htmlspecialchars($os['descricao'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="responsavel" class="text-gray-700 mb-1 text-sm font-medium">Responsavel</label>
                        <input type="text" id="responsavel_os" name="responsavel_os" required
                            value="<?= htmlspecialchars($os['responsavel_os'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="numero_os" class="text-gray-700 mb-1 text-sm font-medium">Número O.S</label>
                        <input type="number" step="0.01" id="numero_os" name="numero_os" required
                            value="<?= htmlspecialchars($os['numero_os'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="status" class="text-gray-700 mb-1 text-sm font-medium">Status</label>
                        <select id="status" name="status"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 p-3">
                            <?php
                            $status_options = ['Aberta', 'Em andamento', 'Concluída', 'Cancelada'];
                            foreach ($status_options as $status) {
                                $selected = (isset($os['status']) && $os['status'] == $status) ? 'selected' : '';
                                echo "<option value=\"$status\" $selected>$status</option>";
                            }
                            ?>
                        </select>
                    </div>



                    <div class="flex flex-col">
                        <label for="data_inicio" class="text-gray-700 mb-1 text-sm font-medium">Data de Início</label>
                        <input type="date" id="data_inicio" name="data_inicio"
                            value="<?= htmlspecialchars($os['data_inicio'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="data_final" class="text-gray-700 mb-1 text-sm font-medium">Data de Conclusão</label>
                        <input type="date" id="data_final" name="data_final"
                            value="<?= htmlspecialchars($os['data_final'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>


                    <div class="flex flex-col">
                        <label for="local" class="text-gray-700 mb-1 text-sm font-medium">Local</label>
                        <input type="text" step="0.01" id="local" name="local" required
                            value="<?= htmlspecialchars($os['local'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>



                    <div class="flex flex-col ">
                        <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Anexos</label>
                        <input type="file" id="anexos" name="anexos[]" multiple
                            class="w-full bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 p-2" />
                    </div>




                </div>



                <div class="flex justify-end gap-6">

                    <button onclick="generatePDF()" name="salvar"
                        class="bg-gray-400 hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        Gerar PDF
                    </button>
                    <button type="submit" name="salvar"
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <?= isset($os['id']) ? 'Salvar Alterações' : 'Criar Ordem de Serviço' ?>
                    </button>
                </div>

            </form>


            <?php if ($obra): ?>
                <div class="w-full bg-white p-6 rounded-lg shadow-lg mt-10">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Informações da Obra</h3>

                    <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo $obra['nome']; ?></h3>


                    <!-- Endereço será preenchido dinamicamente -->
                    <p class="text-sm text-gray-700 mb-1">
                        <strong>Endereço:</strong>
                        <span id="endereco">Carregando...</span>
                    </p>

                    <p class="text-sm text-gray-700 mb-4"><strong>Responsável Técnico:</strong> <?php echo $obra['responsavel_tecnico']; ?></p>

                    <a href="../Obras/detalhes.php?obra_id=<?php echo $obra['id']; ?>" class="inline-block bg-[#171717] text-white py-2 px-4 rounded-lg text-center hover:bg-blue-600 transition-colors duration-200">Ver mais detalhes</a>
                </div>

                <script>
                    function generatePDF() {
                        const osId = document.getElementById('osId')?.value;
                        if (!osId) {
                            alert("ID da OS não encontrado.");
                            return;
                        }

                        // Abre em nova aba
                        window.open('gerar_os.php?id=' + encodeURIComponent(osId), '_blank');
                    }

                    // Buscar dados do CEP via API
                    const cep = "<?php echo preg_replace('/[^0-9]/', '', $obra['cep']); ?>";
                    if (cep.length === 8) {
                        fetch(`https://viacep.com.br/ws/${cep}/json/`)
                            .then(response => response.json())
                            .then(data => {
                                if (!data.erro) {
                                    const endereco = `${data.logradouro}, ${data.bairro}, ${data.localidade} - ${data.uf}, CEP: ${data.cep}`;
                                    document.getElementById('endereco').innerText = endereco;
                                } else {
                                    document.getElementById('endereco').innerText = "CEP não encontrado.";
                                }
                            })
                            .catch(() => {
                                document.getElementById('endereco').innerText = "Erro ao buscar CEP.";
                            });
                    } else {
                        document.getElementById('endereco').innerText = "CEP inválido.";
                    }
                </script>
            <?php endif; ?>


            <div class="w-full bg-white p-6 rounded-lg shadow-lg w-full mt-10">
                <h2 class="text-xl font-bold text-gray-800 dark:text-black mb-6 text-start">
                    Serviços Vinculados
                </h2>
                <!-- Botão para abrir o modal, alterando o tipo para "button" para evitar o envio do formulário -->
                <button type="button" class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300" data-modal-toggle="adicionarServicoModal">
                    Adicionar Serviço à OS
                </button>

                <div id="servicosContainer" class="mt-6 overflow-x-auto">
                    <table class="w-full table-auto border-collapse rounded-lg overflow-hidden">
                        <thead class="bg-[#F3F5F7] text-left">
                            <tr>
                                <th class="p-3">Nome</th>
                                <th class="p-3">Unidade</th>
                                <th class="p-3">Quantidade</th>
                                <th class="p-3">Tipo</th>
                                <th class="p-3">Executor</th>
                                <th class="p-3">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($servicos as $servico): ?>
                                <tr class="servicoItem" data-servico-id="<?= $servico['id'] ?>">
                                    <td class="p-3 font-semibold"><?= htmlspecialchars($servico['nome']) ?></td>
                                    <td class="p-3"><?= htmlspecialchars($servico['und_do_servico']) ?></td>
                                    <td class="p-3"><?= htmlspecialchars($servico['quantidade']) ?></td>
                                    <td class="p-3"><?= htmlspecialchars($servico['tipo_servico']) ?></td>
                                    <td class="p-3"><?= htmlspecialchars($servico['executor']) ?></td>
                                    <td class="p-3">
                                        <button class="text-red-500 hover:text-red-700 removeServicoBtn">Remover</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>



                <input type="hidden" name="servicos_vinculados" id="servicosVinculados">
            </div>




            <?php
            $totalSolicitacoes = count($solicitacoes);
            $totalInsumos = 0;
            $quantidadeTotal = 0;

            foreach ($solicitacoes as $sc) {
                if (!empty($sc['itens'])) {
                    $totalInsumos += count($sc['itens']);
                    foreach ($sc['itens'] as $item) {
                        $quantidadeTotal += (float) $item['quantidade'];
                    }
                }
            }
            ?>




            <div id="adicionarServicoModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
                <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Adicionar Serviço à OS</h2>
                        <button type="button" class="text-gray-500 hover:text-gray-700" onclick="toggleModal()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form id="formAdicionarServico">
                        <!-- Dropdown de serviços -->
                        <div class="mb-4">
                            <?php if (isset($os['id'])): ?>
                                <input id="osId" type="hidden" name="os_id" value="<?= htmlspecialchars($os['id']) ?>">
                            <?php endif; ?>

                            <label for="servicos" class="block text-sm font-medium text-gray-700">Serviço</label>
                            <input list="lista-servicos" name="nome" id="servicos"
                                class="block w-full mt-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Digite ou selecione um serviço" required>

                            <datalist id="lista-servicos">
                                <?php
                                $sql = "SELECT nome FROM servicos";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['nome'], ENT_QUOTES) . "'>";
                                    }
                                }
                                ?>
                            </datalist>

                        </div>

                        <!-- Unidade de Medida -->
                        <div class="mb-4">
                            <label for="und_do_servico" class="block text-sm font-medium text-gray-700">Unidade de Medida</label>
                            <input type="text" id="und_do_servico" name="und_do_servico" class="block w-full mt-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>

                        <!-- Quantidade -->
                        <div class="mb-4">
                            <label for="quantidade" class="block text-sm font-medium text-gray-700">Quantidade</label>
                            <input type="number" id="quantidade" name="quantidade" class="block w-full mt-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>

                        <!-- Tipo de Serviço -->
                        <div class="mb-4">
                            <label for="tipo_servico" class="block text-sm font-medium text-gray-700">Tipo de Serviço</label>
                            <select id="tipo_servico" name="tipo_servico" class="block w-full mt-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                <option value="preventiva">Preventiva</option>
                                <option value="corretiva">Manutenção</option>
                            </select>
                        </div>

                        <!-- Executor -->
                        <div class="mb-4">
                            <label for="executor" class="block text-sm font-medium text-gray-700">Equipe</label>
                            <input type="text" id="executor" name="executor" class="block w-full mt-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>

                        <!-- Data de Início -->
                        <div class="mb-4">
                            <label for="dt_inicio" class="block text-sm font-medium text-gray-700">Data de Início</label>
                            <input type="date" id="dt_inicio" name="dt_inicio" class="block w-full mt-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>

                        <!-- Data Final -->
                        <div class="mb-4">
                            <label for="dt_final" class="block text-sm font-medium text-gray-700">Data Final</label>
                            <input type="date" id="dt_final" name="dt_final" class="block w-full mt-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>

                        <!-- Botões -->
                        <div class="flex justify-end space-x-4">
                            <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600" onclick="toggleModal()">Cancelar</button>
                            <button id="addServicoBtn" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>




            <?php if (isset($os['id'])): ?>
                <div class="mt-10 p-6 bg-white rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Documentos Vinculados</h3>
                    <?php
                    $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'ordem_de_servico' AND ref_id = ?");
                    $stmt_docs->bind_param("i", $sc_id);
                    $stmt_docs->execute();
                    $result_docs = $stmt_docs->get_result();
                    if ($result_docs->num_rows > 0):
                    ?>
                        <ul class="divide-y divide-gray-200">
                            <?php while ($doc = $result_docs->fetch_assoc()): ?>
                                <li class="flex items-center justify-between py-2">
                                    <div>
                                        <a href="./<?= htmlspecialchars($doc['caminho_arquivo']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                            <?= htmlspecialchars($doc['nome']) ?>
                                        </a>
                                    </div>
                                    <form method="POST" action="delete_document.php" onsubmit="return confirm('Tem certeza que deseja excluir este documento?')">
                                        <input type="hidden" name="documento_id" value="./uploads/empresas/<?= $doc['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Excluir</button>
                                    </form>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-500">Nenhum documento encontrado.</p>
                    <?php endif; ?>
                    <?php $stmt_docs->close(); ?>
                </div>
            <?php endif; ?>


            <div class="mt-10 p-6 bg-white rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-6 text-gray-800">Solicitações de Compra desta OS</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg text-center">
                        <div class="text-sm text-blue-800 font-semibold">Total de Solicitações</div>
                        <div class="text-2xl text-blue-900 font-bold"><?= $totalSolicitacoes ?></div>
                    </div>
                    <div class="bg-green-50 border border-green-200 p-4 rounded-lg text-center">
                        <div class="text-sm text-green-800 font-semibold">Valor Total Solicitado</div>
                        <div class="text-2xl text-green-900 font-bold"><?= $totalInsumos ?></div>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 p-4 rounded-lg text-center">
                        <div class="text-sm text-purple-800 font-semibold">Valor Total Aprovado</div>
                        <div class="text-2xl text-purple-900 font-bold"><?= $quantidadeTotal ?></div>
                    </div>
                </div>
                <?php if (!empty($solicitacoes)): ?>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($solicitacoes as $sc): ?>
                            <div class="bg-gray-50 border border-gray-200 rounded-xl shadow-sm p-5 hover:shadow-md transition duration-200">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-400">Solicitação #<?= htmlspecialchars($sc['id']) ?></span>
                                    <?php
                                    $status = strtolower($sc['status']);
                                    $statusColor = match ($status) {
                                        'pendente' => 'bg-yellow-100 text-yellow-800',
                                        'aprovado' => 'bg-green-100 text-green-800',
                                        'rejeitado' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-200 text-gray-600',
                                    };
                                    ?>
                                    <span class="text-xs font-medium px-3 py-1 rounded-full <?= $statusColor ?>">
                                        <?= ucfirst($sc['status']) ?>
                                    </span>



                                </div>
                                <div class="text-gray-800 text-base font-medium mb-2">
                                    <?= htmlspecialchars($sc['descricao']) ?>
                                </div>

                                <?php if (!empty($sc['itens'])): ?>
                                    <div class="mt-4">
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Itens:</h4>
                                        <ul class="space-y-3 text-sm text-gray-700">
                                            <?php foreach ($sc['itens'] as $item): ?>
                                                <li class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                        <div>
                                                            <div class="mb-1">
                                                                <span class="font-semibold text-gray-500">ID:</span>
                                                                <span class="ml-1"><?= htmlspecialchars($item['id']) ?></span>
                                                            </div>
                                                            <div class="mb-1">
                                                                <span class="font-semibold text-gray-500">Insumo ID:</span>
                                                                <span class="ml-1"><?= htmlspecialchars($item['insumo_id']) ?></span>
                                                            </div>
                                                            <div>
                                                                <span class="font-semibold text-gray-500">Quantidade:</span>
                                                                <span class="ml-1"><?= htmlspecialchars($item['quantidade']) ?></span>
                                                            </div>

                                                            <?php
                                                            $grauTexto = $item['grau'] ?? '';
                                                            $grauLower = strtolower($grauTexto);
                                                            $grauColor = match ($grauTexto) {
                                                                'Baixa' => 'bg-yellow-100 text-yellow-800',
                                                                'Pouca' => 'bg-green-100 text-green-800',
                                                                'Sinistro' => 'bg-red-100 text-red-800',
                                                                'Urgencia' => 'bg-red-100 text-red-800',
                                                                'Alta' => 'bg-red-100 text-red-800',
                                                                'Media' => 'bg-orange-100 text-yellow-800',
                                                                default => 'bg-gray-200 text-gray-600',
                                                            };
                                                            ?>
                                                            <div class="mt-1">
                                                                <span class="font-semibold text-gray-500">Grau:</span>
                                                                <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium <?= $grauColor ?>">
                                                                    <?= htmlspecialchars($grauTexto) ?>
                                                                </span>
                                                            </div>

                                                        </div>

                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>


                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">Nenhuma solicitação de compra encontrada para esta OS.</p>
                <?php endif; ?>
            </div>


        </div>
    </div>


    <script src="./js/detalhes.js"></script>




</body>