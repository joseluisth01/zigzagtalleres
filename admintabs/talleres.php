<?php
if (!isset($dbConnection)) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $dbConnection = new mysqli($db_host, $db_user, $db_password, $db_name);
    $dbConnection->set_charset("utf8");
}
$query = "SELECT t.TallerID, t.Nombre as NombreTaller, u.Nombre as NombreProfesor , u.UsuarioID as IDProfesor
          FROM taller t
          LEFT JOIN usuario u ON t.IDProfesor = u.UsuarioID";
$result = mysqli_query($dbConnection, $query);
$talleres = mysqli_fetch_all($result, MYSQLI_ASSOC);
// Consulta para obtener la lista de profesores
$queryProfesores = "SELECT UsuarioID, Nombre FROM usuario WHERE TipoUsuarioID = 3"; // TipoUsuarioID = 3 para profesores
$resultProfesores = mysqli_query($dbConnection, $queryProfesores);
$profesores = mysqli_fetch_all($resultProfesores, MYSQLI_ASSOC);
?>

<div class="container">
    <h2>Talleres</h2>
    <table id="table_talleres" class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Profesor</th>
                <th>Editar</th>
                <th>Eliminar taller</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($talleres as $taller) : ?>
                <tr>
                    <td><?= $taller['NombreTaller'] ?></td>
                    <td><?= $taller['NombreProfesor'] ?></td>

                    <td>
                        <button class="btn btn-sm btn-primary edit-taller" data-profesorid="<?= $taller['IDProfesor'] ?>" data-tallerid="<?= $taller['TallerID'] ?>">Editar</button>
                    </td>
                    <td><button class="btnEliminarTaller btn btn-danger" data-tallerid="<?= $taller['TallerID'] ?>">Eliminar</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3 class="my-3">Agregar Taller</h3>
    <form id="add-taller-form">
        <div class="mb-3">
            <label for="nombre_taller" class="form-label">Nombre del Taller</label>
            <input type="text" class="form-control" id="nombre_taller" name="nombre_taller" required>
        </div>
        <div class="mb-3">
            <label for="profesor_id" class="form-label">Profesor</label>
            <select class="form-control" id="profesor_id" name="profesor_id">
                <?php foreach ($profesores as $profesor) : ?>
                    <option value="<?= $profesor['UsuarioID'] ?>"><?= $profesor['Nombre'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Agregar Taller</button>
    </form>
</div>

<script>
    $(document).ready(function() {
        // Agregar Taller mediante AJAX
        $("#add-taller-form").submit(function(e) {
            e.preventDefault();

            var formData = {
                action: 'addTaller',
                nombre_taller: $("#nombre_taller").val(),
                profesor_id: $("#profesor_id").val()
            };

            $.ajax({
                type: "POST",
                url: "admin.php", // Cambia la URL según tu configuración
                data: formData,
                dataType: "json",
                success: function(data) {
                    if (data.success) {
                        console.log("Taller agregado exitosamente");
                        $("#horarios").load("admintabs/horarios.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
                        $("#talleres").load("admintabs/talleres.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
                        location.reload();
                    } else if (data.error) {
                        console.log("Error al agregar taller:", data.error);
                        $("#horarios").load("admintabs/horarios.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
                        $("#talleres").load("admintabs/talleres.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
                        location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Error en la solicitud AJAX:");
                    console.log("Estado de la solicitud:", status);
                    console.log("Mensaje del servidor:", xhr.responseText);
                    console.log("Error:", error);
                }
            });
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".btnEliminarTaller").click(function() {
            var tallerID = $(this).data("tallerid");

            if (confirm("¿Estás seguro de que deseas eliminar este taller?")) {
                $.ajax({
                    type: "POST",
                    url: "admin.php", // Archivo PHP que procesará la eliminación
                    data: {
                        action: 'eliminarTaller',
                        tallerID: tallerID
                    },
                    dataType: 'json', // Asegurarse de que se interprete como JSON
                    success: function(response) {
                        console.log("Response:", response);

                        if (response.success) {
                            console.log("Taller borrado exitosamente.");
                            $("#horarios").load("admintabs/horarios.php", {
                                db_host: "<?php echo $db_host; ?>",
                                db_user: "<?php echo $db_user; ?>",
                                db_password: "<?php echo $db_password; ?>",
                                db_name: "<?php echo $db_name; ?>"
                            });
                            $("#talleres").load("admintabs/talleres.php", {
                                db_host: "<?php echo $db_host; ?>",
                                db_user: "<?php echo $db_user; ?>",
                                db_password: "<?php echo $db_password; ?>",
                                db_name: "<?php echo $db_name; ?>"
                            });
                            location.reload();
                        } else {
                            console.log("Error al borrar el taller:", response.error);
                            $("#horarios").load("admintabs/horarios.php", {
                                db_host: "<?php echo $db_host; ?>",
                                db_user: "<?php echo $db_user; ?>",
                                db_password: "<?php echo $db_password; ?>",
                                db_name: "<?php echo $db_name; ?>"
                            });
                            $("#talleres").load("admintabs/talleres.php", {
                                db_host: "<?php echo $db_host; ?>",
                                db_user: "<?php echo $db_user; ?>",
                                db_password: "<?php echo $db_password; ?>",
                                db_name: "<?php echo $db_name; ?>"
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log("Error en la solicitud Ajax:");
                        console.log("Status:", status);
                        console.log("Error:", error);
                        alert("Error en la solicitud Ajax");
                    }
                });
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".edit-taller").click(function() {
            var tallerId = $(this).data("tallerid");
            var profesorId = $(this).data("profesorid");
            var tallerRow = $(this).closest("tr");
            var nombreTaller = tallerRow.find("td:eq(0)").text();
            // Seleccionar al profesor correcto en el select
            $("#edit-profesor_id option").removeAttr("selected"); // Limpiar selección previa
            $("#edit-profesor_id option[value='" + profesorId + "']").attr("selected", "selected");

            console.log("taller " + tallerId);
            console.log("profesorId " + profesorId);
            $("#edit-taller-id").val(tallerId);
            $("#edit-nombre_taller").val(nombreTaller);
            $("#edit-taller-form").modal("show");
        });

        $("#edit-taller-form").submit(function(event) {
            event.preventDefault();

            var tallerId = $("#edit-taller-id").val();
            var nombreTaller = $("#edit-nombre_taller").val();
            var profesorId = $("#edit-profesor_id").val();

            // Realizar la solicitud Ajax para actualizar los datos del taller
            $.ajax({
                type: "POST",
                url: "admin.php", // Archivo PHP para editar taller
                data: {
                    action: "editarTaller",
                    tallerId: tallerId,
                    nombreTaller: nombreTaller,
                    profesorId: profesorId
                },
                success: function(response) {
                    if (response.success) {
                        console.log("Datos del taller actualizados");
                        $("#talleres").load("admintabs/talleres.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
                        $("#edit-taller-form .btn-close").click();
                    } else {
                        console.log(response);
                        $("#talleres").load("admintabs/talleres.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
                        $("#edit-taller-form .btn-close").click();

                    }
                },
                error: function(xhr, status, error) {
                    console.log("Error en la solicitud Ajax:", error);
                    alert("Error en la solicitud Ajax");
                }
            });
        });
    });

    //$('#table_talleres').DataTable();
    var table_talleres = new DataTable('#table_talleres', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
        },
    });
</script>