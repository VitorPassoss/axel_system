<?php
include '../backend/auth.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

include '../layout/imports.php';

// Empresas disponíveis para filtro
$empresas = $conn->query("SELECT id, nome FROM empresas");

// Filtros recebidos via GET
$filtro_empresa_id = isset($_GET['empresa_id']) ? (int) $_GET['empresa_id'] : $_SESSION['empresa_id'];
$filtro_mes = isset($_GET['mes']) ? (int) $_GET['mes'] : date('m');
$filtro_ano = isset($_GET['ano']) ? (int) $_GET['ano'] : date('Y');

// Intervalo de datas para o mês filtrado
$data_inicio = "$filtro_ano-$filtro_mes-01";
$data_fim = date("Y-m-t", strtotime($data_inicio));

// --- Transações de entrada (para exibição opcional) ---
$sql = "SELECT t.*, b.nome AS banco, c.nome AS categoria
        FROM transacoes t
        LEFT JOIN bancos b ON t.banco_id = b.id
        LEFT JOIN categorias c ON t.categoria_id = c.id
        WHERE t.empresa_id = ? AND t.tipo_transacao = 'entrada' 
        AND DATE(t.criado_em) BETWEEN ? AND ?
        ORDER BY t.criado_em DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $filtro_empresa_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();

$bancos = $conn->query("SELECT id, nome FROM bancos");
$categorias = $conn->query("SELECT id, nome FROM categorias");

// --- KPIs: entradas, saídas e saldo ---
$sqlTodas = "SELECT tipo_transacao, valor, categoria_id FROM transacoes 
             WHERE empresa_id = ? 
             AND DATE(criado_em) BETWEEN ? AND ?";
$stmtTodas = $conn->prepare($sqlTodas);
$stmtTodas->bind_param("iss", $filtro_empresa_id, $data_inicio, $data_fim);
$stmtTodas->execute();
$resultTodas = $stmtTodas->get_result();

$entradas = 0;
$saidas = 0;
$categoriasSaida = [];

while ($row = $resultTodas->fetch_assoc()) {
  $valor = (float)$row['valor'];
  if ($row['tipo_transacao'] === 'entrada') {
    $entradas += $valor;
  } elseif ($row['tipo_transacao'] === 'saida') {
    $saidas += $valor;
    $catId = $row['categoria_id'] ?? 0;
    $categoriasSaida[$catId] = ($categoriasSaida[$catId] ?? 0) + $valor;
  }
}

$saldoAtual = $entradas - $saidas;

// --- Categorias (gráfico) ---
$categoriasLabels = [];
$valoresCategorias = [];

if (!empty($categoriasSaida)) {
  $ids = implode(',', array_keys($categoriasSaida));
  $res = $conn->query("SELECT id, nome FROM categorias WHERE id IN ($ids)");
  $nomesCategorias = [];

  while ($cat = $res->fetch_assoc()) {
    $nomesCategorias[$cat['id']] = $cat['nome'];
  }

  foreach ($categoriasSaida as $catId => $valor) {
    $categoriasLabels[] = $nomesCategorias[$catId] ?? 'Desconhecida';
    $valoresCategorias[] = $valor;
  }
}

// --- Gastos por setor ---
$sqlSetores = "SELECT t.setor_id, SUM(t.valor) AS total 
               FROM transacoes t
               WHERE t.empresa_id = ? 
               AND t.tipo_transacao = 'saida' 
               AND t.setor_id IS NOT NULL 
               AND DATE(t.criado_em) BETWEEN ? AND ?
               GROUP BY t.setor_id";
$stmtSetores = $conn->prepare($sqlSetores);
$stmtSetores->bind_param("iss", $filtro_empresa_id, $data_inicio, $data_fim);
$stmtSetores->execute();
$resultSetores = $stmtSetores->get_result();

$setorLabels = [];
$valoresSetores = [];
$idsSetores = [];
$valoresPorId = [];

while ($row = $resultSetores->fetch_assoc()) {
  $idsSetores[] = $row['setor_id'];
  $valoresPorId[$row['setor_id']] = floatval($row['total']);
}

if (!empty($idsSetores)) {
  $ids = implode(',', $idsSetores);
  $res = $conn->query("SELECT id, nome FROM setores WHERE id IN ($ids)");
  while ($setor = $res->fetch_assoc()) {
    $id = $setor['id'];
    $setorLabels[] = $setor['nome'];
    $valoresSetores[] = $valoresPorId[$id] ?? 0;
  }
}
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
            primary: '#1B1E26',
          }
        }
      }
    }
  </script>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
  <?php include '../layout/sidemenu.php'; ?>

  <div class="flex-1 p-8 space-y-8">
    <div class="flex flex-row justify-between items-center shadow bg-[#FFFFFF] py-4 px-6 rounded-2xl">
      <h1 class="text-3xl font-bold text-primary">Balanço</h1>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6 bg-white p-4 rounded-xl shadow-sm border border-gray-200">
      <div class="flex flex-col">
        <label class="text-sm font-medium text-gray-600 mb-1">Empresa</label>
        <select name="empresa_id" class="h-10 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <?php while ($e = $empresas->fetch_assoc()): ?>
            <option value="<?= $e['id'] ?>" <?= $e['id'] == $filtro_empresa_id ? 'selected' : '' ?>>
              <?= $e['nome'] ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

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

      <div class="flex flex-col">
        <label class="text-sm font-medium text-gray-600 mb-1">Ano</label>
        <input type="number" name="ano" value="<?= $filtro_ano ?>" min="2000" max="<?= date('Y') ?>"
          class="h-10 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div class="flex items-end">
        <button type="submit"
          class="w-full h-10 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150">
          Filtrar
        </button>
      </div>
    </form>



    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white shadow rounded-xl p-6">
        <h3 class="text-sm font-semibold text-gray-500">Total de Entradas</h3>
        <p class="text-2xl font-bold text-green-600">R$ <?= number_format($entradas, 2, ',', '.') ?></p>
      </div>
      <div class="bg-white shadow rounded-xl p-6">
        <h3 class="text-sm font-semibold text-gray-500">Total de Saídas</h3>
        <p class="text-2xl font-bold text-red-600">R$ <?= number_format($saidas, 2, ',', '.') ?></p>
      </div>
      <div class="bg-white shadow rounded-xl p-6">
        <h3 class="text-sm font-semibold text-gray-500">Saldo </h3>
        <p class="text-2xl font-bold <?= $saldoAtual >= 0 ? 'text-green-700' : 'text-red-700' ?>">
          R$ <?= number_format($saldoAtual, 2, ',', '.') ?>
        </p>
      </div>
    </div>

    <!-- Gráfico de Categorias -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 ">
      <div class="bg-white  rounded-xl shadow p-6 mb-10">
        <h2 class="text-xl font-bold mb-4">Categorias Mais Compradas</h2>
        <canvas id="graficoCategorias"></canvas>
      </div>

      <!-- Gráfico de Gastos por Setor -->
      <div class="bg-white  rounded-xl shadow p-6 mb-10">
        <h2 class="text-xl font-bold mb-4">Gastos por Setor</h2>
        <canvas id="graficoSetores"></canvas>
      </div>

    </div>
  </div>
</body>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const categorias = <?= json_encode($categoriasLabels) ?>;
  const valores = <?= json_encode($valoresCategorias) ?>;

  const ctx = document.getElementById('graficoCategorias').getContext('2d');

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: categorias,
      datasets: [{
        label: 'Valor gasto (R$)',
        data: valores,
        backgroundColor: 'rgba(239, 68, 68, 0.6)',
        borderColor: 'rgba(220, 38, 38, 1)',
        borderWidth: 1,
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return 'R$ ' + value.toLocaleString('pt-BR');
            }
          }
        }
      }
    }
  });
</script>
<script>
  const setores = <?= json_encode($setorLabels) ?>;
  const valoresSetores = <?= json_encode($valoresSetores) ?>;

  const ctxSetores = document.getElementById('graficoSetores').getContext('2d');

  new Chart(ctxSetores, {
    type: 'bar',
    data: {
      labels: setores,
      datasets: [{
        label: 'Gasto por setor (R$)',
        data: valoresSetores,
        backgroundColor: 'rgba(59, 130, 246, 0.6)',
        borderColor: 'rgba(37, 99, 235, 1)',
        borderWidth: 1,
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return 'R$ ' + value.toLocaleString('pt-BR');
            }
          }
        }
      }
    }
  });
</script>

</html>