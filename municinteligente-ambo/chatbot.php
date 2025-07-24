<?php
session_start();
require_once 'includes/functions.php';

// Verificar autenticación (el chatbot puede ser accesible sin login)
$user = isLoggedIn() ? getCurrentUser() : null;

// Procesar mensajes del chatbot
$messages = [];
$bot_responses = [
    'hola' => '¡Hola! Soy el asistente virtual de MunicInteligente Ambo. ¿En qué puedo ayudarte hoy?',
    'tramites' => 'Puedes realizar los siguientes trámites: Licencia de Funcionamiento, Certificado de Posesión, Pago de Arbitrios y Permisos de Construcción. ¿Cuál necesitas?',
    'licencia' => 'Para obtener una Licencia de Funcionamiento necesitas: 1. Copia de DNI, 2. Recibo de servicio público, 3. Croquis de ubicación. ¿Quieres iniciar el trámite ahora?',
    'pago' => 'Puedes realizar pagos de arbitrios en la sección "Trámites" > "Pago de Arbitrios". Necesitarás tu número de predio o código de contribuyente.',
    'contacto' => 'Puedes contactarnos al teléfono (062) 421365 o al correo contacto@ambo.gob.pe. Nuestro horario de atención es de Lunes a Viernes de 8:00 AM a 4:00 PM.',
    'gracias' => '¡De nada! ¿Hay algo más en lo que pueda ayudarte?',
    'default' => 'Lo siento, no entendí tu consulta. ¿Podrías reformularla? También puedes llamarnos al (062) 421365 para atención personalizada.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $user_message = strtolower(trim($_POST['message']));
    
    // Guardar mensaje del usuario
    $messages[] = [
        'sender' => 'user',
        'text' => $_POST['message'],
        'time' => date('H:i')
    ];
    
    // Determinar respuesta del bot
    $bot_response = $bot_responses['default'];
    
    if (strpos($user_message, 'hola') !== false || strpos($user_message, 'buenos días') !== false || strpos($user_message, 'buenas tardes') !== false) {
        $bot_response = $bot_responses['hola'];
    } elseif (strpos($user_message, 'trámite') !== false || strpos($user_message, 'tramite') !== false) {
        $bot_response = $bot_responses['tramites'];
    } elseif (strpos($user_message, 'licencia') !== false || strpos($user_message, 'funcionamiento') !== false) {
        $bot_response = $bot_responses['licencia'];
    } elseif (strpos($user_message, 'pago') !== false || strpos($user_message, 'arbitrio') !== false) {
        $bot_response = $bot_responses['pago'];
    } elseif (strpos($user_message, 'contacto') !== false || strpos($user_message, 'teléfono') !== false || strpos($user_message, 'telefono') !== false) {
        $bot_response = $bot_responses['contacto'];
    } elseif (strpos($user_message, 'gracias') !== false) {
        $bot_response = $bot_responses['gracias'];
    }
    
    // Guardar respuesta del bot
    $messages[] = [
        'sender' => 'bot',
        'text' => $bot_response,
        'time' => date('H:i')
    ];
    
    // Guardar historial en sesión
    $_SESSION['chat_messages'] = $messages;
}

// Obtener historial de mensajes si existe
if (isset($_SESSION['chat_messages'])) {
    $messages = $_SESSION['chat_messages'];
}

// Mostrar interfaz del chatbot
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot - MunicInteligente Ambo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .chat-container {
            width: 350px;
            height: 500px;
            position: fixed;
            bottom: 80px;
            right: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            background-color: white;
            z-index: 1000;
        }
        
        .chat-header {
            background-color: #1A56DB;
            color: white;
            padding: 15px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-body {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        
        .chat-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            background-color: white;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 80%;
        }
        
        .user-message {
            margin-left: auto;
            background-color: #1A56DB;
            color: white;
            padding: 10px 15px;
            border-radius: 18px 18px 0 18px;
        }
        
        .bot-message {
            margin-right: auto;
            background-color: #e9ecef;
            color: #212529;
            padding: 10px 15px;
            border-radius: 18px 18px 18px 0;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 5px;
            text-align: right;
        }
        
        .chat-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #1A56DB;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 999;
        }
        
        .close-chat {
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php if (!isset($_GET['embed'])): ?>
        <button id="chatBtn" class="chat-btn">
            <i class="fas fa-robot"></i>
        </button>
    <?php endif; ?>
    
    <div id="chatContainer" class="chat-container" style="<?php echo isset($_GET['embed']) ? 'position:relative;bottom:auto;right:auto;width:100%;height:400px;' : 'display:none;' ?>">
        <div class="chat-header">
            <div>
                <strong>Asistente Virtual</strong>
                <div class="small">MunicInteligente Ambo</div>
            </div>
            <button class="close-chat" id="closeChat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="chat-body" id="chatBody">
            <?php if (empty($messages)): ?>
                <div class="text-center text-muted mt-3">
                    <p>¡Hola! Soy el asistente virtual de MunicInteligente Ambo. ¿En qué puedo ayudarte hoy?</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                        <button class="btn btn-sm btn-outline-primary quick-question" data-question="¿Qué trámites puedo realizar?">Trámites</button>
                        <button class="btn btn-sm btn-outline-primary quick-question" data-question="¿Cómo pago mis arbitrios?">Pagos</button>
                        <button class="btn btn-sm btn-outline-primary quick-question" data-question="¿Cuál es su horario de atención?">Contacto</button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender'] == 'user' ? 'user-message' : 'bot-message'; ?>">
                        <?php echo htmlspecialchars($msg['text']); ?>
                        <div class="message-time"><?php echo $msg['time']; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="chat-footer">
            <form id="chatForm" method="POST">
                <div class="input-group">
                    <input type="text" class="form-control" name="message" placeholder="Escribe tu mensaje..." required>
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar chat
        const chatBtn = document.getElementById('chatBtn');
        const chatContainer = document.getElementById('chatContainer');
        const closeChat = document.getElementById('closeChat');
        
        if (chatBtn && chatContainer && closeChat) {
            chatBtn.addEventListener('click', () => {
                chatContainer.style.display = 'flex';
                chatBtn.style.display = 'none';
                scrollToBottom();
            });
            
            closeChat.addEventListener('click', () => {
                chatContainer.style.display = 'none';
                chatBtn.style.display = 'flex';
            });
        }
        
        // Preguntas rápidas
        const quickQuestions = document.querySelectorAll('.quick-question');
        quickQuestions.forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelector('input[name="message"]').value = btn.dataset.question;
                document.getElementById('chatForm').submit();
            });
        });
        
        // Auto-scroll al final del chat
        function scrollToBottom() {
            const chatBody = document.getElementById('chatBody');
            chatBody.scrollTop = chatBody.scrollHeight;
        }
        
        // Scroll al cargar la página si hay mensajes
        window.addEventListener('load', scrollToBottom);
    </script>
</body>
</html>