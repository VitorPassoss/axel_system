<?php include '../backend/auth.php'; ?>
<?php include '../layout/imports.php'; ?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            background: '#f8f9fa',
            primary: '#1f2937',
            accent: '#3b82f6'
          }
        }
      }
    }
  </script>
</head>

<body class="bg-background text-primary min-h-screen flex">

  <!-- Side Menu -->
  <?php include '../layout/sidemenu.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 p-8 overflow-y-auto">
  <div class="flex mb-4 flex-row justify-between items-center justify-center shadow  bg-[#FFFFFF] py-4 px-6 rounded-2xl ">
      <div class="">
        <h1 class="text-3xl font-bold text-primary">Ações Rapidas</h1>
      </div>



    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

      <!-- Card: Emitir O.S. -->
      <a href="../os/" class="bg-white hover:shadow-lg transition-all rounded-xl p-10 flex items-start gap-4 border border-gray-200">
        <div class="p-3 bg-blue-100 text-blue-600 rounded-full">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
          </svg>
        </div>
        <div>
          <h2 class="text-lg font-medium">Emitir O.S.</h2>
          <p class="text-sm text-gray-500">Criar nova ordem de serviço</p>
        </div>
      </a>

      <!-- Card: Solicitar Compra -->
      <a href="../recursos/" class="bg-white hover:shadow-lg transition-all rounded-xl p-10 flex items-start gap-4 border border-gray-200">
        <div class="p-3 bg-green-100 text-green-600 rounded-full">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <div>
          <h2 class="text-lg font-medium">Solicitar Compra</h2>
          <p class="text-sm text-gray-500">Abrir nova solicitação</p>
        </div>
      </a>

      <!-- Card: Gerar Cotação -->
      <a href="../cotacao" class="bg-white hover:shadow-lg transition-all rounded-xl p-10 flex items-start gap-4 border border-gray-200">
        <div class="p-3 bg-purple-100 text-purple-600 rounded-full">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 00-4-4H3m0 0V7a4 4 0 014-4h10a4 4 0 014 4v4m-4 4h2a4 4 0 014 4v2m0 0H9"></path>
          </svg>
        </div>
        <div>
          <h2 class="text-lg font-medium">Gerar Cotação</h2>
          <p class="text-sm text-gray-500">Criar cotação com base na O.S.</p>
        </div>
      </a>


    </div>
  </main>

</body>

</html>