<?php
// notificaciones.php - VERSIÓN CON FILTROS
require_once 'config.php';

// Verificar autenticación usando el mismo sistema que validar_ine.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario desde la sesión admin
$nombre_usuario = isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'Admin';
$rol_usuario = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

// Obtener parámetros de filtro
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
$filtro_urgente = isset($_GET['urgente']) ? $_GET['urgente'] : 'todos';
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : 'recientes';

// Inicializar variables
$donaciones = [];
$menus = [];
$notificaciones = [];
$total_donaciones = 0;
$total_menus = 0;
$total_urgentes = 0;

try {
    $conn = getDB();
    
    // Consulta base para donaciones
    $sqlDonaciones = "SELECT 
                        nombre_donante, 
                        categoria_nombre, 
                        producto_especifico, 
                        cantidad, 
                        descripcion, 
                        fecha_creacion,
                        es_urgente
                      FROM publicaciones_donaciones 
                      WHERE 1=1";
    
    // Consulta base para menús
    $sqlMenus = "SELECT 
                    usuario_id, 
                    fecha, 
                    alimento_principal, 
                    plato_fuerte, 
                    guarnicion, 
                    postre, 
                    bebida,
                    created_at
                 FROM menus_publicados 
                 WHERE 1=1";
    
    // Aplicar filtros si es necesario
    if ($filtro_tipo === 'donaciones') {
        // Solo mostrar donaciones
        $stmtDonaciones = $conn->prepare($sqlDonaciones . " ORDER BY fecha_creacion DESC LIMIT 50");
        $stmtDonaciones->execute();
        $donaciones = $stmtDonaciones->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($filtro_tipo === 'menus') {
        // Solo mostrar menús
        $stmtMenus = $conn->prepare($sqlMenus . " ORDER BY created_at DESC LIMIT 50");
        $stmtMenus->execute();
        $menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Mostrar todos (por defecto)
        $stmtDonaciones = $conn->prepare($sqlDonaciones . " ORDER BY fecha_creacion DESC LIMIT 25");
        $stmtDonaciones->execute();
        $donaciones = $stmtDonaciones->fetchAll(PDO::FETCH_ASSOC);
        
        $stmtMenus = $conn->prepare($sqlMenus . " ORDER BY created_at DESC LIMIT 25");
        $stmtMenus->execute();
        $menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Combinar y ordenar todas las notificaciones por fecha
    foreach ($donaciones as $donacion) {
        $notificaciones[] = [
            'tipo' => 'donacion',
            'fecha' => $donacion['fecha_creacion'],
            'datos' => $donacion
        ];
    }
    
    foreach ($menus as $menu) {
        $notificaciones[] = [
            'tipo' => 'menu',
            'fecha' => $menu['created_at'],
            'datos' => $menu
        ];
    }
    
    // Ordenar por fecha
    if ($filtro_fecha === 'antiguos') {
        usort($notificaciones, function($a, $b) {
            return strtotime($a['fecha']) - strtotime($b['fecha']);
        });
    } else {
        // Recientes primero (por defecto)
        usort($notificaciones, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
    }
    
    // Aplicar filtro de urgentes
    if ($filtro_urgente === 'urgentes') {
        $notificaciones = array_filter($notificaciones, function($notif) {
            return $notif['tipo'] === 'donacion' && $notif['datos']['es_urgente'];
        });
    }
    
    // Calcular estadísticas
    $total_donaciones = count($donaciones);
    $total_menus = count($menus);
    $total_urgentes = 0;
    foreach ($donaciones as $donacion) {
        if ($donacion['es_urgente']) $total_urgentes++;
    }
    
} catch(PDOException $e) {
    $error_db = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Sistema Foodlink</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/notificaciones.css">
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
        <h1 class="welcome-title">Notificaciones del Sistema</h1>
        <p class="subtitle">Todas las actividades recientes de donaciones y menús publicados.</p>

        <!-- Sección de Filtros -->
        <div class="filtros-section">
            <h3 class="filtros-title">🔍 Filtros de Notificaciones</h3>
            
            <form method="GET" action="notificaciones.php" id="filtrosForm">
                <div class="filtros-grid">
                    <div class="filtro-group">
                        <label class="filtro-label">Tipo de Notificación</label>
                        <select name="tipo" class="filtro-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_tipo === 'todos' ? 'selected' : ''; ?>>Todos los tipos</option>
                            <option value="donaciones" <?php echo $filtro_tipo === 'donaciones' ? 'selected' : ''; ?>>Solo Donaciones</option>
                            <option value="menus" <?php echo $filtro_tipo === 'menus' ? 'selected' : ''; ?>>Solo Menús</option>
                        </select>
                    </div>
                    
                    <div class="filtro-group">
                        <label class="filtro-label">Estado</label>
                        <select name="urgente" class="filtro-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_urgente === 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                            <option value="urgentes" <?php echo $filtro_urgente === 'urgentes' ? 'selected' : ''; ?>>Solo Urgentes</option>
                        </select>
                    </div>
                    
                    <div class="filtro-group">
                        <label class="filtro-label">Ordenar por Fecha</label>
                        <select name="fecha" class="filtro-select" onchange="this.form.submit()">
                            <option value="recientes" <?php echo $filtro_fecha === 'recientes' ? 'selected' : ''; ?>>Más recientes primero</option>
                            <option value="antiguos" <?php echo $filtro_fecha === 'antiguos' ? 'selected' : ''; ?>>Más antiguos primero</option>
                        </select>
                    </div>
                </div>
                
                <div class="filtros-actions">
                    <button type="submit" class="btn-filtrar">
                        <span>Aplicar Filtros</span>
                    </button>
                    <a href="notificaciones.php" class="btn-limpiar">
                        <span>Limpiar Filtros</span>
                    </a>
                    <div class="resultados-info">
                        Mostrando <span class="resultados-count"><?php echo count($notificaciones); ?></span> notificaciones
                    </div>
                </div>
            </form>
        </div>

        <!-- Notificaciones -->
        <div class="notifications-container">
            <?php if (isset($error_db)): ?>
                <div class='notification'>
                    <h3 class='notification-title'>Error de conexión</h3>
                    <p class='notification-content'>No se pudo conectar con la base de datos. Por favor, verifica la configuración.</p>
                    <p><strong>Detalles:</strong> <?php echo htmlspecialchars($error_db); ?></p>
                </div>
            <?php elseif (count($notificaciones) > 0): ?>
                <?php foreach ($notificaciones as $notificacion): ?>
                    <?php if ($notificacion['tipo'] === 'donacion'): ?>
                        <?php $donacion = $notificacion['datos']; ?>
                        <div class='notification donation-notification'>
                            <div class='notification-header'>
                                <span class='notification-type donation-badge'>🎁 Donación</span>
                                <?php if ($donacion['es_urgente']): ?>
                                    <span class='urgente-badge'>URGENTE</span>
                                <?php endif; ?>
                                <span class='notification-date'><?php echo date('d/m/Y H:i', strtotime($donacion['fecha_creacion'])); ?></span>
                            </div>
                            <h3 class='notification-title'><?php echo htmlspecialchars($donacion['nombre_donante']); ?> ha realizado una donación</h3>
                            <?php if (!empty($donacion['descripcion'])): ?>
                                <p class='notification-content'><?php echo htmlspecialchars($donacion['descripcion']); ?></p>
                            <?php endif; ?>
                            <div class='notification-details'>
                                <div class='detail-item'><span class='detail-label'>Categoría</span><span class='detail-value'><?php echo htmlspecialchars($donacion['categoria_nombre']); ?></span></div>
                                <div class='detail-item'><span class='detail-label'>Producto</span><span class='detail-value'><?php echo htmlspecialchars($donacion['producto_especifico']); ?></span></div>
                                <div class='detail-item'><span class='detail-label'>Cantidad</span><span class='detail-value'><?php echo htmlspecialchars($donacion['cantidad']); ?></span></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php $menu = $notificacion['datos']; ?>
                        <div class='notification menu-notification'>
                            <div class='notification-header'>
                                <span class='notification-type menu-badge'>👩‍🍳 Menú Publicado</span>
                                <span class='notification-date'><?php echo date('d/m/Y H:i', strtotime($menu['created_at'])); ?></span>
                            </div>
                            <h3 class='notification-title'>Cocinera ID: <?php echo htmlspecialchars($menu['usuario_id']); ?> ha publicado un menú</h3>
                            <p class='notification-content'>Menú programado para el <?php echo date('d/m/Y', strtotime($menu['fecha'])); ?></p>
                            <div class='notification-details'>
                                <?php if (!empty($menu['alimento_principal'])): ?>
                                    <div class='detail-item'><span class='detail-label'>Alimento Principal</span><span class='detail-value'><?php echo htmlspecialchars($menu['alimento_principal']); ?></span></div>
                                <?php endif; ?>
                                <?php if (!empty($menu['plato_fuerte'])): ?>
                                    <div class='detail-item'><span class='detail-label'>Plato Fuerte</span><span class='detail-value'><?php echo htmlspecialchars($menu['plato_fuerte']); ?></span></div>
                                <?php endif; ?>
                                <?php if (!empty($menu['guarnicion'])): ?>
                                    <div class='detail-item'><span class='detail-label'>Guarnición</span><span class='detail-value'><?php echo htmlspecialchars($menu['guarnicion']); ?></span></div>
                                <?php endif; ?>
                                <?php if (!empty($menu['postre'])): ?>
                                    <div class='detail-item'><span class='detail-label'>Postre</span><span class='detail-value'><?php echo htmlspecialchars($menu['postre']); ?></span></div>
                                <?php endif; ?>
                                <?php if (!empty($menu['bebida'])): ?>
                                    <div class='detail-item'><span class='detail-label'>Bebida</span><span class='detail-value'><?php echo htmlspecialchars($menu['bebida']); ?></span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class='no-notifications'>
                    <div class='no-notifications-icon'>🔔</div>
                    <h3>No hay notificaciones disponibles</h3>
                    <p>No se han encontrado notificaciones que coincidan con los filtros aplicados.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Estadísticas -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">🎁</div>
                <div class="stat-info">
                    <div class="stat-label">Total Donaciones</div>
                    <div class="stat-value"><?php echo $total_donaciones; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👩‍🍳</div>
                <div class="stat-info">
                    <div class="stat-label">Total Menús</div>
                    <div class="stat-value"><?php echo $total_menus; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏰</div>
                <div class="stat-info">
                    <div class="stat-label">Notificaciones Mostradas</div>
                    <div class="stat-value"><?php echo count($notificaciones); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <div class="stat-label">Donaciones Urgentes</div>
                    <div class="stat-value"><?php echo $total_urgentes; ?></div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Animaciones y efectos interactivos
        document.addEventListener('DOMContentLoaded', function() {
            // Efecto parallax suave en las notificaciones
            const notifications = document.querySelectorAll('.notification');
            
            notifications.forEach(notification => {
                notification.addEventListener('mousemove', function(e) {
                    const rect = notification.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    
                    const rotateX = (y - centerY) / 25;
                    const rotateY = (centerX - x) / 25;
                    
                    notification.style.transform = `translateY(-4px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                });
                
                notification.addEventListener('mouseleave', function() {
                    notification.style.transform = 'translateY(0) rotateX(0) rotateY(0)';
                });
            });

            // Auto-submit del formulario cuando cambian los selects
            const selects = document.querySelectorAll('.filtro-select');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    document.getElementById('filtrosForm').submit();
                });
            });

            console.log('✅ Módulo de Notificaciones con Filtros - Cargado correctamente');
        });
    </script>
</body>
</html>