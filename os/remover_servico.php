<?php
include '../backend/auth.php'; // A autenticação do usuário

// Verifica se o servico_id foi enviado
if (isset($_POST['servico_id'])) {
    $servico_id = $_POST['servico_id'];

    // Verifica se o ID do serviço foi enviado corretamente
    if (empty($servico_id)) {
        echo json_encode(['success' => false, 'error' => 'ID do serviço não pode estar vazio']);
        exit;
    }

    // Conexão com o banco de dados
    $host = 'localhost';
    $dbname = 'axel_db';
    $username = 'root';
    $password = '';
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }

    // Remove o serviço da tabela servicos_os
    $stmt = $conn->prepare("DELETE FROM servicos_os WHERE id = ?");
    $stmt->bind_param("i", $servico_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao remover o serviço']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'error' => 'ID do serviço não informado']);
}
?>
