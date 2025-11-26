<?php
// agregar_admin.php
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Mostrar mensajes de éxito o error
$exito = '';
$errores = [];
$datos_formulario = [];

if (isset($_SESSION['exito_admin'])) {
    $exito = $_SESSION['exito_admin'];
    unset($_SESSION['exito_admin']);
}

if (isset($_SESSION['errores_admin'])) {
    $errores = $_SESSION['errores_admin'];
    unset($_SESSION['errores_admin']);
}

if (isset($_SESSION['datos_formulario'])) {
    $datos_formulario = $_SESSION['datos_formulario'];
    unset($_SESSION['datos_formulario']);
}

$nombre_usuario = isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'Admin';
$rol_usuario = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Administrador - Sistema de Apoyo Alimentario</title>
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

        .nav-links a[href="agregar_admin.php"] {
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
            max-width: 800px;
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

        .welcome-title {
            font-size: 36px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 12px;
            letter-spacing: -1px;
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

        .subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 48px;
            line-height: 1.6;
            animation: slideDown 0.7s ease-out;
        }

        /* Form Container */
        .form-container {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 48px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            animation: cardFadeIn 0.5s ease-out;
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

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
            color: white;
        }

        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .form-description {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input:hover {
            border-color: #d1d5db;
        }

        /* Button Styles */
        .form-actions {
            display: flex;
            gap: 16px;
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
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

            .welcome-title {
                font-size: 28px;
            }

            main {
                padding: 32px 20px;
            }

            .form-container {
                padding: 32px 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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

        /* Success Message */
        .success-message {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
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
                <a href="agregar_admin.php">Agregar Admin</a>
                <div class="user-info">
                    <span>👤 <?php echo htmlspecialchars($nombre_usuario); ?></span>
                    <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <h1 class="welcome-title">Agregar Usuario Administrador</h1>
        <p class="subtitle">Complete el formulario para registrar un nuevo usuario administrador en el sistema.</p>

        <div class="form-container">
            <div class="form-header">
                <div class="form-icon">👨‍💼</div>
                <h2 class="form-title">Nuevo Administrador</h2>
                <p class="form-description">Ingrese la información del nuevo usuario administrador. Todos los campos son obligatorios.</p>
            </div>

            <form id="adminForm" method="POST" action="procesar_admin.php">
                <?php if ($exito): ?>
                <div class="success-message">
                    ✅ <?php echo htmlspecialchars($exito); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($errores)): ?>
                <div class="error-message">
                    <?php foreach ($errores as $error): ?>
                        ❌ <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                        <input type="text" id="nombre_completo" name="nombre_completo" class="form-input" 
                               placeholder="Ej: Juan Pérez García" 
                               value="<?php echo isset($datos_formulario['nombre_completo']) ? htmlspecialchars($datos_formulario['nombre_completo']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="usuario" class="form-label">Nombre de Usuario *</label>
                        <input type="text" id="usuario" name="usuario" class="form-input" 
                               placeholder="Ej: juan.perez" 
                               value="<?php echo isset($datos_formulario['usuario']) ? htmlspecialchars($datos_formulario['usuario']) : ''; ?>" 
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="correo" class="form-label">Correo Electrónico *</label>
                        <input type="email" id="correo" name="correo" class="form-input" 
                               placeholder="Ej: juan.perez@ejemplo.com" 
                               value="<?php echo isset($datos_formulario['correo']) ? htmlspecialchars($datos_formulario['correo']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" class="form-input" 
                               placeholder="Ej: 555-123-4567" 
                               value="<?php echo isset($datos_formulario['telefono']) ? htmlspecialchars($datos_formulario['telefono']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contrasena" class="form-label">Contraseña *</label>
                        <input type="password" id="contrasena" name="contrasena" class="form-input" 
                               placeholder="Mínimo 8 caracteres" 
                               required minlength="8">
                    </div>

                    <div class="form-group">
                        <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña *</label>
                        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" class="form-input" 
                               placeholder="Repita la contraseña" 
                               required>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="inicio.php" class="btn btn-secondary">
                        <span>←</span>
                        Volver al Inicio
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span>👨‍💼</span>
                        Agregar Administrador
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('adminForm');
            const contrasena = document.getElementById('contrasena');
            const confirmarContrasena = document.getElementById('confirmar_contrasena');
            const submitBtn = document.getElementById('submitBtn');

            // Validación de contraseñas
            function validatePasswords() {
                if (contrasena.value !== confirmarContrasena.value) {
                    confirmarContrasena.setCustomValidity('Las contraseñas no coinciden');
                    return false;
                } else {
                    confirmarContrasena.setCustomValidity('');
                    return true;
                }
            }

            contrasena.addEventListener('input', validatePasswords);
            confirmarContrasena.addEventListener('input', validatePasswords);

            // Manejo del envío del formulario
            form.addEventListener('submit', function(e) {
                if (!validatePasswords()) {
                    e.preventDefault();
                    return;
                }

                // Mostrar loading
                submitBtn.innerHTML = '<div class="loading"></div> Procesando...';
                submitBtn.disabled = true;
            });

            console.log('✅ Formulario de agregar administrador - Cargado correctamente');
        });
    </script>
</body>
</html>