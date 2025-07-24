<?php
session_start();
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = sanitizeInput($_POST['dni']);
    $password = $_POST['password'];
    
    try {
        $sql = "SELECT id, dni, nombres, apellidos, password, rol, activo FROM usuarios WHERE dni = :dni";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':dni' => $dni]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['activo']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_dni'] = $user['dni'];
                $_SESSION['user_name'] = $user['nombres'] . ' ' . $user['apellidos'];
                $_SESSION['user_role'] = $user['rol'];
                
                // Actualizar último acceso
                $update_sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([':id' => $user['id']]);
                
                logAction('login_exitoso', 'usuarios', $user['id']);
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Tu cuenta está desactivada. Contacta al administrador.';
                logAction('login_cuenta_inactiva', 'usuarios', $user['id']);
            }
        } else {
            $error = 'DNI o contraseña incorrectos';
            logAction('login_fallido', null, null, null, ['dni' => $dni]);
        }
    } catch (PDOException $e) {
        $error = 'Error al iniciar sesión. Por favor, inténtelo más tarde.';
        error_log("Error de login: " . $e->getMessage());
    }
}

// Mostrar página de login
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow mt-5">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Iniciar Sesión</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" class="form-control" id="dni" name="dni" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Ingresar</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="register.php">¿No tienes cuenta? Regístrate aquí</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>