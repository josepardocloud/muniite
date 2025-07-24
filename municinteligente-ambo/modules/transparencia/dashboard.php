<?php
session_start();
require_once '../../includes/functions.php';

// Verificar autenticación (acceso público)
$user = isLoggedIn() ? getCurrentUser() : null;

// Obtener datos para el dashboard
$presupuestoData = [];
$obrasData = [];
$contratosData = [];

try {
    // Datos de ejecución presupuestal
    $sql = "SELECT 
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) AS ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) AS gastos,
                (SELECT SUM(monto) FROM transparencia WHERE tipo = 'presupuesto' AND YEAR(fecha_evento) = YEAR(CURDATE())) AS presupuesto_anual
            FROM transparencia
            WHERE tipo IN ('ingreso', 'gasto') AND YEAR(fecha_evento) = YEAR(CURDATE())";
    
    $stmt = $pdo->query($sql);
    $presupuestoData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Datos de obras públicas
    $sql = "SELECT 
                COUNT(*) AS total_obras,
                SUM(CASE WHEN estado = 'en_ejecucion' THEN 1 ELSE 0 END) AS en_ejecucion,
                SUM(CASE WHEN estado = 'concluido' THEN 1 ELSE 0 END) AS concluidas,
                SUM(monto) AS inversion_total
            FROM (
                SELECT 
                    id,
                    titulo,
                    monto,
                    CASE 
                        WHEN fecha_evento > CURDATE() THEN 'en_ejecucion'
                        ELSE 'concluido'
                    END AS estado
                FROM transparencia
                WHERE tipo = 'obra' AND YEAR(fecha_evento) = YEAR(CURDATE())
            ) AS obras";
    
    $stmt = $pdo->query($sql);
    $obrasData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Datos de contratos
    $sql = "SELECT 
                COUNT(*) AS total_contratos,
                SUM(monto) AS monto_total,
                AVG(monto) AS monto_promedio
            FROM transparencia
            WHERE tipo = 'contrato' AND YEAR(fecha_evento) = YEAR(CURDATE())";
    
    $stmt = $pdo->query($sql);
    $contratosData = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error al obtener datos de transparencia: " . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar los datos del dashboard.';
}

// Mostrar página
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="../transparencia/">Transparencia</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard de Transparencia</h2>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                Año <?php echo date('Y'); ?>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <li><a class="dropdown-item" href="#">2023</a></li>
                <li><a class="dropdown-item" href="#">2022</a></li>
                <li><a class="dropdown-item" href="#">2021</a></li>
            </ul>
        </div>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Ejecución Presupuestal</h5>
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="presupuestoChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span>Presupuesto:</span>
                            <strong>S/ <?php echo number_format($presupuestoData['presupuesto_anual'] ?? 0, 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Ingresos:</span>
                            <strong>S/ <?php echo number_format($presupuestoData['ingresos'] ?? 0, 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Gastos:</span>
                            <strong>S/ <?php echo number_format($presupuestoData['gastos'] ?? 0, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Obras Públicas</h5>
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="obrasChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Obras:</span>
                            <strong><?php echo $obrasData['total_obras'] ?? 0; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>En Ejecución:</span>
                            <strong><?php echo $obrasData['en_ejecucion'] ?? 0; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Inversión Total:</span>
                            <strong>S/ <?php echo number_format($obrasData['inversion_total'] ?? 0, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Contrataciones</h5>
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="contratosChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Contratos:</span>
                            <strong><?php echo $contratosData['total_contratos'] ?? 0; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Monto Total:</span>
                            <strong>S/ <?php echo number_format($contratosData['monto_total'] ?? 0, 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Monto Promedio:</span>
                            <strong>S/ <?php echo number_format($contratosData['monto_promedio'] ?? 0, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Últimos Contratos</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Contrato</th>
                                    <th>Proveedor</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $sql = "SELECT titulo, descripcion, monto, fecha_evento 
                                            FROM transparencia 
                                            WHERE tipo = 'contrato' 
                                            ORDER BY fecha_evento DESC 
                                            LIMIT 5";
                                    
                                    $stmt = $pdo->query($sql);
                                    $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($contratos as $contrato): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($contrato['titulo'], 0, 20)) . '...'; ?></td>
                                            <td><?php echo htmlspecialchars(substr($contrato['descripcion'], 0, 15)) . '...'; ?></td>
                                            <td>S/ <?php echo number_format($contrato['monto'], 2); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($contrato['fecha_evento'])); ?></td>
                                        </tr>
                                    <?php endforeach;
                                } catch (PDOException $e) {
                                    error_log("Error al obtener últimos contratos: " . $e->getMessage());
                                    echo '<tr><td colspan="4" class="text-center text-muted">Error al cargar los datos</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-2">
                        <a href="contratos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Últimas Adquisiciones</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Proceso</th>
                                    <th>Descripción</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $sql = "SELECT titulo, descripcion, monto, fecha_evento 
                                            FROM transparencia 
                                            WHERE tipo = 'adquisicion' 
                                            ORDER BY fecha_evento DESC 
                                            LIMIT 5";
                                    
                                    $stmt = $pdo->query($sql);
                                    $adquisiciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($adquisiciones as $adq): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($adq['titulo'], 0, 20)) . '...'; ?></td>
                                            <td><?php echo htmlspecialchars(substr($adq['descripcion'], 0, 15)) . '...'; ?></td>
                                            <td>S/ <?php echo number_format($adq['monto'], 2); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($adq['fecha_evento'])); ?></td>
                                        </tr>
                                    <?php endforeach;
                                } catch (PDOException $e) {
                                    error_log("Error al obtener últimas adquisiciones: " . $e->getMessage());
                                    echo '<tr><td colspan="4" class="text-center text-muted">Error al cargar los datos</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-2">
                        <a href="adquisiciones.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico de ejecución presupuestal
    const presupuestoCtx = document.getElementById('presupuestoChart').getContext('2d');
    const presupuestoChart = new Chart(presupuestoCtx, {
        type: 'doughnut',
        data: {
            labels: ['Ejecutado', 'Pendiente'],
            datasets: [{
                data: [
                    <?php echo ($presupuestoData['gastos'] ?? 0) / ($presupuestoData['presupuesto_anual'] ?? 1) * 100; ?>, 
                    <?php echo 100 - (($presupuestoData['gastos'] ?? 0) / ($presupuestoData['presupuesto_anual'] ?? 1) * 100); ?>
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(220, 220, 220, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(220, 220, 220, 1)'
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
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw.toFixed(2) + '%';
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de obras públicas
    const obrasCtx = document.getElementById('obrasChart').getContext('2d');
    const obrasChart = new Chart(obrasCtx, {
        type: 'bar',
        data: {
            labels: ['En Ejecución', 'Concluidas'],
            datasets: [{
                label: 'Obras',
                data: [
                    <?php echo $obrasData['en_ejecucion'] ?? 0; ?>, 
                    <?php echo $obrasData['concluidas'] ?? 0; ?>
                ],
                backgroundColor: [
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(75, 192, 192, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 159, 64, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // Gráfico de contratos
    const contratosCtx = document.getElementById('contratosChart').getContext('2d');
    const contratosChart = new Chart(contratosCtx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [{
                label: 'Contratos por mes',
                data: [12, 19, 8, 15, 10, 14, 18, 12, 9, 11, 7, 5], // Datos simulados
                fill: false,
                backgroundColor: 'rgba(153, 102, 255, 0.7)',
                borderColor: 'rgba(153, 102, 255, 1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>