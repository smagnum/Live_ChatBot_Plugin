<?php
/**
 * API Debug Tool - Test all endpoints and database connectivity
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Debug Tool</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #0f5132; background: #d1e7dd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #842029; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #055160; background: #cff4fc; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .debug { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #0d6efd; background: #f8f9fa; }
        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .test-section { margin: 20px 0; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .test-header { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; }
        .test-content { padding: 15px; }
        button { background: #0d6efd; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin: 5px; }
        input { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ WordPress Support Hub - API Debug Tool</h1>
        
        <?php
        // Test 1: Database Connection
        echo "<div class='test-section'>";
        echo "<div class='test-header'>1. Database Connection Test</div>";
        echo "<div class='test-content'>";
        
        try {
            require_once 'config.php';
            $db = Database::getInstance()->getConnection();
            echo "<div class='success'>✓ Database connection successful</div>";
            
            // Test tables exist
            $tables = ['wp_connections', 'clients', 'messages'];
            foreach ($tables as $table) {
                $stmt = $db->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->rowCount() > 0) {
                    echo "<div class='success'>✓ Table '$table' exists</div>";
                    
                    // Count records
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table");
                    $stmt->execute();
                    $count = $stmt->fetch()['count'];
                    echo "<div class='info'>→ $count records in '$table'</div>";
                } else {
                    echo "<div class='error'>✗ Table '$table' missing</div>";
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>✗ Database error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div></div>";
        
        // Test 2: API Endpoints
        echo "<div class='test-section'>";
        echo "<div class='test-header'>2. API Endpoints Test</div>";
        echo "<div class='test-content'>";
        
        $endpoints = [
            'config.php' => 'Configuration file',
            'connect.php' => 'Connection endpoint',
            'connect-simple.php' => 'Simplified connection endpoint',
            'clients.php' => 'Clients API',
            'messages.php' => 'Messages API'
        ];
        
        foreach ($endpoints as $file => $description) {
            if (file_exists($file)) {
                echo "<div class='success'>✓ $description ($file) exists</div>";
                
                // Check file permissions
                if (is_readable($file)) {
                    echo "<div class='info'>→ File is readable</div>";
                } else {
                    echo "<div class='error'>→ File permission issues</div>";
                }
            } else {
                echo "<div class='error'>✗ $description ($file) missing</div>";
            }
        }
        
        echo "</div></div>";
        
        // Test 3: CORS and Headers
        echo "<div class='test-section'>";
        echo "<div class='test-header'>3. CORS and Headers Test</div>";
        echo "<div class='test-content'>";
        
        $headers = getallheaders();
        echo "<div class='debug'>";
        echo "<strong>Current Request Headers:</strong><br>";
        echo "<pre>" . print_r($headers, true) . "</pre>";
        echo "</div>";
        
        echo "<div class='info'>Response headers should include CORS headers for API access</div>";
        
        echo "</div></div>";
        
        // Test 4: Test Connection with Simplified Mode
        echo "<div class='test-section'>";
        echo "<div class='test-header'>4. Test Simple Connection</div>";
        echo "<div class='test-content'>";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_simple'])) {
            $testUrl = $_POST['test_url'];
            $testUser = $_POST['test_user'];
            $testPass = $_POST['test_pass'];
            
            // Simulate API call to connect-simple.php
            $postData = json_encode([
                'wpUrl' => $testUrl,
                'wpUsername' => $testUser,
                'wpPassword' => $testPass
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'connect-simple.php',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_HEADER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                echo "<div class='success'>✓ Simple connection test successful (HTTP $httpCode)</div>";
                
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $body = substr($response, $headerSize);
                $data = json_decode($body, true);
                
                if ($data && $data['success']) {
                    echo "<div class='success'>✓ JWT token generated successfully</div>";
                    echo "<div class='info'>Session ID: " . $data['data']['session_id'] . "</div>";
                    
                    // Test clients endpoint with this token
                    echo "<br><strong>Testing Clients API with generated token:</strong><br>";
                    
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => 'clients.php',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => [
                            'Authorization: Bearer ' . $data['data']['token'],
                            'Content-Type: application/json'
                        ]
                    ]);
                    
                    $clientResponse = curl_exec($ch);
                    $clientHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($clientHttpCode === 200) {
                        echo "<div class='success'>✓ Clients API working (HTTP $clientHttpCode)</div>";
                        $clientData = json_decode($clientResponse, true);
                        if ($clientData && isset($clientData['data']['clients'])) {
                            echo "<div class='info'>→ Found " . count($clientData['data']['clients']) . " sample clients</div>";
                        }
                    } else {
                        echo "<div class='error'>✗ Clients API failed (HTTP $clientHttpCode)</div>";
                        echo "<div class='debug'><pre>" . htmlspecialchars($clientResponse) . "</pre></div>";
                    }
                    
                } else {
                    echo "<div class='error'>✗ Invalid response format</div>";
                    echo "<div class='debug'><pre>" . htmlspecialchars($body) . "</pre></div>";
                }
            } else {
                echo "<div class='error'>✗ Simple connection failed (HTTP $httpCode)</div>";
                echo "<div class='debug'><pre>" . htmlspecialchars($response) . "</pre></div>";
            }
        }
        
        ?>
        
        <form method="POST">
            <h4>Test Simple Connection:</h4>
            <input type="url" name="test_url" placeholder="https://masbantech.com" value="https://masbantech.com" required style="width: 250px;"><br>
            <input type="text" name="test_user" placeholder="username" value="admin" required><br>
            <input type="password" name="test_pass" placeholder="password" required><br>
            <button type="submit" name="test_simple">🧪 Test Simple Connection</button>
        </form>
        
        <?php
        
        echo "</div></div>";
        
        // Test 5: JavaScript Console Errors
        echo "<div class='test-section'>";
        echo "<div class='test-header'>5. Frontend Debug Instructions</div>";
        echo "<div class='test-content'>";
        
        echo "<div class='info'>";
        echo "<strong>To check for JavaScript errors:</strong><br>";
        echo "1. Open your WordPress Support Hub in browser<br>";
        echo "2. Press F12 to open Developer Tools<br>";
        echo "3. Go to 'Console' tab<br>";
        echo "4. Look for any red error messages<br>";
        echo "5. Try connecting and check for new errors<br>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<strong>To check network requests:</strong><br>";
        echo "1. In Developer Tools, go to 'Network' tab<br>";
        echo "2. Try connecting to WordPress<br>";
        echo "3. Look for failed requests (red status codes)<br>";
        echo "4. Click on failed requests to see error details<br>";
        echo "</div>";
        
        echo "</div></div>";
        
        ?>
        
        <div class="step">
            <h3>🔧 Common Solutions</h3>
            <ul>
                <li><strong>Database issues:</strong> Run api/install.php again</li>
                <li><strong>Permission issues:</strong> Ensure API files are readable by web server</li>
                <li><strong>CORS errors:</strong> Check if CORS headers are being sent</li>
                <li><strong>JavaScript errors:</strong> Check browser console for errors</li>
                <li><strong>Session issues:</strong> Clear browser storage and try again</li>
            </ul>
        </div>
    </div>
</body>
</html> 