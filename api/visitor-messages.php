<?php
/**
 * Visitor Messages API Endpoint
 * Handles anonymous visitor messages (no authentication required)
 * - POST: Send visitor message
 * - GET: Poll for new messages (real-time updates)
 */

require_once 'config.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Handle GET request - Poll for new messages for visitor
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $clientIp = $_GET['client_ip'] ?? null;
    $lastMessageId = (int)($_GET['last_message_id'] ?? 0);
    
    if (!$clientIp) {
        sendError('Missing client_ip parameter', 400);
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get messages for this client IP from all active sessions
        $stmt = $db->prepare("
            SELECT m.id, m.message, m.sender, m.timestamp, m.session_id
            FROM messages m
            INNER JOIN wp_connections wc ON m.session_id = wc.session_id
            WHERE m.client_ip = ? 
            AND m.id > ?
            AND wc.connection_status = 'active'
            ORDER BY m.timestamp ASC
        ");
        $stmt->execute([$clientIp, $lastMessageId]);
        $newMessages = $stmt->fetchAll();
        
        // Get the highest message ID for next polling
        $highestId = $lastMessageId;
        if (!empty($newMessages)) {
            $highestId = max(array_column($newMessages, 'id'));
        }
        
        sendResponse([
            'messages' => $newMessages,
            'last_message_id' => $highestId,
            'client_ip' => $clientIp,
            'count' => count($newMessages)
        ], 200, 'Messages retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Visitor poll error: " . $e->getMessage());
        sendError('Failed to retrieve messages: ' . $e->getMessage(), 500);
    }
    exit;
}

// Handle POST request - Send visitor message (original functionality)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    sendError('Invalid JSON data', 400);
}

// Validate required fields
$requiredFields = ['client_ip', 'message'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        sendError("Missing required field: $field", 400);
    }
}

$clientIp = trim($data['client_ip']);
$message = trim($data['message']);
$clientName = isset($data['client_name']) ? trim($data['client_name']) : null;
$clientEmail = isset($data['client_email']) ? trim($data['client_email']) : null;

// Basic validation
if (strlen($message) > 1000) {
    sendError('Message too long (max 1000 characters)', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Find an active support session to attach the visitor message to
    // If no active session, we'll create a default one
    $stmt = $db->prepare("
        SELECT session_id FROM wp_connections 
        WHERE connection_status = 'active' AND expires_at > NOW()
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $activeConnection = $stmt->fetch();
    
    $sessionId = null;
    
    if ($activeConnection) {
        $sessionId = $activeConnection['session_id'];
    } else {
        // Create a default visitor session if no support session is active
        $sessionId = 'visitor_' . uniqid();
        
        // Create a visitor connection record
        $stmt = $db->prepare("
            INSERT INTO wp_connections 
            (session_id, wp_url, wp_username, connection_status, created_at, expires_at) 
            VALUES (?, 'https://masbantech.com', 'visitor_messages', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ");
        $stmt->execute([$sessionId]);
    }
    
    // Insert the visitor message
    $stmt = $db->prepare("
        INSERT INTO messages (session_id, client_ip, sender, message, timestamp) 
        VALUES (?, ?, 'user', ?, NOW())
    ");
    $stmt->execute([$sessionId, $clientIp, $message]);
    $messageId = $db->lastInsertId();
    
    // Update or create client record
    $stmt = $db->prepare("
        SELECT id FROM clients 
        WHERE session_id = ? AND client_ip = ?
    ");
    $stmt->execute([$sessionId, $clientIp]);
    $existingClient = $stmt->fetch();
    
    if ($existingClient) {
        // Update existing client
        $stmt = $db->prepare("
            UPDATE clients 
            SET last_message = ?, 
                message_count = message_count + 1,
                last_visit = NOW(),
                status = 'online'
            WHERE session_id = ? AND client_ip = ?
        ");
        $stmt->execute([$message, $sessionId, $clientIp]);
    } else {
        // Create new client record
        $stmt = $db->prepare("
            INSERT INTO clients 
            (session_id, client_ip, client_name, email, status, last_message, message_count, first_visit, last_visit) 
            VALUES (?, ?, ?, ?, 'online', ?, 1, NOW(), NOW())
        ");
        $stmt->execute([
            $sessionId, 
            $clientIp, 
            $clientName ?: 'Anonymous Visitor',
            $clientEmail,
            $message
        ]);
    }
    
    // Log the visitor message for debugging
    error_log("Visitor message received: IP=$clientIp, Message=$message, Session=$sessionId");
    
    sendResponse([
        'message_id' => $messageId,
        'session_id' => $sessionId,
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'client_ip' => $clientIp,
        'status' => 'Message sent successfully - visible in support dashboard'
    ], 201, 'Visitor message sent successfully');
    
} catch (Exception $e) {
    error_log("Visitor message error: " . $e->getMessage());
    sendError('Failed to send message: ' . $e->getMessage(), 500);
}
?> 