<?php
/**
 * Chat Widget Template
 * This template renders the frontend chat widget
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$settings = array(
    'position' => get_option('live_chatbot_chat_position', 'bottom-right'),
    'title' => get_option('live_chatbot_chat_title', 'Live Support'),
    'welcomeMessage' => get_option('live_chatbot_welcome_message', 'Hello! How can we help you today?'),
    'offlineMessage' => get_option('live_chatbot_offline_message', 'We are currently offline. Please leave a message.'),
    'enableEmoji' => get_option('live_chatbot_enable_emoji', true),
    'enableSound' => get_option('live_chatbot_enable_sound', true),
    'autoShow' => get_option('live_chatbot_auto_show_chat', false)
);

$position_class = 'chatbot-' . str_replace('-', '_', $settings['position']);
?>

<!-- Live ChatBot Widget -->
<div id="live-chatbot-widget" class="live-chatbot-widget <?php echo esc_attr($position_class); ?>" style="display: none;">
    
    <!-- Chat Button -->
    <div class="chatbot-toggle" id="chatbotToggle">
        <div class="chatbot-icon">
            <i class="fas fa-comments"></i>
        </div>
        <div class="chatbot-close-icon" style="display: none;">
            <i class="fas fa-times"></i>
        </div>
        <?php if (!empty($settings['title'])): ?>
        <div class="chatbot-tooltip">
            <?php echo esc_html($settings['title']); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Chat Window -->
    <div class="chatbot-window" id="chatbotWindow" style="display: none;">
        <!-- Header -->
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <div class="chatbot-avatar">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="chatbot-header-text">
                    <h4><?php echo esc_html($settings['title']); ?></h4>
                    <span class="chatbot-status">
                        <span class="status-indicator online"></span>
                        We're online
                    </span>
                </div>
            </div>
            <div class="chatbot-header-actions">
                <button class="chatbot-minimize" id="chatbotMinimize">
                    <i class="fas fa-minus"></i>
                </button>
                <button class="chatbot-close" id="chatbotClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="chatbot-messages" id="chatbotMessages">
            <!-- Welcome Message -->
            <div class="chatbot-message chatbot-message-bot">
                <div class="chatbot-message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-message-content">
                    <div class="chatbot-message-bubble">
                        <?php echo esc_html($settings['welcomeMessage']); ?>
                    </div>
                    <div class="chatbot-message-time">
                        <?php echo current_time('H:i'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div class="chatbot-typing" id="chatbotTyping" style="display: none;">
            <div class="chatbot-typing-avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="chatbot-typing-content">
                <div class="chatbot-typing-bubble">
                    <div class="chatbot-typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="chatbot-input-area">
            <!-- File Upload Area (if enabled) -->
            <?php if (get_option('live_chatbot_enable_file_upload', false)): ?>
            <div class="chatbot-file-upload" style="display: none;">
                <input type="file" id="chatbotFileInput" accept="<?php echo esc_attr(get_option('live_chatbot_allowed_file_types', 'image/*,.pdf,.doc,.docx')); ?>">
                <div class="chatbot-file-preview" id="chatbotFilePreview"></div>
            </div>
            <?php endif; ?>

            <!-- Input Controls -->
            <div class="chatbot-input-controls">
                <?php if (get_option('live_chatbot_enable_file_upload', false)): ?>
                <button class="chatbot-attach-btn" id="chatbotAttachBtn" title="Attach file">
                    <i class="fas fa-paperclip"></i>
                </button>
                <?php endif; ?>
                
                <?php if ($settings['enableEmoji']): ?>
                <button class="chatbot-emoji-btn" id="chatbotEmojiBtn" title="Add emoji">
                    <i class="fas fa-smile"></i>
                </button>
                <?php endif; ?>
            </div>

            <!-- Input Field -->
            <div class="chatbot-input-wrapper">
                <textarea 
                    id="chatbotInput" 
                    class="chatbot-input" 
                    placeholder="Type your message..."
                    rows="1"
                    maxlength="1000"></textarea>
                <button class="chatbot-send-btn" id="chatbotSendBtn" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="chatbot-footer">
            <div class="chatbot-powered-by">
                <span>Powered by <strong>Live ChatBot</strong></span>
            </div>
        </div>
    </div>

    <!-- Emoji Picker (if enabled) -->
    <?php if ($settings['enableEmoji']): ?>
    <div class="chatbot-emoji-picker" id="chatbotEmojiPicker" style="display: none;">
        <div class="chatbot-emoji-categories">
            <button class="emoji-category active" data-category="smileys">😀</button>
            <button class="emoji-category" data-category="people">👍</button>
            <button class="emoji-category" data-category="objects">📱</button>
            <button class="emoji-category" data-category="symbols">❤️</button>
        </div>
        <div class="chatbot-emoji-grid">
            <div class="emoji-category-content" data-category="smileys">
                <span class="emoji" data-emoji="😀">😀</span>
                <span class="emoji" data-emoji="😃">😃</span>
                <span class="emoji" data-emoji="😄">😄</span>
                <span class="emoji" data-emoji="😁">😁</span>
                <span class="emoji" data-emoji="😊">😊</span>
                <span class="emoji" data-emoji="😇">😇</span>
                <span class="emoji" data-emoji="🙂">🙂</span>
                <span class="emoji" data-emoji="😉">😉</span>
                <span class="emoji" data-emoji="😍">😍</span>
                <span class="emoji" data-emoji="🥰">🥰</span>
                <span class="emoji" data-emoji="😘">😘</span>
                <span class="emoji" data-emoji="😗">😗</span>
                <span class="emoji" data-emoji="😙">😙</span>
                <span class="emoji" data-emoji="😚">😚</span>
                <span class="emoji" data-emoji="😋">😋</span>
                <span class="emoji" data-emoji="😛">😛</span>
                <span class="emoji" data-emoji="😜">😜</span>
                <span class="emoji" data-emoji="🤪">🤪</span>
                <span class="emoji" data-emoji="😝">😝</span>
                <span class="emoji" data-emoji="🤑">🤑</span>
            </div>
            <div class="emoji-category-content" data-category="people" style="display: none;">
                <span class="emoji" data-emoji="👍">👍</span>
                <span class="emoji" data-emoji="👎">👎</span>
                <span class="emoji" data-emoji="👌">👌</span>
                <span class="emoji" data-emoji="✌️">✌️</span>
                <span class="emoji" data-emoji="🤞">🤞</span>
                <span class="emoji" data-emoji="🤟">🤟</span>
                <span class="emoji" data-emoji="🤘">🤘</span>
                <span class="emoji" data-emoji="🤙">🤙</span>
                <span class="emoji" data-emoji="👈">👈</span>
                <span class="emoji" data-emoji="👉">👉</span>
                <span class="emoji" data-emoji="👆">👆</span>
                <span class="emoji" data-emoji="👇">👇</span>
                <span class="emoji" data-emoji="☝️">☝️</span>
                <span class="emoji" data-emoji="✋">✋</span>
                <span class="emoji" data-emoji="🤚">🤚</span>
                <span class="emoji" data-emoji="🖐️">🖐️</span>
                <span class="emoji" data-emoji="🖖">🖖</span>
                <span class="emoji" data-emoji="👋">👋</span>
                <span class="emoji" data-emoji="🤝">🤝</span>
                <span class="emoji" data-emoji="🙏">🙏</span>
            </div>
            <div class="emoji-category-content" data-category="objects" style="display: none;">
                <span class="emoji" data-emoji="📱">📱</span>
                <span class="emoji" data-emoji="💻">💻</span>
                <span class="emoji" data-emoji="🖥️">🖥️</span>
                <span class="emoji" data-emoji="⌨️">⌨️</span>
                <span class="emoji" data-emoji="🖱️">🖱️</span>
                <span class="emoji" data-emoji="🖲️">🖲️</span>
                <span class="emoji" data-emoji="💾">💾</span>
                <span class="emoji" data-emoji="💿">💿</span>
                <span class="emoji" data-emoji="📀">📀</span>
                <span class="emoji" data-emoji="📼">📼</span>
                <span class="emoji" data-emoji="📷">📷</span>
                <span class="emoji" data-emoji="📸">📸</span>
                <span class="emoji" data-emoji="📹">📹</span>
                <span class="emoji" data-emoji="🎥">🎥</span>
                <span class="emoji" data-emoji="📽️">📽️</span>
                <span class="emoji" data-emoji="🎞️">🎞️</span>
                <span class="emoji" data-emoji="📞">📞</span>
                <span class="emoji" data-emoji="☎️">☎️</span>
                <span class="emoji" data-emoji="📟">📟</span>
                <span class="emoji" data-emoji="📠">📠</span>
            </div>
            <div class="emoji-category-content" data-category="symbols" style="display: none;">
                <span class="emoji" data-emoji="❤️">❤️</span>
                <span class="emoji" data-emoji="🧡">🧡</span>
                <span class="emoji" data-emoji="💛">💛</span>
                <span class="emoji" data-emoji="💚">💚</span>
                <span class="emoji" data-emoji="💙">💙</span>
                <span class="emoji" data-emoji="💜">💜</span>
                <span class="emoji" data-emoji="🖤">🖤</span>
                <span class="emoji" data-emoji="🤍">🤍</span>
                <span class="emoji" data-emoji="🤎">🤎</span>
                <span class="emoji" data-emoji="💔">💔</span>
                <span class="emoji" data-emoji="❣️">❣️</span>
                <span class="emoji" data-emoji="💕">💕</span>
                <span class="emoji" data-emoji="💞">💞</span>
                <span class="emoji" data-emoji="💓">💓</span>
                <span class="emoji" data-emoji="💗">💗</span>
                <span class="emoji" data-emoji="💖">💖</span>
                <span class="emoji" data-emoji="💘">💘</span>
                <span class="emoji" data-emoji="💝">💝</span>
                <span class="emoji" data-emoji="💟">💟</span>
                <span class="emoji" data-emoji="☮️">☮️</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sound notification (if enabled) -->
    <?php if ($settings['enableSound']): ?>
    <audio id="chatbotNotificationSound" preload="auto">
        <source src="<?php echo LIVE_CHATBOT_PLUGIN_URL; ?>assets/sounds/notification.mp3" type="audio/mpeg">
        <source src="<?php echo LIVE_CHATBOT_PLUGIN_URL; ?>assets/sounds/notification.ogg" type="audio/ogg">
    </audio>
    <?php endif; ?>
</div>

<script>
// Auto-show chat if enabled
<?php if ($settings['autoShow']): ?>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        if (typeof LiveChatBot !== 'undefined') {
            LiveChatBot.showWidget();
        }
    }, 3000); // Show after 3 seconds
});
<?php endif; ?>

// Initialize chat widget when page loads
document.addEventListener('DOMContentLoaded', function() {
    const widget = document.getElementById('live-chatbot-widget');
    if (widget) {
        widget.style.display = 'block';
        
        // Add entrance animation
        widget.style.opacity = '0';
        widget.style.transform = 'translateY(20px)';
        
        setTimeout(function() {
            widget.style.transition = 'all 0.3s ease';
            widget.style.opacity = '1';
            widget.style.transform = 'translateY(0)';
        }, 500);
    }
});
</script> 