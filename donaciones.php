<?php
// donaciones.php - VERSIÓN SIMPLIFICADA CON COLORES POR CATEGORÍA
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario desde la sesión
$nombre_usuario = isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'Admin';
$usuario_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Mensajes de éxito o error
$mensaje = '';
$tipo_mensaje = '';

// Procesar la acción de "agarrar donación"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agarrar_donacion'])) {
    $donacion_id = (int)$_POST['donacion_id'];
    
    try {
        $pdo = getDB();
        
        // Verificar que la donación exista y esté disponible
        $stmt = $pdo->prepare("SELECT id, estado FROM publicaciones_donaciones WHERE id = ?");
        $stmt->execute([$donacion_id]);
        $donacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$donacion) {
            $mensaje = 'La donación no existe.';
            $tipo_mensaje = 'error';
        } elseif ($donacion['estado'] !== 'disponible') {
            $mensaje = 'Esta donación ya no está disponible.';
            $tipo_mensaje = 'error';
        } elseif (!$usuario_id) {
            $mensaje = 'Error: No se pudo identificar al usuario.';
            $tipo_mensaje = 'error';
        } else {
            // Actualizar la donación
            $updateStmt = $pdo->prepare("
                UPDATE publicaciones_donaciones 
                SET estado = 'no_disponible',
                    usuario_id = ?,
                    fecha_tomada = CURRENT_TIMESTAMP,
                    fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            if ($updateStmt->execute([$usuario_id, $donacion_id])) {
                $mensaje = 'Donación tomada exitosamente.';
                $tipo_mensaje = 'exito';
                
                // Redirigir para evitar reenvío del formulario
                header("Location: donaciones.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo_mensaje);
                exit;
            }
        }
    } catch (PDOException $e) {
        $mensaje = 'Error al procesar la donación: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener donaciones de la base de datos
$donaciones = [];
$total_donaciones = 0;
$donaciones_disponibles = 0;
$donaciones_pendientes = 0;

try {
    $pdo = getDB();
    
    // Consulta para obtener todas las donaciones
    $query = "
        SELECT 
            d.id,
            d.categoria_nombre,
            d.producto_especifico,
            d.cantidad,
            d.descripcion,
            d.estado,
            d.fecha_creacion,
            d.ubicacion,
            d.fecha_caducidad,
            d.imagen_url,
            d.es_urgente,
            d.nombre_donante,
            d.telefono_contacto,
            d.email_contacto,
            d.condiciones_entrega,
            d.fecha_tomada,
            d.usuario_id,
            d.fecha_actualizacion,
            u.nombre_completo as usuario_tomo_nombre,
            u.usuario as usuario_tomo_usuario
        FROM publicaciones_donaciones d
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        ORDER BY 
            CASE 
                WHEN d.estado = 'urgente' THEN 1 
                WHEN d.estado = 'disponible' THEN 2
                WHEN d.estado = 'pendiente' THEN 3
                WHEN d.estado = 'no_disponible' THEN 4
                ELSE 5 
            END,
            CASE WHEN d.estado = 'urgente' THEN d.fecha_caducidad END ASC,
            d.fecha_creacion DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $donaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas
    $total_donaciones = count($donaciones);
    
    foreach ($donaciones as $donacion) {
        if ($donacion['estado'] === 'disponible') {
            $donaciones_disponibles++;
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
                d.id,
                d.categoria_nombre,
                d.producto_especifico,
                d.cantidad,
                d.descripcion,
                d.estado,
                d.fecha_creacion,
                d.ubicacion,
                d.fecha_caducidad,
                d.imagen_url,
                d.es_urgente,
                d.nombre_donante,
                d.telefono_contacto,
                d.email_contacto,
                d.condiciones_entrega,
                d.fecha_tomada,
                d.usuario_id,
                d.fecha_actualizacion,
                u.nombre_completo as usuario_tomo_nombre,
                u.usuario as usuario_tomo_usuario
            FROM publicaciones_donaciones d
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            WHERE 
                d.producto_especifico ILIKE ? OR
                d.categoria_nombre ILIKE ? OR
                d.nombre_donante ILIKE ? OR
                d.descripcion ILIKE ? OR
                u.nombre_completo ILIKE ? OR
                u.usuario ILIKE ?
            ORDER BY 
                CASE 
                    WHEN d.estado = 'urgente' THEN 1 
                    WHEN d.estado = 'disponible' THEN 2
                    WHEN d.estado = 'pendiente' THEN 3
                    WHEN d.estado = 'no_disponible' THEN 4
                    ELSE 5 
                END,
                CASE WHEN d.estado = 'urgente' THEN d.fecha_caducidad END ASC,
                d.fecha_creacion DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $searchTerm = '%' . $busqueda . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
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

// Mostrar mensajes desde URL
if (isset($_GET['mensaje']) && isset($_GET['tipo'])) {
    $mensaje = $_GET['mensaje'];
    $tipo_mensaje = $_GET['tipo'];
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
                <a href="donaciones.php" class="active">Módulo de Donaciones</a>
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

        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
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
                    Disponibles (<?php echo $donaciones_disponibles; ?>)
                </a>
                <a href="?estado=no_disponible&busqueda=<?php echo urlencode($busqueda); ?>" class="filter-btn <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'no_disponible') ? 'active' : ''; ?>">
                    Tomadas (<?php echo $total_donaciones - $donaciones_disponibles - $donaciones_pendientes; ?>)
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
                    <div class="stat-value"><?php echo $donaciones_disponibles; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <div class="stat-label">Tomadas</div>
                    <div class="stat-value"><?php echo $total_donaciones - $donaciones_disponibles - $donaciones_pendientes; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <div class="stat-label">Pendientes</div>
                    <div class="stat-value"><?php echo $donaciones_pendientes; ?></div>
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
                    
                    // Verificar si la donación ya fue tomada
                    $es_tomada = ($donacion['estado'] === 'no_disponible');
                    $tomada_por_mi = ($es_tomada && $donacion['usuario_id'] == $usuario_id);
                    ?>
                    <div class="donation-card <?php echo $categoria_clase; ?> <?php echo $donacion['es_urgente'] ? 'urgente' : ''; ?>">
                        <?php if ($es_tomada): ?>
                            <div class="tomada-badge">TOMADA</div>
                        <?php elseif ($donacion['es_urgente']): ?>
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
                                    case 'disponible':
                                        echo 'status-active';
                                        break;
                                    case 'no_disponible':
                                        echo 'status-no_disponible';
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

                        <!-- Sección de acciones -->
                        <div class="donation-actions">
                            <?php if ($donacion['estado'] === 'disponible'): ?>
                                <form method="POST" action="" class="tomar-donacion-form">
                                    <input type="hidden" name="donacion_id" value="<?php echo $donacion['id']; ?>">
                                    <button type="button" class="btn-tomar-donacion" onclick="confirmarTomarDonacion(<?php echo $donacion['id']; ?>, '<?php echo htmlspecialchars(addslashes($donacion['producto_especifico'])); ?>')">
                                        <span>🫴</span> Agarrar Donación
                                    </button>
                                </form>
                            <?php elseif ($donacion['estado'] === 'no_disponible'): ?>
                                <div class="donacion-tomada-info">
                                    <?php if (!empty($donacion['usuario_tomo_nombre'])): ?>
                                        <p><strong>👤 Tomada por:</strong> <?php echo htmlspecialchars($donacion['usuario_tomo_nombre']); ?></p>
                                        <?php if (!empty($donacion['usuario_tomo_usuario'])): ?>
                                            <p><small>Usuario: <?php echo htmlspecialchars($donacion['usuario_tomo_usuario']); ?></small></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p><strong>📌 Donación tomada</strong></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($donacion['fecha_tomada'])): ?>
                                        <p class="fecha-info">
                                            <strong>📅 Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($donacion['fecha_tomada'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($tomada_por_mi): ?>
                                        <button type="button" class="btn-liberar-donacion" onclick="liberarDonacion(<?php echo $donacion['id']; ?>)">
                                            🔓 Liberar Donación
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de confirmación -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Confirmar acción</h3>
            <p class="modal-message" id="modalMessage"></p>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="cerrarModal()">Cancelar</button>
                <button type="button" class="btn-modal-confirm" onclick="tomarDonacionConfirmada()">Sí, tomar donación</button>
            </div>
        </div>
    </div>

    <script>
        // Variables para el modal
        let donacionIdActual = null;
        let formularioActual = null;

        // Mostrar modal de confirmación
        function confirmarTomarDonacion(id, nombre) {
            donacionIdActual = id;
            formularioActual = document.querySelector(`form input[name="donacion_id"][value="${id}"]`).closest('form');
            
            document.getElementById('modalMessage').textContent = 
                `¿Estás seguro de que quieres tomar la donación "${nombre}"?\n\nUna vez tomada, será marcada como no disponible para otros administradores.`;
            
            document.getElementById('confirmModal').style.display = 'flex';
        }

        // Función para liberar donación (si la tomaste tú)
        function liberarDonacion(id) {
            if (confirm('¿Quieres liberar esta donación?\n\nAl liberarla, otros administradores podrán tomarla nuevamente.')) {
                // Crear formulario para liberar
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'donacion_id';
                inputId.value = id;
                
                const inputAccion = document.createElement('input');
                inputAccion.type = 'hidden';
                inputAccion.name = 'liberar_donacion';
                inputAccion.value = '1';
                
                form.appendChild(inputId);
                form.appendChild(inputAccion);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Cerrar modal
        function cerrarModal() {
            document.getElementById('confirmModal').style.display = 'none';
            donacionIdActual = null;
            formularioActual = null;
        }

        // Confirmar y enviar formulario
        function tomarDonacionConfirmada() {
            if (formularioActual) {
                // Agregar campo oculto para confirmar la acción
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'agarrar_donacion';
                input.value = '1';
                formularioActual.appendChild(input);
                
                formularioActual.submit();
            }
            cerrarModal();
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target === modal) {
                cerrarModal();
            }
        }

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
