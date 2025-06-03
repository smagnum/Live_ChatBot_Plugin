<?php
/**
 * WordPress Connection Debug Script
 * Test WordPress connectivity and see detailed error information
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Connection Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #0f5132; background: #d1e7dd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #842029; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #055160; background: #cff4fc; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .debug { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #0d6efd; background: #f8f9fa; }
        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        input, button { padding: 8px; margin: 5px; }
        button { background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 WordPress Connection Debug Tool</h1>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <?php
            $wpUrl = trim($_POST['wp_url']);
            $wpUsername = trim($_POST['wp_username']);
            $wpPassword = $_POST['wp_password'];
            
            echo "<h2>Testing Connection to: $wpUrl</h2>";
            echo "<p><strong>Username:</strong> $wpUsername</p>";
            
            // Test 1: Basic URL validation
            echo "<div class='step'>";
            echo "<h3>Step 1: URL Validation</h3>";
            if (filter_var($wpUrl, FILTER_VALIDATE_URL)) {
                echo "<div class='success'>✓ URL format is valid</div>";
            } else {
                echo "<div class='error'>✗ Invalid URL format</div>";
                exit;
            }
            echo "</div>";
            
            // Test 2: Check if site is accessible
            echo "<div class='step'>";
            echo "<h3>Step 2: Site Accessibility Test</h3>";
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $wpUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'WordPress Support Hub Debug/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo "<div class='error'>✗ cURL Error: $error</div>";
            } elseif ($httpCode === 200) {
                echo "<div class='success'>✓ Site is accessible (HTTP $httpCode)</div>";
                if (stripos($response, 'wordpress') !== false || stripos($response, 'wp-content') !== false) {
                    echo "<div class='success'>✓ WordPress detected in response</div>";
                } else {
                    echo "<div class='info'>? WordPress not clearly detected in response (might still be WP)</div>";
                }
            } else {
                echo "<div class='error'>✗ Site returned HTTP $httpCode</div>";
            }
            echo "</div>";
            
            // Test 3: Check WordPress login page
            echo "<div class='step'>";
            echo "<h3>Step 3: WordPress Login Page Test</h3>";
            
            $loginUrl = rtrim($wpUrl, '/') . '/wp-login.php';
            echo "<p>Testing: <code>$loginUrl</code></p>";
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $loginUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'WordPress Support Hub Debug/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo "<div class='error'>✗ cURL Error: $error</div>";
            } elseif ($httpCode === 200) {
                echo "<div class='success'>✓ Login page accessible (HTTP $httpCode)</div>";
                if (stripos($response, 'wp-login') !== false || stripos($response, 'login') !== false) {
                    echo "<div class='success'>✓ WordPress login page detected</div>";
                } else {
                    echo "<div class='error'>✗ Does not appear to be WordPress login page</div>";
                }
            } else {
                echo "<div class='error'>✗ Login page returned HTTP $httpCode</div>";
            }
            echo "</div>";
            
            // Test 4: REST API Test
            echo "<div class='step'>";
            echo "<h3>Step 4: WordPress REST API Test</h3>";
            
            $apiUrl = rtrim($wpUrl, '/') . '/wp-json/wp/v2/users/me';
            echo "<p>Testing: <code>$apiUrl</code></p>";
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_USERPWD => $wpUsername . ':' . $wpPassword,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'WordPress Support Hub Debug/1.0',
                CURLOPT_HEADER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo "<div class='error'>✗ cURL Error: $error</div>";
            } else {
                echo "<div class='info'>HTTP Code: $httpCode</div>";
                
                if ($httpCode === 200) {
                    echo "<div class='success'>✓ REST API authentication successful!</div>";
                    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $body = substr($response, $headerSize);
                    $userData = json_decode($body, true);
                    if ($userData && isset($userData['name'])) {
                        echo "<div class='success'>✓ User data retrieved: " . $userData['name'] . "</div>";
                    }
                } elseif ($httpCode === 401) {
                    echo "<div class='error'>✗ REST API: Invalid credentials (HTTP 401)</div>";
                } elseif ($httpCode === 404) {
                    echo "<div class='error'>✗ REST API not found (HTTP 404) - API might be disabled</div>";
                } else {
                    echo "<div class='error'>✗ REST API returned HTTP $httpCode</div>";
                }
                
                echo "<div class='debug'>";
                echo "<strong>Response Headers:</strong><br>";
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $headers = substr($response, 0, $headerSize);
                echo "<pre>" . htmlspecialchars($headers) . "</pre>";
                echo "</div>";
            }
            echo "</div>";
            
            // Test 5: Alternative login test
            echo "<div class='step'>";
            echo "<h3>Step 5: Alternative Login Method Test</h3>";
            
            $loginUrl = rtrim($wpUrl, '/') . '/wp-login.php';
            $postData = http_build_query([
                'log' => $wpUsername,
                'pwd' => $wpPassword,
                'wp-submit' => 'Log In',
                'redirect_to' => rtrim($wpUrl, '/') . '/wp-admin/',
                'testcookie' => '1'
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $loginUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_COOKIEJAR => tempnam(sys_get_temp_dir(), 'wp_cookie'),
                CURLOPT_USERAGENT => 'WordPress Support Hub Debug/1.0',
                CURLOPT_HEADER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo "<div class='error'>✗ cURL Error: $error</div>";
            } else {
                echo "<div class='info'>HTTP Code: $httpCode</div>";
                
                if ($httpCode === 302 && strpos($redirectUrl, '/wp-admin/') !== false) {
                    echo "<div class='success'>✓ Alternative login successful! Redirected to: $redirectUrl</div>";
                } elseif ($httpCode === 302) {
                    echo "<div class='error'>✗ Redirected but not to admin area: $redirectUrl</div>";
                } else {
                    echo "<div class='error'>✗ Login failed (HTTP $httpCode)</div>";
                    
                    if (stripos($response, 'login_error') !== false) {
                        echo "<div class='error'>✗ Login error detected in response</div>";
                    }
                    if (stripos($response, 'incorrect') !== false) {
                        echo "<div class='error'>✗ 'Incorrect' found in response - likely wrong credentials</div>";
                    }
                }
                
                echo "<div class='debug'>";
                echo "<strong>Response preview:</strong><br>";
                echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "...</pre>";
                echo "</div>";
            }
            echo "</div>";
            
            // Common Issues and Solutions
            echo "<div class='step'>";
            echo "<h3>🔧 Common Issues & Solutions</h3>";
            echo "<ul>";
            echo "<li><strong>REST API disabled:</strong> Install a plugin like 'Application Passwords' or enable REST API</li>";
            echo "<li><strong>Security plugins:</strong> Whitelist your server IP in security plugins (Wordfence, etc.)</li>";
            echo "<li><strong>2FA enabled:</strong> Use application passwords instead of regular passwords</li>";
            echo "<li><strong>Hosting restrictions:</strong> Some hosts block external API requests</li>";
            echo "<li><strong>SSL issues:</strong> Try with 'http://' instead of 'https://' for testing</li>";
            echo "<li><strong>Different admin URL:</strong> Some sites use custom admin URLs</li>";
            echo "</ul>";
            echo "</div>";
            
            ?>
        <?php else: ?>
            <div class="info">
                <p>This tool will test your WordPress connection and show detailed debug information.</p>
                <p><strong>Enter your WordPress credentials below:</strong></p>
            </div>
            
            <form method="POST">
                <p>
                    <label>WordPress URL:</label><br>
                    <input type="url" name="wp_url" placeholder="https://your-site.com" style="width: 300px;" required>
                </p>
                <p>
                    <label>Username:</label><br>
                    <input type="text" name="wp_username" placeholder="admin" style="width: 200px;" required>
                </p>
                <p>
                    <label>Password:</label><br>
                    <input type="password" name="wp_password" placeholder="password" style="width: 200px;" required>
                </p>
                <p>
                    <button type="submit">🔍 Test Connection</button>
                </p>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 