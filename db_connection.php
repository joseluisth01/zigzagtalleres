<?php
require_once 'config.php';

// Crear conexion a la base de datos
$dbConnection = new mysqli($db_host, $db_user, $db_password, $db_name);
$dbConnection->set_charset("utf8");

// Verificar la conexion
if ($dbConnection->connect_error) {
    die("Error de conexion: " . $dbConnection->connect_error);
}
?>
