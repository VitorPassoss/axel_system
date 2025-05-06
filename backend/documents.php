<?php
if (!isset($_POST['tabela_ref']) || !isset($_POST['ref_id']) || !isset($_FILES['anexos'])) {
    die("Parâmetros ausentes.");
}

$tabela_ref = $_POST['tabela_ref'];
$ref_id = intval($_POST['ref_id']);
$arquivos = $_FILES['anexos'];

// Pasta onde os arquivos serão salvos (ex: uploads/empresas/5/)
$pasta_base = "uploads/$tabela_ref/$ref_id/";
if (!is_dir($pasta_base)) {
    mkdir($pasta_base, 0777, true); // Cria pasta recursivamente
}

// Conexão (caso o script seja usado isolado também)
if (!isset($conn)) {
    $host = 'localhost';
    $dbname = 'axel_db';
    $username = 'root';
    $password = '';
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }
}

$total = count($arquivos['name']);
for ($i = 0; $i < $total; $i++) {
    $nome_original = basename($arquivos['name'][$i]);
    $tmp_name = $arquivos['tmp_name'][$i];

    if (!is_uploaded_file($tmp_name)) continue;

    $nome_seguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $nome_original);
    $caminho_final = $pasta_base . $nome_seguro;

    if (move_uploaded_file($tmp_name, $caminho_final)) {
        // Inserir no banco
        $stmt = $conn->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome_arquivo, caminho_arquivo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $tabela_ref, $ref_id, $nome_original, $caminho_final);
        $stmt->execute();
        $stmt->close();
    }
}
?>
