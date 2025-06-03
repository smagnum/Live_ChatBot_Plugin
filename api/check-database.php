<?php
/**
 * Check Database Tables in Correct Database
 */

// Database credentials
$host = 'localhost';
$dbname = 'sql_masbantech_c';
$username = 'sql_masbantech_c';
$password = 'eac3f5ab0bb4c';

echo "<h1>🔍 Checking Database: $dbname</h1>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div style='color: green;'>✅ Connected to database: $dbname</div><br>";
    
    // Check required tables
    $requiredTables = ['wp_connections', 'clients', 'messages'];
    $existingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "<div style='color: green;'>✅ Table '$table' exists</div>";
            
            // Count records
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $count = $stmt->fetch()['count'];
            echo "<div style='color: blue;'>→ $count records in '$table'</div><br>";
            
            $existingTables[] = $table;
        } else {
            echo "<div style='color: red;'>❌ Table '$table' MISSING</div><br>";
        }
    }
    
    if (count($existingTables) === count($requiredTables)) {
        echo "<div style='color: green; background: #d4edda; padding: 15px; margin: 20px 0;'>";
        echo "<h2>🎉 ALL TABLES EXIST!</h2>";
        echo "<p>Your WordPress Support Hub should work now.</p>";
        echo "<p><strong>Next step:</strong> Clear browser cache and try connecting again.</p>";
        echo "</div>";
    } else {
        echo "<div style='color: red; background: #f8d7da; padding: 15px; margin: 20px 0;'>";
        echo "<h2>❌ MISSING TABLES!</h2>";
        echo "<p>Some required tables are missing from your database.</p>";
        echo "<p><strong>Solution:</strong> Run the installation script with correct database credentials.</p>";
        echo "</div>";
        
        // Create missing tables
        echo "<h3>🛠️ Creating Missing Tables...</h3>";
        
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
        
        foreach ($tables as $tableName => $sql) {
            try {
                $pdo->exec($sql);
                echo "<div style='color: green;'>✅ Created table: $tableName</div>";
            } catch (Exception $e) {
                echo "<div style='color: red;'>❌ Failed to create table $tableName: " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<div style='color: green; background: #d4edda; padding: 15px; margin: 20px 0;'>";
        echo "<h2>🎉 TABLES CREATED!</h2>";
        echo "<p>All required tables have been created in your database.</p>";
        echo "<p><strong>Your WordPress Support Hub should work now!</strong></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8d7da; padding: 15px;'>";
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='https://masbantech.com/wordpress_support_hub/'>🚀 Try WordPress Support Hub Now</a></p>";
?> 