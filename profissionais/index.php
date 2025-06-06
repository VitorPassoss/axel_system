<?php
// arquivo: profissionais.php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

$sql = "SELECT id, nome, cpf, cargo, cidade FROM profissionais";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profissionais</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>* { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-[#F2F4F7] min-h-screen flex">
  <?php include '../layout/sidemenu.php'; ?>
  <div class="flex-1 p-8 space-y-8">
          <div class="flex flex-row justify-between items-center justify-center shadow  bg-[#FFFFFF] py-4 px-6 rounded-2xl ">
            <div class="">
                <h1 class="text-3xl font-bold text-primary">Profissionais</h1>
            </div>
            <button onclick="window.location.href = './form.php' " class="mt-4  bg-black  py-2 px-8 rounded-lg font-semibold transition text-white">
                + Novo Profissional
            </button>


        </div>
    <div class="overflow-x-auto rounded-lg shadow-lg bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-6 py-3 text-left text-sm  border">Nome</th>
            <th class="px-6 py-3 text-left text-sm  border">CPF</th>
            <th class="px-6 py-3 text-left text-sm  border">Cargo</th>
            <th class="px-6 py-3 text-left text-sm  border">Cidade</th>
            <th class="px-6 py-3 text-left text-sm  border">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr class="hover:bg-gray-100">
              <td class="px-6 py-4 border"><?php echo htmlspecialchars($row['nome']); ?></td>
              <td class="px-6 py-4 border"><?php echo htmlspecialchars($row['cpf']); ?></td>
              <td class="px-6 py-4 border"><?php echo htmlspecialchars($row['cargo']); ?></td>
              <td class="px-6 py-4 border"><?php echo htmlspecialchars($row['cidade']); ?></td>
              <td class="px-6 py-4 border">
                <a href="profissional_update.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline">Editar</a>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
<?php $conn->close(); ?>