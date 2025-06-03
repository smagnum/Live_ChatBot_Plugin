/*
 * WordPress Support Hub JavaScript
 * Real backend integration with PHP API
 */

class WordPressSupportHub {
    constructor() {
        this.apiUrl = 'api/'; // API base URL
        this.token = localStorage.getItem('wp_support_token');
        this.sessionId = localStorage.getItem('wp_support_session');
        this.clients = [];
        this.selectedClient = null;
        this.isConnected = false;
        this.refreshInterval = null;
        this.testingMode = false; // Add testing mode flag
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.showLoadingScreen();
        
        // Check if already authenticated
        if (this.token) {
            this.validateSession();
        } else {
            this.hideLoadingScreen();
        }
    }

    bindEvents() {
        // Connection form
        const connectionForm = document.getElementById('wpConnectionForm');
        if (connectionForm) {
            connectionForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleConnection();
            });
        }

        // Dashboard events
        const refreshBtn = document.getElementById('refreshBtn');
        const disconnectBtn = document.getElementById('disconnectBtn');
        
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadClients());
        }
        
        if (disconnectBtn) {
            disconnectBtn.addEventListener('click', () => this.disconnect());
        }

        // Modal events
        window.closeChatModal = () => this.closeChatModal();
        window.sendMessage = () => this.sendMessage();
        
        // Message input
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }
    }

    showLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.style.display = 'flex';
        }
    }

    hideLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            setTimeout(() => {
                loadingScreen.style.opacity = '0';
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                }, 500);
            }, 1000);
        }
    }

    async validateSession() {
        console.log('🔍 Validating session...');
        console.log('📋 Token exists:', !!this.token);
        
        try {
            const response = await this.apiRequest('clients.php', {
                method: 'GET'
            });

            console.log('📊 Session validation response:', response);

            if (response.success) {
                console.log('✅ Session valid, showing dashboard');
                this.isConnected = true;
                this.showDashboard(response.data.wordpress_site);
                this.loadClients();
            } else {
                console.log('❌ Session invalid:', response.error);
                throw new Error('Session invalid');
            }
        } catch (error) {
            console.error('💥 Session validation failed:', error);
            this.clearSession();
            this.hideLoadingScreen();
        }
    }

    async handleConnection() {
        const form = document.getElementById('wpConnectionForm');
        const connectBtn = document.getElementById('connectBtn');
        
        // Get form data
        const formData = new FormData(form);
        const wpUrl = formData.get('wpUrl').trim();
        const wpUsername = formData.get('wpUsername').trim();
        const wpPassword = formData.get('wpPassword');

        // Validate input
        if (!wpUrl || !wpUsername || !wpPassword) {
            this.showError('Please fill in all required fields');
            return;
        }

        // Show loading state
        connectBtn.classList.add('loading');
        connectBtn.disabled = true;

        try {
            // Use the working connect-simple.php endpoint directly
            console.log('🔌 Connecting using simplified endpoint...');
            const response = await this.apiRequest('connect-simple.php', {
                method: 'POST',
                body: JSON.stringify({
                    wpUrl: wpUrl,
                    wpUsername: wpUsername,
                    wpPassword: wpPassword
                })
            });

            console.log('📡 Connection response:', response);

            if (response.success) {
                // Store authentication data
                this.token = response.data.token;
                this.sessionId = response.data.session_id;
                
                localStorage.setItem('wp_support_token', this.token);
                localStorage.setItem('wp_support_session', this.sessionId);
                localStorage.setItem('wp_support_testing_mode', 'true');
                
                this.isConnected = true;
                
                console.log('✅ Connection successful, showing dashboard...');
                
                // Show success and transition to dashboard
                this.showSuccess('Connected successfully!');
                
                setTimeout(() => {
                    this.showDashboard(response.data.wordpress_site);
                    this.loadClients();
                }, 1500);
                
            } else {
                throw new Error(response.error || 'Connection failed');
            }

        } catch (error) {
            console.error('💥 Connection error:', error);
            
            // Show helpful error message
            let errorMessage = error.message || 'Failed to connect to WordPress site';
            
            if (errorMessage.includes('Invalid username or password')) {
                errorMessage += '\n\n🔧 Troubleshooting tips:\n';
                errorMessage += '• Check your WordPress admin credentials\n';
                errorMessage += '• Visit api/debug.php to run connection tests\n';
                errorMessage += '• Try disabling security plugins temporarily\n';
                errorMessage += '• Use application passwords if 2FA is enabled';
            }
            
            this.showError(errorMessage);
        } finally {
            connectBtn.classList.remove('loading');
            connectBtn.disabled = false;
        }
    }

    async loadClients() {
        console.log('📋 Loading clients...');
        console.log('🔗 Connected status:', this.isConnected);
        console.log('🎫 Token exists:', !!this.token);
        
        if (!this.isConnected) {
            console.log('⚠️ Not connected, skipping client load');
            return;
        }

        try {
            console.log('🌐 Making API request to clients.php...');
            const response = await this.apiRequest('clients.php', {
                method: 'GET'
            });

            console.log('📊 Clients response:', response);

            if (response.success) {
                console.log(`✅ Loaded ${response.data.clients.length} clients`);
                this.clients = response.data.clients;
                this.displayClients();
                this.updateStatistics(response.data.statistics);
                console.log('✅ Dashboard updated successfully');
            } else {
                console.log('❌ Failed to load clients:', response.error);
                throw new Error(response.error || 'Failed to load clients');
            }

        } catch (error) {
            console.error('💥 Error loading clients:', error);
            this.showError('Failed to load client data: ' + error.message);
        }
    }

    displayClients() {
        const clientsList = document.getElementById('clientsList');
        if (!clientsList) return;

        if (this.clients.length === 0) {
            clientsList.innerHTML = `
                <div class="no-clients">
                    <div class="no-clients-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>No clients found</h3>
                    <p>Client conversations will appear here when users interact with your WordPress site</p>
                </div>
            `;
            return;
        }

        const clientsHTML = this.clients.map(client => `
            <div class="client-item clickable-client" 
                 data-client-ip="${client.ip_address}"
                 style="cursor: pointer;"
                 title="Click to open conversation">
                <div class="client-avatar">
                    <div class="avatar-circle">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="status-indicator ${client.status}"></div>
                </div>
                
                <div class="client-info">
                    <div class="client-header">
                        <span class="client-name">${this.escapeHtml(client.name)}</span>
                        <span class="client-status ${client.status}">${client.status}</span>
                    </div>
                    <div class="client-details">
                        <span class="client-ip">
                            <i class="fas fa-map-marker-alt"></i>
                            ${client.ip_address}
                        </span>
                        <span class="message-count">
                            <i class="fas fa-comments"></i>
                            ${client.message_count} messages
                        </span>
                    </div>
                    ${client.last_message ? `
                        <div class="last-message">
                            "${this.escapeHtml(this.truncateText(client.last_message, 50))}"
                        </div>
                    ` : ''}
                    <div class="last-seen">
                        <i class="fas fa-clock"></i>
                        ${client.time_since_last_visit}
                    </div>
                </div>
                
                <div class="client-actions">
                    <button class="btn-icon chat-open-btn" 
                            data-client-ip="${client.ip_address}"
                            onclick="event.stopPropagation(); supportHub.openChat('${client.ip_address}')" 
                            title="Open Chat">
                        <i class="fas fa-comments"></i>
                    </button>
                </div>
            </div>
        `).join('');

        clientsList.innerHTML = clientsHTML;
        
        // Add click handlers to entire client items (main fix)
        const clientItems = clientsList.querySelectorAll('.clickable-client');
        clientItems.forEach(item => {
            item.addEventListener('click', (e) => {
                const clientIp = item.getAttribute('data-client-ip');
                console.log(`🔄 Client item clicked for IP: ${clientIp}`);
                
                // Add visual feedback
                item.style.backgroundColor = '#f0f9ff';
                setTimeout(() => {
                    item.style.backgroundColor = '';
                }, 200);
                
                if (this.openChat) {
                    this.openChat(clientIp).catch(error => {
                        console.error('❌ Error in openChat:', error);
                        this.showError('Failed to open conversation: ' + error.message);
                    });
                } else {
                    console.error('❌ openChat method not found!');
                    this.showError('Chat functionality not available');
                }
            });
        });
        
        // Add event listeners to chat buttons (backup method)
        const chatButtons = clientsList.querySelectorAll('.chat-open-btn');
        chatButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent triggering parent click
                const clientIp = button.getAttribute('data-client-ip');
                console.log(`🔄 Chat button clicked for IP: ${clientIp}`);
                
                if (this.openChat) {
                    this.openChat(clientIp).catch(error => {
                        console.error('❌ Error in openChat:', error);
                        this.showError('Failed to open conversation: ' + error.message);
                    });
                } else {
                    console.error('❌ openChat method not found!');
                    this.showError('Chat functionality not available');
                }
            });
        });
        
        console.log(`✅ Displayed ${this.clients.length} clients with click handlers (entire items clickable)`);
    }

    updateStatistics(stats) {
        const totalClients = document.getElementById('totalClients');
        const activeChats = document.getElementById('activeChats');
        
        if (totalClients) totalClients.textContent = stats.total_clients;
        if (activeChats) activeChats.textContent = stats.online_clients;
    }

    async openChat(clientIp) {
        console.log(`🔄 openChat called with IP: ${clientIp}`);
        
        try {
            console.log(`📡 Making API request to messages.php...`);
            const response = await this.apiRequest(`messages.php?client_ip=${encodeURIComponent(clientIp)}`, {
                method: 'GET'
            });

            console.log(`📊 API response:`, response);

            if (response.success) {
                console.log(`✅ Messages loaded successfully:`, response.data);
                
                this.selectedClient = {
                    ip: clientIp,
                    ...response.data.client
                };
                
                console.log(`👤 Selected client:`, this.selectedClient);
                console.log(`💬 Messages to display:`, response.data.messages.length);
                
                this.showChatModal(response.data.messages);
                
                // Start real-time polling for this conversation
                this.startMessagePolling();
            } else {
                throw new Error(response.error || 'Failed to load messages');
            }

        } catch (error) {
            console.error('💥 Error in openChat:', error);
            console.error('📋 Error details:', {
                message: error.message,
                stack: error.stack,
                clientIp: clientIp
            });
            this.showError('Failed to load conversation: ' + error.message);
        }
    }

    startMessagePolling() {
        // Clear any existing polling
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
        }
        
        // Poll for new messages every 2 seconds
        this.messagePollingInterval = setInterval(async () => {
            if (this.selectedClient && this.isConnected) {
                await this.checkForNewMessages();
            }
        }, 2000);
        
        console.log('🔄 Started real-time message polling');
    }
    
    stopMessagePolling() {
        if (this.messagePollingInterval) {
            clearInterval(this.messagePollingInterval);
            this.messagePollingInterval = null;
            console.log('⏹️ Stopped message polling');
        }
    }
    
    async checkForNewMessages() {
        if (!this.selectedClient) return;
        
        try {
            const response = await this.apiRequest(`messages.php?client_ip=${encodeURIComponent(this.selectedClient.ip)}`, {
                method: 'GET'
            });

            if (response.success) {
                const currentMessages = response.data.messages;
                const chatMessagesContainer = document.getElementById('chatMessages');
                
                if (chatMessagesContainer) {
                    const existingMessageCount = chatMessagesContainer.children.length;
                    
                    // If we have new messages, update the display
                    if (currentMessages.length > existingMessageCount) {
                        console.log(`📨 New messages detected: ${currentMessages.length - existingMessageCount} new`);
                        
                        // Clear and reload all messages to ensure correct order
                        const messagesHTML = currentMessages.map(message => `
                            <div class="message ${message.sender}">
                                <div class="message-content">
                                    ${this.escapeHtml(message.message)}
                                </div>
                                <div class="message-time">
                                    ${this.formatTime(message.timestamp)}
                                </div>
                            </div>
                        `).join('');

                        chatMessagesContainer.innerHTML = messagesHTML;
                        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
                        
                        // Also refresh the clients list to update last message
                        this.loadClients();
                    }
                }
            }
        } catch (error) {
            console.error('Error checking for new messages:', error);
        }
    }

    showChatModal(messages) {
        const modal = document.getElementById('chatModal');
        const clientName = document.getElementById('modalClientName');
        const clientIP = document.getElementById('modalClientIP');
        const clientStatus = document.getElementById('modalClientStatus');
        const chatMessages = document.getElementById('chatMessages');

        if (!modal || !this.selectedClient) return;

        // Update modal header
        if (clientName) clientName.textContent = this.selectedClient.name;
        if (clientIP) clientIP.textContent = `IP: ${this.selectedClient.ip}`;
        if (clientStatus) {
            clientStatus.textContent = this.selectedClient.status;
            clientStatus.className = `client-status ${this.selectedClient.status}`;
        }

        // Display messages
        if (chatMessages) {
            const messagesHTML = messages.map(message => `
                <div class="message ${message.sender}">
                    <div class="message-content">
                        ${this.escapeHtml(message.message)}
                    </div>
                    <div class="message-time">
                        ${this.formatTime(message.timestamp)}
                    </div>
                </div>
            `).join('');

            chatMessages.innerHTML = messagesHTML;
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Show modal
        modal.style.display = 'flex';
        modal.classList.add('active');

        // Focus on input
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.focus();
        }
    }

    async sendMessage() {
        const messageInput = document.getElementById('messageInput');
        if (!messageInput || !this.selectedClient) return;

        const message = messageInput.value.trim();
        if (!message) return;

        try {
            const response = await this.apiRequest('messages.php', {
                method: 'POST',
                body: JSON.stringify({
                    client_ip: this.selectedClient.ip,
                    message: message,
                    sender: 'support'
                })
            });

            if (response.success) {
                // Add message to chat immediately
                this.addMessageToChat({
                    sender: 'support',
                    message: message,
                    timestamp: new Date().toISOString()
                });

                // Clear input
                messageInput.value = '';
                
                // Refresh clients list to update last message
                this.loadClients();
                
            } else {
                throw new Error(response.error || 'Failed to send message');
            }

        } catch (error) {
            console.error('Error sending message:', error);
            this.showError('Failed to send message');
        }
    }

    addMessageToChat(message) {
        const chatMessages = document.getElementById('chatMessages');
        if (!chatMessages) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.sender}`;
        messageDiv.innerHTML = `
            <div class="message-content">
                ${this.escapeHtml(message.message)}
            </div>
            <div class="message-time">
                ${this.formatTime(message.timestamp)}
            </div>
        `;

        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    closeChatModal() {
        const modal = document.getElementById('chatModal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        this.selectedClient = null;
        
        // Stop message polling when modal is closed
        this.stopMessagePolling();
    }

    showDashboard(siteInfo) {
        // Hide loading screen first
        this.hideLoadingScreen();
        
        const connectionForm = document.getElementById('connectionForm');
        const dashboard = document.getElementById('dashboard');
        const siteName = document.getElementById('siteName');
        const siteUrl = document.getElementById('siteUrl');

        if (connectionForm) {
            connectionForm.style.opacity = '0';
            connectionForm.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                connectionForm.style.display = 'none';
                
                if (dashboard) {
                    dashboard.classList.remove('hidden');
                    dashboard.style.opacity = '0';
                    dashboard.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        dashboard.style.transition = 'all 0.6s ease';
                        dashboard.style.opacity = '1';
                        dashboard.style.transform = 'translateY(0)';
                    }, 100);
                }
            }, 300);
        }

        // Update site info
        if (siteName) siteName.textContent = this.extractDomainName(siteInfo.url);
        if (siteUrl) siteUrl.textContent = siteInfo.url;

        // Start auto-refresh
        this.startAutoRefresh();
    }

    startAutoRefresh() {
        // Refresh clients every 5 seconds for better real-time updates
        this.refreshInterval = setInterval(() => {
            if (this.isConnected) {
                this.loadClients();
            }
        }, 5000);
        
        console.log('🔄 Started auto-refresh every 5 seconds');
    }

    disconnect() {
        this.clearSession();
        // Stop all polling intervals
        this.stopMessagePolling();
        location.reload();
    }

    clearSession() {
        localStorage.removeItem('wp_support_token');
        localStorage.removeItem('wp_support_session');
        this.token = null;
        this.sessionId = null;
        this.isConnected = false;
        
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        // Stop message polling
        this.stopMessagePolling();
    }

    async apiRequest(endpoint, options = {}) {
        const url = this.apiUrl + endpoint;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            }
        };

        // Add authorization header if token exists
        if (this.token) {
            defaultOptions.headers.Authorization = `Bearer ${this.token}`;
        }

        const finalOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        const response = await fetch(url, finalOptions);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || `HTTP ${response.status}`);
        }

        return data;
    }

    showError(message) {
        // Create notification
        const notification = document.createElement('div');
        notification.className = 'notification error';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-exclamation-circle"></i>
                <span>${this.escapeHtml(message)}</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Show with animation
        setTimeout(() => notification.classList.add('show'), 100);

        // Auto hide after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    showSuccess(message) {
        // Create notification
        const notification = document.createElement('div');
        notification.className = 'notification success';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-check-circle"></i>
                <span>${this.escapeHtml(message)}</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Show with animation
        setTimeout(() => notification.classList.add('show'), 100);

        // Auto hide after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    extractDomainName(url) {
        try {
            const domain = new URL(url).hostname;
            return domain.replace(/^www\./, '');
        } catch {
            return url;
        }
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        // If less than 24 hours, show time
        if (diff < 24 * 60 * 60 * 1000) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // If less than 7 days, show day and time
        if (diff < 7 * 24 * 60 * 60 * 1000) {
            return date.toLocaleDateString([], { weekday: 'short', hour: '2-digit', minute: '2-digit' });
        }
        
        // Otherwise show full date
        return date.toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
}

// Password visibility toggle
function togglePassword() {
    const passwordInput = document.getElementById('wpPassword');
    const toggleBtn = document.querySelector('.password-toggle i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleBtn.className = 'fas fa-eye';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.supportHub = new WordPressSupportHub();
});

// CSS for notifications
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        z-index: 1000000;
        transform: translateX(400px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 4px solid #10b981;
        max-width: 400px;
    }
    
    .notification.error {
        border-left-color: #ef4444;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        color: #374151;
    }
    
    .notification.success .notification-content i {
        color: #10b981;
    }
    
    .notification.error .notification-content i {
        color: #ef4444;
    }
    
    .no-clients {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    
    .no-clients-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .no-clients h3 {
        margin: 0 0 8px 0;
        color: #374151;
    }
    
    .no-clients p {
        margin: 0;
        font-size: 14px;
    }
`;
document.head.appendChild(notificationStyles); 