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

// Recuperar status e armazenar em arrays reutilizáveis
$status_sql = "SELECT * FROM status ORDER BY id";
$status_result = $conn->query($status_sql);

$status_lista = [];
$status_nomes = [];
$status_cores = [];

while ($s = $status_result->fetch_assoc()) {
    $status_lista[] = $s;
    $status_nomes[$s['id']] = $s['nome'];
    $status_cores[$s['id']] = $s['cor'];
}

// Recuperar projetos da empresa específica
$projetos_sql = "SELECT p.*, s.nome AS status_nome 
                 FROM projetos p 
                 LEFT JOIN status s ON p.status_fk = s.id 
                 WHERE p.empresa_id = ?";

$stmt = $conn->prepare($projetos_sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$projetos_result = $stmt->get_result();

// Organizar projetos por status
$projetos_por_status = [];
while ($projeto = $projetos_result->fetch_assoc()) {
    $projetos_por_status[$projeto['status_fk']][] = $projeto;
}

$total_projetos = 0;
$contagem_status = [];
$projetos_contrato = [];

foreach ($projetos_por_status as $status_id => $projetos) {
    $contagem_status[$status_id] = count($projetos);
    $total_projetos += count($projetos);

    foreach ($projetos as $proj) {
        $tipo_contrato = $proj['tipo_contrato'] ?? 'Não informado';
        $projetos_contrato[$tipo_contrato] = ($projetos_contrato[$tipo_contrato] ?? 0) + 1;
    }
}

// Debug: Verifique se os projetos estão sendo organizados corretamente
// var_dump($projetos_por_status);
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
                <button onclick="window.location.href='../projetos'" class="text-gray-600 hover:text-primary transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </button>
                <h1 class="text-3xl font-bold text-primary">Quadro de Projetos</h1>
            </div>

            <button onclick="document.getElementById('modal-status').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded">
                + Novo Quadro
            </button>

        </div>



        <!-- Tabela -->
        <!-- Quadro Kanban de Projetos -->
        <div class="flex space-x-4 overflow-x-auto px-4 py-2 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
            <?php foreach ($status_lista as $status): ?>
                <div class="w-[25%] bg-white rounded-lg shadow p-4 flex-shrink-0" style="background: <?= htmlspecialchars($status['cor']) ?>;">
                    <h2 class="text-lg font-bold mb-4">
                        <?= htmlspecialchars($status['nome']) ?>
                    </h2>
                    <div class="space-y-2 min-h-[100px]" data-status-id="<?= $status['id'] ?>" ondrop="drop(event)" ondragover="allowDrop(event)">
                        <?php
                        $projetos = $projetos_por_status[$status['id']] ?? [];
                        foreach ($projetos as $projeto):
                        ?>
                            <div class="bg-gray-100 p-3 rounded shadow cursor-pointer" draggable="true" ondragstart="drag(event)" id="projeto-<?= $projeto['id'] ?>" ondblclick="window.location.href='../projetos/detalhes.php?projeto_id=<?= $projeto['id'] ?>'">
                                <p class="font-semibold"><?= htmlspecialchars($projeto['nome']) ?></p>
                                <p class="text-sm text-gray-600">Responsável: <?= htmlspecialchars($projeto['responsavel']) ?></p>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($projetos)): ?>
                            <p class="text-gray-500 text-sm">Nenhum projeto encontrado</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>





        <script>
            let obraArrastada = null;

            function drag(event) {
                obraArrastada = event.target;
                event.dataTransfer.setData("text/plain", event.target.id);
            }

            function allowDrop(event) {
                event.preventDefault();
            }

            function drop(event) {
                event.preventDefault();
                const statusId = event.currentTarget.getAttribute('data-status-id');
                event.currentTarget.appendChild(obraArrastada);

                const obraId = obraArrastada.id.replace('projeto-', '');


                fetch('./atualizar_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            projeto_id: obraId,
                            status_id: statusId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.sucesso) {
                            alert('Erro ao atualizar o status da obra.');
                        }
                    });
            }
        </script>


        <!-- Modal Novo Projeto -->
        <div id="modal-status" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white p-6 rounded shadow-lg">
                <h2 class="text-xl font-bold mb-4">Novo Status</h2>
                <form method="POST" action="./criar_quadro.php">
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