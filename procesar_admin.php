<?php
// procesar_admin.php
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Verificar que sea método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: agregar_admin.php');
    exit;
}

// Obtener y validar datos del formulario
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';
$confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

// Validaciones básicas
$errores = [];

if (empty($nombre_completo)) {
    $errores[] = "El nombre completo es obligatorio";
}

if (empty($usuario)) {
    $errores[] = "El nombre de usuario es obligatorio";
}

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El correo electrónico no es válido";
}

if (empty($contrasena) || strlen($contrasena) < 8) {
    $errores[] = "La contraseña debe tener al menos 8 caracteres";
}

if ($contrasena !== $confirmar_contrasena) {
    $errores[] = "Las contraseñas no coinciden";
}

// Si hay errores, redirigir con mensajes
if (!empty($errores)) {
    $_SESSION['errores_admin'] = $errores;
    $_SESSION['datos_formulario'] = $_POST;
    header('Location: agregar_admin.php');
    exit;
}

try {
    // Verificar si el usuario ya existe
    $sql_check = "SELECT id FROM usuarios WHERE usuario = ? OR correo = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$usuario, $correo]);
    
    if ($stmt_check->fetch()) {
        $_SESSION['errores_admin'] = ["El nombre de usuario o correo electrónico ya existe"];
        $_SESSION['datos_formulario'] = $_POST;
        header('Location: agregar_admin.php');
        exit;
    }

    // Hash de la contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Insertar nuevo administrador
    // El campo 'tipo' siempre será 'admin' para usuarios administradores
    // El campo 'aprobado' será true para administradores
    $sql_insert = "INSERT INTO usuarios (
        usuario, 
        contrasena, 
        password, 
        tipo, 
        correo, 
        nombre_completo, 
        telefono, 
        fecha_registro, 
        aprobado
    ) VALUES (?, ?, ?, 'admin', ?, ?, ?, NOW(), true)";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    $resultado = $stmt_insert->execute([
        $usuario,
        $contrasena_hash, // contrasena (hash para login)
        $contrasena,      // password (texto plano - si es necesario para referencia)
        $correo,
        $nombre_completo,
        $telefono
    ]);

    if ($resultado) {
        $_SESSION['exito_admin'] = "Usuario administrador agregado correctamente";
    } else {
        $_SESSION['errores_admin'] = ["Error al agregar el usuario administrador"];
    }

} catch (PDOException $e) {
    error_log("Error al agregar administrador: " . $e->getMessage());
    $_SESSION['errores_admin'] = ["Error en el sistema. Por favor, intente nuevamente."];
}

header('Location: agregar_admin.php');
exit;
?>