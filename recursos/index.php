<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

// --- LÓGICA DE FILTROS ---
$empresa_id_sessao = $_SESSION['empresa_id'];
$user_id = $_SESSION['user_id'];

// Define o filtro de empresa, usando o da sessão como padrão
$filtro_empresa = !empty($_GET['empresa_id']) ? intval($_GET['empresa_id']) : $empresa_id_sessao;
$filtro_obra = $_GET['obra_id'] ?? '';
$filtro_contrato = $_GET['contrato_id'] ?? '';
$filtro_periodo = $_GET['periodo'] ?? '';

// Popula os dropdowns de filtro
$empresas = $conn->query("SELECT id, nome FROM empresas ORDER BY nome");
$obras = $conn->query("SELECT id, nome FROM obras WHERE empresa_id = $filtro_empresa ORDER BY nome");
$contratos = $conn->query("SELECT id, numero_contrato FROM contratos WHERE empresa_id = $filtro_empresa ORDER BY numero_contrato");

// --- CONSTRUÇÃO DOS FILTROS (WHERE) ---
$params_obras = [$filtro_empresa];
$types_obras = "i";
$where_obras = "WHERE sc.empresa_id = ? AND sc.os_id IS NOT NULL";

$params_avulsas = [$filtro_empresa];
$types_avulsas = "i";
$where_avulsas = "WHERE sc.empresa_id = ? AND sc.os_id IS NULL";

if (!empty($filtro_obra)) {
  $where_obras .= " AND os.obra_id = ?";
  $params_obras[] = intval($filtro_obra);
  $types_obras .= "i";
}
if (!empty($filtro_contrato)) {
  // Assumindo que o contrato está ligado à obra, e não direto na OS
  $where_obras .= " AND o.contrato_id = ?";
  $params_obras[] = intval($filtro_contrato);
  $types_obras .= "i";
}

if (!empty($filtro_periodo)) {
  $period_condition = '';
  if ($filtro_periodo == 'hoje') {
    $period_condition = " AND DATE(sc.criado_em) = CURDATE()";
  } elseif ($filtro_periodo == 'mes') {
    $period_condition = " AND MONTH(sc.criado_em) = MONTH(CURDATE()) AND YEAR(sc.criado_em) = YEAR(CURDATE())";
  }
  if ($period_condition) {
    $where_obras .= $period_condition;
    $where_avulsas .= $period_condition;
  }
}

// --- OTIMIZAÇÃO: A consulta agora traz o status da cotação junto ---
$cotacao_subquery = "(SELECT status FROM cotacao WHERE sc_id = sc.id ORDER BY id DESC LIMIT 1) as cotacao_status";
$tem_cotacao_paga_subquery = "(EXISTS(
    SELECT 1 FROM cotacao c 
    WHERE c.sc_id = sc.id AND c.status = 'Pago'
) OR EXISTS(
    SELECT 1 FROM transacoes t JOIN cotacao c ON t.cotacao_id = c.id WHERE c.sc_id = sc.id
)) AS tem_cotacao_paga";

// --- CONSULTA 1: SOLICITAÇÕES DE OBRA (VINCULADAS) ---
$sql_obras = "
SELECT 
    sc.id, sc.os_id, sc.solicitante, sc.status, sc.criado_em, sc.descricao,
    o.nome AS nome_obra,
    $cotacao_subquery,
    $tem_cotacao_paga_subquery 
FROM 
    solicitacao_compras sc
JOIN 
    empresas e ON e.id = sc.empresa_id
LEFT JOIN 
    ordem_de_servico os ON os.id = sc.os_id
LEFT JOIN 
    obras o ON o.id = os.obra_id
LEFT JOIN 
    contratos c ON c.id = o.contrato_id
$where_obras
ORDER BY sc.id DESC
";
$stmt_obras = $conn->prepare($sql_obras);
$stmt_obras->bind_param($types_obras, ...$params_obras);
$stmt_obras->execute();
$result_obras = $stmt_obras->get_result();

// --- CONSULTA 2: SOLICITAÇÕES AVULSAS (SEM VÍNCULO) ---
$sql_avulsas = "
SELECT 
    sc.id, sc.os_id, sc.solicitante, sc.status, sc.criado_em, sc.descricao,
    $cotacao_subquery,
    $tem_cotacao_paga_subquery 

FROM 
    solicitacao_compras sc
$where_avulsas
ORDER BY sc.id DESC
";
$stmt_avulsas = $conn->prepare($sql_avulsas);
$stmt_avulsas->bind_param($types_avulsas, ...$params_avulsas);
$stmt_avulsas->execute();
$result_avulsas = $stmt_avulsas->get_result();

// --- CONSULTA DE USUÁRIO (PARA PERMISSÕES) ---
$stmt_user = $conn->prepare("SELECT u.id, u.setor_id, s.nome AS setor_nome FROM users u LEFT JOIN setores s ON u.setor_id = s.id WHERE u.id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$usuario = $stmt_user->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fluxo de Compras</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      font-family: "Poppins", sans-serif;
    }
  </style>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#171717'
          }
        }
      }
    }
  </script>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
  <?php include '../layout/sidemenu.php'; ?>

  <div class="flex-1 p-8 space-y-8">
    <div class="flex flex-row justify-between items-center shadow bg-white py-6 px-6 rounded-2xl">
      <div>
        <h1 class="text-3xl font-bold text-primary">Solicitações de Compra</h1>
      </div>
      <a href="./form.php" class="flex items-center gap-2 bg-blue-600 text-white font-semibold py-2.5 px-5 rounded-lg shadow-sm hover:bg-blue-700">
        <i class="fas fa-plus-circle"></i> Solicitar
      </a>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-lg">
      <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 items-end">
        <div>
          <label for="empresa_id" class="text-sm font-medium text-gray-700 flex items-center gap-2"><i class="fas fa-building text-gray-500"></i>Empresa</label>
          <select name="empresa_id" id="empresa_id" onchange="this.form.submit()" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
            <?php $empresas->data_seek(0);
            while ($empresa = $empresas->fetch_assoc()) { ?>
              <option value="<?= $empresa['id'] ?>" <?= $filtro_empresa == $empresa['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($empresa['nome']) ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div>
          <label for="obra_id" class="text-sm font-medium text-gray-700 flex items-center gap-2"><i class="fas fa-hammer text-gray-500"></i>Obra</label>
          <select name="obra_id" id="obra_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
            <option value="">Todas</option>
            <?php $obras->data_seek(0);
            while ($obra = $obras->fetch_assoc()) { ?>
              <option value="<?= $obra['id'] ?>" <?= $filtro_obra == $obra['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($obra['nome']) ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div>
          <label for="contrato_id" class="text-sm font-medium text-gray-700 flex items-center gap-2"><i class="fas fa-file-contract text-gray-500"></i>Contrato</label>
          <select name="contrato_id" id="contrato_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
            <option value="">Todos</option>
            <?php $contratos->data_seek(0);
            while ($contrato = $contratos->fetch_assoc()) { ?>
              <option value="<?= $contrato['id'] ?>" <?= $filtro_contrato == $contrato['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($contrato['numero_contrato']) ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div>
          <label for="periodo" class="text-sm font-medium text-gray-700 flex items-center gap-2"><i class="fas fa-calendar text-gray-500"></i>Período</label>
          <select name="periodo" id="periodo" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
            <option value="">Todos</option>
            <option value="hoje" <?= $filtro_periodo == 'hoje' ? 'selected' : '' ?>>Hoje</option>
            <option value="mes" <?= $filtro_periodo == 'mes' ? 'selected' : '' ?>>Este Mês</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <button type="submit" class="w-full flex justify-center items-center gap-2 bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800"><i class="fas fa-filter"></i>Filtrar</button>
          <a href="?" class="w-full flex justify-center items-center gap-2 bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300" title="Limpar Filtros"><i class="fas fa-times"></i>Limpar</a>
        </div>
      </form>
    </div>

    <div class="overflow-x-auto rounded-lg shadow-lg bg-white p-6">
      <h2 class="text-xl font-bold text-primary mb-4">Solicitações de Obra</h2>
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-4 py-3 text-left text-sm border">ID</th>

            <th class="px-4 py-3 text-left text-sm border">Solicitante</th>
            <th class="px-4 py-3 text-left text-sm border">Descrição</th>
            <th class="px-4 py-3 text-left text-sm border">Obra</th>
            <th class="px-4 py-3 text-left text-sm border">O.S</th>
            <th class="px-4 py-3 text-left text-sm border">Status</th>
            <th class="px-4 py-3 text-center text-sm w-[180px] border">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if ($result_obras->num_rows > 0): ?>
            <?php while ($row = $result_obras->fetch_assoc()): ?>
              <tr class="hover:bg-gray-100" id="btn-<?= $row['id'] ?>" onclick="toggleDropdown(<?= $row['id'] ?>)">
                <td class="px-4 py-4 border text-sm"><?= htmlspecialchars($row['id'] ?? 'S/N') ?></td>

                <td class="px-4 py-4 border text-sm"><?= htmlspecialchars($row['solicitante'] ?? 'S/N') ?></td>
                <td class="px-4 py-4 border text-sm"><?= htmlspecialchars(mb_strimwidth($row['descricao'] ?? "", 0, 15, "...")) ?></td>
                <td class="px-4 py-4 border text-sm"><?= htmlspecialchars(mb_strimwidth($row['nome_obra'] ?? "", 0, 15, "...")) ?></td>
                <td class="px-4 py-4 border text-sm"><?= htmlspecialchars($row['os_id'] ?? 'S/N') ?></td>
                <?php
                // LÓGICA DE STATUS E BOTÃO IDÊNTICA À ORIGINAL, MAS OTIMIZADA
                $status = strtoupper($row['status']);
                $sc_id = intval($row['id']);
                $cotacao_status = $row['cotacao_status'];
                $tem_cotacao_paga = $row['tem_cotacao_paga']; // <<< Capture a nova bandeira aqui

                $statusDisplay = $status;
                $bgColor = 'bg-gray-100 text-gray-800';
                $buttonText = '';
                $showButton = true;

                if ($status === 'PENDENTE') {
                  $statusDisplay = 'PENDENTE';
                  $bgColor = 'bg-blue-100 text-blue-800';
                  $buttonText = 'Aprovar Solicitação';
                } elseif ($status === 'APROVADO') {
                  if ($tem_cotacao_paga) {
                    $statusDisplay = 'PAGO';
                    $bgColor = 'bg-green-100 text-green-800';
                    $buttonText = 'Ver Cotação';

                    // Se não houver pagamento, continua com a lógica original...
                  } elseif (is_null($cotacao_status)) {
                    $statusDisplay = 'APROVADO P/COTAÇÃO';
                    $bgColor = 'bg-green-100 text-green-800';
                    $buttonText = 'Iniciar Cotação';
                  } elseif ($cotacao_status !== 'aprovado') { // Comparação em minúsculas
                    $statusDisplay = 'PARA APROVAR COTAÇÃO';
                    $bgColor = 'bg-sky-100 text-sky-800';
                    $buttonText = 'Ver Cotação';
                  } else { // Significa que a cotação mais recente está 'aprovado'
                    $statusDisplay = 'AGUARDANDO COMPRA';
                    $bgColor = 'bg-indigo-100 text-indigo-800';
                    $buttonText = 'Ver Cotação';
                  }
                } elseif ($status === 'EM ANDAMENTO') {
                  $bgColor = 'bg-yellow-100 text-yellow-800';
                } elseif ($status === 'REJEITADO') {
                  $bgColor = 'bg-red-100 text-red-800';
                } elseif ($status === 'PAGO') {
                  $bgColor = 'bg-green-100 text-green-800';
                }
                ?>
                <td class="px-4 py-4 border text-sm">
                  <span class="px-3 py-1 rounded-full text-[12px] font-semibold <?= $bgColor ?>">
                    <?= $statusDisplay ?>
                  </span>
                </td>
                <td class="px-4 py-2 text-center border text-sm">
                  <?php if ($showButton && !empty($buttonText)): ?>
                    <button onclick="event.stopPropagation(); handleButtonClick(<?= $sc_id ?>, '<?= $statusDisplay ?>')" class="bg-green-600 text-[10px] p-2 rounded text-white whitespace-nowrap">
                      <?= $buttonText ?>
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
              <tr id="dropdown-<?= $row['id'] ?>" class="hidden bg-gray-50">
                <td colspan="6" class="p-4">
                  <div id="detalhes-<?= $row['id'] ?>"></div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-10 text-gray-500">Nenhuma solicitação de obra encontrada.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="overflow-x-auto rounded-lg shadow-lg bg-white p-6">
      <h2 class="text-xl font-bold text-primary mb-4">Solicitações Avulsas</h2>
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-4 py-3 text-left text-sm border">ID</th>
            <th class="px-4 py-3 text-left text-sm border">Solicitante</th>
            <th class="px-4 py-3 text-left text-sm border">Descrição</th>
            <th class="px-4 py-3 text-left text-sm border">Status</th>
            <th class="px-4 py-3 text-center text-sm w-[180px] border">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if ($result_avulsas->num_rows > 0): ?>
            <?php while ($row = $result_avulsas->fetch_assoc()): ?>
              <tr class="hover:bg-gray-100 cursor-pointer" onclick="toggleDropdown(<?= $row['id'] ?>)">
                <td class="px-4 py-4 border text-sm"><?= htmlspecialchars($row['id'] ?? 'S/N') ?></td>

                <td class="px-4 py-4 border text-sm"><?= htmlspecialchars($row['solicitante'] ?? 'S/N') ?></td>
                <td class="px-4 py-4 border text-sm"><?= htmlspecialchars(mb_strimwidth($row['descricao'] ?? "", 0, 30, "...")) ?></td>
                <?php
                // LÓGICA DE STATUS E BOTÃO IDÊNTICA À ORIGINAL, MAS OTIMIZADA
                $status = strtoupper($row['status']);
                $sc_id = intval($row['id']);
                $cotacao_status = $row['cotacao_status']; // Campo da consulta otimizada
                $tem_cotacao_paga = $row['tem_cotacao_paga']; // <<< Capture a nova bandeira aqui

                $statusDisplay = $status;
                $bgColor = 'bg-gray-100 text-gray-800';
                $buttonText = '';
                $showButton = true;

                if ($status === 'PENDENTE') {
                  $statusDisplay = 'PENDENTE';
                  $bgColor = 'bg-blue-100 text-blue-800';
                  $buttonText = 'Aprovar Solicitação';
                } elseif ($status === 'APROVADO') {
                   if ($tem_cotacao_paga) {
                    $statusDisplay = 'PAGO';
                    $bgColor = 'bg-green-100 text-green-800';
                    $buttonText = 'Ver Cotação';

                    // Se não houver pagamento, continua com a lógica original...
                  } elseif (is_null($cotacao_status)) {
                    $statusDisplay = 'APROVADO P/COTAÇÃO';
                    $bgColor = 'bg-green-100 text-green-800';
                    $buttonText = 'Iniciar Cotação';
                  } elseif ($cotacao_status !== 'aprovado') { // Comparação em minúsculas
                    $statusDisplay = 'PARA APROVAR COTAÇÃO';
                    $bgColor = 'bg-sky-100 text-sky-800';
                    $buttonText = 'Ver Cotação';
                  } else { // Significa que a cotação mais recente está 'aprovado'
                    $statusDisplay = 'AGUARDANDO COMPRA';
                    $bgColor = 'bg-indigo-100 text-indigo-800';
                    $buttonText = 'Ver Cotação';
                  }
                } elseif ($status === 'EM ANDAMENTO') {
                  $bgColor = 'bg-yellow-100 text-yellow-800';
                } elseif ($status === 'REJEITADO') {
                  $bgColor = 'bg-red-100 text-red-800';
                } elseif ($status === 'PAGO') {
                  $bgColor = 'bg-green-100 text-green-800';
                }
                ?>
                <td class="px-4 py-4 border text-sm">
                  <span class="px-3 py-1 rounded-full text-[12px] font-semibold <?= $bgColor ?>">
                    <?= $statusDisplay ?>
                  </span>
                </td>
                <td class="px-4 py-2 text-center border text-sm">
                  <?php if ($showButton && !empty($buttonText)): ?>
                    <button onclick="event.stopPropagation(); handleButtonClick(<?= $sc_id ?>, '<?= $statusDisplay ?>')" class="bg-green-600 text-[10px] p-2 rounded text-white whitespace-nowrap">
                      <?= $buttonText ?>
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
              <tr id="dropdown-<?= $row['id'] ?>" class="hidden bg-gray-50">
                <td colspan="4" class="p-4">
                  <div id="detalhes-<?= $row['id'] ?>"></div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center py-10 text-gray-500">Nenhuma solicitação avulsa encontrada.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>


  <div id="modal-aprovar" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
      <h2 class="text-lg font-semibold mb-4">Aprovar Solicitação</h2>
      <input type="hidden" id="modal-id-solicitacao">

      <label class="block text-sm font-medium text-gray-700 mb-1">Nome de quem está aprovando:</label>
      <input type="text" id="input-aprovador"
        class="w-full border border-gray-300 rounded px-3 py-2 mb-4 focus:outline-none focus:ring focus:border-blue-500"
        placeholder="Digite seu nome">

      <label class="block text-sm font-medium text-gray-700 mb-1">PIN:</label>
      <input type="password" id="input-senha"
        class="w-full border border-gray-300 rounded px-3 py-2 mb-4 focus:outline-none focus:ring focus:border-blue-500"
        placeholder="Digite o PIN">

      <div class="flex justify-end space-x-2">
        <button onclick="fecharModal('modal-aprovar')"
          class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Cancelar</button>
        <button onclick="enviarAprovacao()"
          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Aprovar</button>
      </div>
    </div>
  </div>

  <script>
    function handleButtonClick(id, status) {
      switch (status) {
        case 'PENDENTE':
          abrirModalAprovacao(id);
          break;
        case 'APROVADO P/COTAÇÃO':
          // Redireciona para o formulário de criação de cotação
          window.location.href = '../cotacoes/form.php?sc_id=' + id;
          break;
        case 'PARA APROVAR COTAÇÃO':
          // Redireciona para visualizar ou aprovar a cotação
          window.location.href = '../cotacoes/detalhes.php?sc_id=' + id;
          break;
        default:
          // fallback opcional
          window.location.href = '../cotacoes/detalhes.php?sc_id=' + id;
          break;
      }
    }

    function abrirModalCotacao() {
      document.getElementById('modalCotacao').classList.remove('hidden');
    }

    function abrirModalAprovacao(id) {
      document.getElementById('modal-aprovar').classList.remove('hidden');
      document.getElementById('modal-id-solicitacao').value = id;
      document.getElementById('input-aprovador').value = '';
    }

    function fecharModal(modalId) {
      document.getElementById(modalId).classList.add('hidden');
    }
    async function enviarAprovacao() {
      const id = parseInt(document.getElementById('modal-id-solicitacao').value, 10);
      const aprovador = document.getElementById('input-aprovador').value.trim();
      const senha = document.getElementById('input-senha').value;

      if (!id || aprovador === '') {
        alert('Por favor, informe seu nome para aprovação.');
        return;
      }
      if (!senha) {
        alert('Por favor, informe sua senha.');
        return;
      }

      try {
        const response = await fetch('./aprovar.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id,
            aprovador,
            senha
          })
        });

        const data = await response.json();

        if (data.success) {
          fecharModal('modal-aprovar');
          window.location.reload();
        } else {
          alert('Erro ao aprovar: ' + (data.error || 'Erro desconhecido'));
        }
      } catch (error) {
        alert('Erro ao enviar a aprovação: ' + error.message);
      }
    }
  </script>



  <script>
    function toggleDropdown(id) {
      const row = document.getElementById('dropdown-' + id);
      const detalhesDiv = document.getElementById('detalhes-' + id);
      const iconBtn = document.querySelector('#btn-' + id + ' i');

      const isHidden = row.classList.contains('hidden');

      if (iconBtn) {
        iconBtn.classList.remove('fa-chevron-down', 'fa-chevron-up');
        iconBtn.classList.add(isHidden ? 'fa-chevron-up' : 'fa-chevron-down');
      }

      if (isHidden) {
        row.classList.remove('hidden');

        if (!detalhesDiv.dataset.loaded) {
          detalhesDiv.innerHTML = "<em>Carregando...</em>";

          fetch('./get_detalhes_json.php?id=' + id)
            .then(res => res.json())
            .then(data => {
              detalhesDiv.innerHTML = `
  <div class="grid gap-4 text-sm text-gray-700">
    <div class="grid grid-cols-2 gap-2  p-4 rounded shadow bg-white">
      <div><strong>Solicitante:</strong> ${data.solicitante}</div>
      <div><strong>Status:</strong> ${data.status}</div>
      <div><strong>Descrição:</strong> ${data.descricao}</div>
      <div><strong>Criado em:</strong> ${data.criado_em}</div>
    </div>

    <div class="grid grid-cols-2 gap-2 bg-white p-4 rounded shadow border">
      <div><strong>Empresa:</strong> ${data.empresa.nome}</div>
      <div><strong>CNPJ:</strong> ${data.empresa.cnpj}</div>
    </div>

    <div class="grid grid-cols-2 gap-2 bg-white p-4 rounded shadow border">
      <div><strong>Número Contrato:</strong> ${data.obra.numero_contrato}</div>
      
    </div>

        <div class="grid grid-cols-2 gap-2 bg-gray-50 p-4 rounded shadow">
      <div><strong>Obra:</strong> ${data.obra.nome}</div>
      <div><strong>CEP:</strong> ${data.obra.cep}</div>
    </div>

     <div class="bg-white p-4 rounded shadow border">
      <h3 class="font-semibold mb-2 text-gray-800">Insumos Solicitados</h3>
      <ul class="space-y-2">
        ${data.itens.map(item => `
          <li class="border rounded p-2">
            <div><strong>Nome:</strong> ${item.insumo_nome}</div>
            <div><strong>Quantidade:</strong> ${item.quantidade}</div>
            <div><strong>Grau:</strong> ${item.grau}</div>

          </li>
        `).join('')}
      </ul>
    </div>


    <div class="grid grid-cols-2 gap-2 bg-gray-50 p-4 rounded shadow">
      <div><strong>Ordem de Serviço:</strong> <a class="text-blue-700" href="../os/detalhes.php?sc_id=${data.ordem_de_servico.id}">${data.ordem_de_servico.id} </a> </div>
      <div><strong>Status OS:</strong> ${data.ordem_de_servico.status}</div>
    </div>

    <div class="bg-white p-4 rounded shadow border">
      <h3 class="font-semibold mb-2 text-gray-800">Serviços da OS:</h3>
      <ul class="space-y-2">
        ${data.ordem_de_servico.servicos.map(servico => `
          <li class="border rounded p-2">
            <div><strong>Nome:</strong> ${servico.nome}</div>
            <div><strong>Tipo:</strong> ${servico.tipo}</div>
            <div><strong>Quantidade:</strong> ${servico.quantidade} ${servico.und}</div>
            <div><strong>Executor:</strong> ${servico.executor}</div>
            <div><strong>Início:</strong> ${servico.inicio || '-'}</div>
            <div><strong>Final:</strong> ${servico.final || '-'}</div>
          </li>
        `).join('')}
      </ul>
    </div>


  </div>
`;

              detalhesDiv.dataset.loaded = "true";
            })
            .catch(err => {
              console.error(err);
              detalhesDiv.innerHTML = '<p class="text-red-500">Erro ao carregar detalhes.</p>';
            });
        }
      } else {
        row.classList.add('hidden');
      }
    }
  </script>
</body>

</html>

<?php
$stmt_obras->close();
$stmt_avulsas->close();
$stmt_user->close();
$conn->close();
?>