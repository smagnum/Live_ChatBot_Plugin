<?php
/**
 * WordPress Support Hub - Database Installation Script
 * Run this file once to set up the database and tables
 */

// Configuration - Update these with your database details
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'support_hub';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Support Hub - Installation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .header p {
            color: #6b7280;
            margin: 0;
        }
        
        .step {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .step h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
        }
        
        .step p {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .code {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 10px 0;
            overflow-x: auto;
        }
        
        .requirements {
            background: #fffbeb;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        
        .requirements h4 {
            margin: 0 0 10px 0;
            color: #92400e;
        }
        
        .requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #92400e;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 WordPress Support Hub</h1>
            <p>Installation & Setup</p>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <?php
            $host = $_POST['db_host'] ?? $DB_HOST;
            $user = $_POST['db_user'] ?? $DB_USER;
            $pass = $_POST['db_pass'] ?? $DB_PASS;
            $name = $_POST['db_name'] ?? $DB_NAME;
            
            try {
                // Create database connection
                $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$name`");
                
                // Create tables
                $tables = [
                    "wp_connections" => "
                        CREATE TABLE IF NOT EXISTS wp_connections (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            session_id VARCHAR(255) UNIQUE NOT NULL,
                            wp_url VARCHAR(500) NOT NULL,
                            wp_username VARCHAR(100) NOT NULL,
                            wp_password_hash VARCHAR(255) NOT NULL,
                            connection_status ENUM('active', 'expired', 'invalid') DEFAULT 'active',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            expires_at TIMESTAMP NOT NULL,
                            INDEX idx_session (session_id),
                            INDEX idx_status (connection_status)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ",
                    "clients" => "
                        CREATE TABLE IF NOT EXISTS clients (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            session_id VARCHAR(255) NOT NULL,
                            client_ip VARCHAR(45) NOT NULL,
                            client_name VARCHAR(100),
                            email VARCHAR(200),
                            user_agent TEXT,
                            status ENUM('online', 'offline') DEFAULT 'offline',
                            first_visit TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            last_visit TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            last_message TEXT,
                            message_count INT DEFAULT 0,
                            FOREIGN KEY (session_id) REFERENCES wp_connections(session_id) ON DELETE CASCADE,
                            INDEX idx_session_ip (session_id, client_ip)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ",
                    "messages" => "
                        CREATE TABLE IF NOT EXISTS messages (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            session_id VARCHAR(255) NOT NULL,
                            client_ip VARCHAR(45) NOT NULL,
                            sender ENUM('user', 'support') NOT NULL,
                            message TEXT NOT NULL,
                            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            is_read BOOLEAN DEFAULT FALSE,
                            FOREIGN KEY (session_id) REFERENCES wp_connections(session_id) ON DELETE CASCADE,
                            INDEX idx_session_client (session_id, client_ip),
                            INDEX idx_timestamp (timestamp)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    "
                ];
                
                $createdTables = [];
                foreach ($tables as $tableName => $sql) {
                    $pdo->exec($sql);
                    $createdTables[] = $tableName;
                }
                
                // Update config file
                $configPath = __DIR__ . '/config.php';
                if (file_exists($configPath)) {
                    $configContent = file_get_contents($configPath);
                    $configContent = preg_replace("/define\('DB_HOST', '[^']*'\);/", "define('DB_HOST', '$host');", $configContent);
                    $configContent = preg_replace("/define\('DB_USER', '[^']*'\);/", "define('DB_USER', '$user');", $configContent);
                    $configContent = preg_replace("/define\('DB_PASS', '[^']*'\);/", "define('DB_PASS', '$pass');", $configContent);
                    $configContent = preg_replace("/define\('DB_NAME', '[^']*'\);/", "define('DB_NAME', '$name');", $configContent);
                    file_put_contents($configPath, $configContent);
                }
                
                echo '<div class="success">';
                echo '<h4>✅ Installation Completed Successfully!</h4>';
                echo '<p>Database and tables have been created:</p>';
                echo '<ul>';
                foreach ($createdTables as $table) {
                    echo "<li>✓ Table: $table</li>";
                }
                echo '</ul>';
                echo '<p><strong>Next Steps:</strong></p>';
                echo '<ol>';
                echo '<li>Delete this installation file for security</li>';
                echo '<li>Open <code>index.html</code> in your browser</li>';
                echo '<li>Enter your WordPress credentials to start using the support hub</li>';
                echo '</ol>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h4>❌ Installation Failed</h4>';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p>Please check your database configuration and try again.</p>';
                echo '</div>';
            }
            ?>
        <?php endif; ?>

        <div class="requirements">
            <h4>⚡ System Requirements</h4>
            <ul>
                <li>PHP 7.4 or higher</li>
                <li>MySQL 5.7 or higher</li>
                <li>PDO MySQL extension</li>
                <li>cURL extension (for WordPress validation)</li>
            </ul>
        </div>

        <form method="POST">
            <div class="step">
                <h3>1. Database Configuration</h3>
                <p>Enter your MySQL database connection details:</p>
            </div>

            <div class="form-group">
                <label for="db_host">Database Host:</label>
                <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($DB_HOST); ?>" required>
            </div>

            <div class="form-group">
                <label for="db_user">Database Username:</label>
                <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($DB_USER); ?>" required>
            </div>

            <div class="form-group">
                <label for="db_pass">Database Password:</label>
                <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($DB_PASS); ?>">
            </div>

            <div class="form-group">
                <label for="db_name">Database Name:</label>
                <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($DB_NAME); ?>" required>
            </div>

            <button type="submit" class="btn" id="installBtn">
                <span id="installText">🚀 Install WordPress Support Hub</span>
                <span id="installLoading" style="display: none;"><span class="loading"></span> Installing...</span>
            </button>
        </form>

        <div class="step">
            <h3>2. File Structure</h3>
            <p>Ensure your files are organized as follows:</p>
            <div class="code">
your-domain.com/
├── index.html
├── styles.css
├── script.js
└── api/
    ├── config.php
    ├── connect.php
    ├── clients.php
    ├── messages.php
    └── install.php (this file)
            </div>
        </div>

        <div class="step">
            <h3>3. Security Notice</h3>
            <p>After installation:</p>
            <ul>
                <li>Delete this <code>install.php</code> file</li>
                <li>Change the JWT secret in <code>config.php</code></li>
                <li>Use HTTPS in production</li>
                <li>Configure proper database user permissions</li>
            </ul>
        </div>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.getElementById('installBtn');
            const text = document.getElementById('installText');
            const loading = document.getElementById('installLoading');
            
            btn.disabled = true;
            text.style.display = 'none';
            loading.style.display = 'inline-block';
        });
    </script>
</body>
</html> 