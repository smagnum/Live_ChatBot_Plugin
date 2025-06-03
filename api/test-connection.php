<?php
/**
 * Test WordPress Connection with Fixed Validation
 */

require_once 'config.php';

// Test your WordPress credentials
$wpUrl = 'https://masbantech.com';
$wpUsername = 'admin'; // Replace with your username
$wpPassword = 'your-password'; // Replace with your password

echo "<h1>Testing Fixed WordPress Validation</h1>";
echo "<p><strong>Site:</strong> $wpUrl</p>";
echo "<p><strong>Username:</strong> $wpUsername</p>";

$validation = WordPressValidator::validateConnection($wpUrl, $wpUsername, $wpPassword);

if ($validation['valid']) {
    echo "<div style='color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ SUCCESS!</h3>";
    echo "<p>WordPress validation successful!</p>";
    echo "<p><strong>User Data:</strong></p>";
    echo "<pre>" . print_r($validation['user_data'], true) . "</pre>";
    echo "</div>";
} else {
    echo "<div style='color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ FAILED</h3>";
    echo "<p><strong>Error:</strong> " . $validation['error'] . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><em>If this test succeeds, your WordPress Support Hub should now work!</em></p>";
?> 