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

$empresa = null;

if (isset($_GET['empresa_id'])) {
    $empresa_id = intval($_GET['empresa_id']);
    $stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
    $stmt->bind_param("i",  $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $empresa = $result->fetch_assoc();
}


?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Empresa Detalhes</title>

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
            <button onclick="window.location.href = './index.php' " class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-3xl">
                &times;
            </button>

            <!-- Cabeçalho -->
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-6 text-center">
                Detalhes da Empresa
            </h2>

            <!-- Formulário -->
            <!-- Formulário -->
            <form method="POST" class="space-y-6" action="./update.php" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (isset($empresa['id'])): ?>
                        <input type="hidden" name="empresa_id" value="<?= $empresa['id'] ?>">
                    <?php endif; ?>

                    <div class="flex flex-col">
                        <label for="nome" class="text-gray-700 mb-1 text-sm font-medium">Nome da Empresa</label>
                        <input type="text" id="nome" name="nome" required
                            value="<?= htmlspecialchars($empresa['nome'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="cnpj" class="text-gray-700 mb-1 text-sm font-medium">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" required
                            value="<?= htmlspecialchars($empresa['cnpj'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>

                    <div class="flex flex-col">
                        <label for="cidade" class="text-gray-700 mb-1 text-sm font-medium">Cidade</label>
                        <input type="text" id="cidade" name="cidade" required
                            value="<?= htmlspecialchars($empresa['localizacao'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>


                    <div class="flex flex-col">
                        <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Anexos</label>
                        <input type="file" id="anexos" name="anexos[]" multiple
                            class="w-full text-gray-800 dark:text-gray-100" />
                    </div>

                </div>

                <div class="flex justify-end">
                    <button type="submit" name="criar"
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <?= isset($empresa['id']) ? 'Salvar Alterações' : 'Criar' ?>
                    </button>
                </div>
            </form>


            <?php if (isset($empresa['id'])): ?>
                <div class="mt-10 p-6 bg-white rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Documentos Vinculados</h3>
                    <?php
                    $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'empresas' AND ref_id = ?");
                    $stmt_docs->bind_param("i", $empresa['id']);
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




        </div>
    </div>


    <script>
        document.querySelector("form").addEventListener("submit", async function(e) {
            e.preventDefault();


            const urlParams = new URLSearchParams(window.location.search);
            const empresaId = urlParams.get("empresa_id");


            const form = e.target;
            const formData = new FormData(form);

            const url = empresaId ? './update.php' : './create.php';


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