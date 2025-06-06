<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id_sessao = $_SESSION['empresa_id'];

$filtro_empresa = $_GET['empresa_id'] ?? '';
$filtro_obra = $_GET['obra_id'] ?? '';
$filtro_contrato = $_GET['contrato_id'] ?? '';
$filtro_periodo = $_GET['periodo'] ?? '';

// Empresas para o select
$empresas = $conn->query("SELECT id, nome FROM empresas");

// Obras com base na empresa selecionada
$empresa_para_obras = !empty($filtro_empresa) ? intval($filtro_empresa) : $empresa_id_sessao;
$obras = $conn->query("SELECT id, nome FROM obras WHERE empresa_id = $empresa_para_obras");

// Contratos com base na empresa selecionada
$contratos = $conn->query("SELECT id, numero_contrato FROM contratos WHERE empresa_id = $empresa_para_obras");

// WHERE dinâmico
$where = "WHERE sc.empresa_id = ?";
$params = [$empresa_para_obras];
$types = "i";

// Filtro de obra (relacionamento via os.obra_id)
if (!empty($filtro_obra)) {
  $where .= " AND os.obra_id = ?";
  $params[] = intval($filtro_obra);
  $types .= "i";
}

// Filtro de contrato
if (!empty($filtro_contrato)) {
  $where .= " AND os.contrato_id = ?";
  $params[] = intval($filtro_contrato);
  $types .= "i";
}

// Filtro de período
if (!empty($filtro_periodo)) {
  if ($filtro_periodo == 'hoje') {
    $where .= " AND DATE(sc.criado_em) = CURDATE()";
  } elseif ($filtro_periodo == 'mes') {
    $where .= " AND MONTH(sc.criado_em) = MONTH(CURDATE()) AND YEAR(sc.criado_em) = YEAR(CURDATE())";
  }
}

// Consulta final
$sql = "
SELECT 
    sc.id,
    sc.os_id,
    sc.solicitante,
    sc.empresa_id,
    sc.valor,
    sc.status,
    sc.grau,
    sc.criado_em,
    sc.descricao,
    sc.aprovado_por,
    sc.aprovado_em,
    e.nome AS nome_empresa,
    os.id AS os_id,
    o.nome AS nome_obra,
    c.numero_contrato
FROM 
    solicitacao_compras sc
JOIN 
    empresas e ON e.id = sc.empresa_id
LEFT JOIN 
    ordem_de_servico os ON os.id = sc.os_id
LEFT JOIN 
    obras o ON o.id = os.obra_id
LEFT JOIN 
    contratos c ON c.id = os.contrato_id
$where
ORDER BY 
    sc.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        u.id, u.email, u.setor_id, u.empresa_id,
        s.nome AS setor_nome,
        e.nome AS empresa_nome
    FROM users u
    LEFT JOIN setores s ON u.setor_id = s.id
    LEFT JOIN empresas e ON u.empresa_id = e.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rest = $stmt->get_result();
$usuario = $rest->fetch_assoc();



?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fluxo de Compras </title>

  <script src="https://cdn.tailwindcss.com"></script>



  <style>
    * {
      font-family: "Poppins", sans-serif;
      font-style: normal;
    }
  </style>


  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#171717', // blue-500
          }
        }
      }
    }
  </script>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
  <!-- Side Menu -->
  <?php include '../layout/sidemenu.php'; ?>

  <div class="flex-1 p-8 space-y-8">
    <!-- Cabeçalho -->
    <div
      class="flex flex-row justify-between items-center justify-center shadow  bg-[#FFFFFF] py-6 px-6 rounded-2xl ">
      <div class="">
        <h1 class="text-3xl font-bold text-primary">Solicitações de Compra</h1>
      </div>


    </div>

    <!-- Tabela -->
    <div class="overflow-x-auto  rounded-lg shadow-lg bg-white">
      <form method="GET" class="mb-1 flex items-center">
        <!-- Filtro de Empresa -->
        <div class="flex items-center px-4 py-4">
          <div>
            <i class="fas fa-building text-gray-500"></i> <!-- Ícone de empresa -->
            <label for="empresa_id" class="text-sm font-medium text-gray-700">Empresa</label>

            <select name="empresa_id" id="empresa_id"
              class="mt-1 block w-full p-2 mt-2 sm:w-[150px] border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="">Todas</option>
              <?php while ($empresa = $empresas->fetch_assoc()) { ?>
                <option value="<?= $empresa['id'] ?>"
                  <?= $filtro_empresa == $empresa['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($empresa['nome']) ?>
                </option>
              <?php } ?>
            </select>
          </div>
        </div>

        <!-- Filtro de Obra -->
        <div class="flex items-center">
          <div>
            <i class="fas fa-hammer text-gray-500"></i> <!-- Ícone de obra -->
            <label for="obra_id" class="text-sm font-medium text-gray-700">Obra</label>
            <select name="obra_id" id="obra_id"
              class="mt-1 p-2 block mt-2 w-full sm:w-[150px] border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="">Todas</option>
              <?php while ($obra = $obras->fetch_assoc()) { ?>
                <option value="<?= $obra['id'] ?>" <?= $filtro_obra == $obra['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($obra['nome']) ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <!-- Filtro de Contrato -->
          <div class="flex items-center  px-4 py-4">
            <div>
              <i class="fas fa-file-contract text-gray-500"></i>
              <label for="contrato_id" class="text-sm font-medium text-gray-700">Contrato</label>
              <select name="contrato_id" id="contrato_id"
                class="mt-1 block w-full p-2 mt-2 sm:w-[150px] border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Todos</option>
                <?php
                $contratos = $conn->query("SELECT id, numero_contrato FROM contratos WHERE empresa_id = $empresa_id_sessao");
                while ($contrato = $contratos->fetch_assoc()) {
                ?>
                  <option value="<?= $contrato['id'] ?>"
                    <?= ($_GET['contrato_id'] ?? '') == $contrato['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($contrato['numero_contrato']) ?>
                  </option>
                <?php } ?>
              </select>
            </div>
          </div>

          <!-- Filtro de Data -->
          <div class="flex items-center  ">
            <div>
              <i class="fas fa-calendar text-gray-500"></i>
              <label for="periodo" class="text-sm font-medium text-gray-700">Período</label>
              <select name="periodo" id="periodo"
                class="mt-1 block w-full p-2 mt-2 sm:w-[150px] border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Todos</option>
                <option value="hoje" <?= ($_GET['periodo'] ?? '') == 'hoje' ? 'selected' : '' ?>>Hoje
                </option>
                <option value="mes" <?= ($_GET['periodo'] ?? '') == 'mes' ? 'selected' : '' ?>>Este Mês
                </option>
              </select>
            </div>
          </div>


          <div class="flex items-center ">
            <button type="submit"
              class="flex items-center bg-black mt-7 ml-4 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50">
              <i class="fas fa-filter mr-2"></i> <!-- Ícone de filtro -->
              Filtrar
            </button>
          </div>
        </div>

        <!-- Botão de Filtro -->

      </form>
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-4 py-3 text-left text-sm  border">Solicitante</th>
            <th class="px-4 py-3 text-left text-sm  border">Descrição</th>

            <th class="px-4 py-3 text-left text-sm  border">Obra</th>
            <!-- <th class="px-4 py-3 text-left text-sm  border">Contrato</th> -->
            <th class="px-4 py-3 text-left text-sm  border">O.S</th>
            <th class="px-4 py-1 text-left text-sm  border">Status</th>
            <th class="px-4 py-3 text-center text-sm  w-[160px] border">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
          ?>
            <tr class="hover:bg-gray-100" id="btn-<?= $id ?>" onclick="toggleDropdown(<?= $id ?>)">
              <td class="px-4 py-2 border text-sm"><?= htmlspecialchars($row['solicitante']) ?></td>
              <td class="px-2 py-2 border text-sm">
                <?php
                $descricao = $row['descricao'] ?? '-';
                $palavras = explode(' ', $descricao);

                if (count($palavras) > 3) {
                  echo htmlspecialchars(implode(' ', array_slice($palavras, 0, 3))) . '...';
                } else {
                  echo htmlspecialchars($descricao);
                }
                ?>
              </td>


              <td class="px-4 py-2 border text-sm"><?= htmlspecialchars($row['nome_obra'] ?? '-') ?></td>
              <!-- <td class="px-4 py-2 border"><?= htmlspecialchars($row['numero_contrato'] ?? '-') ?></td> -->
              <td class="px-4 py-2 border text-sm"><?= htmlspecialchars($row['os_id'] ?? '-') ?></td>

              <td class="px-4 py-2 border text-sm">
                <?php
                $status = strtoupper($row['status']);
                $sc_id = intval($row['id']);
                $statusDisplay = $status;
                $bgColor = 'bg-gray-100 text-gray-800';
                $buttonText = '';
                $showButton = true;

                // Verifica se há cotação associada
                $cotacaoStmt = $conn->prepare("SELECT status FROM cotacao WHERE sc_id = ? LIMIT 1");
                $cotacaoStmt->bind_param("i", $sc_id);
                $cotacaoStmt->execute();
                $cotacaoResult = $cotacaoStmt->get_result();
                $cotacao = $cotacaoResult->fetch_assoc();
                $cotacaoStmt->close();

                if ($status === 'PENDENTE') {
                  $statusDisplay = 'PENDENTE';
                  $bgColor = 'bg-blue-100 text-blue-800';

                  if ($usuario['setor_nome'] == "Gestão") {
                    $buttonText = 'Aprovar Solicitação';
                  } else {
                    $showButton = false;
                  }
                } elseif ($status === 'APROVADO') {
                  if (!$cotacao) {
                    $statusDisplay = 'APROVADO P/COTAÇÃO';
                    $bgColor = 'bg-green-100 text-green-800';

                    $buttonText = 'Iniciar Cotação';

                    if ($usuario['setor_nome'] == "Gestão" || $usuario['setor_nome'] == "Operacional") {
                      $showButton = false;
                    }
                  } elseif ($cotacao['status'] !== 'aprovado') {
                    $statusDisplay = 'PARA APROVAR COTAÇÃO';
                    $bgColor = 'bg-sky-100 text-sky-800';
                    $buttonText = 'Ver Cotação';
                  } else {
                    $statusDisplay = 'AGUARDANDO COMPRA';
                    $bgColor = 'bg-indigo-100 text-indigo-800';
                    $showButton = false;
                  }
                } elseif ($status === 'EM ANDAMENTO') {
                  $bgColor = 'bg-yellow-100 text-yellow-800';
                } elseif ($status === 'REJEITADO') {
                  $bgColor = 'bg-red-100 text-red-800';
                } elseif ($status === 'PAGO') {
                  $bgColor = 'bg-green-100 text-green-800';
                }
                ?>
                <span class="px-3 py-1 rounded-full text-[12px]  <?= $bgColor ?>">
                  <?= $statusDisplay ?>
                </span>
              </td>
              <td class="px-4 py-2 text-center border text-sm">
                <?php if ($showButton): ?>
                  <button onclick="handleButtonClick(<?= $sc_id ?>, '<?= $statusDisplay ?>')"
                    class="bg-green-600 text-[10px] p-2 rounded text-white">
                    <?= $buttonText ?>
                  </button>

                <?php endif; ?>
              </td>

            </tr>

            <!-- Linha expandida (opcional) -->
            <tr id="dropdown-<?= $id ?>" class="hidden bg-gray-50">
              <td colspan="12" class="px-6 py-4">
                <div class="text-sm">
                  <strong>Mais detalhes do item:</strong>
                  <div id="detalhes-<?= $id ?>" class="mt-2 text-gray-700">
                    <em>Carregando...</em>
                  </div>
                </div>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>

    </div>

  </div>

  <div id="modalCotacao"
    class="fixed inset-0 flex  z-50 items-center justify-center bg-gray-800 bg-opacity-50 hidden">
    <div class="bg-white p-4 rounded w-96">
      <h3 class="text-lg font-semibold mb-4">Iniciar Cotação</h3>
      <form action="processar_cotacao.php" method="POST">
        <div class="mb-4">
          <label for="fornecedor" class="block text-sm font-medium text-gray-700">Fornecedor</label>
          <input type="text" name="fornecedor" id="fornecedor" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label for="valor" class="block text-sm font-medium text-gray-700">Valor</label>
          <input type="number" name="valor" id="valor" class="w-full p-2 border rounded" required>
        </div>
        <input type="hidden" name="solicitacao_id" value="<?= $id ?>">
        <div class="flex justify-end">
          <button type="submit" class="bg-blue-600 text-white p-2 rounded">Enviar Cotação</button>
          <button type="button" onclick="fecharModal('modalCotacao')"
            class="ml-2 p-2 rounded bg-red-600 text-white">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de Aprovação -->
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
          console.warn('Ação não definida para status:', status);
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


  <script>
    function toggleModal() {
      window.location.href = './form.php'

    }

    function visualizarProjeto(id) {
      window.location.href = 'detalhes.php?sc_id=' + id;

    }
  </script>

  <script>
    function deleteContrato(id) {
      if (!confirm('Tem certeza que deseja excluir este contrato?')) return;

      const form = document.getElementById(`delete-${id}`);
      const formData = new FormData(form);

      fetch('./delete.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Toastify({
              text: "Empresa Excluida com Sucesso!",
              duration: 3000,
              gravity: "top", // "top" ou "bottom"
              position: "right", // "left", "center" ou "right"
              backgroundColor: "#10b981", // Verde (tailwind: bg-green-500)
              close: true
            }).showToast(); // Opcional: Redirecionar ou atualizar a página

            location.reload(); // ou remova o elemento da DOM diretamente
          } else {
            Toastify({
              text: "Operação com Erro!. Por Favor Consulte o Suporte",
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "#ef4444", // Vermelho (tailwind: bg-red-500)
              close: true
            }).showToast();
          }
        })
        .catch(error => {
          console.error('Erro:', error);
          alert('Erro ao processar requisição.');
        });
    }
  </script>



  <style>
    .input {
      @apply w-full p-3 bg-dark border border-gray-700 rounded-lg focus: ring-2 focus:ring-primary focus:border-transparent;
    }
  </style>

</body>

</html>