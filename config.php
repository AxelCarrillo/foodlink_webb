<?php
// config.php - CONFIGURACIÓN OPTIMIZADA PARA RENDER + SUPABASE

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// CREDENCIALES DESDE VARIABLES DE ENTORNO
// ==========================================

define('DB_HOST', getenv('DB_HOST'));
define('DB_PORT', getenv('DB_PORT'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));

// ==========================================
// FUNCIÓN DE CONEXIÓN CON MANEJO DE ERRORES
// ==========================================
function getDB() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s",
            DB_HOST,
            DB_PORT,
            DB_NAME
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_PERSISTENT => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Configurar el esquema por defecto a public
        $pdo->exec("SET search_path TO public");
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Error de conexión DB: " . $e->getMessage());
        
        $errorMsg = "<h2>❌ Error de Conexión a la Base de Datos</h2>";
        $errorMsg .= "<p>No se pudo conectar con la base de datos. Por favor, contacte al administrador.</p>";
        
        if (strpos($e->getMessage(), 'password authentication failed') !== false) {
            $errorMsg .= "<p><strong>Error de autenticación:</strong> Verifique las credenciales de la base de datos.</p>";
        }
        
        die("
        <div style='font-family: Arial; padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; max-width: 600px; margin: 50px auto; text-align: center;'>
            $errorMsg
            <br>
            <a href='login.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;'>
                🔄 Volver al Login
            </a>
        </div>
        ");
    }
}

// ==========================================
// FUNCIÓN DE PRUEBA DE CONEXIÓN
// ==========================================
function testConnection() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT version(), current_database(), current_user");
        $info = $stmt->fetch();
        
        echo "<div style='font-family: Arial; padding: 20px; background: #d4edda; border: 2px solid #28a745; border-radius: 8px; max-width: 900px; margin: 50px auto;'>";
        echo "<h2 style='color: #155724;'>✅ Conexión Exitosa a Supabase</h2>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Parámetro</th><th>Valor</th></tr>";
        echo "<tr><td><strong>Host</strong></td><td>" . htmlspecialchars(DB_HOST) . "</td></tr>";
        echo "<tr><td><strong>Puerto</strong></td><td>" . htmlspecialchars(DB_PORT) . "</td></tr>";
        echo "<tr><td><strong>Base de datos</strong></td><td>" . htmlspecialchars($info['current_database']) . "</td></tr>";
        echo "<tr><td><strong>Usuario conectado</strong></td><td>" . htmlspecialchars($info['current_user']) . "</td></tr>";
        echo "<tr><td><strong>Versión PostgreSQL</strong></td><td>" . htmlspecialchars($info['version']) . "</td></tr>";
        echo "</table>";
        echo "</div>";
        
        return true;
    } catch (Exception $e) {
        echo "<div style='font-family: Arial; padding: 20px; background: #f8d7da; border: 2px solid #dc3545; border-radius: 8px; max-width: 900px; margin: 50px auto;'>";
        echo "<h2 style='color: #721c24;'>❌ Error de Conexión</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
        return false;
    }
}

// ==========================================
// FUNCIONES DE AUTENTICACIÓN (sin cambios)
// ==========================================

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && 
           $_SESSION['admin_logged_in'] === true && 
           isset($_SESSION['ultimo_acceso']) && 
           (time() - $_SESSION['ultimo_acceso'] < 3600);
}

function requireLogin() {
    if (!isLoggedIn()) {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_user']);
        unset($_SESSION['user_id']);
        
        header('Location: login.php');
        exit;
    }
    
    $_SESSION['ultimo_acceso'] = time();
}

function getUserData($username) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT id, usuario, contrasena, tipo, nombre_completo, correo, aprobado, foto_url
            FROM usuarios 
            WHERE usuario = ? AND aprobado = true
        ");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener datos de usuario: " . $e->getMessage());
        return false;
    }
}

function verifyPassword($password, $hash) {
    if (password_verify($password, $hash)) {
        return true;
    }
    
    if ($password === $hash) {
        return true;
    }
    
    return false;
}

function updatePasswordHash($userId, $password) {
    try {
        $pdo = getDB();
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios SET contrasena = ? WHERE id = ?");
        return $stmt->execute([$hashed_password, $userId]);
    } catch (PDOException $e) {
        error_log("Error al actualizar hash de contraseña: " . $e->getMessage());
        return false;
    }
}

function loginUser($userData, $password) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = $userData['usuario'];
    $_SESSION['nombre'] = $userData['nombre_completo'] ?: $userData['usuario'];
    $_SESSION['rol'] = $userData['tipo'];
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['correo'] = $userData['correo'];
    $_SESSION['foto_url'] = $userData['foto_url'];
    $_SESSION['ultimo_acceso'] = time();
    
    if ($password === $userData['contrasena']) {
        updatePasswordHash($userData['id'], $password);
    }
    
    return true;
}

function logout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    header('Location: login.php');
    exit;
}

// ==========================================
// CONFIGURACIONES GENERALES
// ==========================================
define('SITE_NAME', 'Sistema Presidencial de Apoyo Alimentario');
define('SESSION_TIMEOUT', 3600);

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

date_default_timezone_set('America/Mexico_City');

if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED);
}
?>
