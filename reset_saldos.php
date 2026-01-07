<?php
require_once 'db_connection.php'; 

// Prueba de conexión
if ($dbConnection->ping()) {
    echo "Conexión exitosa a la base de datos.<br>";
} else {
    die("Error de conexión: " . $dbConnection->error);
}

// Consulta para reiniciar el saldo de todos los usuarios a cero
$query = "UPDATE usuario SET Saldo = 0";

// Ejecuta la consulta
if (mysqli_query($dbConnection, $query)) {
    echo "Saldo de todos los usuarios reiniciado a 0 correctamente.";
} else {
    echo "Error al reiniciar el saldo: " . mysqli_error($dbConnection);
}
?>
