<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];
$solicitacao = null;

if (isset($_GET['sc_id'])) {
    $sc_id = intval($_GET['sc_id']);
    $stmt = $conn->prepare("SELECT * FROM solicitacao_compras WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $sc_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitacao = $result->fetch_assoc();
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


$ordensComObra = [];

$query = "
  SELECT 
    os.id AS os_id,
    os.status,
    o.id AS obra_id,
    o.nome AS obra_nome,
    o.endereco AS obra_endereco
  FROM ordem_de_servico os
  JOIN obras o ON os.obra_id = o.id
  WHERE os.empresa_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $ordensComObra[] = $row;
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
                Registrar Solicitação de Compra
            </h2>

            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">



                    <div class="flex flex-col md:col-span-2">
                        <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Selecione os itens</label>
                        <textarea id="descricao" name="descricao" rows="4"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100"><?= htmlspecialchars($solicitacao['descricao'] ?? '') ?></textarea>
                    </div>


                    <div class="flex flex-col">
                        <label for="numero_os" class="text-gray-700 mb-1 text-sm font-medium">Und de Medida</label>
                        <input type="number" step="0.01" id="numero_os" name="numero_os" required
                            value="<?= htmlspecialchars($os['numero_os'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>



                    <!-- Quantidade -->



                    <!-- Unidade de Medida -->
                    <div class="flex flex-col">
                        <label for="numero_os" class="text-gray-700 mb-1 text-sm font-medium">Quantidade</label>
                        <input type="number" step="0.01" id="numero_os" name="numero_os" required
                            value="<?= htmlspecialchars($os['numero_os'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="ordem_servico_id" class="text-gray-700 mb-1 text-sm font-medium">Selecione uma Ordem de Serviço</label>
                        <select id="ordem_servico_id" name="ordem_servico_id"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 p-3">
                            <option value="">Selecione</option>
                            <?php foreach ($ordensComObra as $ordem): ?>
                                <option value="<?= $ordem['os_id'] ?>">
                                   Numero Os: <?= htmlspecialchars($ordem['os_id']) ?> — <?= htmlspecialchars($ordem['obra_nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>


                    <div class="flex flex-col md:col-span-2">
                        <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Anexos</label>
                        <input type="file" id="anexos" name="anexos[]" multiple
                            class="w-full bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 p-2" />
                    </div>

                </div>

                <div class="flex justify-end">
                    <button type="submit" name="salvar"
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <?= isset($solicitacao['id']) ? 'Salvar Alterações' : 'Criar Solicitação' ?>
                    </button>
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


</body>