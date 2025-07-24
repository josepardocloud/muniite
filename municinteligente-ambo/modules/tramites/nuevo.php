<?php
session_start();
require_once '../../../includes/functions.php';

// Verificar autenticación y rol
checkRole(['ciudadano', 'funcionario', 'admin']);

$user = getCurrentUser();

// Obtener trámite específico si viene con ID
$tramite_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tramite = null;
$requisitos = [];

if ($tramite_id > 0) {
    try {
        // Obtener información del trámite
        $sql = "SELECT * FROM tramites WHERE id = :id AND activo = TRUE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $tramite_id]);
        $tramite = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tramite) {
            $_SESSION['error'] = 'El trámite solicitado no está disponible.';
            header('Location: ../');
            exit();
        }
        
        // Obtener requisitos del trámite (en un sistema real esto vendría de otra tabla)
        $requisitos = [
            ['id' => 1, 'descripcion' => 'Copia de DNI', 'obligatorio' => true],
            ['id' => 2, 'descripcion' => 'Recibo de servicio público', 'obligatorio' => true],
            ['id' => 3, 'descripcion' => 'Croquis de ubicación', 'obligatorio' => $tramite['nombre'] == 'Licencias de Funcionamiento'],
            // Más requisitos según el tipo de trámite
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener trámite: " . $e->getMessage());
        $_SESSION['error'] = 'Error al cargar la información del trámite.';
        header('Location: ../');
        exit();
    }
}

// Procesar formulario de nuevo trámite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tramite'])) {
    $tramite_id = (int)$_POST['tramite_id'];
    $datos_adicionales = $_POST['datos_adicionales'] ?? [];
    $documentos_subidos = [];
    
    // Validar que el trámite existe y está activo
    try {
        $sql = "SELECT id, nombre, costo FROM tramites WHERE id = :id AND activo = TRUE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $tramite_id]);
        $tramite = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tramite) {
            throw new Exception('Trámite no válido.');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: nuevo.php?id=$tramite_id");
        exit();
    }
    
    // Procesar documentos subidos
    $upload_dir = '../../../uploads/documentos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    foreach ($_FILES['documentos'] as $key => $values) {
        foreach ($values as $index => $value) {
            $documentos[$index][$key] = $value;
        }
    }
    
    foreach ($documentos as $doc) {
        if ($doc['error'] === UPLOAD_ERR_OK) {
            $file_ext = pathinfo($doc['name'], PATHINFO_EXTENSION);
            $file_name = "doc_" . uniqid() . ".$file_ext";
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($doc['tmp_name'], $file_path)) {
                $documentos_subidos[] = [
                    'tipo' => $_POST['tipo_documento'][$index] ?? 'general',
                    'nombre' => $doc['name'],
                    'ruta' => $file_path,
                    'hash' => md5_file($file_path)
                ];
            }
        }
    }
    
    // Verificar requisitos obligatorios (simplificado)
    $requisitos_obligatorios = array_filter($requisitos, function($req) {
        return $req['obligatorio'];
    });
    
    if (count($documentos_subidos) < count($requisitos_obligatorios)) {
        $_SESSION['error'] = 'Debes subir todos los documentos obligatorios.';
        header("Location: nuevo.php?id=$tramite_id");
        exit();
    }
    
    // Crear la solicitud en la base de datos
    try {
        $pdo->beginTransaction();
        
        // Insertar solicitud
        $codigo_tramite = 'AMB-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        $sql = "INSERT INTO solicitudes (codigo_tramite, usuario_id, tramite_id, datos_json, hash_blockchain) 
                VALUES (:codigo, :user_id, :tramite_id, :datos, :hash)";
        
        $datos_json = json_encode([
            'datos_adicionales' => $datos_adicionales,
            'documentos' => array_map(function($doc) {
                return ['tipo' => $doc['tipo'], 'nombre' => $doc['nombre']];
            }, $documentos_subidos)
        ]);
        
        $hash_blockchain = generateBlockchainHash([
            'usuario_id' => $user['id'],
            'tramite_id' => $tramite_id,
            'fecha' => date('Y-m-d H:i:s'),
            'datos' => $datos_json
        ]);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':codigo' => $codigo_tramite,
            ':user_id' => $user['id'],
            ':tramite_id' => $tramite_id,
            ':datos' => $datos_json,
            ':hash' => $hash_blockchain
        ]);
        
        $solicitud_id = $pdo->lastInsertId();
        
        // Insertar documentos
        foreach ($documentos_subidos as $doc) {
            $sql = "INSERT INTO documentos (solicitud_id, tipo_documento, nombre_archivo, ruta_archivo, hash_verificacion) 
                    VALUES (:solicitud_id, :tipo, :nombre, :ruta, :hash)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':solicitud_id' => $solicitud_id,
                ':tipo' => $doc['tipo'],
                ':nombre' => $doc['nombre'],
                ':ruta' => $doc['ruta'],
                ':hash' => $doc['hash']
            ]);
        }
        
        // Si el trámite requiere pago, redirigir a la página de pago
        if ($tramite['costo'] > 0) {
            $pdo->commit();
            $_SESSION['solicitud_pago'] = $solicitud_id;
            header("Location: ../pagos/nuevo.php?solicitud_id=$solicitud_id");
            exit();
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = 'Tu trámite ha sido registrado correctamente. Código: ' . $codigo_tramite;
        logAction('nuevo_tramite', 'solicitudes', $solicitud_id);
        header("Location: ../detalle.php?id=$solicitud_id");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Eliminar archivos subidos en caso de error
        foreach ($documentos_subidos as $doc) {
            if (file_exists($doc['ruta'])) {
                unlink($doc['ruta']);
            }
        }
        
        error_log("Error al registrar trámite: " . $e->getMessage());
        $_SESSION['error'] = 'Error al registrar el trámite. Por favor, inténtelo nuevamente.';
        header("Location: nuevo.php?id=$tramite_id");
        exit();
    }
}

// Mostrar página
require_once '../../../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../../dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="../">Trámites</a></li>
            <li class="breadcrumb-item active" aria-current="page">Nuevo Trámite</li>
        </ol>
    </nav>
    
    <h2 class="mb-4"><?php echo $tramite ? htmlspecialchars($tramite['nombre']) : 'Seleccionar Trámite'; ?></h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (!$tramite): ?>
        <!-- Listado de trámites disponibles -->
        <div class="row">
            <?php 
            $tramites = getAvailableProcedures();
            foreach ($tramites as $t): 
            ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($t['nombre']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($t['descripcion']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($t['costo'] > 0): ?>
                                        <span class="badge bg-info">Costo: S/ <?php echo number_format($t['costo'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Gratuito</span>
                                    <?php endif; ?>
                                    <span class="badge bg-secondary ms-1"><?php echo $t['duracion_estimada']; ?> días</span>
                                </div>
                                <a href="nuevo.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-primary">Seleccionar</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Formulario para el trámite específico -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="nuevo.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="tramite_id" value="<?php echo $tramite['id']; ?>">
                    
                    <div class="mb-4">
                        <h4 class="mb-3">Requisitos</h4>
                        <ul class="list-group">
                            <?php foreach ($requisitos as $req): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($req['descripcion']); ?>
                                    <?php if ($req['obligatorio']): ?>
                                        <span class="badge bg-danger">Obligatorio</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Opcional</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="mb-3">Documentos</h4>
                        <p>Sube los documentos requeridos en formato PDF o imagen (JPG, PNG). Tamaño máximo por archivo: 5MB.</p>
                        
                        <div id="documentos-container">
                            <?php foreach ($requisitos as $index => $req): ?>
                                <div class="mb-3 documento-item">
                                    <label class="form-label">
                                        <?php echo htmlspecialchars($req['descripcion']); ?>
                                        <?php if ($req['obligatorio']): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="hidden" name="tipo_documento[]" value="<?php echo htmlspecialchars($req['descripcion']); ?>">
                                    <input type="file" name="documentos[]" class="form-control" <?php echo $req['obligatorio'] ? 'required' : ''; ?> accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="form-text"><?php echo $req['obligatorio'] ? 'Documento obligatorio' : 'Documento opcional'; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="mb-3">Información Adicional</h4>
                        
                        <?php if ($tramite['nombre'] == 'Licencias de Funcionamiento'): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nombre_comercio" class="form-label">Nombre del Comercio *</label>
                                    <input type="text" class="form-control" id="nombre_comercio" name="datos_adicionales[nombre_comercio]" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="actividad" class="form-label">Actividad Económica *</label>
                                    <select class="form-select" id="actividad" name="datos_adicionales[actividad]" required>
                                        <option value="">Seleccione una actividad</option>
                                        <option value="restaurante">Restaurante</option>
                                        <option value="tienda">Tienda de Abarrotes</option>
                                        <option value="servicios">Servicios</option>
                                        <option value="otros">Otros</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="direccion_comercio" class="form-label">Dirección del Comercio *</label>
                                    <input type="text" class="form-control" id="direccion_comercio" name="datos_adicionales[direccion_comercio]" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="area_comercio" class="form-label">Área del Local (m²) *</label>
                                    <input type="number" class="form-control" id="area_comercio" name="datos_adicionales[area_comercio]" required>
                                </div>
                            </div>
                            
                        <?php elseif ($tramite['nombre'] == 'Certificados de Posesión'): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="direccion_predio" class="form-label">Dirección del Predio *</label>
                                    <input type="text" class="form-control" id="direccion_predio" name="datos_adicionales[direccion_predio]" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="area_predio" class="form-label">Área del Predio (m²) *</label>
                                    <input type="number" class="form-control" id="area_predio" name="datos_adicionales[area_predio]" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="lindero_norte" class="form-label">Lindero Norte</label>
                                <input type="text" class="form-control" id="lindero_norte" name="datos_adicionales[lindero_norte]">
                            </div>
                            
                            <div class="mb-3">
                                <label for="lindero_sur" class="form-label">Lindero Sur</label>
                                <input type="text" class="form-control" id="lindero_sur" name="datos_adicionales[lindero_sur]">
                            </div>
                            
                        <?php else: ?>
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="datos_adicionales[observaciones]" rows="3"></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="nuevo.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" name="submit_tramite" class="btn btn-primary">Enviar Solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../../includes/footer.php'; ?>