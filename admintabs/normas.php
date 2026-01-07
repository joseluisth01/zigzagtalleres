<?php
if (!isset($dbConnection)) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $dbConnection = new mysqli($db_host, $db_user, $db_password, $db_name);
    $dbConnection->set_charset("utf8");
}

// Obtener las normas actuales
$query = "SELECT Normas FROM admin";
$result = mysqli_query($dbConnection, $query);
$row = mysqli_fetch_assoc($result);
$normasActuales = $row['Normas'];
?>

<div class="container">
    <h2>Normas</h2>
    
    <form id="normas-form">
        <div class="mb-3">
            <label for="normas" class="form-label">Texto de Normas</label>
            <textarea id="normas-textarea" name="normas-textarea"></textarea>
        </div>
        <button type="submit" name="submitNormas" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>

<script>
    $(document).ready(function() {
        // Configurar el editor TinyMCE después de cargar el contenido de la base de datos
        tinymce.init({
            selector: '#normas-textarea',
            plugins: 'tinycomments mentions anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed permanentpen footnotes advtemplate advtable advcode editimage tableofcontents mergetags powerpaste tinymcespellchecker autocorrect a11ychecker typography inlinecss',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | align lineheight | tinycomments | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
            tinycomments_mode: 'embedded',
            tinycomments_author: 'Author name',
            init_instance_callback: function (editor) {
                // Obtener el contenido de la base de datos y establecerlo en el editor
                var normasActuales = <?php echo json_encode($normasActuales); ?>;
                editor.setContent(normasActuales);
            }
        });

        $('#normas-form').submit(function(event) {
            event.preventDefault();
            var formData = $(this).serialize();
            formData += '&action=updateNormas';

            // Obtener el contenido del editor TinyMCE
            var tinyMCEContent = tinymce.get('normas-textarea').getContent();
            formData += '&normas-textarea=' + encodeURIComponent(tinyMCEContent);

            // Código PHP para actualizar las normas en la base de datos
            $.ajax({
                url: 'admin.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log(response);
                    // Puedes añadir aquí lógica adicional si es necesario
                    location.reload();
                },
                error: function(error) {
                    console.log('Error al actualizar las normas');
                }
            });
        });
    });
</script>
