<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];

$sql = "
    SELECT 
        id, 
        descricao, 
        orgao_cidade, 
        data, 
        objeto, 
        valor_lote, 
        status, 
        valor_total,
        dt_fim,
        DATEDIFF(dt_fim, CURDATE()) AS dias_restantes
    FROM licitacoes
    WHERE empresa_id = ?
    ORDER BY dias_restantes ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Licitações</title>

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


    <!-- Tabela -->
    <div class="flex-1 p-8 space-y-8">
        <div class="flex flex-row justify-between items-center shadow bg-white py-6 px-6 rounded-2xl">
            <h1 class="text-3xl font-bold text-primary">Licitações</h1>
            <button onclick="toggleModal()" class="bg-primary py-2 px-8 rounded-lg font-semibold text-white">
                + Nova Licitação
            </button>
        </div>

        <div class="overflow-x-auto rounded-lg shadow-lg bg-white">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-sm  border">Licitação</th>
                        <th class="px-6 py-3 text-left text-sm  border">Órgão / Cidade</th>
                        <th class="px-6 py-3 text-left text-sm  border">Abertura</th>
                        <th class="px-6 py-3 text-left text-sm  border">Fechamento</th>
                        <th class="px-6 py-3 text-left text-sm  border">Prazo Restante</th>

                        <th class="px-6 py-3 text-left text-sm  border">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($row = $result->fetch_assoc()) {
                        $hoje = new DateTime(); // Data atual
                        $data_fim = new DateTime($row['dt_fim']);
                        $intervalo = $hoje->diff($data_fim);
                        $dias_restantes = $data_fim >= $hoje ? $intervalo->days : 0;

                        // Classe condicional para destacar prazo crítico
                        $row_class = "hover:bg-gray-100";
                        if ($dias_restantes < 10 && $data_fim >= $hoje) {
                            $row_class .= " bg-red-50 border-l-4 border-red-500";
                        }
                    ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td onclick="window.location.href='detalhes.php?id=<?php echo htmlspecialchars($row['id']); ?>'" class="px-6 py-4 border text-sm">
                                <?php
                                $descricao = htmlspecialchars($row['descricao']);
                                $palavras = explode(' ', $descricao);
                                $descricao_truncada = implode(' ', array_slice($palavras, 0, 2));
                                echo count($palavras) > 2 ? $descricao_truncada . '...' : $descricao;
                                ?>
                            </td>

                            <td onclick="window.location.href='detalhes.php?id=<?php echo htmlspecialchars($row['id']); ?>'" class="px-6 py-4 border text-sm">
                                <?php
                                $orgao = htmlspecialchars($row['orgao_cidade']);
                                $palavras = explode(' ', $orgao);
                                $orgao_truncado = implode(' ', array_slice($palavras, 0, 2));
                                echo count($palavras) > 2 ? $orgao_truncado . '...' : $orgao;
                                ?>
                            </td>
                            <td onclick="window.location.href='detalhes.php?id=<?php echo htmlspecialchars($row['id']); ?>'" class="px-6 py-4 border text-sm"><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
                            <td onclick="window.location.href='detalhes.php?id=<?php echo htmlspecialchars($row['id']); ?>'" class="px-6 py-4 border text-sm"><?php echo date('d/m/Y', strtotime($row['dt_fim'])); ?></td>

                            <td onclick="window.location.href='detalhes.php?id=<?php echo htmlspecialchars($row['id']); ?>'" class="px-6 py-4 border text-sm">
                                <?php
                                if ($data_fim < $hoje) {
                                    echo 'Vencido';
                                } else {
                                    echo $dias_restantes . ' dias';
                                }
                                ?>
                            </td>
                            <td onclick="window.location.href='detalhes.php?id=<?= htmlspecialchars($row['id']); ?>'" class="px-6 py-4 border text-sm">
                                <?php
                                $status = htmlspecialchars($row['status']);

                                $statusClasses = [
                                    'A Receber'     => 'bg-yellow-100 text-yellow-800',
                                    'Proposta Enviada'       => 'bg-blue-100 text-blue-800',
                                    'Em Julgamento' => 'bg-purple-100 text-purple-800',
                                    'Arquivada'     => 'bg-gray-200 text-gray-800',
                                    'Ganha'     => 'bg-green-100 text-green-800',

                                    'Encerrada'     => 'bg-red-100 text-white-800',
                                ];

                                $class = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';

                                echo "<span class='px-3 py-1 rounded-full font-medium text-sm {$class}'>{$status}</span>";
                                ?>
                            </td>

                        </tr>
                    <?php } ?>
                </tbody>



            </table>
        </div>
    </div>






    <script>
        function toggleModal() {
            window.location.href = './form.php'
        }

        function editarContrato(id) {
            if (confirm('Deseja editar este projeto?')) {
                window.location.href = 'form.php?licitacao_id=' + id;
            }
        }

        function visualizarContrato(id) {
            window.location.href = 'detalhes.php?licitacao_id=' + id;

        }
    </script>

    <script>
        function deleteContrato(id) {
            if (confirm('Tem certeza que deseja excluir?')) {
                const formData = new FormData(document.getElementById('delete-form-' + id));
                fetch('./delete.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Toastify({
                                text: "Contrato Excluido com Sucesso!",
                                duration: 3000,
                                gravity: "top", // "top" ou "bottom"
                                position: "right", // "left", "center" ou "right"
                                backgroundColor: "#10b981", // Verde (tailwind: bg-green-500)
                                close: true
                            }).showToast(); // Opcional: Redirecionar ou atualizar a página

                            setTimeout(() => {
                                window.location.reload()
                            }, 500)
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
                    })
                    .catch(error => {
                        alert('Erro na requisição: ' + error);
                    });
            }
        }
    </script>
    <style>
        .input {
            @apply w-full p-3 bg-dark border border-gray-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent;
        }
    </style>

</body>

</html>

<?php $conn->close(); ?>