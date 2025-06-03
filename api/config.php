<?php
/**
 * WordPress Support Hub - API Configuration
 * Main configuration file for the backend API
 */

// Prevent direct access
if (!defined('SUPPORT_HUB_API')) {
    define('SUPPORT_HUB_API', true);
}

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS Headers for frontend access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration for local storage
define('DB_HOST', 'localhost');
define('DB_NAME', 'sql_masbantech_c');
define('DB_USER', 'sql_masbantech_c');
define('DB_PASS', 'eac3f5ab0bb4c');
define('DB_CHARSET', 'utf8mb4');

// API configuration
define('API_VERSION', '1.0.0');
define('API_BASE_URL', '/api/');

// Security
define('JWT_SECRET', 'your-super-secret-jwt-key-change-this-in-production');
define('SESSION_TIMEOUT', 3600); // 1 hour

// WordPress connection timeout
define('WP_CONNECTION_TIMEOUT', 10);

// Database connection
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Initialize database tables
function initializeTables() {
    $db = Database::getInstance()->getConnection();
    
    // WordPress connections table
    $sql = "CREATE TABLE IF NOT EXISTS wp_connections (
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
    )";
    $db->exec($sql);
    
    // Clients table (local storage for demonstration)
    $sql = "CREATE TABLE IF NOT EXISTS clients (
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
    )";
    $db->exec($sql);
    
    // Messages table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
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
    )";
    $db->exec($sql);
}

// Utility functions
function generateSessionId() {
    return bin2hex(random_bytes(32));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateJWT($data, $expiry = null) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'data' => $data,
        'exp' => $expiry ?: time() + SESSION_TIMEOUT,
        'iat' => time()
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

function verifyJWT($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return false;
    }
    
    [$header, $payload, $signature] = $parts;
    
    $validSignature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET, true);
    $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
    
    if ($signature !== $validSignature) {
        return false;
    }
    
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    
    if ($payload['exp'] < time()) {
        return false; // Token expired
    }
    
    return $payload['data'];
}

function sendResponse($data, $status = 200, $message = '') {
    http_response_code($status);
    echo json_encode([
        'success' => $status >= 200 && $status < 300,
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit();
}

function sendError($message, $status = 400, $details = null) {
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'status' => $status,
        'error' => $message,
        'details' => $details,
        'timestamp' => date('c')
    ]);
    exit();
}

// Initialize database tables
try {
    initializeTables();
} catch (Exception $e) {
    sendError('Failed to initialize database: ' . $e->getMessage(), 500);
}

// WordPress connection validator
class WordPressValidator {
    public static function validateConnection($url, $username, $password) {
        // Clean and validate URL
        $url = rtrim($url, '/');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }
        
        // Try to access WordPress login page
        $loginUrl = $url . '/wp-login.php';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $loginUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => WP_CONNECTION_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'WordPress Support Hub API/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || strpos($response, 'wp-login') === false) {
            return ['valid' => false, 'error' => 'WordPress site not accessible or login page not found'];
        }
        
        // Attempt to validate credentials via WordPress API
        $apiUrl = $url . '/wp-json/wp/v2/users/me';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => WP_CONNECTION_TIMEOUT,
            CURLOPT_USERPWD => $username . ':' . $password,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'WordPress Support Hub API/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $userData = json_decode($response, true);
            return [
                'valid' => true,
                'user_data' => [
                    'id' => $userData['id'] ?? null,
                    'name' => $userData['name'] ?? $username,
                    'email' => $userData['email'] ?? '',
                    'roles' => $userData['roles'] ?? []
                ]
            ];
        } else if ($httpCode === 401) {
            return ['valid' => false, 'error' => 'Invalid username or password'];
        } else {
            // Fallback: Try alternative validation method
            return self::validateCredentialsAlternative($url, $username, $password);
        }
    }
    
    private static function validateCredentialsAlternative($url, $username, $password) {
        // Alternative validation for sites without REST API enabled
        $loginUrl = $url . '/wp-login.php';
        
        $postData = http_build_query([
            'log' => $username,
            'pwd' => $password,
            'wp-submit' => 'Log In',
            'redirect_to' => $url . '/wp-admin/',
            'testcookie' => '1'
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $loginUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => WP_CONNECTION_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIEJAR => tempnam(sys_get_temp_dir(), 'wp_cookie'),
            CURLOPT_USERAGENT => 'WordPress Support Hub API/1.0',
            CURLOPT_HEADER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        
        // Check if redirected to admin dashboard (successful login)
        if ($httpCode === 302 && strpos($redirectUrl, '/wp-admin/') !== false) {
            return [
                'valid' => true,
                'user_data' => [
                    'name' => $username,
                    'email' => '',
                    'roles' => ['administrator'] // Assume admin for successful login
                ]
            ];
        }
        
        // NEW: Check for WordPress authentication cookies (alternative success indicator)
        if ($httpCode === 200 && (strpos($response, 'wordpress_logged_in_') !== false || strpos($response, 'wordpress_sec_') !== false)) {
            // Cookies indicate successful authentication
            return [
                'valid' => true,
                'user_data' => [
                    'name' => $username,
                    'email' => '',
                    'roles' => ['administrator']
                ]
            ];
        }
        
        // Check for login errors in response
        if (strpos($response, 'login_error') !== false || strpos($response, 'Incorrect username') !== false || strpos($response, 'incorrect') !== false) {
            return ['valid' => false, 'error' => 'Invalid username or password'];
        }
        
        return ['valid' => false, 'error' => 'Unable to validate credentials. Please check your WordPress configuration.'];
    }
} 