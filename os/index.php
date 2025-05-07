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
$obras = $conn->query("SELECT id, nome FROM obras WHERE empresa_id = $empresa_para_obras");

// Montagem do WHERE dinâmico
$where = "WHERE os.empresa_id = ?";
$params = [$empresa_id_sessao];
$types = "i";

if (!empty($filtro_empresa)) {
  $where = "WHERE os.empresa_id = ?";
  $params = [intval($filtro_empresa)];
}

if (!empty($filtro_obra)) {
  $where .= " AND os.obra_id = ?";
  $params[] = intval($filtro_obra);
  $types .= "i";
}

// Consulta final com joins
$sql = "
SELECT 
    os.*, 
    o.nome AS nome_obra
FROM 
    ordem_de_servico os
LEFT JOIN 
    obras o ON os.obra_id = o.id
$where
ORDER BY os.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ordens de Serviço </title>

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
        <h1 class="text-3xl font-bold text-primary">Ordens de Serviço</h1>
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

        <!-- Filtro de Obra -->
        <div class="flex items-center gap-2">
          <div>
            <i class="fas fa-hammer text-gray-500"></i> <!-- Ícone de obra -->
            <label for="obra_id" class="text-sm font-medium text-gray-700">Obra</label>
            <select name="obra_id" id="obra_id" class="mt-1 p-2 block mt-2 w-full sm:w-56 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="">Todas</option>
              <?php while ($obra = $obras->fetch_assoc()) { ?>
                <option value="<?= $obra['id'] ?>" <?= $filtro_obra == $obra['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($obra['nome']) ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="flex items-center py-4 ">
            <button type="submit" class="flex items-center bg-black mt-7 ml-4 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50">
              <i class="fas fa-filter mr-2"></i> <!-- Ícone de filtro -->
              Filtrar
            </button>
          </div>
        </div>

        <!-- Botão de Filtro -->

      </form>

      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr class="">
            <th class="px-6 py-3 text-left text-sm uppercase">Número</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Status</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Obra</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Ações</th>

          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr class="hover:bg-gray-100">
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['numero_os']); ?></td>

              <?php
              $status = htmlspecialchars($row['status']);
              $bgColor = match ($status) {
                'Aberta'     => 'bg-blue-100 text-blue-800',
                'Em andamento'  => 'bg-yellow-100 text-yellow-800',
                'Concluída'     => 'bg-green-100 text-green-800',
                'Cancelada'     => 'bg-red-100 text-red-800',
                default         => 'bg-gray-100 text-gray-800',
              };
              ?>
              <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $bgColor ?>">
                  <?= $status ?>
                </span>
              </td>

              <td class="px-6 py-4"><?php echo htmlspecialchars($row['nome_obra']); ?></td>

              <td class="px-6 py-4 ">
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