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

// Consulta de empresas e obras
$empresas = $conn->query("SELECT id, nome FROM empresas");
$empresa_para_obras = !empty($filtro_empresa) ? intval($filtro_empresa) : $empresa_id_sessao;
$obras = $conn->query("SELECT * FROM ordem_de_servico WHERE empresa_id = $empresa_para_obras");

// Montagem do WHERE dinâmico baseado na empresa da ordem de serviço
$where = "WHERE os.empresa_id = ?";
$params = [$empresa_id_sessao];
$types = "i";

if (!empty($filtro_empresa)) {
  $where = "WHERE os.empresa_id = ?";
  $params = [intval($filtro_empresa)];
}

if (!empty($filtro_obra)) {
  $where .= " AND o.id = ?";
  $params[] = intval($filtro_obra);
  $types .= "i";
}

// Consulta de cotações com joins
$sql = "
SELECT 
    c.id,
    c.sc_id,
    c.os_id,
    c.cotante,
    c.descricao,
    c.status,
    c.valor_total,
    c.dt_criado,
    o.nome AS nome_obra,
    e.nome AS nome_empresa,
    os.id AS os_id,
    con.numero_contrato
FROM 
    cotacao c
LEFT JOIN ordem_de_servico os ON os.id = c.os_id
LEFT JOIN obras o ON o.id = os.obra_id
LEFT JOIN contratos con ON con.id = os.contrato_id
LEFT JOIN empresas e ON e.id = os.empresa_id
$where
ORDER BY c.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Carregar cotações e calcular o valor_total de cada uma
$cotacoes = [];
while ($row = $result->fetch_assoc()) {
  $cotacao_id = $row['id'];

  // Soma dos itens da cotação
  $stmtTotal = $conn->prepare("SELECT SUM(valor_final) AS total FROM cotacao_item WHERE cotacao_id = ?");
  $stmtTotal->bind_param("i", $cotacao_id);
  $stmtTotal->execute();
  $resTotal = $stmtTotal->get_result();
  $total = $resTotal->fetch_assoc();
  $stmtTotal->close();

  $row['valor_total'] = $total['total'] ?? 0;

  $cotacoes[] = $row;
}

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
    <div class="overflow-x-auto rounded-lg shadow-lg bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-4 py-3 text-left text-sm ">Cotante</th>
            <th class="px-4 py-3 text-left text-sm ">Obra</th>
            <th class="px-4 py-3 text-left text-sm ">Contrato</th>
            <th class="px-4 py-3 text-left text-sm ">O.S</th>
            <th class="px-4 py-1 text-left text-sm ">Status</th>
            <th class="px-4 py-3 text-left text-sm ">Valor Total</th>
            <th class="px-4 py-3 text-center text-sm  w-[160px]">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php foreach ($cotacoes as $row) {
            $id = $row['id'];
          ?>
            <tr class="hover:bg-gray-100" id="btn-<?= $id ?>" onclick="toggleDropdown(<?= $id ?>)">
              <td class="px-4 py-2 text-sm"><?= htmlspecialchars($row['cotante'] ?? '-') ?></td>
              <td class="px-4 py-2 text-sm"><?= htmlspecialchars($row['nome_obra'] ?? '-') ?></td>
              <td class="px-4 py-2 text-sm"><?= htmlspecialchars($row['numero_contrato'] ?? '-') ?></td>
              <td class="px-4 py-2 text-sm"><?= htmlspecialchars($row['os_id'] ?? '-') ?></td>
              <td class="px-4 py-2 text-sm">
                <?php
                $status = strtoupper(htmlspecialchars($row['status']));
                $bgColor = match ($status) {
                  'PENDENTE'     => 'bg-blue-100 text-blue-800',
                  'APROVADO'     => 'bg-green-100 text-green-800',
                  'REJEITADO'    => 'bg-red-100 text-red-800',
                  default        => 'bg-gray-100 text-gray-800',
                };
                ?>
                <span class="px-3 py-1 rounded-full text-[12px] font-semibold <?= $bgColor ?>">
                  <?= $status ?>
                </span>
              </td>
              <td class="px-4 py-2 text-sm">R$ <?= number_format($row['valor_total'], 2, ',', '.') ?></td>
              <td class="px-4 py-2 text-center text-sm">
                <button onclick="window.location.href='./detalhes.php?cotacao_id=<?= htmlspecialchars($id ?? '-') ?>&sc_id=<?= htmlspecialchars($row['sc_id'] ?? '-') ?>'" class="bg-green-600 text-[10px] p-2 rounded text-white">
                  Ver Detalhes
                </button>
              </td>
            </tr>

            <tr id="dropdown-<?= $id ?>" class="hidden bg-gray-50">
              <td colspan="12" class="px-6 py-4">
                <div class="text-sm">
                  <strong>Descrição:</strong>
                  <div class="mt-2 text-gray-700">
                    <?= nl2br(htmlspecialchars($row['descricao'] ?? 'Sem descrição.')) ?>
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
        window.location.href = './form.php?sc_id=' + id
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