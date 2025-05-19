<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id_sessao = $_SESSION['empresa_id'];

// Filtros recebidos via GET
$filtro_empresa = $_GET['empresa_id'] ?? '';
$filtro_obra = $_GET['obra_id'] ?? '';

// Consulta de empresas para o filtro
$empresas = $conn->query("SELECT id, nome FROM empresas");

// Consulta de obras da empresa logada (ou da empresa filtrada, se houver)
$empresa_para_obras = !empty($filtro_empresa) ? intval($filtro_empresa) : $empresa_id_sessao;
$obras = $conn->query("SELECT * FROM ordem_de_servico WHERE empresa_id = $empresa_para_obras");

// Montagem do WHERE dinâmico
$where = "WHERE sc.empresa_id = ? AND sc.status = 'aprovado'";
$params = [$empresa_id_sessao];
$types = "i";
if (!empty($filtro_empresa)) {
  $where = "WHERE sc.empresa_id = ? AND sc.status = 'aprovado'";
  $params = [intval($filtro_empresa)];
}


if (!empty($filtro_obra)) {
  $where .= " AND sc.obra_id = ?";
  $params[] = intval($filtro_obra);
  $types .= "i";
}

// Consulta final com joins
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
$stmt->bind_param("i", $empresa_id_sessao);
$stmt->execute();
$result = $stmt->get_result();
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
    <div class="flex flex-row justify-between items-center justify-center shadow  bg-[#FFFFFF] py-6 px-6 rounded-2xl ">
      <div class="">
        <h1 class="text-3xl font-bold text-primary">Cotações</h1>
      </div>


    </div>

    <!-- Tabela -->
    <div class="overflow-x-auto  rounded-lg shadow-lg bg-white">
      <form method="GET" class="mb-1 flex gap-2 items-center">
        <!-- Filtro de Empresa -->
        <div class="flex items-center gap-2 px-4 py-4">
          <div>
            <i class="fas fa-building text-gray-500"></i> <!-- Ícone de empresa -->
            <label for="empresa_id" class="text-sm font-medium text-gray-700">Empresa</label>

            <select name="empresa_id" id="empresa_id" class="mt-1 block w-full p-2 mt-2 sm:w-56 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="">Todas</option>
              <?php while ($empresa = $empresas->fetch_assoc()) { ?>
                <option value="<?= $empresa['id'] ?>" <?= $filtro_empresa == $empresa['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($empresa['nome']) ?>
                </option>
              <?php } ?>
            </select>
          </div>
        </div>
        <!-- Botão de Filtro -->

      </form>
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-4 py-3 text-left text-sm uppercase">Solicitante</th>
            <th class="px-4 py-3 text-left text-sm uppercase">Obra</th>
            <th class="px-4 py-3 text-left text-sm uppercase">Contrato</th>
            <th class="px-4 py-3 text-left text-sm uppercase">O.S</th>
            <th class="px-4 py-1 text-left text-sm uppercase">Status</th>
            <th class="px-4 py-3 text-center text-sm uppercase w-[160px]">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
          ?>
            <tr class="hover:bg-gray-100" id="btn-<?= $id ?>" onclick="toggleDropdown(<?= $id ?>)">
              <td class="px-4 py-2"><?= htmlspecialchars($row['solicitante']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['nome_obra'] ?? '-') ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['numero_contrato'] ?? '-') ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['os_id'] ?? '-') ?></td>
              <td class="px-4 py-2">
                <?php
                $status = htmlspecialchars($row['status']);
                $bgColor = match (strtoupper($status)) {
                  'PENDENTE', 'COTAÇÃO' => 'bg-blue-100 text-blue-800',
                  'EM ANDAMENTO'        => 'bg-yellow-100 text-yellow-800',
                  'APROVADO', 'PAGO'    => 'bg-green-100 text-green-800',
                  'REJEITADO'           => 'bg-red-100 text-red-800',
                  default               => 'bg-gray-100 text-gray-800',
                };
                $statusDisplay = strtoupper($status) === 'APROVADO' ? 'APROVADO P/COTAÇÃO' : $status;
                ?>
                <span class="px-3 py-1 rounded-full text-[12px] font-semibold <?= $bgColor ?>">
                  <?= $statusDisplay ?>
                </span>
              </td>
              <td class="px-4 py-2 text-center">
                <?php
                $status = strtoupper($row['status']);
                $buttonText = ($status === 'APROVADO') ? 'Iniciar Cotação' : 'Aprovar Solicitação';
                ?>
                <button onclick="handleButtonClick(<?= $id ?>, '<?= $status ?>')" class="bg-green-600 text-[10px] p-2 rounded text-white">
                  <?= $buttonText ?>
                </button>
                <form id="delete-<?= $id ?>" class="inline" onsubmit="return false;">
                  <input type="hidden" name="id" value="<?= $id ?>">
                </form>
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

  <div id="modalCotacao" class="fixed inset-0 flex  z-50 items-center justify-center bg-gray-800 bg-opacity-50 hidden">
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
          <button type="button" onclick="fecharModal('modalCotacao')" class="ml-2 p-2 rounded bg-red-600 text-white">Cancelar</button>
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
      <input type="text" id="input-aprovador" class="w-full border border-gray-300 rounded px-3 py-2 mb-4 focus:outline-none focus:ring focus:border-blue-500" placeholder="Digite seu nome">
      <div class="flex justify-end space-x-2">
        <button onclick="fecharModal('modal-aprovar')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Cancelar</button>
        <button onclick="enviarAprovacao()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Aprovar</button>
      </div>
    </div>
  </div>


  <script>
    function handleButtonClick(id, status) {
      if (status === 'APROVADO') {
        abrirModalCotacao();
      } else {
        abrirModalAprovacao(id);
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

      if (!id || aprovador === '') {
        alert('Por favor, informe seu nome para aprovação.');
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
            aprovador
          })
        });

        const data = await response.json();

        if (data.success) {

          fecharModal('modal-aprovar');
          window.location.reload()
          // Aqui você pode adicionar uma lógica para atualizar a lista ou a linha da solicitação aprovada
          // Exemplo: recarregar a página ou atualizar o status via JS
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
      <div><strong>Valor:</strong> R$ ${data.valor}</div>
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
      <div><strong>Ordem de Serviço:</strong> ${data.ordem_de_servico.numero_os || data.ordem_de_servico.id}</div>
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
      @apply w-full p-3 bg-dark border border-gray-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent;
    }
  </style>

</body>

</html>

<?php $conn->close(); ?>