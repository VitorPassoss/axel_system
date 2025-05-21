<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}


$empresa_id = $_SESSION['empresa_id'];

$sql = "
  SELECT 
    obras.*, 
    status_obras.nome AS status_nome,
    status_obras.cor as status_cor
  FROM obras 
  JOIN status_obras ON obras.status_id = status_obras.id 
  WHERE obras.empresa_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();


?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Obras</title>

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

    <div class="flex-1 p-8 space-y-8">
        <!-- Cabeçalho -->
        <div class="flex flex-row justify-between items-center justify-center shadow  bg-[#FFFFFF] py-4 px-6 rounded-2xl ">
            <div class="">
                <h1 class="text-3xl font-bold text-primary">Obras</h1>
            </div>
            <button onclick="toggleModal()" class="mt-4  bg-primary  py-2 px-8 rounded-lg font-semibold transition text-white">
                + Nova Obra
            </button>


        </div>

        <button onclick="window.location.href='../quadro/obras_quadro.php'" class="mt-4 flex items-center gap-2 bg-white py-2 px-6 rounded-lg font-semibold border border-gray-300 hover:bg-gray-100 text-gray-800 transition">
      <i class="fas fa-th-large text-gray-600"></i>
      Visualizar Quadro
    </button>


        <!-- Tabela -->
        <div class="overflow-x-auto rounded-lg shadow-lg bg-white">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th></th> <!-- Botão dropdown -->
                        <th class="px-6 py-3 text-left text-sm uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-sm uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-sm uppercase">Início</th>
                        <th class="px-6 py-3 text-left text-sm uppercase">Responsável</th>
                        <th class="px-6 py-3 text-left text-sm uppercase">Cliente</th>
                        <th class="px-6 py-3 text-center text-sm uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($row = $result->fetch_assoc()) {
                        $obra_id = $row['id'];
                    ?>
                        <tr class="hover:bg-gray-100">
                            <td class="px-2 text-center">
                                <button id="btn-<?php echo $obra_id; ?>" onclick="toggleDropdown(<?php echo $obra_id; ?>)">
                                    <i class="fas fa-chevron-down text-gray-500"></i>
                                </button>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($row['nome']); ?></td>

                            <td class="px-6 py-4">
                                <span style="background-color: <?= htmlspecialchars($row['status_cor']) ?>; color: black;"
                                    class="px-3 py-1 rounded-full text-sm font-semibold ">
                                    <?= htmlspecialchars($row['status_nome']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4"><?php echo $row['data_inicio']; ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($row['responsavel_tecnico']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($row['cliente']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="editarObra(<?php echo $obra_id; ?>)" class="hover:underline ml-2">
                                    <i class="fas fa-eye mr-3 text-gray-500"></i>
                                </button>
                                <form id="delete-<?php echo $obra_id; ?>" class="inline" onsubmit="return false;">
                                    <input type="hidden" name="id" value="<?php echo $obra_id; ?>">
                                    <button type="button" class="text-red-500 hover:underline ml-2" onclick="deleteObra(<?php echo $obra_id; ?>)">
                                        <i class="fas fa-trash mr-1"></i> Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Linha expandida com as solicitações -->
                        <tr id="dropdown-<?php echo $obra_id; ?>" class="hidden bg-gray-50">
                            <td colspan="7" class="px-6 py-4">
                                <div class="text-sm">
                                    <strong>Solicitações de Compra:</strong>
                                    <div id="solicitacoes-<?php echo $obra_id; ?>" class="mt-2 text-gray-700">
                                        <em>Carregando...</em>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    <?php } ?>
                </tbody>
            </table>
        </div>


        <!-- Modal Novo Projeto -->
        <div id="modal-criar" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="relative bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-2xl w-11/12 md:w-2/3 lg:w-1/2 animate-fadeIn">

                <!-- Botão Fechar -->
                <button onclick="toggleModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-3xl">
                    &times;
                </button>

                <!-- Cabeçalho -->
                <h2 class="text-2xl font-bold text-gray-800 dark:text-black mb-4 text-center">
                    Nova Obra
                </h2>

                <!-- Formulário -->
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Nome da Obra -->
                        <div class="flex flex-col">
                            <label for="nome" class="text-gray-700 mb-1 text-sm font-medium">Nome da Obra</label>
                            <input type="text" id="nome" name="nome" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                        </div>

                        <!-- Data de Início -->
                        <div class="flex flex-col">
                            <label for="data_inicio" class="text-gray-700 mb-1 text-sm font-medium">Data de Início</label>
                            <input type="date" id="data_inicio" name="data_inicio" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                        </div>

                        <!-- Previsão de Término -->
                        <div class="flex flex-col">
                            <label for="data_previsao_fim" class="text-gray-700 mb-1 text-sm font-medium">Previsão de Término</label>
                            <input type="date" id="data_previsao_fim" name="data_previsao_fim" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                        </div>

                        <!-- Responsável Técnico -->
                        <div class="flex flex-col">
                            <label for="responsavel_tecnico" class="text-gray-700 mb-1 text-sm font-medium">Responsável Técnico</label>
                            <input type="text" id="responsavel_tecnico" name="responsavel_tecnico" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                        </div>

                        <!-- Cliente -->
                        <div class="flex flex-col">
                            <label for="cliente" class="text-gray-700 mb-1 text-sm font-medium">Cliente</label>
                            <input type="text" id="cliente" name="cliente" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                        </div>

                        <!-- Tipo de Obra -->
                        <div class="flex flex-col">
                            <label for="tipo_obra" class="text-gray-700 mb-1 text-sm font-medium">Tipo de Obra</label>
                            <input type="text" id="tipo_obra" name="tipo_obra" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                        </div>

                        <div class="flex flex-col">
                            <label for="tipo_obra" class="text-gray-700 mb-1 text-sm font-medium">Cidade</label>
                            <input type="text" id="cidade" name="cidade" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                        </div>

                        <div class="flex flex-col">
                            <label for="tipo_obra" class="text-gray-700 mb-1 text-sm font-medium">Estado</label>
                            <input type="text" id="estado" name="estado" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                        </div>


                        <div class="flex flex-col">
                            <label for="tipo_obra" class="text-gray-700 mb-1 text-sm font-medium">CEP</label>
                            <input type="text" id="cep" name="cep" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                        </div>



                        <!-- Status -->
                        <div class="flex flex-col">
                            <label for="status" class="text-gray-700 mb-1 text-sm font-medium">Status</label>
                            <select id="status" name="status" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="Planejada">Planejada</option>
                                <option value="Em andamento">Em andamento</option>
                                <option value="Concluída">Concluída</option>
                                <option value="Suspensa">Suspensa</option>
                                <option value="Cancelada">Cancelada</option>
                            </select>
                        </div>

                        <!-- Contrato -->
                        <div class="flex flex-col">
                            <label for="contrato_id" class="text-gray-700 mb-1 text-sm font-medium">Contrato (Número)</label>
                            <select id="contrato_id" name="contrato_id" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">Nenhum Contrato</option>

                                <?php
                                // Consulta os contratos pela numeração
                                $contratos_sql = "SELECT id, numero_contrato FROM contratos";
                                $contratos_result = $conn->query($contratos_sql);
                                while ($contrato_row = $contratos_result->fetch_assoc()) {
                                    echo '<option value="' . $contrato_row['id'] . '">' . $contrato_row['numero_contrato'] . '</option>';
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
                                    echo '<option value="' . $projeto_row['id'] . '">' . $projeto_row['nome'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                    <!-- Descrição -->
                    <div class="flex flex-col">
                        <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="4" placeholder="Escreva uma descrição..."
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
                    </div>

                    <!-- Botão de Enviar -->
                    <div class="flex justify-end">
                        <button type="submit" name="criar"
                            class="bg-primary hover:bg-primary-dark text-white font-semibold px-6 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                            Criar
                        </button>
                    </div>
                </form>

            </div>
        </div>



    </div>

    <script>
        function toggleModal() {
            window.location.href = './form.php'
        }

        function editarObra(id) {
            window.location.href = 'detalhes.php?obra_id=' + id;
        }
    </script>
    <script>
        function toggleDropdown(obraId) {

            const row = document.getElementById('dropdown-' + obraId);
            const solicitacoesDiv = document.getElementById('solicitacoes-' + obraId);
            const iconBtn = document.querySelector('#btn-' + obraId + ' i'); // ícone dentro do botão

            const isHidden = row.classList.contains('hidden');

            // Alterna o ícone
            if (iconBtn) {
                iconBtn.classList.remove('fa-chevron-down', 'fa-chevron-up');
                iconBtn.classList.add(isHidden ? 'fa-chevron-up' : 'fa-chevron-down');
            }



            if (row.classList.contains('hidden')) {
                row.classList.remove('hidden');

                if (!solicitacoesDiv.dataset.loaded) {
                    solicitacoesDiv.innerHTML = "<em>Carregando...</em>";

                    fetch('./get_solicitacoes_json.php?obra_id=' + obraId)
                        .then(response => response.json())
                        .then(data => {
                            if (Array.isArray(data) && data.length > 0) {
                                let html = '<div class="space-y-4">';
                                data.forEach(item => {
                                    html += `
                                    <div class="flex flex-col "> 
                                           <div class="border border-gray-300 rounded-lg p-3 bg-white shadow-sm flex gap-4">
                                    <div><strong>ID:</strong> #${item.id}</div>
                                    <div><strong>Descrição:</strong> ${item.descricao}</div>
                                    <div><strong>Status:</strong> ${item.status}</div>
                                    <div><strong>Fornecedor:</strong> ${item.fornecedor ?? '---'}</div>
                                    <div><strong>Valor Total:</strong> R$ ${parseFloat(item.valor).toFixed(2)}</div>
                                  
                                </div>

                                  <button onclick="window.location.href='../recursos/detalhes.php?sc_id=${item.id}'" class="mt-2 w-[150px] px-3 py-1 bg-black text-white text-sm rounded hover:bg-blue-700">
                                        Ver Detalhes
                                    </button>
                                    </div>
                            `;
                                });
                                html += '</div>';
                                solicitacoesDiv.innerHTML = html;
                            } else {
                                solicitacoesDiv.innerHTML = '<p class="text-gray-500">Nenhuma solicitação encontrada para esta obra.</p>';
                            }

                            solicitacoesDiv.dataset.loaded = "true";
                        })
                        .catch(error => {
                            console.error(error);
                            solicitacoesDiv.innerHTML = '<p class="text-red-500">Erro ao carregar solicitações.</p>';
                        });
                }
            } else {
                row.classList.add('hidden');
            }
        }

        // Função de exemplo para botão de detalhes
    </script>


    <script>
        function deleteObra(id) {
            if (!confirm('Tem certeza que deseja excluir este contrato?')) return;

            const form = document.getElementById(`delete-${id}`);
            const formData = new FormData(form);

            fetch('./delete.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Toastify({
                            text: "Obra Excluida com Sucesso!",
                            duration: 3000,
                            gravity: "top", // "top" ou "bottom"
                            position: "right", // "left", "center" ou "right"
                            backgroundColor: "#10b981", // Verde (tailwind: bg-green-500)
                            close: true
                        }).showToast(); // Opcional: Redirecionar ou atualizar a página

                        setTimeout(() => {
                            window.location.reload()
                        }, 500)
                    } else {
                        alert('Erro ao excluir: ' + (data.message || 'Tente novamente.'));
                    }
                })
                .catch(error => {
                    Toastify({
                        text: "Operação com Erro!. Por Favor Consulte o Suporte",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#ef4444", // Vermelho (tailwind: bg-red-500)
                        close: true
                    }).showToast();
                });
        }
    </script>
    <style>
        .input {
            @apply w-full p-3 bg-dark border border-gray-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent;
        }
    </style>

</body>

</html>

<?php $conn->close(); ?>