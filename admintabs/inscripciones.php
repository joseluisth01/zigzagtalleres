<?php
if (!isset($dbConnection)) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $dbConnection = new mysqli($db_host, $db_user, $db_password, $db_name);
    $dbConnection->set_charset("utf8");
} ?>
<div class="container">
    <h2>Listado de Usuarios Inscritos en Talleres</h2>
    <table id="tabla_inscripciones" class="table">
        <thead>
            <tr>
                <th>Taller</th>
                <th>Usuario</th>
                <th>Acciones</th> <!-- Agregamos una columna para acciones -->
            </tr>
        </thead>
        <tbody>
            <?php
            $queryInscripciones = "SELECT
            taller.TallerID as TallerID,
            taller.Nombre AS Taller,
            usuario.Nombre AS Usuario,
            usuario.UsuarioID AS UsuarioID
        FROM
            inscripcion
        JOIN
            usuario ON inscripcion.UsuarioID = usuario.UsuarioID
        JOIN
            taller ON inscripcion.TallerID = taller.TallerID
        GROUP BY
            taller.TallerID, usuario.UsuarioID;
        ";
            $resultInscripciones = mysqli_query($dbConnection, $queryInscripciones);
            while ($rowInscripcion = mysqli_fetch_assoc($resultInscripciones)) {
                echo '<tr>';
                echo '<td>' . $rowInscripcion['Taller'] . '</td>';
                echo '<td>' . $rowInscripcion['Usuario'] . '</td>';
                echo '<td>';
                echo '<button class="me-2 btn btn-sm btn-danger delete-inscripcion" data-userid="' . $rowInscripcion['UsuarioID'] . '" data-tallerid="' . $rowInscripcion['TallerID'] . '">Eliminar</button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>


<script>
    $(document).ready(function() {

        // Eliminar inscripción
        $(document).on('click', '.delete-inscripcion', function() {
            var userid = $(this).data('userid');
            var tallerid = $(this).data('tallerid');

            // Realizar la solicitud AJAX para eliminar la inscripción
            $.ajax({
                url: 'admin.php',
                type: 'POST',
                data: {
                    action: 'eliminarInscripcion',
                    userid: userid,
                    tallerid: tallerid
                },
                success: function(response) {
                    if (response.success) {
                        $("#inscripciones").load("admintabs/inscripciones.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
                        location.reload();
                    } else {
                        console.log(response.error);
                        $("#inscripciones").load("admintabs/inscripciones.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Error en la solicitud Ajax:", error);
                    alert("Error en la solicitud Ajax");
                }
            });
        });
    });

    var tabla_inscripciones = new DataTable('#tabla_inscripciones', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
        },
    });
</script>