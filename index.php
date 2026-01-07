<?php
session_start();
require_once 'db_connection.php';
// WW
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_tipo'] == 1 || $_SESSION['user_tipo'] == 3) { // Administrador
        header("Location: admin.php");
        exit();
    } elseif ($_SESSION['user_tipo'] == 2) { // Alumno
        header("Location: mistalleres.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = md5($_POST["password"]);

    $sql = "SELECT * FROM usuario WHERE DNI = ?";
    $stmt = $dbConnection->prepare($sql);

if (!$stmt) {
    die('Error en la preparación de la consulta: ' . $dbConnection->error);
}

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($password == $user['Contrasena']) {
            $_SESSION['user_name'] = $user['Nombre'];
            $_SESSION['user_id'] = $user['UsuarioID'];
            $_SESSION['user_tipo'] = $user['TipoUsuarioID'];
            if ($user['TipoUsuarioID'] == 1 || $user['TipoUsuarioID'] == 3) { // Administrador
                header("Location: admin.php");
                exit();
            } elseif ($user['TipoUsuarioID'] == 2) { // Alumno
                header("Location: mistalleres.php");
                exit();
            }
        } else {
            $error_message = "contraseña - Credenciales inválidas.";
        }
    } else {
        $error_message = "Sin resultados - Credenciales inválidas.";
    }
}

// Mostrar el formulario de inicio de sesión
?>
<!DOCTYPE html>
<html>
<head>
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="custom.min.css">

</head>
<body>
    <div class="login container mt-5">
        <div class="text-center mb-3">
            <img class="logozig" src="logo.webp" alt="logo">
        </div>
        <h1 class="mb-3">Iniciar Sesión</h1>
        <?php if (!empty($error_message)) { ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php } ?>
        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario:</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña:</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>
