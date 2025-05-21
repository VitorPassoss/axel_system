<?php
$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
} catch (Exception $e) {
    die("Erro na conexão: " . $e->getMessage());
}
