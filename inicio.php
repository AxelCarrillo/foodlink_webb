<?php
// inicio.php - VERSIÓN COMPLETA
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$nombre_usuario = $_SESSION['admin_user'] ?? 'Admin';
$total_donaciones_activas = 0;
$total_beneficiarios = 0;

// OBTENER CONEXIÓN USANDO getDB()
try {
    $pdo = getDB();
    
    // CONSULTA 1: Donaciones activas (estado = 'disponible')
    $sql_donaciones = "SELECT COUNT(*) as total FROM publicaciones_donaciones WHERE estado = 'disponible'";
    $stmt_donaciones = $pdo->query($sql_donaciones);
    if ($stmt_donaciones) {
        $resultado_donaciones = $stmt_donaciones->fetch(PDO::FETCH_ASSOC);
        $total_donaciones_activas = $resultado_donaciones['total'] ?? 0;
    }
    
    // CONSULTA 2: Total beneficiarios (tipo = 'beneficiado')
    $sql_beneficiarios = "SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'beneficiado'";
    $stmt_beneficiarios = $pdo->query($sql_beneficiarios);
    if ($stmt_beneficiarios) {
        $resultado_beneficiarios = $stmt_beneficiarios->fetch(PDO::FETCH_ASSOC);
        $total_beneficiarios = $resultado_beneficiarios['total'] ?? 0;
    }
    
} catch (PDOException $e) {
    $total_donaciones_activas = 0;
    $total_beneficiarios = 0;
    $error_info = "<!-- Error de consulta: " . $e->getMessage() . " -->";
}
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
    <?php echo $error_info ?? ''; ?>
    
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
                    <div class="stat-value"><?php echo $total_beneficiarios; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎁</div>
                <div class="stat-info">
                    <div class="stat-label">Donaciones Activas</div>
                    <div class="stat-value"><?php echo $total_donaciones_activas; ?></div>
                </div>
            </div>
        </div>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Sistema cargado');
        });
    </script>
</body>
</html>
