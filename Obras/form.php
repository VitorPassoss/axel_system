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


$sql = "SELECT * FROM status_obras";
$stmt = $conn->prepare($sql);
$stmt->execute();
$status_obras = $stmt->get_result();


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
            <!-- Cabeçalho -->
            <h2 class="text-3xl font-bold text-gray-800 dark:text-black mb-6 text-center">
                <?php
                if (isset($contrato_id) && !empty($contrato_id)) {
                    // Se houver contrato, exibe "Editar Contrato"
                    echo 'Editar Obra';
                } else {
                    // Caso contrário, exibe "Novo Contrato"
                    echo 'Nova Obra';
                }
                ?>
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
                        <select id="status" name="status_id" required
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Selecione o Status</option> <!-- Opção padrão -->
                            <?php while ($status = $status_obras->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($status['id']) ?>">
                                    <?= htmlspecialchars($status['nome']) ?>
                                </option>
                            <?php endwhile; ?>
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


    <script>
        document.querySelector("form").addEventListener("submit", async function(e) {
            e.preventDefault();

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


</body>