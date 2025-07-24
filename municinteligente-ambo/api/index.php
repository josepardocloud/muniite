<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

// Configurar CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Obtener el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

// Obtener la ruta solicitada
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$endpoint = array_shift($request);

// Conectar a la base de datos
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}

// Procesar la solicitud
switch ($method) {
    case 'GET':
        switch ($endpoint) {
            case 'tramites':
                // Obtener listado de trámites
                try {
                    $stmt = $pdo->query("SELECT id, nombre, descripcion, codigo, costo, duracion_estimada FROM tramites WHERE activo = TRUE");
                    $tramites = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($tramites);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al obtener trámites']);
                }
                break;
                
            case 'noticias':
                // Obtener noticias
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                try {
                    $stmt = $pdo->prepare("SELECT id, titulo, contenido, imagen_path, fecha_publicacion FROM noticias ORDER BY fecha_publicacion DESC LIMIT :limit");
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    $stmt->execute();
                    $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($noticias);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al obtener noticias']);
                }
                break;
                
            case 'participacion':
                // Obtener iniciativas de participación ciudadana
                try {
                    $stmt = $pdo->query("SELECT p.id, p.titulo, p.descripcion, p.tipo, p.fecha_creacion, p.fecha_cierre, p.estado, 
                                                COUNT(v.id) AS votos, d.nombre AS distrito_nombre
                                         FROM participacion p
                                         LEFT JOIN distritos d ON p.distrito_id = d.id
                                         LEFT JOIN votaciones v ON p.id = v.participacion_id
                                         GROUP BY p.id
                                         ORDER BY p.fecha_creacion DESC");
                    $iniciativas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($iniciativas);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al obtener iniciativas de participación']);
                }
                break;
                
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint no encontrado']);
                break;
        }
        break;
        
    case 'POST':
        // Verificar autenticación para endpoints que lo requieran
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        
        switch ($endpoint) {
            case 'login':
                // Autenticación de usuario
                $data = json_decode(file_get_contents('php://input'), true);
                $dni = $data['dni'] ?? '';
                $password = $data['password'] ?? '';
                
                try {
                    $stmt = $pdo->prepare("SELECT id, dni, nombres, apellidos, password, rol FROM usuarios WHERE dni = :dni");
                    $stmt->execute([':dni' => $dni]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && password_verify($password, $user['password'])) {
                        // Generar token JWT (simplificado para el ejemplo)
                        $payload = [
                            'user_id' => $user['id'],
                            'dni' => $user['dni'],
                            'rol' => $user['rol'],
                            'exp' => time() + (60 * 60 * 24) // Expira en 24 horas
                        ];
                        
                        // En un sistema real usaríamos una librería JWT como firebase/php-jwt
                        $token = base64_encode(json_encode($payload));
                        
                        echo json_encode([
                            'token' => $token,
                            'user' => [
                                'id' => $user['id'],
                                'dni' => $user['dni'],
                                'nombres' => $user['nombres'],
                                'apellidos' => $user['apellidos'],
                                'rol' => $user['rol']
                            ]
                        ]);
                    } else {
                        http_response_code(401);
                        echo json_encode(['error' => 'DNI o contraseña incorrectos']);
                    }
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al autenticar usuario']);
                }
                break;
                
            case 'tramites':
                // Crear nuevo trámite (requiere autenticación)
                if (!$token) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Token de autenticación requerido']);
                    break;
                }
                
                // Verificar token (simplificado)
                $payload = json_decode(base64_decode($token), true);
                if (!$payload || $payload['exp'] < time()) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Token inválido o expirado']);
                    break;
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                $tramite_id = $data['tramite_id'] ?? null;
                $usuario_id = $payload['user_id'];
                $datos_adicionales = $data['datos_adicionales'] ?? [];
                
                try {
                    // Verificar que el trámite existe
                    $stmt = $pdo->prepare("SELECT id, nombre FROM tramites WHERE id = :id AND activo = TRUE");
                    $stmt->execute([':id' => $tramite_id]);
                    $tramite = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$tramite) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Trámite no válido']);
                        break;
                    }
                    
                    // Crear solicitud
                    $codigo_tramite = 'AMB-' . date('Ymd') . '-' . strtoupper(uniqid());
                    $hash_blockchain = generateBlockchainHash([
                        'usuario_id' => $usuario_id,
                        'tramite_id' => $tramite_id,
                        'fecha' => date('Y-m-d H:i:s'),
                        'datos' => $datos_adicionales
                    ]);
                    
                    $stmt = $pdo->prepare("INSERT INTO solicitudes (codigo_tramite, usuario_id, tramite_id, datos_json, hash_blockchain) 
                                          VALUES (:codigo, :user_id, :tramite_id, :datos, :hash)");
                    $stmt->execute([
                        ':codigo' => $codigo_tramite,
                        ':user_id' => $usuario_id,
                        ':tramite_id' => $tramite_id,
                        ':datos' => json_encode($datos_adicionales),
                        ':hash' => $hash_blockchain
                    ]);
                    
                    $solicitud_id = $pdo->lastInsertId();
                    
                    echo json_encode([
                        'success' => true,
                        'codigo_tramite' => $codigo_tramite,
                        'solicitud_id' => $solicitud_id
                    ]);
                    
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al crear el trámite']);
                }
                break;
                
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint no encontrado']);
                break;
        }
        break;
        
    case 'OPTIONS':
        // Respuesta para preflight requests
        http_response_code(200);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>