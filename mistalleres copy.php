<?php
session_start();
require_once 'db_connection.php'; // Ajusta la ruta según sea necesario

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['user_tipo'] == 1) { // Administrador
    header("Location: admin.php");
    exit();
}
$userID = $_SESSION['user_id'];
$nombreUsuario = $_SESSION['user_name'];

$query = "
SELECT 
    I.InscripcionID,
    I.UsuarioID,
    I.TallerID,
    T.Nombre AS NombreTaller,
    COALESCE(T3.HorarioID, I.HorarioID) AS HorarioID,
    COALESCE(T3.HoraInicio, H.HoraInicio) AS HoraInicio,
    COALESCE(T3.HoraFin, H.HoraFin) AS HoraFin,
    COALESCE(T3.DiaSemana, H.DiaSemana) AS DiaSemana,
    COALESCE(T3.Limite, H.Limite) AS Limite,
    I.FechaInscripcion
FROM Inscripcion I
JOIN Horario H ON I.HorarioID = H.HorarioID
JOIN Taller T ON I.TallerID = T.TallerID
LEFT JOIN Temporales T2 ON I.HorarioID = T2.HorarioAntiguo AND I.UsuarioID = T2.Usuario AND T2.Fecha > CURRENT_TIMESTAMP()
LEFT JOIN Horario T3 ON T2.HorarioNuevo = T3.HorarioID
WHERE 
    I.UsuarioID = $userID
    AND I.HorarioID NOT IN (
        SELECT HorarioID 
        FROM Cancelaciones 
        WHERE Fecha > CURRENT_DATE()
    )

UNION

SELECT 
    NULL AS InscripcionID,
    NULL AS UsuarioID,
    NULL AS TallerID,
    T.Nombre AS NombreTaller,
    T3.HorarioID,
    T3.HoraInicio,
    T3.HoraFin,
    T3.DiaSemana,
    T3.Limite,
    NULL AS FechaInscripcion
FROM Temporales T2
JOIN Horario T3 ON T2.HorarioNuevo = T3.HorarioID
JOIN Taller T ON T3.TallerID = T.TallerID
WHERE 
    T2.HorarioAntiguo IS NULL
    AND T2.Fecha > CURRENT_TIMESTAMP()
    AND T2.Usuario = $userID;

    ";
$result = mysqli_query($dbConnection, $query);
$inscripciones = mysqli_fetch_all($result, MYSQLI_ASSOC);
$dia_actual = ucfirst(strftime('%A'));

// Función de comparación para ordenar el array
function compararFechas($a, $b)
{
    global $dia_actual;

    // Array de días de la semana en español
    $dias_semana = array(
        "Domingo" => 0,
        "Lunes" => 1,
        "Martes" => 2,
        "Miércoles" => 3,
        "Jueves" => 4,
        "Viernes" => 5,
        "Sábado" => 6,
    );

    // Obtener el índice del día actual en el array de días de la semana
    $indice_dia_actual = $dias_semana[$dia_actual];

    // Obtener el índice del día de cada taller en el array de días de la semana
    $indice_dia_a = $dias_semana[$a['DiaSemana']];
    $indice_dia_b = $dias_semana[$b['DiaSemana']];

    // Calcular la diferencia en días desde el día actual
    $diferencia_dias_a = ($indice_dia_a - $indice_dia_actual + 7) % 7;
    $diferencia_dias_b = ($indice_dia_b - $indice_dia_actual + 7) % 7;

    // Comparar por la proximidad al día actual
    if ($diferencia_dias_a == $diferencia_dias_b) {
        // Si la diferencia en días es la misma, comparar por la hora de inicio
        return strtotime($a['HoraInicio']) - strtotime($b['HoraInicio']);
    }

    return $diferencia_dias_a - $diferencia_dias_b;
}

// Ordenar el array utilizando la función de comparación
usort($inscripciones, 'compararFechas');

function obtenerProximaFecha($diaSemana)
{
    // Obtener el número del día de la semana (0 = domingo, 1 = lunes, ..., 6 = sábado)
    $numeroDiaSemana = date('w');

    // Obtener el número correspondiente al día de la semana proporcionado
    $numeroDiaSolicitado = array_search(ucfirst($diaSemana), array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'));

    // Calcular la diferencia de días entre el día actual y el día solicitado
    $diferenciaDias = ($numeroDiaSolicitado - $numeroDiaSemana + 7) % 7;

    // Calcular la fecha del próximo día solicitado
    $proximaFecha = date('Y-m-d', strtotime("+$diferenciaDias days"));

    return $proximaFecha;
}
function getFoto($dbConnection, $userID){
    $sqlgetfoto = "SELECT Foto FROM Usuario WHERE UsuarioID = ?";
    
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
        if ($_POST['action'] === 'cambiarHorario') {
            $horarioAntiguoID = $_POST['horarioAntiguoID']; // Agregamos la obtención del ID antiguo
            $nuevoHorarioID = $_POST['nuevoHorarioID'];
            $consultaDiaSemana = "SELECT DiaSemana FROM Horario WHERE HorarioID = $nuevoHorarioID";
            $resultadoDiaSemana = mysqli_query($dbConnection, $consultaDiaSemana);
            $fechaTemporal = obtenerProximaFecha($resultadoDiaSemana);
            $sql = "INSERT INTO Temporales (Usuario, HorarioAntiguo, HorarioNuevo, Fecha) VALUES ('$userID', '$horarioAntiguoID', '$nuevoHorarioID', '$fechaTemporal')";

            if (mysqli_query($dbConnection, $sql)) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['error' => mysqli_error($dbConnection)]);
                exit();
            }
        }elseif ($_POST['action'] === 'usarSaldo') {
            $tallerID = $_POST['tallerID'];
            $horarioID = $_POST['horarioID'];
            $consultaDiaSemana = "SELECT DiaSemana FROM Horario WHERE HorarioID = $horarioID";
            $resultadoDiaSemana = mysqli_query($dbConnection, $consultaDiaSemana);
            $fechaTemporal = obtenerProximaFecha($resultadoDiaSemana);
            $sql = "INSERT INTO Temporales (Usuario, HorarioNuevo, Fecha) VALUES ('$userID', '$horarioID', '$fechaTemporal')";

            if (mysqli_query($dbConnection, $sql)) {
                $sqlSaldo = "UPDATE Usuario SET Saldo = Saldo - 1 WHERE UsuarioID = '$userID'";

                if (mysqli_query($dbConnection, $sqlSaldo)) {
                    // Si ambas operaciones son exitosas, realiza el commit
                    mysqli_commit($dbConnection);
                    echo json_encode(['success' => true]);
                    exit();
                } else {
                    // Si hay un error, realiza el rollback
                    mysqli_rollback($dbConnection);
                    echo json_encode(['error' => mysqli_error($dbConnection)]);
                    exit();
                }
            } else {
                echo json_encode(['error' => mysqli_error($dbConnection)]);
                exit();
            }
        } elseif ($_POST['action'] === 'cancelarHorario') {
            $horarioAntiguoID = $_POST['horarioAntiguoID'];
            $consultaDiaSemana = "SELECT DiaSemana FROM Horario WHERE HorarioID = $nuevoHorarioID";
            $resultadoDiaSemana = mysqli_query($dbConnection, $consultaDiaSemana);
            $fechaTemporal = obtenerProximaFecha($resultadoDiaSemana);

            // Segunda consulta para actualizar el saldo del usuario
            $consultaSaldo = "SELECT Saldo FROM Usuario WHERE UsuarioID = $userID";
            $resultadoSaldo = mysqli_query($dbConnection, $consultaSaldo);
            $registroSaldo = mysqli_fetch_assoc($resultadoSaldo);
            $nuevoSaldo = $registroSaldo['Saldo'] + 1;

            $sqlActualizarSaldo = "UPDATE Usuario SET Saldo = $nuevoSaldo WHERE UsuarioID = $userID";
            if (mysqli_query($dbConnection, $sqlActualizarSaldo)) {
                // Ahora realiza la inserción en la tabla Cancelaciones
                $sqlInsertarCancelacion = "INSERT INTO Cancelaciones (UsuarioID, HorarioID, Fecha) VALUES ('$userID', '$horarioAntiguoID', '$fechaTemporal')";

                if (mysqli_query($dbConnection, $sqlInsertarCancelacion)) {
                    echo json_encode(['success' => true]);
                    exit();
                } else {
                    echo json_encode(['error' => mysqli_error($dbConnection)]);
                    exit();
                }
            } else {
                echo json_encode(['error' => mysqli_error($dbConnection)]);
                exit();
            }
        } elseif ($_POST['action'] === 'cargarHorarios') {
            $tallerID = $_POST['tallerID'];
            $horarios = obtenerHorariosDisponibles($dbConnection, $tallerID);

            // Establece el encabezado Content-Type a JSON
            header('Content-Type: application/json');

            // Imprime la respuesta JSON
            echo json_encode($horarios);
            exit();
        } elseif ($_POST['action'] === 'actualizarDatos') {
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
                    $query = "UPDATE Usuario SET Foto = ? WHERE UsuarioID = ?";
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
            if($nuevaContrasena != ""){
                $query = "UPDATE Usuario SET Nombre = ?, Telefono = ?, CodigoPostal = ?, Contrasena = ? WHERE UsuarioID = ?";
            }else{
                $query = "UPDATE Usuario SET Nombre = ?, Telefono = ?, CodigoPostal = ? WHERE UsuarioID = ?";
            }
            $stmt = mysqli_prepare($dbConnection, $query);

            // Asegúrate de hacer un hash de la nueva contraseña si es necesario
            // Ejemplo de hash con MD5 (no recomendado para contraseñas seguras):
            $hashContrasena = md5($nuevaContrasena);
            if($nuevaContrasena != ""){
                mysqli_stmt_bind_param($stmt, "ssssi", $nuevoNombre, $nuevoTelefono, $nuevoCodigoPostal, $hashContrasena, $userID);
            }else{
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
            }
        } elseif ($_POST['action'] === 'cerrarSesion') {
            // Cerrar la sesión actual
            session_destroy();

            echo json_encode(['success' => true]);
        }
    }
}
/*
function obtenerHorariosDisponibles($dbConnection, $tallerID, $horarioIDActual)
{
    $query = "SELECT Horario.HorarioID, Horario.DiaSemana, DATE_FORMAT(Horario.HoraInicio, '%H:%i') AS HoraInicio
              FROM Horario
              WHERE Horario.TallerID = $tallerID AND Horario.HorarioID != $horarioIDActual";
    $result = mysqli_query($dbConnection, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}*/
function obtenerHorariosDisponibles($dbConnection, $tallerID)
{
    $query = "SELECT H.HorarioID, H.DiaSemana, H.HoraInicio
              FROM Horario H
              LEFT JOIN (
                  SELECT HorarioID, COUNT(*) AS InscripcionesCount
                  FROM Inscripcion
                  GROUP BY HorarioID
              ) I ON H.HorarioID = I.HorarioID
              WHERE H.TallerID = $tallerID
              AND (I.InscripcionesCount IS NULL OR I.InscripcionesCount < H.Limite)";

    $result = mysqli_query($dbConnection, $query);
    $horarios = mysqli_fetch_all($result, MYSQLI_ASSOC);
    return $horarios;
}
/*
function cambiarHorario($dbConnection, $userID, $nuevoHorarioID)
{
    $inscripcionID = obtenerInscripcionID($dbConnection, $userID);
    $nuevoTallerID = obtenerTallerIDPorHorario($dbConnection, $nuevoHorarioID);

    if ($nuevoTallerID === false) {
        echo json_encode(['success' => false, 'message' => 'Horario no válido para este usuario']);
        exit();
    }

    if (verificarDisponibilidadHorario($dbConnection, $nuevoTallerID, $nuevoHorarioID)) {
        $updateQuery = "UPDATE Inscripcion SET HorarioID = $nuevoHorarioID WHERE InscripcionID = $inscripcionID";
        mysqli_query($dbConnection, $updateQuery);
        echo json_encode(['success' => true]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'El nuevo horario ya está completo']);
        exit();
    }
}
*/
function cambiarHorario($dbConnection, $userID, $horarioAntiguoID, $horarioNuevoID)
{
    // Insertar la nueva fecha en la tabla temporales
    $queryInsertTemporal = "INSERT INTO temporales (UsuarioID, HorarioAntiguo, HorarioNuevo) 
                            VALUES ($userID, $horarioAntiguoID, $horarioNuevoID)";
    mysqli_query($dbConnection, $queryInsertTemporal);
    return true;
}

function obtenerInscripcionID($dbConnection, $userID)
{
    $query = "SELECT InscripcionID FROM Inscripcion WHERE UsuarioID = $userID";
    $result = mysqli_query($dbConnection, $query);
    $inscripcion = mysqli_fetch_assoc($result);
    return $inscripcion['InscripcionID'];
}

function obtenerTallerIDPorHorario($dbConnection, $horarioID)
{
    $query = "SELECT TallerID FROM Horario WHERE HorarioID = $horarioID";
    $result = mysqli_query($dbConnection, $query);
    $horario = mysqli_fetch_assoc($result);
    return $horario ? $horario['TallerID'] : false;
}

function verificarDisponibilidadHorario($dbConnection, $tallerID, $horarioID)
{
    $query = "SELECT COUNT(*) AS TotalInscritos FROM Inscripcion WHERE TallerID = $tallerID AND HorarioID = $horarioID";
    $result = mysqli_query($dbConnection, $query);
    $totalInscritos = mysqli_fetch_assoc($result)['TotalInscritos'];
    $limitePersonas = obtenerLimitePersonasTaller($dbConnection, $tallerID);
    return $totalInscritos < $limitePersonas;
}

function obtenerLimitePersonasTaller($dbConnection, $tallerID)
{
    $query = "SELECT LimitePersonas FROM Taller WHERE TallerID = $tallerID";
    $result = mysqli_query($dbConnection, $query);
    $taller = mysqli_fetch_assoc($result);
    return $taller ? $taller['LimitePersonas'] : 0;
}

function getDay()
{
    $diaSemana = date('N'); // 1 (lunes) hasta 7 (domingo)

    $nombreDia = '';
    switch ($diaSemana) {
        case 1:
            $nombreDia = 'Lunes';
            break;
        case 2:
            $nombreDia = 'Martes';
            break;
        case 3:
            $nombreDia = 'Miércoles';
            break;
        case 4:
            $nombreDia = 'Jueves';
            break;
        case 5:
            $nombreDia = 'Viernes';
            break;
        case 6:
            $nombreDia = 'Sábado';
            break;
        case 7:
            $nombreDia = 'Domingo';
            break;
    }

    return $nombreDia;
}
$actualDay = getDay();
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Próximos Talleres</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="custom.min.css">
</head>

<body>
    <div class="container p-3">
        <div class="text-center mb-5">
            <img class="logozig" src="logo.webp" alt="logo">
        </div>
        <h1 class="mb-4">Bienvenid@, <?php echo $nombreUsuario; ?></h1>
        <?php $foto = getFoto($dbConnection,$userID);?>
        <div class="row d-flex mb-4">
            <button id="cerrar-sesion-btn" class="btn btn-danger d-inline-block w-auto me-3">Cerrar Sesión</button>
            <button id="mostrar-formulario-edicion" class="btn btn-primary d-inline-block w-auto">Editar mis datos</button>
        </div>
        <div id="formulario-edicion" style="display: none;">
            <?php
            $query = "SELECT Nombre, Telefono, CodigoPostal FROM Usuario WHERE UsuarioID = $userID";
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
            <h2>Editar Datos</h2>
            <img width="100px" height="100px" style="object-fit:contain;" src="https://zigzagmerceriacreativa.es/talleres/fotos_usuarios/<?= $foto;?>">
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
                <button type="button" class="btn btn-primary" id="guardar-cambios-btn">Guardar Cambios</button>
            </form>
        </div>
        <h2 class="mb-4">Próximos Talleres</h1>
            <table id="tabla_personal" class="table">
                <thead>
                    <tr>
                        <th>Taller</th>
                        <th>Día</th>
                        <th>Hora</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscripciones as $inscripcion) : ?>
                        <tr>
                            <td><?= $inscripcion['NombreTaller'] ?></td>
                            <td><?= $inscripcion['DiaSemana'] ?></td>
                            <td><?= date('H:i', strtotime($inscripcion['HoraInicio'])) ?></td>
                            <td>
                                <?php if ($actualDay != $inscripcion['DiaSemana']) : ?>
                                    <button class="btn btn-primary cambiar-horario-btn" data-taller="<?= $inscripcion['TallerID'] ?>" data-horario="<?= $inscripcion['HorarioID'] ?>">Cambiar Horario</button>
                                    <button class="btn btn-danger cancelar-horario-btn" data-taller="<?= $inscripcion['TallerID'] ?>" data-horario="<?= $inscripcion['HorarioID'] ?>">Cancelar Horario</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div id="formulario-cambio" style="display: none;">
                <h2>Cambiar Horario</h2>
                <form id="form-cambio" class="mb-3">
                    <input type="hidden" id="horario_antiguo" name="horarioAntiguoID" value="valor_inicial">
                    <label for="nuevo_horario" class="form-label">Nuevo Horario:</label>
                    <select class="form-select" id="nuevo_horario" name="nuevoHorarioID">
                        <!-- Opciones de horarios se cargarán dinámicamente con JavaScript -->
                    </select>
                    <button type="button" class="btn btn-primary mt-2" id="confirmar-cambio-btn">Confirmar Cambio</button>
                </form>
            </div>
            <?php
            // Consulta para obtener el saldo del usuario
            $consultaSaldo = "SELECT Saldo FROM Usuario WHERE UsuarioID = $userID";
            $resultadoSaldo = mysqli_query($dbConnection, $consultaSaldo);
            $registroSaldo = mysqli_fetch_assoc($resultadoSaldo);
            $saldoUsuario = $registroSaldo['Saldo'];

            // Consulta adicional para verificar si el saldo es mayor que 0
            $mensajeSaldo = '';
            $opcionInscripcionTemporal = '';

            if ($saldoUsuario > 0) {
                $mensajeSaldo = "Inscripciones atrasadas disponibles: $saldoUsuario";
                $opcionInscripcionTemporal = '<br><br><button class="btn btn-success inscripcion-temporal-btn">Añadir Inscripción Temporal</button>';
            } else {
                $mensajeSaldo = 'No tienes inscripciones atrasadas disponibles.';
            }
            function obtenerTalleresUsuario($dbConnection, $userID)
            {
                $query = "SELECT DISTINCT T.TallerID, T.Nombre
                FROM Inscripcion I
                JOIN Taller T ON I.TallerID = T.TallerID
                WHERE I.UsuarioID = $userID";
                $result = mysqli_query($dbConnection, $query);
                return mysqli_fetch_all($result, MYSQLI_ASSOC);
            }

            echo $mensajeSaldo;
            echo $opcionInscripcionTemporal;

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

            mostrarFormularioEdicionButton.on("click", function() {
                formularioEdicion.css("display", "block");
            });

            guardarCambiosButton.on("click", function() {
                const nuevoNombre = $("#nombre").val();
                const nuevoTelefono = $("#telefono").val();
                const nuevoCodigoPostal = $("#codigo_postal").val();
                const nuevaContrasena = $("#nueva_contrasena").val();
                const fotoInput = $('#foto')[0];
                const fotoArchivo = fotoInput.files[0];

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
    </script>

</body>

</html>