<?php
include '../../layout/imports.php';

session_start();

// Função para verificar se o usuário está autenticado
function verificarAutenticacao()
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: ../onboard/login.php");
        exit();
    }
}

// Chama a função automaticamente
verificarAutenticacao();

// Conexão com o banco
include '../../backend/dbconn.php';

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
// Armazenar o empresa_id na sessão


// Busca os dados completos do usuário logado
$stmt = $conn->prepare("
   SELECT 
    u.id, u.email,  u.is_superuser,
    u.setor_id, u.empresa_id,
    COALESCE(s.nome, '') AS setor_nome,
    COALESCE(e.localizacao, '') AS empresa_nome
FROM users u
LEFT JOIN setores s ON u.setor_id = s.id
LEFT JOIN empresas e ON u.empresa_id = e.id
WHERE u.id = ?

");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Usuário não encontrado.");
}


$usuario = $result->fetch_assoc();

$_SESSION['empresa_id'] = $usuario['empresa_id'];

$stmt->close();




// Conexão com o banco de dados



if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];


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


?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Solicitar Compra</title>

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
    <?php include '../../layout/sidemenu_in.php'; ?>
    <div class="w-full ">
        <div class="relative  p-8 animate-fadeIn">




            <header class="bg-white rounded-2xl shadow-lg p-6 mb-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="window.location.href='../detalhes.php?sc_id=<?php echo htmlspecialchars($os['id']); ?>'" class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-800"> Solicitar Compra - OS N<?= htmlspecialchars($os['id']) ?></h1>

                </div>


            </header>




            <div class="flex gap-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-8 w-[50%] rounded shadow">
                    <div class="flex flex-col">
                        <label for="insumo-input" class="text-gray-700 mb-1 text-sm font-medium">Insumo</label>
                        <input id="insumo-input" list="insumos" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" placeholder="Digite ou selecione um insumo">
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
                    <div class="flex flex-col ">
                        <label for="quantidade-input" class="text-gray-700 mb-1 text-sm font-medium">Quantidade</label>
                        <input type="number" id="quantidade-input" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" placeholder="Quantidade">
                    </div>

                    <div class="flex flex-col">
                        <label for="grau" class="text-gray-700 mb-1 text-sm font-medium">Grau de Prioridade do Insumo</label>
                        <select id="grau" name="grau" required
                            class="w-full bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 p-2">
                            <option value="" disabled selected>Selecione o grau</option>
                            <option value="Sinistro">🚨 Sinistro</option>
                            <option value="Urgencia">🚨 Urgência</option>
                            <option value="Alta">🔴 Alta</option>
                            <option value="Media">🟠 Média</option>
                            <option value="Baixa">🟡 Baixa</option>
                            <option value="Pouca">🟢 Pouca</option>
                        </select>
                    </div>

                    <!-- Campo Unidade de Medida -->


                    <!-- Botão Adicionar -->
                    <div class="flex flex-col col-span-2 ">
                        <button type="button" onclick="adicionarInsumo()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            Adicionar Insumo
                        </button>
                    </div>

                    <!-- Lista de Insumos Adicionados -->

                </div>

                <div id="insumos-container" class="bg-white p-8 w-[50%] rounded shadow max-h-[290px] overflow-y-auto space-y-4">
                    <h1 class="text-xl font-bold mb-4">Insumos Adicionados</h1>
                    <!-- Cards vão aparecer aqui -->
                </div>

            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-6 mt-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (isset($os['id'])): ?>
                        <input id="osId" type="hidden" name="id" value="<?= htmlspecialchars($os['id']) ?>">
                    <?php endif; ?>


                    <div class="flex flex-col col-span-2">
                        <label for="solicitante " class="text-gray-700 mb-1 text-sm font-medium">Solicitante</label>
                        <input type="text" id="solicitante" name="solicitante" required
                            value=""
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>



                    <!-- <div class="flex flex-col">
                        <label for="grau" class="text-gray-700 mb-1 text-sm font-medium">Grau de Prioridade Geral</label>
                        <select id="grau" name="grau1" required
                            class="w-full bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 p-2">
                            <option value="" disabled selected>Selecione o grau</option>
                            <option value="Sinistro">🚨 Sinistro</option>
                            <option value="Urgencia">🚨 Urgência</option>
                            <option value="Alta">🔴 Alta</option>
                            <option value="Media">🟠 Média</option>
                            <option value="Baixa">🟡 Baixa</option>
                            <option value="Pouca">🟢 Pouca</option>
                        </select>
                    </div> -->


                    <div class="flex flex-col col-span-2">
                        <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Motivo</label>
                        <textarea id="descricao" name="descricao" required
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 resize-y min-h-[100px]"></textarea>
                    </div>





                </div>



                <div class="flex justify-end gap-6">


                    <button type="submit" name="salvar"
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        Salvar Alterações
                    </button>
                </div>

            </form>



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

    <script>
        const insumosSelecionados = [];

        function adicionarInsumo() {
            const insumoInput = document.getElementById('insumo-input');
            const quantidadeInput = document.getElementById('quantidade-input');
            const unidadeInput = document.getElementById('unidade-input');
            const grauInput = document.getElementById('grau');
            const container = document.getElementById('insumos-container');

            const insumo = insumoInput.value.trim();
            const quantidade = quantidadeInput.value.trim();
            const unidade = unidadeInput.value.trim();
            const grau = grauInput.value;

            if (!insumo || !quantidade || !unidade || !grau) {
                alert('Preencha todos os campos!');
                return;
            }

            const index = insumosSelecionados.length;

            // Salva o insumo na variável
            insumosSelecionados.push({
                insumo_nome: insumo,
                insumo_quantidade: quantidade,
                insumo_unidade: unidade,
                insumo_grau: grau
            });

            const item = document.createElement('div');
            item.className = 'bg-white border border-gray-300 rounded shadow p-4 flex justify-between items-start';
            item.dataset.index = index;

            item.innerHTML = `
            <div>
                <p class="font-semibold text-gray-800">${insumo}</p>
                <p class="text-gray-600 text-sm">Quantidade: ${quantidade} ${unidade}</p>
                <p class="text-gray-600 text-sm">Grau: ${grau}</p>
            </div>
            <div class="flex items-center">
                <button type="button" class="text-red-600 hover:text-red-800 text-xl font-bold ml-4" onclick="removerInsumo(this)">×</button>
            </div>
            <input type="hidden" name="insumos[${index}][nome]" value="${insumo}">
            <input type="hidden" name="insumos[${index}][quantidade]" value="${quantidade}">
            <input type="hidden" name="insumos[${index}][unidade]" value="${unidade}">
            <input type="hidden" name="insumos[${index}][grau]" value="${grau}">
        `;

            container.appendChild(item);

            // Limpar campos após adicionar
            insumoInput.value = '';
            quantidadeInput.value = '';
            unidadeInput.value = '';
            grauInput.selectedIndex = 0;
        }

        function removerInsumo(button) {
            const item = button.closest('div[data-index]');
            const index = parseInt(item.dataset.index);
            item.remove();

            // Marcar posição como null (pode filtrar depois se quiser limpar array)
            insumosSelecionados[index] = null;
        }


        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault(); // Impede envio padrão

            const solicitante = document.getElementById('solicitante').value;
            const grau = '';
            const descricao = document.getElementById('descricao').value;
            const osIdInput = document.getElementById('osId');
            const osId = osIdInput ? osIdInput.value : null;

            const body = {
                insumos: insumosSelecionados,
                solicitante,
                grau,
                descricao,
            };

            console.log(body)

            if (osId) body.osId = osId;

            // Aqui você pode enviar por fetch
            fetch('./create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                })
                .then(res => {

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


                    setTimeout(() => {
                        window.location.reload()
                    }, 1000);




                })
                .catch(err => {
                    console.error("Erro no envio:", err);
                    Toastify({
                        text: "Erro ao enviar a solicitação. Tente novamente mais tarde.",
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