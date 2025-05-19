<?php
session_start();

// Função para verificar se o usuário está autenticado
function verificarAutenticacao()
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: ../onboard/login.php");
        exit();
    }
}

verificarAutenticacao();

$conn = new mysqli('localhost', 'root', '', 'axel_db');
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Busca dados do usuário
$stmt = $conn->prepare("
   SELECT 
    u.id, u.email,  u.is_superuser,
    u.setor_id, u.empresa_id,
    COALESCE(s.nome, '') AS setor_nome,
    COALESCE(e.localizacao, '') AS empresa_nome
FROM users u
LEFT JOIN setores s ON u.setor_id = s.id
LEFT JOIN empresas e ON u.empresa_id = e.id
WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Usuário não encontrado.");
}

$usuario = $result->fetch_assoc();

$_SESSION['empresa_id'] = $usuario['empresa_id'];
$stmt->close();

$empresa_id = $_SESSION['empresa_id'];

$sc_id = 0;
if (isset($_GET['sc_id'])) {
    $sc_id = intval($_GET['sc_id']);

    // Recuperar dados da OS
    $stmt = $conn->prepare("SELECT * FROM ordem_de_servico WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $sc_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $os = $result->fetch_assoc();

    if ($os) {
        $obra_id = $os['obra_id'];

        $stmtObra = $conn->prepare("SELECT * FROM obras WHERE id = ? AND empresa_id = ?");
        $stmtObra->bind_param("ii", $obra_id, $empresa_id);
        $stmtObra->execute();
        $resultObra = $stmtObra->get_result();
        $obra = $resultObra->fetch_assoc();

        if ($obra) {
            $contrato_id = $obra['contrato_id'];

            $stmtContrato = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
            $stmtContrato->bind_param("ii", $contrato_id, $empresa_id);
            $stmtContrato->execute();
            $resultContrato = $stmtContrato->get_result();
            $contrato = $resultContrato->fetch_assoc();
        }
    }
}

// Buscar todos os relatórios que tenham observação e data, para a OS
$relatoriosObservacao = [];

if ($sc_id > 0) {
    $stmtRelatorios = $conn->prepare("
        SELECT observacao, criado_em 
        FROM relatorio_os 
        WHERE os_id = ? 
        ORDER BY criado_em DESC
    ");
    $stmtRelatorios->bind_param("i", $sc_id);
    $stmtRelatorios->execute();
    $resultRelatorios = $stmtRelatorios->get_result();

    while ($row = $resultRelatorios->fetch_assoc()) {
        // Só adiciona se tiver observação (não vazia)
        if (!empty(trim($row['observacao']))) {
            $relatoriosObservacao[] = $row;
        }
    }
    $stmtRelatorios->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ordem de Serviço - Detalhes</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"
    />

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <link rel="icon" type="image/png" href="../assets/logo/il_fullxfull.2974258879_pxm3.webp" />

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
                        primary: "#171717",
                    },
                },
            },
        };
    </script>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
    <!-- Side Menu -->
    <?php include '../../layout/sidemenu_in.php'; ?>
    

    <div class="w-full">
        <div class="relative p-8 animate-fadeIn">
            <header
                class="bg-white rounded-2xl shadow-lg p-6 mb-10 flex items-center justify-between"
            >
                <div class="flex items-center gap-4">
                    <button
                        onclick="window.location.href='../os'"
                        class="text-gray-600 hover:text-primary transition"
                    >
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-800">
                        Relatório Fotográfico
                    </h1>
                </div>
            </header>

            <!-- Aqui exibimos as observações e datas dos relatórios -->
            <?php if (!empty($relatoriosObservacao)) : ?>
                <section class="mb-10">
                    <h2 class="text-xl font-semibold mb-4">Observações dos Relatórios</h2>
                    <ul class="space-y-4">
                        <?php foreach ($relatoriosObservacao as $relatorio) : ?>
                            <li class="bg-white p-4 rounded shadow">
                                <p><?= nl2br(htmlspecialchars($relatorio['observacao'])) ?></p>
                                <small class="text-gray-500">
                                    Data: <?= date('d/m/Y H:i', strtotime($relatorio['criado_em'])) ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php else : ?>
                <p class="text-gray-600">Nenhuma observação de relatório encontrada para esta OS.</p>
            <?php endif; ?>

        </div>
    </div>

</body>

</html>
