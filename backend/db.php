<?php
try {
    $host = 'localhost';
    $dbname = 'u470175651_zion2';
    $username = 'u470175651_zion2';
    $password = '99746510Gg@';



    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
