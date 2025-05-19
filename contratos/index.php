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

$empresa_id = $_SESSION['empresa_id'];


$sql = "
    SELECT 
        c.id, 
        c.numero_contrato, 
        c.numero_empenho, 
        c.nome_cliente, 
        e.localizacao,
        c.situacao,
        c.dt_inicio,
        c.dt_fim
    FROM contratos c
    JOIN empresas e ON c.empresa_id = e.id
    WHERE c.empresa_id = ?
    ORDER BY c.criado_em DESC
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
  <title>Contratos</title>

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
        <h1 class="text-3xl font-bold text-primary">Contratos</h1>
      </div>
      <button onclick="toggleModal()" class="mt-4  bg-primary  py-2 px-8 rounded-lg font-semibold transition text-white">
        + Novo Contrato
      </button>


    </div>

    <!-- Tabela -->
    <div class="overflow-x-auto  rounded-lg shadow-lg bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr class="">
            <th class="px-6 py-3 text-left text-sm uppercase">N°Contrato</th>
            <th class="px-6 py-3 text-left text-sm uppercase">N°Empenho</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Cliente</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Polo</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Data Inicio</th>
            <th class="px-6 py-3 text-left text-sm uppercase">Data Fim</th>

            <th class="px-6 py-3 text-left text-sm uppercase">Situação</th>

            <th class="px-6 py-3 text-center text-sm uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr class="hover:bg-gray-100" onclick="visualizarContrato(<?php echo $row['id']; ?>)">
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['numero_contrato']); ?></td>
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['numero_empenho']); ?></td>
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['nome_cliente']); ?></td>
              <td class="px-6 py-4"><?php echo htmlspecialchars($row['localizacao']); ?></td>
              <td class="px-6 py-4"><?php echo htmlspecialchars(date('d/m/Y', strtotime($row['dt_inicio']))); ?></td>
              <td class="px-6 py-4"><?php echo htmlspecialchars(date('d/m/Y', strtotime($row['dt_fim']))); ?></td>

              <td class="px-6 py-4"><?php echo htmlspecialchars($row['situacao']); ?></td>

              <td class="px-6 py-4 text-center">


                <button onclick="visualizarContrato(<?php echo $row['id']; ?>)" class=" hover:underline ml-2">
                  <i class="fas fa-eye mr-3 text-gray-500"></i>

                </button>

                <form id="delete-form-<?php echo $row['id']; ?>" class="inline" onsubmit="return false;">
                  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                  <button type="button" class="text-red-500 hover:underline ml-2" onclick="deleteContrato(<?php echo $row['id']; ?>)">
                    <i class="fas fa-trash mr-3"></i>
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

    function editarContrato(id) {
      if (confirm('Deseja editar este projeto?')) {
        window.location.href = 'form.php?contrato_id=' + id;
      }
    }

    function visualizarContrato(id) {
      window.location.href = 'detalhes.php?contrato_id=' + id;

    }
  </script>

  <script>
    function deleteContrato(id) {
      if (confirm('Tem certeza que deseja excluir?')) {
        const formData = new FormData(document.getElementById('delete-form-' + id));
        fetch('./delete.php', {
            method: 'POST',
            body: formData,
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Toastify({
                text: "Contrato Excluido com Sucesso!",
                duration: 3000,
                gravity: "top", // "top" ou "bottom"
                position: "right", // "left", "center" ou "right"
                backgroundColor: "#10b981", // Verde (tailwind: bg-green-500)
                close: true
              }).showToast(); // Opcional: Redirecionar ou atualizar a página

              setTimeout(() => {
                window.location.reload()
              }, 500)
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
            alert('Erro na requisição: ' + error);
          });
      }
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