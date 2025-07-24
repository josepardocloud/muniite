<?php
require_once __DIR__ . '/../config/database.php';

// Función para limpiar datos de entrada
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para verificar roles de usuario
function checkRole($allowedRoles) {
    if (!isLoggedIn() || !in_array($_SESSION['user_role'], $allowedRoles)) {
        header('Location: /login.php');
        exit();
    }
}

// Función para registrar logs
function logAction($action, $table = null, $record_id = null, $old_data = null, $new_data = null) {
    global $pdo;
    
    $sql = "INSERT INTO logs (usuario_id, accion, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip_address) 
            VALUES (:user_id, :action, :table, :record_id, :old_data, :new_data, :ip)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'] ?? null,
        ':action' => $action,
        ':table' => $table,
        ':record_id' => $record_id,
        ':old_data' => $old_data ? json_encode($old_data) : null,
        ':new_data' => $new_data ? json_encode($new_data) : null,
        ':ip' => $_SERVER['REMOTE_ADDR']
    ]);
}

// Función para obtener información del usuario actual
function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) return null;
    
    $sql = "SELECT u.*, d.nombre AS distrito_nombre 
            FROM usuarios u 
            LEFT JOIN distritos d ON u.distrito_id = d.id 
            WHERE u.id = :user_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Función para obtener trámites disponibles
function getAvailableProcedures($phase = null) {
    global $pdo;
    
    $sql = "SELECT * FROM tramites WHERE activo = TRUE";
    $params = [];
    
    if ($phase) {
        $sql .= " AND fase_implementacion = :phase";
        $params[':phase'] = $phase;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para simular blockchain (en un sistema real se integraría con una red blockchain)
function generateBlockchainHash($data) {
    $json_data = json_encode($data);
    return 'amb_' . hash('sha256', $json_data . time() . uniqid());
}
?>