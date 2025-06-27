<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$nota = null;
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM notas_fiscais WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $nota = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <title>Detalhes da Nota Fiscal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            font-family: "Poppins", sans-serif;
        }
    </style>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>
    <div class="w-full">
        <div class="relative p-8 animate-fadeIn">
            <button onclick="window.location.href = './index.php'" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-3xl">&times;</button>

            <header class="bg-white rounded-2xl shadow-lg p-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Título e botão voltar -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <button onclick="window.location.href='../notas'" class="text-gray-600 hover:text-primary transition self-start sm:self-auto">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">
                        Resumo do Nota
                    </h1>
                </div>


            </header>
            <form class="bg-white rounded-xl shadow-md p-8 ">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col">
                        <label class="text-gray-700 text-sm font-medium mb-1">Contrato</label>
                        <select disabled class="rounded-lg border p-3">
                            <?php
                            $result = $conn->query("SELECT id, numero_contrato FROM contratos");
                            while ($row = $result->fetch_assoc()) {
                                $selected = $nota && $nota['contrato_id'] == $row['id'] ? 'selected' : '';
                                echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['numero_contrato']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label class="text-gray-700 text-sm font-medium mb-1">Número da Nota</label>
                        <input type="text" disabled class="rounded-lg border p-3" value="<?= htmlspecialchars($nota['numero_nota'] ?? '') ?>">
                    </div>

                    <div class="flex flex-col">
                        <label class="text-gray-700 text-sm font-medium mb-1">Valor Total (R$)</label>
                        <input type="number" step="0.01" disabled class="rounded-lg border p-3" value="<?= htmlspecialchars($nota['valor_total'] ?? '') ?>">
                    </div>

                    <div class="flex flex-col">
                        <label class="text-gray-700 text-sm font-medium mb-1">Data de Recebimento</label>
                        <input type="date" disabled class="rounded-lg border p-3" value="<?= htmlspecialchars($nota['data_recebimento'] ?? '') ?>">
                    </div>

                    <div class="flex flex-col">
                        <label class="text-gray-700 text-sm font-medium mb-1">Data de Emissão</label>
                        <input type="date" disabled class="rounded-lg border p-3" value="<?= htmlspecialchars($nota['data_emissao'] ?? '') ?>">
                    </div>

                    <div class="flex flex-col">
                        <label class="text-gray-700 text-sm font-medium mb-1">Competência - Mês</label>
                        <select disabled class="rounded-lg border p-3">
                            <?php
                            foreach (range(1, 12) as $mes) {
                                $selected = $nota && (int)$nota['competencia_mes'] === $mes ? 'selected' : '';
                                printf('<option value="%02d" %s>%02d</option>', $mes, $selected, $mes);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label class="text-gray-700 text-sm font-medium mb-1">Competência - Ano</label>
                        <input type="number" disabled class="rounded-lg border p-3" value="<?= htmlspecialchars($nota['competencia_ano'] ?? '') ?>">
                    </div>
                </div>

                <div class="flex flex-col">
                    <label class="text-gray-700 text-sm font-medium mb-1">Observações</label>
                    <textarea disabled rows="3" class="rounded-lg border p-3"><?= htmlspecialchars($nota['observacoes'] ?? '') ?></textarea>
                </div>
            </form>

            <!-- Documentos vinculados -->
            <div class="mt-10 p-6 bg-white rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">Documentos Vinculados</h3>
                <?php
                if ($nota):
                    $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'notas_fiscais' AND ref_id = ?");
                    $stmt_docs->bind_param("i", $nota['id']);
                    $stmt_docs->execute();
                    $result_docs = $stmt_docs->get_result();
                    if ($result_docs->num_rows > 0):
                ?>
                        <ul class="divide-y divide-gray-200">
                            <?php while ($doc = $result_docs->fetch_assoc()): ?>
                                <li class="flex items-center justify-between py-2">
                                    <a href="<?= htmlspecialchars($doc['caminho_arquivo']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                        <?= htmlspecialchars($doc['nome']) ?>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-500">Nenhum documento encontrado.</p>
                <?php endif;
                    $stmt_docs->close();
                endif; ?>
            </div>
        </div>
    </div>
</body>

</html>