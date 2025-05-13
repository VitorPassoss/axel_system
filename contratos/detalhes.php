<?php
include '../backend/auth.php';
include '../layout/imports.php';

$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];
$contrato = null;
$obras = [];
$ordens_servico = [];
$projetos = [];

if (isset($_GET['contrato_id'])) {
    $contrato_id = intval($_GET['contrato_id']);

    // Buscar o contrato
    $stmt = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $contrato_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contrato = $result->fetch_assoc();

    if ($contrato) {
        // Buscar obras atreladas
        $stmt = $conn->prepare("SELECT * FROM obras WHERE contrato_id = ?");
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        $obras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Buscar ordens de serviço atreladas
        $stmt = $conn->prepare("SELECT * FROM ordem_de_servico WHERE contrato_id = ?");
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        $ordens_servico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Buscar projetos atrelados
        $stmt = $conn->prepare("SELECT * FROM projetos WHERE contrato_id = ?");
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        $projetos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}


$stmt = $conn->prepare("
    SELECT status, COUNT(*) as total 
    FROM ordem_de_servico 
    WHERE contrato_id = ? 
    GROUP BY status
");
$stmt->bind_param("i", $contrato_id);
$stmt->execute();
$result = $stmt->get_result();

$status_labels = [];
$status_counts = [];

while ($row = $result->fetch_assoc()) {
    $status_labels[] = $row['status'];
    $status_counts[] = $row['total'];
}


$stmt = $conn->prepare("
    SELECT s.nome as status, COUNT(*) as total
    FROM obras o
    JOIN status_obras s ON o.status_id = s.id
    WHERE o.contrato_id = ?
    GROUP BY s.nome
");
$stmt->bind_param("i", $contrato_id);
$stmt->execute();
$result = $stmt->get_result();

$status_obras_labels = [];
$status_obras_counts = [];

while ($row = $result->fetch_assoc()) {
    $status_obras_labels[] = $row['status'];
    $status_obras_counts[] = $row['total'];
}

$stmt = $conn->prepare("
    SELECT o.nome AS obra_nome, COUNT(sc.id) AS total_solicitacoes
    FROM solicitacao_compras sc
    JOIN ordem_de_servico os ON sc.os_id = os.id
    JOIN obras o ON os.obra_id = o.id
    WHERE o.contrato_id = ?
    GROUP BY o.id
");
$stmt->bind_param("i", $contrato_id);
$stmt->execute();
$result = $stmt->get_result();

$obras_labels = [];
$obras_solicitacoes_counts = [];

while ($row = $result->fetch_assoc()) {
    $obras_labels[] = $row['obra_nome'];
    $obras_solicitacoes_counts[] = $row['total_solicitacoes'];
}


$stmt = $conn->prepare("
    SELECT o.nome AS obra_nome, COUNT(os.id) AS total_ordens_servico
    FROM ordem_de_servico os
    JOIN obras o ON os.obra_id = o.id
    WHERE o.contrato_id = ?
    GROUP BY o.id
");
$stmt->bind_param("i", $contrato_id);
$stmt->execute();
$result = $stmt->get_result();

$obras_ordens_labels = [];
$obras_ordens_counts = [];

while ($row = $result->fetch_assoc()) {
    $obras_ordens_labels[] = $row['obra_nome'];
    $obras_ordens_counts[] = $row['total_ordens_servico'];
}


// Definindo as cores para os diferentes status
$status_cores = [
    'Em Andamento' => 'rgba(54, 162, 235, 0.6)',
    'Concluída' => 'rgba(75, 192, 192, 0.6)',
    'Cancelada' => 'rgba(255, 99, 132, 0.6)',
    'Aguardando' => 'rgba(255, 159, 64, 0.6)',
    'Suspensa' => 'rgba(153, 102, 255, 0.6)',
];


?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contratos</title>

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

            <!-- Botão Fechar -->
            <button onclick="window.location.href = './index.php'" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-3xl">
                &times;
            </button>

            <!-- Cabeçalho -->
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-6 text-center">
                Resumo do Contrato
            </h2>


            <div class="mx-auto space-y-8">

                <!-- RESUMO DO CONTRATO -->
                <div class="bg-white shadow-md rounded-2xl p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Resumo do Contrato</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-gray-700">
                        <div><span class="font-semibold">Número do Contrato:</span> <?php echo $contrato['numero_contrato']; ?></div>
                        <div><span class="font-semibold">Valor Mensal:</span> R$ <?php echo number_format($contrato['valor_mensal'], 2, ',', '.'); ?></div>
                        <div><span class="font-semibold">Valor Anual:</span> R$ <?php echo number_format($contrato['valor_anual'], 2, ',', '.'); ?></div>
                        <div><span class="font-semibold">Cliente:</span> <?php echo $contrato['nome_cliente']; ?></div>
                        <div><span class="font-semibold">CNPJ:</span> <?php echo $contrato['cnpj_cliente']; ?></div>
                        <div><span class="font-semibold">Telefone:</span> <?php echo $contrato['telefone_cliente']; ?></div>
                    </div>
                </div>

                <!-- OBRAS EM ANDAMENTO -->
                <div class="bg-white shadow-md rounded-2xl p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Obras em Andamento</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($obras as $obra):
                            $cep = preg_replace('/[^0-9]/', '', $obra['cep']);
                            $enderecoId = "endereco_" . $obra['id']; // ID único baseado no ID da obra
                        ?>
                            <div class="bg-blue-50 p-4 rounded-xl shadow-sm border border-blue-100">
                                <h3 class="font-semibold text-lg text-blue-800">Obra: <?php echo htmlspecialchars($obra['nome']); ?></h3>
                                <p class="text-sm text-blue-700">Status: <?php echo htmlspecialchars($obra['status_id']); ?></p>
                                <p class="text-sm text-blue-700">Endereço: <span id="<?php echo $enderecoId; ?>">Buscando endereço...</span></p>
                                <p class="text-sm text-blue-700">Responsável: <?php echo htmlspecialchars($obra['responsavel_tecnico']); ?></p>
                            </div>

                            <script>
                                (function() {
                                    const cep = "<?php echo $cep; ?>";
                                    const enderecoId = "<?php echo $enderecoId; ?>";
                                    if (cep.length === 8) {
                                        fetch(`https://viacep.com.br/ws/${cep}/json/`)
                                            .then(response => response.json())
                                            .then(data => {
                                                const span = document.getElementById(enderecoId);
                                                if (!data.erro) {
                                                    span.innerText = `${data.logradouro}, ${data.bairro}, ${data.localidade} - ${data.uf}, CEP: ${data.cep}`;
                                                } else {
                                                    span.innerText = "CEP não encontrado.";
                                                }
                                            })
                                            .catch(() => {
                                                document.getElementById(enderecoId).innerText = "Erro ao buscar CEP.";
                                            });
                                    } else {
                                        document.getElementById(enderecoId).innerText = "CEP inválido.";
                                    }
                                })();
                            </script>
                        <?php endforeach; ?>
                    </div>
                </div>


                <!-- ORDENS DE SERVIÇO -->
                <div class="bg-white shadow-md rounded-2xl p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Ordens de Serviço</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-700">
                            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                                <tr>
                                    <th class="p-3">Código</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Data Início</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordens_servico as $ordem): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="p-3"><?php echo $ordem['id']; ?></td>
                                        <td class="p-3"><span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded"><?php echo $ordem['status']; ?></span></td>
                                        <td class="p-3"><?php echo date('d/m/Y', strtotime($ordem['data_inicio'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PROJETOS RELACIONADOS -->
                <div class="bg-white shadow-md rounded-2xl p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Projetos Relacionados</h2>
                    <ul class="list-disc pl-5 text-gray-700 space-y-1">
                        <?php foreach ($projetos as $projeto): ?>
                            <li><span class="font-medium"><?php echo $projeto['nome_projeto']; ?></span> - <?php echo $projeto['descricao_projeto']; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>

            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data" class="space-y-6 mt-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="id" value="<?= $contrato['id'] ?? '' ?>">

                    <div class="flex flex-col">
                        <label for="numero_contrato" class="text-gray-700 mb-1 text-sm font-medium">N° Contrato</label>
                        <input type="text" id="numero_contrato" name="numero_contrato" required
                            value="<?= htmlspecialchars($contrato['numero_contrato'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="numero_empenho" class="text-gray-700 mb-1 text-sm font-medium">N° Empenho</label>
                        <input type="text" id="numero_empenho" name="numero_empenho" required
                            value="<?= htmlspecialchars($contrato['numero_empenho'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="cnpj_cliente" class="text-gray-700 mb-1 text-sm font-medium">CNPJ do Cliente</label>
                        <input type="text" id="cnpj_cliente" name="cnpj_cliente" required
                            value="<?= htmlspecialchars($contrato['cnpj_cliente'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="nome_cliente" class="text-gray-700 mb-1 text-sm font-medium">Nome do Cliente</label>
                        <input type="text" id="nome_cliente" name="nome_cliente" required
                            value="<?= htmlspecialchars($contrato['nome_cliente'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="endereco_cliente" class="text-gray-700 mb-1 text-sm font-medium">Endereço do Cliente</label>
                        <input type="text" id="endereco_cliente" name="endereco_cliente"
                            value="<?= htmlspecialchars($contrato['endereco_cliente'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="telefone_cliente" class="text-gray-700 mb-1 text-sm font-medium">Telefone do Cliente</label>
                        <input type="text" id="telefone_cliente" name="telefone_cliente"
                            value="<?= htmlspecialchars($contrato['telefone_cliente'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="email_cliente" class="text-gray-700 mb-1 text-sm font-medium">Email do Cliente</label>
                        <input type="email" id="email_cliente" name="email_cliente"
                            value="<?= htmlspecialchars($contrato['email_cliente'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="valor_mensal" class="text-gray-700 mb-1 text-sm font-medium">Valor Mensal</label>
                        <input type="number" step="0.01" id="valor_mensal" name="valor_mensal"
                            value="<?= htmlspecialchars($contrato['valor_mensal'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="valor_anual" class="text-gray-700 mb-1 text-sm font-medium">Valor Anual</label>
                        <input type="number" step="0.01" id="valor_anual" name="valor_anual"
                            value="<?= htmlspecialchars($contrato['valor_anual'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Anexos</label>
                        <input type="file" id="anexos" name="anexos[]" multiple
                            class="w-full text-gray-800 dark:text-gray-100" />
                    </div>

                </div>

                <div class="flex flex-col">
                    <label for="observacoes" class="text-gray-700 mb-1 text-sm font-medium">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="4"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100"><?= htmlspecialchars($contrato['observacoes'] ?? '') ?></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" name="criar"
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <?= isset($contrato['id']) ? 'Salvar Alterações' : 'Criar' ?>
                    </button>
                </div>
            </form>



            <?php if (isset($contrato['id'])): ?>
                <div class="mt-10 p-6 bg-white rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Documentos Vinculados</h3>
                    <?php
                    $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'contratos' AND ref_id = ?");
                    $stmt_docs->bind_param("i", $contrato['id']);
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


            <div class="p-6">
                <h1 class="text-2xl font-bold mb-6">Dashboard do Contrato</h1>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Gráfico de Ordens de Serviço -->
                    <div class="bg-white shadow-lg rounded-lg p-[30px] flex flex-col items-center">
                        <h2 class="text-lg font-semibold mb-4">Ordens de Serviço por Status</h2>
                        <canvas id="graficoPizzaOS" class=""></canvas> <!-- largura e altura reduzidas -->
                    </div>

                    <!-- Gráfico de Obras por Status -->
                    <div class="bg-white shadow-lg rounded-lg p-8 flex flex-col items-center">
                        <h2 class="text-lg font-semibold mb-4">Obras por Status</h2>
                        <canvas id="graficoObrasStatus" class="h-[300px]"></canvas> <!-- largura máxima controlada -->
                    </div>

                    <div class="bg-white shadow-lg rounded-lg p-8 flex flex-col items-center">
                        <h2 class="text-lg font-semibold mb-4">Solicitação de Insumos por Obra</h2>
                        <canvas id="graficoSolicitacoesObras" class="h-[300px]"></canvas>
                    </div>

                    <!-- Gráfico de Ordens de Serviço por Obra -->
                    <div class=" bg-white shadow-lg rounded-lg p-8 mt-6">
                        <h2 class="text-lg font-semibold mb-4 text-center">Ordens de Serviço por Obra</h2>
                        <canvas id="graficoOrdensPorObra" class="  w-full h-[200px]"></canvas>
                    </div>


                </div>
            </div>


            <!-- Inclui a lib Chart.js via CDN -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        </div>
    </div>

    <!-- Inclui a lib Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>




    <script>
        const ctxOrdensPorObra = document.getElementById('graficoOrdensPorObra').getContext('2d');
        const statusCores = <?= json_encode($status_cores) ?>; // Passando as cores definidas do PHP para o JS

        new Chart(ctxOrdensPorObra, {
            type: 'bar',
            data: {
                labels: <?= json_encode($obras_ordens_labels) ?>,
                datasets: [{
                    label: 'Ordens de Serviço',
                    data: <?= json_encode($obras_ordens_counts) ?>,
                    backgroundColor: <?= json_encode(array_values($status_cores)) ?>, // Utilizando as cores no gráfico
                    borderColor: 'rgba(0, 0, 0, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // <-- isso deixa o gráfico deitado
                responsive: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Ordens de Serviço por Obra'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>

    <script>
        const ctxSolicitacoesObras = document.getElementById('graficoSolicitacoesObras').getContext('2d');
        const coresSolicitacoes = ['rgba(54, 162, 235, 0.6)', 'rgba(255, 99, 132, 0.6)', 'rgba(75, 192, 192, 0.6)', 'rgba(255, 159, 64, 0.6)']; // Cores personalizadas para as solicitações

        new Chart(ctxSolicitacoesObras, {
            type: 'bar',
            data: {
                labels: <?= json_encode($obras_labels) ?>,
                datasets: [{
                    label: 'Solicitações de Compra',
                    data: <?= json_encode($obras_solicitacoes_counts) ?>,
                    backgroundColor: coresSolicitacoes, // Cores aplicadas
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Solicitações por Obra'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>

    <script>
        const osCtx = document.getElementById('graficoPizzaOS').getContext('2d');
        const obrasCtx = document.getElementById('graficoObrasStatus').getContext('2d');

        // Gráfico de Pizza - Ordens de Serviço
        new Chart(osCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    label: 'Ordens de Serviço',
                    data: <?php echo json_encode($status_counts); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Ordens de Serviço por Status'
                    }
                }
            }
        });

        // Gráfico de Barras - Obras por Status
        new Chart(obrasCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($status_obras_labels); ?>,
                datasets: [{
                    label: 'Obras',
                    data: <?php echo json_encode($status_obras_counts); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'], // Cores personalizadas por status
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Obras por Status'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>



    <script>
        document.querySelector("form").addEventListener("submit", async function(e) {
            e.preventDefault();


            const urlParams = new URLSearchParams(window.location.search);
            const contratoId = urlParams.get("contrato_id");


            const form = e.target;
            const formData = new FormData(form);

            const url = contratoId ? './update.php' : './create.php';


            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    Toastify({
                        text: "Operação realizada com sucesso!",
                        duration: 3000,
                        gravity: "top", // "top" ou "bottom"
                        position: "right", // "left", "center" ou "right"
                        backgroundColor: "#10b981", // Verde (tailwind: bg-green-500)
                        close: true
                    }).showToast();

                    form.reset();

                    window.location.href = './index.php'
                } else {
                    Toastify({
                        text: "Operação com Erro!. Por Favor Consulte o Suporte",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#ef4444", // Vermelho (tailwind: bg-red-500)
                        close: true
                    }).showToast();

                }
            } catch (error) {
                alert('Erro na requisição: ' + error.message);
            }
        });
    </script>


</body>