# WordPress Support Hub - Premium Client Portal

A sophisticated web platform for WordPress support services with real-time client conversation tracking, impressive 3D animations, and mobile-responsive design.

![WordPress Support Hub](https://img.shields.io/badge/WordPress-Support%20Hub-blue)
![Version](https://img.shields.io/badge/version-1.0.0-green)
![License](https://img.shields.io/badge/license-GPL%20v2%2B-orange)

## 🚀 Features

### Web Interface Features
- **3D Animated Loading Screen** with rotating sphere and floating shapes
- **Professional Connection Form** with WordPress credentials input
- **Real-time Dashboard** displaying client conversations by IP address
- **Interactive Chat Interface** with message history and client management
- **Mobile-First Responsive Design** supporting all devices
- **Glassmorphism Effects** with backdrop filters and modern animations
- **Live Chat Notifications** with sound alerts and visual indicators

### WordPress Plugin Features
- **Complete Database Integration** with three structured tables
- **Client IP Tracking** with online/offline status monitoring
- **Message Storage System** with sender identification and timestamps
- **AJAX/REST API Endpoints** for external dashboard communication
- **Admin Dashboard** with statistics and conversation management
- **Chat Widget** with emoji support and file attachments
- **Customizable Themes** and positioning options
- **Security Implementation** with nonces and permission checks

## 📁 Project Structure

```
├── index.html                                 # Main web interface
├── styles.css                                 # Complete CSS with animations
├── script.js                                  # JavaScript functionality
├── wordpress-plugin/
│   ├── live-chatbot-plugin.php               # Main plugin file
│   ├── assets/
│   │   ├── chatbot.css                       # Widget styles
│   │   └── chatbot.js                        # Widget functionality
│   └── templates/
│       ├── chat-widget.php                   # Frontend widget template
│       └── admin-dashboard.php               # Admin dashboard template
└── README.md                                 # This file
```

## 🛠️ Installation Guide

### 1. Web Interface Setup

1. **Upload Files**
   ```bash
   # Upload these files to your web server
   - index.html
   - styles.css
   - script.js
   ```

2. **Server Requirements**
   - Web server (Apache/Nginx)
   - PHP 7.4+ (for WordPress integration)
   - Modern browser support

3. **Access the Platform**
   - Open `index.html` in your browser
   - Enter WordPress credentials to connect
   - Start managing client conversations

### 2. WordPress Plugin Installation

1. **Upload Plugin**
   ```bash
   # Upload the wordpress-plugin folder to your WordPress plugins directory
   wp-content/plugins/live-chatbot-plugin/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Live ChatBot Support Plugin"
   - Click "Activate"

3. **Database Setup**
   ```sql
   # Tables are created automatically on activation:
   - wp_chatbot_clients
   - wp_chatbot_messages  
   - wp_chatbot_conversations
   ```

4. **Plugin Configuration**
   - Go to WordPress Admin → Live ChatBot
   - Configure widget settings
   - Customize appearance and behavior

### 3. Integration Setup

1. **API Connection**
   ```javascript
   // Update script.js with your WordPress site details
   const WORDPRESS_API_URL = 'https://your-site.com/wp-json/live-chatbot/v1/';
   const WORDPRESS_AJAX_URL = 'https://your-site.com/wp-admin/admin-ajax.php';
   ```

2. **CORS Configuration** (if needed)
   ```php
   // Add to your WordPress theme's functions.php
   header('Access-Control-Allow-Origin: https://your-support-domain.com');
   header('Access-Control-Allow-Credentials: true');
   ```

## 🎨 Customization Guide

### Theme Customization

1. **CSS Variables**
   ```css
   :root {
       --primary-color: #6366f1;        /* Main brand color */
       --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
       --font-family: 'Inter', sans-serif;
   }
   ```

2. **Animation Settings**
   ```css
   .loading-sphere {
       animation-duration: 2s;           /* Loading animation speed */
   }
   
   .floating-shapes .shape-1 {
       animation-duration: 25s;          /* Background animation speed */
   }
   ```

### Widget Customization

1. **Position & Appearance**
   ```php
   // WordPress Admin → Live ChatBot → Settings
   'chat_position' => 'bottom-right',   // bottom-left, top-right, top-left
   'chat_theme' => 'modern',            // default, modern, minimal
   'chat_title' => 'Live Support',
   ```

2. **Feature Toggles**
   ```php
   'enable_emoji' => true,
   'enable_sound' => true,
   'enable_file_upload' => false,
   'auto_show_chat' => false,
   ```

## 📊 Database Schema

### Clients Table (`wp_chatbot_clients`)
```sql
- id (PRIMARY KEY)
- client_id (UNIQUE)
- name, email
- ip_address
- user_agent
- first_visit, last_visit
- status (online/offline)
- session_data (JSON)
```

### Messages Table (`wp_chatbot_messages`)
```sql
- id (PRIMARY KEY)
- client_id (FOREIGN KEY)
- sender (user/support)
- message (TEXT)
- timestamp
- is_read
- support_user_id
```

### Conversations Table (`wp_chatbot_conversations`)
```sql
- id (PRIMARY KEY)
- client_id (FOREIGN KEY)
- subject
- status (open/closed/pending)
- priority (low/medium/high)
- created_at, updated_at, closed_at
- assigned_to
```

## 🔧 API Endpoints

### REST API Routes
```
GET    /wp-json/live-chatbot/v1/clients
GET    /wp-json/live-chatbot/v1/client/{id}/messages
POST   /wp-json/live-chatbot/v1/message
```

### AJAX Actions
```
live_chat_send_message
live_chat_get_messages
live_chat_get_clients
live_chat_get_client_history
```

## 🎯 Usage Examples

### Basic Connection
```javascript
// Connect to WordPress site
const connection = await supportHub.handleConnection();
// Load client conversations
await supportHub.loadClients();
// Open specific client chat
supportHub.openChat(clientData);
```

### Send Message (Frontend)
```javascript
LiveChatBot.sendMessage('Hello, I need help with my account');
```

### Admin Response (Backend)
```php
wp_ajax_live_chat_send_message();
// Message automatically appears in both admin dashboard and client widget
```

## 🔒 Security Features

- **Nonce Verification** for all AJAX requests
- **User Permission Checks** for admin functions
- **SQL Injection Prevention** with prepared statements
- **XSS Protection** with input sanitization
- **CSRF Protection** with WordPress nonces

## 📱 Mobile Responsiveness

- **Breakpoints**: 768px, 480px
- **Touch-Friendly** interface elements
- **Optimized Animations** for mobile performance
- **Responsive Grid Layouts**
- **Mobile-First CSS** approach

## 🎨 Animation Features

### 3D Effects
- Rotating sphere loader
- Floating background shapes
- Perspective transforms
- CSS 3D transforms

### Micro-Interactions
- Button hover effects
- Message slide animations
- Typing indicators
- Status pulse animations

## 🛠️ Development Setup

### Prerequisites
```bash
# Required tools
- Modern web browser
- WordPress development environment
- PHP 7.4+
- MySQL 5.7+
```

### Development Mode
```bash
# Enable debug mode in WordPress
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

# Enable AJAX debugging
define('SCRIPT_DEBUG', true);
```

## 🐛 Troubleshooting

### Common Issues

1. **Connection Failed**
   - Check WordPress URL format
   - Verify user credentials
   - Ensure plugin is activated

2. **Messages Not Appearing**
   - Check database tables exist
   - Verify AJAX URL is correct
   - Check browser console for errors

3. **Widget Not Showing**
   - Confirm plugin is activated
   - Check widget settings in admin
   - Verify no CSS conflicts

### Debug Mode
```javascript
// Enable debug logging
localStorage.setItem('liveChatbotDebug', 'true');
```

## 📞 Support & Contributing

### Getting Help
- Check the troubleshooting section
- Review WordPress debug logs
- Inspect browser console for errors

### Contributing
1. Fork the repository
2. Create feature branch
3. Make changes with tests
4. Submit pull request

## 📄 License

This project is licensed under the GPL v2 or later - see the WordPress Plugin License for details.

## 🚀 Future Enhancements

- **Real-time WebSocket** connection
- **Multi-language Support** 
- **Advanced Analytics** dashboard
- **Automated Responses** with AI
- **Video Chat Integration**
- **Screen Sharing** capabilities
- **Ticket System** integration

## 📈 Performance Optimizations

- **Lazy Loading** for images and scripts
- **CSS/JS Minification** in production
- **Database Query** optimization
- **Caching Strategy** implementation
- **CDN Integration** support

---

**Made with ❤️ for WordPress Support Teams**

For questions or support, please refer to the documentation or create an issue in the repository. 