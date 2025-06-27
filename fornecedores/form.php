<?php

include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

// A função salvarAnexos pode ser movida para um arquivo de helpers, como funcoes_upload.php
// e incluída aqui para reutilização de código.
// include '../backend/funcoes_upload.php';
function salvarAnexos(mysqli $mysqli, $tabela_ref, $ref_id, $arquivos)
{
    // O ideal é que a pasta 'uploads' esteja fora do diretório público, se possível.
    // E o caminho __DIR__ . '/../uploads/' garante que ele está um nível acima do backend.
    $pasta_base = __DIR__ . "/../uploads/$tabela_ref/$ref_id/";
    if (!is_dir($pasta_base)) {
        // Criar o diretório com permissões seguras.
        mkdir($pasta_base, 0775, true);
    }

    $total = count($arquivos['name']);
    for ($i = 0; $i < $total; $i++) {
        if ($arquivos['error'][$i] !== UPLOAD_ERR_OK || !is_uploaded_file($arquivos['tmp_name'][$i])) {
            continue;
        }

        $nome_original = basename($arquivos['name'][$i]);
        $tmp_name = $arquivos['tmp_name'][$i];
        // Gera um nome único para evitar conflitos e problemas com caracteres especiais.
        $nome_seguro = uniqid(date('YmdHis_')) . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $nome_original);
        $caminho_final = $pasta_base . $nome_seguro;

        if (move_uploaded_file($tmp_name, $caminho_final)) {
            // Salva o caminho relativo no banco de dados.
            $caminho_db = "uploads/$tabela_ref/$ref_id/" . $nome_seguro;
            $stmt = $mysqli->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $tabela_ref, $ref_id, $nome_original, $caminho_db);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$erros = [];
$dados = [];

// Lista de todos os campos do formulário de fornecedores
$campos = [
    'razao_social',
    'nome_fantasia',
    'cnpj',
    'inscricao_estadual',
    'inscricao_municipal',
    'email',
    'telefone',
    'celular',
    'site',
    'contato_responsavel',
    'endereco',
    'numero',
    'complemento',
    'bairro',
    'cidade',
    'estado',
    'cep',
    'observacoes',
    'ativo',
    'empresa_id'
];

// Defina aqui os campos obrigatórios.
$campos_obrigatorios = [
    'razao_social',
    'cnpj',
    'email',
    'celular',
    'contato_responsavel',
    'cep',
    'endereco',
    'numero',
    'bairro',
    'cidade',
    'estado',
    'empresa_id'
];


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($campos as $campo) {
        $valor = $_POST[$campo] ?? '';
        $dados[$campo] = trim($valor);

        if (in_array($campo, $campos_obrigatorios) && empty(trim($valor))) {
            $nome_campo_legivel = ucfirst(str_replace('_', ' ', $campo));
            $erros[] = "O campo <strong>$nome_campo_legivel</strong> é obrigatório.";
        }
    }

    if (empty($erros)) {
        try {
            $conn->begin_transaction();

            // O campo 'data_cadastro' será preenchido automaticamente pelo banco (DEFAULT NOW())
            $sql = "INSERT INTO fornecedores (" . implode(', ', $campos) . ", data_cadastro) VALUES (" . rtrim(str_repeat('?, ', count($campos)), ', ') . ", NOW())";
            $stmt = $conn->prepare($sql);

            // Gerar a string de tipos dinamicamente
            $types = '';
            foreach ($campos as $campo) {
                if (in_array($campo, ['empresa_id', 'ativo'])) {
                    $types .= 'i'; // Inteiro
                } else {
                    $types .= 's'; // String
                }
            }
            $stmt->bind_param($types, ...array_values($dados));


            if ($stmt->execute()) {
                $fornecedor_id = $conn->insert_id;

                if (isset($_FILES['anexos']) && $_FILES['anexos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                    salvarAnexos($conn, 'fornecedores', $fornecedor_id, $_FILES['anexos']);
                }

                $conn->commit();
                header("Location: ./index.php");
                exit();
            } else {
                throw new Exception("Erro ao cadastrar o fornecedor: " . $stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = "Erro na transação: " . $e->getMessage();
        }
    }

    if (!empty($erros)) {
        $dados = $_POST;
    }
}


// Busca empresas para o dropdown de vínculo
$empresas = [];
$sql_empresas = "SELECT id, nome FROM empresas ORDER BY nome ASC";
$resultado_empresas = $conn->query($sql_empresas);
if ($resultado_empresas) {
    while ($empresa = $resultado_empresas->fetch_assoc()) {
        $empresas[] = $empresa;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Novo Fornecedor - Cadastro</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        .step-indicator-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 10;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #E5E7EB;
            color: #6B7280;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #D1D5DB;
        }

        .step-label {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #6B7280;
            font-weight: 500;
            text-align: center;
        }

        .step.active .step-circle {
            background-color: #3B82F6;
            color: white;
            border-color: #2563EB;
        }

        .step.active .step-label {
            color: #1F2937;
        }

        .step.completed .step-circle {
            background-color: #10B981;
            color: white;
            border-color: #059669;
        }

        .progress-bar {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #E5E7EB;
            transform: translateY(-50%);
            z-index: 1;
        }

        .progress {
            height: 100%;
            width: 0%;
            background-color: #3B82F6;
            transition: width 0.4s ease;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        input:invalid,
        select:invalid {
            border-color: #EF4444;
        }
    </style>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>

    <div class="flex-1 p-8 space-y-6">
        <div class="flex flex-row justify-between items-center bg-white py-4 px-6 rounded-2xl shadow-sm">
            <h1 class="text-3xl font-bold text-gray-800">Cadastrar Novo Fornecedor</h1>
            <a href="./index.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Voltar para a listagem
            </a>
        </div>

        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm">
            <div class="step-indicator-container">
                <div class="progress-bar">
                    <div class="progress" id="progress"></div>
                </div>
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Dados Cadastrais</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Contato</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Endereço</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label">Finalização</div>
                </div>
            </div>

            <form id="multiStepForm" method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <?php
                // Funções auxiliares para renderizar campos
                function input($label, $name, $type = 'text', $required = false, $defaultValue = '', $placeholder = '')
                {
                    $value = htmlspecialchars($defaultValue ?? '', ENT_QUOTES, 'UTF-8');
                    $req_attr = $required ? "required" : "";
                    $req_span = $required ? " <span class='text-red-500'>*</span>" : "";
                    echo "<div><label for='{$name}' class='block text-sm font-medium mb-1'>{$label}{$req_span}</label><input id='{$name}' type='{$type}' name='{$name}' class='p-2 border rounded-lg w-full transition' value='{$value}' {$req_attr} placeholder='{$placeholder}'/></div>";
                }
                function select_dinamico($label, $name, $options, $required = false, $defaultValue = '')
                {
                    $req_attr = $required ? "required" : "";
                    $req_span = $required ? " <span class='text-red-500'>*</span>" : "";
                    echo "<div><label for='{$name}' class='block text-sm font-medium mb-1'>{$label}{$req_span}</label><select id='{$name}' name='{$name}' class='p-2 border rounded-lg w-full transition' {$req_attr}><option value='' disabled " . (empty($defaultValue) ? 'selected' : '') . ">Selecione...</option>";
                    foreach ($options as $option) {
                        $value = htmlspecialchars($option['id'], ENT_QUOTES, 'UTF-8');
                        $text = htmlspecialchars($option['nome'] ?? '', ENT_QUOTES, 'UTF-8');
                        $selected = ($option['id'] == $defaultValue) ? 'selected' : '';
                        echo "<option value='{$value}' {$selected}>{$text}</option>";
                    }
                    echo "</select></div>";
                }
                function select_assoc($label, $name, $options, $required = false, $defaultValue = '')
                {
                    $req_attr = $required ? "required" : "";
                    $req_span = $required ? " <span class='text-red-500'>*</span>" : "";
                    echo "<div><label for='{$name}' class='block text-sm font-medium mb-1'>{$label}{$req_span}</label><select id='{$name}' name='{$name}' class='p-2 border rounded-lg w-full transition' {$req_attr}><option value='' disabled " . (empty($defaultValue) ? 'selected' : '') . ">Selecione...</option>";
                    foreach ($options as $value => $text) {
                        $val = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        $txt = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                        $selected = ((string)$value === (string)$defaultValue) ? 'selected' : '';
                        echo "<option value='{$val}' {$selected}>{$txt}</option>";
                    }
                    echo "</select></div>";
                }
                function textarea($label, $name, $defaultValue = '', $placeholder = '')
                {
                    $value = htmlspecialchars($defaultValue ?? '', ENT_QUOTES, 'UTF-8');
                    echo "<div><label for='{$name}' class='block text-sm font-medium mb-1'>{$label}</label><textarea id='{$name}' name='{$name}' class='p-2 border rounded-lg w-full' placeholder='{$placeholder}'>{$value}</textarea></div>";
                }
                ?>

                <div class="form-step active" data-step="1">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Dados Cadastrais</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php input('Razão Social', 'razao_social', 'text', true, $dados['razao_social'] ?? ''); ?>
                        <?php input('Nome Fantasia', 'nome_fantasia', 'text', false, $dados['nome_fantasia'] ?? ''); ?>
                        <?php input('CNPJ', 'cnpj', 'text', true, $dados['cnpj'] ?? ''); ?>
                        <?php input('Inscrição Estadual', 'inscricao_estadual', 'text', false, $dados['inscricao_estadual'] ?? ''); ?>
                        <?php input('Inscrição Municipal', 'inscricao_municipal', 'text', false, $dados['inscricao_municipal'] ?? ''); ?>
                        <?php input('Site', 'site', 'url', false, $dados['site'] ?? '', 'https://exemplo.com'); ?>
                    </div>
                </div>

                <div class="form-step" data-step="2">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Informações de Contato</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php input('Nome do Contato Responsável', 'contato_responsavel', 'text', true, $dados['contato_responsavel'] ?? ''); ?>
                        <?php input('E-mail Principal', 'email', 'email', true, $dados['email'] ?? ''); ?>
                        <?php input('Telefone Fixo', 'telefone', 'tel', false, $dados['telefone'] ?? ''); ?>
                        <?php input('Celular / WhatsApp', 'celular', 'tel', true, $dados['celular'] ?? ''); ?>
                    </div>
                </div>

                <div class="form-step" data-step="3">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Endereço</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <?php input('CEP', 'cep', 'text', true, $dados['cep'] ?? ''); ?>
                        <div class="md:col-span-2 lg:col-span-3">
                            <?php input('Endereço (Rua, Av.)', 'endereco', 'text', true, $dados['endereco'] ?? ''); ?>
                        </div>
                        <?php input('Número', 'numero', 'text', true, $dados['numero'] ?? ''); ?>
                        <?php input('Complemento', 'complemento', 'text', false, $dados['complemento'] ?? ''); ?>
                        <?php input('Bairro', 'bairro', 'text', true, $dados['bairro'] ?? ''); ?>
                        <?php input('Cidade', 'cidade', 'text', true, $dados['cidade'] ?? ''); ?>
                        <?php input('Estado', 'estado', 'text', true, $dados['estado'] ?? ''); ?>
                    </div>
                </div>

                <div class="form-step" data-step="4">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Vinculação, Status e Anexos</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <?php select_dinamico('Empresa Vinculada', 'empresa_id', $empresas, true, $dados['empresa_id'] ?? ''); ?>
                            <?php select_assoc('Status', 'ativo', ['1' => 'Ativo', '0' => 'Inativo'], true, $dados['ativo'] ?? '1'); ?>
                            <?php textarea('Observações', 'observacoes', $dados['observacoes'] ?? '', 'Alguma informação adicional sobre o fornecedor...'); ?>
                        </div>
                        <div class="p-6 bg-gray-50 rounded-lg border">
                            <h3 class="font-medium text-gray-800 mb-2">Anexar Documentos</h3>
                            <p class="text-sm text-gray-500 mb-4">Anexe o contrato social, certidões ou outros documentos relevantes.</p>
                            <input type="file" name="anexos[]" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-between pt-6 border-t mt-6">
                    <button type="button" id="prevBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-semibold transition hidden">Anterior</button>
                    <button type="button" id="nextBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition ml-auto">Próximo</button>
                    <button type="submit" id="submitBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold transition hidden">Salvar Fornecedor</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('multiStepForm');
            const steps = Array.from(form.querySelectorAll('.form-step'));
            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            const submitBtn = document.getElementById('submitBtn');
            const stepIndicators = Array.from(document.querySelectorAll('.step'));
            const progress = document.getElementById('progress');
            let currentStep = 0;

            function showStep(stepIndex) {
                steps.forEach((step, index) => {
                    step.classList.toggle('active', index === stepIndex);
                });
                updateStepIndicator(stepIndex);
                updateNavigationButtons(stepIndex);
            }

            function updateStepIndicator(stepIndex) {
                stepIndicators.forEach((indicator, index) => {
                    const circle = indicator.querySelector('.step-circle');
                    if (index < stepIndex) {
                        indicator.classList.add('completed');
                        indicator.classList.remove('active');
                        circle.innerHTML = '&#10003;';
                    } else if (index === stepIndex) {
                        indicator.classList.add('active');
                        indicator.classList.remove('completed');
                        circle.innerHTML = index + 1;
                    } else {
                        indicator.classList.remove('active', 'completed');
                        circle.innerHTML = index + 1;
                    }
                });
                const progressPercentage = (steps.length > 1) ? (stepIndex / (steps.length - 1)) * 100 : 0;
                progress.style.width = `${progressPercentage}%`;
            }

            function updateNavigationButtons(stepIndex) {
                prevBtn.classList.toggle('hidden', stepIndex === 0);
                nextBtn.classList.toggle('hidden', stepIndex === steps.length - 1);
                submitBtn.classList.toggle('hidden', stepIndex !== steps.length - 1);
            }

            function validateCurrentStep() {
                const currentStepFields = steps[currentStep].querySelectorAll('input[required], select[required]');
                let allValid = true;
                currentStepFields.forEach(field => {
                    if (!field.checkValidity()) {
                        allValid = false;
                        field.reportValidity();
                    }
                });
                if (!allValid) {
                    showToast('Por favor, preencha todos os campos obrigatórios (*) antes de continuar.', 'error');
                }
                return allValid;
            }

            function showToast(message, type = 'info') {
                const backgroundColor = type === 'error' ? '#EF4444' : (type === 'success' ? '#10B981' : '#3B82F6');
                Toastify({
                    text: message,
                    duration: 4000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: {
                        background: backgroundColor,
                        borderRadius: "8px",
                        fontWeight: "500"
                    }
                }).showToast();
            }

            nextBtn.addEventListener('click', () => {
                if (validateCurrentStep()) {
                    if (currentStep < steps.length - 1) {
                        currentStep++;
                        showStep(currentStep);
                    }
                }
            });

            prevBtn.addEventListener('click', () => {
                if (currentStep > 0) {
                    currentStep--;
                    showStep(currentStep);
                }
            });

            <?php if (!empty($erros)): ?>
                <?php foreach ($erros as $erro): ?>
                    showToast("<?php echo strip_tags(addslashes($erro)); ?>", 'error');
                <?php endforeach; ?>
            <?php endif; ?>

            showStep(currentStep);
        });
    </script>
</body>

</html>