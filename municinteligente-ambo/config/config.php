<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'municinteligente_user');
define('DB_PASS', 'Seguro123!');
define('DB_NAME', 'municinteligente_ambo');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error al conectar con la base de datos. Por favor, inténtelo más tarde.");
}
?>