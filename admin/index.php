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

<body class="bg-dark min-h-screen flex items-center justify-center p-4">

  <form method="POST" action="../backend/login.php" id="loginForm" class="bg-gray-900 p-8 rounded-2xl shadow-2xl w-full max-w-md text-white">
    <h2 class="text-3xl font-bold mb-8 text-center">Admin</h2>

    <div class="mb-6">
      <label class="block mb-2 text-sm font-semibold">Email</label>
      <input 
        type="email" 
        name="email" 
        class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition" 
        required
      >
    </div>

    <div class="mb-8">
      <label class="block mb-2 text-sm font-semibold">Senha</label>
      <input 
        type="password" 
        name="senha" 
        id="senha" 
        class="w-full p-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition" 
        required
        minlength="6"
      >
      <small class="text-gray-400 mt-1 block">Sua senha deve ter pelo menos 6 caracteres.</small>
    </div>


    <button 
      type="submit" 
      class="w-full bg-primary hover:bg-green-600 py-3 rounded-lg font-semibold transition text-white"
    >
      Entrar
    </button>

    <p id="formError" class="mt-4 text-center text-red-400 hidden"></p>

  </form>

  <script>
    const form = document.getElementById('loginForm');
    const senhaInput = document.getElementById('senha');
    const formError = document.getElementById('formError');

    form.addEventListener('submit', function (e) {
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
