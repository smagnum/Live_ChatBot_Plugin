<?php
/**
 * Messages API Endpoint
 * Handles message retrieval and sending
 */

require_once 'config.php';

// Verify authentication
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    sendError('Missing or invalid authorization header', 401);
}

$jwt = $matches[1];
$tokenData = verifyJWT($jwt);

if (!$tokenData) {
    sendError('Invalid or expired token', 401);
}

$sessionId = $tokenData['session_id'];

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        getMessages($sessionId);
        break;
    case 'POST':
        sendMessage($sessionId);
        break;
    default:
        sendError('Method not allowed', 405);
}

function getMessages($sessionId) {
    $clientIp = $_GET['client_ip'] ?? '';
    
    if (!$clientIp) {
        sendError('Missing client_ip parameter', 400);
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Verify current session is valid (for authentication)
        $stmt = $db->prepare("
            SELECT * FROM wp_connections 
            WHERE session_id = ? AND connection_status = 'active' AND expires_at > NOW()
        ");
        $stmt->execute([$sessionId]);
        $connection = $stmt->fetch();
        
        if (!$connection) {
            sendError('Session expired or invalid', 401);
        }
        
        // Find the actual session for this client IP (could be different from current session)
        $stmt = $db->prepare("
            SELECT c.session_id, c.client_name, c.email, c.status 
            FROM clients c
            INNER JOIN wp_connections wc ON c.session_id = wc.session_id
            WHERE c.client_ip = ? AND wc.connection_status = 'active' AND wc.expires_at > NOW()
            ORDER BY c.last_visit DESC
            LIMIT 1
        ");
        $stmt->execute([$clientIp]);
        $client = $stmt->fetch();
        
        if (!$client) {
            sendError('Client not found', 404);
        }
        
        $clientSessionId = $client['session_id'];
        
        // Get messages for specific client using their actual session
        $stmt = $db->prepare("
            SELECT 
                id,
                sender,
                message,
                timestamp,
                is_read
            FROM messages 
            WHERE session_id = ? AND client_ip = ?
            ORDER BY timestamp ASC
        ");
        
        $stmt->execute([$clientSessionId, $clientIp]);
        $messages = $stmt->fetchAll();
        
        // Format messages
        $formattedMessages = [];
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'id' => (int)$message['id'],
                'sender' => $message['sender'],
                'message' => $message['message'],
                'timestamp' => $message['timestamp'],
                'time_formatted' => date('M j, Y g:i A', strtotime($message['timestamp'])),
                'is_read' => (bool)$message['is_read']
            ];
        }
        
        sendResponse([
            'messages' => $formattedMessages,
            'client' => [
                'ip' => $clientIp,
                'name' => $client['client_name'] ?: 'Anonymous User',
                'email' => $client['email'] ?: '',
                'status' => $client['status']
            ],
            'total_messages' => count($messages)
        ], 200, 'Messages retrieved successfully');
        
    } catch (Exception $e) {
        sendError('Failed to retrieve messages: ' . $e->getMessage(), 500);
    }
}

function sendMessage($sessionId) {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        sendError('Invalid JSON data', 400);
    }
    
    // Validate required fields
    $requiredFields = ['client_ip', 'message', 'sender'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            sendError("Missing required field: $field", 400);
        }
    }
    
    $clientIp = trim($data['client_ip']);
    $message = trim($data['message']);
    $sender = trim($data['sender']);
    
    // Validate sender
    if (!in_array($sender, ['user', 'support'])) {
        sendError('Invalid sender. Must be "user" or "support"', 400);
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Verify session
        $stmt = $db->prepare("
            SELECT * FROM wp_connections 
            WHERE session_id = ? AND connection_status = 'active' AND expires_at > NOW()
        ");
        $stmt->execute([$sessionId]);
        $connection = $stmt->fetch();
        
        if (!$connection) {
            sendError('Session expired or invalid', 401);
        }
        
        // Insert message
        $stmt = $db->prepare("
            INSERT INTO messages (session_id, client_ip, sender, message, timestamp) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$sessionId, $clientIp, $sender, $message]);
        $messageId = $db->lastInsertId();
        
        // Update client's last message and message count
        $stmt = $db->prepare("
            UPDATE clients 
            SET last_message = ?, 
                message_count = message_count + 1,
                last_visit = NOW()
            WHERE session_id = ? AND client_ip = ?
        ");
        $stmt->execute([$message, $sessionId, $clientIp]);
        
        // If this is the first message from this IP, create client record
        if ($stmt->rowCount() === 0) {
            $stmt = $db->prepare("
                INSERT INTO clients 
                (session_id, client_ip, client_name, email, status, last_message, message_count, first_visit, last_visit) 
                VALUES (?, ?, ?, ?, 'online', ?, 1, NOW(), NOW())
            ");
            $stmt->execute([
                $sessionId, 
                $clientIp, 
                $data['client_name'] ?? null,
                $data['client_email'] ?? null,
                $message
            ]);
        }
        
        sendResponse([
            'message_id' => $messageId,
            'timestamp' => date('Y-m-d H:i:s'),
            'sender' => $sender,
            'message' => $message
        ], 201, 'Message sent successfully');
        
    } catch (Exception $e) {
        sendError('Failed to send message: ' . $e->getMessage(), 500);
    }
} 