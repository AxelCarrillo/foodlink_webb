<?php
// beneficiarios.php - VERSIÓN CORREGIDA
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

// Primero, vamos a detectar qué tipos de usuarios existen
try {
    $stmt = $pdo->query("SELECT DISTINCT tipo FROM usuarios ORDER BY tipo");
    $tipos_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Buscar el tipo correcto para beneficiarios
    $tipo_beneficiario = 'beneficiario'; // valor por defecto
    
    // Verificar si existe 'beneficiado' u otros tipos similares
    $tipos_posibles = ['beneficiado', 'beneficiario', 'user', 'usuario'];
    foreach ($tipos_posibles as $tipo) {
        if (in_array($tipo, $tipos_existentes)) {
            $tipo_beneficiario = $tipo;
            break;
        }
    }
    
} catch (PDOException $e) {
    $mensaje = 'Error al detectar tipos de usuario: ' . $e->getMessage();
    $tipo_mensaje = 'error';
    $tipo_beneficiario = 'beneficiado'; // valor por defecto
}

// Procesar eliminación de beneficiario
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    
    try {
        // Verificar que existe antes de eliminar
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = ?");
        $stmt->execute([$id, $tipo_beneficiario]);
        
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = ?");
            if ($stmt->execute([$id, $tipo_beneficiario])) {
                $mensaje = 'Beneficiario eliminado exitosamente';
                $tipo_mensaje = 'exito';
            }
        } else {
            $mensaje = 'Beneficiario no encontrado';
            $tipo_mensaje = 'error';
        }
    } catch (PDOException $e) {
        $mensaje = 'Error al eliminar: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener lista de beneficiarios
try {
    $stmt = $pdo->prepare("
        SELECT 
            id, usuario, nombre_completo, correo, telefono, 
            fecha_registro, aprobado, tipo
        FROM usuarios 
        WHERE tipo = ?
        ORDER BY fecha_registro DESC
    ");
    $stmt->execute([$tipo_beneficiario]);
    $beneficiarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $mensaje = 'Error al cargar beneficiarios: ' . $e->getMessage();
    $tipo_mensaje = 'error';
    $beneficiarios = [];
}

$nombre_usuario = isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'Admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Beneficiarios - Sistema de Apoyo Alimentario</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/beneficiado.css">

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
            <h1>Gestión de Beneficiarios</h1>
            <p>Visualiza y administra a los beneficiarios registrados en el programa alimentario</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="list-section">
                <div class="stats-header">
                    <h2>Beneficiarios Registrados (<?php echo count($beneficiarios); ?>)</h2>
                    <div class="stats-info">
                        <?php
                        $aprobados = array_filter($beneficiarios, function($b) { 
                            return $b['aprobado'] === true || $b['aprobado'] === 't'; 
                        });
                        echo count($aprobados) . ' aprobados • ' . (count($beneficiarios) - count($aprobados)) . ' pendientes';
                        ?>
                    </div>
                </div>
                
                <?php if (count($beneficiarios) > 0): ?>
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
                                <?php foreach ($beneficiarios as $beneficiario): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($beneficiario['id']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($beneficiario['nombre_completo']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($beneficiario['usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($beneficiario['correo']); ?></td>
                                        <td>
                                            <?php if (!empty($beneficiario['telefono'])): ?>
                                                <?php echo htmlspecialchars($beneficiario['telefono']); ?>
                                            <?php else: ?>
                                                <span style="color: #9ca3af; font-style: italic;">No registrado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($beneficiario['fecha_registro'])); ?></td>
                                        <td>
                                            <?php if ($beneficiario['aprobado'] === true || $beneficiario['aprobado'] === 't'): ?>
                                                <span class="status-aprobado">✅ Aprobado</span>
                                            <?php else: ?>
                                                <span class="status-pendiente">⏳ Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="confirmarEliminacion(<?php echo $beneficiario['id']; ?>, '<?php echo htmlspecialchars(addslashes($beneficiario['nombre_completo'])); ?>')" class="btn-delete">Eliminar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <h3>No hay beneficiarios registrados</h3>
                        <p>Los beneficiarios aparecerán aquí una vez que se registren en el sistema con el tipo "<?php echo $tipo_beneficiario; ?>"</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function confirmarEliminacion(id, nombre) {
            if (confirm('¿Estás seguro de eliminar a \"' + nombre + '\"?\n\nEsta acción eliminará todos los datos del beneficiario y no se puede deshacer.')) {
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