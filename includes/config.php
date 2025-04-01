<?php
$host = 'localhost:3306';
$db   = 'terap.ia';  // Altere para o nome do seu banco de dados
$user = 'root';        // Altere para o seu usuário do MySQL
$pass = '';          // Altere para a sua senha do MySQL
$charset = 'utf8';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>
