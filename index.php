<?php
session_start();

// Verifica se existe o token na sessão
if (isset($_SESSION['token']) && !empty($_SESSION['token'])) {
    // Opcional: aqui você pode adicionar verificação se o token é válido no banco
    header("Location: home/index.php");
    exit();
}

// Se não estiver logado, manda para login
header("Location: onboard/login.php");
exit();
?>
