<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];
$contrato = null;
$obras = [];
$ordens_servico = [];
$projetos = [];
$solicitacoes_compra_recentes = [];
$status_labels = [];
$status_counts = [];
$status_obras_labels = [];
$status_obras_counts = [];
$obras_labels = [];
$obras_solicitacoes_counts = [];
$obras_ordens_labels = [];
$obras_ordens_counts = [];

if (isset($_GET['contrato_id'])) {
    $contrato_id = intval($_GET['contrato_id']);

    // Buscar o contrato
    $stmt = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $contrato_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contrato = $result->fetch_assoc();

    if ($contrato) {
        $contrato_ids = [$contrato_id];

        // Buscar aditivos
        $stmt = $conn->prepare("SELECT contrato_aditivo_id FROM aditivos WHERE contrato_principal_id = ?");
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $contrato_ids[] = $row['contrato_aditivo_id'];
        }

        $placeholders = implode(',', array_fill(0, count($contrato_ids), '?'));
        $types = str_repeat('i', count($contrato_ids));

        $stmt = $conn->prepare("SELECT SUM(valor_anual) AS total FROM contratos WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$contrato_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $valor_total  = $row['total'] ?? 0;


        // Buscar obras
        $stmt = $conn->prepare("SELECT * FROM obras WHERE contrato_id IN ($placeholders) ORDER BY criado_em DESC");
        $stmt->bind_param($types, ...$contrato_ids);
        $stmt->execute();
        $obras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Buscar ordens de serviço
        $stmt = $conn->prepare("SELECT * FROM ordem_de_servico WHERE contrato_id IN ($placeholders) ORDER BY criado_em DESC");
        $stmt->bind_param($types, ...$contrato_ids);
        $stmt->execute();
        $ordens_servico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Buscar projetos
        $stmt = $conn->prepare("SELECT * FROM projetos WHERE contrato_id IN ($placeholders)");
        $stmt->bind_param($types, ...$contrato_ids);
        $stmt->execute();
        $projetos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Solicitações de compra recentes (ALTERAÇÃO FEITA AQUI)
        $stmt = $conn->prepare("
            SELECT sc.* 
            FROM solicitacao_compras sc
            JOIN ordem_de_servico os ON sc.os_id = os.id
            JOIN obras o ON os.obra_id = o.id
            WHERE o.contrato_id IN ($placeholders)
            ORDER BY sc.criado_em DESC
        ");
        $stmt->bind_param($types, ...$contrato_ids);
        $stmt->execute();
        $solicitacoes_compra_recentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Contagem de ordens de serviço por status
        $stmt = $conn->prepare("
            SELECT status, COUNT(*) as total 
            FROM ordem_de_servico 
            WHERE contrato_id IN ($placeholders)
            GROUP BY status
        ");
        $stmt->bind_param($types, ...$contrato_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $status_labels[] = $row['status'];
            $status_counts[] = $row['total'];
        }

        // Contagem de obras por status
        $stmt = $conn->prepare("
            SELECT s.nome as status, COUNT(*) as total
            FROM obras o
            JOIN status_obras s ON o.status_id = s.id
            WHERE o.contrato_id IN ($placeholders)
            GROUP BY s.nome
        ");
        $stmt->bind_param($types, ...$contrato_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $status_obras_labels[] = $row['status'];
            $status_obras_counts[] = $row['total'];
        }

        // Contagem de solicitações de compra por obra
        $stmt = $conn->prepare("
            SELECT o.nome AS obra_nome, COUNT(sc.id) AS total_solicitacoes
            FROM solicitacao_compras sc
            JOIN ordem_de_servico os ON sc.os_id = os.id
            JOIN obras o ON os.obra_id = o.id
            WHERE o.contrato_id IN ($placeholders)
            GROUP BY o.id
        ");
        $stmt->bind_param($types, ...$contrato_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $obras_labels[] = $row['obra_nome'];
            $obras_solicitacoes_counts[] = $row['total_solicitacoes'];
        }

        // Contagem de ordens de serviço por obra
        $stmt = $conn->prepare("
            SELECT o.nome AS obra_nome, COUNT(os.id) AS total_ordens_servico
            FROM ordem_de_servico os
            JOIN obras o ON os.obra_id = o.id
            WHERE o.contrato_id IN ($placeholders)
            GROUP BY o.id
        ");
        $stmt->bind_param($types, ...$contrato_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $obras_ordens_labels[] = $row['obra_nome'];
            $obras_ordens_counts[] = $row['total_ordens_servico'];
        }
    }
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


            <header class="bg-white rounded-2xl shadow-lg p-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Título e botão voltar -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <button onclick="window.location.href='../contratos'" class="text-gray-600 hover:text-primary transition self-start sm:self-auto">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">
                        Resumo do Contrato
                    </h1>
                </div>

                <!-- Botões de ações -->
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <?php if ($contrato['aditivo'] != '1') : ?>
                        <button onclick="openModal()"
                            class="bg-blue-800 text-white px-5 py-2.5 rounded-xl shadow hover:bg-blue-700 transition duration-200">
                            Cadastrar Aditivo
                        </button>
                    <?php endif; ?>
                    <button onclick="openEmpenhoModal()"
                        class="bg-blue-800 text-white px-5 py-2.5 rounded-xl shadow hover:bg-blue-700 transition duration-200">
                        Cadastrar Empenho
                    </button>


                </div>
            </header>

            <div class="mx-auto space-y-8">

                <!-- RESUMO DO CONTRATO -->
                <div class="bg-white shadow-md rounded-2xl p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Resumo deste Contrato</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-gray-700">
                        <div><span class="font-semibold">Número do Contrato:</span> <?php echo $contrato['numero_contrato']; ?></div>
                        <div><span class="font-semibold">Valor Mensal:</span> R$ <?php echo number_format($contrato['valor_mensal'], 2, ',', '.'); ?></div>
                        <div><span class="font-semibold">Valor Total:</span> R$ <?php echo number_format($contrato['valor_anual'], 2, ',', '.'); ?></div>
                        <div><span class="font-semibold">Cliente:</span> <?php echo $contrato['nome_cliente']; ?></div>
                        <div><span class="font-semibold">CNPJ:</span> <?php echo $contrato['cnpj_cliente']; ?></div>
                        <div><span class="font-semibold">Telefone:</span> <?php echo $contrato['telefone_cliente']; ?></div>
                        <div><span class="font-semibold">Data de Início:</span>
                            <?php
                            echo !empty($contrato['dt_inicio']) ? date('d/m/Y', strtotime($contrato['dt_inicio'])) : '';
                            ?>
                        </div>
                        <div><span class="font-semibold">Data Final:</span>
                            <?php
                            echo !empty($contrato['dt_fim']) ? date('d/m/Y', strtotime($contrato['dt_fim'])) : '';
                            ?>
                        </div>

                        <div><span class="font-semibold">Situação</span> <?php echo $contrato['situacao']; ?></div>




                    </div>

                    <hr class="mt-4">
                    <h2 class="text-xl font-medium text-gray-800 mt-4">Resumo com Aditivos</h2>
                    <div><span class="font-semibold">Valor Total</span> R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></div>

                </div>



                <?php
                // Buscar aditivos relacionados ao contrato principal
                $stmt = $conn->prepare("
                    SELECT c.* 
                    FROM aditivos a
                    JOIN contratos c ON a.contrato_aditivo_id = c.id
                    WHERE a.contrato_principal_id = ?
                    ORDER BY c.criado_em DESC
                ");
                $stmt->bind_param("i", $contrato_id);
                $stmt->execute();
                $aditivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>

                <?php if (!empty($aditivos)) : ?>
                    <div class="bg-white shadow-md rounded-2xl p-6">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Aditivos do Contrato</h2>
                        <div class="space-y-4">
                            <?php foreach ($aditivos as $aditivo) : ?>
                                <a href="./detalhes.php?contrato_id=<?php echo $aditivo['id']; ?>" class="block border rounded-xl p-4 hover:bg-gray-50 transition">
                                    <div class="font-semibold text-gray-800"><?php echo $aditivo['numero_contrato']; ?></div>
                                    <div class="text-sm text-gray-600">
                                        Vigência: <?php echo date('d/m/Y', strtotime($aditivo['dt_inicio'])); ?> até <?php echo date('d/m/Y', strtotime($aditivo['dt_fim'])); ?> —
                                        Valor Mensal: R$ <?php echo number_format($aditivo['valor_mensal'], 2, ',', '.'); ?>
                                        Valor Total: R$ <?php echo number_format($aditivo['valor_anual'], 2, ',', '.'); ?>

                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>


                <?php
                // Buscar empenhos vinculados ao contrato atual
                $stmt = $conn->prepare("
                        SELECT * 
                        FROM empenhos 
                        WHERE contrato_id = ?
                        ORDER BY data_empenho DESC
                    ");
                $stmt->bind_param("i", $contrato_id);
                $stmt->execute();
                $empenhos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>

                <?php if (!empty($empenhos)) : ?>
                    <div class="bg-white shadow-md rounded-2xl p-6 mt-6">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Empenhos</h2>
                        <div class="space-y-4">
                            <?php foreach ($empenhos as $emp) : ?>
                                <div class="border rounded-xl p-4 bg-gray-50 hover:bg-gray-100 transition">
                                    <div class="font-semibold text-gray-800">
                                        Nº: <?php echo htmlspecialchars($emp['numero_empenho']); ?>
                                    </div>
                                    <div class="font-semibold text-gray-800">
                                        Fonte: <?php echo htmlspecialchars($emp['fonte_empenho']); ?>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Data: <?php echo date('d/m/Y', strtotime($emp['data_empenho'])); ?><br>
                                        Valor Empenhado: R$ <?php echo number_format($emp['valor_empenhado'], 2, ',', '.'); ?><br>
                                        <?php if (!empty($emp['descricao'])) : ?>
                                            Descrição: <?php echo nl2br(htmlspecialchars($emp['descricao'])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>


                <!-- Formulário -->
                <form method="POST" enctype="multipart/form-data" class="space-y-6 mt-6 bg-white px-8 py-10 rounded shadow">

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

                        <div class="flex flex-col ">
                            <label for="valor_anual_formatado" class="text-gray-700 mb-1 text-sm font-medium">Valor Total</label>
                            <input type="text" id="valor_anual_formatado"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100"
                                value="<?= number_format($contrato['valor_anual'] ?? 0, 2, ',', '.') ?>" placeholder="0,00" />
                            <input type="hidden" name="valor_anual" id="valor_anual_real"
                                value="<?= htmlspecialchars($contrato['valor_anual'] ?? '') ?>" />
                        </div>


                        <div class="flex flex-col">
                            <label for="situacao" class="text-gray-700 mb-1 text-sm font-medium">Situação</label>
                            <select id="situacao" name="situacao"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100">
                                <?php
                                $status_options = ['Ativo', 'Inativo', 'Suspenso', 'Cancelado', 'Concluído', 'Pendências'];
                                $current_status = $contrato['situacao'] ?? '';
                                foreach ($status_options as $status) {
                                    $selected = ($status === $current_status) ? 'selected' : '';
                                    echo "<option value=\"$status\" $selected>$status</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="flex flex-col">
                            <label for="seguro_contrato" class="text-gray-700 mb-1 text-sm font-medium">Seguro do Contrato</label>
                            <input type="text" " id=" seguro_contrato" name=" seguro_contrato"
                                value=" <?= htmlspecialchars($contrato['seguro_contrato'] ?? '') ?>"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                        </div>

                        <div class="flex flex-col">
                            <label for="art" class="text-gray-700 mb-1 text-sm font-medium">Anotação de Responsabilidade Técnica (art)</label>
                            <input type="text" " id=" " name=" art"
                                value="<?= htmlspecialchars($contrato['ART'] ?? '') ?>"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                        </div>
                        <div class="flex flex-col">
                            <label for="dt_inicio" class="text-gray-700 mb-1 text-sm font-medium">Data de Início</label>
                            <input type="date" id="dt_inicio" name="dt_inicio"
                                value="<?= htmlspecialchars($contrato['dt_inicio'] ?? '') ?>"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                        </div>

                        <div class="flex flex-col">
                            <label for="dt_fim" class="text-gray-700 mb-1 text-sm font-medium">Data de Fim</label>
                            <input type="date" id="dt_fim" name="dt_fim"
                                value="<?= htmlspecialchars($contrato['dt_fim'] ?? '') ?>"
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
                <!-- OBRAS EM ANDAMENTO -->
                <div class="bg-white shadow-md rounded-2xl p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Obras em Andamento</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($obras as $obra):
                            $cep = preg_replace('/[^0-9]/', '', $obra['cep']);
                            $enderecoId = "endereco_" . $obra['id']; // ID único baseado no ID da obra
                        ?>
                            <div onclick="window.location.href = '../obras/detalhes.php?obra_id=<?php echo $obra['id']; ?>'" class="bg-blue-50 p-4 rounded-xl shadow-sm border border-blue-100">
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



            </div>




            <div class="">
                <h1 class="text-2xl font-bold mb-6 mt-6">Dashboard do Contrato</h1>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">

                    <!-- Gráfico de Ordens de Serviço -->
                    <div class="bg-white shadow-lg rounded-lg p-6 flex flex-col items-center max-h-[400px]">
                        <h2 class="text-lg font-semibold mb-4 text-center">Ordens de Serviço por Status</h2>
                        <canvas id="graficoPizzaOS" class="w-full max-w-[300px] h-auto"></canvas>
                    </div>

                    <!-- Gráfico de Obras por Status -->
                    <div class="bg-white shadow-lg rounded-lg p-6 flex flex-col items-center max-h-[400px]">
                        <h2 class="text-lg font-semibold mb-4 text-center">Obras por cada Status</h2>
                        <canvas id="graficoObrasStatus" class="w-full max-w-[500px] h-[300px]"></canvas>
                    </div>

                    <!-- Gráfico de Solicitação de Insumos -->
                    <div class="bg-white shadow-lg rounded-lg p-6 flex flex-col items-center max-h-[400px]">
                        <h2 class="text-lg font-semibold mb-4 text-center">Solicitação de Compras por Obra</h2>
                        <canvas id="graficoSolicitacoesObras" class="w-full max-w-[300px] h-[300px]"></canvas>
                    </div>

                    <!-- Gráfico de Ordens de Serviço por Obra -->
                    <div class="bg-white shadow-lg rounded-lg p-6 flex flex-col items-center max-h-[400px]">
                        <h2 class="text-lg font-semibold mb-4 text-center">Quantidade de Ordens de Serviço por cada Obra</h2>
                        <canvas id="graficoOrdensPorObra" class="w-full max-w-[500px] h-[300px]"></canvas>
                    </div>

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


            <!-- Inclui a lib Chart.js via CDN -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        </div>
    </div>

    <!-- Inclui a lib Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <div id="aditivoModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Cadastrar Aditivo</h2>
            <p class="m-0 p-0 text-gray-600 text-sm">Ao Criar Aditivos, os dados do contrato principal serão replicados.</p>
            <form action="./criar_aditivo.php" method="POST" class="space-y-4">
                <input type="hidden" name="contrato_principal_id" value="<?php echo htmlspecialchars($contrato['id']); ?>">

                <div>
                    <label class="block text-gray-700 mb-1">Valor Total</label>
                    <input type="text" id="valor_formatado" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2"
                        placeholder="0,00">
                    <input type="hidden" name="valor_anual" id="valor_real">
                </div>

                <script>
                    document.getElementById('valor_formatado').addEventListener('input', function(e) {
                        let input = e.target;
                        let value = input.value;

                        // Remove tudo que não for número
                        value = value.replace(/\D/g, '');

                        // Se estiver vazio, não formata
                        if (value.length === 0) {
                            document.getElementById('valor_real').value = '';
                            input.value = '';
                            return;
                        }

                        // Converte para número com centavos
                        let valorCentavos = (parseFloat(value) / 100).toFixed(2);

                        // Atualiza campo oculto com ponto (para o backend)
                        document.getElementById('valor_real').value = valorCentavos;

                        // Formata para visualização com vírgula e ponto (para o usuário)
                        input.value = formatarValor(valorCentavos);
                    });

                    function formatarValor(valor) {
                        return valor
                            .replace('.', ',') // troca ponto por vírgula
                            .replace(/\B(?=(\d{3})+(?!\d))/g, '.'); // adiciona pontos de milhar
                    }
                </script>

                <div>
                    <label class="block text-gray-700 mb-1">Data de Início</label>
                    <input type="date" name="dt_inicio" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="block text-gray-700 mb-1">Data de Fim</label>
                    <input type="date" name="dt_fim" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 rounded-lg bg-blue-800 text-white hover:bg-blue-700">
                        Salvar Aditivo
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal de Empenho -->
    <div id="empenhoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-lg p-6 relative">
            <h2 class="text-xl font-semibold mb-4">Novo Empenho</h2>
            <form action="criar_empenho.php" method="POST" class="space-y-4">
                <!-- Número do Empenho -->
                <div>
                    <label for="numero_empenho" class="block font-medium text-gray-700">Número do Empenho</label>
                    <input type="text" name="numero_empenho" id="numero_empenho" required
                        class="w-full border border-gray-300 rounded-md px-4 py-2 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>

                <!-- Contrato ID (oculto ou dropdown, conforme o contexto) -->
                <input type="hidden" name="contrato_id" value="<?= $contrato_id ?? '' ?>">

                <!-- Fonte do Empenho -->
                <div>
                    <label for="fonte_empenho" class="block font-medium text-gray-700">Fonte do Empenho</label>
                    <input type="text" name="fonte_empenho" id="fonte_empenho"
                        class="w-full border border-gray-300 rounded-md px-4 py-2 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>


                <!-- Data do Empenho -->
                <div>
                    <label for="data_empenho" class="block font-medium text-gray-700">Data do Empenho</label>
                    <input type="date" name="data_empenho" id="data_empenho" required
                        class="w-full border border-gray-300 rounded-md px-4 py-2 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>

                <!-- Valor Empenhado -->
                <div>
                    <label for="valor_empenhado" class="block font-medium text-gray-700">Valor Empenhado</label>
                    <input type="number" step="0.01" name="valor_empenhado" id="valor_empenhado" required
                        class="w-full border border-gray-300 rounded-md px-4 py-2 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>

                <!-- Descrição -->
                <div>
                    <label for="descricao" class="block font-medium text-gray-700">Descrição</label>
                    <textarea name="descricao" id="descricao" rows="3"
                        class="w-full border border-gray-300 rounded-md px-4 py-2 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-600"></textarea>
                </div>

                <!-- Ações -->
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeEmpenhoModal()"
                        class="px-4 py-2 rounded-md border border-gray-400 text-gray-700 hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 rounded-md bg-blue-700 text-white hover:bg-blue-800">
                        Salvar Empenho
                    </button>
                </div>
            </form>

            <!-- Botão fechar (X) -->
            <button onclick="closeEmpenhoModal()" class="absolute top-3 right-4 text-gray-500 hover:text-gray-800 text-xl">&times;</button>
        </div>
    </div>



    <script>
        function aplicarMascaraFinanceira(campoVisivelId, campoRealId) {
            const campoVisivel = document.getElementById(campoVisivelId);
            const campoReal = document.getElementById(campoRealId);

            campoVisivel.addEventListener('input', function() {
                let valor = campoVisivel.value.replace(/\D/g, '');

                if (!valor) {
                    campoVisivel.value = '';
                    campoReal.value = '';
                    return;
                }

                valor = (parseFloat(valor) / 100).toFixed(2);

                campoReal.value = valor; // Enviado ao backend (com ponto)

                campoVisivel.value = formatarValorParaUsuario(valor); // Visível ao usuário
            });

            function formatarValorParaUsuario(valor) {
                return valor
                    .replace('.', ',')
                    .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }
        }

        aplicarMascaraFinanceira('valor_anual_formatado', 'valor_anual_real');
    </script>

    <script>
        function openEmpenhoModal() {
            document.getElementById('empenhoModal').classList.remove('hidden');
        }

        function closeEmpenhoModal() {
            document.getElementById('empenhoModal').classList.add('hidden');
        }
    </script>

    <script>
        function openModal() {
            document.getElementById('aditivoModal').classList.remove('hidden');
            document.getElementById('aditivoModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('aditivoModal').classList.add('hidden');
            document.getElementById('aditivoModal').classList.remove('flex');
        }
    </script>


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
                responsive: true,
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
                responsive: true,
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
                responsive: true,
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