<?php
/**
 * Admin Dashboard Template
 * Displays main dashboard with statistics and recent conversations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get statistics
$clients_table = $wpdb->prefix . 'chatbot_clients';
$messages_table = $wpdb->prefix . 'chatbot_messages';
$conversations_table = $wpdb->prefix . 'chatbot_conversations';

$total_clients = $wpdb->get_var("SELECT COUNT(*) FROM $clients_table");
$online_clients = $wpdb->get_var("SELECT COUNT(*) FROM $clients_table WHERE status = 'online'");
$total_messages = $wpdb->get_var("SELECT COUNT(*) FROM $messages_table");
$today_messages = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $messages_table WHERE DATE(timestamp) = %s", current_time('Y-m-d')));

// Recent conversations
$recent_conversations = $wpdb->get_results("
    SELECT 
        c.client_id,
        c.name,
        c.email,
        c.ip_address,
        c.status,
        c.last_visit,
        (SELECT message FROM $messages_table m WHERE m.client_id = c.client_id ORDER BY timestamp DESC LIMIT 1) as last_message,
        (SELECT timestamp FROM $messages_table m WHERE m.client_id = c.client_id ORDER BY timestamp DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM $messages_table m WHERE m.client_id = c.client_id) as message_count
    FROM $clients_table c
    WHERE c.last_visit >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY c.last_visit DESC
    LIMIT 10
");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="dashicons dashicons-format-chat"></i>
        Live ChatBot Dashboard
    </h1>
    
    <div class="chatbot-admin-header">
        <div class="chatbot-stats-cards">
            <!-- Total Clients -->
            <div class="chatbot-stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="dashicons dashicons-groups"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($total_clients); ?></div>
                    <div class="stat-label">Total Clients</div>
                </div>
            </div>
            
            <!-- Online Clients -->
            <div class="chatbot-stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="dashicons dashicons-admin-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($online_clients); ?></div>
                    <div class="stat-label">Online Now</div>
                    <div class="stat-trend">
                        <span class="status-indicator online"></span>
                        Active
                    </div>
                </div>
            </div>
            
            <!-- Total Messages -->
            <div class="chatbot-stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="dashicons dashicons-email-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($total_messages); ?></div>
                    <div class="stat-label">Total Messages</div>
                </div>
            </div>
            
            <!-- Today's Messages -->
            <div class="chatbot-stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <i class="dashicons dashicons-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($today_messages); ?></div>
                    <div class="stat-label">Today's Messages</div>
                    <div class="stat-trend">
                        <i class="dashicons dashicons-arrow-up-alt"></i>
                        +12% from yesterday
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="chatbot-admin-content">
        <div class="chatbot-main-section">
            <!-- Recent Conversations -->
            <div class="chatbot-panel">
                <div class="panel-header">
                    <h2>
                        <i class="dashicons dashicons-format-chat"></i>
                        Recent Conversations
                    </h2>
                    <div class="panel-actions">
                        <button class="button" id="refreshConversations">
                            <i class="dashicons dashicons-update"></i>
                            Refresh
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=live-chatbot-conversations'); ?>" class="button button-primary">
                            View All
                        </a>
                    </div>
                </div>
                
                <div class="conversations-list">
                    <?php if (empty($recent_conversations)): ?>
                        <div class="no-conversations">
                            <div class="no-conversations-icon">
                                <i class="dashicons dashicons-format-chat"></i>
                            </div>
                            <h3>No Recent Conversations</h3>
                            <p>When customers start chatting, their conversations will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_conversations as $conversation): ?>
                            <div class="conversation-item" data-client-id="<?php echo esc_attr($conversation->client_id); ?>">
                                <div class="conversation-avatar">
                                    <div class="avatar-circle">
                                        <i class="dashicons dashicons-admin-users"></i>
                                    </div>
                                    <div class="status-indicator <?php echo esc_attr($conversation->status); ?>"></div>
                                </div>
                                
                                <div class="conversation-content">
                                    <div class="conversation-header">
                                        <div class="client-info">
                                            <span class="client-name">
                                                <?php echo esc_html($conversation->name ?: 'Anonymous User'); ?>
                                            </span>
                                            <span class="client-ip">
                                                <?php echo esc_html($conversation->ip_address); ?>
                                            </span>
                                        </div>
                                        <div class="conversation-meta">
                                            <span class="message-count">
                                                <i class="dashicons dashicons-email-alt"></i>
                                                <?php echo number_format($conversation->message_count); ?>
                                            </span>
                                            <span class="last-seen">
                                                <?php echo human_time_diff(strtotime($conversation->last_visit), current_time('timestamp')); ?> ago
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($conversation->last_message): ?>
                                        <div class="last-message">
                                            "<?php echo esc_html(wp_trim_words($conversation->last_message, 15)); ?>"
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="conversation-actions">
                                    <button class="button button-small view-conversation" 
                                            data-client-id="<?php echo esc_attr($conversation->client_id); ?>"
                                            title="View Conversation">
                                        <i class="dashicons dashicons-visibility"></i>
                                    </button>
                                    <button class="button button-small reply-conversation"
                                            data-client-id="<?php echo esc_attr($conversation->client_id); ?>"
                                            title="Reply">
                                        <i class="dashicons dashicons-edit"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="chatbot-sidebar">
            <!-- Quick Actions -->
            <div class="chatbot-panel">
                <div class="panel-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=live-chatbot-settings'); ?>" class="quick-action">
                        <i class="dashicons dashicons-admin-settings"></i>
                        <span>Settings</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=live-chatbot-clients'); ?>" class="quick-action">
                        <i class="dashicons dashicons-groups"></i>
                        <span>View Clients</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=live-chatbot-conversations'); ?>" class="quick-action">
                        <i class="dashicons dashicons-format-chat"></i>
                        <span>All Conversations</span>
                    </a>
                    <button class="quick-action" onclick="exportData()">
                        <i class="dashicons dashicons-download"></i>
                        <span>Export Data</span>
                    </button>
                </div>
            </div>
            
            <!-- System Status -->
            <div class="chatbot-panel">
                <div class="panel-header">
                    <h3>System Status</h3>
                </div>
                <div class="system-status">
                    <div class="status-item">
                        <div class="status-label">Chat Widget</div>
                        <div class="status-value">
                            <?php if (get_option('live_chatbot_enable_chat', true)): ?>
                                <span class="status-active">Active</span>
                            <?php else: ?>
                                <span class="status-inactive">Disabled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-label">Database</div>
                        <div class="status-value">
                            <span class="status-active">Connected</span>
                        </div>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-label">Last Update</div>
                        <div class="status-value">
                            <?php echo human_time_diff(time() - 300); ?> ago
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="chatbot-panel">
                <div class="panel-header">
                    <h3>Recent Activity</h3>
                </div>
                <div class="activity-feed">
                    <?php
                    $recent_activity = $wpdb->get_results($wpdb->prepare("
                        SELECT 
                            m.*,
                            c.name,
                            c.ip_address
                        FROM $messages_table m
                        LEFT JOIN $clients_table c ON m.client_id = c.client_id
                        WHERE m.timestamp >= %s
                        ORDER BY m.timestamp DESC
                        LIMIT 5
                    ", date('Y-m-d H:i:s', strtotime('-1 hour'))));
                    ?>
                    
                    <?php if (empty($recent_activity)): ?>
                        <div class="no-activity">
                            <i class="dashicons dashicons-clock"></i>
                            <span>No recent activity</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php if ($activity->sender === 'user'): ?>
                                        <i class="dashicons dashicons-admin-users"></i>
                                    <?php else: ?>
                                        <i class="dashicons dashicons-admin-tools"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text">
                                        <?php if ($activity->sender === 'user'): ?>
                                            <strong><?php echo esc_html($activity->name ?: 'Anonymous'); ?></strong> sent a message
                                        <?php else: ?>
                                            Support replied to <strong><?php echo esc_html($activity->name ?: 'Anonymous'); ?></strong>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo human_time_diff(strtotime($activity->timestamp), current_time('timestamp')); ?> ago
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Conversation Modal -->
<div id="conversationModal" class="chatbot-modal" style="display: none;">
    <div class="chatbot-modal-content">
        <div class="chatbot-modal-header">
            <h2>Conversation Details</h2>
            <button class="chatbot-modal-close" onclick="closeConversationModal()">
                <i class="dashicons dashicons-no-alt"></i>
            </button>
        </div>
        <div class="chatbot-modal-body">
            <div id="conversationDetails">
                <!-- Conversation content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Admin Dashboard Styles */
.chatbot-admin-header {
    margin: 20px 0;
}

.chatbot-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.chatbot-stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.chatbot-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    margin-top: 4px;
}

.stat-trend {
    font-size: 12px;
    color: #10b981;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.chatbot-admin-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.chatbot-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 20px;
}

.panel-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
}

.panel-header h2,
.panel-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #1f2937;
}

.panel-actions {
    display: flex;
    gap: 8px;
}

.conversations-list {
    padding: 0;
}

.conversation-item {
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: background 0.2s ease;
    cursor: pointer;
}

.conversation-item:hover {
    background: #f9fafb;
}

.conversation-item:last-child {
    border-bottom: none;
}

.conversation-avatar {
    position: relative;
}

.avatar-circle {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid white;
}

.status-indicator.online {
    background: #10b981;
}

.status-indicator.offline {
    background: #6b7280;
}

.conversation-content {
    flex: 1;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.client-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.client-name {
    font-weight: 600;
    color: #1f2937;
}

.client-ip {
    font-size: 12px;
    color: #6b7280;
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 4px;
}

.conversation-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    color: #6b7280;
}

.message-count {
    display: flex;
    align-items: center;
    gap: 4px;
}

.last-message {
    font-size: 14px;
    color: #6b7280;
    font-style: italic;
}

.conversation-actions {
    display: flex;
    gap: 4px;
}

.no-conversations {
    padding: 60px 24px;
    text-align: center;
    color: #6b7280;
}

.no-conversations-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.quick-actions {
    padding: 20px;
    display: grid;
    gap: 12px;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s ease;
}

.quick-action:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #1f2937;
}

.system-status,
.activity-feed {
    padding: 20px;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.status-item:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: 500;
    color: #374151;
}

.status-active {
    color: #10b981;
    font-weight: 500;
}

.status-inactive {
    color: #ef4444;
    font-weight: 500;
}

.activity-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: 14px;
    color: #374151;
}

.activity-time {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.no-activity {
    text-align: center;
    color: #6b7280;
    padding: 20px 0;
}

/* Modal Styles */
.chatbot-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chatbot-modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.chatbot-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
}

.chatbot-modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #6b7280;
    padding: 4px;
}

.chatbot-modal-body {
    max-height: 60vh;
    overflow-y: auto;
    padding: 24px;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .chatbot-admin-content {
        grid-template-columns: 1fr;
    }
    
    .chatbot-stats-cards {
        grid-template-columns: 1fr;
    }
    
    .conversation-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .conversation-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Refresh conversations
    $('#refreshConversations').on('click', function() {
        location.reload();
    });
    
    // View conversation
    $('.view-conversation').on('click', function() {
        const clientId = $(this).data('client-id');
        loadConversationDetails(clientId);
    });
    
    // Reply to conversation
    $('.reply-conversation').on('click', function() {
        const clientId = $(this).data('client-id');
        // Redirect to conversations page with client selected
        window.location.href = '<?php echo admin_url('admin.php?page=live-chatbot-conversations&client='); ?>' + clientId;
    });
});

function loadConversationDetails(clientId) {
    // Show modal
    document.getElementById('conversationModal').style.display = 'flex';
    
    // Load conversation details via AJAX
    jQuery.post(ajaxurl, {
        action: 'live_chat_get_client_history',
        nonce: '<?php echo wp_create_nonce('live_chatbot_admin_nonce'); ?>',
        client_id: clientId
    }, function(response) {
        if (response.success) {
            displayConversationDetails(response.data);
        }
    });
}

function displayConversationDetails(messages) {
    const container = document.getElementById('conversationDetails');
    let html = '<div class="conversation-messages">';
    
    messages.forEach(message => {
        const time = new Date(message.timestamp).toLocaleString();
        const senderClass = message.sender === 'user' ? 'user' : 'support';
        
        html += `
            <div class="message-item message-${senderClass}">
                <div class="message-content">${message.message}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function closeConversationModal() {
    document.getElementById('conversationModal').style.display = 'none';
}

function exportData() {
    // Implement data export functionality
    alert('Export functionality will be implemented in the full version.');
}
</script> 