<?php
session_start();
require_once '../../includes/functions.php';

// Verificar autenticación
checkRole(['ciudadano', 'funcionario', 'admin']);

$user = getCurrentUser();

// Procesar formulario de reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitizeInput($_POST['titulo']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $categoria = sanitizeInput($_POST['categoria']);
    $direccion = sanitizeInput($_POST['direccion']);
    $latitud = sanitizeInput($_POST['latitud']);
    $longitud = sanitizeInput($_POST['longitud']);
    
    // Validar datos
    $errors = [];
    
    if (empty($titulo)) {
        $errors['titulo'] = 'Debes ingresar un título para el reporte';
    }
    
    if (empty($descripcion)) {
        $errors['descripcion'] = 'Debes describir el problema';
    }
    
    if (empty($categoria)) {
        $errors['categoria'] = 'Debes seleccionar una categoría';
    }
    
    // Procesar imagen si se subió
    $imagen_path = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/reportes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $file_name = "reporte_" . uniqid() . ".$file_ext";
        $file_path = $upload_dir . $file_name;
        
        // Validar que sea una imagen
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['imagen']['type'], $allowed_types)) {
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $file_path)) {
                $imagen_path = $file_path;
            } else {
                $errors['imagen'] = 'Error al subir la imagen';
            }
        } else {
            $errors['imagen'] = 'Formato de imagen no válido. Solo se aceptan JPG, PNG o GIF';
        }
    }
    
    // Si no hay errores, guardar el reporte
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO reportes (usuario_id, titulo, descripcion, categoria, direccion, latitud, longitud, imagen_path) 
                    VALUES (:usuario_id, :titulo, :descripcion, :categoria, :direccion, :latitud, :longitud, :imagen_path)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $user['id'],
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':categoria' => $categoria,
                ':direccion' => $direccion,
                ':latitud' => $latitud ?: null,
                ':longitud' => $longitud ?: null,
                ':imagen_path' => $imagen_path
            ]);
            
            $reporte_id = $pdo->lastInsertId();
            logAction('nuevo_reporte', 'reportes', $reporte_id);
            
            $_SESSION['success'] = 'Tu reporte ha sido registrado correctamente. Número de seguimiento: RP-' . $reporte_id;
            header('Location: ../reportes/');
            exit();
            
        } catch (PDOException $e) {
            error_log("Error al guardar reporte: " . $e->getMessage());
            $errors['general'] = 'Error al registrar el reporte. Por favor, inténtelo nuevamente.';
        }
    }
}

// Mostrar página
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="../reportes/">Reportes Ciudadanos</a></li>
            <li class="breadcrumb-item active" aria-current="page">Nuevo Reporte</li>
        </ol>
    </nav>
    
    <h2 class="mb-4">Nuevo Reporte Ciudadano</h2>
    
    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="nuevo.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título del Reporte *</label>
                            <input type="text" class="form-control <?php echo isset($errors['titulo']) ? 'is-invalid' : ''; ?>" 
                                   id="titulo" name="titulo" value="<?php echo $_POST['titulo'] ?? ''; ?>" required>
                            <?php if (isset($errors['titulo'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['titulo']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría *</label>
                            <select class="form-select <?php echo isset($errors['categoria']) ? 'is-invalid' : ''; ?>" 
                                    id="categoria" name="categoria" required>
                                <option value="">Seleccione una categoría</option>
                                <option value="limpieza" <?php echo ($_POST['categoria'] ?? '') == 'limpieza' ? 'selected' : ''; ?>>Limpieza Pública</option>
                                <option value="bache" <?php echo ($_POST['categoria'] ?? '') == 'bache' ? 'selected' : ''; ?>>Baches en vía pública</option>
                                <option value="alumbrado" <?php echo ($_POST['categoria'] ?? '') == 'alumbrado' ? 'selected' : ''; ?>>Alumbrado Público</option>
                                <option value="seguridad" <?php echo ($_POST['categoria'] ?? '') == 'seguridad' ? 'selected' : ''; ?>>Seguridad Ciudadana</option>
                                <option value="arbol" <?php echo ($_POST['categoria'] ?? '') == 'arbol' ? 'selected' : ''; ?>>Árboles o ramas caídas</option>
                                <option value="agua" <?php echo ($_POST['categoria'] ?? '') == 'agua' ? 'selected' : ''; ?>>Fuga de agua</option>
                                <option value="otros" <?php echo ($_POST['categoria'] ?? '') == 'otros' ? 'selected' : ''; ?>>Otros</option>
                            </select>
                            <?php if (isset($errors['categoria'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['categoria']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción del Problema *</label>
                            <textarea class="form-control <?php echo isset($errors['descripcion']) ? 'is-invalid' : ''; ?>" 
                                      id="descripcion" name="descripcion" rows="4" required><?php echo $_POST['descripcion'] ?? ''; ?></textarea>
                            <?php if (isset($errors['descripcion'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['descripcion']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección o Referencia</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" 
                                   value="<?php echo $_POST['direccion'] ?? ''; ?>">
                            <small class="text-muted">Describe la ubicación del problema</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ubicación en Mapa</label>
                            <div id="map" style="height: 200px; width: 100%; background-color: #eee; border-radius: 5px;"></div>
                            <small class="text-muted">Haz clic en el mapa para marcar la ubicación exacta</small>
                            <input type="hidden" id="latitud" name="latitud" value="<?php echo $_POST['latitud'] ?? ''; ?>">
                            <input type="hidden" id="longitud" name="longitud" value="<?php echo $_POST['longitud'] ?? ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen del Problema</label>
                            <input type="file" class="form-control <?php echo isset($errors['imagen']) ? 'is-invalid' : ''; ?>" 
                                   id="imagen" name="imagen" accept="image/*">
                            <?php if (isset($errors['imagen'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['imagen']; ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Sube una foto que muestre claramente el problema (opcional)</small>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                    <a href="../reportes/" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Enviar Reporte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Leaflet JS para el mapa -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
    // Inicializar mapa
    const map = L.map('map').setView([-10.1300, -76.2100], 15); // Coordenadas aproximadas de Ambo
    
    // Añadir capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    let marker = null;
    
    // Manejar clic en el mapa
    map.on('click', function(e) {
        const { lat, lng } = e.latlng;
        
        // Actualizar coordenadas en el formulario
        document.getElementById('latitud').value = lat;
        document.getElementById('longitud').value = lng;
        
        // Mover o crear marcador
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map)
                .bindPopup('Ubicación del reporte')
                .openPopup();
        }
    });
    
    // Si hay coordenadas previas, colocar marcador
    const lat = document.getElementById('latitud').value;
    const lng = document.getElementById('longitud').value;
    
    if (lat && lng) {
        marker = L.marker([lat, lng]).addTo(map)
            .bindPopup('Ubicación del reporte')
            .openPopup();
    }
    
    // Opcional: Geolocalización del navegador
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const { latitude, longitude } = position.coords;
                map.setView([latitude, longitude], 16);
                
                // Si no hay marcador, colocar uno en la ubicación actual
                if (!marker) {
                    document.getElementById('latitud').value = latitude;
                    document.getElementById('longitud').value = longitude;
                    
                    marker = L.marker([latitude, longitude]).addTo(map)
                        .bindPopup('Tu ubicación actual')
                        .openPopup();
                }
            },
            function(error) {
                console.error('Error al obtener geolocalización:', error);
            }
        );
    }
</script>

<?php require_once '../../includes/footer.php'; ?>