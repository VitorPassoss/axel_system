<?php

include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

// ADICIONADO: Inclua o arquivo que contém a sua função salvarAnexos.
// Crie um arquivo chamado 'funcoes_upload.php' dentro da pasta 'backend' 
// e coloque a função 'salvarAnexos' lá.
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
$erros = [];
$dados = [];

// Lista de todos os campos do formulário
$campos = [
    'nome',
    'data_nascimento',
    'genero',
    'estado_civil',
    'nacionalidade',
    'naturalidade',
    'cpf',
    'rg',
    'orgao_emissor',
    'data_emissao',
    'titulo_eleitor',
    'ctps_numero',
    'ctps_serie',
    'pis_pasep',
    'reservista',
    'cnh',
    'cnh_categoria',
    'validade_cnh',
    'telefone',
    'celular',
    'email',
    'cep',
    'endereco',
    'numero',
    'complemento',
    'bairro',
    'cidade',
    'estado',
    'banco',
    'tipo_conta',
    'agencia',
    'conta',
    'pix_chave',
    'pix_tipo',
    'empresa_id',
    'setor_id',
    'cargo',
    'tipo_contrato',
    'salario',
    'data_admissao',
    'jornada_trabalho',
    'data_termino_contrato',
    'beneficios',
    'contato_emergencia_nome',
    'contato_emergencia_parentesco',
    'contato_emergencia_telefone',
    'possui_deficiencia',
    'tipo_deficiencia',
    'observacoes',
    'status'
];

// Defina aqui os campos obrigatórios.
$campos_obrigatorios = [
    'nome',
    'data_nascimento',
    'genero',
    'estado_civil',
    'nacionalidade',
    'naturalidade',
    'cpf',
    'rg',
    'celular',
    'email',
    'cep',
    'endereco',
    'numero',
    'bairro',
    'cidade',
    'estado',
    'empresa_id',
    'setor_id',
    'cargo',
    'tipo_contrato',
    'salario',
    'data_admissao',
    'jornada_trabalho',
    'contato_emergencia_nome',
    'contato_emergencia_telefone'
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

            $sql = "INSERT INTO profissionais (" . implode(', ', $campos) . ") VALUES (" . rtrim(str_repeat('?, ', count($campos)), ', ') . ")";
            $stmt = $conn->prepare($sql);

            // Gerar a string de tipos dinamicamente é mais seguro
            $types = '';
            foreach ($campos as $campo) {
                if (in_array($campo, ['empresa_id', 'setor_id'])) {
                    $types .= 'i'; // Inteiro
                } elseif ($campo == 'salario') {
                    $types .= 'd'; // Double/Decimal
                } else {
                    $types .= 's'; // String
                }
            }
            $stmt->bind_param($types, ...array_values($dados));


            if ($stmt->execute()) {
                // Pega o ID do profissional que acabamos de criar.
                $profissional_id = $conn->insert_id;

                // Verifica se algum arquivo foi enviado.
                // UPLOAD_ERR_NO_FILE é o erro quando o campo de arquivo está vazio.
                if (isset($_FILES['anexos']) && $_FILES['anexos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                    // Chama a função para salvar os anexos, passando a conexão e os dados necessários.
                    salvarAnexos($conn, 'profissionais', $profissional_id, $_FILES['anexos']);
                }

                // Se tudo deu certo (inserção + upload), confirma a transação.
                $conn->commit();
                header("Location: ./index.php?status=success");
                exit();
            } else {
                throw new Exception("Erro ao cadastrar o profissional: " . $stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = "Erro na transação: " . $e->getMessage();
        }
    }

    // Se houver erros, repopula os dados para o formulário.
    if (!empty($erros)) {
        $dados = $_POST;
    }
}


$empresas = [];
$sql_empresas = "SELECT id, nome FROM empresas ORDER BY nome ASC";
$resultado_empresas = $conn->query($sql_empresas);
if ($resultado_empresas) {
    while ($empresa = $resultado_empresas->fetch_assoc()) {
        $empresas[] = $empresa;
    }
}

$setores = [];
$sql_setores = "SELECT id, nome FROM setores ORDER BY nome ASC";
$resultado_setores = $conn->query($sql_setores);
if ($resultado_setores) {
    while ($setor = $resultado_setores->fetch_assoc()) {
        $setores[] = $setor;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Novo Profissional - Cadastro por Etapas</title>

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
        <div class="flex flex-row justify-between items-center justify-center shadow bg-[#FFFFFF] py-4 px-6 rounded-2xl">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Cadastrar Novo Profissional</h1>
            </div>
        </div>

        <div class="bg-white p-6 md:p-8 rounded-2xl shadow">
            <div class="step-indicator-container">
                <div class="progress-bar">
                    <div class="progress" id="progress"></div>
                </div>
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Pessoais</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Documentos</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Endereço</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label">Profissional</div>
                </div>
                <div class="step" data-step="5">
                    <div class="step-circle">5</div>
                    <div class="step-label">Finalização</div>
                </div>
            </div>

            <form id="multiStepForm" method="POST" action="" class="space-y-6" enctype="multipart/form-data">
                <?php
                // Funções auxiliares para renderizar campos do formulário
                function input($label, $name, $type = 'text', $required = false, $defaultValue = '')
                {
                    $value = htmlspecialchars($defaultValue ?? '', ENT_QUOTES, 'UTF-8');
                    $req_attr = $required ? "required" : "";
                    $req_span = $required ? " <span class='text-red-500'>*</span>" : "";
                    echo "<div><label for='{$name}' class='block text-sm font-medium mb-1'>{$label}{$req_span}</label><input id='{$name}' type='{$type}' name='{$name}' class='p-2 border rounded-lg w-full transition' value='{$value}' {$req_attr}/></div>";
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
                function select($label, $name, $options, $associative = false, $required = false, $defaultValue = '')
                {
                    $req_attr = $required ? "required" : "";
                    $req_span = $required ? " <span class='text-red-500'>*</span>" : "";
                    echo "<div><label for='{$name}' class='block text-sm font-medium mb-1'>{$label}{$req_span}</label><select id='{$name}' name='{$name}' class='p-2 border rounded-lg w-full transition' {$req_attr}><option value='' disabled " . (empty($defaultValue) ? 'selected' : '') . ">Selecione...</option>";
                    if ($associative) {
                        foreach ($options as $value => $text) {
                            $val = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                            $txt = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                            $selected = ($value == $defaultValue) ? 'selected' : '';
                            echo "<option value='{$val}' {$selected}>{$txt}</option>";
                        }
                    } else {
                        foreach ($options as $op) {
                            $val = htmlspecialchars($op, ENT_QUOTES, 'UTF-8');
                            $selected = ($op == $defaultValue) ? 'selected' : '';
                            echo "<option value='{$val}' {$selected}>{$val}</option>";
                        }
                    }
                    echo "</select></div>";
                }
                function textarea($label, $name, $defaultValue = '')
                {
                    $value = htmlspecialchars($defaultValue ?? '', ENT_QUOTES, 'UTF-8');
                    echo "<div><label for='{$name}' class='block text-sm font-medium mb-1'>{$label}</label><textarea id='{$name}' name='{$name}' class='p-2 border rounded-lg w-full'>{$value}</textarea></div>";
                }
                ?>

                <div class="form-step active" data-step="1">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Dados Pessoais</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php
                        input('Nome Completo', 'nome', 'text', true, $dados['nome'] ?? '');
                        input('Data de Nascimento', 'data_nascimento', 'date', true, $dados['data_nascimento'] ?? '');
                        select('Gênero', 'genero', ['Masculino', 'Feminino', 'Outro'], false, true, $dados['genero'] ?? '');
                        select('Estado Civil', 'estado_civil', ['Solteiro', 'Casado', 'Divorciado', 'Viúvo', 'União Estável'], false, true, $dados['estado_civil'] ?? '');
                        input('Nacionalidade', 'nacionalidade', 'text', true, $dados['nacionalidade'] ?? '');
                        input('Naturalidade', 'naturalidade', 'text', true, $dados['naturalidade'] ?? '');
                        ?>
                    </div>
                </div>

                <div class="form-step" data-step="2">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Documentação</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php
                        input('CPF', 'cpf', 'text', true, $dados['cpf'] ?? '');
                        input('RG', 'rg', 'text', true, $dados['rg'] ?? '');
                        input('Órgão Emissor', 'orgao_emissor', 'text', false, $dados['orgao_emissor'] ?? '');
                        input('Data de Emissão', 'data_emissao', 'date', false, $dados['data_emissao'] ?? '');
                        input('Título de Eleitor', 'titulo_eleitor', 'text', false, $dados['titulo_eleitor'] ?? '');
                        input('CTPS Número', 'ctps_numero', 'text', false, $dados['ctps_numero'] ?? '');
                        input('CTPS Série', 'ctps_serie', 'text', false, $dados['ctps_serie'] ?? '');
                        input('PIS/PASEP', 'pis_pasep', 'text', false, $dados['pis_pasep'] ?? '');
                        input('Reservista', 'reservista', 'text', false, $dados['reservista'] ?? '');
                        input('CNH', 'cnh', 'text', false, $dados['cnh'] ?? '');
                        input('Categoria CNH', 'cnh_categoria', 'text', false, $dados['cnh_categoria'] ?? '');
                        input('Validade CNH', 'validade_cnh', 'date', false, $dados['validade_cnh'] ?? '');
                        ?>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-4 text-gray-700">Anexar Documentos</h3>
                        <div class="space-y-4 p-6 bg-gray-50 rounded-lg border">
                            <p class="text-sm text-gray-500">Você pode selecionar múltiplos arquivos (Ex: RG, CPF, Comprovante de Residência).</p>
                            <input type="file" name="anexos[]" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        </div>
                    </div>
                </div>

                <div class="form-step" data-step="3">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Endereço e Contato</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <?php
                        input('Celular', 'celular', 'tel', true, $dados['celular'] ?? '');
                        input('Telefone Fixo', 'telefone', 'tel', false, $dados['telefone'] ?? '');
                        input('Email', 'email', 'email', true, $dados['email'] ?? '');
                        input('CEP', 'cep', 'text', true, $dados['cep'] ?? '');
                        input('Endereço', 'endereco', 'text', true, $dados['endereco'] ?? '');
                        input('Número', 'numero', 'text', true, $dados['numero'] ?? '');
                        input('Complemento', 'complemento', 'text', false, $dados['complemento'] ?? '');
                        input('Bairro', 'bairro', 'text', true, $dados['bairro'] ?? '');
                        input('Cidade', 'cidade', 'text', true, $dados['cidade'] ?? '');
                        input('Estado', 'estado', 'text', true, $dados['estado'] ?? '');
                        ?>
                    </div>
                </div>

                <div class="form-step" data-step="4">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Informações Profissionais e Bancárias</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php
                        select_dinamico('Empresa', 'empresa_id', $empresas, true, $dados['empresa_id'] ?? '');
                        select_dinamico('Setor', 'setor_id', $setores, true, $dados['setor_id'] ?? '');
                        input('Cargo', 'cargo', 'text', true, $dados['cargo'] ?? '');
                        select('Tipo de Contrato', 'tipo_contrato', ['CLT', 'Temporário', 'Estágio'], false, true, $dados['tipo_contrato'] ?? '');
                        input('Salário (R$)', 'salario', 'number', true, $dados['salario'] ?? '');
                        input('Data de Admissão', 'data_admissao', 'date', true, $dados['data_admissao'] ?? '');
                        input('Jornada de Trabalho', 'jornada_trabalho', 'text', true, $dados['jornada_trabalho'] ?? '');
                        input('Data Término Contrato', 'data_termino_contrato', 'date', false, $dados['data_termino_contrato'] ?? '');
                        input('Banco', 'banco', 'text', false, $dados['banco'] ?? '');
                        select('Tipo de Conta', 'tipo_conta', ['Corrente', 'Poupança'], false, false, $dados['tipo_conta'] ?? '');
                        input('Agência', 'agencia', 'text', false, $dados['agencia'] ?? '');
                        input('Conta', 'conta', 'text', false, $dados['conta'] ?? '');
                        input('Chave PIX', 'pix_chave', 'text', false, $dados['pix_chave'] ?? '');
                        select('Tipo PIX', 'pix_tipo', ['CPF', 'CNPJ', 'E-mail', 'Telefone', 'Aleatória'], false, false, $dados['pix_tipo'] ?? '');
                        ?>
                    </div>
                </div>

                <div class="form-step" data-step="5">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Informações Adicionais</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h3 class="font-medium text-gray-600 mb-2">Contato de Emergência</h3>
                            <div class="space-y-4 p-4 border rounded-lg">
                                <?php
                                input('Nome Contato de Emergência', 'contato_emergencia_nome', 'text', true, $dados['contato_emergencia_nome'] ?? '');
                                input('Parentesco', 'contato_emergencia_parentesco', 'text', false, $dados['contato_emergencia_parentesco'] ?? '');
                                input('Telefone Contato Emergência', 'contato_emergencia_telefone', 'tel', true, $dados['contato_emergencia_telefone'] ?? '');
                                ?>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-600 mb-2">Outras Informações</h3>
                            <div class="space-y-4 p-4 border rounded-lg">
                                <?php
                                select('Possui Deficiência?', 'possui_deficiencia', ['0' => 'Não', '1' => 'Sim'], true, false, $dados['possui_deficiencia'] ?? '');
                                input('Tipo de Deficiência', 'tipo_deficiencia', 'text', false, $dados['tipo_deficiencia'] ?? '');
                                select('Status', 'status', ['Ativo', 'Inativo', 'Férias', 'Processo Trabalhista', 'Licença Médica', 'Licença Maternidade', 'Desligado'], false, true, $dados['status'] ?? 'Ativo');
                                ?>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <?php
                            textarea('Benefícios', 'beneficios', $dados['beneficios'] ?? '');
                            textarea('Observações', 'observacoes', $dados['observacoes'] ?? '');
                            ?>
                        </div>
                    </div>
                </div>


                <div class="flex justify-between pt-6 border-t mt-6">
                    <button type="button" id="prevBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-semibold transition hidden">Anterior</button>
                    <button type="button" id="nextBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition ml-auto">Próximo</button>
                    <button type="submit" id="submitBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold transition hidden">Salvar Cadastro</button>
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
                const progressPercentage = (stepIndex / (steps.length - 1)) * 100;
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
                        field.reportValidity(); // Mostra a mensagem de erro padrão do navegador
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

            // Exibe as mensagens de erro do PHP, se houver.
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