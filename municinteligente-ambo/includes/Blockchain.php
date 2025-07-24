<?php
class Blockchain {
    private $pdo;
    private $nodeUrl = 'http://blockchain-node-ambo:5000'; // URL de un nodo blockchain en producción
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Registrar un trámite en la blockchain
    public function registrarTramite($tramiteData) {
        // En un sistema real, esto haría una llamada a la red blockchain
        // Para este ejemplo, simulamos la generación de un hash y lo guardamos localmente
        
        $hash = $this->generarHash($tramiteData);
        
        try {
            $stmt = $this->pdo->prepare("UPDATE solicitudes SET hash_blockchain = :hash WHERE id = :id");
            $stmt->execute([':hash' => $hash, ':id' => $tramiteData['solicitud_id']]);
            
            return $hash;
        } catch (PDOException $e) {
            error_log("Error al registrar hash blockchain: " . $e->getMessage());
            return false;
        }
    }
    
    // Verificar un trámite en la blockchain
    public function verificarTramite($solicitud_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT hash_blockchain, datos_json FROM solicitudes WHERE id = :id");
            $stmt->execute([':id' => $solicitud_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data || !$data['hash_blockchain']) {
                return false;
            }
            
            // En un sistema real, verificaríamos con la red blockchain
            // Aquí solo verificamos que el hash coincida con los datos
            $expectedHash = $this->generarHash([
                'datos_json' => $data['datos_json'],
                'solicitud_id' => $solicitud_id
            ]);
            
            return $expectedHash === $data['hash_blockchain'];
        } catch (PDOException $e) {
            error_log("Error al verificar trámite blockchain: " . $e->getMessage());
            return false;
        }
    }
    
    // Generar hash simulado para blockchain
    private function generarHash($data) {
        $jsonData = json_encode($data);
        return 'amb_' . hash('sha256', $jsonData . time() . uniqid());
    }
    
    // Registrar voto en blockchain (para presupuesto participativo)
    public function registrarVoto($participacion_id, $usuario_id, $opcion_voto) {
        $hash = $this->generarHash([
            'participacion_id' => $participacion_id,
            'usuario_id' => $usuario_id,
            'opcion_voto' => $opcion_voto,
            'timestamp' => time()
        ]);
        
        try {
            $stmt = $this->pdo->prepare("INSERT INTO votaciones (participacion_id, usuario_id, opcion_voto, hash_blockchain) 
                                        VALUES (:participacion_id, :usuario_id, :opcion_voto, :hash)");
            $stmt->execute([
                ':participacion_id' => $participacion_id,
                ':usuario_id' => $usuario_id,
                ':opcion_voto' => $opcion_voto,
                ':hash' => $hash
            ]);
            
            return $hash;
        } catch (PDOException $e) {
            error_log("Error al registrar voto blockchain: " . $e->getMessage());
            return false;
        }
    }
}
?>