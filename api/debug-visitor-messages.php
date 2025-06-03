<?php
/**
 * Debug Visitor Messages
 * Shows all visitor messages and sessions to help troubleshoot dashboard issues
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h1>🔍 Visitor Messages Debug Report</h1>";
    echo "<style>body{font-family:Arial;margin:20px;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;} .section{margin:30px 0;padding:20px;border:1px solid #ccc;}</style>";
    
    // 1. Show all active connections/sessions
    echo "<div class='section'>";
    echo "<h2>📋 All Active Sessions (wp_connections)</h2>";
    $stmt = $db->prepare("
        SELECT session_id, wp_url, wp_username, connection_status, created_at, expires_at 
        FROM wp_connections 
        WHERE connection_status = 'active' AND expires_at > NOW()
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $connections = $stmt->fetchAll();
    
    if ($connections) {
        echo "<table>";
        echo "<tr><th>Session ID</th><th>WP URL</th><th>Username</th><th>Status</th><th>Created</th><th>Expires</th></tr>";
        foreach ($connections as $conn) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars(substr($conn['session_id'], 0, 20) . '...') . "</td>";
            echo "<td>" . htmlspecialchars($conn['wp_url']) . "</td>";
            echo "<td>" . htmlspecialchars($conn['wp_username']) . "</td>";
            echo "<td>" . htmlspecialchars($conn['connection_status']) . "</td>";
            echo "<td>" . htmlspecialchars($conn['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($conn['expires_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ No active sessions found!</p>";
    }
    echo "</div>";
    
    // 2. Show all clients 
    echo "<div class='section'>";
    echo "<h2>👥 All Clients</h2>";
    $stmt = $db->prepare("
        SELECT c.*, wc.wp_username, wc.wp_url
        FROM clients c
        LEFT JOIN wp_connections wc ON c.session_id = wc.session_id
        ORDER BY c.last_visit DESC
        LIMIT 20
    ");
    $stmt->execute();
    $clients = $stmt->fetchAll();
    
    if ($clients) {
        echo "<table>";
        echo "<tr><th>Client IP</th><th>Name</th><th>Session</th><th>WP User</th><th>Status</th><th>Messages</th><th>Last Message</th><th>Last Visit</th></tr>";
        foreach ($clients as $client) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($client['client_ip']) . "</td>";
            echo "<td>" . htmlspecialchars($client['client_name'] ?: 'Anonymous') . "</td>";
            echo "<td>" . htmlspecialchars(substr($client['session_id'], 0, 15) . '...') . "</td>";
            echo "<td>" . htmlspecialchars($client['wp_username'] ?: 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($client['status']) . "</td>";
            echo "<td>" . htmlspecialchars($client['message_count']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($client['last_message'] ?: '', 0, 50) . '...') . "</td>";
            echo "<td>" . htmlspecialchars($client['last_visit']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ No clients found!</p>";
    }
    echo "</div>";
    
    // 3. Show recent messages
    echo "<div class='section'>";
    echo "<h2>💬 Recent Messages (Last 20)</h2>";
    $stmt = $db->prepare("
        SELECT m.*, c.client_name, wc.wp_username 
        FROM messages m
        LEFT JOIN clients c ON m.session_id = c.session_id AND m.client_ip = c.client_ip
        LEFT JOIN wp_connections wc ON m.session_id = wc.session_id
        ORDER BY m.timestamp DESC
        LIMIT 20
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll();
    
    if ($messages) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Session</th><th>Client IP</th><th>Client Name</th><th>WP User</th><th>Sender</th><th>Message</th><th>Timestamp</th></tr>";
        foreach ($messages as $msg) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($msg['id']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($msg['session_id'], 0, 15) . '...') . "</td>";
            echo "<td>" . htmlspecialchars($msg['client_ip']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['client_name'] ?: 'Anonymous') . "</td>";
            echo "<td>" . htmlspecialchars($msg['wp_username'] ?: 'visitor_messages') . "</td>";
            echo "<td>" . htmlspecialchars($msg['sender']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($msg['message'], 0, 100) . '...') . "</td>";
            echo "<td>" . htmlspecialchars($msg['timestamp']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ No messages found!</p>";
    }
    echo "</div>";
    
    // 4. Show specific test messages
    echo "<div class='section'>";
    echo "<h2>🧪 Recent Test Messages (Message IDs 364-365)</h2>";
    $stmt = $db->prepare("
        SELECT m.*, c.client_name, wc.wp_username 
        FROM messages m
        LEFT JOIN clients c ON m.session_id = c.session_id AND m.client_ip = c.client_ip
        LEFT JOIN wp_connections wc ON m.session_id = wc.session_id
        WHERE m.id IN (364, 365) OR m.id > 360
        ORDER BY m.id DESC
    ");
    $stmt->execute();
    $testMessages = $stmt->fetchAll();
    
    if ($testMessages) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Session</th><th>Client IP</th><th>Client Name</th><th>WP User</th><th>Sender</th><th>Message</th><th>Timestamp</th></tr>";
        foreach ($testMessages as $msg) {
            echo "<tr style='background:" . ($msg['id'] >= 364 ? '#ffffcc' : 'white') . ";'>";
            echo "<td><strong>" . htmlspecialchars($msg['id']) . "</strong></td>";
            echo "<td>" . htmlspecialchars(substr($msg['session_id'], 0, 15) . '...') . "</td>";
            echo "<td>" . htmlspecialchars($msg['client_ip']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['client_name'] ?: 'Anonymous') . "</td>";
            echo "<td>" . htmlspecialchars($msg['wp_username'] ?: 'visitor_messages') . "</td>";
            echo "<td>" . htmlspecialchars($msg['sender']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['message']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['timestamp']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ No test messages found!</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>✅ What to Check</h2>";
    echo "<ul>";
    echo "<li>Look for your test message IDs (364, 365) in the Recent Test Messages section</li>";
    echo "<li>Check if the client IP from the test appears in the Clients section</li>";
    echo "<li>Verify that the session for your test messages appears in Active Sessions</li>";
    echo "<li>Compare session IDs between your dashboard login and the visitor messages</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h1 style='color:red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h1>";
}
?> 