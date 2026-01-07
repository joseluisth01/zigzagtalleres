<?php
if (!isset($dbConnection)) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $dbConnection = new mysqli($db_host, $db_user, $db_password, $db_name);
    $dbConnection->set_charset("utf8");
}

$query = "SELECT * FROM festivos";
$result = mysqli_query($dbConnection, $query);
$festivos = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<h2>Festivos</h2>
<table id="table_festivos" class="table">
    <thead>
        <tr>
            <th>FestivoID</th>
            <th>Fecha</th>
            <th>Editar</th>
            <th>Eliminar</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($festivos as $festivo) : ?>
            <tr>
                <td><?= $festivo['FestivoID'] ?></td>
                <td><?= $festivo['Fecha'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary edit-festivo" data-festivoid="<?= $festivo['FestivoID'] ?>">Editar</button>
                </td>
                <td>
                    <button class="btn btn-sm btn-danger delete-festivo" data-festivoid="<?= $festivo['FestivoID'] ?>">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<h2>Añadir Festivo</h2>
<form id="form-add-festivo">
    <div class="mb-3">
        <label for="fecha_festivo" class="form-label">Fecha del Festivo</label>
        <input type="date" class="form-control" id="fecha_festivo" name="fecha_festivo" required>
    </div>
    <button type="submit" class="btn btn-primary">Añadir Festivo</button>
</form>

<!-- Formulario para editar festivo -->
<form id="edit-festivo-form" class="modal fade" tabindex="-1" aria-labelledby="formulario-edicionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title" id="formulario-edicionEditarLabel">Editar Festivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" id="edit-festivo-id" name="edit-festivo-id">
            <div class="mb-3">
                <label for="edit-fecha" class="form-label">Fecha</label>
                <input type="date" class="form-control" id="edit-fecha" name="edit-fecha" required>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
    </div>
</form>

<!-- Script para manejar la edición de festivo -->
<script>
    $(".edit-festivo").click(function() {
        var festivoId = $(this).data("festivoid");
        var festivoRow = $(this).closest("tr");
        var fecha = festivoRow.find("td:eq(1)").text();

        $("#edit-festivo-id").val(festivoId);
        $("#edit-fecha").val(fecha);

        $("#edit-festivo-form").modal("show");
    });

    $("#edit-festivo-form").submit(function(event) {
        event.preventDefault();

        var festivoId = $("#edit-festivo-id").val();
        var fecha = $("#edit-fecha").val();

        // Realizar la solicitud Ajax para actualizar los datos del festivo
        $.ajax({
            type: "POST",
            url: "admin.php", // Archivo PHP para editar festivo
            data: {
                action: "editarFestivo",
                festivoId: festivoId,
                fecha: fecha
            },
            success: function(response) {
                if (response.success) {
                    $("#festivos").load("admintabs/festivos.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    $("#edit-festivo-form .btn-close").click();
                } else {
                    console.log("Error al editar festivo:", response);
                    $("#festivos").load("admintabs/festivos.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    $("#edit-festivo-form .btn-close").click();
                }
            },
            error: function(xhr, status, error) {
                console.log("Error en la solicitud Ajax:", error);
            }
        });
    });
</script>

<!-- Script para manejar la eliminación de festivo -->
<script>
    $(".delete-festivo").click(function() {
        var festivoId = $(this).data("festivoid");

        // Realizar la solicitud Ajax para eliminar el festivo
        $.ajax({
            type: "POST",
            url: "admin.php", // Archivo PHP para eliminar festivo
            data: {
                action: "eliminarFestivo",
                festivoId: festivoId
            },
            success: function(response) {
                if (response.success) {
                    $("#festivos").load("admintabs/festivos.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    location.reload();
                } else {
                    console.log("Error al eliminar festivo:", response);
                    $("#festivos").load("admintabs/festivos.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                console.log("Error en la solicitud Ajax:", error);
            }
        });
    });
</script>

<script>
    $("#form-add-festivo").submit(function(event) {
        event.preventDefault();

        // Obtener los valores del formulario
        var fecha = $("#fecha_festivo").val();

        // Realizar la solicitud Ajax para añadir el festivo
        $.ajax({
            type: "POST",
            url: "admin.php", // Archivo PHP para añadir festivo
            data: {
                action: "addFestivo",
                fecha: fecha
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar la tabla de festivos después de añadir uno nuevo
                    $("#festivos").load("admintabs/festivos.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });

                    // Limpiar el formulario
                    $("#festivo-form")[0].reset();

                    // Cerrar el modal si está abierto
                    $("#add-festivo-form .btn-close").click();
                } else {
                    console.log("Error al añadir festivo:", response);
                    $("#festivos").load("admintabs/festivos.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log("Error en la solicitud Ajax:", error);
            }
        });
    });
</script>



<!-- DataTable para la tabla de festivos -->
<script>
    var table_festivos = new DataTable('#table_festivos', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
        },
    });
</script>
