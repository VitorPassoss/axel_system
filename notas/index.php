<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}


$sql = "SELECT * FROM notas_fiscais";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Notas Fiscais</title>

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
        <h1 class="text-3xl font-bold text-primary">Notas Fiscais</h1>
      </div>
      <button onclick="window.location.href = './form.php' " class="mt-4  bg-primary  py-2 px-8 rounded-lg font-semibold transition text-white">
        + Adicionar
      </button>


    </div>

    <!-- Tabela -->
    <div class="overflow-x-auto  rounded-lg shadow-lg bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr class="">
            <th class="px-6 py-3 text-left text-sm ">Número da Nota</th>
            <th class="px-6 py-3 text-left text-sm ">Data Recebimento</th>
            <th class="px-6 py-3 text-left text-sm ">Tipo de Nota</th>
            <th class="px-6 py-3 text-left text-sm ">Valor Total (Bruto)</th>

          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr class="hover:bg-gray-100 cursor-pointer" onclick="window.location.href='./detalhes.php?id=<?= $row['id'] ?>'">
              <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($row['numero_nota']); ?></td>
              <td class="px-6 py-4 text-sm">
                <?= isset($row['data_recebimento']) ? date('d/m/Y', strtotime($row['data_recebimento'])) : '' ?>
              </td>
              <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($row['tipo_nota']); ?></td>
              <td class="px-6 py-4 text-sm">
                R$ <?= number_format($row['valor_total'], 2, ',', '.') ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>

      </table>
    </div>

  </div>





  <style>
    .input {
      @apply w-full p-3 bg-dark border border-gray-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent;
    }
  </style>

</body>

</html>

<?php $conn->close(); ?>