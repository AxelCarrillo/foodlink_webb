<?php
// cocineras.php - VERSIÓN PARA POSTGRESQL/SUPABASE

// Incluir configuración primero (maneja sesión y BD)
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

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    $nombre_completo = trim($_POST['nombre_completo']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    
    // Validaciones
    if (empty($nombre_completo) || empty($correo) || empty($contrasena)) {
        $mensaje = 'Todos los campos obligatorios deben ser completados';
        $tipo_mensaje = 'error';
    } elseif ($contrasena !== $confirmar_contrasena) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } elseif (strlen($contrasena) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar si el correo ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? AND tipo = 'cocinera'");
            $stmt->execute([$correo]);
            
            if ($stmt->rowCount() > 0) {
                $mensaje = 'Este correo ya está registrado';
                $tipo_mensaje = 'error';
            } else {
                // Insertar nueva cocinera
                $password_hash = password_hash($contrasena, PASSWORD_DEFAULT);
                
                // Generar nombre de usuario automático
                $usuario = strtolower(str_replace(' ', '.', $nombre_completo));
                
                // Verificar si el usuario ya existe y hacerlo único si es necesario
                $contador = 1;
                $usuario_base = $usuario;
                while (true) {
                    $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
                    $stmt_check->execute([$usuario]);
                    if ($stmt_check->rowCount() === 0) {
                        break;
                    }
                    $usuario = $usuario_base . '.' . $contador;
                    $contador++;
                }
                
                // Para PostgreSQL usamos CURRENT_TIMESTAMP en lugar de NOW()
                $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, contrasena, tipo, correo, nombre_completo, telefono, fecha_registro, aprobado) VALUES (?, ?, 'cocinera', ?, ?, ?, CURRENT_TIMESTAMP, true)");
                
                if ($stmt->execute([$usuario, $password_hash, $correo, $nombre_completo, $telefono])) {
                    $mensaje = 'Cocinera registrada exitosamente. Usuario: ' . $usuario;
                    $tipo_mensaje = 'exito';
                    
                    // Limpiar el formulario después de registro exitoso
                    $_POST['nombre_completo'] = '';
                    $_POST['correo'] = '';
                    $_POST['telefono'] = '';
                }
            }
        } catch (PDOException $e) {
            $mensaje = 'Error en la base de datos: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Procesar eliminación
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
    $stmt = $pdo->query("SELECT id, nombre_completo, correo, telefono, fecha_registro FROM usuarios WHERE tipo = 'cocinera' ORDER BY fecha_registro DESC");
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

        /* Header */
        header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        nav {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 72px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo {
            width: 48px;
            height: 48px;
            background: #ffffff;
            border: 2px solid #3b82f6;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #3b82f6;
            font-weight: 700;
            letter-spacing: -0.5px;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.3px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a {
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: #3b82f6;
            background: #f0f9ff;
        }

        .nav-links a[href="cocineras.php"] {
            color: #3b82f6;
            background: #f0f9ff;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: 16px;
            padding-left: 16px;
            border-left: 1px solid #e5e7eb;
        }

        .user-info span {
            font-size: 14px;
            color: #374151;
            font-weight: 600;
        }

        .logout-btn {
            padding: 8px 16px !important;
            background: #fee2e2 !important;
            color: #dc2626 !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
        }

        .logout-btn:hover {
            background: #fecaca !important;
            color: #b91c1c !important;
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
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-top: 24px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Form Section */
        .form-section {
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

        .form-section h2 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .required {
            color: #ef4444;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #ffffff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* List Section */
        .list-section {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 32px;
            animation: cardFadeIn 0.5s ease-out forwards;
            opacity: 0;
            animation-delay: 0.2s;
        }

        .list-section h2 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                height: auto;
                padding: 16px 20px;
                gap: 16px;
            }

            .title {
                font-size: 14px;
                text-align: center;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }

            .nav-links a {
                font-size: 13px;
                padding: 8px 12px;
            }

            .user-info {
                margin-left: 0;
                padding-left: 0;
                border-left: none;
                padding-top: 12px;
                border-top: 1px solid #e5e7eb;
                width: 100%;
                justify-content: center;
            }

            .page-header h1 {
                font-size: 28px;
            }

            main {
                padding: 32px 20px;
            }

            .form-section,
            .list-section {
                padding: 24px;
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
            <h1>Gestión de Usuarios Cocineras</h1>
            <p>Registra y administra a las cocineras del programa alimentario</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="form-section">
                <h2>Registrar Nueva Cocinera</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Nombre Completo <span class="required">*</span></label>
                        <input type="text" name="nombre_completo" value="<?php echo isset($_POST['nombre_completo']) ? htmlspecialchars($_POST['nombre_completo']) : ''; ?>" required placeholder="Ej: María García López">
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico - Para Contacto<span class="required">*</span></label>
                        <input type="email" name="correo" value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>" required placeholder="correo@ejemplo.com">
                    </div>

                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" 
                            value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>" 
                            placeholder="7711234567"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                            title="Por favor ingresa exactamente 10 dígitos (sin espacios ni guiones)">
                    </div>

                    <div class="form-group">
                        <label>Contraseña <span class="required">*</span></label>
                        <input type="password" name="contrasena" required minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="form-group">
                        <label>Confirmar Contraseña <span class="required">*</span></label>
                        <input type="password" name="confirmar_contrasena" required minlength="6" placeholder="Repite la contraseña">
                    </div>

                    <button type="submit" name="registrar" class="btn">Registrar Cocinera</button>
                </form>
            </div>

            <div class="list-section">
                <h2>Cocineras Registradas (<?php echo count($cocineras); ?>)</h2>
                
                <?php if (count($cocineras) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre Completo</th>
                                    <th>Correo</th>
                                    <th>Teléfono</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cocineras as $cocinera): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cocinera['id']); ?></td>
                                        <td><?php echo htmlspecialchars($cocinera['nombre_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($cocinera['correo']); ?></td>
                                        <td><?php echo htmlspecialchars($cocinera['telefono'] ?: 'No especificado'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($cocinera['fecha_registro'])); ?></td>
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
                        <p>Comienza registrando tu primera cocinera usando el formulario</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function confirmarEliminacion(id, nombre) {
            if (confirm('¿Estás seguro de eliminar a \"' + nombre + '\"?\n\nEsta acción no se puede deshacer.')) {
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