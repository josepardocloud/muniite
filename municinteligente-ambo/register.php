<?php
session_start();
require_once 'includes/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = sanitizeInput($_POST['dni']);
    $nombres = sanitizeInput($_POST['nombres']);
    $apellidos = sanitizeInput($_POST['apellidos']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telefono = sanitizeInput($_POST['telefono']);
    $direccion = sanitizeInput($_POST['direccion']);
    $distrito_id = sanitizeInput($_POST['distrito_id']);
    $fecha_nacimiento = sanitizeInput($_POST['fecha_nacimiento']);
    
    // Validaciones
    if (strlen($dni) != 8 || !ctype_digit($dni)) {
        $errors['dni'] = 'El DNI debe tener 8 dígitos';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Ingrese un correo electrónico válido';
    }
    
    if (strlen($password) < 8) {
        $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($password != $confirm_password) {
        $errors['confirm_password'] = 'Las contraseñas no coinciden';
    }
    
    if (empty($errors)) {
        try {
            // Verificar si el DNI o email ya existen
            $check_sql = "SELECT id FROM usuarios WHERE dni = :dni OR email = :email";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([':dni' => $dni, ':email' => $email]);
            
            if ($check_stmt->fetch()) {
                $errors['general'] = 'El DNI o correo electrónico ya están registrados';
            } else {
                // Insertar nuevo usuario
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $insert_sql = "INSERT INTO usuarios (dni, nombres, apellidos, email, password, telefono, direccion, distrito_id, fecha_nacimiento, rol) 
                               VALUES (:dni, :nombres, :apellidos, :email, :password, :telefono, :direccion, :distrito_id, :fecha_nacimiento, 'ciudadano')";
                
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([
                    ':dni' => $dni,
                    ':nombres' => $nombres,
                    ':apellidos' => $apellidos,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':telefono' => $telefono,
                    ':direccion' => $direccion,
                    ':distrito_id' => $distrito_id,
                    ':fecha_nacimiento' => $fecha_nacimiento
                ]);
                
                $user_id = $pdo->lastInsertId();
                logAction('registro_exitoso', 'usuarios', $user_id);
                
                $success = true;
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Error al registrar el usuario. Por favor, inténtelo más tarde.';
            error_log("Error de registro: " . $e->getMessage());
        }
    }
}

// Obtener distritos para el select
$distritos = [];
try {
    $sql = "SELECT id, nombre FROM distritos ORDER BY nombre";
    $stmt = $pdo->query($sql);
    $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener distritos: " . $e->getMessage());
}

// Mostrar página de registro
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow mt-4">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Registro de Ciudadano</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h4 class="alert-heading">¡Registro exitoso!</h4>
                            <p>Tu cuenta ha sido creada correctamente. Ahora puedes <a href="login.php">iniciar sesión</a>.</p>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                        <?php endif; ?>
                        
                        <form action="register.php" method="POST" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="dni" class="form-label">DNI *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['dni']) ? 'is-invalid' : ''; ?>" 
                                           id="dni" name="dni" value="<?php echo $_POST['dni'] ?? ''; ?>" required>
                                    <?php if (isset($errors['dni'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['dni']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                           value="<?php echo $_POST['fecha_nacimiento'] ?? ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombres" class="form-label">Nombres *</label>
                                    <input type="text" class="form-control" id="nombres" name="nombres" 
                                           value="<?php echo $_POST['nombres'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="apellidos" class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                           value="<?php echo $_POST['apellidos'] ?? ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico *</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                           id="password" name="password" required>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                    <?php endif; ?>
                                    <small class="form-text text-muted">Mínimo 8 caracteres</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Contraseña *</label>
                                    <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                           id="confirm_password" name="confirm_password" required>
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                       value="<?php echo $_POST['telefono'] ?? ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" 
                                       value="<?php echo $_POST['direccion'] ?? ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="distrito_id" class="form-label">Distrito *</label>
                                <select class="form-select" id="distrito_id" name="distrito_id" required>
                                    <option value="">Seleccione un distrito</option>
                                    <?php foreach ($distritos as $distrito): ?>
                                        <option value="<?php echo $distrito['id']; ?>" 
                                            <?php echo (isset($_POST['distrito_id']) && $_POST['distrito_id'] == $distrito['id']) ? 'selected' : ''; ?>>
                                            <?php echo $distrito['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terminos" name="terminos" required>
                                <label class="form-check-label" for="terminos">
                                    Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#terminosModal">Términos y Condiciones</a> *
                                </label>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Registrarse</button>
                            </div>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="login.php">¿Ya tienes cuenta? Inicia sesión aquí</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Términos y Condiciones -->
<div class="modal fade" id="terminosModal" tabindex="-1" aria-labelledby="terminosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminosModalLabel">Términos y Condiciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Aceptación de los Términos</h6>
                <p>Al registrarte en la plataforma MunicInteligente Ambo, aceptas cumplir con estos términos y condiciones...</p>
                
                <h6>2. Uso de la Plataforma</h6>
                <p>La plataforma está diseñada para facilitar los trámites municipales de los ciudadanos de la provincia de Ambo...</p>
                
                <!-- Más contenido de términos y condiciones -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>