<?php
session_start();
require_once '../../includes/functions.php';

// Verificar autenticación
checkRole(['ciudadano', 'funcionario', 'admin']);

$user = getCurrentUser();

// Obtener iniciativas de presupuesto participativo activas
$iniciativas = [];
try {
    $sql = "SELECT p.id, p.titulo, p.descripcion, p.fecha_creacion, p.fecha_cierre, 
                   COUNT(v.id) AS votos, d.nombre AS distrito_nombre,
                   (SELECT COUNT(*) FROM votaciones WHERE participacion_id = p.id AND usuario_id = :user_id) AS ya_voto
            FROM participacion p
            LEFT JOIN distritos d ON p.distrito_id = d.id
            LEFT JOIN votaciones v ON p.id = v.participacion_id
            WHERE p.tipo = 'presupuesto' AND p.estado = 'activo'
            GROUP BY p.id
            ORDER BY p.fecha_creacion DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user['id']]);
    $iniciativas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener iniciativas de presupuesto: " . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar las iniciativas de presupuesto participativo.';
}

// Procesar voto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['votar'])) {
    $participacion_id = (int)$_POST['participacion_id'];
    $opcion_voto = sanitizeInput($_POST['opcion_voto']);
    
    try {
        // Verificar que la iniciativa existe y está activa
        $sql = "SELECT id FROM participacion WHERE id = :id AND tipo = 'presupuesto' AND estado = 'activo'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $participacion_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception('La iniciativa de presupuesto no está disponible para votación.');
        }
        
        // Verificar que el usuario no haya votado antes
        $sql = "SELECT id FROM votaciones WHERE participacion_id = :participacion_id AND usuario_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':participacion_id' => $participacion_id,
            ':user_id' => $user['id']
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception('Ya has participado en esta votación.');
        }
        
        // Registrar voto en blockchain
        require_once '../../includes/Blockchain.php';
        $blockchain = new Blockchain($pdo);
        $hash = $blockchain->registrarVoto($participacion_id, $user['id'], $opcion_voto);
        
        if (!$hash) {
            throw new Exception('Error al registrar el voto en la blockchain.');
        }
        
        $_SESSION['success'] = '¡Tu voto ha sido registrado correctamente! Gracias por participar.';
        logAction('voto_presupuesto', 'votaciones', null, null, [
            'participacion_id' => $participacion_id,
            'opcion_voto' => $opcion_voto,
            'hash_blockchain' => $hash
        ]);
        
        header('Location: presupuesto.php');
        exit();
        
    } catch (Exception $e) {
        error_log("Error al procesar voto: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header('Location: presupuesto.php');
        exit();
    }
}

// Mostrar página
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="../">Participación Ciudadana</a></li>
            <li class="breadcrumb-item active" aria-current="page">Presupuesto Participativo</li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Presupuesto Participativo 2023</h2>
        <?php if ($user['rol'] == 'admin' || $user['rol'] == 'funcionario'): ?>
            <a href="nueva.php?tipo=presupuesto" class="btn btn-primary">Nueva Iniciativa</a>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">¿Qué es el Presupuesto Participativo?</h5>
            <p class="card-text">
                El Presupuesto Participativo es un mecanismo de participación ciudadana mediante el cual la población 
                decide sobre el uso de una parte de los recursos públicos para la ejecución de proyectos de desarrollo 
                local. En Ambo, el 10% del presupuesto municipal se asigna mediante este proceso.
            </p>
            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Presupuesto Total</h6>
                            <p class="card-text display-6">S/ 1,245,000</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Proyectos Presentados</h6>
                            <p class="card-text display-6"><?php echo count($iniciativas); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Participación Ciudadana</h6>
                            <p class="card-text display-6">
                                <?php 
                                $total_votos = array_sum(array_column($iniciativas, 'votos'));
                                echo $total_votos; 
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($iniciativas)): ?>
        <div class="alert alert-info">
            No hay iniciativas de presupuesto participativo activas en este momento.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($iniciativas as $iniciativa): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($iniciativa['titulo']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?php echo htmlspecialchars($iniciativa['distrito_nombre'] ?? 'Todos los distritos'); ?>
                            </h6>
                            <p class="card-text"><?php echo htmlspecialchars(substr($iniciativa['descripcion'], 0, 150)) . '...'; ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="badge bg-primary">
                                        <?php echo $iniciativa['votos']; ?> votos
                                    </span>
                                    <span class="badge bg-secondary ms-1">
                                        Cierre: <?php echo date('d/m/Y', strtotime($iniciativa['fecha_cierre'])); ?>
                                    </span>
                                </div>
                                
                                <a href="detalle.php?id=<?php echo $iniciativa['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Ver detalles
                                </a>
                            </div>
                            
                            <?php if ($iniciativa['ya_voto']): ?>
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-check-circle me-2"></i> Ya participaste en esta votación
                                </div>
                            <?php else: ?>
                                <form action="presupuesto.php" method="POST">
                                    <input type="hidden" name="participacion_id" value="<?php echo $iniciativa['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tu voto:</label>
                                        <select class="form-select" name="opcion_voto" required>
                                            <option value="">Selecciona una opción</option>
                                            <option value="apoyar">Apoyar este proyecto</option>
                                            <option value="no_apoyar">No apoyar este proyecto</option>
                                            <option value="abstener">Abstenerse</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" name="votar" class="btn btn-primary w-100">
                                        <i class="fas fa-vote-yea me-2"></i> Registrar Voto
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>