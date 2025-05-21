<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados


include '../backend/dbconn.php';
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Obter o ID da empresa do usuário logado
$empresa_id = $_SESSION['empresa_id'];

// Recuperar status das obras
$status_sql = "SELECT * FROM status_obras ORDER BY id";
$status_result = $conn->query($status_sql);

// Recuperar obras da empresa específica
$obras_sql = "SELECT o.*, s.nome AS status_nome 
              FROM obras o 
              LEFT JOIN status_obras s ON o.status_id = s.id 
              WHERE o.empresa_id = ?";

$stmt = $conn->prepare($obras_sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$obras_result = $stmt->get_result();

// Organizar obras por status
$obras_por_status = [];
while ($obra = $obras_result->fetch_assoc()) {
    $obras_por_status[$obra['status_id']][] = $obra;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Projetos em Andamento</title>

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

    <div class="flex-1 p-8 space-y-8 max-w-full">
        <!-- Cabeçalho -->
        <div class="flex flex-row justify-between items-center shadow bg-[#FFFFFF] py-4 px-6 rounded-2xl">

            <div class="flex items-center gap-4">
                <button onclick="window.location.href='../obras'" class="text-gray-600 hover:text-primary transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </button>
                <h1 class="text-3xl font-bold text-primary">Quadro de Obras</h1>
            </div>

            <button onclick="document.getElementById('modal-status').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded">
                + Novo Quadro
            </button>

        </div>

        <div class="flex space-x-4 overflow-x-auto px-4 py-2 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
            <?php while ($status = $status_result->fetch_assoc()): ?>
                <div class="w-[25%] bg-white rounded-lg shadow p-4 flex-shrink-0" style="background: <?= htmlspecialchars($status['cor']) ?>;">
                    <h2 class="text-lg font-bold mb-4">
                        <?= htmlspecialchars($status['nome']) ?>
                    </h2>
                    <div class="space-y-2 min-h-[100px]" data-status-id="<?= $status['id'] ?>" ondrop="drop(event)" ondragover="allowDrop(event)">
                        <?php
                        $obras = $obras_por_status[$status['id']] ?? [];
                        foreach ($obras as $obra):
                        ?>
                            <div class="bg-gray-100 p-3 rounded shadow cursor-pointer" draggable="true" ondragstart="drag(event)" id="obra-<?= $obra['id'] ?>" ondblclick="window.location.href='../projetos/detalhes.php?projeto_id=<?= $obra['id'] ?>'">
                                <p class="font-semibold"><?= htmlspecialchars($obra['nome']) ?></p>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($obras)): ?>
                            <p class="text-gray-500 text-sm">Nenhuma obra encontrada</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>





        <script>
            let projetoArrastado = null;

            function drag(event) {
                projetoArrastado = event.target;
                event.dataTransfer.setData("text/plain", event.target.id);
            }

            function allowDrop(event) {
                event.preventDefault();
            }

            function drop(event) {
                event.preventDefault();
                const statusId = event.currentTarget.getAttribute('data-status-id');

                console.log(statusId)

                event.currentTarget.appendChild(projetoArrastado);

                const projetoId = projetoArrastado.id.replace('obra-', '');

                // Atualizar o status do projeto via AJAX
                fetch('./atualizar_status_obra.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            obra_id: projetoId,
                            status_id: statusId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.sucesso) {
                            alert('Erro ao atualizar o status do projeto.');
                        }
                    });
            }
        </script>

        <!-- Modal Novo Projeto -->
        <div id="modal-status" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white p-6 rounded shadow-lg">
                <h2 class="text-xl font-bold mb-4">Novo Status</h2>
                <form method="POST" action="./criar_quadro_obra.php">
                    <label class="block mb-2">Nome do Status</label>
                    <input type="text" name="nome" required class="border p-2 w-full mb-4">

                    <label class="block mb-2">Cor</label>
                    <input type="color" name="cor" value="#171717" class="border p-2 w-full mb-4">

                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="document.getElementById('modal-status').classList.add('hidden')" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded">Salvar</button>
                    </div>
                </form>
            </div>
        </div>



    </div>



    <style>
        .input {
            @apply w-full p-3 bg-dark border border-gray-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent;
        }
    </style>

</body>

</html>

<?php $conn->close(); ?>