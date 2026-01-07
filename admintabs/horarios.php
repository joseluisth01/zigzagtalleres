<?php
if (!isset($dbConnection)) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $dbConnection = new mysqli($db_host, $db_user, $db_password, $db_name);
    $dbConnection->set_charset("utf8");
}
$query = "SELECT taller.Nombre, horario.DiaSemana,horario.HorarioID, horario.HoraInicio, horario.HoraFin, horario.Limite FROM taller
          INNER JOIN horario ON taller.TallerID = horario.TallerID";
$result = mysqli_query($dbConnection, $query);
$horarios = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!-- <script>
    $(document).ready(function() {
    $('#horario-form').submit(function(event) {
        event.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=addHorario'; // Agregar la acción correspondiente

        $.ajax({
            url: 'admin.php', // Ajusta la URL para manejar la acción en este archivo
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log("Horario añadido con éxito");
                // Recargar toda la página después de la inserción exitosa
                location.reload(); 
            },
            error: function(error) {
                alert("Error al añadir el horario");
                // También puedes recargar la página en caso de error si lo prefieres
                location.reload();
            }
        });
    });
});

</script> -->
<h2>Horarios de Talleres</h2>
<table id="table_horarios" class="table">
    <thead>
        <tr>
            <th>Taller</th>
            <th>Día de la Semana</th>
            <th>Hora de Inicio</th>
            <th>Hora de Fin</th>
            <th>Límite</th>
            <th>Editar</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($horarios as $horario) : ?>
            <tr>
                <td><?= $horario['Nombre'] ?></td>
                <td><?= $horario['DiaSemana'] ?></td>
                <td><?= $horario['HoraInicio'] ?></td>
                <td><?= $horario['HoraFin'] ?></td>
                <td><?= $horario['Limite'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary edit-horario" data-horarioid="<?= $horario['HorarioID'] ?>">Editar</button>
                    <button class="btn btn-sm btn-danger delete-horario" data-horarioid="<?= $horario['HorarioID'] ?>">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<form id="edit-horario-form" class="modal fade" tabindex="-1" aria-labelledby="formulario-edicionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title" id="formulario-edicionEditarLabel">Editar Horario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <input type="hidden" id="edit-horario-id" name="edit-horario-id">
                <div class="mb-3">
                    <label for="edit-dia_semana" class="form-label">Día de la Semana</label>
                    <select class="form-select" id="edit-dia_semana" name="edit-dia_semana" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miercoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sabado">Sábado</option>
                        <option value="Domingo">Domingo</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit-hora_inicio" class="form-label">Hora de Inicio</label>
                    <input type="time" class="form-control" id="edit-hora_inicio" name="edit-hora_inicio" required>
                </div>
                <div class="mb-3">
                    <label for="edit-hora_fin" class="form-label">Hora de Fin</label>
                    <input type="time" class="form-control" id="edit-hora_fin" name="edit-hora_fin" required>
                </div>
                <div class="mb-3">
                    <label for="limite" class="form-label">Límite de Personas</label>
                    <input type="number" class="form-control" id="limite" name="limite" value="6" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </form>

<h2>Añadir Horario a Taller</h2>
<form id="horario-form">
    <div class="mb-3">
        <label for="taller_id" class="form-label">Seleccionar Taller</label>
        <select class="form-select" id="taller_id" name="taller_id" required>
            <?php

            $query = "SELECT TallerID, Nombre FROM taller";
            $result = mysqli_query($dbConnection, $query);

            while ($row = mysqli_fetch_assoc($result)) {
                echo '<option value="' . $row['TallerID'] . '">' . $row['Nombre'] . '</option>';
            }
            ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="dia_semana" class="form-label">Día de la Semana</label>
        <select class="form-select" id="dia_semana" name="dia_semana" required>
            <option value="Lunes">Lunes</option>
            <option value="Martes">Martes</option>
            <option value="Miercoles">Miércoles</option>
            <option value="Jueves">Jueves</option>
            <option value="Viernes">Viernes</option>
            <option value="Sabado">Sábado</option>
            <option value="Domingo">Domingo</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="hora_inicio" class="form-label">Hora de Inicio</label>
        <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
    </div>
    <div class="mb-3">
        <label for="hora_fin" class="form-label">Hora de Fin</label>
        <input type="time" class="form-control" id="hora_fin" name="hora_fin" required>
    </div>
    <div class="mb-3">
        <label for="limite" class="form-label">Límite de Personas</label>
        <input type="number" class="form-control" id="limite" name="limite" value="6" required>
    </div>
    <button type="submit" class="btn btn-primary">Añadir Horario</button>
</form>
<script>
    $(".edit-horario").click(function() {
        var horarioId = $(this).data("horarioid");
        var horarioRow = $(this).closest("tr");
        var diaSemana = horarioRow.find("td:eq(1)").text();
        var horaInicio = horarioRow.find("td:eq(2)").text();
        var horaFin = horarioRow.find("td:eq(3)").text();
        var limite = horarioRow.find("td:eq(4)").text(); // Nuevo campo

        $("#edit-horario-id").val(horarioId);
        $("#edit-dia_semana").val(diaSemana);
        $("#edit-hora_inicio").val(horaInicio);
        $("#edit-hora_fin").val(horaFin);
        $("#limite").val(limite);


        $("#edit-horario-form").modal("show");
    });
    $("#edit-horario-form").submit(function(event) {
        event.preventDefault();

        var horarioId = $("#edit-horario-id").val();
        var horaInicio = $("#edit-hora_inicio").val();
        var diaSemana = $("#edit-dia_semana").val();
        var horaFin = $("#edit-hora_fin").val();
        var limite = $("#limite").val();

        // Realizar la solicitud Ajax para actualizar los datos del horario
        $.ajax({
            type: "POST",
            url: "admin.php", // Archivo PHP para editar horario
            data: {
                action: "editarHorario",
                horarioId: horarioId,
                horaInicio: horaInicio,
                diaSemana: diaSemana,
                horaFin: horaFin,
                limite: limite
            },
            success: function(response) {
                if (response.success) {
                    $("#horarios").load("admintabs/horarios.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    $("#edit-horario-form .btn-close").click();
                } else {
                    $("#horarios").load("admintabs/horarios.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    $("#edit-horario-form .btn-close").click();

                }
            },
            error: function(xhr, status, error) {
                console.log("Error en la solicitud Ajax:", error);
                alert("Error en la solicitud Ajax");
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#horario-form').submit(function(event) {
            event.preventDefault();
            var formData = $(this).serialize();
            formData += '&action=addHorario'; // Agregar la acción correspondiente

            $.ajax({
                url: 'admin.php', // Ajusta la URL para manejar la acción en este archivo
                type: 'POST',
                data: formData,
                success: function(response) {
                    $("#horarios").load("admintabs/horarios.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    location.reload();
                    console.log("Horario añadido con éxito");
                },
                error: function(error) {
                    $("#horarios").load("admintabs/horarios.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    location.reload();
                }
            });
        });
    });
</script>
<script>
    $(".delete-horario").click(function() {
    var horarioId = $(this).data("horarioid");
    
    // Realizar la solicitud Ajax para eliminar el horario
    $.ajax({
        type: "POST",
        url: "admin.php", // Archivo PHP para eliminar horario
        data: {
            action: "eliminarHorario",
            horarioId: horarioId
        },
        success: function(response) {
            if (response.success) {
                $("#horarios").load("admintabs/horarios.php", {
                    db_host: "<?php echo $db_host; ?>",
                    db_user: "<?php echo $db_user; ?>",
                    db_password: "<?php echo $db_password; ?>",
                    db_name: "<?php echo $db_name; ?>"
                });
                location.reload();
                alert("Horario eliminado");
            } else {
                $("#horarios").load("admintabs/horarios.php", {
                    db_host: "<?php echo $db_host; ?>",
                    db_user: "<?php echo $db_user; ?>",
                    db_password: "<?php echo $db_password; ?>",
                    db_name: "<?php echo $db_name; ?>"
                });
                location.reload();
                alert("Horario eliminado");
            }
        },
        error: function(xhr, status, error) {
            console.log("Error en la solicitud Ajax:", error);
            alert("Error en la solicitud Ajax");
        }
    });
});



//$('#table_horarios').DataTable();
var table_horarios = new DataTable('#table_horarios', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
        },
    });
</script>