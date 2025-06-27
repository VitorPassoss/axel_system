<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];
$obra = null;
$obra_completa = [];

if (isset($_GET['obra_id'])) {
    $obra_id = intval($_GET['obra_id']);

    // Buscar obra
    $stmt = $conn->prepare("SELECT * FROM obras WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $obra_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $obra = $result->fetch_assoc();

    if ($obra) {
        // Buscar ordens de serviço da obra
        $stmt = $conn->prepare("SELECT * FROM ordem_de_servico WHERE obra_id = ? ORDER BY criado_em DESC");
        $stmt->bind_param("i", $obra['id']);
        $stmt->execute();
        $ordens_servico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($ordens_servico as &$os) {
            // Buscar serviços da O.S.
            $stmt = $conn->prepare("SELECT so.*, s.nome AS nome_servico 
                                    FROM servicos_os so 
                                    JOIN servicos s ON s.id = so.servico_id 
                                    WHERE so.os_id = ?");
            $stmt->bind_param("i", $os['id']);
            $stmt->execute();
            $servicos_os = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Para cada serviço, buscar os registros da OS correspondente
            foreach ($servicos_os as &$servico_os) {
                $stmt = $conn->prepare("SELECT * FROM registro_os WHERE servico_id = ? AND os_id = ?");
                $stmt->bind_param("ii", $servico_os['id'], $os['id']); // Aqui: id do servico_os, não servico_id
                $stmt->execute();
                $registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $servico_os['registros'] = $registros;
            }


            // Adiciona os serviços com registros à O.S.
            $os['servicos'] = $servicos_os;
        }

        // Monta estrutura final
        $obra_completa = [
            'obra' => $obra,
            'ordens_servico' => $ordens_servico
        ];
    }
}
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
    <div class="w-full">
        <div class="relative p-8 animate-fadeIn">

            <!-- Botão Fechar -->
            <button onclick="window.location.href = './index.php'" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-3xl">
                &times;
            </button>

            <header class="bg-white rounded-2xl shadow-lg p-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Título e botão voltar -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <button onclick="window.location.href='../Obras'" class="text-gray-600 hover:text-primary transition self-start sm:self-auto">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">
                        Resumo da Obra
                    </h1>
                </div>

                <!-- Botões de ações -->
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">

                    <button onclick="gerarRelatorio()" style="padding: 10px 20px; background-color: black; color: white; border: none; border-radius: 5px;">
                        Gerar Relatório Fotográfico
                    </button>

                </div>
            </header>


            <div class="bg-white shadow-md rounded-2xl p-6 mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Resumo da Obra</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-gray-700">

                    <div><span class="font-semibold">Nome da Obra:</span> <?= htmlspecialchars($obra['nome'] ?? '') ?></div>

                    <div><span class="font-semibold">Data de Início:</span>
                        <?= !empty($obra['data_inicio']) ? date('d/m/Y', strtotime($obra['data_inicio'])) : '' ?>
                    </div>

                    <div><span class="font-semibold">Previsão de Término:</span>
                        <?= !empty($obra['data_previsao_fim']) ? date('d/m/Y', strtotime($obra['data_previsao_fim'])) : '' ?>
                    </div>

                    <div><span class="font-semibold">Responsável Técnico:</span> <?= htmlspecialchars($obra['responsavel_tecnico'] ?? '') ?></div>

                    <div><span class="font-semibold">Cliente:</span> <?= htmlspecialchars($obra['cliente'] ?? '') ?></div>

                    <div><span class="font-semibold">Tipo de Obra:</span> <?= htmlspecialchars($obra['tipo_obra'] ?? '') ?></div>

                    <div><span class="font-semibold">Cidade:</span> <?= htmlspecialchars($obra['cidade'] ?? '') ?></div>

                    <div><span class="font-semibold">Estado:</span> <?= htmlspecialchars($obra['estado'] ?? '') ?></div>

                    <div><span class="font-semibold">CEP:</span> <?= htmlspecialchars($obra['cep'] ?? '') ?></div>

                    <div><span class="font-semibold">Status:</span>
                        <?php
                        if (!empty($obra['status_id'])) {
                            $status_sql = "SELECT nome FROM status_obras WHERE id = " . intval($obra['status_id']);
                            $status_result = $conn->query($status_sql);
                            if ($status_result && $status_row = $status_result->fetch_assoc()) {
                                echo htmlspecialchars($status_row['nome']);
                            }
                        }
                        ?>
                    </div>

                    <div><span class="font-semibold">Contrato (Número):</span>
                        <?php
                        if (!empty($obra['contrato_id'])) {
                            $contrato_sql = "SELECT numero_contrato FROM contratos WHERE id = " . intval($obra['contrato_id']);
                            $contrato_result = $conn->query($contrato_sql);
                            if ($contrato_result && $contrato_row = $contrato_result->fetch_assoc()) {
                                echo htmlspecialchars($contrato_row['numero_contrato']);
                            }
                        }
                        ?>
                    </div>

                    <div><span class="font-semibold">Projeto:</span>
                        <?php
                        if (!empty($obra['projeto_id'])) {
                            $projeto_sql = "SELECT nome FROM projetos WHERE id = " . intval($obra['projeto_id']);
                            $projeto_result = $conn->query($projeto_sql);
                            if ($projeto_result && $projeto_row = $projeto_result->fetch_assoc()) {
                                echo htmlspecialchars($projeto_row['nome']);
                            }
                        }
                        ?>
                    </div>

                </div>
            </div>



            <!-- Formulário -->
            <form method="POST" class="space-y-6 mt-6 bg-white px-8 py-10 rounded shadow">


                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Nome da Obra -->
                    <div class="flex flex-col">
                        <label for="nome" class="text-gray-700 mb-1 text-sm font-medium">Nome da Obra</label>
                        <input type="text" id="nome" name="nome" required value="<?= isset($obra['nome']) ? htmlspecialchars($obra['nome']) : '' ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <!-- Data de Início -->
                    <div class="flex flex-col">
                        <label for="data_inicio" class="text-gray-700 mb-1 text-sm font-medium">Data de Início</label>
                        <input type="date" id="data_inicio" name="data_inicio" required value="<?= isset($obra['data_inicio']) ? $obra['data_inicio'] : '' ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <!-- Previsão de Término -->
                    <div class="flex flex-col">
                        <label for="data_previsao_fim" class="text-gray-700 mb-1 text-sm font-medium">Previsão de Término</label>
                        <input type="date" id="data_previsao_fim" name="data_previsao_fim" required value="<?= isset($obra['data_previsao_fim']) ? $obra['data_previsao_fim'] : '' ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <!-- Responsável Técnico -->
                    <div class="flex flex-col">
                        <label for="responsavel_tecnico" class="text-gray-700 mb-1 text-sm font-medium">Responsável Técnico</label>
                        <input type="text" id="responsavel_tecnico" name="responsavel_tecnico" required value="<?= isset($obra['responsavel_tecnico']) ? htmlspecialchars($obra['responsavel_tecnico']) : '' ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <!-- Cliente -->
                    <div class="flex flex-col">
                        <label for="cliente" class="text-gray-700 mb-1 text-sm font-medium">Cliente</label>
                        <input type="text" id="cliente" name="cliente" required value="<?= isset($obra['cliente']) ? htmlspecialchars($obra['cliente']) : '' ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <!-- Tipo de Obra -->
                    <div class="flex flex-col">
                        <label for="tipo_obra" class="text-gray-700 mb-1 text-sm font-medium">Tipo de Obra</label>
                        <input type="text" id="tipo_obra" name="tipo_obra" required value="<?= isset($obra['tipo_obra']) ? htmlspecialchars($obra['tipo_obra']) : '' ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="cidade" class="text-gray-700 mb-1 text-sm font-medium">Cidade</label>
                        <input type="text" id="cidade" name="cidade" required value="<?= isset($obra['cidade']) ? htmlspecialchars($obra['cidade']) : '' ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="estado" class="text-gray-700 mb-1 text-sm font-medium">Estado</label>
                        <input type="text" id="estado" name="estado" required value="<?= isset($obra['estado']) ? htmlspecialchars($obra['estado']) : '' ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="cep" class="text-gray-700 mb-1 text-sm font-medium">CEP</label>
                        <input type="text" id="cep" name="cep" required value="<?= isset($obra['cep']) ? htmlspecialchars($obra['cep']) : '' ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col">
                        <label for="status_id" class="text-gray-700 mb-1 text-sm font-medium">Status da Obra</label>
                        <select id="status_id" name="status_id" required
                            class="w-full rounded-lg border border-gray-300 p-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary">
                            <?php
                            $status_sql = "SELECT * FROM status_obras";
                            $status_result = $conn->query($status_sql);
                            while ($status_row = $status_result->fetch_assoc()) {
                                $selected = (isset($obra['status_id']) && $obra['status_id'] == $status_row['id']) ? 'selected' : '';
                                echo '<option value="' . $status_row['id'] . '" ' . $selected . '>' . $status_row['nome'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>


                    <!-- Contrato -->
                    <div class="flex flex-col">
                        <label for="contrato_id" class="text-gray-700 mb-1 text-sm font-medium">Contrato (Número)</label>
                        <select id="contrato_id" name="contrato_id" required
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Nenhum Contrato</option>
                            <?php
                            $contratos_sql = "SELECT id, numero_contrato FROM contratos";
                            $contratos_result = $conn->query($contratos_sql);
                            while ($contrato_row = $contratos_result->fetch_assoc()) {
                                echo '<option value="' . $contrato_row['id'] . '" ' . (isset($obra['contrato_id']) && $obra['contrato_id'] == $contrato_row['id'] ? 'selected' : '') . '>' . $contrato_row['numero_contrato'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label for="projeto_id" class="text-gray-700 mb-1 text-sm font-medium">Projeto</label>
                        <select id="projeto_id" name="projeto_id"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Nenhum projeto</option>
                            <?php
                            $projetos_sql = "SELECT id, nome FROM projetos";
                            $projetos_result = $conn->query($projetos_sql);
                            while ($projeto_row = $projetos_result->fetch_assoc()) {
                                echo '<option value="' . $projeto_row['id'] . '" ' . (isset($obra['projeto_id']) && $obra['projeto_id'] == $projeto_row['id'] ? 'selected' : '') . '>' . $projeto_row['nome'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                </div>

                <!-- Descrição -->
                <div class="flex flex-col">
                    <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="4" placeholder="Escreva uma descrição..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"><?= isset($obra['descricao']) ? htmlspecialchars($obra['descricao']) : '' ?></textarea>
                </div>

                <!-- Botão de Enviar -->
                <div class="flex justify-end">
                    <button type="submit" name="criar"
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-6 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        Salvar Alterações
                    </button>
                </div>
            </form>


            <?php if (count($ordens_servico) > 0): ?>
                <div class="mt-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">
                        Ordens de Serviço da Obra: <?php echo htmlspecialchars($obra['nome']); ?>
                    </h2>

                    <?php foreach ($ordens_servico as $ordem): ?>
                        <div class="bg-white shadow-lg rounded-xl mb-6 p-6 border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-xl font-semibold text-blue-800">
                                        Ordem de Serviço #<?php echo htmlspecialchars($ordem['id']); ?> - <?php echo htmlspecialchars($ordem['descricao']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        Início: <?php echo date('d/m/Y', strtotime($ordem['data_inicio'])); ?>
                                    </p>
                                </div>
                                <span class="px-3 py-1 text-sm rounded-full 
                        <?php
                        echo match ($ordem['status']) {
                            'Aberta' => 'bg-yellow-100 text-yellow-800',
                            'Concluída' => 'bg-green-100 text-green-800',
                            'Cancelada' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-700'
                        };
                        ?>">
                                    <?php echo htmlspecialchars($ordem['status']); ?>
                                </span>
                            </div>

                            <p class="mb-4 text-gray-700"><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($ordem['descricao'])); ?></p>

                            <?php
                            $stmt_servicos = $conn->prepare("
                    SELECT so.*, s.nome AS nome_servico
                    FROM servicos_os so
                    JOIN servicos s ON so.servico_id = s.id
                    WHERE so.os_id = ?
                    ORDER BY so.dt_inicio DESC
                ");
                            $stmt_servicos->bind_param("i", $ordem['id']);
                            $stmt_servicos->execute();
                            $servicos_os = $stmt_servicos->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>

                            <?php if (count($servicos_os) > 0): ?>
                                <h4 class="text-md font-semibold text-gray-800 mb-2">Serviços Vinculados:</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm text-left text-gray-700 border rounded">
                                        <thead class="bg-gray-100 text-gray-600  text-xs">
                                            <tr>
                                                <th class="p-2 border">Serviço</th>
                                                <th class="p-2 border">Quantidade</th>
                                                <th class="p-2 border">Unidade</th>
                                                <th class="p-2 border">Executor</th>
                                                <th class="p-2 border">Data Início</th>
                                                <th class="p-2 border">Data Final</th>
                                                <th class="p-2 border">Tipo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($servicos_os as $servico): ?>
                                                <tr class="bg-white border-b hover:bg-gray-50">
                                                    <td class="p-2 border"><?php echo htmlspecialchars($servico['nome_servico']); ?></td>
                                                    <td class="p-2 border"><?php echo htmlspecialchars($servico['quantidade']); ?></td>
                                                    <td class="p-2 border"><?php echo htmlspecialchars($servico['und_do_servico']); ?></td>
                                                    <td class="p-2 border"><?php echo htmlspecialchars($servico['executor']); ?></td>
                                                    <td class="p-2 border"><?php echo date('d/m/Y', strtotime($servico['dt_inicio'])); ?></td>
                                                    <td class="p-2 border"><?php echo date('d/m/Y', strtotime($servico['dt_final'])); ?></td>
                                                    <td class="p-2 border"><?php echo htmlspecialchars($servico['tipo_servico']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-gray-500 mt-2">Nenhum serviço relacionado a esta O.S.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600 mt-4">Nenhuma ordem de serviço encontrada para esta obra.</p>
            <?php endif; ?>

        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            #relatorio,
            #relatorio * {
                visibility: visible;
            }

            #relatorio {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }

        * {
            margin: 0px;
            padding: 0px;
        }

        #relatorio {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background-color: #fff;
            margin: 0;
        }

        #relatorio .topo-logo {
            text-align: center;
            border: 1px solid #ccc;
            ;
            z-index: 99999;
            padding: 6px;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }

        #relatorio .topo-logo img {
            max-height: 60px;
        }

        #relatorio h2 {
            font-size: 16px;
            color: #222;

        }

        .os-card {
            display: table;
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc;
            margin: 30px 0px;

        }

        .os-card h3 {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            padding: 6px 8px;

        }

        .os-card p {
            font-size: 14px;
            color: #555;
            padding: 6px 8px;
        }

        .servico {
            display: table;
            width: 100%;
            border: 1px solid #ccc;

        }

        .servico h4 {
            font-size: 14px;
            font-weight: bold;
            color: #34495e;
            padding: 6px 8px;

        }

        .registros-fotos {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 0;
        }

        .foto-card {
            display: table-cell;
            vertical-align: top;
            margin: 0;
        }

        .foto-card h5 {
            font-size: 14px;
            text-align: center;
            color: #2c3e50;
            border: 1px solid #ccc;

        }

        .foto-card img {
            display: block;
            width: 100%;
            height: 600px;
            object-fit: cover;
            border: none;
        }

        .sem-imagem {
            font-style: italic;
            color: #888;
            text-align: center;
            padding: 40px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
            color: #444;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #f4f6f8;
            font-weight: bold;
            color: #333;
        }
    </style>


    <?php
    // Filtra OS duplicadas por ID
    $os_ids_processadas = [];
    $ordens_servico_unicas = [];

    foreach ($obra_completa['ordens_servico'] as $os) {
        if (!in_array($os['id'], $os_ids_processadas)) {
            $os_ids_processadas[] = $os['id'];
            $ordens_servico_unicas[] = $os;
        }
    }
    ?>

    <style>
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }


        .grid2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }

        .grid3 {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
        }


        .item {
            display: flex;
            font-size: 14px;
            background-color: #f9f9f9;
            overflow: hidden;
        }

        .prefixo {
            padding: 6px 8px;
            font-weight: bold;
            white-space: nowrap;
            flex-shrink: 0;
            border: 1px solid #ccc;

            background-color: rgb(214, 214, 214);
        }

        .conteudo {
            padding: 6px 8px;
            flex-grow: 1;
            min-width: 0;
            border: 1px solid #ccc;

        }

        .title {
            position: relative;
            left: -50px;
            font-weight: 700;
        }
    </style>

    <div id="relatorio" style="display: none;">
        <div class="topo-logo">
            <img style="height: 40px !important;" src="../assets/logo/Imagem1.png" alt="Logo">
            
            <h1 class="title">Relatório Fotográfico</h1>

            <img style="width: 60px; height: 60px !important;" src="../assets/logo/il_fullxfull.2974258879_pxm3.webp" alt="Logo">

        </div>

        <div class="grid1">
            <div class="item">
                <div class="prefixo">Obra:</div>
                <div class="conteudo"><?= htmlspecialchars($obra_completa['obra']['nome']) ?></div>
            </div>

        </div>
        <div class="grid">

            <div class="item">
                <div class="prefixo">Responsável:</div>
                <div class="conteudo"><?= htmlspecialchars($obra_completa['obra']['responsavel_tecnico'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="prefixo">Início:</div>
                <div class="conteudo">
                    <?= isset($obra_completa['obra']['data_inicio']) ? (new DateTime(datetime: $obra_completa['obra']['data_inicio']))->format('d/m/Y') : '-' ?>
                </div>
            </div>



            <div class="item">
                <div class="prefixo">Cep:</div>
                <div class="conteudo"><?= htmlspecialchars($obra_completa['obra']['cep']) ?></div>
            </div>

        </div>
        <div class="grid2">
            <div class="item">
                <div class="prefixo">Cidade/Estado</div>
                <div class="conteudo"><?= htmlspecialchars($obra_completa['obra']['cidade']) ?>/ <?= htmlspecialchars($obra_completa['obra']['estado']) ?></div>
            </div>
            <div class="item">
                <div class="prefixo">Contrato Nº:</div>
                <div class="conteudo"> <?php
                                        if (!empty($obra_completa['obra']['contrato_id'])) {
                                            $contrato_sql = "SELECT numero_contrato FROM contratos WHERE id = " . intval($obra['contrato_id']);
                                            $contrato_result = $conn->query($contrato_sql);
                                            if ($contrato_result && $contrato_row = $contrato_result->fetch_assoc()) {
                                                echo htmlspecialchars($contrato_row['numero_contrato']);
                                            }
                                        }
                                        ?></div>
            </div>



            <div class="item">

            </div>


        </div>
        <div class="os">
            <?php foreach ($ordens_servico_unicas as $os): ?>
                <div class="os-card">
                    <h3>O.S. #<?= htmlspecialchars($os['id']) ?> - <?= htmlspecialchars($os['descricao']) ?></h3>

                    <div class="grid2">
                        <div class=" prefixo">Local:</div>
                        <div class=" conteudo"><?= htmlspecialchars($os['local']) ?></div>
                        <div class=" prefixo">Data Inicio:</div>
                        <div class="conteudo">
                            <?= isset($os['data_inicio']) ? (new DateTime($os['data_inicio']))->format('d/m/Y') : '-' ?>
                        </div>

                    </div>

                    <?php if (!empty($os['servicos'])): ?>
                        <?php foreach ($os['servicos'] as $servico): ?>
                            <div class="servico">
                                <h4>Serviço: <?= htmlspecialchars($servico['nome_servico']) ?> (<?= htmlspecialchars($servico['quantidade']) ?> <?= htmlspecialchars($servico['und_do_servico']) ?>)</h4>

                                <?php if (!empty($servico['registros'])): ?>
                                    <div class="registros-fotos">
                                        <?php
                                        // Separar registros "antes" e "depois"
                                        $antes = array_filter($servico['registros'], fn($r) => strtolower($r['momento']) === 'antes');
                                        $depois = array_filter($servico['registros'], fn($r) => strtolower($r['momento']) === 'depois');
                                        ?>

                                        <div class="foto-card">
                                            <h5>Antes</h5>
                                            <?php if (!empty($antes)): ?>
                                                <?php
                                                $imagens_exibidas_antes = [];
                                                ?>
                                                <?php foreach ($antes as $registro): ?>
                                                    <?php if (!empty($registro['imagem'])): ?>
                                                        <?php if (!in_array($registro['imagem'], $imagens_exibidas_antes)): ?>
                                                            <img src="../os/relatorio_fotografico/uploads/<?= rawurlencode($registro['imagem']) ?>" alt="Foto Antes">
                                                            <?php $imagens_exibidas_antes[] = $registro['imagem']; ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="sem-imagem">Sem imagem</div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="sem-imagem">Nenhuma foto antes</div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="foto-card">
                                            <h5>Depois</h5>
                                            <?php if (!empty($depois)): ?>
                                                <?php
                                                $imagens_exibidas_depois = [];
                                                ?>
                                                <?php foreach ($depois as $registro): ?>
                                                    <?php if (!empty($registro['imagem'])): ?>
                                                        <?php if (!in_array($registro['imagem'], $imagens_exibidas_depois)): ?>
                                                            <img src="../os/relatorio_fotografico/uploads/<?= rawurlencode($registro['imagem']) ?>" alt="Foto Depois">
                                                            <?php $imagens_exibidas_depois[] = $registro['imagem']; ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="sem-imagem">Sem imagem</div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="sem-imagem">Nenhuma foto depois</div>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                <?php else: ?>
                                    <p><em>Sem registros fotográficos para este serviço.</em></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><em>Esta O.S. não possui serviços cadastrados.</em></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>



    <script>
        function gerarRelatorio() {
            const relatorioDiv = document.getElementById('relatorio');
            relatorioDiv.style.display = 'block'; // mostra para impressão
            window.print();
            relatorioDiv.style.display = 'none'; // esconde após impressão
        }
    </script>
    <script>
        document.querySelector("form").addEventListener("submit", async function(e) {
            e.preventDefault();


            const urlParams = new URLSearchParams(window.location.search);
            const obraId = urlParams.get("obra_id");


            const form = e.target;
            const formData = new FormData(form);

            const url = './update.php?obra_id=' + obraId;


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

</html>