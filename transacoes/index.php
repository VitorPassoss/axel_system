<?php
include '../backend/auth.php';
include '../backend/dbconn.php';
include '../layout/imports.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

// Filtros
$filtro_empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : $_SESSION['empresa_id'];
$filtro_mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$filtro_ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');

$sql = "SELECT t.*, b.nome AS banco, c.nome AS categoria
        FROM transacoes t
        LEFT JOIN bancos b ON t.banco_id = b.id
        LEFT JOIN categorias c ON t.categoria_id = c.id
        WHERE t.empresa_id = ?
          AND tipo_transacao = 'entrada'
          AND MONTH(t.criado_em) = ?
          AND YEAR(t.criado_em) = ?
        ORDER BY t.criado_em DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $filtro_empresa_id, $filtro_mes, $filtro_ano);
$stmt->execute();
$result = $stmt->get_result();

// Pré-carregar os dados auxiliares em arrays
$empresas = $conn->query("SELECT id, nome FROM empresas");
$bancos = $conn->query("SELECT id, nome FROM bancos");
$categorias = $conn->query("SELECT id, nome FROM categorias");
$setores = $conn->query("SELECT id, nome FROM setores");

?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Transações</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#1B1E26', // blue-500
          }
        }
      }
    }
  </script>
</head>

<div id="modal-criar" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="relative   p-8 rounded-2xl shadow-2xl w-11/12 md:w-2/3 lg:w-1/2 animate-fadeIn bg-white">

    <!-- Botão Fechar -->
    <button onclick="toggleModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-3xl">
      &times;
    </button>

    <h2 class="text-3xl font-bold text-gray-800  mb-6 text-center">Nova Transação</h2>

    <!-- Formulário -->
    <form id="form-transacao" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Descrição</label>
          <input type="text" name="descricao" required class="rounded-lg p-3 border border-gray-300" />
        </div>


        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Valor</label>
          <input type="text" name="valor" class="rounded-lg p-3 border border-gray-300" id="valor-input" oninput="formatarValor()" />
        </div>

        <!-- Banco -->
        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Banco</label>
          <select name="banco_id" id="select-banco" class="rounded-lg p-3 border border-gray-300">
            <?php while ($banco = $bancos->fetch_assoc()) : ?>
              <option value="<?= $banco['id'] ?>"><?= htmlspecialchars($banco['nome']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>



      </div>

      <div class="flex justify-end pt-4">
        <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg hover:bg-gray-800">
          Criar
        </button>
      </div>
    </form>

  </div>
</div>


<body class="bg-[#F2F4F7] min-h-screen flex">

  <?php include '../layout/sidemenu.php'; ?>

  <div class="flex-1 p-8 space-y-8">

    <!-- Cabeçalho -->
    <div class="flex flex-row justify-between items-center shadow bg-[#FFFFFF] py-4 px-6 rounded-2xl">
      <h1 class="text-3xl font-bold text-primary">Entradas</h1>
      <button onclick="toggleModal()" class="bg-primary py-2 px-8 rounded-lg font-semibold transition text-white">
        + Nova Entrada
      </button>
    </div>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6 bg-white p-4 rounded-xl shadow-sm border border-gray-200">
      <!-- Empresa -->
      <div class="flex flex-col">
        <label class="text-sm font-medium text-gray-600 mb-1">Empresa</label>
        <select name="empresa_id" class="h-10 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <?php foreach ($empresas as $empresa): ?>
            <option value="<?= $empresa['id'] ?>" <?= $empresa['id'] == $filtro_empresa_id ? 'selected' : '' ?>>
              <?= htmlspecialchars($empresa['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Mês -->
      <div class="flex flex-col">
        <label class="text-sm font-medium text-gray-600 mb-1">Mês</label>
        <select name="mes" class="h-10 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m == $filtro_mes ? 'selected' : '' ?>>
              <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>

      <!-- Ano -->
      <div class="flex flex-col">
        <label class="text-sm font-medium text-gray-600 mb-1">Ano</label>
        <input type="number" name="ano" value="<?= $filtro_ano ?>" min="2000" max="<?= date('Y') ?>"
          class="h-10 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <!-- Botão -->
      <div class="flex items-end">
        <button type="submit"
          class="w-full h-10 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150">
          Filtrar
        </button>
      </div>
    </form>

    <!-- Tabela -->
    <div class="overflow-x-auto rounded-lg shadow-lg bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-6 py-3 text-left text-sm ">Valor</th>
            <th class="px-6 py-3 text-left text-sm ">Data</th>

            <th class="px-6 py-3 text-center text-sm ">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) : ?>
            <tr class="hover:bg-gray-100">


              <td class="px-6 py-4">R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?></td>
              <td class="px-6 py-4">
                <?php
                // Converte a data e hora para o formato brasileiro
                $data = new DateTime($row['criado_em']);
                echo $data->format('d/m/Y H:i:s');
                ?>
              </td>

              <td class="px-6 py-4 text-center">
                <button onclick="visualizarTransacao(<?php echo $row['id']; ?>)" class="hover:underline ml-2">
                  <i class="fas fa-eye mr-3 text-gray-500"></i>
                </button>

                <form id="delete-<?php echo $row['id']; ?>" class="inline" onsubmit="return false;">
                  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                  <button type="button" class="text-red-500 hover:underline ml-2" onclick="deleteTransacao(<?php echo $row['id']; ?>)">
                    <i class="fas fa-trash mr-1"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>

<script>
  function toggleModal() {
    document.getElementById("modal-criar").classList.toggle("hidden");
  }

  document.getElementById("form-transacao").addEventListener("submit", async function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    try {
      const response = await fetch('./create.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (!response.ok || result.error) {
        alert(result.error || "Erro ao criar transação.");
      } else {
        Toastify({
          text: "Operação realizada com sucesso!",
          duration: 3000,
          gravity: "top", // "top" ou "bottom"
          position: "right", // "left", "center" ou "right"
          backgroundColor: "#10b981", // Verde (tailwind: bg-green-500)
          close: true
        }).showToast();
        setTimeout(() => {
          window.location.reload()
        }, 1000)
      }

    } catch (err) {
      console.error(err);
      alert("Erro inesperado.");
    }
  });
</script>



</html>