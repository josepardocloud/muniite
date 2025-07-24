<?php
session_start();
require_once '../../includes/functions.php';

// Verificar autenticación (acceso público, pero con funciones adicionales para usuarios logueados)
$user = isLoggedIn() ? getCurrentUser() : null;

// Obtener datos de transparencia
$datos_transparencia = [];
try {
    $sql = "SELECT * FROM transparencia WHERE visible = TRUE ORDER BY fecha_publicacion DESC";
    $stmt = $pdo->query($sql);
    $datos_transparencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener datos de transparencia: " . $e->getMessage());
}

// Mostrar página
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Transparencia</li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Portal de Transparencia</h2>
        <?php if ($user && ($user['rol'] == 'admin' || $user['rol'] == 'funcionario')): ?>
            <a href="nuevo.php" class="btn btn-primary">Nuevo Registro</a>
        <?php endif; ?>
    </div>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="card-title mb-4">Datos Clave de la Gestión Municipal</h4>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">Presupuesto Anual</h5>
                            <p class="card-text display-6">S/ 12,450,000</p>
                            <a href="#" class="text-white">Ver detalle</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">Ejecución Presupuestal</h5>
                            <p class="card-text display-6">78%</p>
                            <a href="#" class="text-white">Ver detalle</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">Obras en Ejecución</h5>
                            <p class="card-text display-6">24</p>
                            <a href="#" class="text-white">Ver mapa</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h5 class="card-title">Contratos Vigentes</h5>
                            <p class="card-text display-6">56</p>
                            <a href="#" class="text-dark">Ver listado</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4" id="transparenciaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="contratos-tab" data-bs-toggle="tab" data-bs-target="#contratos" type="button" role="tab" aria-controls="contratos" aria-selected="true">
                        Contratos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="presupuesto-tab" data-bs-toggle="tab" data-bs-target="#presupuesto" type="button" role="tab" aria-controls="presupuesto" aria-selected="false">
                        Presupuesto
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="obras-tab" data-bs-toggle="tab" data-bs-target="#obras" type="button" role="tab" aria-controls="obras" aria-selected="false">
                        Obras Públicas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="adquisiciones-tab" data-bs-toggle="tab" data-bs-target="#adquisiciones" type="button" role="tab" aria-controls="adquisiciones" aria-selected="false">
                        Adquisiciones
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="transparenciaTabContent">
                <div class="tab-pane fade show active" id="contratos" role="tabpanel" aria-labelledby="contratos-tab">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>N° Contrato</th>
                                    <th>Objeto</th>
                                    <th>Proveedor</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Documento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $contratos = array_filter($datos_transparencia, function($item) {
                                    return $item['tipo'] == 'contrato';
                                });
                                
                                if (empty($contratos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay contratos publicados</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($contratos as $cont): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cont['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($cont['descripcion'], 0, 50)) . '...'; ?></td>
                                            <td><?php echo htmlspecialchars($cont['descripcion']); ?></td>
                                            <td>S/ <?php echo number_format($cont['monto'], 2); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($cont['fecha_evento'])); ?></td>
                                            <td>
                                                <?php if ($cont['documento_path']): ?>
                                                    <a href="<?php echo htmlspecialchars($cont['documento_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="fas fa-download"></i> Descargar
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No disponible</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="presupuesto" role="tabpanel" aria-labelledby="presupuesto-tab">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="mb-3">Ejecución Presupuestal 2023</h5>
                            <div class="chart-container" style="height: 300px;">
                                <!-- Aquí iría un gráfico de ejecución presupuestal -->
                                <img src="../../assets/images/chart-placeholder.png" class="img-fluid" alt="Gráfico de ejecución presupuestal">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h5 class="mb-3">Descargas</h5>
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-file-pdf me-2"></i> Presupuesto Institucional 2023
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-file-excel me-2"></i> Ejecución Mensual (Excel)
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-file-alt me-2"></i> Memoria Anual 2022
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="obras" role="tabpanel" aria-labelledby="obras-tab">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Mapa de Obras</h5>
                                    <!-- Aquí iría un mapa con las obras -->
                                    <img src="../../assets/images/map-placeholder.png" class="img-fluid" alt="Mapa de obras">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Obra</th>
                                            <th>Ubicación</th>
                                            <th>Avance</th>
                                            <th>Inversión</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $obras = array_filter($datos_transparencia, function($item) {
                                            return $item['tipo'] == 'obra';
                                        });
                                        
                                        if (empty($obras)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No hay obras publicadas</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($obras as $obra): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($obra['titulo']); ?></td>
                                                    <td><?php echo htmlspecialchars($obra['descripcion']); ?></td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75%</div>
                                                        </div>
                                                    </td>
                                                    <td>S/ <?php echo number_format($obra['monto'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="adquisiciones" role="tabpanel" aria-labelledby="adquisiciones-tab">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Proceso</th>
                                    <th>Descripción</th>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Documentos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $adquisiciones = array_filter($datos_transparencia, function($item) {
                                    return $item['tipo'] == 'adquisicion';
                                });
                                
                                if (empty($adquisiciones)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay procesos de adquisición publicados</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($adquisiciones as $adq): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($adq['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($adq['descripcion'], 0, 50)) . '...'; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($adq['fecha_evento'])); ?></td>
                                            <td>S/ <?php echo number_format($adq['monto'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-success">Concluido</span>
                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-search"></i> Ver
                                                </a>
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