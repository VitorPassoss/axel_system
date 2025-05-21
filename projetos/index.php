<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

$empresa_id = $_SESSION['empresa_id'];




$sql = "
  SELECT 
    projetos.*, 
    status.nome AS status_nome,
    status.cor as status_cor
  FROM projetos 
  JOIN status ON projetos.status_fk = status.id 
  WHERE projetos.empresa_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();



?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Projetos</title>

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
        <h1 class="text-3xl font-bold text-primary">Projetos</h1>
      </div>
      <button onclick="toggleModal()" class="mt-4  bg-primary  py-2 px-8 rounded-lg font-semibold transition text-white">
        + Novo Projeto
      </button>


    </div>


    <button onclick="window.location.href='../quadro'" class="mt-4 flex items-center gap-2 bg-white py-2 px-6 rounded-lg font-semibold border border-gray-300 hover:bg-gray-100 text-gray-800 transition">
      <i class="fas fa-th-large text-gray-600"></i>
      Visualizar Quadro
    </button>

    <!-- Tabela -->
    <div class="overflow-x-auto  rounded-lg shadow-lg bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr class="">
            <th class="px-6 py-3 text-left text-sm uppercase">Nome</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Status</th>
            <!-- <th class="px-6 py-3 text-left text-sm uppercase">Valor</th> -->
            <th class="px-6 py-3 text-left text-sm uppercase">Início</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Responsável</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Cliente</th>
            <th class="px-6 py-3 text-center text-sm uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr class="hover:bg-gray-100">
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['nome']); ?></td>
              <td class="px-6 py-4">
                <span style="background-color: <?= htmlspecialchars($row['status_cor']) ?>; color: black;"
                  class="px-3 py-1 rounded-full text-sm font-semibold ">
                  <?= htmlspecialchars($row['status_nome']) ?>
                </span>
              </td>

              <td class="px-6 py-4"><?php echo $row['data_inicio']; ?></td>
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['responsavel']); ?></td>
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['cliente_nome']); ?></td>
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
      window.location.href = 'detalhes.php?projeto_id=' + id;

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
              text: "Projeto Excluido com Sucesso!",
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