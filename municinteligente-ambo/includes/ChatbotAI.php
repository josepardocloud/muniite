<?php
class ChatbotAI {
    private $intents = [];
    
    public function __construct() {
        // Cargar intenciones básicas (en un sistema real esto vendría de una base de datos)
        $this->intents = [
            'saludo' => [
                'patterns' => ['hola', 'buenos días', 'buenas tardes', 'buenas noches'],
                'responses' => [
                    '¡Hola! Soy el asistente virtual de MunicInteligente Ambo. ¿En qué puedo ayudarte hoy?',
                    '¡Buen día! ¿Cómo puedo ayudarte con tus trámites municipales?'
                ]
            ],
            'tramites' => [
                'patterns' => ['qué trámites', 'qué tramites', 'tipos de trámites', 'quiero hacer un trámite'],
                'responses' => [
                    'Puedes realizar varios trámites con nosotros: Licencias de Funcionamiento, Certificados de Posesión, Pago de Arbitrios y Permisos de Construcción. ¿Cuál te interesa?',
                    'Los trámites más solicitados son: Licencia de Funcionamiento para comercios, Certificado de Posesión para propiedades, y Pago de Arbitrios. ¿Necesitas información sobre alguno en particular?'
                ]
            ],
            'licencia_funcionamiento' => [
                'patterns' => ['licencia de funcionamiento', 'abrir un negocio', 'permiso para comercio'],
                'responses' => [
                    'Para obtener una Licencia de Funcionamiento necesitas: 1. Copia de DNI, 2. Recibo de servicio público, 3. Croquis de ubicación. El costo es de S/ 150 y tarda aproximadamente 5 días hábiles. ¿Quieres iniciar el trámite ahora?',
                    'La Licencia de Funcionamiento es obligatoria para operar cualquier negocio en Ambo. Requieres presentar documentos que acrediten la propiedad o alquiler del local, y el pago correspondiente. ¿Deseas más información?'
                ]
            ],
            'pago_arbitrios' => [
                'patterns' => ['pago de arbitrios', 'pagar arbitrios', 'impuestos municipales'],
                'responses' => [
                    'Puedes pagar tus arbitrios municipales en línea ingresando tu número de predio o código de contribuyente. El pago es seguro y recibirás tu recibo electrónico al instante. ¿Quieres que te guíe en el proceso?',
                    'Los arbitrios municipales incluyen la limpieza pública, parques y jardines, y seguridad ciudadana. Puedes pagarlos en nuestra plataforma con tarjeta de crédito/débito o transferencia bancaria. ¿Necesitas ayuda para realizar el pago?'
                ]
            ],
            'contacto' => [
                'patterns' => ['contacto', 'teléfono', 'dirección', 'dónde están ubicados'],
                'responses' => [
                    'Puedes contactarnos al teléfono (062) 421365 o al correo contacto@ambo.gob.pe. Nuestra dirección es Plaza de Armas s/n, Ambo. Horario de atención: Lunes a Viernes de 8:00 AM a 4:00 PM.',
                    'Estamos ubicados en la Plaza de Armas de Ambo. Atendemos de Lunes a Viernes de 8am a 4pm. Teléfono: (062) 421365. También puedes escribirnos a contacto@ambo.gob.pe'
                ]
            ],
            'agradecimiento' => [
                'patterns' => ['gracias', 'muchas gracias', 'te agradezco', 'muy amable'],
                'responses' => [
                    '¡De nada! Estoy aquí para ayudarte. ¿Hay algo más en lo que pueda asistirte?',
                    '¡Fue un placer ayudarte! No dudes en consultarme si tienes otra pregunta sobre los servicios municipales.'
                ]
            ],
            'despedida' => [
                'patterns' => ['adiós', 'hasta luego', 'nos vemos', 'chao'],
                'responses' => [
                    '¡Hasta luego! Recuerda que estoy aquí para ayudarte con tus trámites municipales cuando lo necesites.',
                    '¡Que tengas un buen día! Si tienes más preguntas, no dudes en volver.'
                ]
            ],
            'default' => [
                'patterns' => [],
                'responses' => [
                    'Lo siento, no entendí tu consulta. ¿Podrías reformularla?',
                    'No estoy seguro de entender. ¿Podrías ser más específico?',
                    'Mi conocimiento es limitado a trámites municipales. ¿Te refieres a algún servicio en particular?'
                ]
            ]
        ];
    }
    
    // Procesar mensaje del usuario
    public function procesarMensaje($mensaje) {
        $mensaje = mb_strtolower(trim($mensaje));
        
        // Buscar intención que coincida
        foreach ($this->intents as $intent => $data) {
            foreach ($data['patterns'] as $pattern) {
                if (strpos($mensaje, $pattern) !== false) {
                    return $this->getRandomResponse($data['responses']);
                }
            }
        }
        
        // Si no encuentra coincidencia, usar respuesta default
        return $this->getRandomResponse($this->intents['default']['responses']);
    }
    
    // Obtener respuesta aleatoria de las disponibles para la intención
    private function getRandomResponse($responses) {
        return $responses[array_rand($responses)];
    }
    
    // Aprender de nuevas preguntas (simplificado)
    public function aprender($pregunta, $respuesta) {
        // En un sistema real, esto guardaría en una base de datos para análisis posterior
        // y posible incorporación a las intenciones después de validación
        
        error_log("Nuevo aprendizaje: Pregunta: $pregunta - Respuesta: $respuesta");
        return true;
    }
}
?>