<?php
// inicio.php - VERSIÓN FINAL
require_once 'config.php'; // Incluir config

// Verificar autenticación usando el mismo sistema que validar_ine.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario desde la sesión admin
$nombre_usuario = isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'Admin';
$rol_usuario = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Presidencial de Apoyo Alimentario</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/style_inicio.css">
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
                <a href="notificaciones.php">Notificaciones</a>
                <div class="user-info">
                    <span>👤 <?php echo htmlspecialchars($nombre_usuario); ?></span>
                    <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <h1 class="welcome-title">Bienvenido al Sistema de Apoyo Alimentario</h1>
        <p class="subtitle">Sistema integral para la gestión y administración del programa de apoyo alimentario municipal.</p>

        <div class="cards-container">
            <a href="beneficiarios.php" class="card">
                <div class="card-icon">👥</div>
                <h2 class="card-title">Beneficiarios</h2>
                <p class="card-description">Gestión y validación de usuarios del programa de apoyo alimentario</p>
            </a>

            <a href="cocineras_todas.php" class="card">
                <div class="card-icon">👩‍🍳</div>
                <h2 class="card-title">Cocineras</h2>
                <p class="card-description">Administración del personal de cocina</p>
            </a>

            <a href="donaciones.php" class="card">
                <div class="card-icon">🎁</div>
                <h2 class="card-title">Donaciones</h2>
                <p class="card-description">Gestión de donaciones</p>
            </a>

            <!-- NUEVO RECUADRO PARA VER DONADORES -->
            <a href="ver_donadores.php" class="card">
                <div class="card-icon">🏢</div>
                <h2 class="card-title">Donadores</h2>
                <p class="card-description">Ver y gestionar todos los donadores registrados en el sistema</p>
            </a>
        </div>

        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-label">Total Beneficiarios</div>
                    <div class="stat-value">1,247</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👨‍👩‍👧‍👦</div>
                <div class="stat-info">
                    <div class="stat-label">Familias Atendidas</div>
                    <div class="stat-value">342</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎁</div>
                <div class="stat-info">
                    <div class="stat-label">Donaciones Activas</div>
                    <div class="stat-value">28</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-label">Validaciones Hoy</div>
                    <div class="stat-value">15</div>
                </div>
            </div>
        </div>

    </main>

    <script>
        // Animaciones y efectos interactivos
        document.addEventListener('DOMContentLoaded', function() {
            // Efecto parallax suave en las cards
            const cards = document.querySelectorAll('.card');
            
            cards.forEach(card => {
                card.addEventListener('mousemove', function(e) {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    
                    const rotateX = (y - centerY) / 20;
                    const rotateY = (centerX - x) / 20;
                    
                    card.style.transform = `translateY(-8px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                });
                
                card.addEventListener('mouseleave', function() {
                    card.style.transform = 'translateY(0) rotateX(0) rotateY(0)';
                });
            });

            // Efecto de clic en las cards
            cards.forEach(card => {
                card.addEventListener('click', function(e) {
                    const ripple = document.createElement('div');
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(59, 130, 246, 0.3)';
                    ripple.style.width = '20px';
                    ripple.style.height = '20px';
                    ripple.style.left = e.offsetX - 10 + 'px';
                    ripple.style.top = e.offsetY - 10 + 'px';
                    ripple.style.animation = 'ripple 0.6s ease-out';
                    ripple.style.pointerEvents = 'none';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                });
            });

            console.log('✅ Sistema de Apoyo Alimentario - Cargado correctamente');
        });

        // Función para agregar administrador - Redirige a agregar_admin.php
        function agregarAdministrador() {
            window.location.href = 'agregar_admin.php';
        }

        // Animación CSS para el ripple
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                from {
                    transform: scale(0);
                    opacity: 1;
                }
                to {
                    transform: scale(20);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>