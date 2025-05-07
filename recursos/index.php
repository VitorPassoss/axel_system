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
$where = "WHERE sc.empresa_id = ?";
$params = [$empresa_id_sessao];
$types = "i";

if (!empty($filtro_empresa)) {
  $where = "WHERE sc.empresa_id = ?";
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
    sc.*,
    e.nome AS nome_empresa
FROM 
    solicitacao_compras sc
JOIN 
    empresas e ON e.id = sc.empresa_id
WHERE 
    sc.empresa_id = ?
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
  <title>Solicitações de Compras </title>

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
    <div class="flex flex-row justify-between items-center justify-center shadow  bg-[#FFFFFF] py-4 px-6 rounded-2xl ">
      <div class="">
        <h1 class="text-3xl font-bold text-primary">Solicitações de Compras</h1>
      </div>
      <button onclick="toggleModal()" class="mt-4  bg-primary  py-2 px-8 rounded-lg font-semibold transition text-white">
        + Adicionar
      </button>


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
          <tr class="">
            <th class="px-6 py-3 text-left text-sm uppercase">Solicitante</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Status</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Valor Total</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Grau</th>
            <th class="px-6 py-3 text-center text-sm uppercase">Ações</th>

          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr class="hover:bg-gray-100">
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['solicitante']); ?></td>


              <?php
              $status = htmlspecialchars($row['status']);
              $bgColor = match ($status) {
                'PENDENTE'     => 'bg-blue-100 text-blue-800',
                'COTAÇÃO'     => 'bg-blue-100 text-blue-800',
                'Em andamento'  => 'bg-yellow-100 text-yellow-800',
                'APROVADO'     => 'bg-green-100 text-green-800',
                'PAGO'     => 'bg-green-100 text-green-800',
                'REJEITADO'     => 'bg-red-100 text-red-800',
                default         => 'bg-gray-100 text-gray-800',
              };
              ?>
              <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $bgColor ?>">
                  <?= $status ?>
                </span>
              </td>
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['valor']); ?></td>

              <td class="px-6 py-4"><?php echo htmlspecialchars($row['grau']); ?></td>

              <td class="px-6 py-4 text-center">
                <button onclick="visualizarProjeto(<?php echo $row['id']; ?>)" class=" hover:underline ml-2">
                  <i class="fas fa-eye mr-3 text-gray-500"></i>

                </button>


                <form id="delete-<?php echo $row['id']; ?>" class="inline" onsubmit="return false;">
                  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                  <button type="button" class="text-red-500 hover:underline ml-2" onclick="deleteContrato(<?php echo $row['id']; ?>)">
                    <i class="fas fa-trash mr-1"></i>
                  </button>
                </form>

              </td>

            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  </div>






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