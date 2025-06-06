<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

$erros = [];
$dados = [];

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
  'cargo',
  'departamento',
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

$campos_obrigatorios = ['nome', 'data_nascimento', 'cpf', 'genero', 'estado_civil']; // adicione os campos obrigatórios aqui

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  foreach ($campos as $campo) {
    $valor = $_POST[$campo] ?? '';
    $dados[$campo] = trim($valor);

    if (in_array($campo, $campos_obrigatorios) && empty($valor)) {
      $erros[] = "O campo <strong>$campo</strong> é obrigatório.";
    }
  }

  if (empty($erros)) {
    $stmt = $conn->prepare("INSERT INTO profissionais (
            " . implode(', ', $campos) . "
        ) VALUES (
            " . rtrim(str_repeat('?, ', count($campos)), ', ') . "
        )");

    $stmt->bind_param(
      str_repeat('s', count($campos)),
      ...array_values($dados)
    );

    if ($stmt->execute()) {
      header("Location: ./");
      exit();
    } else {
      $erros[] = "Erro ao cadastrar: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
  }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Novo Profissional</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    * {
      font-family: 'Poppins', sans-serif;
    }
  </style>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
  <?php include '../layout/sidemenu.php'; ?>
  <div class="flex-1 p-8 space-y-6">
    <!-- Cabeçalho -->
    <div class="flex flex-row justify-between items-center justify-center shadow bg-[#FFFFFF] py-4 px-6 rounded-2xl">
      <div class="p-4">
        <h1 class="text-3xl font-bold text-primary">Cadastrar Profissional</h1>
      </div>
    </div>
    <?php if (!empty($erros)): ?>
      <div class="bg-red-100 text-red-700 p-4 rounded-lg">
        <ul class="list-disc list-inside space-y-1">
          <?php foreach ($erros as $erro): ?>
            <li><?= $erro ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-6 rounded-2xl shadow space-y-6">
      <?php
      function input($label, $name, $type = 'text', $required = false)
      {
        echo "<div><label class='block text-sm font-medium mb-1'>{$label}</label><input type='{$type}' name='{$name}' class='p-2 border rounded-lg w-full' " . ($required ? "required" : "") . "/></div>";
      }
      function select($label, $name, $options)
      {
        echo "<div><label class='block text-sm font-medium mb-1'>{$label}</label><select name='{$name}' class='p-2 border rounded-lg w-full'>";
        foreach ($options as $op) echo "<option value='{$op}'>{$op}</option>";
        echo "</select></div>";
      }
      function textarea($label, $name)
      {
        echo "<div><label class='block text-sm font-medium mb-1'>{$label}</label><textarea name='{$name}' class='p-2 border rounded-lg w-full'></textarea></div>";
      }
      ?>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php
        input('Nome Completo', 'nome', 'text', true);
        input('Data de Nascimento', 'data_nascimento', 'date', true);
        select('Gênero', 'genero', ['Masculino', 'Feminino', 'Outro']);
        select('Estado Civil', 'estado_civil', ['Solteiro', 'Casado', 'Divorciado', 'Viúvo', 'União Estável']);
        input('Nacionalidade', 'nacionalidade');
        input('Naturalidade', 'naturalidade');
        input('CPF', 'cpf', 'text', true);
        input('RG', 'rg');
        input('Órgão Emissor', 'orgao_emissor');
        input('Data de Emissão', 'data_emissao', 'date');
        input('Título de Eleitor', 'titulo_eleitor');
        input('CTPS Número', 'ctps_numero');
        input('CTPS Série', 'ctps_serie');
        input('PIS/PASEP', 'pis_pasep');
        input('Reservista', 'reservista');
        input('CNH', 'cnh');
        input('Categoria CNH', 'cnh_categoria');
        input('Validade CNH', 'validade_cnh', 'date');
        input('Telefone', 'telefone');
        input('Celular', 'celular');
        input('Email', 'email');
        input('CEP', 'cep');
        input('Endereço', 'endereco');
        input('Número', 'numero');
        input('Complemento', 'complemento');
        input('Bairro', 'bairro');
        input('Cidade', 'cidade');
        input('Estado', 'estado');
        input('Banco', 'banco');
        select('Tipo de Conta', 'tipo_conta', ['Corrente', 'Poupança']);
        input('Agência', 'agencia');
        input('Conta', 'conta');
        input('Chave PIX', 'pix_chave');
        select('Tipo PIX', 'pix_tipo', ['CPF', 'CNPJ', 'E-mail', 'Telefone', 'Aleatória']);
        input('Cargo', 'cargo');
        input('Departamento', 'departamento');
        select('Tipo de Contrato', 'tipo_contrato', ['CLT', 'PJ', 'Temporário', 'Estágio']);
        input('Salário', 'salario', 'number');
        input('Data de Admissão', 'data_admissao', 'date');
        input('Jornada de Trabalho', 'jornada_trabalho');
        input('Data Término Contrato', 'data_termino_contrato', 'date');
        textarea('Benefícios', 'beneficios');
        input('Nome Contato de Emergência', 'contato_emergencia_nome');
        input('Parentesco Contato Emergência', 'contato_emergencia_parentesco');
        input('Telefone Contato Emergência', 'contato_emergencia_telefone');
        select('Possui Deficiência?', 'possui_deficiencia', ['0', '1']);
        input('Tipo de Deficiência', 'tipo_deficiencia');
        textarea('Observações', 'observacoes');
        select('Status', 'status', ['Ativo', 'Inativo', 'Férias', 'Processo Trabalhista', 'Licença Médica', 'Licença Maternidade', 'Desligado']);
        ?>
      </div>

      <div class="flex justify-end pt-6">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">Salvar</button>
      </div>
    </form>
  </div>
</body>

</html>