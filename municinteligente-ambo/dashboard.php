<?php
session_start();
require_once 'includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getCurrentUser();
$tramites = getAvailableProcedures();

// Obtener solicitudes recientes del usuario
$solicitudes_recientes = [];
try {
    $sql = "SELECT s.id, s.codigo_tramite, s.fecha_solicitud, s.estado, t.nombre AS tramite_nombre 
            FROM solicitudes s 
            JOIN tramites t ON s.tramite_id = t.id 
            WHERE s.usuario_id = :user_id 
            ORDER BY s.fecha_solicitud DESC 
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $solicitudes_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener solicitudes recientes: " . $e->getMessage());
}

// Obtener noticias destacadas
$noticias_destacadas = [];
try {
    $sql = "SELECT id, titulo, imagen_path, fecha_publicacion 
            FROM noticias 
            WHERE destacada = TRUE 
            ORDER BY fecha_publicacion DESC 
            LIMIT 3";
    
    $stmt = $pdo->query($sql);
    $noticias_destacadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener noticias destacadas: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Panel de usuario -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="assets/images/user-default.png" class="rounded-circle" width="100" height="100" alt="Foto de perfil">
                    </div>
                    <h4><?php echo htmlspecialchars($user['nombres'] . ' ' . htmlspecialchars($user['apellidos']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['dni']); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['distrito_nombre'] ?? 'Distrito no especificado'); ?></p>
                    
                    <div class="d-grid gap-2">
                        <a href="perfil.php" class="btn btn-outline-primary">Editar Perfil</a>
                        <a href="logout.php" class="btn btn-outline-danger">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
            
            <!-- Accesos rápidos -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Accesos Rápidos</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="tramites/nuevo.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> Nuevo Trámite
                        </a>
                        <a href="participacion/" class="list-group-item list-group-item-action">
                            <i class="fas fa-comments me-2"></i> Participación Ciudadana
                        </a>
                        <a href="transparencia/" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar me-2"></i> Transparencia
                        </a>
                        <a href="reportes/nuevo.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-exclamation-triangle me-2"></i> Reportar Problema
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Bienvenida -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title">Bienvenido, <?php echo htmlspecialchars($user['nombres']); ?></h3>
                    <p class="card-text">Desde esta plataforma puedes realizar trámites municipales, participar en decisiones de tu comunidad y acceder a información transparente de la gestión municipal.</p>
                    
                    <div class="search-bar mt-3">
                        <input type="text" class="form-control" placeholder="¿Qué trámite necesitas realizar hoy?">
                        <button class="btn btn-primary">Buscar</button>
                    </div>
                </div>
            </div>
            
            <!-- Trámites más solicitados -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Trámites más solicitados</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($tramites as $tramite): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($tramite['nombre']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($tramite['descripcion']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted"><?php echo $tramite['duracion_estimada']; ?> días hábiles</small>
                                            <a href="tramites/nuevo.php?id=<?php echo $tramite['id']; ?>" class="btn btn-sm btn-primary">Iniciar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Mis trámites recientes -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Mis Trámites Recientes</h5>
                        <a href="tramites/" class="btn btn-sm btn-light">Ver Todos</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($solicitudes_recientes)): ?>
                        <p class="text-muted">No has realizado ningún trámite recientemente.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Trámite</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes_recientes as $solicitud): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($solicitud['codigo_tramite']); ?></td>
                                            <td><?php echo htmlspecialchars($solicitud['tramite_nombre']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_solicitud'])); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php echo $solicitud['estado'] == 'completado' ? 'bg-success' : 
                                                          ($solicitud['estado'] == 'rechazado' ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $solicitud['estado'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="tramites/detalle.php?id=<?php echo $solicitud['id']; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Noticias destacadas -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Noticias Destacadas</h5>
                        <a href="noticias/" class="btn btn-sm btn-light">Ver Todas</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($noticias_destacadas as $noticia): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <?php if ($noticia['imagen_path']): ?>
                                        <img src="<?php echo htmlspecialchars($noticia['imagen_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h6>
                                        <p class="card-text text-muted small"><?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?></p>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <a href="noticias/detalle.php?id=<?php echo $noticia['id']; ?>" class="btn btn-sm btn-outline-primary">Leer más</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>