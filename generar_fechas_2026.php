<?php
require_once 'db_connection.php';

echo "<h2>Generando fechas para enero 2026...</h2>";

// Paso 1: Obtener una fecha de referencia para cada horario (de diciembre 2025)
echo "<h3>Paso 1: Analizando horarios y sus inscripciones en diciembre 2025</h3>";

$horariosPorDia = [];

// Obtener todos los horarios con su día de la semana
$queryHorarios = "SELECT h.HorarioID, h.DiaSemana, h.HoraInicio, t.Nombre as NombreTaller
    FROM horario h
    JOIN taller t ON h.TallerID = t.TallerID
    ORDER BY h.DiaSemana, h.HoraInicio";
$resultHorarios = mysqli_query($dbConnection, $queryHorarios);

while ($horario = mysqli_fetch_assoc($resultHorarios)) {
    $horarioID = $horario['HorarioID'];
    $diaSemana = $horario['DiaSemana'];
    
    // Buscar una fecha de referencia de este horario en diciembre 2025
    $queryFechaRef = "SELECT IDFecha, Fecha 
        FROM fechas 
        WHERE HorarioID = $horarioID 
        AND Fecha BETWEEN '2025-12-15' AND '2025-12-31'
        AND Fecha NOT IN (SELECT Fecha FROM festivos)
        ORDER BY Fecha DESC
        LIMIT 1";
    $resultFechaRef = mysqli_query($dbConnection, $queryFechaRef);
    
    if ($fechaRef = mysqli_fetch_assoc($resultFechaRef)) {
        $fechaRefID = $fechaRef['IDFecha'];
        
        // Obtener las inscripciones de esta fecha específica
        $queryInscripciones = "SELECT DISTINCT
            i.UsuarioID,
            i.TallerID,
            u.Nombre as NombreUsuario
        FROM inscripcion i
        JOIN usuario u ON i.UsuarioID = u.UsuarioID
        WHERE i.FechaID = $fechaRefID
        AND i.HorarioID = $horarioID";
        
        $resultInscripciones = mysqli_query($dbConnection, $queryInscripciones);
        $inscripciones = [];
        
        while ($insc = mysqli_fetch_assoc($resultInscripciones)) {
            $inscripciones[] = $insc;
        }
        
        $horariosPorDia[$horarioID] = [
            'diaSemana' => $diaSemana,
            'horaInicio' => $horario['HoraInicio'],
            'nombreTaller' => $horario['NombreTaller'],
            'inscripciones' => $inscripciones,
            'fechaReferencia' => $fechaRef['Fecha']
        ];
        
        echo "✓ Horario $horarioID ($diaSemana {$horario['HoraInicio']} - {$horario['NombreTaller']}): " 
            . count($inscripciones) . " inscripciones (ref: {$fechaRef['Fecha']})<br>";
    }
}

// Mapa de días de la semana en español a número
$diasSemanaMap = [
    'Lunes' => 1,
    'Martes' => 2,
    'Miercoles' => 3,
    'Miércoles' => 3,
    'Jueves' => 4,
    'Viernes' => 5,
    'Sabado' => 6,
    'Sábado' => 6,
    'Domingo' => 7
];

// Paso 2: Generar el día 30 de diciembre si falta
echo "<hr><h3>Paso 2: Verificando día 30 diciembre 2025</h3>";
$fecha30 = '2025-12-30';
$diaSemana30 = date('N', strtotime($fecha30)); // 2 = Martes

$queryCheck30 = "SELECT COUNT(*) as total FROM fechas WHERE Fecha = '$fecha30'";
$resultCheck30 = mysqli_query($dbConnection, $queryCheck30);
$rowCheck30 = mysqli_fetch_assoc($resultCheck30);

if ($rowCheck30['total'] == 0) {
    echo "⚠️ Día 30/12/2025 no existe. Generando...<br>";
    
    foreach ($horariosPorDia as $horarioID => $info) {
        $diaNumero = $diasSemanaMap[$info['diaSemana']];
        
        // Solo crear fecha si el día de la semana coincide (martes = 2)
        if ($diaNumero == $diaSemana30) {
            $insertFecha = "INSERT INTO fechas (HorarioID, Fecha) VALUES ($horarioID, '$fecha30')";
            
            if (mysqli_query($dbConnection, $insertFecha)) {
                $fechaID = mysqli_insert_id($dbConnection);
                
                // Copiar inscripciones
                foreach ($info['inscripciones'] as $insc) {
                    $insertInsc = "INSERT INTO inscripcion (UsuarioID, TallerID, HorarioID, FechaID) 
                        VALUES ({$insc['UsuarioID']}, {$insc['TallerID']}, $horarioID, $fechaID)";
                    mysqli_query($dbConnection, $insertInsc);
                }
                
                echo "  ✓ {$info['diaSemana']} {$info['horaInicio']} - {$info['nombreTaller']}: " 
                    . count($info['inscripciones']) . " inscritos<br>";
            }
        }
    }
} else {
    echo "✓ Día 30/12/2025 ya existe<br>";
}

// Paso 3: Generar fechas de enero 2026 (del 1 al 31)
echo "<hr><h3>Paso 3: Generando enero 2026</h3>";

$fechasCreadas = 0;
$inscripcionesCreadas = 0;

for ($dia = 1; $dia <= 31; $dia++) {
    $fecha = "2026-01-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
    $diaSemanaNumero = date('N', strtotime($fecha)); // 1=Lun, 2=Mar, etc.
    
    echo "<br><strong>$fecha (" . date('l', strtotime($fecha)) . ")</strong><br>";
    
    // Para cada horario, verificar si el día de la semana coincide
    foreach ($horariosPorDia as $horarioID => $info) {
        $diaNumero = $diasSemanaMap[$info['diaSemana']];
        
        // Solo crear si el día de la semana coincide
        if ($diaNumero == $diaSemanaNumero) {
            // Verificar si ya existe
            $checkQuery = "SELECT IDFecha FROM fechas WHERE Fecha = '$fecha' AND HorarioID = $horarioID";
            $resultCheck = mysqli_query($dbConnection, $checkQuery);
            
            if (mysqli_num_rows($resultCheck) == 0) {
                // Crear fecha
                $insertFecha = "INSERT INTO fechas (HorarioID, Fecha) VALUES ($horarioID, '$fecha')";
                
                if (mysqli_query($dbConnection, $insertFecha)) {
                    $fechaID = mysqli_insert_id($dbConnection);
                    $fechasCreadas++;
                    
                    // Copiar inscripciones de este horario específico
                    foreach ($info['inscripciones'] as $insc) {
                        $checkInsc = "SELECT * FROM inscripcion 
                            WHERE UsuarioID = {$insc['UsuarioID']} 
                            AND TallerID = {$insc['TallerID']}
                            AND HorarioID = $horarioID 
                            AND FechaID = $fechaID";
                        
                        if (mysqli_num_rows(mysqli_query($dbConnection, $checkInsc)) == 0) {
                            $insertInsc = "INSERT INTO inscripcion (UsuarioID, TallerID, HorarioID, FechaID) 
                                VALUES ({$insc['UsuarioID']}, {$insc['TallerID']}, $horarioID, $fechaID)";
                            
                            if (mysqli_query($dbConnection, $insertInsc)) {
                                $inscripcionesCreadas++;
                            }
                        }
                    }
                    
                    echo "  ✓ {$info['horaInicio']} - {$info['nombreTaller']}: " 
                        . count($info['inscripciones']) . " inscritos<br>";
                }
            }
        }
    }
}

echo "<hr>";
echo "<h3>✅ Proceso completado</h3>";
echo "Fechas creadas: $fechasCreadas<br>";
echo "Inscripciones creadas: $inscripcionesCreadas<br>";
echo "<br><a href='admin.php'>Volver al panel de administración</a>";
?>