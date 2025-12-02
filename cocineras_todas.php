<?php
// cocineras.php - GESTIÓN DE COCINERAS
require_once 'config.php';

// Verificar si el usuario ha iniciado sesión COMO ADMIN
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Obtener conexión a la base de datos
$pdo = getDB();

$mensaje = '';
$tipo_mensaje = '';

// Procesar eliminación de cocinera
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    
    try {
        // Verificar que existe antes de eliminar
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'cocinera'");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'cocinera'");
            if ($stmt->execute([$id])) {
                $mensaje = 'Cocinera eliminada exitosamente';
                $tipo_mensaje = 'exito';
            }
        } else {
            $mensaje = 'Cocinera no encontrada';
            $tipo_mensaje = 'error';
        }
    } catch (PDOException $e) {
        $mensaje = 'Error al eliminar: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener lista de cocineras CON dirección
try {
    $stmt = $pdo->query("
        SELECT 
            id, usuario, nombre_completo, correo, telefono, 
            direccion_localidad, fecha_registro, aprobado, tipo
        FROM usuarios 
        WHERE tipo = 'cocinera' 
        ORDER BY fecha_registro DESC
    ");
    $cocineras = $stmt->fetchAll();
} catch (PDOException $e) {
    $mensaje = 'Error al cargar cocineras: ' . $e->getMessage();
    $tipo_mensaje = 'error';
    $cocineras = [];
}

$nombre_usuario = isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'Admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cocineras - Sistema de Apoyo Alimentario</title>
    <link rel="stylesheet" href="css/navbar.css">
    <style>
                * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            color: #111827;
        }

        /* Main Content */
        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 48px 24px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header {
            margin-bottom: 32px;
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header h1 {
            font-size: 36px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }

        .page-header p {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.6;
        }

        /* Mensajes */
        .mensaje {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        .mensaje.exito {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .mensaje.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            margin-top: 24px;
        }

        /* List Section */
        .list-section {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 32px;
            animation: cardFadeIn 0.5s ease-out forwards;
            opacity: 0;
            animation-delay: 0.1s;
        }

        @keyframes cardFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .list-section h2 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .stats-info {
            font-size: 14px;
            color: #6b7280;
            background: #f8fafc;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        th {
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-register {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-register:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Status Styles */
        .status-aprobado {
            color: #059669;
            font-weight: 600;
            background: #d1fae5;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
        }

        .status-pendiente {
            color: #d97706;
            font-weight: 600;
            background: #fef3c7;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
        }

        /* Cocinera Icon */
        .cocinera-icon {
            font-size: 16px;
            margin-right: 8px;
            display: inline-block;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            line-height: 1.5;
            max-width: 400px;
            margin: 0 auto 24px auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 28px;
            }

            main {
                padding: 32px 20px;
            }

            .list-section {
                padding: 24px;
            }

            .stats-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .empty-state {
                padding: 32px 16px;
            }

            .empty-state-icon {
                font-size: 48px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }

        }
        /* Estilos adicionales para el botón y badges */
        .btn-nueva-cocinera {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-nueva-cocinera:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, #0da271 0%, #047857 100%);
        }

        .btn-nueva-cocinera:active {
            transform: translateY(0);
        }

        .localidad-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: #f0f9ff;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #0369a1;
            border: 1px solid #bae6fd;
            white-space: nowrap;
        }

        .localidad-badge::before {
            content: "📍";
            margin-right: 4px;
            font-size: 11px;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .stats-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .actions-column {
            display: flex;
            gap: 8px;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        /* Estilos para la tabla responsiva */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th {
            padding: 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .cocinera-icon {
            margin-right: 8px;
            font-size: 16px;
        }

        .status-aprobado {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: #d1fae5;
            color: #065f46;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            gap: 4px;
        }

        .status-pendiente {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            gap: 4px;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo-section">
                <div class="logo">FL</div>
                <div class="title">Administrador Foodlink</div>
            </div>
            <div class="nav-links">
                <a href="inicio.php">Inicio</a>
                <a href="validar_ine.php">Validar INE</a>
                <a href="cocineras.php">Usuarios Cocineras</a>
                <a href="beneficiarios.php">Beneficiarios</a>
                <a href="donaciones.php">Módulo de Donaciones</a>
                <a href="notificaciones.php">Notificaciones</a>
                <div class="user-info">
                    <span>👤 <?php echo htmlspecialchars($nombre_usuario); ?></span>
                    <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <div class="header-actions">
                <div>
                    <h1>Gestión de Cocineras</h1>
                    <p>Visualiza y administra a las cocineras registradas en el programa alimentario</p>
                </div>
                <a href="cocineras.php" class="btn-nueva-cocinera">
                    <span>👩‍🍳</span>
                    ¿Cocineras Nueva?
                </a>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="list-section">
                <div class="stats-header">
                    <h2>Cocineras Registradas (<?php echo count($cocineras); ?>)</h2>
                    <div class="stats-info">
                        <?php
                        $aprobados = array_filter($cocineras, function($c) { 
                            return $c['aprobado'] === true || $c['aprobado'] === 't'; 
                        });
                        echo count($aprobados) . ' aprobadas • ' . (count($cocineras) - count($aprobados)) . ' pendientes';
                        ?>
                    </div>
                </div>
                
                <?php if (count($cocineras) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre Completo</th>
                                    <th>Usuario</th>
                                    <th>Correo</th>
                                    <th>Teléfono</th>
                                    <th>Dirección</th>
                                    <th>Fecha Registro</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cocineras as $cocinera): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cocinera['id']); ?></td>
                                        <td>
                                            <span class="cocinera-icon">👩‍🍳</span>
                                            <strong><?php echo htmlspecialchars($cocinera['nombre_completo']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($cocinera['usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($cocinera['correo']); ?></td>
                                        <td>
                                            <?php if (!empty($cocinera['telefono'])): ?>
                                                <?php echo htmlspecialchars($cocinera['telefono']); ?>
                                            <?php else: ?>
                                                <span style="color: #9ca3af; font-style: italic;">No registrado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($cocinera['direccion_localidad'])): ?>
                                                <span class="localidad-badge">
                                                    <?php echo htmlspecialchars($cocinera['direccion_localidad']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #9ca3af; font-style: italic;">No especificada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($cocinera['fecha_registro'])); ?></td>
                                        <td>
                                            <?php if ($cocinera['aprobado'] === true || $cocinera['aprobado'] === 't'): ?>
                                                <span class="status-aprobado">✅ Aprobada</span>
                                            <?php else: ?>
                                                <span class="status-pendiente">⏳ Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-column">
                                            <!-- Botón para editar (puedes implementarlo después) -->
                                            <!--
                                            <a href="editar_cocinera.php?id=<?php echo $cocinera['id']; ?>" class="btn-edit">
                                                Editar
                                            </a>
                                            -->
                                            <button onclick="confirmarEliminacion(<?php echo $cocinera['id']; ?>, '<?php echo htmlspecialchars(addslashes($cocinera['nombre_completo'])); ?>')" class="btn-delete">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👩‍🍳</div>
                        <h3>No hay cocineras registradas</h3>
                        <p>Las cocineras aparecerán aquí una vez que se registren en el sistema con el tipo "cocinera"</p>
                        <a href="cocineras.php" class="btn-nueva-cocinera">
                            👩‍🍳 ¿Cocineras Nueva?
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function confirmarEliminacion(id, nombre) {
            if (confirm('¿Estás seguro de eliminar a \"' + nombre + '\"?\n\nEsta acción eliminará todos los datos de la cocinera y no se puede deshacer.')) {
                window.location.href = '?eliminar=' + id;
            }
        }
        
        // Auto-ocultar mensajes después de 5 segundos
        setTimeout(function() {
            const mensajes = document.querySelectorAll('.mensaje');
            mensajes.forEach(function(mensaje) {
                mensaje.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>

