<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $documento_id = intval($_GET['id']);
    $empresa_id = $_SESSION['empresa_id'];

    // Verificar se o anexo pertence a uma licitação da empresa
    $sql = "
        SELECT d.id, d.caminho_arquivo
        FROM documentos d
        JOIN licitacoes l ON l.id = d.ref_id
        WHERE d.id = ? AND d.tabela_ref = 'licitacoes' AND l.empresa_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $documento_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo "Anexo não encontrado ou acesso negado.";
        exit;
    }

    $documento = $result->fetch_assoc();
    $caminho = $documento['caminho_arquivo'];

    // Tentar excluir o arquivo
    if (file_exists($caminho)) {
        unlink($caminho);
    }

    // Excluir do banco
    $delete = $conn->prepare("DELETE FROM documentos WHERE id = ?");
    $delete->bind_param("i", $documento_id);

    if ($delete->execute()) {
        echo "Anexo excluído com sucesso.";
    } else {
        http_response_code(500);
        echo "Erro ao excluir do banco: " . $delete->error;
    }

    $delete->close();
    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo "Método não permitido ou ID não informado.";
}
