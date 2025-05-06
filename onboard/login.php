<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            dark: '#1f2937', // gray-800
            primary: '#3b82f6', // blue-500
          }
        }
      }
    }
  </script>
</head>

<body class="bg-white  flex items-center justify-center ">

  <form method="POST" action="../backend/login.php" id="loginForm" class="   p-8 rounded-2xl  w-full max-w-md ">
    <div class="flex flex-col justify-center items-center">

      <img class="w-[140px] " src="https://i.etsystatic.com/25290379/r/il/19a886/2974258879/il_fullxfull.2974258879_pxm3.jpg" alt="">

      <h2 class="text-3xl font-bold mb-8 text-center">Zion Managment </h2>

    </div>
    <div class="mb-6">
      <label class="block mb-2 text-sm font-semibold">Email</label>
      <input
        type="email"
        name="email"
        class="w-full p-3 bg-gray-200 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition"
        required>
    </div>

    <div class="mb-4">
      <label class="block mb-2 text-sm font-semibold">Senha</label>
      <input
        type="password"
        name="senha"
        id="senha"
        class="w-full p-3 bg-gray-200 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition"
        required
        minlength="6">
      <small class="text-gray-500 mt-1 block">Sua senha deve ter pelo menos 6 caracteres.</small>
    </div>



    <button
      type="submit"
      class="w-full bg-black hover:bg-white hover:text-black hover:border hover:border-[2px]  py-3 rounded-lg font-semibold transition text-white">
      Entrar
    </button>


    <button
      onclick="primeiroAcesso()"
      class="w-full bg-[white] mt-2 border border-[2px] border-black-400 hover:bg-black hover:text-white py-3 rounded-lg font-semibold transition text-black">
      Primeiro Acesso
    </button>



    <p id="formError" class="mt-4 text-center text-red-400 hidden"></p>

  </form>

  <script>


    const primeiroAcesso = () => {
      window.location.href = './cadastro.php'
    }

    const form = document.getElementById('loginForm');
    const senhaInput = document.getElementById('senha');
    const formError = document.getElementById('formError');

    form.addEventListener('submit', function(e) {
      formError.classList.add('hidden');
      if (senhaInput.value.length < 6) {
        e.preventDefault();
        formError.textContent = "Senha muito curta. Mínimo 6 caracteres.";
        formError.classList.remove('hidden');
      }
    });
  </script>

</body>

</html>