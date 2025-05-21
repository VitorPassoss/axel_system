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
    u.id, u.email, u.is_superuser,
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
$os = null;
$obra = null;
$contrato = null;
$registros = [];

if (isset($_GET['sc_id'])) {
    $sc_id = intval($_GET['sc_id']);

    // Recuperar dados da OS
    $stmt = $conn->prepare("SELECT * FROM ordem_de_servico WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $sc_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $os = $result->fetch_assoc();
    $stmt->close();

    if ($os) {
        $obra_id = $os['obra_id'];

        $stmtObra = $conn->prepare("SELECT * FROM obras WHERE id = ? AND empresa_id = ?");
        $stmtObra->bind_param("ii", $obra_id, $empresa_id);
        $stmtObra->execute();
        $resultObra = $stmtObra->get_result();
        $obra = $resultObra->fetch_assoc();
        $stmtObra->close();

        if ($obra) {
            $contrato_id = $obra['contrato_id'];

            $stmtContrato = $conn->prepare("SELECT * FROM contratos WHERE id = ? AND empresa_id = ?");
            $stmtContrato->bind_param("ii", $contrato_id, $empresa_id);
            $stmtContrato->execute();
            $resultContrato = $stmtContrato->get_result();
            $contrato = $resultContrato->fetch_assoc();
            $stmtContrato->close();
        }

        // Buscar todos os registros com observação e data
        $stmtRelatorios = $conn->prepare("
            SELECT id, imagem, observacao, data, momento
            FROM registro_os
            WHERE os_id = ? AND observacao IS NOT NULL AND data IS NOT NULL
            ORDER BY data DESC
        ");
        $stmtRelatorios->bind_param("i", $sc_id);
        $stmtRelatorios->execute();
        $resultRelatorios = $stmtRelatorios->get_result();

        while ($row = $resultRelatorios->fetch_assoc()) {
            $registros[] = $row;
        }

        $stmtRelatorios->close();
    }
}


// Deletar imagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);
    if (isset($_POST['sc_id'])) {
        $sc_id = intval($_POST['sc_id']);
    }

    // Buscar nome da imagem
    $stmtSelect = $conn->prepare("SELECT imagem FROM registro_os WHERE id = ?");
    $stmtSelect->bind_param("i", $deleteId);
    $stmtSelect->execute();
    $stmtSelect->bind_result($imagemNome);
    $stmtSelect->fetch();
    $stmtSelect->close();

    // Excluir imagem
    if (!empty($imagemNome) && file_exists("uploads/$imagemNome")) {
        unlink("uploads/$imagemNome");
    }

    // Excluir do banco
    $stmtDelete = $conn->prepare("DELETE FROM registro_os WHERE id = ?");
    $stmtDelete->bind_param("i", $deleteId);
    $stmtDelete->execute();
    $stmtDelete->close();

    header("Location: ?sc_id=$sc_id&deleted=1");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $sc_id = intval($_POST['sc_id']);
    $observacao = trim($_POST['observacao']);
    $momento = $_POST['momento'];
    $dataAtual = date('Y-m-d H:i:s');

    // Verifica se o arquivo foi enviado sem erros
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $imagemTmp = $_FILES['imagem']['tmp_name'];
        $imagemNome = basename($_FILES['imagem']['name']);
        $uploadDir = 'uploads/';
        $imagemPath = $uploadDir . $imagemNome;

        // Garante nome único
        $imagemNomeFinal = uniqid() . '_' . $imagemNome;
        $imagemPathFinal = $uploadDir . $imagemNomeFinal;

        if (move_uploaded_file($imagemTmp, $imagemPathFinal)) {
            // Inserir no banco
            $stmtInsert = $conn->prepare("INSERT INTO registro_os (os_id, imagem, observacao, data, momento) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->bind_param("issss", $sc_id, $imagemNomeFinal, $observacao, $dataAtual, $momento);
            $stmtInsert->execute();
            $stmtInsert->close();

            header("Location: ?sc_id=$sc_id&sucesso=1");
            exit();
        } else {
            echo "<script>alert('Erro ao mover a imagem.');</script>";
        }
    } else {
        echo "<script>alert('Erro no upload da imagem.');</script>";
    }
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
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" />

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
            <header class="bg-white rounded-2xl shadow-lg p-4 sm:p-6 mb-6 sm:mb-10 flex flex-wrap sm:flex-nowrap items-center justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4 flex-wrap">
                    <button
                        onclick="window.location.href='../../os'"
                        class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-arrow-left text-lg sm:text-xl"></i>
                    </button>
                    <h1 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-800">

                        Diário de Obra
                    </h1>
                </div>
            </header>


            <!-- Formulário -->
            <form method="POST" enctype="multipart/form-data"
                class="mb-10 bg-white p-6 rounded-2xl shadow-md  w-full mx-auto space-y-5 px-4 sm:px-6 lg:px-8">
                <input type="hidden" name="sc_id" value="<?= $sc_id ?>" />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 ">Imagem:</label>
                    <input type="file" name="imagem" accept="image/*" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent max-w-[450px]" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observação:</label>
                    <textarea name="observacao" rows="4" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent max-w-[450px]"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Momento da foto:</label>
                    <select name="momento" required
                        class="w-full max-w-[450px] border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="antes">Antes da realização</option>
                        <option value="depois">Depois da realização</option>
                    </select>
                </div>

                <button type="submit" name="enviar"
                    class="bg-primary hover:bg-black transition-colors duration-300 text-white font-semibold px-5 py-2.5 rounded-lg w-full sm:w-auto">
                    Salvar Registro
                </button>
            </form>

            <!-- Modal de Zoom -->
            <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
                <img id="modalImage" class="max-w-full max-h-full rounded-lg" />
            </div>

            <!-- Grid de cards -->
            <ul class="grid gap-6 px-4 sm:px-6 lg:px-8 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <?php foreach ($registros as $registro) : ?>
                    <li class="bg-white rounded-2xl shadow-md overflow-hidden flex flex-col relative group">

                        <!-- Imagem com zoom -->
                        <div class="w-full h-[250px] overflow-hidden">
                            <img src="uploads/<?= htmlspecialchars($registro['imagem']) ?>"
                                alt="Registro"
                                class="w-full h-full object-cover cursor-zoom-in zoomable transition duration-300 group-hover:opacity-90" />
                        </div>

                        <!-- Conteúdo -->
                        <div class="p-4 flex flex-col flex-1">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-bold
                    <?= $registro['momento'] === 'antes' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                <?= ucfirst($registro['momento']) ?>
                            </span>

                            <p class="mt-2 flex-1 text-gray-700 text-sm leading-relaxed overflow-hidden">
                                <?= nl2br(htmlspecialchars($registro['observacao'])) ?>
                            </p>

                            <small class="text-gray-400 mt-3 block text-xs">
                                Data: <?= date('d/m/Y H:i', strtotime($registro['data'])) ?>
                            </small>
                        </div>

                        <!-- Botão Excluir -->
                        <form method="POST" class="absolute top-2 right-2">
                            <input type="hidden" name="delete_id" value="<?= $registro['id'] ?>">
                            <button type="submit"
                                class="text-red-500 bg-white border border-red-200 rounded-full w-8 h-8 text-xs font-bold hover:bg-red-100 transition"
                                title="Excluir"
                                onclick="return confirm('Deseja realmente excluir esta imagem?')">
                                ✕
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>


            <script>
                // Zoom ao clicar na imagem
                document.addEventListener("DOMContentLoaded", () => {
                    const modal = document.getElementById("imageModal");
                    const modalImage = document.getElementById("modalImage");

                    document.querySelectorAll(".zoomable").forEach(img => {
                        img.addEventListener("click", () => {
                            modalImage.src = img.src;
                            modal.classList.remove("hidden");
                        });
                    });

                    modal.addEventListener("click", () => modal.classList.add("hidden"));
                });
            </script>

        </div>
    </div>

</body>

</html>