<?php
require_once 'config.php';
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $beneficiado_id = $_POST['beneficiado_id'];
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    $password = $_POST['password'];
    
    $pdo = getDB();
    
    try {
        // Verificar que las contraseñas coincidan
        if ($contrasena !== $password) {
            $_SESSION['error'] = "Las contraseñas no coinciden";
            header('Location: dashboard.php');
            exit;
        }
        
        // Actualizar el beneficiado
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET usuario = ?, contrasena = ?, password = ?, aprobado = true 
            WHERE id = ? AND tipo = 'beneficiado'
        ");
        
        $stmt->execute([$usuario, $contrasena, $password, $beneficiado_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "✅ Beneficiado aprobado exitosamente. Credenciales asignadas.";
        } else {
            $_SESSION['error'] = "❌ Error: No se pudo aprobar al beneficiado.";
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "❌ Error de base de datos: " . $e->getMessage();
    }
    
    header('Location: dashboard.php');
    exit;
}
?>