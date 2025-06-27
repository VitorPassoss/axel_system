<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

$erros = [];
$profissional = [];
$id_profissional = 0;

// Lista de todos os campos do formulário/banco
$campos = [
    'nome', 'data_nascimento', 'genero', 'estado_civil', 'nacionalidade', 'naturalidade', 'cpf', 'rg', 'orgao_emissor', 'data_emissao',
    'titulo_eleitor', 'ctps_numero', 'ctps_serie', 'pis_pasep', 'reservista', 'cnh', 'cnh_categoria', 'validade_cnh', 'telefone', 'celular',
    'email', 'cep', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'banco', 'tipo_conta', 'agencia', 'conta', 'pix_chave',
    'pix_tipo', 'cargo', 'departamento', 'tipo_contrato', 'salario', 'data_admissao', 'jornada_trabalho', 'data_termino_contrato', 'beneficios',
    'contato_emergencia_nome', 'contato_emergencia_parentesco', 'contato_emergencia_telefone', 'possui_deficiencia', 'tipo_deficiencia',
    'observacoes', 'status'
];

$campos_obrigatorios = ['nome', 'data_nascimento', 'cpf', 'genero', 'estado_civil'];

// ETAPA 1: BUSCAR DADOS DO PROFISSIONAL (GET)
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['id'])) {
    $id_profissional = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    if ($id_profissional > 0) {
        $stmt = $conn->prepare("SELECT * FROM profissionais WHERE id = ?");
        $stmt->bind_param("i", $id_profissional);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $profissional = $result->fetch_assoc();
        } else {
            $erros[] = "Profissional não encontrado.";
        }
        $stmt->close();
    } else {
        $erros[] = "ID do profissional é inválido.";
    }
}

// Função para salvar anexos
function salvarAnexos(mysqli $mysqli, $tabela_ref, $ref_id, $arquivos)
{
    $pasta_base = __DIR__ . "/uploads/$tabela_ref/$ref_id/";
    if (!is_dir($pasta_base)) {
        mkdir($pasta_base, 0777, true);
    }

    $total = count($arquivos['name']);
    for ($i = 0; $i < $total; $i++) {
        if ($arquivos['error'][$i] !== UPLOAD_ERR_OK || !is_uploaded_file($arquivos['tmp_name'][$i])) {
            continue;
        }

        $nome_original = basename($arquivos['name'][$i]);
        $tmp_name = $arquivos['tmp_name'][$i];
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

// ETAPA 2: ATUALIZAR OS DADOS (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dados = [];
    $id_profissional = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

    foreach ($campos as $campo) {
        $valor = $_POST[$campo] ?? '';
        $dados[$campo] = trim($valor);
        if (in_array($campo, $campos_obrigatorios) && empty($valor)) {
            $erros[] = "O campo <strong>$campo</strong> é obrigatório.";
        }
    }

    if (empty($erros)) {
        $update_fields = [];
        foreach ($campos as $campo) {
            $update_fields[] = "$campo = ?";
        }
        $sql = "UPDATE profissionais SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);

        $types = str_repeat('s', count($campos)) . 'i';
        $values = array_values($dados);
        $values[] = $id_profissional;

        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            // Salva os anexos após atualizar o profissional
            if (isset($_FILES['anexos']) && count($_FILES['anexos']['name']) > 0 && $_FILES['anexos']['error'][0] == 0) {
                salvarAnexos($conn, 'profissionais', $id_profissional, $_FILES['anexos']);
            }
            header("Location: ./"); // Redireciona para a lista
            exit();
        } else {
            $erros[] = "Erro ao atualizar o profissional: " . $stmt->error;
        }
        $stmt->close();
    }

    $profissional = $dados;
    $profissional['id'] = $id_profissional;
}

// Funções de formulário (não precisam estar dentro do form)
function input($label, $name, $type = 'text', $value = '', $required = false)
{
    $value_attr = htmlspecialchars($value ?? '');
    $req_attr = $required ? 'required' : '';
    $label_req = $required ? '<span class="text-red-500">*</span>' : '';
    echo "<div><label for='{$name}' class='block text-sm font-medium text-gray-700 mb-1'>{$label} {$label_req}</label><input type='{$type}' id='{$name}' name='{$name}' value='{$value_attr}' class='p-2 bg-gray-50 border border-gray-300 rounded-lg w-full focus:ring-blue-500 focus:border-blue-500' {$req_attr}></div>";
}

function select($label, $name, $options, $selectedValue = '', $required = false)
{
    $req_attr = $required ? 'required' : '';
    $label_req = $required ? '<span class="text-red-500">*</span>' : '';
    echo "<div><label for='{$name}' class='block text-sm font-medium text-gray-700 mb-1'>{$label} {$label_req}</label><select id='{$name}' name='{$name}' class='p-2 bg-gray-50 border border-gray-300 rounded-lg w-full focus:ring-blue-500 focus:border-blue-500' {$req_attr}>";
    foreach ($options as $op_val => $op_text) {
        $selected_attr = ($op_val == ($selectedValue ?? '')) ? 'selected' : '';
        echo "<option value='{$op_val}' {$selected_attr}>{$op_text}</option>";
    }
    echo "</select></div>";
}

function textarea($label, $name, $value = '')
{
    $content = htmlspecialchars($value ?? '');
    echo "<div><label for='{$name}' class='block text-sm font-medium text-gray-700 mb-1'>{$label}</label><textarea id='{$name}' name='{$name}' rows='3' class='p-2 bg-gray-50 border border-gray-300 rounded-lg w-full focus:ring-blue-500 focus:border-blue-500'>{$content}</textarea></div>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Editar Profissional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Poppins', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>

    <div class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center bg-white py-4 px-6 rounded-2xl shadow-sm">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Editar Profissional</h1>
                <p class="text-sm text-gray-500">Atualize as informações do profissional.</p>
            </div>
        </div>

        <?php if (!empty($erros)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
                <p class="font-bold">Erro!</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <?php foreach ($erros as $erro): ?>
                        <li><?= $erro ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($profissional)): ?>
            <form method="POST" action="" class="bg-white p-6 rounded-2xl shadow-sm space-y-8" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id_profissional) ?>" />

                <div class="border-b border-gray-200">
                    <nav id="tab-nav" class="-mb-px flex flex-wrap space-x-6" aria-label="Tabs">
                        <a href="#pessoal" class="tab-link whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">Dados Pessoais</a>
                        <a href="#contato" class="tab-link whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">Contato e Endereço</a>
                        <a href="#financeiro" class="tab-link whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">Financeiro e Contrato</a>
                        <a href="#adicional" class="tab-link whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">Informações Adicionais</a>
                        <a href="#documentos" class="tab-link whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">Documentos</a>
                    </nav>
                </div>

                <div id="pessoal" class="tab-content active">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Dados Pessoais e Documentos</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        input('Nome Completo', 'nome', 'text', $profissional['nome'], true);
                        input('Data de Nascimento', 'data_nascimento', 'date', $profissional['data_nascimento'], true);
                        select('Gênero', 'genero', ['Masculino' => 'Masculino', 'Feminino' => 'Feminino', 'Outro' => 'Outro'], $profissional['genero'], true);
                        select('Estado Civil', 'estado_civil', ['Solteiro' => 'Solteiro', 'Casado' => 'Casado', 'Divorciado' => 'Divorciado', 'Viúvo' => 'Viúvo', 'União Estável' => 'União Estável'], $profissional['estado_civil'], true);
                        input('Nacionalidade', 'nacionalidade', 'text', $profissional['nacionalidade']);
                        input('Naturalidade', 'naturalidade', 'text', $profissional['naturalidade']);
                        input('CPF', 'cpf', 'text', $profissional['cpf'], true);
                        input('RG', 'rg', 'text', $profissional['rg']);
                        input('Órgão Emissor', 'orgao_emissor', 'text', $profissional['orgao_emissor']);
                        input('Data de Emissão', 'data_emissao', 'date', $profissional['data_emissao']);
                        input('Título de Eleitor', 'titulo_eleitor', 'text', $profissional['titulo_eleitor']);
                        input('CTPS Número', 'ctps_numero', 'text', $profissional['ctps_numero']);
                        input('CTPS Série', 'ctps_serie', 'text', $profissional['ctps_serie']);
                        input('PIS/PASEP', 'pis_pasep', 'text', $profissional['pis_pasep']);
                        input('Reservista', 'reservista', 'text', $profissional['reservista']);
                        input('CNH', 'cnh', 'text', $profissional['cnh']);
                        input('Categoria CNH', 'cnh_categoria', 'text', $profissional['cnh_categoria']);
                        input('Validade CNH', 'validade_cnh', 'date', $profissional['validade_cnh']);
                        ?>
                    </div>
                </div>

                <div id="contato" class="tab-content">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Contato e Endereço</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        input('Telefone', 'telefone', 'text', $profissional['telefone']);
                        input('Celular', 'celular', 'text', $profissional['celular']);
                        input('Email', 'email', 'email', $profissional['email']);
                        input('CEP', 'cep', 'text', $profissional['cep']);
                        input('Endereço', 'endereco', 'text', $profissional['endereco']);
                        input('Número', 'numero', 'text', $profissional['numero']);
                        input('Complemento', 'complemento', 'text', $profissional['complemento']);
                        input('Bairro', 'bairro', 'text', $profissional['bairro']);
                        input('Cidade', 'cidade', 'text', $profissional['cidade']);
                        input('Estado', 'estado', 'text', $profissional['estado']);
                        ?>
                    </div>
                </div>

                <div id="financeiro" class="tab-content">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Dados Financeiros e Contratuais</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        input('Banco', 'banco', 'text', $profissional['banco']);
                        select('Tipo de Conta', 'tipo_conta', ['Corrente' => 'Corrente', 'Poupança' => 'Poupança'], $profissional['tipo_conta']);
                        input('Agência', 'agencia', 'text', $profissional['agencia']);
                        input('Conta', 'conta', 'text', $profissional['conta']);
                        input('Chave PIX', 'pix_chave', 'text', $profissional['pix_chave']);
                        select('Tipo PIX', 'pix_tipo', ['CPF' => 'CPF', 'CNPJ' => 'CNPJ', 'E-mail' => 'E-mail', 'Telefone' => 'Telefone', 'Aleatória' => 'Aleatória'], $profissional['pix_tipo']);
                        input('Cargo', 'cargo', 'text', $profissional['cargo']);
                        input('Departamento', 'departamento', 'text', $profissional['departamento']);
                        select('Tipo de Contrato', 'tipo_contrato', ['CLT' => 'CLT',  'Temporário' => 'Temporário', 'Estágio' => 'Estágio'], $profissional['tipo_contrato']);
                        input('Salário', 'salario', 'number', $profissional['salario']);
                        input('Data de Admissão', 'data_admissao', 'date', $profissional['data_admissao']);
                        input('Jornada de Trabalho', 'jornada_trabalho', 'text', $profissional['jornada_trabalho']);
                        input('Data Término Contrato', 'data_termino_contrato', 'date', $profissional['data_termino_contrato']);
                        ?>
                    </div>
                </div>

                <div id="adicional" class="tab-content">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Informações Adicionais</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        textarea('Benefícios', 'beneficios', $profissional['beneficios']);
                        input('Nome Contato de Emergência', 'contato_emergencia_nome', 'text', $profissional['contato_emergencia_nome']);
                        input('Parentesco Contato Emergência', 'contato_emergencia_parentesco', 'text', $profissional['contato_emergencia_parentesco']);
                        input('Telefone Contato Emergência', 'contato_emergencia_telefone', 'text', $profissional['contato_emergencia_telefone']);
                        select('Possui Deficiência?', 'possui_deficiencia', ['0' => 'Não', '1' => 'Sim'], $profissional['possui_deficiencia']);
                        input('Tipo de Deficiência', 'tipo_deficiencia', 'text', $profissional['tipo_deficiencia']);
                        textarea('Observações', 'observacoes', $profissional['observacoes']);
                        select('Status', 'status', ['Ativo' => 'Ativo', 'Inativo' => 'Inativo', 'Férias' => 'Férias', 'Afastado' => 'Afastado', 'Desligado' => 'Desligado'], $profissional['status']);
                        ?>
                    </div>
                </div>

                <div id="documentos" class="tab-content">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Anexar e Visualizar Documentos</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4 p-6 bg-gray-50 rounded-lg border">
                            <h3 class="font-medium text-gray-800">Anexar Novos Documentos</h3>
                            <p class="text-sm text-gray-500">Você pode selecionar múltiplos arquivos (Ex: RG, CPF, Comprovante de Residência).</p>
                            <input type="file" name="anexos[]" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        </div>

                        <div class="space-y-4 p-6 bg-gray-50 rounded-lg border">
                            <h3 class="font-medium text-gray-800">Documentos Vinculados</h3>
                            <?php
                            // CORREÇÃO: Busca documentos do profissional, não de 'notas_fiscais'.
                            if ($id_profissional > 0):
                                $stmt_docs = $conn->prepare("SELECT id, nome, caminho_arquivo FROM documentos WHERE tabela_ref = 'profissionais' AND ref_id = ? ORDER BY id DESC");
                                $stmt_docs->bind_param("i", $id_profissional);
                                $stmt_docs->execute();
                                $result_docs = $stmt_docs->get_result();

                                if ($result_docs->num_rows > 0): ?>
                                    <ul class="divide-y divide-gray-200">
                                        <?php while ($doc = $result_docs->fetch_assoc()): ?>
                                            <li class="flex items-center justify-between py-2">
                                                <a href="./<?= htmlspecialchars($doc['caminho_arquivo']) ?>" target="_blank" class="text-blue-600 hover:underline truncate">
                                                    <?= htmlspecialchars($doc['nome']) ?>
                                                </a>
                                                </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm">Nenhum documento encontrado.</p>
                                <?php endif;
                                $stmt_docs->close();
                            endif;
                            ?>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t mt-8">
                    <a href="./" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-semibold mr-4">Cancelar</a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">Salvar Alterações</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');

            // Set active tab based on hash or default to the first
            const activeHash = window.location.hash || tabLinks[0].getAttribute('href');
            
            function activateTab(hash) {
                tabLinks.forEach(link => {
                    const linkHash = link.getAttribute('href');
                    if (linkHash === hash) {
                        link.classList.add('border-blue-500', 'text-blue-600');
                        link.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    } else {
                        link.classList.remove('border-blue-500', 'text-blue-600');
                        link.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    }
                });

                tabContents.forEach(content => {
                    if ('#' + content.id === hash) {
                        content.classList.add('active');
                    } else {
                        content.classList.remove('active');
                    }
                });
            }

            activateTab(activeHash);

            tabLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const hash = this.getAttribute('href');
                    window.location.hash = hash; // Update URL hash for persistence
                    activateTab(hash);
                });
            });
        });
    </script>
</body>
</html>
<?php
// CORREÇÃO: Conexão com o banco de dados fechada no final do script.
$conn->close();
?>