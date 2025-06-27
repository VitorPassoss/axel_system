<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

// Valida o ID recebido pela URL
$fornecedor_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$fornecedor_id) {
    header("Location: ./index.php?status=error&msg=ID inválido");
    exit();
}

// Lista de campos editáveis
$campos = [
    'razao_social', 'nome_fantasia', 'cnpj', 'inscricao_estadual', 'inscricao_municipal',
    'email', 'telefone', 'celular', 'site', 'contato_responsavel',
    'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep',
    'observacoes', 'ativo', 'empresa_id'
];

// --- PROCESSAMENTO DO FORMULÁRIO DE ATUALIZAÇÃO ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dados = [];
    foreach ($campos as $campo) {
        $dados[$campo] = trim($_POST[$campo] ?? '');
    }

    // Validação pode ser adicionada aqui se necessário

    try {
        $conn->begin_transaction();

        // Construção da query de UPDATE
        $set_parts = [];
        foreach ($campos as $campo) {
            $set_parts[] = "$campo = ?";
        }
        $sql_update = "UPDATE fornecedores SET " . implode(', ', $set_parts) . " WHERE id = ?";
        
        $stmt_update = $conn->prepare($sql_update);

        // Bind dos parâmetros
        $types = str_repeat('s', count($campos) - 2) . 'ii'; // Ajuste os tipos conforme os últimos campos
        $params_to_bind = array_values($dados);
        $params_to_bind[] = $fornecedor_id; // Adiciona o ID no final para o WHERE
        $stmt_update->bind_param($types . 'i', ...$params_to_bind);

        if (!$stmt_update->execute()) {
            throw new Exception("Erro ao atualizar o fornecedor: " . $stmt_update->error);
        }
        $stmt_update->close();

        // Salva novos anexos, se houver
        if (isset($_FILES['novos_anexos']) && $_FILES['novos_anexos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            salvarAnexos($conn, 'fornecedores', $fornecedor_id, $_FILES['novos_anexos']);
        }

        $conn->commit();
        header("Location: ./detalhes.php?id=" . $fornecedor_id . "&status=update_success");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // Você pode redirecionar com uma mensagem de erro
        header("Location: ./detalhes.php?id=" . $fornecedor_id . "&status=update_error&msg=" . urlencode($e->getMessage()));
        exit();
    }
}


// --- LÓGICA PARA BUSCAR DADOS (GET) ---

// Função para salvar anexos
function salvarAnexos(mysqli $mysqli, $tabela_ref, $ref_id, $arquivos) {
    $pasta_base = __DIR__ . "/../uploads/$tabela_ref/$ref_id/";
    if (!is_dir($pasta_base)) { mkdir($pasta_base, 0775, true); }
    foreach ($arquivos['name'] as $i => $name) {
        if ($arquivos['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $arquivos['tmp_name'][$i];
            $nome_original = basename($name);
            $nome_seguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $nome_original);
            $caminho_final = $pasta_base . $nome_seguro;
            if (move_uploaded_file($tmp_name, $caminho_final)) {
                $caminho_db = "uploads/$tabela_ref/$ref_id/" . $nome_seguro;
                $stmt = $mysqli->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siss", $tabela_ref, $ref_id, $nome_original, $caminho_db);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// 1. BUSCAR DADOS DO FORNECEDOR
$sql_fornecedor = "SELECT f.*, e.nome AS nome_empresa FROM fornecedores f LEFT JOIN empresas e ON f.empresa_id = e.id WHERE f.id = ?";
$stmt_get = $conn->prepare($sql_fornecedor);
$stmt_get->bind_param("i", $fornecedor_id);
$stmt_get->execute();
$fornecedor = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$fornecedor) {
    header("Location: ./index.php?status=error&msg=Fornecedor não encontrado");
    exit();
}

// 2. BUSCAR DOCUMENTOS ASSOCIADOS
$sql_documentos = "SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'fornecedores' AND ref_id = ? ORDER BY id DESC";
$stmt_docs = $conn->prepare($sql_documentos);
$stmt_docs->bind_param("i", $fornecedor_id);
$stmt_docs->execute();
$documentos = $stmt_docs->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_docs->close();

// 3. BUSCAR EMPRESAS PARA DROPDOWN
$empresas = $conn->query("SELECT id, nome FROM empresas ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);


// --- FUNÇÕES HELPER PARA RENDERIZAR FORMULÁRIO ---
function input($label, $name, $type, $required, $value, $placeholder = '') {
    $req_html = $required ? 'required' : '';
    $req_span = $required ? " <span class='text-red-500'>*</span>" : "";
    echo "<div><label for='{$name}' class='block text-sm font-medium text-gray-700 mb-1'>{$label}{$req_span}</label><input type='{$type}' id='{$name}' name='{$name}' value='" . htmlspecialchars($value) . "' placeholder='{$placeholder}' class='p-2 border border-gray-300 rounded-lg w-full transition focus:ring-2 focus:ring-blue-500' {$req_html}></div>";
}
function textarea($label, $name, $value, $placeholder = '') {
    echo "<div><label for='{$name}' class='block text-sm font-medium text-gray-700 mb-1'>{$label}</label><textarea id='{$name}' name='{$name}' placeholder='{$placeholder}' rows='4' class='p-2 border border-gray-300 rounded-lg w-full transition focus:ring-2 focus:ring-blue-500'>" . htmlspecialchars($value) . "</textarea></div>";
}
function select_assoc($label, $name, $options, $required, $selectedValue) {
    $req_html = $required ? 'required' : '';
    $req_span = $required ? " <span class='text-red-500'>*</span>" : "";
    $html = "<div><label for='{$name}' class='block text-sm font-medium text-gray-700 mb-1'>{$label}{$req_span}</label><select id='{$name}' name='{$name}' class='p-2 border border-gray-300 rounded-lg w-full transition focus:ring-2 focus:ring-blue-500' {$req_html}>";
    foreach ($options as $value => $text) {
        $selected = ((string)$value === (string)$selectedValue) ? 'selected' : '';
        $html .= "<option value='" . htmlspecialchars($value) . "' {$selected}>" . htmlspecialchars($text) . "</option>";
    }
    $html .= "</select></div>";
    echo $html;
}
function select_dinamico($label, $name, $options, $required, $selectedValue) {
    $req_html = $required ? 'required' : '';
    $req_span = $required ? " <span class='text-red-500'>*</span>" : "";
    $html = "<div><label for='{$name}' class='block text-sm font-medium text-gray-700 mb-1'>{$label}{$req_span}</label><select id='{$name}' name='{$name}' class='p-2 border border-gray-300 rounded-lg w-full transition focus:ring-2 focus:ring-blue-500' {$req_html}>";
    foreach ($options as $option) {
        $selected = ($option['id'] == $selectedValue) ? 'selected' : '';
        $html .= "<option value='" . htmlspecialchars($option['id']) . "' {$selected}>" . htmlspecialchars($option['nome']) . "</option>";
    }
    $html .= "</select></div>";
    echo $html;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Fornecedor - <?php echo htmlspecialchars($fornecedor['razao_social']); ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, button, input, select, textarea { font-family: 'Poppins', sans-serif; }
        .tab-button.active { border-color: #3B82F6; color: #3B82F6; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>

    <main class="flex-1 p-6 lg:p-8 space-y-6">
        <form action="./detalhes.php?id=<?php echo $fornecedor_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Editar Fornecedor</h1>
                    <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($fornecedor['razao_social']); ?></p>
                </div>
                <div class="flex gap-3">
                    <a href="./index.php" class="bg-white border border-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-left fa-fw mr-2"></i>Voltar
                    </a>
                    <button type="submit" class="bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-sm hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save fa-fw mr-2"></i>Salvar Alterações
                    </button>
                </div>
            </div>

            <div class="bg-white p-2 mt-6 rounded-lg shadow-sm">
                <nav class="flex space-x-2" aria-label="Tabs">
                    <button type="button" class="tab-button active" data-tab="geral">Dados Gerais</button>
                    <button type="button" class="tab-button" data-tab="contato">Contato e Endereço</button>
                    <button type="button" class="tab-button" data-tab="documentos">Documentos</button>
                </nav>
            </div>

            <div class="bg-white p-6 mt-2 rounded-lg shadow-sm">
                <div id="geral" class="tab-content active">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php input('Razão Social', 'razao_social', 'text', true, $fornecedor['razao_social']); ?>
                        <?php input('Nome Fantasia', 'nome_fantasia', 'text', false, $fornecedor['nome_fantasia']); ?>
                        <?php input('CNPJ', 'cnpj', 'text', true, $fornecedor['cnpj']); ?>
                        <?php input('Inscrição Estadual', 'inscricao_estadual', 'text', false, $fornecedor['inscricao_estadual']); ?>
                        <?php input('Inscrição Municipal', 'inscricao_municipal', 'text', false, $fornecedor['inscricao_municipal']); ?>
                        <?php input('Site', 'site', 'url', false, $fornecedor['site']); ?>
                        <?php select_dinamico('Empresa Vinculada', 'empresa_id', $empresas, true, $fornecedor['empresa_id']); ?>
                        <?php select_assoc('Status', 'ativo', ['1' => 'Ativo', '0' => 'Inativo'], true, $fornecedor['ativo']); ?>
                        <div class="md:col-span-2 lg:col-span-3">
                            <?php textarea('Observações', 'observacoes', $fornecedor['observacoes']); ?>
                        </div>
                    </div>
                </div>

                <div id="contato" class="tab-content">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-800">Contato</h3>
                            <?php input('Contato Responsável', 'contato_responsavel', 'text', true, $fornecedor['contato_responsavel']); ?>
                            <?php input('E-mail', 'email', 'email', true, $fornecedor['email']); ?>
                            <?php input('Telefone Fixo', 'telefone', 'tel', false, $fornecedor['telefone']); ?>
                            <?php input('Celular / WhatsApp', 'celular', 'tel', true, $fornecedor['celular']); ?>
                        </div>
                        <div class="space-y-6">
                             <h3 class="text-lg font-semibold text-gray-800">Endereço</h3>
                            <?php input('CEP', 'cep', 'text', true, $fornecedor['cep']); ?>
                            <?php input('Endereço', 'endereco', 'text', true, $fornecedor['endereco']); ?>
                             <div class="grid grid-cols-2 gap-4">
                                <?php input('Número', 'numero', 'text', true, $fornecedor['numero']); ?>
                                <?php input('Complemento', 'complemento', 'text', false, $fornecedor['complemento']); ?>
                            </div>
                            <?php input('Bairro', 'bairro', 'text', true, $fornecedor['bairro']); ?>
                            <div class="grid grid-cols-2 gap-4">
                                <?php input('Cidade', 'cidade', 'text', true, $fornecedor['cidade']); ?>
                                <?php input('Estado', 'estado', 'text', true, $fornecedor['estado']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="documentos" class="tab-content">
                     <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Documentos Anexados</h3>
                            <?php if (empty($documentos)): ?>
                                <p class="text-gray-500">Nenhum documento anexado.</p>
                            <?php else: ?>
                                <ul class="space-y-3">
                                    <?php foreach ($documentos as $doc): ?>
                                    <li class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <i class="fas fa-file-alt text-gray-500 flex-shrink-0"></i>
                                            <span class="text-sm font-medium text-gray-800 truncate"><?php echo htmlspecialchars($doc['nome']); ?></span>
                                        </div>
                                        <div class="flex items-center gap-3 flex-shrink-0">
                                            <a href="../<?php echo htmlspecialchars($doc['caminho_arquivo']); ?>" download class="text-blue-600 hover:text-blue-800" title="Baixar"><i class="fas fa-download"></i></a>
                                            <a href="./delete_doc.php?id=<?php echo $doc['id']; ?>&fornecedor_id=<?php echo $fornecedor_id; ?>" onclick="return confirm('Tem certeza que deseja excluir este documento?');" class="text-red-500 hover:text-red-700" title="Excluir"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border h-fit">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Anexar Novo Documento</h3>
                            <label for="novos_anexos" class="block text-sm font-medium text-gray-700">Selecione os arquivos</label>
                            <input type="file" name="novos_anexos[]" multiple class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                             <p class="text-xs text-gray-500 mt-2">Você pode selecionar múltiplos arquivos. Eles serão salvos quando você clicar em "Salvar Alterações".</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            // Use 'button' type to prevent form submission
            tab.setAttribute('type', 'button'); 
            tab.addEventListener('click', () => {
                tabs.forEach(item => item.classList.remove('active', 'font-semibold', 'border-blue-600', 'text-blue-600'));
                tabs.forEach(item => item.classList.add('font-medium', 'text-gray-500', 'border-transparent'));
                
                tabContents.forEach(content => content.classList.remove('active'));

                tab.classList.add('active', 'font-semibold', 'border-blue-600', 'text-blue-600');
                tab.classList.remove('font-medium', 'text-gray-500', 'border-transparent');
                
                document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });

        // Estilo para os botões de aba
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.add('px-4', 'py-2', 'rounded-md', 'text-sm', 'border-b-2', 'transition-colors');
            if(!button.classList.contains('active')){
                button.classList.add('font-medium', 'text-gray-500', 'border-transparent');
            } else {
                 button.classList.add('font-semibold', 'border-blue-600', 'text-blue-600');
            }
        });
    });
    </script>
</body>
</html>
<?php
$conn->close();
?>