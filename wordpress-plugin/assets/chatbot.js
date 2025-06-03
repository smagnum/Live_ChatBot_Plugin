/*
 * Live ChatBot Widget JavaScript
 * Handles chat functionality, real-time messaging, and animations
 */

(function() {
    'use strict';

    class LiveChatBot {
        constructor() {
            this.isOpen = false;
            this.isMinimized = false;
            this.clientId = liveChatbot.clientId;
            this.lastMessageId = 0;
            this.pollInterval = null;
            this.typingTimeout = null;
            this.soundEnabled = liveChatbot.settings.enableSound;
            this.emojiPickerOpen = false;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.startPolling();
            this.initializeWidget();
        }

        initializeWidget() {
            // Show widget after a delay
            setTimeout(() => {
                this.showWidget();
            }, 1000);
        }

        showWidget() {
            const widget = document.getElementById('live-chatbot-widget');
            if (widget) {
                widget.style.display = 'block';
            }
        }

        bindEvents() {
            // Toggle button events
            const toggle = document.getElementById('chatbotToggle');
            if (toggle) {
                toggle.addEventListener('click', () => {
                    if (this.isOpen) {
                        this.closeChat();
                    } else {
                        this.openChat();
                    }
                });
            }

            // Close and minimize buttons
            const closeBtn = document.getElementById('chatbotClose');
            const minimizeBtn = document.getElementById('chatbotMinimize');
            
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.closeChat());
            }
            
            if (minimizeBtn) {
                minimizeBtn.addEventListener('click', () => this.minimizeChat());
            }

            // Input events
            const input = document.getElementById('chatbotInput');
            const sendBtn = document.getElementById('chatbotSendBtn');
            
            if (input) {
                input.addEventListener('input', () => this.handleInput());
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
                
                // Auto-resize textarea
                input.addEventListener('input', () => {
                    this.autoResizeTextarea(input);
                });
            }
            
            if (sendBtn) {
                sendBtn.addEventListener('click', () => this.sendMessage());
            }

            // File upload events
            const attachBtn = document.getElementById('chatbotAttachBtn');
            const fileInput = document.getElementById('chatbotFileInput');
            
            if (attachBtn && fileInput) {
                attachBtn.addEventListener('click', () => {
                    fileInput.click();
                });
                
                fileInput.addEventListener('change', (e) => {
                    this.handleFileUpload(e.target.files);
                });
            }

            // Emoji picker events
            const emojiBtn = document.getElementById('chatbotEmojiBtn');
            const emojiPicker = document.getElementById('chatbotEmojiPicker');
            
            if (emojiBtn && emojiPicker) {
                emojiBtn.addEventListener('click', () => this.toggleEmojiPicker());
                
                // Emoji category switching
                const emojiCategories = emojiPicker.querySelectorAll('.emoji-category');
                emojiCategories.forEach(category => {
                    category.addEventListener('click', (e) => {
                        this.switchEmojiCategory(e.target.dataset.category);
                    });
                });
                
                // Emoji selection
                const emojis = emojiPicker.querySelectorAll('.emoji');
                emojis.forEach(emoji => {
                    emoji.addEventListener('click', (e) => {
                        this.insertEmoji(e.target.dataset.emoji);
                    });
                });
            }

            // Close emoji picker when clicking outside
            document.addEventListener('click', (e) => {
                if (this.emojiPickerOpen && !e.target.closest('.chatbot-emoji-picker') && !e.target.closest('.chatbot-emoji-btn')) {
                    this.closeEmojiPicker();
                }
            });

            // Window resize handling
            window.addEventListener('resize', () => this.handleResize());
        }

        openChat() {
            const window = document.getElementById('chatbotWindow');
            const toggle = document.getElementById('chatbotToggle');
            
            if (window && toggle) {
                this.isOpen = true;
                this.isMinimized = false;
                
                window.style.display = 'block';
                window.classList.add('active');
                toggle.classList.add('active');
                
                // Focus on input
                setTimeout(() => {
                    const input = document.getElementById('chatbotInput');
                    if (input) {
                        input.focus();
                    }
                }, 300);
                
                // Scroll to bottom
                this.scrollToBottom();
            }
        }

        closeChat() {
            const window = document.getElementById('chatbotWindow');
            const toggle = document.getElementById('chatbotToggle');
            const emojiPicker = document.getElementById('chatbotEmojiPicker');
            
            if (window && toggle) {
                this.isOpen = false;
                this.isMinimized = false;
                
                window.classList.remove('active');
                toggle.classList.remove('active');
                
                if (emojiPicker) {
                    emojiPicker.style.display = 'none';
                    this.emojiPickerOpen = false;
                }
                
                setTimeout(() => {
                    window.style.display = 'none';
                }, 300);
            }
        }

        minimizeChat() {
            const window = document.getElementById('chatbotWindow');
            
            if (window) {
                this.isMinimized = true;
                window.classList.remove('active');
                
                setTimeout(() => {
                    window.style.display = 'none';
                }, 300);
            }
        }

        handleInput() {
            const input = document.getElementById('chatbotInput');
            const sendBtn = document.getElementById('chatbotSendBtn');
            
            if (input && sendBtn) {
                const hasContent = input.value.trim().length > 0;
                sendBtn.disabled = !hasContent;
                
                if (hasContent) {
                    sendBtn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                } else {
                    sendBtn.style.background = '#e2e8f0';
                }
            }
        }

        autoResizeTextarea(textarea) {
            textarea.style.height = 'auto';
            const newHeight = Math.min(textarea.scrollHeight, 100);
            textarea.style.height = newHeight + 'px';
        }

        async sendMessage() {
            const input = document.getElementById('chatbotInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add message to UI immediately
            this.addMessage(message, 'user');
            input.value = '';
            this.handleInput();
            
            // Show typing indicator
            this.showTypingIndicator();
            
            try {
                const response = await fetch(liveChatbot.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'live_chat_send_message',
                        nonce: liveChatbot.nonce,
                        client_id: this.clientId,
                        message: message,
                        sender: 'user'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.lastMessageId = data.data.message_id;
                    
                    // Simulate support response (in real implementation, this would come from polling)
                    setTimeout(() => {
                        this.hideTypingIndicator();
                        this.simulateSupportResponse();
                    }, 1000 + Math.random() * 2000);
                } else {
                    throw new Error(data.data || 'Failed to send message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                this.hideTypingIndicator();
                this.showErrorMessage('Failed to send message. Please try again.');
            }
        }

        addMessage(content, sender, timestamp = null) {
            const messagesContainer = document.getElementById('chatbotMessages');
            if (!messagesContainer) return;
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `chatbot-message chatbot-message-${sender}`;
            
            const time = timestamp ? new Date(timestamp) : new Date();
            const timeString = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            const avatarIcon = sender === 'user' ? 'fas fa-user' : 'fas fa-robot';
            
            messageDiv.innerHTML = `
                <div class="chatbot-message-avatar">
                    <i class="${avatarIcon}"></i>
                </div>
                <div class="chatbot-message-content">
                    <div class="chatbot-message-bubble">
                        ${this.escapeHtml(content)}
                    </div>
                    <div class="chatbot-message-time">
                        ${timeString}
                    </div>
                </div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            this.scrollToBottom();
            
            // Play notification sound for incoming messages
            if (sender !== 'user' && this.soundEnabled) {
                this.playNotificationSound();
            }
        }

        showTypingIndicator() {
            const indicator = document.getElementById('chatbotTyping');
            if (indicator) {
                indicator.style.display = 'flex';
                this.scrollToBottom();
            }
        }

        hideTypingIndicator() {
            const indicator = document.getElementById('chatbotTyping');
            if (indicator) {
                indicator.style.display = 'none';
            }
        }

        simulateSupportResponse() {
            const responses = [
                "Thank you for your message! I'll help you right away.",
                "I understand your concern. Let me assist you with that.",
                "That's a great question! Here's what I can tell you...",
                "I'm here to help! Can you provide more details about your issue?",
                "Thanks for reaching out. I'll look into this for you.",
                "I appreciate your patience. Let me find the best solution for you."
            ];
            
            const response = responses[Math.floor(Math.random() * responses.length)];
            this.addMessage(response, 'bot');
        }

        showErrorMessage(message) {
            const messagesContainer = document.getElementById('chatbotMessages');
            if (!messagesContainer) return;
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'chatbot-message chatbot-message-error';
            errorDiv.innerHTML = `
                <div class="chatbot-message-content">
                    <div class="chatbot-message-bubble" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;">
                        ${this.escapeHtml(message)}
                    </div>
                </div>
            `;
            
            messagesContainer.appendChild(errorDiv);
            this.scrollToBottom();
        }

        scrollToBottom() {
            const messagesContainer = document.getElementById('chatbotMessages');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        toggleEmojiPicker() {
            const picker = document.getElementById('chatbotEmojiPicker');
            if (!picker) return;
            
            if (this.emojiPickerOpen) {
                this.closeEmojiPicker();
            } else {
                picker.style.display = 'block';
                this.emojiPickerOpen = true;
                
                // Animate in
                picker.style.opacity = '0';
                picker.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    picker.style.transition = 'all 0.2s ease';
                    picker.style.opacity = '1';
                    picker.style.transform = 'translateY(0)';
                }, 10);
            }
        }

        closeEmojiPicker() {
            const picker = document.getElementById('chatbotEmojiPicker');
            if (!picker) return;
            
            picker.style.opacity = '0';
            picker.style.transform = 'translateY(10px)';
            
            setTimeout(() => {
                picker.style.display = 'none';
                this.emojiPickerOpen = false;
            }, 200);
        }

        switchEmojiCategory(category) {
            const categories = document.querySelectorAll('.emoji-category');
            const contents = document.querySelectorAll('.emoji-category-content');
            
            categories.forEach(cat => cat.classList.remove('active'));
            contents.forEach(content => content.style.display = 'none');
            
            const activeCategory = document.querySelector(`[data-category="${category}"]`);
            const activeContent = document.querySelector(`[data-category="${category}"].emoji-category-content`);
            
            if (activeCategory) activeCategory.classList.add('active');
            if (activeContent) activeContent.style.display = 'grid';
        }

        insertEmoji(emoji) {
            const input = document.getElementById('chatbotInput');
            if (!input) return;
            
            const cursorPos = input.selectionStart;
            const textBefore = input.value.substring(0, cursorPos);
            const textAfter = input.value.substring(input.selectionEnd);
            
            input.value = textBefore + emoji + textAfter;
            input.selectionStart = input.selectionEnd = cursorPos + emoji.length;
            
            this.handleInput();
            input.focus();
            this.closeEmojiPicker();
        }

        handleFileUpload(files) {
            const preview = document.getElementById('chatbotFilePreview');
            const uploadArea = document.querySelector('.chatbot-file-upload');
            
            if (!files.length || !preview || !uploadArea) return;
            
            uploadArea.style.display = 'block';
            preview.innerHTML = '';
            
            Array.from(files).forEach(file => {
                const item = document.createElement('div');
                item.className = 'file-preview-item';
                item.innerHTML = `
                    <i class="fas fa-file"></i>
                    <span>${this.escapeHtml(file.name)}</span>
                    <button class="file-preview-remove" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                preview.appendChild(item);
            });
        }

        playNotificationSound() {
            const audio = document.getElementById('chatbotNotificationSound');
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {
                    // Ignore audio play errors (usually due to autoplay restrictions)
                });
            }
        }

        startPolling() {
            this.pollInterval = setInterval(() => {
                this.pollForMessages();
            }, 3000); // Poll every 3 seconds
        }

        async pollForMessages() {
            try {
                const response = await fetch(liveChatbot.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'live_chat_get_messages',
                        nonce: liveChatbot.nonce,
                        client_id: this.clientId,
                        last_message_id: this.lastMessageId
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(message => {
                        if (message.sender === 'support') {
                            this.addMessage(message.message, 'bot', message.timestamp);
                            this.lastMessageId = Math.max(this.lastMessageId, parseInt(message.id));
                        }
                    });
                }
            } catch (error) {
                console.error('Error polling for messages:', error);
            }
        }

        handleResize() {
            // Handle responsive behavior
            const window = document.getElementById('chatbotWindow');
            if (!window) return;
            
            if (window.innerWidth <= 480) {
                window.style.width = 'calc(100vw - 40px)';
                window.style.height = 'calc(100vh - 140px)';
            } else {
                window.style.width = '360px';
                window.style.height = '500px';
            }
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        destroy() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }
            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
            }
        }
    }

    // Initialize the chatbot when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.LiveChatBot = new LiveChatBot();
        });
    } else {
        window.LiveChatBot = new LiveChatBot();
    }

    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        if (window.LiveChatBot) {
            window.LiveChatBot.destroy();
        }
    });

})(); 