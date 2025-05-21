<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];
$projeto = null;

if (isset($_GET['projeto_id'])) {
    $projeto_id = intval($_GET['projeto_id']);
    $stmt = $conn->prepare("SELECT * FROM projetos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $projeto_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $projeto = $result->fetch_assoc();
}


?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>projetos</title>

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
                if (isset($projeto_id) && !empty($projeto_id)) {
                    // Se houver contrato, exibe "Editar Contrato"
                    echo 'Editar Projeto';
                } else {
                    // Caso contrário, exibe "Novo Contrato"
                    echo 'Novo Projeto';
                }
                ?>
            </h2>

            <!-- Formulário -->
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <input type="hidden" name="projeto_id" value="<?= $projeto['id'] ?? '' ?>">

                    <div class="flex flex-col">
                        <label for="nome" class="text-gray-700 mb-1 text-sm font-medium">Nome do Projeto</label>
                        <input type="text" id="nome" name="nome" required
                            value="<?= htmlspecialchars($projeto['nome'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 p-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="data_inicio" class="text-gray-700 mb-1 text-sm font-medium">Data de Início</label>
                        <input type="date" id="data_inicio" name="data_inicio" required
                            value="<?= htmlspecialchars($projeto['data_inicio'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 p-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="data_fim" class="text-gray-700 mb-1 text-sm font-medium">Data de Fim</label>
                        <input type="date" id="data_fim" name="data_fim" required
                            value="<?= htmlspecialchars($projeto['data_fim'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 p-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="valor" class="text-gray-700 mb-1 text-sm font-medium">Valor</label>
                        <input type="number" id="valor" name="valor" required
                            value="<?= htmlspecialchars($projeto['valor'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 p-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="responsavel" class="text-gray-700 mb-1 text-sm font-medium">Responsável</label>
                        <input type="text" id="responsavel" name="responsavel" required
                            value="<?= htmlspecialchars($projeto['responsavel'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 p-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="cliente_nome" class="text-gray-700 mb-1 text-sm font-medium">Cliente</label>
                        <input type="text" id="cliente_nome" name="cliente_nome" required
                            value="<?= htmlspecialchars($projeto['cliente_nome'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 p-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col">
                        <label for="status" class="text-gray-700 mb-1 text-sm font-medium">Status do Projeto</label>
                        <select id="status" name="status" required
                            class="w-full rounded-lg border border-gray-300 p-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary">
                            <?php
                            $status_sql = "SELECT * FROM status";
                            $status_result = $conn->query($status_sql);
                            while ($status_row = $status_result->fetch_assoc()) {
                                $selected = (isset($projeto['status_fk']) && $projeto['status_fk'] == $status_row['id']) ? 'selected' : '';
                                echo '<option value="' . $status_row['id'] . '" ' . $selected . '>' . $status_row['nome'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Contrato -->
                    <div class="flex flex-col">
                        <label for="contrato_id" class="text-gray-700 mb-1 text-sm font-medium">Contrato (Número)</label>
                        <select id="contrato_id" name="contrato_id" required
                            class="w-full rounded-lg border border-gray-300 p-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary">
                            <?php
                            $contratos_sql = "SELECT id, numero_contrato FROM contratos WHERE empresa_id = ?";
                            $stmt = $conn->prepare($contratos_sql);
                            $stmt->bind_param("i", $empresa_id);
                            $stmt->execute();
                            $contratos_result = $stmt->get_result();
                            while ($contrato_row = $contratos_result->fetch_assoc()) {
                                $selected = (isset($projeto['contrato_id']) && $projeto['contrato_id'] == $contrato_row['id']) ? 'selected' : '';
                                echo '<option value="' . $contrato_row['id'] . '" ' . $selected . '>' . $contrato_row['numero_contrato'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Descrição -->

                    <div class="flex flex-col  md:col-span-2">
                        <label for="observacoes" class="text-gray-700 mb-1 text-sm font-medium">Observações</label>
                        <textarea id="descricao" name="descricao" rows="4" value="<?= htmlspecialchars($projeto['descricao'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100"><?= htmlspecialchars($projeto['descricao'] ?? '') ?></textarea>
                    </div>


                    <div class="flex justify-end  md:col-span-2">
                        <button type="submit" name="criar"
                            class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                            <?= isset($projeto['id']) ? 'Salvar Alterações' : 'Criar' ?>
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

            const url = './update.php';


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