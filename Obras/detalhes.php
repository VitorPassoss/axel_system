<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];
$obra = null;

if (isset($_GET['obra_id'])) {
    $obra_id = intval($_GET['obra_id']);
    $stmt = $conn->prepare("SELECT * FROM obras WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $obra_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $obra = $result->fetch_assoc();
}



$ordens_servico = [];
if ($obra) {
    $stmt = $conn->prepare("SELECT * FROM ordem_de_servico WHERE obra_id = ? ORDER BY criado_em DESC");
    $stmt->bind_param("i", $obra['id']);
    $stmt->execute();
    $ordens_servico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

            <!-- Cabeçalho -->
            <h2 class="text-3xl font-bold text-gray-800 dark:text-black mb-6 text-center">
                Detalhes da Obra
            </h2>

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
            <form method="POST" class="space-y-4">


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
                <div class="bg-white shadow-md rounded-2xl p-6 mt-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Ordens de Serviço da Obra <?php echo htmlspecialchars($obra['nome']); ?></h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-700">
                            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                                <tr>
                                    <th class="p-3">ID.OS</th>

                                    <th class="p-3">Descrição Os</th>

                                    <th class="p-3">Status</th>
                                    <th class="p-3">Data Início</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordens_servico as $ordem): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="p-3"><?php echo htmlspecialchars($ordem['id']); ?></td>

                                        <td class="p-3"><?php echo htmlspecialchars($ordem['descricao']); ?></td>
                                        <td class="p-3">
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                                                <?php echo htmlspecialchars($ordem['status']); ?>
                                            </span>
                                        </td>
                                        <td class="p-3"><?php echo date('d/m/Y', strtotime($ordem['data_inicio'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="p-3 bg-gray-50">
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
                                            <strong>Serviços relacionados:</strong>
                                            <?php if (count($servicos_os) > 0): ?>
                                                <table class="min-w-full text-sm text-left text-gray-600 mt-2 border border-gray-300 rounded">
                                                    <thead class="bg-gray-200">
                                                        <tr>
                                                            <th class="p-2 border-b">Serviço</th>
                                                            <th class="p-2 border-b">Quantidade</th>
                                                            <th class="p-2 border-b">Unidade</th>
                                                            <th class="p-2 border-b">Executor</th>
                                                            <th class="p-2 border-b">Data Início</th>
                                                            <th class="p-2 border-b">Data Final</th>
                                                            <th class="p-2 border-b">Tipo</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($servicos_os as $servico): ?>
                                                            <tr class="border-b hover:bg-gray-100">
                                                                <td class="p-2 border-r"><?php echo htmlspecialchars($servico['nome_servico']); ?></td>
                                                                <td class="p-2 border-r"><?php echo htmlspecialchars($servico['quantidade']); ?></td>
                                                                <td class="p-2 border-r"><?php echo htmlspecialchars($servico['und_do_servico']); ?></td>
                                                                <td class="p-2 border-r"><?php echo htmlspecialchars($servico['executor']); ?></td>
                                                                <td class="p-2 border-r"><?php echo date('d/m/Y', strtotime($servico['dt_inicio'])); ?></td>
                                                                <td class="p-2 border-r"><?php echo date('d/m/Y', strtotime($servico['dt_final'])); ?></td>
                                                                <td class="p-2 border-r"><?php echo htmlspecialchars($servico['tipo_servico']); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <p class="text-sm text-gray-500 mt-2">Nenhum serviço relacionado.</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-gray-600 mt-4">Nenhuma ordem de serviço encontrada para esta obra.</p>
            <?php endif; ?>

        </div>
    </div>


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