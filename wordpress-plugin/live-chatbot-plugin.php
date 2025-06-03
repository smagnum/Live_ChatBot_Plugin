<?php
/**
 * Plugin Name: Live ChatBot Support Plugin
 * Plugin URI: https://yoursite.com/live-chatbot-plugin
 * Description: Advanced WordPress support chat plugin with client conversation tracking and support dashboard integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: live-chatbot
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LIVE_CHATBOT_VERSION', '1.0.0');
define('LIVE_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LIVE_CHATBOT_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main Live ChatBot Plugin Class
 */
class LiveChatBotPlugin {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load plugin textdomain
        load_plugin_textdomain('live-chatbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        $this->initHooks();
        $this->initDatabase();
        $this->initAPI();
        $this->initAdmin();
        $this->initFrontend();
    }
    
    /**
     * Initialize hooks
     */
    private function initHooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('wp_footer', array($this, 'renderChatWidget'));
        add_action('wp_ajax_live_chat_send_message', array($this, 'handleSendMessage'));
        add_action('wp_ajax_nopriv_live_chat_send_message', array($this, 'handleSendMessage'));
        add_action('wp_ajax_live_chat_get_messages', array($this, 'handleGetMessages'));
        add_action('wp_ajax_nopriv_live_chat_get_messages', array($this, 'handleGetMessages'));
        add_action('wp_ajax_live_chat_get_clients', array($this, 'handleGetClients'));
        add_action('wp_ajax_live_chat_get_client_history', array($this, 'handleGetClientHistory'));
        add_action('rest_api_init', array($this, 'registerRestRoutes'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->createTables();
        $this->setDefaultOptions();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
    }
    
    /**
     * Create database tables
     */
    private function createTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Clients table
        $clients_table = $wpdb->prefix . 'chatbot_clients';
        $clients_sql = "CREATE TABLE $clients_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id varchar(255) NOT NULL,
            name varchar(100),
            email varchar(100),
            ip_address varchar(45) NOT NULL,
            user_agent text,
            first_visit datetime DEFAULT CURRENT_TIMESTAMP,
            last_visit datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('online','offline') DEFAULT 'offline',
            session_data longtext,
            PRIMARY KEY (id),
            UNIQUE KEY client_id (client_id),
            KEY ip_address (ip_address),
            KEY status (status)
        ) $charset_collate;";
        
        // Messages table
        $messages_table = $wpdb->prefix . 'chatbot_messages';
        $messages_sql = "CREATE TABLE $messages_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id varchar(255) NOT NULL,
            sender enum('user','support') NOT NULL,
            message longtext NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            is_read tinyint(1) DEFAULT 0,
            support_user_id mediumint(9),
            PRIMARY KEY (id),
            KEY client_id (client_id),
            KEY sender (sender),
            KEY timestamp (timestamp),
            KEY is_read (is_read)
        ) $charset_collate;";
        
        // Conversations table
        $conversations_table = $wpdb->prefix . 'chatbot_conversations';
        $conversations_sql = "CREATE TABLE $conversations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id varchar(255) NOT NULL,
            subject varchar(255),
            status enum('open','closed','pending') DEFAULT 'open',
            priority enum('low','medium','high') DEFAULT 'medium',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            closed_at datetime NULL,
            assigned_to mediumint(9),
            PRIMARY KEY (id),
            KEY client_id (client_id),
            KEY status (status),
            KEY priority (priority)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($clients_sql);
        dbDelta($messages_sql);
        dbDelta($conversations_sql);
    }
    
    /**
     * Set default options
     */
    private function setDefaultOptions() {
        $default_options = array(
            'enable_chat' => true,
            'chat_position' => 'bottom-right',
            'chat_title' => 'Live Support',
            'welcome_message' => 'Hello! How can we help you today?',
            'offline_message' => 'We are currently offline. Please leave a message.',
            'chat_theme' => 'default',
            'enable_file_upload' => false,
            'max_file_size' => 5, // MB
            'allowed_file_types' => 'jpg,jpeg,png,gif,pdf,doc,docx',
            'enable_emoji' => true,
            'enable_sound' => true,
            'auto_show_chat' => false,
            'show_agent_typing' => true
        );
        
        foreach ($default_options as $key => $value) {
            if (get_option('live_chatbot_' . $key) === false) {
                add_option('live_chatbot_' . $key, $value);
            }
        }
    }
    
    /**
     * Initialize database operations
     */
    private function initDatabase() {
        // Database operations are handled in individual methods
    }
    
    /**
     * Initialize API endpoints
     */
    private function initAPI() {
        // API endpoints for the support dashboard
    }
    
    /**
     * Initialize admin interface
     */
    private function initAdmin() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'addAdminMenu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        }
    }
    
    /**
     * Initialize frontend
     */
    private function initFrontend() {
        if (!is_admin()) {
            add_action('wp_head', array($this, 'addChatStyles'));
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueueScripts() {
        if (!get_option('live_chatbot_enable_chat', true)) {
            return;
        }
        
        wp_enqueue_script('live-chatbot-js', LIVE_CHATBOT_PLUGIN_URL . 'assets/chatbot.js', array('jquery'), LIVE_CHATBOT_VERSION, true);
        wp_enqueue_style('live-chatbot-css', LIVE_CHATBOT_PLUGIN_URL . 'assets/chatbot.css', array(), LIVE_CHATBOT_VERSION);
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('live-chatbot-js', 'liveChatbot', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('live_chatbot_nonce'),
            'clientId' => $this->getClientId(),
            'settings' => $this->getChatSettings()
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'live-chatbot') === false) {
            return;
        }
        
        wp_enqueue_script('live-chatbot-admin-js', LIVE_CHATBOT_PLUGIN_URL . 'assets/admin.js', array('jquery'), LIVE_CHATBOT_VERSION, true);
        wp_enqueue_style('live-chatbot-admin-css', LIVE_CHATBOT_PLUGIN_URL . 'assets/admin.css', array(), LIVE_CHATBOT_VERSION);
        
        wp_localize_script('live-chatbot-admin-js', 'liveChatbotAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('live_chatbot_admin_nonce')
        ));
    }
    
    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        add_menu_page(
            __('Live ChatBot', 'live-chatbot'),
            __('Live ChatBot', 'live-chatbot'),
            'manage_options',
            'live-chatbot',
            array($this, 'adminDashboard'),
            'dashicons-format-chat',
            30
        );
        
        add_submenu_page(
            'live-chatbot',
            __('Conversations', 'live-chatbot'),
            __('Conversations', 'live-chatbot'),
            'manage_options',
            'live-chatbot-conversations',
            array($this, 'adminConversations')
        );
        
        add_submenu_page(
            'live-chatbot',
            __('Clients', 'live-chatbot'),
            __('Clients', 'live-chatbot'),
            'manage_options',
            'live-chatbot-clients',
            array($this, 'adminClients')
        );
        
        add_submenu_page(
            'live-chatbot',
            __('Settings', 'live-chatbot'),
            __('Settings', 'live-chatbot'),
            'manage_options',
            'live-chatbot-settings',
            array($this, 'adminSettings')
        );
    }
    
    /**
     * Admin dashboard page
     */
    public function adminDashboard() {
        include LIVE_CHATBOT_PLUGIN_PATH . 'templates/admin-dashboard.php';
    }
    
    /**
     * Admin conversations page
     */
    public function adminConversations() {
        include LIVE_CHATBOT_PLUGIN_PATH . 'templates/admin-conversations.php';
    }
    
    /**
     * Admin clients page
     */
    public function adminClients() {
        include LIVE_CHATBOT_PLUGIN_PATH . 'templates/admin-clients.php';
    }
    
    /**
     * Admin settings page
     */
    public function adminSettings() {
        include LIVE_CHATBOT_PLUGIN_PATH . 'templates/admin-settings.php';
    }
    
    /**
     * Render chat widget
     */
    public function renderChatWidget() {
        if (!get_option('live_chatbot_enable_chat', true)) {
            return;
        }
        
        include LIVE_CHATBOT_PLUGIN_PATH . 'templates/chat-widget.php';
    }
    
    /**
     * Add chat styles to head
     */
    public function addChatStyles() {
        $theme = get_option('live_chatbot_chat_theme', 'default');
        echo '<style id="live-chatbot-custom-styles">';
        echo $this->generateChatCSS($theme);
        echo '</style>';
    }
    
    /**
     * Generate chat CSS based on theme
     */
    private function generateChatCSS($theme) {
        $css = '';
        
        switch ($theme) {
            case 'modern':
                $css .= '
                    .live-chatbot-widget {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border-radius: 20px;
                    }
                ';
                break;
            case 'minimal':
                $css .= '
                    .live-chatbot-widget {
                        background: #ffffff;
                        border: 1px solid #e5e7eb;
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                    }
                ';
                break;
            default:
                $css .= '
                    .live-chatbot-widget {
                        background: #6366f1;
                        border-radius: 10px;
                    }
                ';
        }
        
        return $css;
    }
    
    /**
     * Get client ID
     */
    private function getClientId() {
        $client_id = isset($_COOKIE['live_chatbot_client_id']) ? $_COOKIE['live_chatbot_client_id'] : '';
        
        if (empty($client_id)) {
            $client_id = uniqid('client_', true);
            setcookie('live_chatbot_client_id', $client_id, time() + (365 * 24 * 60 * 60), '/');
        }
        
        return $client_id;
    }
    
    /**
     * Get chat settings
     */
    private function getChatSettings() {
        return array(
            'position' => get_option('live_chatbot_chat_position', 'bottom-right'),
            'title' => get_option('live_chatbot_chat_title', 'Live Support'),
            'welcomeMessage' => get_option('live_chatbot_welcome_message', 'Hello! How can we help you today?'),
            'offlineMessage' => get_option('live_chatbot_offline_message', 'We are currently offline. Please leave a message.'),
            'enableEmoji' => get_option('live_chatbot_enable_emoji', true),
            'enableSound' => get_option('live_chatbot_enable_sound', true),
            'autoShow' => get_option('live_chatbot_auto_show_chat', false)
        );
    }
    
    /**
     * Handle send message AJAX request
     */
    public function handleSendMessage() {
        check_ajax_referer('live_chatbot_nonce', 'nonce');
        
        $client_id = sanitize_text_field($_POST['client_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $sender = sanitize_text_field($_POST['sender']);
        
        if (empty($client_id) || empty($message)) {
            wp_die('Invalid data');
        }
        
        // Save client info if not exists
        $this->saveClientInfo($client_id);
        
        // Save message
        $message_id = $this->saveMessage($client_id, $sender, $message);
        
        if ($message_id) {
            wp_send_json_success(array(
                'message_id' => $message_id,
                'timestamp' => current_time('mysql')
            ));
        } else {
            wp_send_json_error('Failed to save message');
        }
    }
    
    /**
     * Handle get messages AJAX request
     */
    public function handleGetMessages() {
        check_ajax_referer('live_chatbot_nonce', 'nonce');
        
        $client_id = sanitize_text_field($_POST['client_id']);
        $last_message_id = intval($_POST['last_message_id']);
        
        if (empty($client_id)) {
            wp_die('Invalid client ID');
        }
        
        $messages = $this->getMessages($client_id, $last_message_id);
        
        wp_send_json_success($messages);
    }
    
    /**
     * Handle get clients AJAX request
     */
    public function handleGetClients() {
        check_ajax_referer('live_chatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $clients = $this->getAllClients();
        
        wp_send_json_success($clients);
    }
    
    /**
     * Handle get client history AJAX request
     */
    public function handleGetClientHistory() {
        check_ajax_referer('live_chatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $client_id = sanitize_text_field($_POST['client_id']);
        
        if (empty($client_id)) {
            wp_die('Invalid client ID');
        }
        
        $history = $this->getClientHistory($client_id);
        
        wp_send_json_success($history);
    }
    
    /**
     * Register REST API routes
     */
    public function registerRestRoutes() {
        register_rest_route('live-chatbot/v1', '/clients', array(
            'methods' => 'GET',
            'callback' => array($this, 'restGetClients'),
            'permission_callback' => array($this, 'checkRestPermissions')
        ));
        
        register_rest_route('live-chatbot/v1', '/client/(?P<id>[a-zA-Z0-9_]+)/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'restGetClientMessages'),
            'permission_callback' => array($this, 'checkRestPermissions')
        ));
        
        register_rest_route('live-chatbot/v1', '/message', array(
            'methods' => 'POST',
            'callback' => array($this, 'restSendMessage'),
            'permission_callback' => array($this, 'checkRestPermissions')
        ));
    }
    
    /**
     * Check REST API permissions
     */
    public function checkRestPermissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * REST API: Get clients
     */
    public function restGetClients($request) {
        $clients = $this->getAllClients();
        return rest_ensure_response($clients);
    }
    
    /**
     * REST API: Get client messages
     */
    public function restGetClientMessages($request) {
        $client_id = $request->get_param('id');
        $messages = $this->getMessages($client_id);
        return rest_ensure_response($messages);
    }
    
    /**
     * REST API: Send message
     */
    public function restSendMessage($request) {
        $client_id = $request->get_param('client_id');
        $message = $request->get_param('message');
        $sender = $request->get_param('sender');
        
        $message_id = $this->saveMessage($client_id, $sender, $message);
        
        if ($message_id) {
            return rest_ensure_response(array(
                'success' => true,
                'message_id' => $message_id
            ));
        } else {
            return new WP_Error('save_failed', 'Failed to save message', array('status' => 500));
        }
    }
    
    /**
     * Save client information
     */
    private function saveClientInfo($client_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'chatbot_clients';
        $ip_address = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE client_id = %s",
            $client_id
        ));
        
        if (!$existing) {
            $wpdb->insert(
                $table,
                array(
                    'client_id' => $client_id,
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent,
                    'status' => 'online'
                ),
                array('%s', '%s', '%s', '%s')
            );
        } else {
            $wpdb->update(
                $table,
                array(
                    'last_visit' => current_time('mysql'),
                    'status' => 'online'
                ),
                array('client_id' => $client_id),
                array('%s', '%s'),
                array('%s')
            );
        }
    }
    
    /**
     * Save message
     */
    private function saveMessage($client_id, $sender, $message) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'chatbot_messages';
        
        $result = $wpdb->insert(
            $table,
            array(
                'client_id' => $client_id,
                'sender' => $sender,
                'message' => $message,
                'support_user_id' => ($sender === 'support') ? get_current_user_id() : null
            ),
            array('%s', '%s', '%s', '%d')
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get messages for a client
     */
    private function getMessages($client_id, $last_message_id = 0) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'chatbot_messages';
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE client_id = %s AND id > %d ORDER BY timestamp ASC",
            $client_id,
            $last_message_id
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get all clients
     */
    private function getAllClients() {
        global $wpdb;
        
        $clients_table = $wpdb->prefix . 'chatbot_clients';
        $messages_table = $wpdb->prefix . 'chatbot_messages';
        
        $query = "
            SELECT 
                c.*,
                (SELECT message FROM $messages_table m WHERE m.client_id = c.client_id ORDER BY timestamp DESC LIMIT 1) as last_message,
                (SELECT timestamp FROM $messages_table m WHERE m.client_id = c.client_id ORDER BY timestamp DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM $messages_table m WHERE m.client_id = c.client_id) as message_count
            FROM $clients_table c
            ORDER BY c.last_visit DESC
        ";
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get client conversation history
     */
    private function getClientHistory($client_id) {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'chatbot_messages';
        
        $query = $wpdb->prepare(
            "SELECT * FROM $messages_table WHERE client_id = %s ORDER BY timestamp ASC",
            $client_id
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

// Initialize the plugin
LiveChatBotPlugin::getInstance(); 