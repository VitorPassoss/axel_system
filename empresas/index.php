<?php
include '../backend/auth.php';
include '../layout/imports.php';

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}


$sql = "SELECT * FROM empresas";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Empresas</title>

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
        <h1 class="text-3xl font-bold text-primary">Matriz e Filiais </h1>
      </div>
      <button onclick="toggleModal()" class="mt-4  bg-primary  py-2 px-8 rounded-lg font-semibold transition text-white">
        + Adicionar
      </button>


    </div>

    <!-- Tabela -->
    <div class="overflow-x-auto  rounded-lg shadow-lg bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr class="">
            <th class="px-6 py-3 text-left text-sm ">Nome</th>
            <th class="px-6 py-3 text-left text-sm ">Cnpj</th>
            <th class="px-6 py-3 text-left text-sm ">Razao Social</th>
            <th class="px-6 py-3 text-left text-sm ">Cidade</th>
            <th class="px-6 py-3 text-center text-sm ">Ações</th>

          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr class="hover:bg-gray-100">
              <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($row['nome']); ?></td>
              <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($row['cnpj']); ?></td>
              <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($row['razao_social']); ?></td>
              <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($row['localizacao']); ?></td>
              <td class="px-6 py-4 text-center text-sm">
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

    <!-- Modal Novo Projeto -->
    <!-- Modal Novo Projeto -->
    <div id="modal-criar" class="fixed inset-0  flex items-center justify-center z-[99999] hidden">
      <div class="relative bg-white  p-8 rounded-2xl shadow-2xl w-11/12 md:w-2/3 lg:w-1/2 animate-fadeIn">

        <!-- Botão Fechar -->
        <button onclick="toggleModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-3xl">
          &times;
        </button>

        <!-- Cabeçalho -->
        <h2 class="text-3xl font-bold text-gray-800 dark:text-black mb-6 text-center">
          Registrar Filial
        </h2>

        <!-- Formulário -->
        <form method="POST" class="space-y-6" action="./create.php" enctype="multipart/form-data">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div class="flex flex-col">
              <label for="nome" class="text-gray-700 mb-1 text-sm font-medium">Nome da Empresa</label>
              <input type="text" id="nome" name="nome" required
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
            </div>

            <div class="flex flex-col">
              <label for="cnpj" class="text-gray-700 mb-1 text-sm font-medium">CNPJ</label>
              <input type="text" id="cnpj" name="cnpj" required
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
            </div>

            <div class="flex flex-col">
              <label for="cidade" class="text-gray-700 mb-1 text-sm font-medium">Cidade</label>
              <input type="text" id="cidade" name="cidade" required
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
            </div>


            <div class="flex flex-col">
              <label for="anexos" class="text-gray-700 mb-1 text-sm font-medium">Anexos</label>
              <input type="file" id="anexos" name="anexos[]" multiple
                class="w-full text-gray-800 dark:text-gray-100" />
            </div>

          </div>

          <div class="flex justify-end">
            <button type="submit" name="criar"
              class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
              Criar
            </button>
          </div>
        </form>

      </div>
    </div>

    <div id="modal-editar" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
      <div class="relative bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-2xl w-11/12 md:w-2/3 lg:w-1/2 animate-fadeIn">

        <!-- Botão Fechar -->
        <button onclick="toggleModalEditar()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-3xl">
          &times;
        </button>

        <!-- Cabeçalho -->
        <h2 class="text-3xl font-bold text-gray-800 dark:text-black mb-6 text-center">
          Editar Empresa
        </h2>

        <!-- Formulário -->
        <form method="POST" class="space-y-6" action="./update.php">
          <input type="hidden" id="edit-id" name="id">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col">
              <label for="edit-nome" class="text-gray-700 mb-1 text-sm font-medium">Nome da Empresa</label>
              <input type="text" id="edit-nome" name="nome" required
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
            </div>

            <div class="flex flex-col">
              <label for="edit-cnpj" class="text-gray-700 mb-1 text-sm font-medium">CNPJ</label>
              <input type="text" id="edit-cnpj" name="cnpj" required
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
            </div>

            <div class="flex flex-col">
              <label for="edit-localizacao" class="text-gray-700 mb-1 text-sm font-medium">Cidade</label>
              <input type="text" id="edit-localizacao" name="localizacao" required
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-gray-800 dark:text-gray-100" />
            </div>
          </div>

          <div class="flex justify-end">
            <button type="submit"
              class="bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg shadow-md hover:shadow-lg transition duration-300">
              Salvar Alterações
            </button>
          </div>
        </form>

      </div>
    </div>



  </div>





  <script>
    function toggleModal() {
      document.getElementById('modal-criar').classList.toggle('hidden');
    }
  </script>

  <script>
    function toggleModal() {
      document.getElementById('modal-criar').classList.toggle('hidden');
    }

    function toggleModalEditar() {
      document.getElementById('modal-editar').classList.toggle('hidden');
    }

    function visualizarProjeto(id, nome, cnpj, localizacao) {
      window.location.href = 'detalhes.php?empresa_id=' + id;
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