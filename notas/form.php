<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nova Nota Fiscal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            font-family: "Poppins", sans-serif;
            font-style: normal;
        }
    </style>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>
    <div class="w-full">
        <div class="relative p-8 animate-fadeIn ">
            <button onclick="window.location.href = './index.php'" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-3xl">&times;</button>

            <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Nova Nota Fiscal</h2>

            <form id="formNotaFiscal" enctype="multipart/form-data" class="bg-white rounded-xl shadow-md p-8  space-y-6">


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col">
                        <label for="contrato_id" class="text-gray-700 text-sm font-medium mb-1">Contrato</label>
                        <select id="contrato_id" name="contrato_id" required class="rounded-lg border p-3">
                            <option value="">Selecione um contrato</option>
                            <?php
                            $result = $conn->query("SELECT id, numero_contrato FROM contratos");
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['numero_contrato']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label for="numero_nota" class="text-gray-700 text-sm font-medium mb-1">Número da Nota</label>
                        <input type="text" id="numero_nota" name="numero_nota" required class="rounded-lg border p-3">
                    </div>

                    <div class="flex flex-col">
                        <label for="valor_total_formatado" class="text-gray-700 text-sm font-medium mb-1">Valor Total (R$)</label>

                        <!-- Campo visível formatado -->
                        <input type="text" id="valor_total_formatado" class="rounded-lg border p-3" placeholder="R$ 0,00">

                        <!-- Campo oculto que será enviado -->
                        <input type="hidden" id="valor_total" name="valor_total">
                    </div>


                    <div class="flex flex-col">
                        <label for="data_recebimento" class="text-gray-700 text-sm font-medium mb-1">Data de Recebimento</label>
                        <input type="date" id="data_recebimento" name="data_recebimento" required class="rounded-lg border p-3">
                    </div>
                    <div class="flex flex-col">
                        <label for="data_emissao" class="text-gray-700 text-sm font-medium mb-1">Data de Emissão</label>
                        <input type="date" id="data_emissao" name="data_emissao" class="rounded-lg border p-3">
                    </div>

                    <div class="flex flex-col">
                        <label for="competencia_mes" class="text-gray-700 text-sm font-medium mb-1">Competência - Mês</label>
                        <select id="competencia_mes" name="competencia_mes" required class="rounded-lg border p-3">
                            <?php
                            foreach (range(1, 12) as $mes) {
                                printf('<option value="%02d">%02d</option>', $mes, $mes);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label for="competencia_ano" class="text-gray-700 text-sm font-medium mb-1">Competência - Ano</label>
                        <input type="number" id="competencia_ano" name="competencia_ano" min="2000" max="2100"
                            value="<?= date('Y') ?>" required class="rounded-lg border p-3">
                    </div>


                    <div class="flex flex-col">
                        <label for="anexos" class="text-gray-700 text-sm font-medium mb-1">Anexos</label>
                        <input type="file" id="anexos" name="anexos[]" multiple
                            class="rounded-lg border p-3 text-gray-700 bg-white" />
                    </div>

                </div>

                <div class="flex flex-col">
                    <label for="observacoes" class="text-gray-700 text-sm font-medium mb-1">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3" class="rounded-lg border p-3"></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-[#1D4ED8] hover:bg-[#1E40AF] text-white font-semibold px-6 py-3 rounded-lg transition">
                        Salvar
                    </button>
                </div>
            </form>

        </div>
    </div>
</body>

<script>
    document.getElementById("valor_total_formatado").addEventListener("input", function() {
        const formatted = this.value;

        // Remove tudo que não for número ou vírgula/ponto
        const clean = formatted.replace(/[^\d,]/g, '').replace(',', '.');

        // Converte para float
        const numericValue = parseFloat(clean);

        if (!isNaN(numericValue)) {
            // Atualiza campo oculto
            document.getElementById("valor_total").value = numericValue.toFixed(2);

            // Reformatar para exibição (R$ 1.234,56)
            const reais = numericValue.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
            this.value = reais;
        }
    });
</script>
<script>
    document.getElementById("formNotaFiscal").addEventListener("submit", function(e) {
        e.preventDefault(); // Impede envio tradicional

        const form = e.target;
        const formData = new FormData(form);

        fetch("./create.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Toastify({
                    text: data.message,
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: data.success ? "#16A34A" : "#DC2626",
                    stopOnFocus: true
                }).showToast();

                if (data.success) {
                    form.reset();
                    // Se quiser redirecionar: window.location.href = './index.php';
                }
            })
            .catch(() => {
                Toastify({
                    text: "Erro ao enviar os dados.",
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#DC2626"
                }).showToast();
            });
    });
</script>

</html>