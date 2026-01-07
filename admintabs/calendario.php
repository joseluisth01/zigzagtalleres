<?php
if (!isset($dbConnection)) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $dbConnection = new mysqli($db_host, $db_user, $db_password, $db_name);
    $dbConnection->set_charset("utf8");
}
$usuario_tipo = $_SESSION['user_tipo'];
$usuario_id = $_SESSION['user_id'];
if($usuario_tipo == 3){
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
    (
        SELECT COUNT(*) 
        FROM inscripcion AS I 
        WHERE I.HorarioID = horario.HorarioID AND I.FechaID = fechas.IDFecha
    ) AS CantidadInscritos,
    (
        SELECT GROUP_CONCAT(usuario.Nombre SEPARATOR '<br> ') 
        FROM inscripcion AS I 
        JOIN usuario ON I.UsuarioID = usuario.UsuarioID 
        WHERE I.HorarioID = horario.HorarioID AND I.FechaID = fechas.IDFecha
    ) AS Inscritos
FROM
    horario
    INNER JOIN taller ON horario.TallerID = taller.TallerID
    INNER JOIN fechas ON horario.HorarioID = fechas.HorarioID
    INNER JOIN usuario ON taller.IDProfesor = usuario.UsuarioID
    WHERE
        usuario.UsuarioID = $usuario_id
        AND fechas.Fecha NOT IN (
            SELECT Fecha
            FROM festivos
        )";
}else{
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
(SELECT COUNT(*) FROM inscripcion AS I WHERE I.HorarioID = horario.HorarioID AND I.FechaID = fechas.IDFecha) AS CantidadInscritos,
(
        SELECT GROUP_CONCAT(usuario.Nombre SEPARATOR '<br> ') 
        FROM inscripcion AS I 
        JOIN usuario ON I.UsuarioID = usuario.UsuarioID 
        WHERE I.HorarioID = horario.HorarioID AND I.FechaID = fechas.IDFecha
    ) AS Inscritos
FROM
horario
INNER JOIN taller ON horario.TallerID = taller.TallerID
INNER JOIN fechas ON horario.HorarioID = fechas.HorarioID
INNER JOIN usuario ON taller.IDProfesor = usuario.UsuarioID
WHERE
        1 = 1
        AND fechas.Fecha NOT IN (
            SELECT Fecha
            FROM festivos
        )";
}


$result_calendario = mysqli_query($dbConnection, $query_calendario);
$calendario_array = mysqli_fetch_all($result_calendario, MYSQLI_ASSOC);
foreach ($calendario_array as $evento) {
    $events[] = array(
        'id' => $evento['IDFecha'],
        'title' => $evento['NombreTaller'],
        'tallerID' => $evento['TallerID'],
        'horarioID' => $evento['HorarioID'],
        'profesor' => $evento['NombreProfesor'],
        'start' => $evento['Fecha'] . 'T' . $evento['HoraInicio'],
        'end' => $evento['Fecha'] . 'T' . $evento['HoraFin'],
        'CantidadInscritos' => $evento['CantidadInscritos'],
        'inscritos' => $evento['Inscritos'],
        'horaInicio' => $evento['HoraInicio'],
        'fecha' => $evento['Fecha']
    );
}

?>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales-all.global.min.js'></script>

<div class="<?= $usuario_tipo; ?>"></div>
<div class="gotodate mb-3">
    <input type="date" name="gotodate" id="gotodate">
    <button type="button" class="ms-3 btn btn-primary" id="gotodatebutton">Buscar</button>
</div>

<div id="calendario_eventos"></div>
<div id="listado-eventos"></div>
<!-- Modal Cancelar Cita -->
<div class="modal fade" id="modalInfo" tabindex="-1" aria-labelledby="modalInfoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalInfoLabel">Información</h5>
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
                        <p><b>Usuarios: (<span class="info-cantidad"></span>)</b>
                        <div class="info-usuarios"></div>
                        </p>
                    </div>
                </div>
            </div>
        </div>
<script>
    $(document).ready(function() {
        setTimeout(function() {
            var calendarEl = document.getElementById('calendario_eventos');
            var listadoEventosEl = document.getElementById('listado-eventos');
            

            var calendar = new FullCalendar.Calendar(calendarEl, {
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode($events); ?>,
                locale: 'es',
                height: 'auto',
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: false
                },
                eventDidMount: function(info) {
                    var evento = info.event;
                    var plazas = 6;
                    var extra = "inscrito";
                    /* INSCRITO */
                        var inscripcion = document.createElement('span');
                        var plazas = 6 - evento.extendedProps.CantidadInscritos;
                        inscripcion.textContent = "plazas: " + plazas;
                        inscripcion.classList.add("no-inscrito");
                        inscripcion.classList.add("plazas");
                        if (plazas <= 0) {
                            var extra = "block";
                        } else {
                            var extra = "disponible";
                        }
                    

                    // Crear un div para el evento
                    var eventoDiv = document.createElement('div');
                    eventoDiv.classList.add('evento');
                    eventoDiv.classList.add(extra);
                    eventoDiv.id = evento.id; // Usar el ID de la fecha

                    // Crear un párrafo para la fecha y la hora de inicio
                    var fechaInicioP = document.createElement('span');
                    fechaInicioP.textContent = evento.start.toLocaleString();
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
                    //listadoEventosEl.appendChild(eventoDiv);
                },
                themeSystem: 'Flatly',
eventClick: function(info) {
    var evento = info.event;
    var fecha_taller = formatearFecha(evento.extendedProps.fecha);
    var hora_taller = formatearHora(evento.extendedProps.horaInicio);
    var profesor = evento.extendedProps.profesor;
    var taller = evento.title;
    var inscritos = evento.extendedProps.inscritos;
    var cantidad = evento.extendedProps.CantidadInscritos;

    // Generar la lista de usuarios con botones de eliminar
    var usuariosList = inscritos.split('<br> ').map(function(usuario) {
        return '<div>' + usuario + ' <button class="btn btn-danger btn-sm eliminar-usuario" data-usuario="' + usuario + '" data-horario="' + evento.extendedProps.horarioID + '" data-fecha="' + evento.id + '">Eliminar</button></div>';
    }).join('');

    $('#modalInfo .info-cantidad').html(cantidad);
    $('#modalInfo .info-taller').html(taller);
    $('#modalInfo .info-hora').html(hora_taller);
    $('#modalInfo .info-fecha').html(fecha_taller);
    $('#modalInfo .info-profesor').html(profesor);
    $('#modalInfo .info-usuarios').html(usuariosList); // Usa la lista generada
    $('#modalInfo').modal('show'); // Muestra el modal
},

            });

            calendar.render();

            $('#gotodatebutton').on('click', function() {
                // Obtener los valores necesarios del modal
                var gotodate = $('#gotodate').val();
                calendar.gotoDate( gotodate )
            });

        }, 100);


    });
    $(document).on('click', '.eliminar-usuario', function() {
        var usuario = $(this).data('usuario');
        var horarioID = $(this).data('horario');
        var fechaID = $(this).data('fecha');

        if (confirm("¿Estás seguro de que deseas eliminar a " + usuario + " de este taller?")) {
            $.ajax({
                type: "POST",
                url: "admin.php", // Archivo PHP que manejará la eliminación
                data: {
                    action: "eliminarUsuarioDeTaller",
                    usuario: usuario,
                    horarioID: horarioID,
                    fechaID: fechaID
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        alert("Usuario eliminado exitosamente.");
                        location.reload(); // Recarga la página para reflejar los cambios
                    } else {
                        alert("Error al eliminar el usuario: " + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error en la solicitud Ajax:", error);
                    alert("Error en la solicitud Ajax");
                }
            });
        }
    });
</script>
<script>
   
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