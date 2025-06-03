<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the current URL context
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$requestUri = $_SERVER['REQUEST_URI'];
$fullUrl = $protocol . '://' . $host . $requestUri;

// Get referrer information
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct access';

// Log the test request
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test_type' => 'iframe_api_test',
    'full_url' => $fullUrl,
    'referrer' => $referrer,
    'host' => $host,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'
];

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'API endpoint is working correctly!',
    'data' => [
        'timestamp' => date('c'),
        'api_url' => $fullUrl,
        'referrer' => $referrer,
        'iframe_context' => strpos($referrer, 'wordpress_support_hub') === false && strpos($referrer, 'masbantech.com') !== false,
        'test_passed' => true
    ]
]);
?> 