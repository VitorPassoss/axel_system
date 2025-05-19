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
$solicitacao = null;

if (isset($_GET['sc_id'])) {
    $sc_id = intval($_GET['sc_id']);
    $stmt = $conn->prepare("
        SELECT 
            sc.*, 
            p.nome AS nome_projeto,
            o.nome AS nome_obra
        FROM 
            solicitacao_compras sc
        LEFT JOIN 
            projetos p ON sc.projeto_id = p.id
        LEFT JOIN 
            obras o ON sc.obra_id = o.id
        WHERE 
            sc.id = ? AND sc.empresa_id = ?");
    $stmt->bind_param("ii", $sc_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitacao = $result->fetch_assoc();
}
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detalhes da Solictação</title>

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
            <h2 class="text-3xl font-bold text-gray-800 dark:text-black mb-6 text-center">
                Detalhes da Solicitação
            </h2>

            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="flex flex-col">
                        <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Descrição</label>
                        <input type="text" id="descricao" name="descricao" required
                            value="<?= htmlspecialchars($solicitacao['descricao'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="fornecedor" class="text-gray-700 mb-1 text-sm font-medium">Fornecedor</label>
                        <input type="text" id="fornecedor" name="fornecedor" required
                            value="<?= htmlspecialchars($solicitacao['fornecedor'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="status" class="text-gray-700 mb-1 text-sm font-medium">Status</label>
                        <select id="status" name="status"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 p-3">
                            <?php
                            $status_options = ['PENDENTE', 'APROVADO', 'REJEITADO', 'PAGO', 'COTAÇÃO'];
                            foreach ($status_options as $status) {
                                // Verifica se o status da solicitação é igual ao status atual da opção
                                $selected = (isset($solicitacao['status']) && $solicitacao['status'] == $status) ? 'selected' : '';
                                echo "<option value=\"$status\" $selected>$status</option>";  // Corrigido o valor da opção
                            }
                            ?>
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <!-- Verifica se há vínculo com Projeto -->
                        <?php if (!empty($solicitacao['projeto_id'])): ?>
                            <label for="projeto" class="text-gray-700 mb-1 text-sm font-medium">Projeto</label>
                            <input type="text" id="projeto" name="projeto"
                                value="<?= htmlspecialchars($solicitacao['nome_projeto'] ?? '') ?>"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" readonly />
                            <small class="text-blue-600"><a href="../projetos/detalhes.php?id=<?= $solicitacao['projeto_id'] ?>" target="_blank">Ver mais detalhes</a></small>
                        <?php endif; ?>

                        <!-- Verifica se há vínculo com Obra -->
                        <?php if (!empty($solicitacao['obra_id'])): ?>
                            <label for="obra" class="text-gray-700 mb-1 text-sm font-medium">Obra</label>
                            <input type="text" id="obra" name="obra"
                                value="<?= htmlspecialchars($solicitacao['nome_obra'] ?? '') ?>"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" readonly />
                            <small class="text-blue-600"><a href="../Obras/detalhes.php??id=<?= $solicitacao['obra_id'] ?>" target="_blank">Ver mais detalhes</a></small>
                        <?php endif; ?>
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
                        <?= isset($solicitacao['id']) ? 'Salvar Alterações' : 'Criar' ?>
                    </button>
                </div>
            </form>




            <?php if (isset($solicitacao['id'])): ?>
                <div class="mt-10 p-6 bg-white rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Documentos Vinculados</h3>
                    <?php
                    $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'contratos' AND ref_id = ?");
                    $stmt_docs->bind_param("i", $solicitacao['id']);
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
            const scId = urlParams.get("sc_id"); // Obtém o ID da solicitação de compra

            const form = e.target;
            const formData = new FormData(form);
            formData.append('sc_id', scId); // Adiciona o ID da solicitação ao FormData

            const url = './update.php'; // Caminho para o arquivo PHP de atualização

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
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#10b981", // Verde
                        close: true
                    }).showToast();

                    form.reset();
                    window.location.href = './index.php'; // Redireciona após sucesso
                } else {
                    Toastify({
                        text: "Operação com erro! Por favor, consulte o suporte.",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#ef4444", // Vermelho
                        close: true
                    }).showToast();
                }
            } catch (error) {
                alert('Erro na requisição: ' + error.message);
            }
        });
    </script>



</body>