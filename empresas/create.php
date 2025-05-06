<?php
session_start();


$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Função para salvar os anexos
function salvarAnexos($conn, $tabela_ref, $ref_id, $arquivos) {
    $pasta_base = "uploads/$tabela_ref/$ref_id/";
    if (!is_dir($pasta_base)) {
        mkdir($pasta_base, 0777, true);
    }

    $total = count($arquivos['name']);
    for ($i = 0; $i < $total; $i++) {
        $nome_original = basename($arquivos['name'][$i]);
        $tmp_name = $arquivos['tmp_name'][$i];

        if (!is_uploaded_file($tmp_name)) continue;

        $nome_seguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $nome_original);
        $caminho_final = $pasta_base . $nome_seguro;

        if (move_uploaded_file($tmp_name, $caminho_final)) {
            $stmt = $conn->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $tabela_ref, $ref_id, $nome_original, $caminho_final);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['criar'])) {
    $nome = trim($_POST['nome']);
    $cnpj = trim($_POST['cnpj']);
    $cidade = trim($_POST['cidade']);

    $razao_social = $nome;
    $telefone = null;
    $email = null;
    $localizacao = $cidade;

    $stmt = $conn->prepare("INSERT INTO empresas (cnpj, nome, razao_social, telefone, email, localizacao) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $cnpj, $nome, $razao_social, $telefone, $email, $localizacao);

    if ($stmt->execute()) {
        $empresa_id = $stmt->insert_id;

        if (!empty($_FILES['anexos']['name'][0])) {
            salvarAnexos($conn, 'empresas', $empresa_id, $_FILES['anexos']);
        }

        header("Location: index.php");
        exit;
    } else {
        die("Erro ao salvar empresa: " . $stmt->error);
    }
} else {
    header("Location: index.php");
    exit;
}
?>
