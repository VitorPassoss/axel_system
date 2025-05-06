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

if (isset($_GET['sc_id'])) {
    $sc_id = intval($_GET['sc_id']);
    $stmt = $conn->prepare("SELECT * FROM ordem_de_servico WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $sc_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $os = $result->fetch_assoc();
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
$stmtServicos = $conn->prepare("SELECT DISTINCT nome FROM servicos ORDER BY nome ASC");
$stmtServicos->execute();
$resultServicos = $stmtServicos->get_result();
while ($row = $resultServicos->fetch_assoc()) {
    $servicos[] = $row['nome'];
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

            <!-- Botão Fechar -->
            <button onclick="window.location.href = './index.php'" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-3xl">
                &times;
            </button>

            <!-- Cabeçalho -->
            <!-- Cabeçalho -->
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-6 text-center">
                Registrar O.S
            </h2>

            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">


                    <div class="flex flex-col">
                        <label for="responsavel" class="text-gray-700 mb-1 text-sm font-medium">Responsavel</label>
                        <input type="text" id="responsavel" name="responsavel_os" required
                            value="<?= htmlspecialchars($os['responsavel'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="numero_os" class="text-gray-700 mb-1 text-sm font-medium">Número O.S</label>
                        <input type="number" step="0.01" id="numero_os" name="numero_os" required
                            value="<?= htmlspecialchars($os['numero_os'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="obra_id" class="text-gray-700 mb-1 text-sm font-medium">Obra</label>
                        <select id="obra_id" name="obra_id"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 p-3">
                            <option value="">Selecione</option>
                            <?php foreach ($obras as $obra): ?>
                                <option value="<?= $obra['id'] ?>" <?= (isset($os['obra_id']) && $os['obra_id'] == $obra['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($obra['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                        <label for="Equipe" class="text-gray-700 mb-1 text-sm font-medium">Equipe</label>
                        <input type="text" step="0.01" id="equipe" name="equipe" required
                            value="<?= htmlspecialchars($os['equipe'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>


                    <div class="flex flex-col">
                        <label for="local" class="text-gray-700 mb-1 text-sm font-medium">Local</label>
                        <input type="text" step="0.01" id="local" name="local" required
                            value="<?= htmlspecialchars($os['local'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
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

                    <div class="flex flex-col ">
                        <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Anexos</label>
                        <input type="file" id="anexos" name="anexos[]" multiple
                            class="w-full bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 p-2" />
                    </div>

                    <div></div>







                </div>



                <div class="w-full bg-white p-6 rounded-lg shadow-lg w-full">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-6 text-start">
                        Serviços Vinculados
                    </h2> <button
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300" data-modal-toggle="adicionarServicoModal">
                        Adicionar Serviço á Os
                    </button>

                    <div id="servicosContainer" class="mt-6 space-y-4">
                        <!-- Serviços adicionados aparecerão aqui -->
                    </div>

                    <input type="hidden" name="servicos_vinculados" id="servicosVinculados">
                </div>

                <div class="flex justify-end">
                    <button type="submit" name="salvar"
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <?= isset($os['id']) ? 'Salvar Alterações' : 'Criar Ordem de Serviço' ?>
                    </button>
                </div>
            </form>

        </div>
    </div>



    <!-- Modal para Adicionar Serviço -->
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
            <form  id="formAdicionarServico">
                <!-- Dropdown de serviços -->
                <div class="mb-4">
                    <label for="servicos" class="block text-sm font-medium text-gray-700">Serviço</label>
                    <select id="servicos" name="servico_id" class="block w-full mt-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        <?php
                        $sql = "SELECT id, nome FROM servicos";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['id'] . "'>" . $row['nome'] . "</option>";
                            }
                        }
                        ?>
                    </select>
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
                        <option value="corretiva">Corretiva</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>

                <!-- Executor -->
                <div class="mb-4">
                    <label for="executor" class="block text-sm font-medium text-gray-700">Executor</label>
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
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Salvar</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        document.querySelector("form").addEventListener("submit", async function(e) {
            e.preventDefault();


            const urlParams = new URLSearchParams(window.location.search);


            const form = e.target;
            const formData = new FormData(form);

            const url = './create.php';


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


    <script>
        let servicos = [];

        function renderServicos() {
            const container = document.getElementById("servicosContainer");
            container.innerHTML = '';

            servicos.forEach((servico, index) => {
                const card = document.createElement('div');
                card.className = "bg-gray-100 p-4 rounded-lg shadow flex justify-between items-center";

                card.innerHTML = `
                <div>
                    <p class="text-lg font-semibold text-gray-800">${servico.nome}</p>
                    <p class="text-sm text-gray-600">Valor: R$ ${servico.valor}</p>
                </div>
                <button onclick="removerServico(${index})" class="text-red-500 hover:text-red-700 font-bold text-sm">Remover</button>
            `;

                container.appendChild(card);
            });

            document.getElementById("servicosVinculados").value = JSON.stringify(servicos);
        }

        function adicionarServico(servico) {
            servicos.push(servico);
            renderServicos();
        }

        function removerServico(index) {
            servicos.splice(index, 1);
            renderServicos();
        }
    </script>

    <script>
        const servicosDisponiveis = <?= json_encode($servicos) ?>;
        const selecionados = new Set();
        const input = document.getElementById("servicoInput");
        const suggestionsBox = document.getElementById("servicoSuggestions");
        const listaSelecionados = document.getElementById("servicosSelecionados");
        const hiddenInput = document.getElementById("servicosHidden");

        function mostrarTodosServicos() {
            suggestionsBox.innerHTML = '';
            const filtrados = servicosDisponiveis.filter(s => !selecionados.has(s));

            filtrados.forEach(servico => {
                const li = document.createElement("li");
                li.textContent = servico;
                li.className = "cursor-pointer px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700";
                li.onclick = () => addServico(servico);
                suggestionsBox.appendChild(li);
            });

            suggestionsBox.classList.toggle("hidden", filtrados.length === 0);
        }

        function filterServicos() {
            const query = input.value.toLowerCase();
            const filtrados = servicosDisponiveis.filter(s => s.toLowerCase().includes(query) && !selecionados.has(s));

            suggestionsBox.innerHTML = '';
            filtrados.forEach(servico => {
                const li = document.createElement("li");
                li.textContent = servico;
                li.className = "cursor-pointer px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700";
                li.onclick = () => addServico(servico);
                suggestionsBox.appendChild(li);
            });

            suggestionsBox.classList.toggle("hidden", filtrados.length === 0);
        }

        function handleAddServico(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                const valor = input.value.trim();
                if (valor && !selecionados.has(valor)) {
                    addServico(valor);
                    if (!servicosDisponiveis.includes(valor)) {
                        servicosDisponiveis.push(valor);
                    }
                }
            }
        }

        function addServico(servico) {
            selecionados.add(servico);
            input.value = '';
            suggestionsBox.classList.add("hidden");
            renderSelecionados();
        }

        function removeServico(servico) {
            selecionados.delete(servico);
            renderSelecionados();
        }

        function renderSelecionados() {
            listaSelecionados.innerHTML = '';
            selecionados.forEach(servico => {
                const tag = document.createElement("div");
                tag.className = "flex items-center bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 rounded-full px-3 py-1 text-sm";
                tag.innerHTML = `
                ${servico}
                <button type="button" class="ml-2 text-blue-700 hover:text-blue-900 dark:hover:text-white" onclick="removeServico('${servico}')">&times;</button>
            `;
                listaSelecionados.appendChild(tag);
            });

            hiddenInput.value = Array.from(selecionados).join(",");
        }
    </script>
    <script>
        // Função para alternar a visibilidade do modal
        function toggleModal() {
            const modal = document.getElementById("adicionarServicoModal");
            modal.classList.toggle("hidden");
        }

        // Abrir o modal (quando o botão for clicado)
        const modalToggleBtns = document.querySelectorAll('[data-modal-toggle="adicionarServicoModal"]');
        modalToggleBtns.forEach(btn => {
            btn.addEventListener("click", () => toggleModal());
        });
    </script>

</body>