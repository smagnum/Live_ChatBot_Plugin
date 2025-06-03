<?php
/**
 * WordPress Connection API Endpoint
 * Handles WordPress credential validation and session management
 */

require_once 'config.php';

// Only allow POST requests
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
$requiredFields = ['wpUrl', 'wpUsername', 'wpPassword'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        sendError("Missing required field: $field", 400);
    }
}

$wpUrl = trim($data['wpUrl']);
$wpUsername = trim($data['wpUsername']);
$wpPassword = $data['wpPassword'];

// Validate WordPress connection
$validation = WordPressValidator::validateConnection($wpUrl, $wpUsername, $wpPassword);

if (!$validation['valid']) {
    sendError($validation['error'], 401);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Generate session
    $sessionId = generateSessionId();
    $passwordHash = hashPassword($wpPassword);
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
    
    // Store connection in database
    $stmt = $db->prepare("
        INSERT INTO wp_connections 
        (session_id, wp_url, wp_username, wp_password_hash, expires_at) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $sessionId,
        $wpUrl,
        $wpUsername,
        $passwordHash,
        $expiresAt
    ]);
    
    // Generate JWT token
    $tokenData = [
        'session_id' => $sessionId,
        'wp_url' => $wpUrl,
        'wp_username' => $wpUsername,
        'user_data' => $validation['user_data'] ?? []
    ];
    
    $jwt = generateJWT($tokenData);
    
    // Create sample client data for demonstration
    createSampleData($sessionId);
    
    sendResponse([
        'token' => $jwt,
        'session_id' => $sessionId,
        'expires_at' => $expiresAt,
        'wordpress_site' => [
            'url' => $wpUrl,
            'username' => $wpUsername,
            'user_data' => $validation['user_data'] ?? []
        ]
    ], 200, 'Connection established successfully');
    
} catch (Exception $e) {
    sendError('Failed to establish connection: ' . $e->getMessage(), 500);
}

// Create sample data for demonstration
function createSampleData($sessionId) {
    $db = Database::getInstance()->getConnection();
    
    // Sample client IPs and data
    $sampleClients = [
        [
            'ip' => '192.168.1.101',
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'status' => 'online',
            'messages' => [
                ['sender' => 'user', 'message' => 'Hello, I need help with my WordPress site.', 'timestamp' => date('Y-m-d H:i:s', time() - 3600)],
                ['sender' => 'support', 'message' => 'Hi John! I\'d be happy to help you. What specific issue are you experiencing?', 'timestamp' => date('Y-m-d H:i:s', time() - 3550)],
                ['sender' => 'user', 'message' => 'My website is loading very slowly and I\'m getting some plugin errors.', 'timestamp' => date('Y-m-d H:i:s', time() - 3500)],
                ['sender' => 'support', 'message' => 'I can help you troubleshoot that. Let me check your site\'s performance and plugins.', 'timestamp' => date('Y-m-d H:i:s', time() - 3450)]
            ]
        ],
        [
            'ip' => '10.0.0.25',
            'name' => 'Sarah Johnson',
            'email' => 'sarah.j@company.com',
            'status' => 'offline',
            'messages' => [
                ['sender' => 'user', 'message' => 'Is there a way to backup my entire WordPress site?', 'timestamp' => date('Y-m-d H:i:s', time() - 7200)],
                ['sender' => 'support', 'message' => 'Absolutely! There are several ways to backup your WordPress site. I recommend using a plugin like UpdraftPlus or BackupBuddy.', 'timestamp' => date('Y-m-d H:i:s', time() - 7150)]
            ]
        ],
        [
            'ip' => '172.16.0.48',
            'name' => 'Mike Chen',
            'email' => 'mike.chen@email.com',
            'status' => 'online',
            'messages' => [
                ['sender' => 'user', 'message' => 'Can you help me set up SSL certificate for my site?', 'timestamp' => date('Y-m-d H:i:s', time() - 1800)],
                ['sender' => 'support', 'message' => 'Of course! SSL is important for security. Are you using shared hosting or do you have a VPS/dedicated server?', 'timestamp' => date('Y-m-d H:i:s', time() - 1750)],
                ['sender' => 'user', 'message' => 'I\'m using shared hosting with cPanel.', 'timestamp' => date('Y-m-d H:i:s', time() - 1700)]
            ]
        ],
        [
            'ip' => '203.0.113.15',
            'name' => 'Emily Davis',
            'email' => 'emily@design.co',
            'status' => 'offline',
            'messages' => [
                ['sender' => 'user', 'message' => 'My theme is not displaying correctly after the last update. Can you help?', 'timestamp' => date('Y-m-d H:i:s', time() - 14400)],
                ['sender' => 'support', 'message' => 'I can definitely help with theme issues. Can you tell me which theme you\'re using and what specific problems you\'re seeing?', 'timestamp' => date('Y-m-d H:i:s', time() - 14350)],
                ['sender' => 'user', 'message' => 'I\'m using the Astra theme and the header layout is completely broken.', 'timestamp' => date('Y-m-d H:i:s', time() - 14300)],
                ['sender' => 'support', 'message' => 'That sounds like it might be a CSS cache issue or a conflict with a plugin. Let me walk you through some troubleshooting steps.', 'timestamp' => date('Y-m-d H:i:s', time() - 14250)]
            ]
        ],
        [
            'ip' => '198.51.100.73',
            'name' => 'David Wilson',
            'email' => 'david@startup.io',
            'status' => 'online',
            'messages' => [
                ['sender' => 'user', 'message' => 'I need to migrate my site to a new hosting provider. What\'s the best approach?', 'timestamp' => date('Y-m-d H:i:s', time() - 600)],
                ['sender' => 'support', 'message' => 'Site migration can be tricky, but I\'ll guide you through it step by step. First, let\'s make sure you have a full backup of your current site.', 'timestamp' => date('Y-m-d H:i:s', time() - 550)]
            ]
        ]
    ];
    
    // Insert sample clients and messages
    foreach ($sampleClients as $client) {
        // Insert client
        $stmt = $db->prepare("
            INSERT INTO clients 
            (session_id, client_ip, client_name, email, status, last_message, message_count, first_visit, last_visit) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $lastMessage = end($client['messages'])['message'];
        $messageCount = count($client['messages']);
        $firstVisit = $client['messages'][0]['timestamp'];
        $lastVisit = end($client['messages'])['timestamp'];
        
        $stmt->execute([
            $sessionId,
            $client['ip'],
            $client['name'],
            $client['email'],
            $client['status'],
            $lastMessage,
            $messageCount,
            $firstVisit,
            $lastVisit
        ]);
        
        // Insert messages
        foreach ($client['messages'] as $message) {
            $stmt = $db->prepare("
                INSERT INTO messages 
                (session_id, client_ip, sender, message, timestamp) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $sessionId,
                $client['ip'],
                $message['sender'],
                $message['message'],
                $message['timestamp']
            ]);
        }
    }
} 