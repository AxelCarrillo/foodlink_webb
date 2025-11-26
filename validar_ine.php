<?php
// validar_ine.php - VERSIÓN SIMPLIFICADA

require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Obtener conexión a la base de datos
try {
    $pdo = getDB();
    
    if (!$pdo) {
        throw new Exception("Error: No se pudo conectar a la base de datos");
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}

// ✅ Consulta para obtener beneficiados pendientes
try {
    $stmt = $pdo->prepare("
        SELECT id, usuario, nombre_completo, telefono, foto_url, fecha_registro 
        FROM usuarios 
        WHERE tipo = :tipo AND aprobado = false
        ORDER BY fecha_registro DESC
    ");
    
    $stmt->execute([
        ':tipo' => 'beneficiado'
    ]);
    
    $beneficiados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("
    <div style='font-family: Arial; padding: 20px; background: #f8d7da; border: 2px solid #dc3545; border-radius: 8px; max-width: 800px; margin: 50px auto;'>
        <h2 style='color: #721c24;'>❌ Error en la Consulta</h2>
        <p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <hr>
        <h3>Posibles soluciones:</h3>
        <ol>
            <li>Verifica que la tabla 'usuarios' exista</li>
            <li>Verifica que la columna 'aprobado' sea de tipo BOOLEAN</li>
            <li>Ejecuta <a href='verificar_tabla.php'>verificar_tabla.php</a> para diagnóstico</li>
        </ol>
    </div>
    ");
}

// Procesar APROBACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aprobar_id'])) {
    $aprobar_id = intval($_POST['aprobar_id']);
    
    try {
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET aprobado = true 
            WHERE id = :id AND tipo = :tipo AND aprobado = false
        ");
        
        $stmt->execute([
            ':id' => $aprobar_id,
            ':tipo' => 'beneficiado'
        ]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?aprobado=1");
            exit;
        } else {
            $error_aprobacion = "No se pudo aprobar el beneficiado. Puede que ya haya sido procesado.";
        }
        
    } catch (Exception $e) {
        $error_aprobacion = "Error al aprobar: " . $e->getMessage();
    }
}

// Procesar ELIMINACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
    $eliminar_id = intval($_POST['eliminar_id']);
    $razon = $_POST['razon_eliminar'] ?? 'Sin razón especificada';
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM usuarios 
            WHERE id = :id AND tipo = :tipo AND aprobado = false
        ");
        
        $stmt->execute([
            ':id' => $eliminar_id,
            ':tipo' => 'beneficiado'
        ]);
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?eliminado=1");
        exit;
        
    } catch (Exception $e) {
        $error_eliminar = "Error al eliminar: " . $e->getMessage();
    }
}

$nombre_usuario = $_SESSION['admin_user'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar INE - Sistema Alimentario</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/validar_ine.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo-section">
                <div class="logo">FL</div>
                <div class="title">Sistema Presidencial de Apoyo Alimentario</div>
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
        <h1 class="welcome-title">Validar INE - Beneficiados Pendientes</h1>
        <p class="subtitle">Revisa y aprueba las solicitudes de beneficiados</p>

        <?php if (isset($_GET['aprobado']) && $_GET['aprobado'] == 1): ?>
            <div class="alert alert-success">
                ✅ Beneficiado aprobado correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['eliminado']) && $_GET['eliminado'] == 1): ?>
            <div class="alert alert-success">
                ✅ Petición eliminada correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($error_aprobacion)): ?>
            <div class="alert alert-danger">
                ❌ <?php echo htmlspecialchars($error_aprobacion); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_eliminar)): ?>
            <div class="alert alert-danger">
                ❌ <?php echo htmlspecialchars($error_eliminar); ?>
            </div>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card">
                <h3><?php echo count($beneficiados); ?></h3>
                <p>Pendientes de Aprobación</p>
            </div>
        </div>

        <div class="content">
            <?php if (empty($beneficiados)): ?>
                <div class="empty-state">
                    <h3>🎉 No hay beneficiados pendientes</h3>
                    <p>Todos los registros han sido procesados.</p>
                </div>
            <?php else: ?>
                <div class="beneficiados-grid">
                    <?php foreach ($beneficiados as $beneficiado): ?>
                        <div class="beneficiado-card">
                            <div class="beneficiado-header">
                                <div class="beneficiado-info">
                                    <h3>
                                        👤 <?php echo htmlspecialchars($beneficiado['nombre_completo'] ?? 'Nombre no disponible'); ?>
                                        <span class="info-badge">PENDIENTE</span>
                                    </h3>
                                    <p>📞 <?php echo htmlspecialchars($beneficiado['telefono'] ?? 'Teléfono no disponible'); ?></p>
                                    <p class="timestamp">
                                        📅 <?php echo date('d/m/Y H:i', strtotime($beneficiado['fecha_registro'] ?? 'now')); ?>
                                    </p>
                                </div>
                                <?php if (!empty($beneficiado['foto_url'])): ?>
                                    <div class="foto-container">
                                        <img src="<?php echo htmlspecialchars($beneficiado['foto_url']); ?>" 
                                             alt="INE" 
                                             onclick="abrirImagen('<?php echo htmlspecialchars($beneficiado['foto_url']); ?>')">
                                        <div>📷 Click para ampliar</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Información de credenciales -->
                            <div class="credenciales-info">
                                <h4>🔐 Credenciales del Usuario:</h4>
                                <div class="credencial-item">
                                    <span class="credencial-label">Usuario:</span>
                                    <span class="credencial-value">
                                        <?php echo htmlspecialchars($beneficiado['usuario'] ?? 'No asignado'); ?>
                                    </span>
                                </div>
                                <div class="credencial-item">
                                    <span class="credencial-label">Estado:</span>
                                    <span class="credencial-value" style="color: #dc3545; font-weight: bold;">
                                        ⚠️ Pendiente de aprobación
                                    </span>
                                </div>
                                <div style="margin-top: 16px; padding: 12px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #f59e0b;">
                                    <small>✅ El usuario ya proporcionó sus credenciales durante el registro</small>
                                </div>
                            </div>
                            
                            <!-- Botones de acción -->
                            <div class="actions-container">
                                <form method="POST" action="" style="flex: 1;">
                                    <input type="hidden" name="aprobar_id" value="<?php echo htmlspecialchars($beneficiado['id']); ?>">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('¿Está seguro de que desea APROBAR este beneficiado?')">
                                        ✅ Aprobar Beneficiado
                                    </button>
                                </form>
                                
                                <button type="button" class="btn btn-danger" 
                                        onclick="mostrarModalEliminar(<?php echo htmlspecialchars($beneficiado['id']); ?>, '<?php echo htmlspecialchars(addslashes($beneficiado['nombre_completo'] ?? 'Usuario')); ?>')">
                                    ❌ Rechazar Solicitud
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal para imagen -->
    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <!-- Modal para eliminar -->
    <div id="modalEliminar" class="modal-eliminar">
        <div class="modal-eliminar-content">
            <h3>❌ Rechazar Solicitud</h3>
            <p id="textoEliminar">¿Está seguro que desea rechazar esta solicitud?</p>
            
            <form id="formEliminar" method="POST" action="">
                <input type="hidden" name="eliminar_id" id="eliminar_id">
                
                <div class="form-group">
                    <label for="razon_eliminar">Motivo del rechazo (opcional):</label>
                    <textarea name="razon_eliminar" id="razon_eliminar" rows="3" 
                              placeholder="Ej: Documentación incompleta, información incorrecta, etc."></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEliminar()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        ❌ Sí, Rechazar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirImagen(url) {
            document.getElementById('modalImage').src = url;
            document.getElementById('imageModal').style.display = 'block';
        }

        document.querySelector('.close').onclick = function() {
            document.getElementById('imageModal').style.display = 'none';
        }

        function mostrarModalEliminar(id, nombre) {
            document.getElementById('eliminar_id').value = id;
            document.getElementById('textoEliminar').innerHTML = 
                '¿Está seguro que desea rechazar la solicitud de <strong>' + nombre + '</strong>?';
            document.getElementById('modalEliminar').style.display = 'block';
        }

        function cerrarModalEliminar() {
            document.getElementById('modalEliminar').style.display = 'none';
            document.getElementById('razon_eliminar').value = '';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('imageModal');
            var modalEliminar = document.getElementById('modalEliminar');
            
            if (event.target == modal) {
                modal.style.display = 'none';
            }
            if (event.target == modalEliminar) {
                cerrarModalEliminar();
            }
        }

        document.getElementById('formEliminar').addEventListener('submit', function() {
            var btn = this.querySelector('.btn-danger');
            btn.disabled = true;
            btn.innerHTML = '⏳ Rechazando...';
        });
    </script>
</body>
</html>