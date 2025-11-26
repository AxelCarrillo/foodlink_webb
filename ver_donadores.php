<?php
// ver_donadores.php
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$nombre_usuario = isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'Admin';

// Manejo de errores para la consulta
try {
    // Obtener conexión PDO desde config.php
    $pdo = getDB();
    
    // Consulta para obtener los donadores (usuarios con tipo = 'donante')
    $query = "SELECT * FROM usuarios WHERE tipo = 'donante' ORDER BY fecha_registro DESC";
    $stmt = $pdo->query($query);
    
    $donadores = [];
    if ($stmt) {
        $donadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
    $donadores = [];
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    $donadores = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donadores - Sistema de Apoyo Alimentario</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/ver_donadores.css">
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
                <a href="ver_donadores.php">Donadores</a>
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
            <div>
                <h1 class="page-title">Donadores Registrados</h1>
                <p class="page-subtitle">Lista completa de todos los donadores en el sistema</p>
            </div>
            <a href="inicio.php" class="back-btn">← Volver al Inicio</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <span>🏢</span>
                    Lista de Donadores (<?php echo count($donadores); ?>)
                </h2>
            </div>

            <?php if (count($donadores) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donadores as $donador): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donador['id'] ?? 'N/A'); ?></td>
                                <td><strong><?php echo htmlspecialchars($donador['usuario']); ?></strong></td>
                                <td><?php echo htmlspecialchars($donador['nombre_completo'] ?? 'No especificado'); ?></td>
                                <td><?php echo htmlspecialchars($donador['correo'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($donador['telefono'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($donador['tipo']); ?></td>
                                <td>
                                    <?php 
                                    $estado = $donador['aprobado'] ?? false;
                                    $clase_estado = '';
                                    $texto_estado = '';
                                    
                                    if ($estado) {
                                        $clase_estado = 'status-active';
                                        $texto_estado = 'Activo';
                                    } else {
                                        $clase_estado = 'status-pending';
                                        $texto_estado = 'Pendiente';
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $clase_estado; ?>">
                                        <?php echo $texto_estado; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if (isset($donador['fecha_registro'])) {
                                        echo date('d/m/Y H:i', strtotime($donador['fecha_registro']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div>🏢</div>
                    <h3>No hay donadores registrados</h3>
                    <p>No se han encontrado usuarios con tipo "donante" en la base de datos.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Módulo de Donadores cargado correctamente');
            
            // Agregar efectos a las filas de la tabla
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('click', function() {
                    this.style.backgroundColor = '#f0f9ff';
                    setTimeout(() => {
                        this.style.backgroundColor = '';
                    }, 200);
                });
            });
        });
    </script>
</body>
</html>