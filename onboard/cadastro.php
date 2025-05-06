<?php
// conexão com o banco
$conn = new mysqli('localhost', 'root', '', 'axel_db');

// verificar conexão
if ($conn->connect_error) {
  die("Falha na conexão: " . $conn->connect_error);
}

// Buscar setores
$setores = [];
$result = $conn->query("SELECT id, nome FROM setores");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $setores[] = $row;
  }
}

// Buscar empresas
$empresas = [];
$result = $conn->query("SELECT id, nome, localizacao FROM empresas");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $empresas[] = $row;
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Criar Conta</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            dark: '#1f2937', // gray-800
            primary: '#3b82f6', // blue-500
          }
        }
      }
    }
  </script>
</head>

<body class="bg-dark min-h-screen flex items-center justify-center p-4">

  <form method="POST" action="../backend/cadastro.php" id="registerForm" class="bg-gray-900 p-8 rounded-2xl shadow-2xl w-full max-w-md text-white">
    <h2 class="text-3xl font-bold mb-8 text-center">Axel Sistema - Cadastro</h2>

    <div class="mb-6">
      <label class="block mb-2 text-sm font-semibold">Email</label>
      <input
        type="email"
        name="email"
        class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition"
        required>
    </div>

    <div class="mb-6">
      <label class="block mb-2 text-sm font-semibold">Senha</label>
      <input
        type="password"
        name="senha"
        id="senha"
        class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition"
        required
        minlength="6"
        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}"
        title="A senha deve conter pelo menos 6 caracteres, incluindo 1 número, 1 letra maiúscula e 1 minúscula">
      <small class="text-gray-400 mt-1 block">Mínimo 6 caracteres, 1 maiúscula, 1 minúscula e 1 número.</small>
    </div>

    <div class="mb-6">
      <label class="block mb-2 text-sm font-semibold">Filial</label>
      <select
        name="empresa_id"
        class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition"
        >
        <option value="">Selecione...</option>
        <?php foreach ($empresas as $empresa): ?>
          <option value="<?= $empresa['id'] ?>"><?= htmlspecialchars($empresa['localizacao']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-8">
      <label class="block mb-2 text-sm font-semibold">Setor</label>
      <select
        name="setor_id"
        class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition"
        >
        <option value="">Selecione...</option>
        <?php foreach ($setores as $setor): ?>
          <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button
      type="submit"
      class="w-full bg-primary hover:bg-blue-600 py-3 rounded-lg font-semibold transition text-white">
      Criar Conta
    </button>

    <p id="formError" class="mt-4 text-center text-red-400 hidden"></p>

  </form>

  <script>
    const form = document.getElementById('registerForm');
    const senhaInput = document.getElementById('senha');
    const formError = document.getElementById('formError');

    form.addEventListener('submit', function(e) {
      formError.classList.add('hidden');
      if (!senhaInput.validity.valid) {
        e.preventDefault();
        formError.textContent = senhaInput.title;
        formError.classList.remove('hidden');
      }
    });
  </script>

</body>

</html>
