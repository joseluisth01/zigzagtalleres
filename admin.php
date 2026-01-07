<?php
session_start();
require_once 'db_connection.php'; // Ajusta la ruta según sea necesario

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['user_tipo'] == 2) { // usuario
    header("Location: mistalleres.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'addUser') {
            $nombre = $_POST["nombre"];
            $DNI = $_POST["DNI"];
            $telefono = $_POST["telefono"];
            $codigoPostal = $_POST["codigo_postal"];
            $contrasena = md5("zigzag");
            $sql = "INSERT INTO usuario (Nombre, TipoUsuarioID, Activo, Contrasena, Telefono, CodigoPostal, DNI) VALUES ('$nombre', 2, 1, '$contrasena', '$telefono', '$codigoPostal', '$DNI')";
            //mysqli_query($dbConnection, $sql);
            if (mysqli_query($dbConnection, $sql)) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['error' => mysqli_error($dbConnection)]);
                exit();
            }
            exit();
        } elseif ($action === 'toggleUser') {
            $userId = $_POST['userId'];
            $userStatus = $_POST['userStatus'];
            toggleUserStatus($userId, $userStatus, $dbConnection);
            echo json_encode(['success' => true]);
            exit();
        } elseif ($action === 'addTaller') {
            $nombre = $_POST["nombre_taller"];
            $idProfesor = $_POST["profesor_id"];
            $sql = "INSERT INTO taller (Nombre, IDProfesor) VALUES ('$nombre', '$idProfesor')";

            if (mysqli_query($dbConnection, $sql)) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['error' => mysqli_error($dbConnection)]);
                exit();
            }
        } elseif ($action === 'editarFestivo') {
            $festivoId = $_POST["festivoId"];
            $fecha = $_POST["fecha"];

            $sql = "UPDATE festivos SET Fecha = '$fecha' WHERE FestivoID = $festivoId";

            if (mysqli_query($dbConnection, $sql)) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['error' => mysqli_error($dbConnection)]);
                exit();
            }
        } elseif ($action === 'eliminarFestivo') {
            $festivoId = $_POST["festivoId"];

            $sql = "DELETE FROM festivos WHERE FestivoID = $festivoId";

            if (mysqli_query($dbConnection, $sql)) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['error' => mysqli_error($dbConnection)]);
                exit();
            }
        } elseif ($action === 'restorePassword') {
            $userId = $_POST['userId'];
            $newPassword = md5("zigzag"); // La nueva contraseña por defecto

            $query = "UPDATE usuario SET Contrasena = '$newPassword' WHERE UsuarioID = $userId";

            if (mysqli_query($dbConnection, $query)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($dbConnection)]);
            }
            exit();
        } elseif ($action === 'addHorario') {
            $tallerID = $_POST['taller_id'];
            $diaSemana = $_POST['dia_semana'];
            $horaInicio = $_POST['hora_inicio'];
            $horaFin = $_POST['hora_fin'];
            $limite = $_POST['limite'];

            // Obtener todas las fechas de los lunes en el año 2024
            $year = date('Y');
            $start_date = new DateTime("{$year}-01-01");
            $end_date = new DateTime("{$year}-12-31");

            $interval = new DateInterval('P1D'); // Periodo de 1 día
            $period = new DatePeriod($start_date, $interval, $end_date);
            $dia = 1;

            switch ($diaSemana) {
                case "Lunes":
                    $dia = 1;
                    break;
                case "Martes":
                    $dia = 2;
                    break;
                case "Miercoles":
                    $dia = 3;
                    break;
                case "Jueves":
                    $dia = 4;
                    break;
                case "Viernes":
                    $dia = 5;
                    break;
                case "Sabado":
                    $dia = 6;
                    break;
                case "Domingo":
                    $dia = 7;
                    break;
                default:
                    $dia = 0; // Valor por defecto si no coincide con ninguno de los días esperados
                    break;
            }

            $mondays = [];
            foreach ($period as $date) {
                if ($date->format('N') == $dia) { // 1 representa lunes en ISO-8601
                    $mondays[] = $date->format('Y-m-d');
                }
            }
?>

<?php
            $insertHorario = "INSERT INTO horario (TallerID, DiaSemana, HoraInicio, HoraFin, Limite) VALUES ($tallerID, '$diaSemana', '$horaInicio', '$horaFin', $limite)";
            if (mysqli_query($dbConnection, $insertHorario)) {
                $horarioID = mysqli_insert_id($dbConnection);
                foreach ($mondays as $monday) {
                    $insertFechas = "INSERT INTO fechas (HorarioID, Fecha) VALUES ($horarioID, '$monday')";
                    if (mysqli_query($dbConnection, $insertFechas)) {
                        echo "Fecha añadida correctamente para $monday<br>";
                    } else {
                        echo 'Error al añadir fecha: ' . mysqli_error($dbConnection);
                    }
                }
            } else {
                echo 'Error al añadir horario: ' . mysqli_error($dbConnection);
            }
        } elseif ($action === 'eliminarHorario') {
            $horarioId = $_POST['horarioId'];

            // Eliminar inscripciones
            $queryEliminarInscripciones = "DELETE FROM inscripcion WHERE HorarioID = $horarioId";
            if (mysqli_query($dbConnection, $queryEliminarInscripciones)) {
                // Eliminar fechas
                $queryEliminarFechas = "DELETE FROM fechas WHERE HorarioID = $horarioId";
                if (mysqli_query($dbConnection, $queryEliminarFechas)) {
                    // Eliminar horario
                    $queryEliminarHorario = "DELETE FROM horario WHERE HorarioID = $horarioId";
                    if (mysqli_query($dbConnection, $queryEliminarHorario)) {
                        echo "Horario, fechas e inscripciones eliminados correctamente.";
                    } else {
                        echo "Error al eliminar el horario: " . mysqli_error($dbConnection) . " (Consulta: $queryEliminarHorario)";
                    }
                } else {
                    echo "Error al eliminar las fechas: " . mysqli_error($dbConnection) . " (Consulta: $queryEliminarFechas)";
                }
            } else {
                echo "Error al eliminar las inscripciones: " . mysqli_error($dbConnection) . " (Consulta: $queryEliminarInscripciones)";
            }
        } elseif ($action === 'addFestivo') {
            $fechaFestivo = $_POST["fecha"];

            $sql = "INSERT INTO festivos (Fecha) VALUES ('$fechaFestivo')";

            if (mysqli_query($dbConnection, $sql)) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['error' => mysqli_error($dbConnection)]);
                exit();
            }
        } elseif ($action === 'loadHorarios') {
            // Código PHP para cargar horarios según el taller
            $tallerID = $_POST['taller_id'];
            $queryHorarios = "SELECT HorarioID, CONCAT(DiaSemana, ' - ', HoraInicio, ' - ', HoraFin) AS HorarioDescripcion FROM horario WHERE TallerID = $tallerID";
            $resultHorarios = mysqli_query($dbConnection, $queryHorarios);

            $options = '';
            while ($rowHorario = mysqli_fetch_assoc($resultHorarios)) {
                $options .= '<option value="' . $rowHorario['HorarioID'] . '">' . $rowHorario['HorarioDescripcion'] . '</option>';
            }

            echo $options;
            exit();
        } elseif ($action === 'addInscripcion') {
            $usuarioID = $_POST['select_usuario'];
            $tallerID = $_POST['select_taller'];
            $horarioID = $_POST['horario_id'];

            // Obtener las fechas asociadas a ese horario
            $queryFechas = "SELECT IDFecha FROM fechas WHERE HorarioID = $horarioID";
            $resultFechas = mysqli_query($dbConnection, $queryFechas);

            if ($resultFechas) {
                while ($row = mysqli_fetch_assoc($resultFechas)) {
                    $fechaID = $row['IDFecha'];

                    // Insertar la inscripción para cada fecha
                    $queryInsert = "INSERT INTO inscripcion (UsuarioID, TallerID, HorarioID, FechaID) VALUES ($usuarioID, $tallerID, $horarioID, $fechaID)";

                    if (mysqli_query($dbConnection, $queryInsert)) {
                        echo "Inscripción exitosa para la fecha con ID $fechaID<br>";
                    } else {
                        echo "Error al inscribir usuario en taller: " . mysqli_error($dbConnection);
                    }
                }

                echo "Todas las fechas han sido procesadas correctamente";
            } else {
                echo "Error al obtener fechas asociadas al horario: " . mysqli_error($dbConnection);
            }

            exit();
        }elseif ($action === 'addMultipleInscripcion') {
    $usuarios = $_POST['usuarios'];
    $tallerId = $_POST['taller_id'];
    $horarioId = $_POST['horario_id'];
    
    $successCount = 0;
    $skippedCount = 0;
    $errorCount = 0;
    
    // Obtener todas las fechas asociadas a ese horario
    $queryFechas = "SELECT IDFecha FROM fechas WHERE HorarioID = $horarioId";
    $resultFechas = mysqli_query($dbConnection, $queryFechas);
    
    if ($resultFechas) {
        // Para cada usuario seleccionado
        foreach ($usuarios as $usuarioId) {
            // Para cada fecha del horario
            mysqli_data_seek($resultFechas, 0); // Reiniciar el puntero del resultado
            while ($row = mysqli_fetch_assoc($resultFechas)) {
                $fechaID = $row['IDFecha'];
                
                // Verificar si ya está inscrito en esta fecha específica
                $checkQuery = "SELECT COUNT(*) as count FROM inscripcion WHERE UsuarioID = ? AND TallerID = ? AND HorarioID = ? AND FechaID = ?";
                $checkStmt = mysqli_prepare($dbConnection, $checkQuery);
                mysqli_stmt_bind_param($checkStmt, "iiii", $usuarioId, $tallerId, $horarioId, $fechaID);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                $existingCount = mysqli_fetch_assoc($checkResult)['count'];
                
                if ($existingCount == 0) {
                    // Insertar inscripción para esta fecha
                    $insertQuery = "INSERT INTO inscripcion (UsuarioID, TallerID, HorarioID, FechaID) VALUES (?, ?, ?, ?)";
                    $insertStmt = mysqli_prepare($dbConnection, $insertQuery);
                    mysqli_stmt_bind_param($insertStmt, "iiii", $usuarioId, $tallerId, $horarioId, $fechaID);
                    
                    if (mysqli_stmt_execute($insertStmt)) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $skippedCount++;
                }
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al obtener fechas del horario']);
        exit();
    }
    
    echo json_encode([
        'success' => true, 
        'inscribed' => $successCount, 
        'skipped' => $skippedCount,
        'errors' => $errorCount
    ]);
    exit();
}
        
        elseif ($action === 'eliminarTaller') {
            $tallerID = $_POST['tallerID'];

            // Realizar la eliminación del taller y registros relacionados
            $sql = "DELETE FROM fechas
            WHERE HorarioID IN (SELECT HorarioID FROM horario WHERE TallerID = $tallerID)
            ";
            if (mysqli_query($dbConnection, $sql)) {
                $sql = "DELETE FROM horario WHERE TallerID = $tallerID";
                if (mysqli_query($dbConnection, $sql)) {
                    $sql = "DELETE FROM inscripcion WHERE TallerID = $tallerID";
                    if (mysqli_query($dbConnection, $sql)) {
                        $sql = "DELETE FROM taller WHERE TallerID = $tallerID";
                        if (mysqli_query($dbConnection, $sql)) {
                            echo json_encode(['success' => true]); // Indicar que la eliminación fue exitosa
                            exit();
                        } else {
                            echo json_encode(['error' => 'Error al eliminar el taller: ' . mysqli_error($dbConnection)]);
                            exit();
                        }
                    } else {
                        echo json_encode(['error' => 'Error al eliminar las inscripciones']);
                        exit();
                    }
                } else {
                    echo json_encode(['error' => $sql]);
                    exit();
                }
            } else {
                echo json_encode(['error' => $sql]);
                exit();
            }
        } elseif ($action === 'editarUsuario') {
            $userId = $_POST['userId'];
            $nombre = $_POST['nombre'];
            $dni = $_POST['DNI'];
            $telefono = $_POST['telefono'];
            $codigoPostal = $_POST['codigoPostal'];
            $tipoUsuario = $_POST['tipoUsuario'];
            $saldo = $_POST['saldo']; // Nuevo campo de saldo

            $query = "UPDATE usuario SET Nombre = '$nombre', DNI = '$dni', Telefono = '$telefono', CodigoPostal = '$codigoPostal', TipoUsuarioID = '$tipoUsuario', Saldo = '$saldo' WHERE UsuarioID = $userId";

            if (mysqli_query($dbConnection, $query)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($dbConnection)]);
            }
            exit();
        } elseif ($action === 'editarTaller') {
            $tallerId = $_POST['tallerId'];
            $nombreTaller = $_POST['nombreTaller'];
            $idProfesor = $_POST['profesorId'];

            $query = "UPDATE taller SET Nombre = '$nombreTaller', IDProfesor = '$idProfesor' WHERE TallerID = $tallerId";


            if (mysqli_query($dbConnection, $query)) {
                echo json_encode($query);
            } else {
                echo json_encode($query);
            }
        } elseif ($action === 'editarHorario') {
            $horarioId = $_POST['horarioId'];
            $diaSemana = $_POST['diaSemana'];
            $horaInicio = $_POST['horaInicio'];
            $horaFin = $_POST['horaFin'];
            $limite = $_POST['limite'];

            $queryUpdateHorario = "UPDATE horario SET DiaSemana = '$diaSemana', HoraInicio = '$horaInicio', HoraFin = '$horaFin', Limite = '$limite' WHERE HorarioID = $horarioId";

            if (mysqli_query($dbConnection, $queryUpdateHorario)) {
                // Obtener todas las fechas de ese día del año 2024
                $year = 2024;
                $fechas = obtenerFechasDeDia($diaSemana, $year);

                // Eliminar las fechas existentes y luego insertar las nuevas
                $queryEliminarFechas = "DELETE FROM fechas WHERE HorarioID = $horarioId";
                mysqli_query($dbConnection, $queryEliminarFechas);

                foreach ($fechas as $fecha) {
                    $insertFechas = "INSERT INTO fechas (HorarioID, Fecha) VALUES ($horarioId, '$fecha')";
                    if (mysqli_query($dbConnection, $insertFechas)) {
                        echo "Fecha actualizada correctamente para $fecha<br>";
                    } else {
                        echo 'Error al actualizar fecha: ' . mysqli_error($dbConnection);
                    }
                }

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($dbConnection)]);
            }
        } elseif ($action === 'eliminarInscripcion') {
            $userid = $_POST['userid'];
            $tallerid = $_POST['tallerid'];

            // Construir la consulta DELETE
            $queryDelete = "DELETE FROM inscripcion WHERE UsuarioID = $userid AND TallerID = $tallerid";

            // Ejecutar la consulta DELETE
            if (mysqli_query($dbConnection, $queryDelete)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $queryDelete]);
            }

            exit();
        } elseif ($action === 'editarInscripcion') {
            $inscripcionId = $_POST['inscripcionId'];
            $usuarioID = $_POST['select_usuario'];
            $tallerID = $_POST['select_taller'];
            $horarioID = $_POST['horario_id'];

            $queryUpdate = "UPDATE inscripcion SET UsuarioID = $usuarioID, TallerID = $tallerID, HorarioID = $horarioID WHERE InscripcionID = $inscripcionId";

            if (mysqli_query($dbConnection, $queryUpdate)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        } elseif ($_POST['action'] === 'updateNormas') {
            $normas = $_POST['normas-textarea'];

            // Asegúrate de que solo afecte la fila con IDNormas = 1
            $updateQuery = "UPDATE admin SET Normas = '$normas' WHERE IDNormas = 1";

            if (mysqli_query($dbConnection, $updateQuery)) {
                echo json_encode($normas);
                exit();
            } else {
                echo json_encode(['error' => mysqli_error($dbConnection)]);
                exit();
            }
        } elseif ($_POST['action'] === 'actualizarPassword') {
            $usuario_id_pass = $_SESSION['user_id'];
            $nuevaContrasenapassword = $_POST["nuevaContrasenapassword"];
            $hashContrasena = md5($nuevaContrasenapassword);
            $query = "UPDATE usuario SET Contrasena = '$hashContrasena' WHERE UsuarioID = $usuario_id_pass";
            ob_clean();
            header('Content-Type: application/json');
            if (mysqli_query($dbConnection, $query)) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['query' => $query]);
                exit();
            }
        } elseif ($action === 'eliminarUsuarioDeTaller') {
            $usuarioNombre = $_POST['usuario'];
            $horarioID = $_POST['horarioID'];
            $fechaID = $_POST['fechaID'];

            // Obtener el UsuarioID basado en el nombre
            $queryUsuario = "SELECT UsuarioID FROM usuario WHERE Nombre = '$usuarioNombre'";
            $resultUsuario = mysqli_query($dbConnection, $queryUsuario);

            if ($resultUsuario && $rowUsuario = mysqli_fetch_assoc($resultUsuario)) {
                $usuarioID = $rowUsuario['UsuarioID'];

                // Eliminar la inscripción del usuario
                $queryDelete = "DELETE FROM inscripcion WHERE UsuarioID = $usuarioID AND HorarioID = $horarioID AND FechaID = $fechaID";
                if (mysqli_query($dbConnection, $queryDelete)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => mysqli_error($dbConnection)]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
            }
            exit();
        } elseif ($_POST['action'] === 'cerrarSesion') {
            // Cerrar la sesión actual
            session_destroy();

            echo json_encode(['success' => true]);
        }
    }
}

function toggleUserStatus($userId, $active, $dbConnection)
{
    $status = $active ? 0 : 1;
    $sql = "UPDATE usuario SET Activo = $status WHERE UsuarioID = $userId";
    mysqli_query($dbConnection, $sql);
}
?>

<?php
// Consulta para saber si la contraseá se cambió
$usuario_id = $_SESSION['user_id'];
$consultaPass = "SELECT Contrasena FROM usuario WHERE UsuarioID = $usuario_id";
$resultadoPass = mysqli_query($dbConnection, $consultaPass);
$registroPass = mysqli_fetch_assoc($resultadoPass);
$passUsuario = $registroPass['Contrasena'];
// Compara la contraseña almacenada con la versión MD5 de "zigzag"
$md5_zigzag = md5("zigzag");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Panel de Administración</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="custom.min.css">
    <script>
        $(document).ready(function() {
            // Verifica si ya se ha recargado la página en esta sesión
            if (!sessionStorage.getItem('recargada')) {
                // Si no se ha recargado, forzamos la recarga
                sessionStorage.setItem('recargada', 'true');
                window.location.reload(true); // Forzar la recarga y evitar caché
            }
        });
    </script>
    <script>
        // Este script fuerza la recarga de la página si el usuario ha llegado aquí desde una redirección
        if (performance.navigation.type === 1) {
            // No hacer nada si la página ya fue recargada
        } else {
            // Forzar la recarga completa (sin caché)
            window.location.reload(true);
        }
    </script>

</head>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css" />
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>
<script src="https://cdn.tiny.cloud/1/nd8kwloujlujkovbejxpqnpp0qz6r1lbio6kxo2vscn5csto/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<body class="admin-panel">
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebar" class="col-12 col-md-3 d-md-block sidebar py-4">
                <div class="position-sticky">
                    <div class="text-center mb-3">
                        <img class="logozig" src="logo.webp" alt="logo">
                    </div>
                    <ul class="nav flex-column">

                        <li class="nav-item">
                            <a class="nav-link active" href="#calendario">Calendario</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link " href="#usuarios">Usuarios</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#talleres">Talleres</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#horarios">Horarios</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#inscripciones">Inscripciones</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#festivos">Festivos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#normas">Normas</a>
                        </li>
                        <li class="nav-item">
                            <button id="cerrar-sesion-btn" class="btn btn-danger d-inline-block w-auto mt-5">Cerrar Sesión</button>
                        </li>
                    </ul>
                </div>
            </nav>

            <main id="administrar" class="col-12 col-md-9 ms-sm-auto px-md-4 py-4">
                <section id="usuarios">
                    <?php
                    include("admintabs/usuarios.php");
                    ?>
                </section>
                <section id="talleres">
                    <?php
                    include("admintabs/talleres.php");
                    ?>
                </section>
                <section id="horarios">
                    <?php
                    include("admintabs/horarios.php");
                    ?>
                </section>
                <section id="inscripciones">
                    <?php
                    include("admintabs/inscripciones.php");
                    ?>
                </section>
                <section id="calendario" class="active">
                    <?php
                    include("admintabs/calendario.php");
                    ?>
                </section>
                <section id="festivos">
                    <?php
                    include("admintabs/festivos.php");
                    ?>
                </section>
                <section id="normas">
                    <?php
                    include("admintabs/normas.php");
                    ?>
                </section>

            </main>
        </div>
    </div>


    <script>
        $(document).ready(function() {
            //$('#listado_usuarios').DataTable();
            //$('#table_talleres').DataTable();
            //$('#table_horarios').DataTable();
            //$('#tabla_inscripciones').DataTable();
        });
        //cerrar sesion
        const cerrarSesionButton = $("#cerrar-sesion-btn");

        cerrarSesionButton.on("click", function() {
            // Realiza la operación de cierre de sesión
            $.ajax({
                url: "admin.php", // Reemplaza "cerrar_sesion.php" con la URL del script de cierre de sesión
                type: "POST",
                data: {
                    action: "cerrarSesion"
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Redirige o recarga la página
                        location.reload(); // Esto recargará la página actual
                    } else {
                        alert("Error al cerrar sesión");
                        location.reload(); // Esto recargará la página actual
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log("Error en la llamada AJAX: " + textStatus, errorThrown);
                    location.reload(); // Esto recargará la página actual
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Cuando se haga clic en el botón "Guardar Cambios" del formulario de contraseña
            $("#guardar-cambios-password-btn").click(function() {
                // Obtener los valores de los campos
                var nuevaContrasenapassword = $("#nueva_contrasena_password").val();
                var confirmarContrasenapassword = $("#confirmar_contrasena_password").val();

                // Validar que las contraseñas coincidan
                if (nuevaContrasenapassword !== confirmarContrasenapassword) {
                    alert("Las contraseñas no coinciden. Por favor, inténtelo de nuevo.");
                    return false; // Evita que el formulario se envíe
                }
                console.log(nuevaContrasenapassword);
                // Realiza la solicitud AJAX para actualizar los datos del usuario
                const formData = new FormData();
                formData.append('action', 'actualizarPassword');
                formData.append('nuevaContrasenapassword', nuevaContrasenapassword);
                $.ajax({
                    url: "admin.php",
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response.success) {
                            alert("Datos actualizados correctamente.");
                            location.reload();
                        } else {
                            console.log(response);
                            alert("Error al actualizar datos: ");
                            location.reload();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                });

                // Aquí puedes cerrar el modal si es necesario
                //$("#formulario-edicion-password").modal("hide");
            });
        });
    </script>
    <?php
    if ($passUsuario) {

        if ($passUsuario === $md5_zigzag) {
            // Si la contraseña coincide, muestra el formulario de cambio de contraseña
            echo '<script>
                    $(document).ready(function(){
                        $("#formulario-edicion-password").modal("show");
                    });
                  </script>';
        }
    }
    ?>
    <form id="edit-user-form" class="modal fade" tabindex="-1" aria-labelledby="formulario-edicionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title" id="formulario-edicionEditarLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <img id="foto_usuario" class="mb-3" width="200px" height="200px" style="object-fit:contain;" src="">
                <input type="hidden" id="edit-user-id" name="edit-user-id">
                <div class="mb-3">
                    <label for="edit-nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="edit-nombre" name="edit-nombre" required>
                </div>
                <div class="mb-3">
                    <label for="edit-DNI" class="form-label">DNI</label>
                    <input type="text" class="form-control" id="edit-DNI" name="edit-DNI" required>
                </div>
                <div class="mb-3">
                    <label for="edit-telefono" class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="edit-telefono" name="edit-telefono" required>
                </div>
                <div class="mb-3">
                    <label for="edit-codigo_postal" class="form-label">Código Postal</label>
                    <input type="text" class="form-control" id="edit-codigo_postal" name="edit-codigo_postal" required>
                </div>
                <div class="mb-3">
                    <label for="edit-saldo" class="form-label">Saldo</label>
                    <input type="number" class="form-control" id="edit-saldo" name="edit-saldo" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label for="edit-tipo-usuario" class="form-label">Tipo de Usuario</label>
                    <select class="form-select" id="edit-tipo-usuario" name="edit-tipo-usuario" required>
                        <option value="2">Alumno</option>
                        <option value="3">Profesor</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <button type="button" class="btn btn-warning" id="restore-password-btn">Restaurar Contraseña</button>
            </div>
        </div>
    </form>



    <form id="edit-taller-form" class="modal fade" tabindex="-1" aria-labelledby="formulario-edicionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title" id="formulario-edicionEditarLabel">Editar Taller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <input type="hidden" id="edit-taller-id" name="edit-taller-id">
                <div class="mb-3">
                    <label for="edit-nombre_taller" class="form-label">Nombre del Taller</label>
                    <input type="text" class="form-control" id="edit-nombre_taller" name="edit-nombre_taller" required>
                </div>
                <div class="mb-3">
                    <label for="edit-profesor_id" class="form-label">Profesor</label>
                    <select class="form-control" id="edit-profesor_id" name="edit-profesor_id">
                        <?php foreach ($profesores as $profesor) : ?>
                            <option value="<?= $profesor['UsuarioID'] ?>"><?= $profesor['Nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </form>

    <?php if ($passUsuario === $md5_zigzag) { ?>
        <div id="formulario-edicion-password" class="modal fade" tabindex="-1" aria-labelledby="formulario-edicion-password-Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="formulario-edicion-password-EditarLabel">Cambia la contraseña</h5>
                        <button type="button" class="btn-close d-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="form-edicion" class="mb-3">
                            <div class="mb-3">
                                <label for="nueva_contrasena_password" class="form-label">Nueva Contraseña:</label>
                                <input type="password" class="form-control" id="nueva_contrasena_password" name="nueva_contrasena_password">
                            </div>
                            <div class="mb-3">
                                <label for="confirmar_contrasena_password" class="form-label">Confirmar Contraseña:</label>
                                <input type="password" class="form-control" id="confirmar_contrasena_password" name="confirmar_contrasena_password">
                            </div>
                            <button type="button" class="btn btn-primary" id="guardar-cambios-password-btn">Guardar Cambios</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>



</body>
<?php //include 'scripts.php';
function obtenerNumeroDiaSemana($diaSemana)
{
    switch ($diaSemana) {
        case "Lunes":
            return 1;
        case "Martes":
            return 2;
        case "Miércoles":
            return 3;
        case "Jueves":
            return 4;
        case "Viernes":
            return 5;
        case "Sábado":
            return 6;
        case "Domingo":
            return 7;
        default:
            return 0;
    }
}

function obtenerFechasDeDia($diaSemana, $year)
{
    $start_date = new DateTime("{$year}-01-01");
    $end_date = new DateTime("{$year}-12-31");

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start_date, $interval, $end_date);
    $dia = obtenerNumeroDiaSemana($diaSemana);

    $fechas = [];
    foreach ($period as $date) {
        if ($date->format('N') == $dia) {
            $fechas[] = $date->format('Y-m-d');
        }
    }

    return $fechas;
}
?>

</html>