<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

$empresa_id = $_SESSION['empresa_id'];
$licitacao = null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM licitacoes WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $licitacao = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <title>Nova Licitação</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>
    <div class="w-full p-8">
        <div class="w-full ">

            <header class="bg-white rounded-2xl shadow-lg p-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Título e botão voltar -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <button onclick="window.location.href='../licitacoes'" class="text-gray-600 hover:text-primary transition self-start sm:self-auto">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">
                        Resumo do Licitação
                    </h1>
                </div>

          
            </header>
            <form method="POST" class="space-y-6 mt-6 bg-white px-8 py-10 rounded shadow" id="licitacaoForm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="flex flex-col">
                        <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Descrição</label>
                        <input type="text" id="descricao" name="descricao"
                            value="<?= htmlspecialchars($licitacao['descricao'] ?? '') ?>"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-gray-800" />
                    </div>

                    <div class="flex flex-col">
                        <label for="orgao_cidade" class="text-gray-700 mb-1 text-sm font-medium">Órgão / Cidade</label>
                        <input type="text" id="orgao_cidade" name="orgao_cidade"
                            value="<?= htmlspecialchars($licitacao['orgao_cidade'] ?? '') ?>"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-gray-800" />
                    </div>

                    <div class="flex flex-col">
                        <label for="data" class="text-gray-700 mb-1 text-sm font-medium">Data Abertura</label>
                        <input type="date" id="data" name="data"
                            value="<?= htmlspecialchars($licitacao['data'] ?? '') ?>"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-gray-800" />
                    </div>

                    <div class="flex flex-col">
                        <label for="data" class="text-gray-700 mb-1 text-sm font-medium">Data Fechamento</label>
                        <input type="date" id="data" name="dt_fim"
                            value="<?= htmlspecialchars($licitacao['dt_fim'] ?? '') ?>"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-gray-800" />
                    </div>
                    <div class="flex flex-col">
                        <label for="valor_lote" class="text-gray-700 mb-1 text-sm font-medium">Valor de Lançe</label>
                        <input type="number" step="0.01" id="valor_lote" name="valor_lote"
                            value="<?= htmlspecialchars($licitacao['valor_lote'] ?? '') ?>"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-gray-800" />
                    </div>


                    <div class="flex flex-col sm:col-span-2">
                        <label for="objeto" class="text-gray-700 mb-1 text-sm font-medium">Objeto</label>
                        <textarea id="objeto" name="objeto" rows="4"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-gray-800"><?= htmlspecialchars($licitacao['objeto'] ?? '') ?></textarea>
                    </div>
                    <div class="flex flex-col">
                        <label for="status" class="text-gray-700 mb-1 text-sm font-medium">Status</label>
                        <select id="status" name="status"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-gray-800">
                            <option value="" disabled <?= empty($licitacao['status']) ? 'selected' : '' ?>>Selecione</option>
                            <option value="A Receber" <?= ($licitacao['status'] ?? '') === 'A Receber' ? 'selected' : '' ?>>A Receber</option>
                            <option value="Proposta Enviada" <?= ($licitacao['status'] ?? '') === 'Proposta Enviada' ? 'selected' : '' ?>>Proposta Enviada</option>
                            <option value="Em Julgamento" <?= ($licitacao['status'] ?? '') === 'Em Julgamento' ? 'selected' : '' ?>>Em Julgamento</option>
                            <option value="Arquivada" <?= ($licitacao['status'] ?? '') === 'Arquivada' ? 'selected' : '' ?>>Arquivada</option>
                            <option value="Ganha" <?= ($licitacao['status'] ?? '') === 'Ganha' ? 'selected' : '' ?>>Ganha</option>

                            <option value="Encerrada" <?= ($licitacao['status'] ?? '') === 'Encerrada' ? 'selected' : '' ?>>Encerrada</option>
                        </select>
                    </div>


                    <div class="flex flex-col">
                        <label for="valor_total" class="text-gray-700 mb-1 text-sm font-medium">Valor Previsto</label>
                        <input type="number" step="0.01" id="valor_total" name="valor_total"
                            value="<?= htmlspecialchars($licitacao['valor_total'] ?? '') ?>"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-gray-800" />
                    </div>

                    <div class="flex flex-col ">
                        <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Anexos</label>
                        <input type="file" id="anexos" name="anexos[]" multiple
                            class="w-full bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-100 p-2" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" name="criar"
                        class="bg-black text-white px-6 py-3 rounded hover:bg-gray-900 transition">
                        <?= isset($licitacao['id']) ? 'Salvar Alterações' : 'Criar Licitação' ?>
                    </button>
                </div>
            </form>

            <?php if (isset($licitacao['id'])): ?>
                <div class="mt-4 p-6 bg-white rounded-lg shadow-md mb-[80px] ">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">Documentos Vinculados</h3>
                    <?php
                    $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'licitacoes' AND ref_id = ?");
                    $stmt_docs->bind_param("i", $licitacao['id']);
                    $stmt_docs->execute();
                    $result_docs = $stmt_docs->get_result();
                    if ($result_docs->num_rows > 0):
                    ?>
                        <ul class="divide-y divide-gray-200">
                            <?php while ($doc = $result_docs->fetch_assoc()): ?>
                                <li class="flex items-center justify-between py-2">
                                    <div>
                                        <a href="<?= htmlspecialchars($doc['caminho_arquivo']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                            <?= htmlspecialchars($doc['nome']) ?>
                                        </a>
                                    </div>
                                    <form method="get" action="delete_document.php" onsubmit="return confirm('Tem certeza que deseja excluir este documento?')">
                                        <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Excluir</button>
                                    </form>

                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-500 ">Nenhum documento encontrado.</p>
                    <?php endif; ?>
                    <?php $stmt_docs->close(); ?>
                </div>
            <?php endif; ?>

        </div>


        <div class="mb-[100px]"></div>
    </div>

    <script>
        document.getElementById('licitacaoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const url = <?= isset($licitacao['id']) ? "'update.php?id={$licitacao['id']}'" : "'create.php'" ?>;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                if (response.ok) {
                    Toastify({
                        text: "Operação realizada com sucesso!",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#10b981",
                        close: true
                    }).showToast();

                    setTimeout(() => {
                        window.location.href = './index.php';
                    }, 1000);
                } else {
                    alert('Erro ao salvar.');
                }
            } catch (error) {
                alert('Erro de rede: ' + error.message);
            }
        });
    </script>
</body>

</html>