<?php
// alta_usuarios.php - INTERFAZ PARA ALTA DE USUARIOS
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';
    $nombre_completo = $_POST['nombre_completo'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($tipo_usuario)) {
        $errores[] = "Debe seleccionar un tipo de usuario";
    }
    
    if (empty($nombre_completo)) {
        $errores[] = "El nombre completo es obligatorio";
    }
    
    if (empty($usuario)) {
        $errores[] = "El nombre de usuario es obligatorio";
    }
    
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es obligatoria";
    }
    
    if ($password !== $confirm_password) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    if (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Si no hay errores, insertar en la base de datos
    if (empty($errores)) {
        try {
            $pdo = getDB();
            
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? OR correo = ?");
            $stmt->execute([$usuario, $correo]);
            $usuario_existente = $stmt->fetch();
            
            if ($usuario_existente) {
                $errores[] = "El usuario o correo electrónico ya está registrado";
            } else {
                // Insertar nuevo usuario
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $aprobado = true; // Los usuarios creados por admin se aprueban automáticamente
                
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios 
                    (usuario, contrasena, tipo, correo, nombre_completo, telefono, aprobado, fecha_registro) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $usuario,
                    $hashed_password,
                    $tipo_usuario,
                    $correo,
                    $nombre_completo,
                    $telefono,
                    $aprobado
                ]);
                
                $mensaje_exito = "✅ Usuario <strong>$nombre_completo</strong> registrado exitosamente como <strong>$tipo_usuario</strong>";
                
                // Limpiar el formulario
                $tipo_usuario = $nombre_completo = $usuario = $correo = $telefono = '';
            }
            
        } catch (PDOException $e) {
            error_log("Error al insertar usuario: " . $e->getMessage());
            $errores[] = "Error al registrar el usuario. Por favor, intente nuevamente.";
        }
    }
}

$nombre_usuario = $_SESSION['nombre'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta de Usuarios - Sistema Foodlink</title>
    <link rel="stylesheet" href="../css/admin_supre.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
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
            padding: 0 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ffffff;
            font-weight: 700;
            letter-spacing: -0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }

        .logo:hover {
            transform: scale(1.05) rotate(-5deg);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.35);
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-info span {
            font-size: 14px;
            color: #374151;
            font-weight: 600;
            padding: 8px 16px;
            background: #f3f4f6;
            border-radius: 8px;
        }

        .logout-btn {
            padding: 10px 20px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: #fecaca;
            color: #b91c1c;
            transform: translateY(-2px);
        }

        /* Main Content */
        main {
            max-width: 900px;
            margin: 0 auto;
            padding: 56px 32px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .page-title {
            font-size: 42px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 12px;
            letter-spacing: -1.5px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Form Container */
        .form-container {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 56px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
            position: relative;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3b82f6, #3b82f6, #3b82f6);
            border-radius: 20px 20px 0 0;
        }

        /* Alert Messages */
        .alert {
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 32px;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
            line-height: 1.6;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert ul {
            margin-top: 10px;
            margin-left: 24px;
        }

        .alert li {
            margin-bottom: 4px;
        }

        /* Section Headers */
        .section-header {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            font-size: 24px;
        }

        /* Form Styles */
        .form-section {
            margin-bottom: 40px;
        }

        .form-section:last-of-type {
            margin-bottom: 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .required {
            color: #ef4444;
            margin-left: 4px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            background: #ffffff;
            transition: all 0.2s ease;
            color: #111827;
            font-weight: 400;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            pointer-events: none;
            color: #9ca3af;
        }

        /* User Type Cards */
        .user-type-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .user-type-card {
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #ffffff;
            position: relative;
        }

        .user-type-card:hover {
            border-color: #3b82f6;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
        }

        .user-type-card.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
        }

        .user-type-card.selected::before {
            content: '✓';
            position: absolute;
            top: 12px;
            right: 12px;
            width: 28px;
            height: 28px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }

        .user-type-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .user-type-name {
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .user-type-desc {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
        }

        .user-type-input {
            display: none;
        }

        /* Password Strength */
        .password-strength {
            margin-top: 10px;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }

        .strength-weak {
            background: #ef4444;
            width: 33%;
        }

        .strength-medium {
            background: #f59e0b;
            width: 66%;
        }

        .strength-strong {
            background: #10b981;
            width: 100%;
        }

        .password-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
        }

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 16px;
            margin-top: 48px;
            padding-top: 32px;
            border-top: 2px solid #e5e7eb;
        }

        .btn {
            padding: 16px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            flex: 1;
        }

        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                height: auto;
                padding: 20px 24px;
                gap: 16px;
            }

            .title {
                font-size: 16px;
                text-align: center;
            }

            .user-info {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            main {
                padding: 32px 20px;
            }

            .page-title {
                font-size: 32px;
            }

            .form-container {
                padding: 32px 24px;
            }

            .user-type-cards {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
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
                <div class="title">Sistema de Gestión Foodlink</div>
            </div>
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($nombre_usuario); ?></span>
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title">Alta de Usuarios</h1>
        </div>

        <div class="form-container">
            <?php if (!empty($errores)): ?>
                <div class="alert alert-error">
                    <strong>❌ Errores encontrados:</strong>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success">
                    <?php echo $mensaje_exito; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="userForm">
                <!-- Sección: Tipo de Usuario -->
                <div class="form-section">
                    <div class="section-header">
                        <span class="section-icon">👥</span>
                        <span>Tipo de Usuario</span>
                    </div>
                    
                    <div class="user-type-cards">
                        <label class="user-type-card" for="tipo_cocinera">
                            <div class="user-type-icon">👩‍🍳</div>
                            <div class="user-type-name">Cocinera</div>
                            <div class="user-type-desc">Personal de cocina y preparación de alimentos</div>
                            <input type="radio" name="tipo_usuario" value="cocinera" id="tipo_cocinera" class="user-type-input" 
                                   <?php echo ($tipo_usuario ?? '') === 'cocinera' ? 'checked' : ''; ?> required>
                        </label>

                        <label class="user-type-card" for="tipo_donante">
                            <div class="user-type-icon">🤝</div>
                            <div class="user-type-name">Donante</div>
                            <div class="user-type-desc">Personas o empresas que realizan donaciones</div>
                            <input type="radio" name="tipo_usuario" value="donante" id="tipo_donante" class="user-type-input"
                                   <?php echo ($tipo_usuario ?? '') === 'donante' ? 'checked' : ''; ?>>
                        </label>

                        <label class="user-type-card" for="tipo_administrador">
                            <div class="user-type-icon">👨‍💼</div>
                            <div class="user-type-name">Administrador</div>
                            <div class="user-type-desc">Personal administrativo del sistema</div>
                            <input type="radio" name="tipo_usuario" value="administrador" id="tipo_administrador" class="user-type-input"
                                   <?php echo ($tipo_usuario ?? '') === 'administrador' ? 'checked' : ''; ?>>
                        </label>
                    </div>
                </div>

                <!-- Sección: Información Personal -->
                <div class="form-section">
                    <div class="section-header">
                        <span class="section-icon">📝</span>
                        <span>Información Personal</span>
                    </div>

                    <div class="form-group">
                        <label for="nombre_completo" class="form-label">
                            Nombre Completo<span class="required">*</span>
                        </label>
                        <input type="text" id="nombre_completo" name="nombre_completo" class="form-input" 
                               placeholder="Ej: María González López" 
                               value="<?php echo htmlspecialchars($nombre_completo ?? ''); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="usuario" class="form-label">
                                Nombre de Usuario<span class="required">*</span>
                            </label>
                            <input type="text" id="usuario" name="usuario" class="form-input" 
                                   placeholder="Ej: mgonzalez" 
                                   value="<?php echo htmlspecialchars($usuario ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" class="form-input" 
                                   placeholder="Ej: +52 55 1234 5678" 
                                   value="<?php echo htmlspecialchars($telefono ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="correo" class="form-label">
                            Correo Electrónico<span class="required">*</span>
                        </label>
                        <input type="email" id="correo" name="correo" class="form-input" 
                               placeholder="Ej: usuario@foodlink.com" 
                               value="<?php echo htmlspecialchars($correo ?? ''); ?>" required>
                    </div>
                </div>

                <!-- Sección: Contraseñas -->
                <div class="form-section">
                    <div class="section-header">
                        <span class="section-icon">🔐</span>
                        <span>Configuración de Acceso</span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">
                                Contraseña<span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" class="form-input" 
                                       placeholder="Mínimo 6 caracteres" required>
                                <span class="input-icon">🔒</span>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrength"></div>
                            </div>
                            <div class="password-hint">La contraseña debe tener al menos 6 caracteres</div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                Confirmar Contraseña<span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                       placeholder="Repite la contraseña" required>
                                <span class="input-icon">🔒</span>
                            </div>
                            <div id="passwordMatch" style="margin-top: 10px; font-size: 13px; font-weight: 600;"></div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="inicio.php" class="btn btn-secondary">
                        ← Volver al Inicio
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span>✅</span>
                        Registrar Usuario
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Selección de tipo de usuario
            const userTypeCards = document.querySelectorAll('.user-type-card');
            const userTypeInputs = document.querySelectorAll('.user-type-input');
            
            userTypeCards.forEach((card, index) => {
                card.addEventListener('click', function() {
                    userTypeCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    userTypeInputs[index].checked = true;
                });
            });

            // Verificar selección inicial
            userTypeInputs.forEach((input, index) => {
                if (input.checked) {
                    userTypeCards[index].classList.add('selected');
                }
            });

            // Validación de fortaleza de contraseña
            const passwordInput = document.getElementById('password');
            const passwordStrength = document.getElementById('passwordStrength');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('passwordMatch');

            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;

                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;

                passwordStrength.className = 'password-strength-bar';
                
                if (password.length === 0) {
                    passwordStrength.style.width = '0%';
                } else if (strength <= 2) {
                    passwordStrength.classList.add('strength-weak');
                } else if (strength <= 4) {
                    passwordStrength.classList.add('strength-medium');
                } else {
                    passwordStrength.classList.add('strength-strong');
                }
            });

            // Validación de coincidencia de contraseñas
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (confirmPassword.length === 0) {
                    passwordMatch.textContent = '';
                    passwordMatch.style.color = '';
                } else if (password === confirmPassword) {
                    passwordMatch.textContent = '✅ Las contraseñas coinciden';
                    passwordMatch.style.color = '#10b981';
                } else {
                    passwordMatch.textContent = '❌ Las contraseñas no coinciden';
                    passwordMatch.style.color = '#ef4444';
                }
            }

            passwordInput.addEventListener('input', checkPasswordMatch);
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);

            // Validación del formulario antes de enviar
            const form = document.getElementById('userForm');
            const submitBtn = document.getElementById('submitBtn');

            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('❌ Las contraseñas no coinciden. Por favor, verifica.');
                    confirmPasswordInput.focus();
                    return;
                }

                if (password.length < 6) {
                    e.preventDefault();
                    alert('❌ La contraseña debe tener al menos 6 caracteres.');
                    passwordInput.focus();
                    return;
                }

                submitBtn.innerHTML = '<div class="loading"></div> Registrando usuario...';
                submitBtn.disabled = true;
            });

            console.log('✅ Interfaz de Alta de Usuarios - Cargada correctamente');
        });
    </script>
</body>
</html>