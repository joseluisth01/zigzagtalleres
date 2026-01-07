<?php
if (!isset($dbConnection)) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $dbConnection = new mysqli($db_host, $db_user, $db_password, $db_name);
    $dbConnection->set_charset("utf8");
}
$query = "SELECT * FROM usuario";
$result = mysqli_query($dbConnection, $query);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!-- Tabla de usuarios -->
<h3>Listado de Usuarios</h3>
<table id="listado_usuarios" class="table mb-4">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>DNI</th>
            <th>Teléfono</th>
            <th>Código Postal</th>
            <th>Saldo</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Acciones</th>
            <th>Editar</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user) : ?>
            <?php
            $tipoUsuario = "";
            if ($user['TipoUsuarioID'] == 1) {
                $tipoUsuario = "Admin";
            } elseif ($user['TipoUsuarioID'] == 2) {
                $tipoUsuario = "Alumno";
            } elseif ($user['TipoUsuarioID'] == 3) {
                $tipoUsuario = "Profesor";
            }
            ?>
            <tr>
                <td><?= $user['UsuarioID'] ?></td>
                <td><?= $user['Nombre'] ?></td>
                <td><?= $user['DNI'] ?></td>
                <td><?= $user['Telefono'] ?></td>
                <td><?= $user['CodigoPostal'] ?></td>
                <td><?= $user['Saldo'] ?></td>
                <td><?= $tipoUsuario; ?></td>
                <td><?= $user['Activo'] == 1 ? 'Activado' : 'Desactivado' ?></td>
                <td>
                    <button class="btn btn-sm btn-primary toggle-user" data-userid="<?= $user['UsuarioID'] ?>" data-userstatus="<?= $user['Activo'] ?>">Activar/Desactivar</button>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary edit-user" data-userid="<?= $user['UsuarioID'] ?>" data-userfoto="<?= $user['Foto']; ?>">Editar</button>
                </td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Formulario para editar usuario -->
<?php
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
?>

<!-- CSS para el formulario de selección múltiple -->
<style>
.user-selection-container {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    background-color: #f8f9fa;
}

.user-checkbox {
    margin: 0.25rem 0;
}

.selected-users-container {
    background-color: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-top: 1rem;
    min-height: 100px;
}

.selected-user-tag {
    display: inline-block;
    background-color: #007bff;
    color: white;
    padding: 0.25rem 0.5rem;
    margin: 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.remove-user {
    background: none;
    border: none;
    color: white;
    margin-left: 0.5rem;
    cursor: pointer;
    font-weight: bold;
}

.select-all-btn {
    margin-bottom: 1rem;
}

.search-highlight {
    background-color: yellow;
    font-weight: bold;
}
</style>

<!-- Formulario para agregar usuario -->
<button id="agregar-usuario" class="btn btn-sm btn-primary my-3">Nuevo Usuario</button>
<form id="add-user-form" style="display:none;">
    <h3>Agregar Usuario</h3>
    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" class="form-control" id="nombre" name="nombre" required>
    </div>
    <div class="mb-3">
        <label for="DNI" class="form-label">DNI</label>
        <input type="text" class="form-control" id="DNI" name="DNI" required>
    </div>
    <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono</label>
        <input type="text" class="form-control" id="telefono" name="telefono" required>
    </div>
    <div class="mb-3">
        <label for="codigo_postal" class="form-label">Código Postal</label>
        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" required>
    </div>
    <div class="mb-3">
        <label for="tipo-usuario" class="form-label">Tipo de Usuario</label>
        <select class="form-select" id="tipo-usuario" name="tipo-usuario" required>
            <option value="2">Alumno</option>
            <option value="3">Profesor</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Agregar Usuario</button>
</form>

<h2 class="mt-3">Inscribir Múltiples Usuarios en Taller</h2>
<form id="inscripcion-multiple-form">
    <!-- Búsqueda de usuarios -->
    <div class="mb-3">
        <label for="search-user-multiple" class="form-label">Buscar Usuarios:</label>
        <input type="text" id="search-user-multiple" class="form-control" placeholder="Escribe para buscar usuarios...">
    </div>
    
    <!-- Botones de selección -->
    <div class="mb-3">
        <button type="button" class="btn btn-outline-primary btn-sm select-all-btn" id="select-all-users">Seleccionar Todos</button>
        <button type="button" class="btn btn-outline-secondary btn-sm select-all-btn" id="clear-all-users">Limpiar Selección</button>
        <button type="button" class="btn btn-outline-info btn-sm select-all-btn" id="select-visible-users">Seleccionar Visibles</button>
    </div>
    
    <!-- Lista de usuarios con checkboxes -->
    <div class="mb-3">
        <label class="form-label">Seleccionar Usuarios:</label>
        <div class="user-selection-container" id="user-selection-container">
            <?php
            $queryUsuarios = "SELECT UsuarioID, Nombre FROM usuario WHERE TipoUsuarioID = 2 AND Activo = 1"; // Solo alumnos activos
            $resultUsuarios = mysqli_query($dbConnection, $queryUsuarios);
            
            while ($rowUsuario = mysqli_fetch_assoc($resultUsuarios)) {
                echo '<div class="user-checkbox">
                        <div class="form-check">
                            <input class="form-check-input user-checkbox-input" type="checkbox" 
                                   value="' . $rowUsuario['UsuarioID'] . '" id="user-' . $rowUsuario['UsuarioID'] . '" 
                                   data-name="' . htmlspecialchars($rowUsuario['Nombre']) . '">
                            <label class="form-check-label" for="user-' . $rowUsuario['UsuarioID'] . '">
                                ' . htmlspecialchars($rowUsuario['Nombre']) . '
                            </label>
                        </div>
                      </div>';
            }
            ?>
        </div>
    </div>
    
    <!-- Usuarios seleccionados -->
    <div class="mb-3">
        <label class="form-label">Usuarios Seleccionados (<span id="selected-count">0</span>):</label>
        <div class="selected-users-container" id="selected-users-container">
            <p class="text-muted mb-0">No hay usuarios seleccionados</p>
        </div>
    </div>
    
    <!-- Selección de taller -->
    <div class="mb-3">
        <label for="select_taller_multiple" class="form-label">Seleccionar Taller</label>
        <select class="form-select" id="select_taller_multiple" name="select_taller_multiple" required>
            <option value="">Seleccionar Taller</option>
            <?php
            $queryTalleres = "SELECT TallerID, Nombre FROM taller";
            $resultTalleres = mysqli_query($dbConnection, $queryTalleres);
            while ($rowTaller = mysqli_fetch_assoc($resultTalleres)) {
                echo '<option value="' . $rowTaller['TallerID'] . '">' . htmlspecialchars($rowTaller['Nombre']) . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="horario_id_multiple" class="form-label">Seleccionar Horario</label>
        <select class="form-select" id="horario_id_multiple" name="horario_id_multiple" required>
            <option value="">Primero selecciona un taller</option>
        </select>
    </div>
    
    <button type="submit" class="btn btn-primary">Inscribir Usuarios Seleccionados</button>
</form>

<h2 class="mt-5">Inscribir Usuario Individual en Taller</h2>
<form id="inscripcion-form">
<div class="mb-3">
        <label for="search-user" class="form-label">Buscar Usuario:</label>
        <input type="text" id="search-user" class="form-control" placeholder="Escribe para buscar un usuario...">
    </div>
    <div class="mb-3">
        <label for="select_usuario" class="form-label">Seleccionar Usuario</label>
        <select class="form-select" id="select_usuario" name="select_usuario" required>
            <option value="">Seleccionar Usuario</option>
            <?php
            $queryUsuarios = "SELECT UsuarioID, Nombre FROM usuario WHERE TipoUsuarioID = 2 AND Activo = 1";
            $resultUsuarios = mysqli_query($dbConnection, $queryUsuarios);

            while ($rowUsuario = mysqli_fetch_assoc($resultUsuarios)) {
                echo '<option value="' . $rowUsuario['UsuarioID'] . '">' . htmlspecialchars($rowUsuario['Nombre']) . '</option>';
            }
            ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="select_taller" class="form-label">Seleccionar Taller</label>
        <select class="form-select" id="select_taller" name="select_taller" required>
            <option value="">Seleccionar Taller</option>
            <?php
            $queryTalleres = "SELECT TallerID, Nombre FROM taller";
            $resultTalleres = mysqli_query($dbConnection, $queryTalleres);

            while ($rowTaller = mysqli_fetch_assoc($resultTalleres)) {
                echo '<option value="' . $rowTaller['TallerID'] . '">' . htmlspecialchars($rowTaller['Nombre']) . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="horario_id" class="form-label">Seleccionar Horario</label>
        <select class="form-select" id="horario_id" name="horario_id" required>
            <!-- Opciones de horarios se cargarán dinámicamente con JavaScript -->
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Inscribir</button>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let selectedUsers = new Set();
    
    // Funciones para el formulario de inscripción múltiple
    function updateSelectedUsers() {
        selectedUsers.clear();
        $('.user-checkbox-input:checked').each(function() {
            selectedUsers.add(parseInt($(this).val()));
        });
        updateSelectedUsersDisplay();
    }
    
    function updateSelectedUsersDisplay() {
        const container = $('#selected-users-container');
        const countSpan = $('#selected-count');
        
        countSpan.text(selectedUsers.size);
        
        if (selectedUsers.size === 0) {
            container.html('<p class="text-muted mb-0">No hay usuarios seleccionados</p>');
            return;
        }
        
        container.empty();
        selectedUsers.forEach(userId => {
            const userName = $(`#user-${userId}`).data('name');
            const tag = $(`
                <span class="selected-user-tag">
                    ${userName}
                    <button type="button" class="remove-user" data-user-id="${userId}">×</button>
                </span>
            `);
            container.append(tag);
        });
    }
    
    // Búsqueda de usuarios para inscripción múltiple
    $('#search-user-multiple').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.user-checkbox').each(function() {
            const userName = $(this).find('label').text().toLowerCase();
            const label = $(this).find('label');
            
            if (userName.includes(searchTerm)) {
                $(this).show();
                
                // Resaltar texto coincidente
                if (searchTerm.length > 0) {
                    const originalText = label.attr('data-original') || label.text();
                    label.attr('data-original', originalText);
                    
                    const highlightedText = originalText.replace(
                        new RegExp(searchTerm, 'gi'), 
                        match => `<span class="search-highlight">${match}</span>`
                    );
                    label.html(highlightedText);
                } else {
                    const originalText = label.attr('data-original');
                    if (originalText) {
                        label.text(originalText);
                    }
                }
            } else {
                $(this).hide();
            }
        });
    });
    
    // Seleccionar todos los usuarios
    $('#select-all-users').click(function() {
        $('.user-checkbox-input').prop('checked', true);
        updateSelectedUsers();
    });
    
    // Limpiar selección
    $('#clear-all-users').click(function() {
        $('.user-checkbox-input').prop('checked', false);
        selectedUsers.clear();
        updateSelectedUsersDisplay();
    });
    
    // Seleccionar usuarios visibles
    $('#select-visible-users').click(function() {
        $('.user-checkbox:visible .user-checkbox-input').prop('checked', true);
        updateSelectedUsers();
    });
    
    // Manejar cambios en checkboxes
    $(document).on('change', '.user-checkbox-input', function() {
        updateSelectedUsers();
    });
    
    // Remover usuario de selección
    $(document).on('click', '.remove-user', function() {
        const userId = parseInt($(this).data('user-id'));
        selectedUsers.delete(userId);
        $(`#user-${userId}`).prop('checked', false);
        updateSelectedUsersDisplay();
    });
    
    // Cargar horarios para inscripción múltiple
    $('#select_taller_multiple').change(function() {
        const tallerID = $(this).val();
        const horarioSelect = $('#horario_id_multiple');
        
        if (!tallerID) {
            horarioSelect.html('<option value="">Primero selecciona un taller</option>');
            return;
        }
        
        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: {
                action: 'loadHorarios',
                taller_id: tallerID
            },
            success: function(response) {
                console.log('Cargando horarios para inscripción múltiple');
                horarioSelect.html(response);
            },
            error: function(error) {
                console.log('Error al cargar horarios');
                horarioSelect.html('<option value="">Error al cargar horarios</option>');
            }
        });
    });
    
    // Manejar envío del formulario múltiple
    $('#inscripcion-multiple-form').submit(function(event) {
        event.preventDefault();
        
        let selectedUserIds = [];
        $('.user-checkbox-input:checked').each(function() {
            selectedUserIds.push($(this).val());
        });
        
        if (selectedUserIds.length === 0) {
            alert('Por favor, selecciona al menos un usuario.');
            return;
        }
        
        const tallerID = $('#select_taller_multiple').val();
        const horarioID = $('#horario_id_multiple').val();
        
        if (!tallerID || !horarioID) {
            alert('Por favor, selecciona un taller y un horario.');
            return;
        }
        
        if (confirm(`¿Estás seguro de inscribir ${selectedUserIds.length} usuarios en este taller?`)) {
            $.ajax({
                url: 'admin.php',
                type: 'POST',
                data: {
                    action: 'addMultipleInscripcion',
                    usuarios: selectedUserIds,
                    taller_id: tallerID,
                    horario_id: horarioID
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(`${response.inscribed} usuarios inscritos correctamente. ${response.skipped || 0} ya estaban inscritos.`);
                    } else {
                        alert('Error al inscribir usuarios');
                    }
                    
                    // Limpiar formulario
                    selectedUsers.clear();
                    updateSelectedUsersDisplay();
                    $('#select_taller_multiple').val('');
                    $('#horario_id_multiple').html('<option value="">Primero selecciona un taller</option>');
                    $('.user-checkbox-input').prop('checked', false);
                    
                    // Recargar la página para mostrar cambios
                    setTimeout(() => location.reload(), 1000);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert("Error al inscribir usuarios");
                    location.reload();
                }
            });
        }
    });

    // Búsqueda de usuarios para inscripción individual
    $("#search-user").on("input", function() {
        var searchTerm = $(this).val().toLowerCase();
        $("#select_usuario option").each(function() {
            var userName = $(this).text().toLowerCase();
            if (userName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Cargar horarios para inscripción individual
    $('#select_taller').change(function() {
        var tallerID = $(this).val();

        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: {
                action: 'loadHorarios',
                taller_id: tallerID
            },
            success: function(response) {
                console.log('Cargando horarios');
                $("#horario_id").html(response);
            },
            error: function(error) {
                console.log('Error al cargar horarios');
            }
        });
    });

    // Manejar envío del formulario individual
    $('#inscripcion-form').submit(function(event) {
        event.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=addInscripcion';

        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log("Inscrito correctamente");
                alert("Usuario inscrito");
                $("#inscripciones").load("admintabs/inscripciones.php", {
                    db_host: "<?php echo $db_host; ?>",
                    db_user: "<?php echo $db_user; ?>",
                    db_password: "<?php echo $db_password; ?>",
                    db_name: "<?php echo $db_name; ?>"
                });
                location.reload();
            },
            error: function(error) {
                console.log('Error al inscribir usuario en taller');
                alert("Usuario inscrito");
                $("#inscripciones").load("admintabs/inscripciones.php", {
                    db_host: "<?php echo $db_host; ?>",
                    db_user: "<?php echo $db_user; ?>",
                    db_password: "<?php echo $db_password; ?>",
                    db_name: "<?php echo $db_name; ?>"
                });
            }
        });
    });

    $("#agregar-usuario").click(function() {
        $("#add-user-form").slideToggle();
    });
    
    // Agregar Usuario mediante AJAX
    $("#add-user-form").submit(function(e) {
        e.preventDefault();

        var formData = {
            action: 'addUser',
            nombre: $("#nombre").val(),
            DNI: $("#DNI").val(),
            telefono: $("#telefono").val(),
            codigo_postal: $("#codigo_postal").val(),
            tipo_usuario: $("#tipo-usuario").val()
        };

        $.ajax({
            type: "POST",
            url: "admin.php",
            data: formData,
            dataType: "json",
            success: function(data) {
                if (data.success) {
                    console.log("Operación exitosa");
                    $("#usuarios").load("admintabs/usuarios.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    location.reload();
                } else if (data.error) {
                    console.log("Error en la operación:", data.error);
                    $("#usuarios").load("admintabs/usuarios.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
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

    // Activar/Desactivar usuario mediante AJAX
    $(".toggle-user").click(function() {
        var userId = $(this).data("userid");
        var userStatus = $(this).data("userstatus");

        var requestData = {
            action: 'toggleUser',
            userId: userId,
            userStatus: userStatus
        };

        $.ajax({
            type: "POST",
            url: "admin.php",
            data: requestData,
            dataType: "json",
            success: function(data) {
                if (data.success) {
                    console.log("Operación exitosa");
                    $("#usuarios").load("admintabs/usuarios.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    location.reload();
                } else if (data.error) {
                    $("#usuarios").load("admintabs/usuarios.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
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

    // Manejar clic en los enlaces del menú
    $(".nav-link").click(function(e) {
        e.preventDefault();
        var target = $(this).attr("href");
        $("section").removeClass("active");
        $(target).addClass("active");
    });

    // Usar MutationObserver para observar cambios en la clase ".listado_usuarios_paginate"
    var observer = new MutationObserver(function(mutations) {
        // Lanzar un evento personalizado cuando cambie algún elemento
        $('#listado_usuarios').trigger('elementChanged');
    });

    var target = document.querySelector('#listado_usuarios');
    var config = {
        childList: true,
        subtree: true
    };
    observer.observe(target, config);

    // Escuchar el evento personalizado 'elementChanged' con jQuery
    $('#listado_usuarios').on('elementChanged', function() {
        console.log('Elementos dentro de #listado_usuarios han cambiado');
        // Aquí puedes agregar el código que deseas ejecutar cuando cambie algún elemento
        // Activar/Desactivar usuario mediante AJAX
        $(".toggle-user").click(function() {
            var userId = $(this).data("userid");
            var userStatus = $(this).data("userstatus");

            var requestData = {
                action: 'toggleUser',
                userId: userId,
                userStatus: userStatus
            };

            $.ajax({
                type: "POST",
                url: "admin.php",
                data: requestData,
                dataType: "json",
                success: function(data) {
                    if (data.success) {
                        console.log("Operación exitosa");
                        $("#usuarios").load("admintabs/usuarios.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
                        location.reload();
                    } else if (data.error) {
                        $("#usuarios").load("admintabs/usuarios.php", {
                            db_host: "<?php echo $db_host; ?>",
                            db_user: "<?php echo $db_user; ?>",
                            db_password: "<?php echo $db_password; ?>",
                            db_name: "<?php echo $db_name; ?>"
                        });
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
        $(".edit-user").click(function() {
            var userId = $(this).data("userid");
            var foto = $(this).data("userfoto");
            if (foto == "") {
                foto = "user.jpg";
            }
            var userRow = $(this).closest("tr");
            var nombre = userRow.find("td:eq(1)").text();
            var DNI = userRow.find("td:eq(2)").text();
            var telefono = userRow.find("td:eq(3)").text();
            var codigoPostal = userRow.find("td:eq(4)").text();
            var saldo = userRow.find("td:eq(5)").text(); // Obtener el saldo
            var tipo = userRow.find("td:eq(6)").text(); // Ajustar índice de columna
            if (tipo == "Profesor") {
                $('#edit-tipo-usuario').val('3');
            } else {
                $('#edit-tipo-usuario').val('2');
            }

            $("#edit-user-id").val(userId);
            $("#edit-nombre").val(nombre);
            $("#edit-DNI").val(DNI);
            $("#edit-telefono").val(telefono);
            $("#edit-codigo_postal").val(codigoPostal);
            $("#edit-saldo").val(saldo); // Establecer el saldo en el formulario
            $("#foto_usuario").attr("src", "https://zigzagmerceriacreativa.es/talleres/fotos_usuarios/" + foto);

            $("#edit-user-form").modal('show');
        });

    });

    $(".edit-user").click(function() {
        var userId = $(this).data("userid");
        var foto = $(this).data("userfoto");
        if (foto == "") {
            foto = "user.jpg";
        }
        var userRow = $(this).closest("tr");
        var nombre = userRow.find("td:eq(1)").text();
        var DNI = userRow.find("td:eq(2)").text();
        var telefono = userRow.find("td:eq(3)").text();
        var codigoPostal = userRow.find("td:eq(4)").text();
        var saldo = userRow.find("td:eq(5)").text(); // Obtener el saldo
        var tipo = userRow.find("td:eq(6)").text(); // Ajustar índice de columna
        if (tipo == "Profesor") {
            $('#edit-tipo-usuario').val('3');
        } else {
            $('#edit-tipo-usuario').val('2');
        }

        $("#edit-user-id").val(userId);
        $("#edit-nombre").val(nombre);
        $("#edit-DNI").val(DNI);
        $("#edit-telefono").val(telefono);
        $("#edit-codigo_postal").val(codigoPostal);
        $("#edit-saldo").val(saldo); // Establecer el saldo en el formulario
        $("#foto_usuario").attr("src", "https://zigzagmerceriacreativa.es/talleres/fotos_usuarios/" + foto);

        $("#edit-user-form").modal('show');
    });
    
    $("#restore-password-btn").click(function() {
        var userId = $("#edit-user-id").val(); // Obtener el ID del usuario

        if (confirm("¿Estás seguro de que deseas restaurar la contraseña de este usuario a 'zigzag'?")) {
            // Realizar la solicitud Ajax para restaurar la contraseña
            $.ajax({
                type: "POST",
                url: "admin.php", // Archivo PHP para manejar la solicitud
                data: {
                    action: "restorePassword",
                    userId: userId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        alert("La contraseña ha sido restaurada a 'zigzag'.");
                    } else {
                        alert("Error al restaurar la contraseña.");
                        console.error("Error en la respuesta del servidor:", response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error en la solicitud Ajax:", error);
                    alert("Error en la solicitud Ajax");
                }
            });
        }
    });

    $("#edit-user-form").submit(function(event) {
        event.preventDefault();

        var userId = $("#edit-user-id").val();
        var nombre = $("#edit-nombre").val();
        var DNI = $("#edit-DNI").val();
        var telefono = $("#edit-telefono").val();
        var codigoPostal = $("#edit-codigo_postal").val();
        var saldo = $("#edit-saldo").val(); // Obtener el saldo actualizado
        var tipoUsuario = $("#edit-tipo-usuario").val();

        // Realizar la solicitud Ajax para actualizar los datos del usuario
        $.ajax({
            type: "POST",
            url: "admin.php", // Archivo PHP para editar usuario
            data: {
                action: "editarUsuario",
                userId: userId,
                nombre: nombre,
                DNI: DNI,
                telefono: telefono,
                codigoPostal: codigoPostal,
                saldo: saldo, // Incluir saldo en los datos enviados
                tipoUsuario: tipoUsuario
            },
            success: function(response) {
                if (response.success) {
                    alert("Datos de usuario actualizados");
                    $("#usuarios").load("admintabs/usuarios.php", {
                        db_host: "<?php echo $db_host; ?>",
                        db_user: "<?php echo $db_user; ?>",
                        db_password: "<?php echo $db_password; ?>",
                        db_name: "<?php echo $db_name; ?>"
                    });
                    $("#edit-user-form .btn-close").click();
                } else {
                    console.error("Error en la respuesta del servidor:", response.error);
                    alert("Datos del usuario editados correctamente");
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                console.error("Error en la solicitud Ajax:", error);
                alert("Error en la solicitud Ajax");
            }
        });
    });
});

//$('#listado_usuarios').DataTable();
var listado_usuarios = new DataTable('#listado_usuarios', {
    language: {
        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
    },
});
</script>