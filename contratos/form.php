<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];
$contrato = null;

if (isset($_GET['contrato_id'])) {
    $contrato_id = intval($_GET['contrato_id']);
    $stmt = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $contrato_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contrato = $result->fetch_assoc();
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
                    echo 'Editar Contrato';
                } else {
                    // Caso contrário, exibe "Novo Contrato"
                    echo 'Novo Contrato';
                }
                ?>
            </h2>

            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

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
                        <label for="situacao" class="text-gray-700 mb-1 text-sm font-medium">Situação do Contrato</label>
                        <select id="situacao" name="situacao" required
                            class="w-full bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 p-2">
                            <option value="" disabled selected>Selecione a situação</option>
                            <option value="Ativo">🟢 Ativo</option>
                            <option value="Inativo">🔴 Inativo</option>

                        </select>
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

                    <input type="file" id="anexos" name="anexos[]" multiple />



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
        </div>
    </div>


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