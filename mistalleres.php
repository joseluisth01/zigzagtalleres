<?php
session_start();
require_once 'db_connection.php'; // Ajusta la ruta según sea necesario

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['user_tipo'] == 1  || $_SESSION['user_tipo'] == 3) { // Administrador
    header("Location: admin.php");
    exit();
}
$userID = $_SESSION['user_id'];
$nombreUsuario = $_SESSION['user_name'];

// Consulta para saber si la contraseá se cambió
$consultaPass = "SELECT Contrasena FROM usuario WHERE UsuarioID = $userID";
$resultadoPass = mysqli_query($dbConnection, $consultaPass);
$registroPass = mysqli_fetch_assoc($resultadoPass);
$passUsuario = $registroPass['Contrasena'];
// Compara la contraseña almacenada con la versión MD5 de "zigzag"
$md5_zigzag = md5("zigzag");




// Consulta para obtener el saldo del usuario
$consultaSaldo = "SELECT Saldo FROM usuario WHERE UsuarioID = $userID";
$resultadoSaldo = mysqli_query($dbConnection, $consultaSaldo);
$registroSaldo = mysqli_fetch_assoc($resultadoSaldo);
$saldoUsuario = $registroSaldo['Saldo'];

// Consulta adicional para verificar si el saldo es mayor que 0
$mensajeSaldo = '';
$opcionInscripcionTemporal = '';

if ($saldoUsuario > 0) {
    $mensajeSaldo = "Saldo de clases disponible : $saldoUsuario";
    $opcionInscripcionTemporal = '<br><br><button class="btn btn-success inscripcion-temporal-btn">Añadir Inscripción Temporal</button>';
} else {
    $mensajeSaldo = "Saldo de clases disponible : $saldoUsuario";
}

function getFoto($dbConnection, $userID)
{
    $sqlgetfoto = "SELECT Foto FROM usuario WHERE UsuarioID = ?";

    $stmt = mysqli_prepare($dbConnection, $sqlgetfoto);
    mysqli_stmt_bind_param($stmt, "i", $userID);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Retorna la foto como un arreglo asociativo
            return $row['Foto'];
        } else {
            // No se encontraron datos
            return null;
        }
    } else {
        // Error en la ejecución de la consulta
        return null;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'cancelarCita') {
            $idFecha = $_POST['idFecha'];
            $idUsuario = $_POST['idUsuario'];

            // Realiza la eliminación de la inscripción
            $sql_delete = "DELETE FROM inscripcion WHERE FechaID = $idFecha AND UsuarioID = $idUsuario";

            // Ejecuta la consulta de eliminación y verifica el resultado
            if (mysqli_query($dbConnection, $sql_delete)) {
                // Éxito: la inscripción fue cancelada
                echo "La clase fue cancelada con éxito";

                // Consulta para obtener el saldo actual del usuario
                $query_saldo = "SELECT Saldo FROM usuario WHERE UsuarioID = $idUsuario";

                // Ejecuta la consulta
                $result_saldo = mysqli_query($dbConnection, $query_saldo);

                // Verifica si la consulta fue exitosa
                if ($result_saldo) {
                    // Obtiene el resultado como un array asociativo
                    $saldo_data = mysqli_fetch_assoc($result_saldo);

                    // Obtiene el saldo del array
                    $saldo_actual = $saldo_data['Saldo'];

                    // Suma 1 al saldo actual
                    $nuevo_saldo = $saldo_actual + 1;

                    // Actualiza el saldo en la base de datos
                    $sql_update_saldo = "UPDATE usuario SET Saldo = $nuevo_saldo WHERE UsuarioID = $idUsuario";

                    // Ejecuta la consulta de actualización del saldo
                    mysqli_query($dbConnection, $sql_update_saldo);
                } else {
                    // Error al obtener el saldo del usuario
                    echo "Error al obtener el saldo del usuario: " . mysqli_error($dbConnection);
                }
            } else {
                // Error en la consulta de eliminación
                echo "Error al cancelar la clase: " . mysqli_error($dbConnection);
            }
        }
        if ($_POST['action'] === 'anadirCita') {
            $idFecha = $_POST['idFecha'];
            $idUsuario = $_POST['idUsuario'];
            $idHorario = $_POST['idHorario'];
            $idTaller = $_POST['idTaller'];
        
            // Comprobar si ya existe una inscripción
            $sql_check = "SELECT COUNT(*) AS count FROM inscripcion WHERE UsuarioID = $idUsuario AND TallerID = $idTaller AND HorarioID = $idHorario AND FechaID = $idFecha";
            $result_check = mysqli_query($dbConnection, $sql_check);
            $row_check = mysqli_fetch_assoc($result_check);
        
            if ($row_check['count'] > 0) {
                // El usuario ya está inscrito en esta clase
                echo "Ya estás inscrito en esta clase.";
                exit();
            }
        
            // Realiza la inserción de la inscripción
            $sql_insert = "INSERT INTO inscripcion (UsuarioID, TallerID, HorarioID, FechaID) VALUES ($idUsuario, $idTaller, $idHorario, $idFecha)";
        
            if (mysqli_query($dbConnection, $sql_insert)) {
                echo "La clase fue añadida con éxito";
        
                $query_saldo = "SELECT Saldo FROM usuario WHERE UsuarioID = $idUsuario";
                $result_saldo = mysqli_query($dbConnection, $query_saldo);
        
                if ($result_saldo) {
                    $saldo_data = mysqli_fetch_assoc($result_saldo);
                    $saldo_actual = $saldo_data['Saldo'];
                    $nuevo_saldo = $saldo_actual - 1;
        
                    $sql_update_saldo = "UPDATE usuario SET Saldo = $nuevo_saldo WHERE UsuarioID = $idUsuario";
                    mysqli_query($dbConnection, $sql_update_saldo);
                } else {
                    echo "Error al obtener el saldo del usuario: " . mysqli_error($dbConnection);
                }
            } else {
                echo "Error al añadir la clase: " . mysqli_error($dbConnection);
            }
        }
         elseif ($_POST['action'] === 'actualizarDatos') {
            // Recopila los datos enviados en la solicitud POST
            $nuevoNombre = $_POST['nombre'];
            $nuevoTelefono = $_POST['telefono'];
            $nuevoCodigoPostal = $_POST['codigoPostal'];
            $nuevaContrasena = $_POST['nuevaContrasena'];

            // Nueva lógica para manejar la carga de la foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
                $directorioDestino = "fotos_usuarios/";

                // Obtener la extensión del archivo
                $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);

                // Crear el nombre de la foto usando el ID del usuario
                $nombreFoto = $userID . "." . $extension;
                $rutaCompleta = $directorioDestino . $nombreFoto;

                if (move_uploaded_file($_FILES["foto"]["tmp_name"], $rutaCompleta)) {
                    // Actualizar la base de datos con la ruta de la foto
                    $query = "UPDATE usuario SET Foto = ? WHERE UsuarioID = ?";
                    $stmt = mysqli_prepare($dbConnection, $query);
                    mysqli_stmt_bind_param($stmt, "si", $nombreFoto, $userID);

                    if (mysqli_stmt_execute($stmt)) {
                        // Éxito al actualizar la base de datos
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error al actualizar los datos en la base de datos']);
                        exit();
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al subir la foto.']);
                    exit();
                }
            }

            // Validaciones
            $errores = [];

            if (empty($nuevoNombre)) {
                $errores[] = "El nombre no puede estar vacío.";
            }


            if (!empty($errores)) {
                echo json_encode(['success' => false, 'message' => 'Errores en los datos: ' . implode(', ', $errores)]);
                exit();
            }
            if ($nuevaContrasena != "") {
                $query = "UPDATE usuario SET Nombre = ?, Telefono = ?, CodigoPostal = ?, Contrasena = ? WHERE UsuarioID = ?";
            } else {
                $query = "UPDATE usuario SET Nombre = ?, Telefono = ?, CodigoPostal = ? WHERE UsuarioID = ?";
            }
            $stmt = mysqli_prepare($dbConnection, $query);

            // Asegúrate de hacer un hash de la nueva contraseña si es necesario
            // Ejemplo de hash con MD5 (no recomendado para contraseñas seguras):
            $hashContrasena = md5($nuevaContrasena);
            if ($nuevaContrasena != "") {
                mysqli_stmt_bind_param($stmt, "ssssi", $nuevoNombre, $nuevoTelefono, $nuevoCodigoPostal, $hashContrasena, $userID);
            } else {
                mysqli_stmt_bind_param($stmt, "sssi", $nuevoNombre, $nuevoTelefono, $nuevoCodigoPostal, $userID);
            }
            //mysqli_stmt_bind_param($stmt, "ssssi", $nuevoNombre, $nuevoTelefono, $nuevoCodigoPostal, $hashContrasena, $userID);

            if (mysqli_stmt_execute($stmt)) {
                // La actualización en la base de datos se realizó con éxito

                // Actualiza la sesión con los nuevos datos (nombre y teléfono en este ejemplo)
                $_SESSION['user_name'] = $nuevoNombre;
                $_SESSION['user_telefono'] = $nuevoTelefono;

                // Puedes realizar otras operaciones de actualización según tu base de datos y lógica de aplicación

                // Luego, envía una respuesta JSON para indicar que la operación se realizó con éxito
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar los datos en la base de datos']);
                exit();
            }/*  */
        } elseif ($_POST['action'] === 'actualizarPassword') {
            $nuevaContrasenapassword = $_POST["nuevaContrasenapassword"];
            $hashContrasena = md5($nuevaContrasenapassword);
            $query = "UPDATE usuario SET Contrasena = '$hashContrasena' WHERE UsuarioID = $userID";
            ob_clean();
            header('Content-Type: application/json');
            if (mysqli_query($dbConnection, $query)) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['success' => false]);
                exit();
            }
        } elseif ($_POST['action'] === 'cerrarSesion') {
            // Cerrar la sesión actual
            session_destroy();

            echo json_encode(['success' => true]);
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Próximos Talleres</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="custom.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales-all.global.min.js'></script>

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

<body id="panel-usuario">
    <div class="container p-3">
        <div class="text-center mb-5">
            <img class="logozig" src="logo.webp" alt="logo">
        </div>
        <h1 class="mb-4">Bienvenid@, <?php echo $nombreUsuario; ?></h1>
        <?php $foto = getFoto($dbConnection, $userID); ?>
        <div class="row d-flex mb-4">
            <button id="cerrar-sesion-btn" class="fw-bold text-white btn btn-danger d-inline-block w-auto me-3">Cerrar Sesión</button>
            <button id="mostrar-formulario-edicion" class="fw-bold text-white btn btn-primary d-inline-block w-auto me-3">Editar mis datos</button>
            <button id="verNormasBtn" class="fw-bold text-white btn btn-warning d-inline-block w-auto">Normas</button>
        </div>
        <?php
        // Asegúrate de tener una conexión a la base de datos aquí
        $queryNormas = "SELECT Normas FROM admin";
        $resultadoNormas = mysqli_query($dbConnection, $queryNormas);
        $normas = '';
        if ($row = mysqli_fetch_assoc($resultadoNormas)) {
            $normas = $row['Normas'];
        }
        ?>
        <div id="normasPopup" class="modal fade" tabindex="-1" aria-labelledby="formulario-normasLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="formulario-edicionEditarLabel">Normas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?= $normas; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="formulario-edicion" class="modal fade" tabindex="-1" aria-labelledby="formulario-edicionLabel" aria-hidden="true">
            <?php
            $query = "SELECT Nombre, Telefono, CodigoPostal FROM usuario WHERE UsuarioID = $userID";
            $result = mysqli_query($dbConnection, $query);

            if ($result) {
                $usuarioData = mysqli_fetch_assoc($result);

                $nombreUsuario = $usuarioData['Nombre'];
                $telefonoUsuario = $usuarioData['Telefono'];
                $codigoPostalUsuario = $usuarioData['CodigoPostal'];
            } else {
                // Manejar el error si la consulta no se realizó con éxito
                echo "Error al consultar la base de datos.";
                exit();
            }
            ?>
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="formulario-edicionEditarLabel">Editar Datos</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <img width="100px" height="100px" style="object-fit:contain;" src="https://zigzagmerceriacreativa.es/talleres/fotos_usuarios/<?= $foto; ?>">
                        <form id="form-edicion" class="mb-3">
                            <div class="mb-3">
                                <label for="foto">Selecciona tu foto:</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre:</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $nombreUsuario; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono:</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo $telefonoUsuario; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="codigo_postal" class="form-label">Código Postal:</label>
                                <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" value="<?php echo $codigoPostalUsuario; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="nueva_contrasena" class="form-label">Nueva Contraseña:</label>
                                <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena">
                            </div>
                            <div class="mb-3">
                                <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña:</label>
                                <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena">
                            </div>
                            <button type="button" class="btn btn-primary" id="guardar-cambios-btn">Guardar Cambios</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
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

        <h2 class="mb-4">Calendario Personal</h2>
        <div id="calendario_personal"></div>
        <div class="msg-saldo mt-3"><?php echo $mensajeSaldo; ?></div>
        <h2 class="mb-4 mt-3 text-center">Próximas clases en las que estás inscrit@</h2>
        <div id="listado-eventos"></div>

        <!-- Modal Cancelar Cita -->
        <div class="modal fade" id="modalCancelarCita" tabindex="-1" aria-labelledby="modalCancelarCitaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCancelarCitaLabel">Cancelar Fecha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><b>Taller: </b>
                        <div class="info-taller"></div>
                        </p>
                        <p><b>Profesor: </b>
                        <div class="info-profesor"></div>
                        </p>
                        <p><b>Fecha: </b>
                        <div class="info-fecha"></div>
                        </p>
                        <p><b>Hora: </b>
                        <div class="info-hora"></div>
                        </p>
                        <p>¿Estás seguro de que deseas cancelar esta clase?</p>
                    </div>
                    <div class="modal-footer">
                        <input type="text" class="idfecha d-none" value="">
                        <input type="text" class="idusuario d-none" value="<?= $userID; ?>">
                        <button type="button" class="btn btn-danger" id="cancelarCitaButton">Cancelar clase</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 24H -->
        <div class="modal fade" id="modalCancelarCita24h" tabindex="-1" aria-labelledby="modalCancelarCita24hLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCancelarCita24hLabel">Información</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><b>Taller: </b>
                        <div class="info-taller"></div>
                        </p>
                        <p><b>Profesor: </b>
                        <div class="info-profesor"></div>
                        </p>
                        <p><b>Fecha: </b>
                        <div class="info-fecha"></div>
                        </p>
                        <p><b>Hora: </b>
                        <div class="info-hora"></div>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <input type="text" class="idfecha d-none" value="">
                        <input type="text" class="idusuario d-none" value="<?= $userID; ?>">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" aria-label="Close">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Anadir Cita -->
        <div class="modal fade" id="modalAnadirCita" tabindex="-1" aria-labelledby="modalAnadirCitaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAnadirCitaLabel">Inscripción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><b>Taller: </b>
                        <div class="info-taller"></div>
                        </p>
                        <p><b>Profesor: </b>
                        <div class="info-profesor"></div>
                        </p>
                        <p><b>Fecha: </b>
                        <div class="info-fecha"></div>
                        </p>
                        <p><b>Hora: </b>
                        <div class="info-hora"></div>
                        </p>
                        <p>¿Estás seguro de que deseas inscribirte a esta clase?</p>
                    </div>
                    <div class="modal-footer">
                        <input type="text" class="idfecha d-none" value="">
                        <input type="text" class="idhorario d-none" value="">
                        <input type="text" class="idtaller d-none" value="">
                        <input type="text" class="idusuario d-none" value="<?= $userID; ?>">
                        <button type="button" class="btn btn-danger" id="anadirCitaButton">Inscribirme</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Sin Citas -->
        <div class="modal fade" id="modalNoCita" tabindex="-1" aria-labelledby="modalNoCitaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalNoCitaLabel">Sin disponibilidad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>No hay clases disponibles en este horario</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Sin Citas -->
        <div class="modal fade" id="modalNoSaldo" tabindex="-1" aria-labelledby="modalNoSaldoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalNoSaldoLabel">Sin disponibilidad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>No tienes saldo disponible para inscribirte</p>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(document).ready(function() {
                $('#cancelarCitaButton').on('click', function() {
                    // Obtener los valores necesarios del modal
                    $('#cancelarCitaButton').prop("disabled", true);
                    console.log("Cancelando clase...");
                    var idFecha = $('#modalCancelarCita .idfecha').val();
                    var idUsuario = $('#modalCancelarCita .idusuario').val();

                    // Hacer una solicitud AJAX al servidor
                    $.ajax({
                        type: 'POST',
                        url: 'mistalleres.php', // Reemplaza 'tucodigo.php' con el nombre de tu archivo PHP
                        data: {
                            action: 'cancelarCita',
                            idFecha: idFecha,
                            idUsuario: idUsuario
                        },
                        success: function(response) {
                            // Manejar la respuesta del servidor aquí (si es necesario)
                            console.log(response);
                            location.reload();
                            // Puedes actualizar la página o realizar otras acciones según la respuesta
                        },
                        error: function(error) {
                            // Manejar errores de la solicitud AJAX aquí
                            console.error(error);
                            location.reload();
                        }
                    });
                });
                $('#anadirCitaButton').on('click', function() {
                    // Obtener los valores necesarios del modal

                    $('#anadirCitaButton').prop("disabled", true);
                    console.log("Añadiendo clase...");
                    var idFecha = $('#modalAnadirCita .idfecha').val();
                    var idUsuario = $('#modalAnadirCita .idusuario').val();
                    var idHorario = $('#modalAnadirCita .idhorario').val();
                    var idTaller = $('#modalAnadirCita .idtaller').val();

                    // Hacer una solicitud AJAX al servidor
                    $.ajax({
                        type: 'POST',
                        url: 'mistalleres.php', // Reemplaza 'tucodigo.php' con el nombre de tu archivo PHP
                        data: {
                            action: 'anadirCita',
                            idFecha: idFecha,
                            idUsuario: idUsuario,
                            idHorario: idHorario,
                            idTaller: idTaller
                        },
                        success: function(response) {
                            // Manejar la respuesta del servidor aquí (si es necesario)
                            console.log(response);
                            location.reload();
                        },
                        error: function(error) {
                            // Manejar errores de la solicitud AJAX aquí
                            console.error(error);
                            location.reload();
                        }
                    });
                });
            });
        </script>

        <?php
        $query_calendario = "SELECT
DISTINCT horario.HorarioID,
taller.Nombre AS NombreTaller,
taller.TallerID AS TallerID,
horario.HorarioID AS HorarioID,
horario.HoraInicio,
horario.HoraFin,
fechas.Fecha,
fechas.IDFecha,
usuario.Nombre AS NombreProfesor,
(SELECT COUNT(*) FROM inscripcion AS I WHERE I.HorarioID = horario.HorarioID AND I.FechaID = fechas.IDFecha) AS CantidadInscritos
FROM
horario
INNER JOIN taller ON horario.TallerID = taller.TallerID
INNER JOIN fechas ON horario.HorarioID = fechas.HorarioID
INNER JOIN usuario ON taller.IDProfesor = usuario.UsuarioID
WHERE
taller.TallerID IN (
    SELECT TallerID
    FROM inscripcion
    WHERE UsuarioID = $userID
)
AND fechas.Fecha NOT IN (
    SELECT Fecha
    FROM festivos
)";


        $result_calendario = mysqli_query($dbConnection, $query_calendario);
        $calendario_array = mysqli_fetch_all($result_calendario, MYSQLI_ASSOC);
        $events = [];

        $query_saldo = "SELECT Saldo FROM usuario WHERE UsuarioID = $userID";
        $result_saldo = mysqli_query($dbConnection, $query_saldo);
        $saldo_data = mysqli_fetch_assoc($result_saldo);
        $saldo = $saldo_data['Saldo'];

        foreach ($calendario_array as $evento) {
            // Determina si el usuario está inscrito en esta fecha
            $usuarioInscrito = verificarInscripcionUsuario($evento['IDFecha'], $userID);

            // Obtener el límite dinámico de plazas
            $limitePlazas = obtenerLimitePlazas($dbConnection, $evento['TallerID'], $evento['HorarioID']);

            // Agrega la propiedad 'inscrito' al array según la inscripción del usuario
            $events[] = array(
                'id' => $evento['IDFecha'],
                'title' => $evento['NombreTaller'],
                'tallerID' => $evento['TallerID'],
                'horarioID' => $evento['HorarioID'],
                'profesor' => $evento['NombreProfesor'],
                'start' => $evento['Fecha'] . 'T' . $evento['HoraInicio'],
                'end' => $evento['Fecha'] . 'T' . $evento['HoraFin'],
                'inscrito' => $usuarioInscrito,
                'CantidadInscritos' => $evento['CantidadInscritos'],
                'SaldoUsuario' => $saldo,
                'horaInicio' => $evento['HoraInicio'],
                'fecha' => $evento['Fecha'],
                'Limite' => $limitePlazas // Aquí añadimos el límite de plazas dinámico
            );
        }


        // Función para verificar si el usuario está inscrito en una fecha específica
        function verificarInscripcionUsuario($fechaID, $usuarioID)
        {
            global $dbConnection;  // Asegúrate de tener acceso a la conexión a la base de datos

            // Consulta para verificar la inscripción del usuario en la fecha específica
            $queryVerificarInscripcion = "SELECT COUNT(*) as countInscripcion FROM inscripcion WHERE UsuarioID = $usuarioID AND FechaID = $fechaID";

            $result = mysqli_query($dbConnection, $queryVerificarInscripcion);

            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $countInscripcion = $row['countInscripcion'];

                // Devuelve true si el usuario está inscrito, false si no lo está
                return ($countInscripcion > 0);
            } else {
                // Manejo de errores (puedes personalizar según tus necesidades)
                echo "Error en la consulta: " . mysqli_error($dbConnection);
                return false;
            }
        }

        // Función para obtener el límite de plazas dinámicamente
        function obtenerLimitePlazas($dbConnection, $tallerID, $horarioID)
        {
            $sql_limite = "SELECT Limite FROM horario WHERE TallerID = $tallerID AND HorarioID = $horarioID";
            $resultado_limite = mysqli_query($dbConnection, $sql_limite);

            if ($resultado_limite) {
                $limite_data = mysqli_fetch_assoc($resultado_limite);
                return $limite_data['Limite']; // Retorna el valor del límite
            } else {
                echo "Error al obtener el límite de plazas: " . mysqli_error($dbConnection);
                return 0; // Retorna 0 en caso de error
            }
        }



        ?>
        <script>
$(document).ready(function() {
    setTimeout(function() {
        var calendarEl = document.getElementById('calendario_personal');
        var listadoEventosEl = document.getElementById('listado-eventos');
        var userID = <?= $userID; ?>

        function limpiarListadoEventos() {
            // Limpiar el contenido del contenedor cuando sea necesario
            listadoEventosEl.innerHTML = '';
        }

        var today = new Date();
        var currentDay = today.getDate();
        var currentMonth = today.getMonth(); // 0-indexado (enero=0, febrero=1, etc.)
        var currentYear = today.getFullYear();

        var visibleEndDate;

        if (currentDay >= 26) {
            // Si estamos en el día 26 o después, se abre el mes siguiente hasta el día 7
            var nextMonth = currentMonth + 2; // +2 porque queremos el mes después del siguiente
            var nextYear = currentYear;
            
            // Manejar el cambio de año
            if (nextMonth > 11) {
                nextMonth = nextMonth - 12;
                nextYear = nextYear + 1;
            }
            
            visibleEndDate = new Date(nextYear, nextMonth, 8); // Hasta el día 7 (8 excluye el 8)
            console.log("Día " + currentDay + " - calendario abierto hasta el 7 de " + (nextMonth + 1) + "/" + nextYear);
        } else {
            // Si estamos antes del día 26, solo se ve hasta el día 7 del mes siguiente al actual
            var nextMonth = currentMonth + 1;
            var nextYear = currentYear;
            
            // Manejar el cambio de año
            if (nextMonth > 11) {
                nextMonth = 0;
                nextYear = nextYear + 1;
            }
            
            visibleEndDate = new Date(nextYear, nextMonth, 8); // Hasta el día 7 (8 excluye el 8)
            console.log("Día " + currentDay + " (antes del 26) - calendario visible hasta el 7 de " + (nextMonth + 1) + "/" + nextYear);
        }

        var calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            },
            initialDate: today, // Establece la fecha inicial como hoy
            validRange: {
                start: today, // No permitir fechas pasadas
                end: visibleEndDate // Límite hasta el día 7 del mes correspondiente
            },
            events: <?php echo json_encode($events); ?>,
            locale: 'es',
            height: 'auto',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                meridiem: false
            },
            eventContent: function(arg) {
                var content = {
                    html: '<div class="fc-content">' + formatearHora(arg.event.extendedProps.horaInicio) + " " + arg.event.title + '</div>'
                };
                return content;
            },
            eventClassNames: function(arg) {
                if (arg.event.extendedProps.inscrito) {
                    return 'evento inscrito';
                } else if ((arg.event.extendedProps.Limite - arg.event.extendedProps.CantidadInscritos) >= 1) {
                    return 'evento disponible';
                } else {
                    return 'evento block';
                }
            },
            eventClick: function(info) {
                var evento = info.event;
                var fecha_taller = formatearFecha(evento.extendedProps.fecha);
                var hora_taller = formatearHora(evento.extendedProps.horaInicio);
                var profesor = evento.extendedProps.profesor;
                var taller = evento.title;

                // Obtener la fecha actual
                var fechaActual = new Date();
                console.log("fecha actual: " + fechaActual);
                // Obtener la fecha del evento
                var fechaEvento = new Date(evento.start);
                console.log("fecha del evento: " + fechaEvento);
                // Calcular la diferencia en milisegundos
                var diferenciaTiempo = fechaEvento - fechaActual;

                // Calcular la diferencia en horas
                var diferenciaHoras = diferenciaTiempo / (1000 * 60 * 60);

                if (evento.extendedProps.inscrito) {
                    // Verificar si la diferencia es mayor a 12 horas
                    if (diferenciaHoras >= 1) {
    $('#modalCancelarCita .idfecha').val(evento.id);
    $('#modalCancelarCita .info-hora').html(hora_taller);
    $('#modalCancelarCita .info-fecha').html(fecha_taller);
    $('#modalCancelarCita .info-profesor').html(profesor);
    $('#modalCancelarCita .info-taller').html(taller);
    $('#modalCancelarCita').modal('show'); // Muestra el modal
} else {
    $('#modalCancelarCita24h .idfecha').val(evento.id);
    $('#modalCancelarCita24h .info-hora').html(hora_taller);
    $('#modalCancelarCita24h .info-fecha').html(fecha_taller);
    $('#modalCancelarCita24h .info-profesor').html(profesor);
    $('#modalCancelarCita24h .info-taller').html(taller);
    $('#modalCancelarCita24h').modal('show'); // Muestra el modal
}
                } else if ((evento.extendedProps.Limite - evento.extendedProps.CantidadInscritos) > 0 && (diferenciaHoras >= 1)) {
                    console.log("saldo: " + evento.extendedProps.SaldoUsuario);
                    if (evento.extendedProps.SaldoUsuario > 0) {
                        $('#modalAnadirCita .idfecha').val(evento.id);
                        $('#modalAnadirCita .idtaller').val(evento.extendedProps.tallerID);
                        $('#modalAnadirCita .idhorario').val(evento.extendedProps.horarioID);
                        $('#modalAnadirCita .info-hora').html(hora_taller);
                        $('#modalAnadirCita .info-fecha').html(fecha_taller);
                        $('#modalAnadirCita .info-profesor').html(profesor);
                        $('#modalAnadirCita .info-taller').html(taller);
                        $('#modalAnadirCita').modal('show'); // Muestra el modal
                    } else {
                        $('#modalNoSaldo').modal('show'); // Muestra el modal
                    }
                } else {
                    $('#modalNoCita').modal('show'); // Muestra el modal
                }
            },
            eventDidMount: function(info) {
                var evento = info.event;
                var plazas = evento.extendedProps.Limite - evento.extendedProps.CantidadInscritos;
                var extra = "inscrito";
                /* INSCRITO */
                if (evento.extendedProps.inscrito) {
                    var inscripcion = document.createElement('span');
                    inscripcion.textContent = "Inscrito";
                    inscripcion.classList.add("inscrito");
                    inscripcion.classList.add("plazas");
                } else {
                    var inscripcion = document.createElement('span');
                    var plazas = evento.extendedProps.Limite - evento.extendedProps.CantidadInscritos;
                    inscripcion.textContent = "plazas: " + plazas;
                    inscripcion.classList.add("no-inscrito");
                    inscripcion.classList.add("plazas");
                    if (plazas <= 0) {
                        var extra = "block";
                    } else {
                        var extra = "disponible";
                    }
                }

                // Crear un div para el evento
                var eventoDiv = document.createElement('div');
                eventoDiv.classList.add('evento');
                eventoDiv.classList.add(extra);
                eventoDiv.id = evento.id; // Usar el ID de la fecha

                // Crear un párrafo para la fecha y la hora de inicio
                var fecha_taller_lista = formatearFecha(evento.extendedProps.fecha);
                var hora_taller_lista = formatearHora(evento.extendedProps.horaInicio);
                var fechaInicioP = document.createElement('span');
                fechaInicioP.textContent = fecha_taller_lista + " - " + hora_taller_lista;
                fechaInicioP.classList.add("fecha");

                // Crear un párrafo para el nombre del taller
                var nombreTallerP = document.createElement('span');
                nombreTallerP.textContent = evento.title;
                nombreTallerP.classList.add("taller");

                // Crear un párrafo para el nombre del profesor
                var profesorP = document.createElement('span');
                profesorP.textContent = evento.extendedProps.profesor;
                profesorP.classList.add("profesor");

                // Agregar los párrafos al div del evento
                eventoDiv.appendChild(fechaInicioP);
                eventoDiv.appendChild(nombreTallerP);
                eventoDiv.appendChild(profesorP);
                eventoDiv.appendChild(inscripcion);

                // Agregar el div del evento al listado
                listadoEventosEl.appendChild(eventoDiv);
            }
        });

        calendar.render();
    }, 0);
});
</script>

        <?php

        function obtenerTalleresUsuario($dbConnection, $userID)
        {
            $query = "SELECT DISTINCT T.TallerID, T.Nombre
                FROM inscripcion I
                JOIN taller T ON I.TallerID = T.TallerID
                WHERE I.UsuarioID = $userID";
            $result = mysqli_query($dbConnection, $query);
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        }

        if ($saldoUsuario > 0) {
        ?>
            <div class="mt-3" id="formulario-inscripcion" style="display: none;">
                <h2>Añadir Inscripción Temporal</h2>
                <form id="form-inscripcion" class="mb-3">
                    <label for="taller_seleccionado" class="form-label">Selecciona un Taller:</label>
                    <select class="form-select mb-3" id="taller_seleccionado" name="tallerID">
                        <?php
                        $talleresUsuario = obtenerTalleresUsuario($dbConnection, $userID);
                        foreach ($talleresUsuario as $taller) {
                            echo "<option value='{$taller['TallerID']}'>{$taller['Nombre']}</option>";
                        }
                        ?>
                    </select>
                    <label for="horario_disponible" class="form-label">Selecciona un Horario Disponible:</label>
                    <select class="form-select" id="horario_disponible" name="horarioID">
                        <!-- Opciones de horarios se cargarán dinámicamente con JavaScript -->
                    </select>
                    <button type="button" class="btn btn-success mt-2" id="confirmar-inscripcion-btn">Confirmar Inscripción</button>
                </form>
            </div>
            <script>
                $(document).ready(function() {
                    $(".inscripcion-temporal-btn").on("click", function() {
                        cargarHorariosDisponibles();
                        $("#formulario-inscripcion").css("display", "block");
                    });

                    // Acción al cambiar el taller seleccionado
                    $("#taller_seleccionado").on("change", function() {
                        cargarHorariosDisponibles();
                    });

                    // Acción al hacer clic en "Confirmar Inscripción"
                    $("#confirmar-inscripcion-btn").on("click", function() {
                        const tallerID = $("#taller_seleccionado").val();
                        const horarioID = $("#horario_disponible").val();
                        confirmarInscripcionTemporal(tallerID, horarioID);
                    });

                    function cargarHorariosDisponibles() {
                        const tallerID = $("#taller_seleccionado").val();
                        $.ajax({
                            url: "mistalleres.php",
                            type: "POST",
                            data: {
                                action: "cargarHorarios",
                                tallerID: tallerID
                            },
                            dataType: "json",
                            success: function(data) {
                                const horarioDisponibleSelect = $("#horario_disponible");
                                horarioDisponibleSelect.empty();
                                $.each(data, function(index, horario) {
                                    horarioDisponibleSelect.append(
                                        $("<option>", {
                                            value: horario.HorarioID,
                                            text: horario.DiaSemana + " - " + horario.HoraInicio
                                        })
                                    );
                                });
                                location.reload();
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.log("Error al cargar horarios", textStatus, errorThrown);
                            }
                        });
                    }

                    function confirmarInscripcionTemporal(tallerID, horarioID) {
                        $.ajax({
                            url: "mistalleres.php", // Ajusta la ruta según sea necesario
                            type: "POST",
                            data: {
                                action: "usarSaldo",
                                tallerID: tallerID,
                                horarioID: horarioID
                            },
                            dataType: "json",
                            success: function(data) {
                                if (data.success) {
                                    location.reload(); // Esto recargará la página actual
                                } else {
                                    location.reload(); // Esto recargará la página actual
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                location.reload(); // Esto recargará la página actual
                            }
                        });
                    }

                });
            </script>
        <?php
        }
        ?>
    </div>

    <script>
        $(document).ready(function() {
            const cambiarHorarioButtons = $(".cambiar-horario-btn");
            const cancelarHorarioButtons = $(".cancelar-horario-btn");
            const formularioCambio = $("#formulario-cambio");
            const nuevoHorarioSelect = $("#nuevo_horario");
            const confirmarCambioButton = $("#confirmar-cambio-btn");

            cambiarHorarioButtons.on("click", function() {
                const tallerID = $(this).data("taller");
                const horarioAntiguoID = $(this).data("horario");
                $("#horario_antiguo").val(horarioAntiguoID);
                cargarHorariosDisponibles(tallerID);
                formularioCambio.css("display", "block");
            });

            cancelarHorarioButtons.on("click", function() {
                const tallerID = $(this).data("taller");
                const horarioAntiguoID = $(this).data("horario");
                cancelarHorario(horarioAntiguoID);
            });

            confirmarCambioButton.on("click", function() {
                const nuevoHorarioID = nuevoHorarioSelect.val();
                const horarioAntiguoID = $("#horario_antiguo").val(); // Agrega la obtención del ID antiguo
                cambiarHorario(horarioAntiguoID, nuevoHorarioID);
            });

            function obtenerHorarioAntiguo() {
                return $("#horario_antiguo").val();
            }

            function cargarHorariosDisponibles(tallerID) {
                $.ajax({
                    url: "mistalleres.php", // Ajusta la ruta según sea necesario
                    type: "POST",
                    data: {
                        action: "cargarHorarios",
                        tallerID: tallerID
                    },
                    dataType: "json",
                    success: function(data) {
                        nuevoHorarioSelect.empty();
                        $.each(data, function(index, horario) {
                            nuevoHorarioSelect.append(
                                $("<option>", {
                                    value: horario.HorarioID,
                                    text: horario.DiaSemana + " - " + horario.HoraInicio
                                })
                            );
                        });
                        location.reload();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log("Error al cargar horarios", textStatus, errorThrown);
                    }
                });

            }

            function cambiarHorario(horarioAntiguoID, nuevoHorarioID) {
                $.ajax({
                    url: "mistalleres.php", // Ajusta la ruta según sea necesario
                    type: "POST",
                    data: {
                        action: "cambiarHorario",
                        nuevoHorarioID: nuevoHorarioID,
                        horarioAntiguoID: horarioAntiguoID
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.success) {
                            location.reload(); // Esto recargará la página actual
                        } else {
                            location.reload(); // Esto recargará la página actual
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log("Error al cambiar horario", textStatus, errorThrown);
                    }
                });
            }

            function cancelarHorario(horarioAntiguoID) {
                $.ajax({
                    url: "mistalleres.php", // Ajusta la ruta según sea necesario
                    type: "POST",
                    data: {
                        action: "cancelarHorario",
                        horarioAntiguoID: horarioAntiguoID
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.success) {
                            location.reload(); // Esto recargará la página actual
                        } else {
                            location.reload(); // Esto recargará la página actual
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log("Error al cambiar horario", textStatus, errorThrown);
                        location.reload();
                    }
                });
            }

            const mostrarFormularioEdicionButton = $("#mostrar-formulario-edicion");
            const formularioEdicion = $("#formulario-edicion");
            const guardarCambiosButton = $("#guardar-cambios-btn");
            const normasButton = $("#verNormasBtn");

            mostrarFormularioEdicionButton.on("click", function() {
                $("#formulario-edicion").modal('show');
            });

            normasButton.on("click", function() {
                $("#normasPopup").modal('show');
            });

            guardarCambiosButton.on("click", function() {
                const nuevoNombre = $("#nombre").val();
                const nuevoTelefono = $("#telefono").val();
                const nuevoCodigoPostal = $("#codigo_postal").val();
                const nuevaContrasena = $("#nueva_contrasena").val();
                const confirmarContrasena = $("#confirmar_contrasena").val();
                const fotoInput = $('#foto')[0];
                const fotoArchivo = fotoInput.files[0];

                // Validar que las contraseñas coincidan
                if (nuevaContrasena != "" && nuevaContrasena !== confirmarContrasena) {
                    alert("Las contraseñas no coinciden. Por favor, inténtelo de nuevo.");
                    return false; // Evita que el formulario se envíe
                }

                const formData = new FormData();
                formData.append('action', 'actualizarDatos');
                formData.append('nombre', nuevoNombre);
                formData.append('telefono', nuevoTelefono);
                formData.append('codigoPostal', nuevoCodigoPostal);
                formData.append('nuevaContrasena', nuevaContrasena);
                formData.append('foto', fotoArchivo);


                // Realiza la solicitud AJAX para actualizar los datos del usuario
                $.ajax({
                    url: "mistalleres.php",
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
                            alert("Error al actualizar datos: " + response.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log(jqXHR);
                    }
                });
            });

            //cerrar sesion
            const cerrarSesionButton = $("#cerrar-sesion-btn");

            cerrarSesionButton.on("click", function() {
                // Realiza la operación de cierre de sesión
                $.ajax({
                    url: "mistalleres.php", // Reemplaza "cerrar_sesion.php" con la URL del script de cierre de sesión
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
        });

        function formatearFecha(fecha) {
            // Suponemos que fecha es una cadena de fecha válida
            var fechaObj = new Date(fecha);

            // Obtener el día, mes y año
            var dia = fechaObj.getDate();
            var mes = fechaObj.getMonth() + 1; // Los meses en JavaScript son de 0 a 11, así que sumamos 1
            var ano = fechaObj.getFullYear();

            // Formatear con ceros a la izquierda si es necesario
            dia = dia < 10 ? '0' + dia : dia;
            mes = mes < 10 ? '0' + mes : mes;

            // Crear la cadena en el formato deseado
            var formatoFecha = dia + '/' + mes + '/' + ano;

            return formatoFecha;
        }

        function formatearHora(hora) {
            // Dividir la cadena de la hora para obtener horas y minutos
            var partesHora = hora.split(':');

            // Crear un nuevo objeto de fecha
            var fecha = new Date();

            // Establecer la hora y los minutos en el objeto de fecha
            fecha.setHours(parseInt(partesHora[0], 10));
            fecha.setMinutes(parseInt(partesHora[1], 10));

            // Obtener la hora y los minutos formateados
            var horas = fecha.getHours();
            var minutos = fecha.getMinutes();

            // Formatear con ceros a la izquierda si es necesario
            horas = horas < 10 ? '0' + horas : horas;
            minutos = minutos < 10 ? '0' + minutos : minutos;

            // Crear la cadena en el formato deseado
            var formatoHora = horas + ':' + minutos;

            return formatoHora;
        }
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
                    url: "mistalleres.php",
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
</body>

</html>