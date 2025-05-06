<?php
$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco.']));
}

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $empresa_id = intval($_POST['empresa_id'] ?? 0);
    $nome = $_POST['nome'] ?? '';
    $cnpj = $_POST['cnpj'] ?? '';
    $cidade = $_POST['cidade'] ?? '';

    if ($empresa_id) {
        $stmt = $conn->prepare("UPDATE empresas SET nome = ?, cnpj = ?, localizacao = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nome, $cnpj, $cidade, $empresa_id);
        if ($stmt->execute()) {
            if (!empty($_FILES['anexos']['name'][0])) {
                salvarAnexos($conn, 'empresas', $empresa_id, $_FILES['anexos']);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID da empresa inválido.']);
    }
    exit;
}
