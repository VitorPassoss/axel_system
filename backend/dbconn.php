<?php
$host = 'localhost';
$dbname = 'u470175651_axel';
$username = 'u470175651_axel';
$password = '99746510Gg@';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
} catch (Exception $e) {
    die("Erro na conexão: " . $e->getMessage());
}
