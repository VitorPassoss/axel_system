<?php include '../backend/auth.php'; ?>
<?php
// Conexão ao banco
include '../layout/imports.php';



include '../backend/dbconn.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

// Consulta separada para obter todos os status com nome e cor
$statusQuery = $conn->query("SELECT id, nome, cor FROM status");
$statusList = [];
while ($row = $statusQuery->fetch_assoc()) {
  $statusList[$row['id']] = [
    'nome' => $row['nome'],
    'cor' => $row['cor']
  ];
}

// Filtro de Anos
$anoQuery = $conn->query("SELECT DISTINCT YEAR(data_inicio) as ano FROM projetos");
$anos = [];
while ($row = $anoQuery->fetch_assoc()) {
  $anos[] = $row['ano'];
}

// Filtro de Contratos
$contratosQuery = $conn->query("SELECT DISTINCT contrato_id FROM projetos WHERE contrato_id IS NOT NULL");
$contratos = [];
while ($row = $contratosQuery->fetch_assoc()) {
  $contratos[] = $row['contrato_id']; 
}

// Consultas com filtros (PROJETOS agrupados por status)
$projetosQuery = $conn->query("SELECT status_fk, SUM(valor) as total_valor, COUNT(*) as total FROM projetos GROUP BY status_fk");
$projetos = [];
foreach ($statusList as $id => $info) {
  $projetos[$id] = [
    'status_nome' => $info['nome'],
    'status_cor' => $info['cor'],
    'total_valor' => 0,
    'total' => 0
  ];
}
while ($row = $projetosQuery->fetch_assoc()) {
  $id = $row['status_fk'];
  if (isset($projetos[$id])) {
    $projetos[$id]['total_valor'] = $row['total_valor'];
    $projetos[$id]['total'] = $row['total'];
  }
}

// ORDENS

// OBRAS
$obrasQuery = $conn->query("SELECT status, COUNT(*) as total FROM obras GROUP BY status");
$obras = [];
while ($row = $obrasQuery->fetch_assoc()) {
  $obras[] = $row;
}

// SOLICITAÇÕES
$solicitacoesObraQuery = $conn->query("SELECT obra_id, COUNT(*) as total FROM solicitacao_compras GROUP BY obra_id");
$solicitacoesObra = [];
while ($row = $solicitacoesObraQuery->fetch_assoc()) {
  $solicitacoesObra[] = $row;
}

$solicitacoesProjetoQuery = $conn->query("SELECT projeto_id, COUNT(*) as total FROM solicitacao_compras GROUP BY projeto_id");
$solicitacoesProjeto = [];
while ($row = $solicitacoesProjetoQuery->fetch_assoc()) {
  $solicitacoesProjeto[] = $row;
}

// VALORES TOTAIS
$result = $conn->query("SELECT SUM(valor) as total FROM projetos");
$valorTotalProjetos = ($result->fetch_assoc()['total']) ?? 0;

$result = $conn->query("SELECT SUM(valor) as total FROM solicitacao_compras");
$valorTotalOS = ($result->fetch_assoc()['total']) ?? 0;

// CONTADORES
$result = $conn->query("SELECT COUNT(*) as total FROM solicitacao_compras");
$recursos = ($result->fetch_assoc()['total']) ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM projetos");
$projetosCount = ($result->fetch_assoc()['total']) ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM obras WHERE status != 'Finalizado'");
$obrasCount = ($result->fetch_assoc()['total']) ?? 0;


?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            dark: '#fff',
            primary: '#000',
          }
        }
      }
    }
  </script>
</head>

<body class="bg-dark min-h-screen flex">
  <!-- Side Menu -->
  <?php include '../layout/sidemenu.php'; ?>

  <!-- Main Content -->
  <div class="flex-1 p-8">
    <h1 class="text-4xl font-bold mb-6">Visão Geral</h1>

    <!-- Filtros -->
    <div class="flex mb-8 gap-6">
      <div>
        <label for="setorFilter" class="block text-gray-400">Filtrar por Status</label>
        <select id="setorFilter" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
          <option value="">Selecione o Status</option>
          <?php foreach ($setores as $setor): ?>
            <option value="<?= $setor ?>"><?= ucfirst($setor) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="anoFilter" class="block text-gray-400">Filtrar por Ano</label>
        <select id="anoFilter" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
          <option value="">Selecione o Ano</option>
          <?php foreach ($anos as $ano): ?>
            <option value="<?= $ano ?>"><?= $ano ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="contratoFilter" class="block text-gray-400">Filtrar por Contrato</label>
        <select id="contratoFilter" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
          <option value="">Selecione o Contrato</option>
          <?php foreach ($contratos as $contrato): ?>
            <option value="<?= $contrato ?>"><?= $contrato ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white shadow-xl p-6 rounded-lg text-center">
        <h2 class="text-2xl font-bold"><?php echo number_format($projetosCount); ?></h2>
        <p class="text-gray-400">Projetos em Andamento</p>
      </div>

      <div class="bg-white shadow-xl p-6 rounded-lg text-center">
        <h2 class="text-2xl font-bold"><?php echo number_format($obrasCount); ?></h2>
        <p class="text-gray-400">Obras em Andamento</p>
      </div>

      <div class="bg-white shadow-xl p-6 rounded-lg text-center">
        <h2 class="text-2xl font-bold"><?php echo number_format($recursos); ?></h2>
        <p class="text-gray-400">Recursos Solicitados</p>
      </div>
    </div>



    <!-- Gráficos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white p-6 rounded-lg">
        <h3 class="text-lg mb-4">Status dos Projetos</h3>
        <canvas id="projetosChart"></canvas>
      </div>

      <div class="bg-white p-6 rounded-lg">
        <h3 class="text-lg mb-4">Status das O.S.</h3>
        <canvas id="ordensChart"></canvas>
      </div>
    </div>

    <!-- Gráficos adicionais -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white p-6 rounded-lg">
        <h3 class="text-lg mb-4">Obras por Status</h3>
        <canvas id="obrasChart"></canvas>
      </div>

      <div class="bg-white p-6 rounded-lg">
        <h3 class="text-lg mb-4">Solicitações de Compras por Obras</h3>
        <canvas id="solicitacoesObraChart"></canvas>
      </div>
    </div>
  </div>

  <script>
  // Gráfico de Projetos com cores por status
  const projetosChart = new Chart(document.getElementById('projetosChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($projetos, 'status_nome')) ?>,
      datasets: [{
        label: 'Projetos',
        data: <?= json_encode(array_column($projetos, 'total')) ?>,
        backgroundColor: <?= json_encode(array_column($projetos, 'status_cor')) ?>,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return `${context.formattedValue} projeto(s)`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      }
    }
  });

  // Gráfico de Ordens de Serviço
  const ordensChart = new Chart(document.getElementById('ordensChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($ordens, 'status')) ?>,
      datasets: [{
        label: 'O.S.',
        data: <?= json_encode(array_column($ordens, 'total')) ?>,
        backgroundColor: '#10b981',
        borderRadius: 6,
        borderSkipped: false,
      }]
    }
  });

  // Gráfico de Obras
  const obrasChart = new Chart(document.getElementById('obrasChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($obras, 'status')) ?>,
      datasets: [{
        label: 'Obras',
        data: <?= json_encode(array_column($obras, 'total')) ?>,
        backgroundColor: '#f97316',
        borderRadius: 6,
        borderSkipped: false,
      }]
    }
  });

  // Gráfico de Solicitações por Obra
  const solicitacoesObraChart = new Chart(document.getElementById('solicitacoesObraChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($solicitacoesObra, 'obra_id')) ?>,
      datasets: [{
        label: 'Solicitações',
        data: <?= json_encode(array_column($solicitacoesObra, 'total')) ?>,
        backgroundColor: '#6366f1',
        borderRadius: 6,
        borderSkipped: false,
      }]
    }
  });
</script>


</body>

</html>