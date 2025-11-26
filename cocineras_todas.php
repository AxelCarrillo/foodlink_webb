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

// Obtener lista de cocineras
try {
    $stmt = $pdo->query("
        SELECT 
            id, usuario, nombre_completo, correo, telefono, 
            fecha_registro, aprobado, tipo
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
    <link rel="stylesheet" href="css/coci_todas.css">
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
            <h1>Gestión de Cocineras</h1>
            <p>Visualiza y administra a las cocineras registradas en el programa alimentario</p>
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
                                        <td><?php echo date('d/m/Y H:i', strtotime($cocinera['fecha_registro'])); ?></td>
                                        <td>
                                            <?php if ($cocinera['aprobado'] === true || $cocinera['aprobado'] === 't'): ?>
                                                <span class="status-aprobado">✅ Aprobada</span>
                                            <?php else: ?>
                                                <span class="status-pendiente">⏳ Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="confirmarEliminacion(<?php echo $cocinera['id']; ?>, '<?php echo htmlspecialchars(addslashes($cocinera['nombre_completo'])); ?>')" class="btn-delete">Eliminar</button>
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
                        <a href="regis_cocineras.php" class="btn-register">
                            ➕ Registrar Nueva Cocinera
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