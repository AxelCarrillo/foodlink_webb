<?php
// donaciones.php - VERSIÓN SIMPLIFICADA CON COLORES POR CATEGORÍA
require_once 'config.php';

// Verificar autenticación usando el MISMO sistema que inicio.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario desde la sesión admin
$nombre_usuario = isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'Admin';
$rol_usuario = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

// Obtener donaciones de la base de datos
$donaciones = [];
$total_donaciones = 0;
$donaciones_activas = 0;
$donaciones_pendientes = 0;

try {
    // Usar tu función getDB() para obtener la conexión
    $pdo = getDB();
    
    // Consulta para obtener todas las donaciones
    $query = "
        SELECT 
            id,
            categoria_nombre,
            producto_especifico,
            cantidad,
            descripcion,
            estado,
            fecha_creacion,
            ubicacion,
            fecha_caducidad,
            imagen_url,
            es_urgente,
            nombre_donante,
            telefono_contacto,
            email_contacto,
            condiciones_entrega
        FROM publicaciones_donaciones 
        ORDER BY 
            CASE WHEN estado = 'urgente' THEN 1 ELSE 2 END,
            fecha_creacion DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $donaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas
    $total_donaciones = count($donaciones);
    
    foreach ($donaciones as $donacion) {
        if ($donacion['estado'] === 'activa' || $donacion['estado'] === 'disponible') {
            $donaciones_activas++;
        } elseif ($donacion['estado'] === 'pendiente') {
            $donaciones_pendientes++;
        }
    }
    
} catch (PDOException $e) {
    $error = "Error al cargar las donaciones: " . $e->getMessage();
}

// Procesar búsqueda si existe
$busqueda = '';
if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
    $busqueda = trim($_GET['busqueda']);
    try {
        $pdo = getDB();
        $query = "
            SELECT 
                id,
                categoria_nombre,
                producto_especifico,
                cantidad,
                descripcion,
                estado,
                fecha_creacion,
                ubicacion,
                fecha_caducidad,
                imagen_url,
                es_urgente,
                nombre_donante,
                telefono_contacto,
                email_contacto,
                condiciones_entrega
            FROM publicaciones_donaciones 
            WHERE 
                producto_especifico ILIKE ? OR
                categoria_nombre ILIKE ? OR
                nombre_donante ILIKE ? OR
                descripcion ILIKE ?
            ORDER BY 
                CASE WHEN estado = 'urgente' THEN 1 ELSE 2 END,
                fecha_creacion DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $searchTerm = '%' . $busqueda . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $donaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Error en la búsqueda: " . $e->getMessage();
    }
}

// Procesar filtro por estado
if (isset($_GET['estado']) && !empty($_GET['estado']) && $_GET['estado'] !== 'todos') {
    $estado_filtro = $_GET['estado'];
    $donaciones = array_filter($donaciones, function($donacion) use ($estado_filtro) {
        return $donacion['estado'] === $estado_filtro;
    });
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Donaciones - Sistema Presidencial de Apoyo Alimentario</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/donaciones.css">
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
        <h1 class="page-title">Módulo de Donaciones</h1>
        <p class="page-subtitle">Gestión integral de donaciones recibidas y su distribución</p>

        <?php if (isset($error)): ?>
            <div class="error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Barra de acciones -->
        <div class="action-bar">
            <form method="GET" class="search-form">
                <span class="search-icon">🔍</span>
                <input type="text" name="busqueda" placeholder="Buscar donaciones..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <input type="hidden" name="estado" value="<?php echo $_GET['estado'] ?? 'todos'; ?>">
            </form>
            <div class="action-buttons">
                <a href="donaciones.php" class="btn btn-secondary">
                    <span>🔄</span> Actualizar
                </a>
            </div>
        </div>

        <!-- Filtros por estado -->
        <div class="filter-section">
            <div class="filter-title">Filtrar por estado:</div>
            <div class="filter-options">
                <a href="?estado=todos&busqueda=<?php echo urlencode($busqueda); ?>" class="filter-btn <?php echo (!isset($_GET['estado']) || $_GET['estado'] === 'todos') ? 'active' : ''; ?>">
                    Todas (<?php echo $total_donaciones; ?>)
                </a>
                <a href="?estado=disponible&busqueda=<?php echo urlencode($busqueda); ?>" class="filter-btn <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'disponible') ? 'active' : ''; ?>">
                    Disponibles
                </a>
                <a href="?estado=pendiente&busqueda=<?php echo urlencode($busqueda); ?>" class="filter-btn <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'pendiente') ? 'active' : ''; ?>">
                    Pendientes (<?php echo $donaciones_pendientes; ?>)
                </a>
                <a href="?estado=urgente&busqueda=<?php echo urlencode($busqueda); ?>" class="filter-btn <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'urgente') ? 'active' : ''; ?>">
                    Urgentes
                </a>
            </div>
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
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-label">Disponibles</div>
                    <div class="stat-value"><?php echo $donaciones_activas; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <div class="stat-label">Pendientes</div>
                    <div class="stat-value"><?php echo $donaciones_pendientes; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <div class="stat-label">Valor Estimado</div>
                    <div class="stat-value">$<?php echo number_format($total_donaciones * 100, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Donaciones -->
        <div class="cards-container">
            <?php if (empty($donaciones)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎁</div>
                    <h3 class="empty-state-title">No se encontraron donaciones</h3>
                    <p class="empty-state-description">
                        <?php echo isset($_GET['busqueda']) || isset($_GET['estado']) ? 
                            'Intenta ajustar los filtros de búsqueda.' : 
                            'No hay donaciones registradas en el sistema.'; ?>
                    </p>
                    <a href="donaciones.php" class="btn btn-primary">Ver todas las donaciones</a>
                </div>
            <?php else: ?>
                <?php foreach ($donaciones as $donacion): ?>
                    <?php
                    // Determinar la clase CSS según la categoría
                    $categoria_clase = strtolower(str_replace(' ', '-', $donacion['categoria_nombre']));
                    $categoria_clase = preg_replace('/[^a-z0-9\-]/', '', $categoria_clase);
                    
                    // Si la categoría no tiene una clase específica, usar 'otros'
                    if (!in_array($categoria_clase, ['comida-rapida', 'proteinas', 'vegetales', 'frutas', 'lacteos', 'cereales', 'bebidas'])) {
                        $categoria_clase = 'otros';
                    }
                    ?>
                    <div class="donation-card <?php echo $categoria_clase; ?> <?php echo $donacion['es_urgente'] ? 'urgente' : ''; ?>">
                        <?php if ($donacion['es_urgente']): ?>
                            <div class="urgente-badge">URGENTE</div>
                        <?php endif; ?>
                        
                        <div class="donation-header">
                            <h3 class="donation-title"><?php echo htmlspecialchars($donacion['producto_especifico']); ?></h3>
                            <span class="donation-category category-<?php echo $categoria_clase; ?>">
                                <?php echo htmlspecialchars($donacion['categoria_nombre']); ?>
                            </span>
                        </div>

                        <div class="donation-details">
                            <div class="donation-detail">
                                <span class="donation-detail-label">Cantidad:</span>
                                <span class="donation-detail-value"><?php echo htmlspecialchars($donacion['cantidad']); ?></span>
                            </div>
                            <div class="donation-detail">
                                <span class="donation-detail-label">Donante:</span>
                                <span class="donation-detail-value"><?php echo htmlspecialchars($donacion['nombre_donante']); ?></span>
                            </div>
                            <div class="donation-detail">
                                <span class="donation-detail-label">Ubicación:</span>
                                <span class="donation-detail-value"><?php echo htmlspecialchars($donacion['ubicacion']); ?></span>
                            </div>
                        </div>

                        <?php if (!empty($donacion['descripcion'])): ?>
                            <div class="donation-description">
                                <?php echo htmlspecialchars($donacion['descripcion']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="donation-footer">
                            <small><?php echo date('d/m/Y', strtotime($donacion['fecha_creacion'])); ?></small>
                            <span class="status-badge 
                                <?php 
                                switch($donacion['estado']) {
                                    case 'activa':
                                    case 'disponible':
                                        echo 'status-active';
                                        break;
                                    case 'pendiente':
                                        echo 'status-pending';
                                        break;
                                    case 'urgente':
                                        echo 'status-urgente';
                                        break;
                                    default:
                                        echo 'status-pending';
                                }
                                ?>
                            ">
                                <?php echo htmlspecialchars(ucfirst($donacion['estado'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Búsqueda automática
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="busqueda"]');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        });
    </script>
</body>
</html>