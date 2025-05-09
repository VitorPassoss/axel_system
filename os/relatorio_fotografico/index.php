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

// Chama a função automaticamente
verificarAutenticacao();

// Conexão com o banco
$conn = new mysqli('localhost', 'root', '', 'axel_db');
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
// Armazenar o empresa_id na sessão


// Busca os dados completos do usuário logado
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
$conn->close();




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


?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ordem de Serviço - Detalhes</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"><!-- Font Awesome via CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Toastify JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <link rel="icon" type="image/png" href="../assets/logo/il_fullxfull.2974258879_pxm3.webp">


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
    <?php include '../../layout/sidemenu_in.php'; ?>
    <div class="w-full ">
        <div class="relative  p-8 animate-fadeIn">




            <header class="bg-white rounded-2xl shadow-lg p-6 mb-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="window.location.href='../os'" class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-800">Relatório Fotográfico</h1>

                </div>
         

            </header>





        </div>
    </div>






</body>