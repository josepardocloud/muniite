<?php
session_start();
require_once '../../includes/functions.php';

// Verificar autenticación
checkRole(['ciudadano', 'funcionario', 'admin']);

$user = getCurrentUser();

// Obtener iniciativas de participación ciudadana
$iniciativas = [];
try {
    $sql = "SELECT p.id, p.titulo, p.descripcion, p.tipo, p.fecha_creacion, p.fecha_cierre, p.estado, 
                   COUNT(v.id) AS votos, d.nombre AS distrito_nombre
            FROM participacion p
            LEFT JOIN distritos d ON p.distrito_id = d.id
            LEFT JOIN votaciones v ON p.id = v.participacion_id
            GROUP BY p.id
            ORDER BY p.fecha_creacion DESC";
    
    $stmt = $pdo->query($sql);
    $iniciativas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener iniciativas de participación: " . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar las iniciativas de participación.';
}

// Mostrar página
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Participación Ciudadana</li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Participación Ciudadana</h2>
        <a href="nueva.php" class="btn btn-primary">Nueva Iniciativa</a>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4" id="participacionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="activas-tab" data-bs-toggle="tab" data-bs-target="#activas" type="button" role="tab" aria-controls="activas" aria-selected="true">
                        Activas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cerradas-tab" data-bs-toggle="tab" data-bs-target="#cerradas" type="button" role="tab" aria-controls="cerradas" aria-selected="false">
                        Cerradas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="implementadas-tab" data-bs-toggle="tab" data-bs-target="#implementadas" type="button" role="tab" aria-controls="implementadas" aria-selected="false">
                        Implementadas
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="participacionTabContent">
                <div class="tab-pane fade show active" id="activas" role="tabpanel" aria-labelledby="activas-tab">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Distrito</th>
                                    <th>Votos</th>
                                    <th>Cierre</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $activas = array_filter($iniciativas, function($ini) {
                                    return $ini['estado'] == 'activo';
                                });
                                
                                if (empty($activas)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay iniciativas activas en este momento</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activas as $ini): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ini['titulo']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php echo $ini['tipo'] == 'presupuesto' ? 'bg-primary' : 
                                                          ($ini['tipo'] == 'votacion' ? 'bg-info' : 'bg-secondary'); ?>">
                                                    <?php echo ucfirst($ini['tipo']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($ini['distrito_nombre'] ?? 'Todos'); ?></td>
                                            <td><?php echo $ini['votos']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($ini['fecha_cierre'])); ?></td>
                                            <td>
                                                <a href="detalle.php?id=<?php echo $ini['id']; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                                <?php if ($user['rol'] == 'admin' || $user['rol'] == 'funcionario'): ?>
                                                    <a href="editar.php?id=<?php echo $ini['id']; ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="cerradas" role="tabpanel" aria-labelledby="cerradas-tab">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Distrito</th>
                                    <th>Votos</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $cerradas = array_filter($iniciativas, function($ini) {
                                    return $ini['estado'] != 'activo' && $ini['estado'] != 'implementado';
                                });
                                
                                if (empty($cerradas)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay iniciativas cerradas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cerradas as $ini): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ini['titulo']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php echo $ini['tipo'] == 'presupuesto' ? 'bg-primary' : 
                                                          ($ini['tipo'] == 'votacion' ? 'bg-info' : 'bg-secondary'); ?>">
                                                    <?php echo ucfirst($ini['tipo']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($ini['distrito_nombre'] ?? 'Todos'); ?></td>
                                            <td><?php echo $ini['votos']; ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php echo $ini['estado'] == 'cerrado' ? 'bg-warning text-dark' : 'bg-secondary'; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $ini['estado'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="detalle.php?id=<?php echo $ini['id']; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                                <?php if ($user['rol'] == 'admin' || $user['rol'] == 'funcionario'): ?>
                                                    <a href="editar.php?id=<?php echo $ini['id']; ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="implementadas" role="tabpanel" aria-labelledby="implementadas-tab">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Distrito</th>
                                    <th>Votos</th>
                                    <th>Implementación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $implementadas = array_filter($iniciativas, function($ini) {
                                    return $ini['estado'] == 'implementado';
                                });
                                
                                if (empty($implementadas)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay iniciativas implementadas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($implementadas as $ini): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ini['titulo']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php echo $ini['tipo'] == 'presupuesto' ? 'bg-primary' : 
                                                          ($ini['tipo'] == 'votacion' ? 'bg-info' : 'bg-secondary'); ?>">
                                                    <?php echo ucfirst($ini['tipo']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($ini['distrito_nombre'] ?? 'Todos'); ?></td>
                                            <td><?php echo $ini['votos']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($ini['fecha_cierre'])); ?></td>
                                            <td>
                                                <a href="detalle.php?id=<?php echo $ini['id']; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>