<?php
// test_conexion.php - TEST COMPLETO DE CONEXIÓN A SUPABASE

require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Conexión - Supabase</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 2.2em;
        }
        
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .test-section {
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .test-section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.4em;
        }
        
        .success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            margin: 15px 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .info-value {
            font-family: 'Courier New', monospace;
            color: #333;
            word-break: break-all;
        }
        
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 5px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .status-success {
            background: #28a745;
            color: white;
        }
        
        .status-error {
            background: #dc3545;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
        }
        
        .metric {
            display: inline-block;
            margin: 10px 20px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        
        .metric-label {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔌 Test de Conexión a Supabase</h1>
            <p class="subtitle">Diagnóstico completo de la conexión a la base de datos</p>
            
            <?php
            // TEST 1: Verificar configuración
            echo '<div class="test-section">';
            echo '<h2>📋 Configuración Actual</h2>';
            echo '<div class="info-grid">';
            echo '<div class="info-label">Host:</div>';
            echo '<div class="info-value">' . htmlspecialchars(DB_HOST) . '</div>';
            echo '<div class="info-label">Puerto:</div>';
            echo '<div class="info-value">' . htmlspecialchars(DB_PORT) . '</div>';
            echo '<div class="info-label">Base de datos:</div>';
            echo '<div class="info-value">' . htmlspecialchars(DB_NAME) . '</div>';
            echo '<div class="info-label">Usuario:</div>';
            echo '<div class="info-value">' . htmlspecialchars(DB_USER) . '</div>';
            echo '<div class="info-label">Password:</div>';
            echo '<div class="info-value">' . str_repeat('•', min(strlen(DB_PASS), 20)) . ' (' . strlen(DB_PASS) . ' caracteres)</div>';
            echo '</div>';
            echo '</div>';
            
            // TEST 2: DNS Lookup
            echo '<div class="test-section">';
            echo '<h2>🌐 Resolución DNS</h2>';
            $dns_result = gethostbyname(DB_HOST);
            if ($dns_result === DB_HOST) {
                echo '<div class="error" style="padding: 15px; margin: 10px 0; border-radius: 6px;">';
                echo '<p><span class="status-badge status-error">❌ ERROR</span></p>';
                echo '<p style="margin-top: 10px;"><strong>No se puede resolver el hostname</strong></p>';
                echo '<p>El DNS no puede encontrar la IP del servidor: ' . htmlspecialchars(DB_HOST) . '</p>';
                echo '</div>';
            } else {
                echo '<div class="success" style="padding: 15px; margin: 10px 0; border-radius: 6px;">';
                echo '<p><span class="status-badge status-success">✅ OK</span></p>';
                echo '<p style="margin-top: 10px;"><strong>Hostname resuelto correctamente</strong></p>';
                echo '<p>IP: ' . htmlspecialchars($dns_result) . '</p>';
                echo '</div>';
            }
            echo '</div>';
            
            // TEST 3: Prueba de conexión PDO
            echo '<div class="test-section">';
            echo '<h2>🔐 Conexión a Base de Datos</h2>';
            
            $conexion_exitosa = false;
            
            try {
                $start_time = microtime(true);
                $pdo = getDB();
                $end_time = microtime(true);
                $connection_time = round(($end_time - $start_time) * 1000, 2);
                
                echo '<div class="success" style="padding: 15px; margin: 10px 0; border-radius: 6px;">';
                echo '<p><span class="status-badge status-success">✅ CONEXIÓN EXITOSA</span></p>';
                echo '<div class="metric">';
                echo '<div class="metric-value">' . $connection_time . 'ms</div>';
                echo '<div class="metric-label">Tiempo de conexión</div>';
                echo '</div>';
                echo '</div>';
                
                $conexion_exitosa = true;
                
                // TEST 4: Información del servidor
                echo '<h3 style="margin-top: 20px; color: #2c3e50;">📊 Información del Servidor</h3>';
                $stmt = $pdo->query("SELECT version(), current_database(), current_user, pg_database_size(current_database()) as size");
                $info = $stmt->fetch();
                
                echo '<div class="info-grid">';
                echo '<div class="info-label">Versión PostgreSQL:</div>';
                echo '<div class="info-value">' . htmlspecialchars($info['version']) . '</div>';
                echo '<div class="info-label">Base de datos actual:</div>';
                echo '<div class="info-value">' . htmlspecialchars($info['current_database']) . '</div>';
                echo '<div class="info-label">Usuario conectado:</div>';
                echo '<div class="info-value">' . htmlspecialchars($info['current_user']) . '</div>';
                echo '<div class="info-label">Tamaño BD:</div>';
                echo '<div class="info-value">' . number_format($info['size'] / 1024 / 1024, 2) . ' MB</div>';
                echo '</div>';
                
                // TEST 5: Verificar tabla usuarios
                echo '<h3 style="margin-top: 20px; color: #2c3e50;">🗃️ Tabla "usuarios"</h3>';
                $stmt = $pdo->query("SELECT EXISTS (
                    SELECT FROM information_schema.tables 
                    WHERE table_schema = 'public' 
                    AND table_name = 'usuarios'
                )");
                $tabla_existe = $stmt->fetchColumn();
                
                if ($tabla_existe) {
                    echo '<div class="success" style="padding: 15px; margin: 10px 0; border-radius: 6px;">';
                    echo '<p><strong>✅ La tabla "usuarios" existe</strong></p>';
                    
                    // Contar registros
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
                    $total = $stmt->fetchColumn();
                    
                    $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM usuarios WHERE tipo = 'beneficiado' AND aprobado = false");
                    $pendientes = $stmt->fetchColumn();
                    
                    echo '<div style="margin-top: 15px;">';
                    echo '<div class="metric">';
                    echo '<div class="metric-value">' . $total . '</div>';
                    echo '<div class="metric-label">Total usuarios</div>';
                    echo '</div>';
                    echo '<div class="metric">';
                    echo '<div class="metric-value" style="color: #ffc107;">' . $pendientes . '</div>';
                    echo '<div class="metric-label">Pendientes aprobación</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Mostrar estructura
                    echo '<h4 style="margin-top: 20px; color: #2c3e50;">Estructura de la tabla:</h4>';
                    $stmt = $pdo->query("
                        SELECT column_name, data_type, is_nullable
                        FROM information_schema.columns 
                        WHERE table_name = 'usuarios'
                        ORDER BY ordinal_position
                    ");
                    $columnas = $stmt->fetchAll();
                    
                    echo '<table>';
                    echo '<tr><th>Columna</th><th>Tipo</th><th>Permite NULL</th></tr>';
                    foreach ($columnas as $col) {
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($col['column_name']) . '</strong></td>';
                        echo '<td>' . htmlspecialchars($col['data_type']) . '</td>';
                        echo '<td>' . ($col['is_nullable'] == 'YES' ? '✅' : '❌') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    
                    echo '</div>';
                } else {
                    echo '<div class="warning" style="padding: 15px; margin: 10px 0; border-radius: 6px;">';
                    echo '<p><strong>⚠️ La tabla "usuarios" NO existe</strong></p>';
                    echo '<p>Necesitas crear la tabla antes de usar el sistema.</p>';
                    echo '</div>';
                }
                
                // TEST 6: Probar consulta de validar_ine.php
                echo '<h3 style="margin-top: 20px; color: #2c3e50;">🧪 Prueba de Consulta validar_ine.php</h3>';
                $stmt = $pdo->prepare("
                    SELECT id, nombre_completo, telefono, foto_url, fecha_registro 
                    FROM usuarios 
                    WHERE tipo = :tipo AND aprobado = false
                    ORDER BY fecha_registro DESC
                    LIMIT 5
                ");
                $stmt->execute([':tipo' => 'beneficiado']);
                $beneficiados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<div class="success" style="padding: 15px; margin: 10px 0; border-radius: 6px;">';
                echo '<p><strong>✅ Consulta ejecutada correctamente</strong></p>';
                echo '<p>Registros encontrados: <strong>' . count($beneficiados) . '</strong></p>';
                
                if (count($beneficiados) > 0) {
                    echo '<table style="margin-top: 15px;">';
                    echo '<tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Fecha</th></tr>';
                    foreach ($beneficiados as $b) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($b['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($b['nombre_completo'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($b['telefono'] ?? 'N/A') . '</td>';
                        echo '<td>' . date('d/m/Y', strtotime($b['fecha_registro'] ?? 'now')) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                echo '</div>';
                
            } catch (PDOException $e) {
                echo '<div class="error" style="padding: 15px; margin: 10px 0; border-radius: 6px;">';
                echo '<p><span class="status-badge status-error">❌ ERROR DE CONEXIÓN</span></p>';
                echo '<p style="margin-top: 10px;"><strong>Mensaje de error:</strong></p>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '</div>';
            }
            echo '</div>';
            ?>
        </div>
        
        <div class="card">
            <?php if ($conexion_exitosa): ?>
                <h2 style="color: #28a745; text-align: center;">🎉 ¡Todo Funciona Correctamente!</h2>
                <p style="text-align: center; margin: 20px 0; color: #6c757d;">
                    Tu conexión a Supabase está configurada correctamente y lista para usar.
                </p>
            <?php else: ?>
                <h2 style="color: #dc3545; text-align: center;">❌ Hay Problemas de Conexión</h2>
                <p style="text-align: center; margin: 20px 0; color: #6c757d;">
                    Revisa la configuración en config.php y verifica tus credenciales en Supabase.
                </p>
            <?php endif; ?>
            
            <div class="actions">
                <a href="validar_ine.php" class="btn btn-success">→ Ir a Validar INE</a>
                <a href="verificar_tabla.php" class="btn">🔍 Verificar Tabla</a>
                <a href="insertar_prueba.php" class="btn">🧪 Crear Prueba</a>
                <a href="inicio.php" class="btn">🏠 Inicio</a>
            </div>
        </div>
    </div>
</body>
</html>