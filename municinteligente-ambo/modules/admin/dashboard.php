<?php
session_start();
require_once '../../../includes/functions.php';

// Verificar autenticación y rol de administrador
checkRole(['admin']);

$user = getCurrentUser();

// Obtener estadísticas para el dashboard
$stats = [];
try {
    // Total de usuarios
    $sql = "SELECT COUNT(*) AS total FROM usuarios";
    $stmt = $pdo->query($sql);
    $stats['total_usuarios'] = $stmt->fetchColumn();
    
    // Total de trámites
    $sql = "SELECT COUNT(*) AS total FROM solicitudes";
    $stmt = $pdo->query($sql);
    $stats['total_tramites'] = $stmt->fetchColumn();
    
    // Trámites por estado
    $sql = "SELECT estado, COUNT(*) AS cantidad FROM solicitudes GROUP BY estado";
    $stmt = $pdo->query($sql);
    $stats['tramites_por_estado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Participación ciudadana
    $sql = "SELECT COUNT(*) AS total FROM participacion";
    $stmt = $pdo->query($sql);
    $stats['total_participacion'] = $stmt->fetchColumn();
    
    // Últimos trámites
    $sql = "SELECT s.id, s.codigo_tramite, s.fecha_solicitud, s.estado, t.nombre AS tramite_nombre, 
                   u.nombres AS usuario_nombre, u.apellidos AS usuario_apellido
            FROM solicitudes s
            JOIN tramites t ON s.tramite_id = t.id
            JOIN usuarios u ON s.usuario_id = u.id
            ORDER BY s.fecha_solicitud DESC
            LIMIT 5";
    $stmt = $pdo->query($sql);
    $stats['ultimos_tramites'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Últimos reportes
    $sql = "SELECT r.id, r.titulo, r.fecha_reporte, r.estado, u.nombres AS usuario_nombre, u.apellidos AS usuario_apellido
            FROM reportes r
            JOIN usuarios u ON r.usuario_id = u.id
            ORDER BY r.fecha_reporte DESC
            LIMIT 5";
    $stmt = $pdo->query($sql);
    $stats['ultimos_reportes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar las estadísticas del sistema.';
}

// Mostrar página
require_once '../../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <img src="../../../assets/images/logo-white.png" alt="Logo MunicInteligente" width="150">
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../tramites/">
                            <i class="fas fa-tasks me-2"></i> Gestión de Trámites
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../participacion/">
                            <i class="fas fa-users me-2"></i> Participación Ciudadana
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../transparencia/">
                            <i class="fas fa-chart-bar me-2"></i> Transparencia
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios/">
                            <i class="fas fa-user-cog me-2"></i> Gestión de Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="configuracion/">
                            <i class="fas fa-cog me-2"></i> Configuración
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reportes/">
                            <i class="fas fa-file-alt me-2"></i> Reportes
                        </a>
                    </li>
                </ul>
                
                <hr class="bg-light">
                
                <div class="text-center mt-3">
                    <a href="../../../logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Panel de Administración</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Exportar</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">Imprimir</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                        <i class="fas fa-calendar me-1"></i> Este mes
                    </button>
                </div>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <!-- Cards de estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title">Usuarios Registrados</h5>
                            <p class="card-text display-5"><?php echo $stats['total_usuarios'] ?? 0; ?></p>
                            <a href="usuarios/" class="text-white">Ver detalles</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-success h-100">
                        <div class="card-body">
                            <h5 class="card-title">Trámites Totales</h5>
                            <p class="card-text display-5"><?php echo $stats['total_tramites'] ?? 0; ?></p>
                            <a href="../tramites/" class="text-white">Ver detalles</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-info h-100">
                        <div class="card-body">
                            <h5 class="card-title">Participación Ciudadana</h5>
                            <p class="card-text display-5"><?php echo $stats['total_participacion'] ?? 0; ?></p>
                            <a href="../participacion/" class="text-white">Ver detalles</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-warning h-100">
                        <div class="card-body">
                            <h5 class="card-title">Reportes Activos</h5>
                            <p class="card-text display-5"><?php 
                                echo count(array_filter($stats['ultimos_reportes'] ?? [], function($r) {
                                    return $r['estado'] == 'recibido' || $r['estado'] == 'en_revision';
                                })); 
                            ?></p>
                            <a href="reportes/" class="text-white">Ver detalles</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Gráfico de trámites por estado -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Trámites por Estado</h5>
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="tramitesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Últimos trámites -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Últimos Trámites</h5>
                                <a href="../tramites/" class="btn btn-sm btn-outline-primary">Ver todos</a>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Trámite</th>
                                            <th>Usuario</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['ultimos_tramites'] ?? [] as $tramite): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($tramite['codigo_tramite']); ?></td>
                                                <td><?php echo htmlspecialchars($tramite['tramite_nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($tramite['usuario_nombre'] . ' ' . htmlspecialchars($tramite['usuario_apellido']); ?></td>
                                                <td>
                                                    <span class="badge 
                                                        <?php echo $tramite['estado'] == 'completado' ? 'bg-success' : 
                                                              ($tramite['estado'] == 'rechazado' ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $tramite['estado'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Últimos reportes -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Últimos Reportes</h5>
                                <a href="reportes/" class="btn btn-sm btn-outline-primary">Ver todos</a>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Usuario</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['ultimos_reportes'] ?? [] as $reporte): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(substr($reporte['titulo'], 0, 20)) . '...'; ?></td>
                                                <td><?php echo htmlspecialchars($reporte['usuario_nombre'] . ' ' . htmlspecialchars($reporte['usuario_apellido'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($reporte['fecha_reporte'])); ?></td>
                                                <td>
                                                    <span class="badge 
                                                        <?php echo $reporte['estado'] == 'resuelto' ? 'bg-success' : 
                                                              ($reporte['estado'] == 'rechazado' ? 'bg-danger' : 
                                                              ($reporte['estado'] == 'en_proceso' ? 'bg-warning text-dark' : 'bg-secondary')); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $reporte['estado'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actividad reciente -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Actividad Reciente</h5>
                            
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Nuevo trámite registrado</h6>
                                        <small>Hace 15 minutos</small>
                                    </div>
                                    <p class="mb-1">Licencia de Funcionamiento - Tienda "El Ahorro"</p>
                                    <small>Código: AMB-20230515-ABC123</small>
                                </a>
                                
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Nuevo reporte ciudadano</h6>
                                        <small>Hace 1 hora</small>
                                    </div>
                                    <p class="mb-1">Bache en Av. Progreso cuadra 3</p>
                                    <small>Reportado por: Juan Pérez</small>
                                </a>
                                
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Votación iniciada</h6>
                                        <small>Hace 3 horas</small>
                                    </div>
                                    <p class="mb-1">Presupuesto participativo 2023 - Distrito Ambo</p>
                                    <small>15 votos registrados</small>
                                </a>
                                
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Nuevo usuario registrado</h6>
                                        <small>Ayer</small>
                                    </div>
                                    <p class="mb-1">María López (DNI: 87654321)</p>
                                    <small>Distrito: San Francisco</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico de trámites por estado
    const ctx = document.getElementById('tramitesChart').getContext('2d');
    const tramitesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php 
                if (isset($stats['tramites_por_estado'])) {
                    foreach ($stats['tramites_por_estado'] as $estado) {
                        echo "'" . ucfirst(str_replace('_', ' ', $estado['estado'])) . "',";
                    }
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    if (isset($stats['tramites_por_estado'])) {
                        foreach ($stats['tramites_por_estado'] as $estado) {
                            echo $estado['cantidad'] . ",";
                        }
                    }
                    ?>
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>