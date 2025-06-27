<?php
    $host = 'localhost';
    $dbname = 'u470175651_zion2';
    $username = 'u470175651_zion2';
    $password = '99746510Gg@';


try {
    $conn = new mysqli($host, $username, $password, $dbname);
} catch (Exception $e) {
    die("Erro na conexão: " . $e->getMessage());
}
