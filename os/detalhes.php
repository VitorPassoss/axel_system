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
$obra = null;
$contrato = null;

if (isset($_GET['sc_id'])) {
    $sc_id = intval($_GET['sc_id']);

    // Recuperar dados da OS
    $stmt = $conn->prepare("SELECT * FROM ordem_de_servico WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $sc_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $os = $result->fetch_assoc();

    // Verificar se a OS foi encontrada
    if ($os) {
        $obra_id = $os['obra_id'];

        // Recuperar dados da obra relacionada à OS
        $stmtObra = $conn->prepare("SELECT * FROM obras WHERE id = ? AND empresa_id = ?");
        $stmtObra->bind_param("ii", $obra_id, $empresa_id);
        $stmtObra->execute();
        $resultObra = $stmtObra->get_result();
        $obra = $resultObra->fetch_assoc();

        // Se encontrar a obra, buscar o contrato relacionado
        if ($obra) {
            $contrato_id = $obra['contrato_id'];

            // Recuperar dados do contrato relacionado à obra
            $stmtContrato = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
            $stmtContrato->bind_param("ii", $contrato_id, $empresa_id);
            $stmtContrato->execute();
            $resultContrato = $stmtContrato->get_result();
            $contrato = $resultContrato->fetch_assoc();
        }
    }
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
                Detalhes da Os
            </h2>

            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (isset($os['id'])): ?>
                        <input id="osId" type="hidden" name="id" value="<?= htmlspecialchars($os['id']) ?>">
                    <?php endif; ?>
                    <div class="flex flex-col md:col-span-2">
                        <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Descrição do Serviço</label>
                        <textarea id="descricao" name="descricao" rows="4"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100"><?= htmlspecialchars($os['descricao'] ?? '') ?></textarea>
                    </div>

                    <div class="flex flex-col">
                        <label for="responsavel" class="text-gray-700 mb-1 text-sm font-medium">Responsavel</label>
                        <input type="text" id="responsavel_os" name="responsavel_os" required
                            value="<?= htmlspecialchars($os['responsavel_os'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>

                    <div class="flex flex-col">
                        <label for="numero_os" class="text-gray-700 mb-1 text-sm font-medium">Número O.S</label>
                        <input type="number" step="0.01" id="numero_os" name="numero_os" required
                            value="<?= htmlspecialchars($os['numero_os'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
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


                    <div class="flex flex-col">
                        <label for="local" class="text-gray-700 mb-1 text-sm font-medium">Local</label>
                        <input type="text" step="0.01" id="local" name="local" required
                            value="<?= htmlspecialchars($os['local'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
                    </div>



                    <div class="flex flex-col ">
                        <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Anexos</label>
                        <input type="file" id="anexos" name="anexos[]" multiple
                            class="w-full bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 p-2" />
                    </div>




                </div>

                <div class="flex justify-end gap-6">

                    <button onclick="generatePDF()" name="salvar"
                        class="bg-gray-400 hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        Gerar PDF
                    </button>
                    <button type="submit" name="salvar"
                        class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <?= isset($os['id']) ? 'Salvar Alterações' : 'Criar Ordem de Serviço' ?>
                    </button>
                </div>

            </form>

            <?php if ($obra): ?>
                <div class="w-full bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Informações da Obra</h3>

                    <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo $obra['nome']; ?></h3>

                    <p class="text-sm text-gray-700 mb-1"><strong>Descrição:</strong> <?php echo $obra['descricao']; ?></p>

                    <!-- Endereço será preenchido dinamicamente -->
                    <p class="text-sm text-gray-700 mb-1">
                        <strong>Endereço:</strong>
                        <span id="endereco">Carregando...</span>
                    </p>

                    <p class="text-sm text-gray-700 mb-4"><strong>Responsável Técnico:</strong> <?php echo $obra['responsavel_tecnico']; ?></p>

                    <a href="../Obras/detalhes.php?obra_id=<?php echo $obra['id']; ?>" class="inline-block bg-[#171717] text-white py-2 px-4 rounded-lg text-center hover:bg-blue-600 transition-colors duration-200">Ver mais detalhes</a>
                </div>

                <script>
                    function generatePDF() {
                        const osId = document.getElementById('osId')?.value;
                        if (!osId) {
                            alert("ID da OS não encontrado.");
                            return;
                        }

                        // Abre em nova aba
                        window.open('gerar_os.php?id=' + encodeURIComponent(osId), '_blank');
                    }

                    // Buscar dados do CEP via API
                    const cep = "<?php echo preg_replace('/[^0-9]/', '', $obra['cep']); ?>";
                    if (cep.length === 8) {
                        fetch(`https://viacep.com.br/ws/${cep}/json/`)
                            .then(response => response.json())
                            .then(data => {
                                if (!data.erro) {
                                    const endereco = `${data.logradouro}, ${data.bairro}, ${data.localidade} - ${data.uf}, CEP: ${data.cep}`;
                                    document.getElementById('endereco').innerText = endereco;
                                } else {
                                    document.getElementById('endereco').innerText = "CEP não encontrado.";
                                }
                            })
                            .catch(() => {
                                document.getElementById('endereco').innerText = "Erro ao buscar CEP.";
                            });
                    } else {
                        document.getElementById('endereco').innerText = "CEP inválido.";
                    }
                </script>
            <?php endif; ?>


            <?php if (isset($os['id'])): ?>
                <div class="mt-10 p-6 bg-white rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Solicitações de Compras</h3>

                    <p class="text-gray-500">Nenhuma Solicitação de compra encontrada.</p>

                </div>
            <?php endif; ?>

            <?php if (isset($os['id'])): ?>
                <div class="mt-10 p-6 bg-white rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Documentos Vinculados</h3>
                    <?php
                    $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'ordem_de_servico' AND ref_id = ?");
                    $stmt_docs->bind_param("i", $sc_id);
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