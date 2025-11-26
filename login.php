<?php
require_once 'config.php';

// ✅ SOLUCIÓN: Verificar estado de la sesión antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado con el sistema nuevo, redirigir según el rol
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $redirect_page = ($_SESSION['rol'] === 'adminsupre') ? 'admin_supre.php' : 'inicio.php';
    header('Location: ' . $redirect_page);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // ✅ CONEXIÓN A BASE DE DATOS - AUTENTICACIÓN MEJORADA
    try {
        $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Consulta el usuario en la base de datos
        $stmt = $pdo->prepare("
            SELECT id, usuario, contrasena, tipo, nombre_completo, correo, aprobado, foto_url
            FROM public.usuarios 
            WHERE usuario = ? AND aprobado = true
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verificar si la contraseña está en texto plano o hasheada
            if (password_verify($password, $user['contrasena'])) {
                // ✅ LOGIN EXITOSO - CONTRASEÑA HASHEDA
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $user['usuario'];
                $_SESSION['nombre'] = $user['nombre_completo'] ?: $user['usuario'];
                $_SESSION['rol'] = $user['tipo'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['correo'] = $user['correo'];
                $_SESSION['foto_url'] = $user['foto_url'];
                $_SESSION['ultimo_acceso'] = time();
                
                // ✅ REDIRECCIÓN SEGÚN TIPO DE USUARIO
                $redirect_page = ($user['tipo'] === 'adminsupre') ? 'admin_supre.php' : 'inicio.php';
                header('Location: ' . $redirect_page);
                exit;
                
            } elseif ($password === $user['contrasena']) {
                // ✅ LOGIN EXITOSO - CONTRASEÑA EN TEXTO PLANO (migración)
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $user['usuario'];
                $_SESSION['nombre'] = $user['nombre_completo'] ?: $user['usuario'];
                $_SESSION['rol'] = $user['tipo'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['correo'] = $user['correo'];
                $_SESSION['foto_url'] = $user['foto_url'];
                $_SESSION['ultimo_acceso'] = time();
                
                // Hashear la contraseña para mejorar seguridad
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $update_stmt = $pdo->prepare("UPDATE public.usuarios SET contrasena = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $user['id']]);
                
                // ✅ REDIRECCIÓN SEGÚN TIPO DE USUARIO
                $redirect_page = ($user['tipo'] === 'adminsupre') ? 'admin_supre.php' : 'inicio.php';
                header('Location: ' . $redirect_page);
                exit;
                
            } else {
                $error = "Contraseña incorrecta";
            }
        } else {
            $error = "Usuario no encontrado o no aprobado";
        }
    } catch (PDOException $e) {
        error_log("Error de conexión: " . $e->getMessage());
        $error = "Error del sistema. Por favor, intente más tarde.";
    }
}

// ✅ También verificar el sistema antiguo por si acaso
if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario'])) {
    header('Location: inicio.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Presidencial de Apoyo Alimentario</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        /* Decoración sutil de fondo */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -15%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.03) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 2;
        }

        .login-card {
            background: #ffffff;
            padding: 48px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.02),
                0 10px 40px rgba(0, 0, 0, 0.03);
            position: relative;
        }

        /* Línea superior decorativa */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            border-radius: 0 0 3px 3px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 64px;
            height: 64px;
            background: #ffffff;
            border: 2px solid #3b82f6;
            border-radius: 16px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #3b82f6;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .system-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .system-subtitle {
            color: #6b7280;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.5;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
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

        .login-form {
            margin-top: 32px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            background: #ffffff;
            transition: all 0.2s ease;
            color: #111827;
            font-weight: 400;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            pointer-events: none;
        }

        .btn {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-primary:hover {
            background: #2563eb;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #f3f4f6;
        }

        .security-notice {
            font-size: 13px;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
            
            .system-title {
                font-size: 20px;
            }
            
            .logo {
                width: 56px;
                height: 56px;
                font-size: 20px;
            }
        }

        /* Animaciones sutiles */
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

        .login-card {
            animation: fadeIn 0.4s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo">FL</div>
                <h1 class="system-title">Sistema Presidencial</h1>
                <p class="system-subtitle">de Apoyo Alimentario</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-input" 
                            required 
                            placeholder="Ingresa tu usuario"
                            autocomplete="username"
                        >
                        <span class="input-icon">👤</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            required 
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                        >
                        <span class="input-icon">🔒</span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="login-footer">
                <div class="security-notice">
                    <span>🛡️</span>
                    <span>Conexión segura y encriptada</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input');
            const button = document.querySelector('.btn-primary');
            const form = document.querySelector('.login-form');
            
            // Auto-focus en el primer campo
            document.getElementById('username').focus();
            
            // Animación suave en inputs
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-1px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
            
            // Estado de carga en el botón
            form.addEventListener('submit', function(e) {
                if (form.checkValidity()) {
                    button.style.background = '#1e40af';
                    button.innerHTML = '⏳ Ingresando...';
                    button.disabled = true;
                }
            });
        });

        console.log('🔐 Sistema de Login - Cargado correctamente');
    </script>
</body>
</html>