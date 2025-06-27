<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_relatorio'])) {
    include '../backend/dbconn.php';

    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }

    $observacao = trim($_POST['observacao'] ?? '');
    $imagemPath = null;

    // Se uma imagem foi enviada
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destPath)) {
            $imagemPath = $destPath;
        }
    }

    if (!empty($observacao) || $imagemPath) {
        $stmt = $conn->prepare("
            INSERT INTO relatorio_os (os_id, observacao, imagem, criado_em) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iss", $sc_id, $observacao, $imagemPath);
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();
    header("Location: " . $_SERVER['REQUEST_URI']); // Evita reenvio em refresh
    exit();
}

exit();
