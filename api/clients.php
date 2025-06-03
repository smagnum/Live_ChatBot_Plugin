<?php
/**
 * Clients API Endpoint
 * Handles client data retrieval and management
 */

require_once 'config.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

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

try {
    $db = Database::getInstance()->getConnection();
    
    // Verify session is still valid
    $stmt = $db->prepare("
        SELECT * FROM wp_connections 
        WHERE session_id = ? AND connection_status = 'active' AND expires_at > NOW()
    ");
    $stmt->execute([$sessionId]);
    $connection = $stmt->fetch();
    
    if (!$connection) {
        sendError('Session expired or invalid', 401);
    }
    
    // Get clients from ALL active sessions, not just the current one
    // This allows visitor messages to appear regardless of which session they're stored in
    $stmt = $db->prepare("
        SELECT 
            c.client_ip,
            c.client_name,
            c.email,
            c.status,
            c.first_visit,
            c.last_visit,
            c.last_message,
            c.message_count,
            c.session_id,
            CASE 
                WHEN c.last_visit > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'online'
                ELSE 'offline'
            END as current_status
        FROM clients c
        INNER JOIN wp_connections wc ON c.session_id = wc.session_id
        WHERE wc.connection_status = 'active' AND wc.expires_at > NOW()
        ORDER BY c.last_visit DESC
    ");
    
    $stmt->execute();
    $clients = $stmt->fetchAll();
    
    // Format client data
    $formattedClients = [];
    foreach ($clients as $client) {
        $formattedClients[] = [
            'id' => $client['client_ip'], // Using IP as unique identifier
            'ip_address' => $client['client_ip'],
            'name' => $client['client_name'] ?: 'Anonymous User',
            'email' => $client['email'] ?: '',
            'status' => $client['current_status'],
            'last_message' => $client['last_message'],
            'message_count' => (int)$client['message_count'],
            'first_visit' => $client['first_visit'],
            'last_visit' => $client['last_visit'],
            'time_since_last_visit' => timeAgo($client['last_visit'])
        ];
    }
    
    // Get summary statistics
    $stats = [
        'total_clients' => count($clients),
        'online_clients' => count(array_filter($clients, function($c) { return $c['current_status'] === 'online'; })),
        'total_messages' => array_sum(array_column($clients, 'message_count')),
        'active_conversations' => count(array_filter($clients, function($c) { 
            return strtotime($c['last_visit']) > strtotime('-1 hour'); 
        }))
    ];
    
    sendResponse([
        'clients' => $formattedClients,
        'statistics' => $stats,
        'wordpress_site' => [
            'url' => $connection['wp_url'],
            'username' => $connection['wp_username']
        ]
    ], 200, 'Clients retrieved successfully');
    
} catch (Exception $e) {
    sendError('Failed to retrieve clients: ' . $e->getMessage(), 500);
}

// Helper function to calculate time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $mins = floor($time / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', strtotime($datetime));
    }
} 