# Maneli Car Inquiry Core

A powerful WordPress plugin for managing car purchase inquiries with integrated Finotex API support and comprehensive admin dashboard features.

## 📋 Overview

Maneli Car Inquiry Core is a comprehensive WordPress plugin designed to streamline the car purchasing inquiry process. It provides users with tools to make informed decisions through loan calculations, credit reports, and installment inquiries, while giving administrators full control over the inquiry management system.

## ✨ Key Features

### For Users
- **Loan Calculator** - Calculate monthly payments and loan details
- **Car Inquiry Forms** - Submit detailed car purchase inquiries
- **Cash & Installment Options** - Support for both payment methods
- **Credit Reports** - Access to integrated credit report system
- **OTP Verification** - Secure login with one-time passwords
- **User Dashboard** - Personal inquiry history and tracking
- **Multi-language Support** - Farsi and English localization

### For Administrators
- **Dashboard Widgets** - Quick overview of inquiries and statistics
- **Inquiry Management** - Complete CRUD operations for inquiries
- **SMS & Telegram Notifications** - Real-time alerts for new submissions
- **Email Integration** - Automated email notifications
- **Expert Panel** - Specialist review and response system
- **Visitor Statistics** - Track user engagement and behavior
- **Custom Product Editor** - Manage car inventory
- **User Roles & Capabilities** - Fine-grained permission control
- **Settings Page** - Comprehensive plugin configuration
- **Credit Report Integration** - Connected reporting system

## 🚀 Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Steps

1. **Download the Plugin**
   ```bash
   cd /path/to/wp-content/plugins/
   git clone https://github.com/arsalanarghavan/maneli-car-inquiry.git
   ```

2. **Activate the Plugin**
   - Go to WordPress Admin Dashboard
   - Navigate to Plugins → Installed Plugins
   - Find "Maneli Car Inquiry Core" and click "Activate"

3. **Configure Settings**
   - Go to Settings → Maneli Car Inquiry
   - Configure API keys and integrations
   - Set up SMS and Telegram credentials
   - Configure email settings

## 📦 File Structure

```
maneli-car-inquiry/
├── includes/                          # Core plugin classes
│   ├── class-maneli-car-inquiry.php   # Main plugin class
│   ├── class-maneli-database.php      # Database operations
│   ├── class-maneli-session.php       # Session management
│   ├── class-maneli-auth.php          # Authentication
│   ├── class-form-handler.php         # Form submission handling
│   ├── class-sms-handler.php          # SMS notifications
│   ├── class-telegram-handler.php     # Telegram notifications
│   ├── class-email-handler.php        # Email handling
│   ├── class-shortcode-handler.php    # Shortcodes
│   ├── admin/                         # Admin-specific classes
│   ├── helpers/                       # Utility functions
│   ├── libs/                          # Third-party libraries
│   └── public/                        # Public-facing classes
├── templates/                         # PHP templates
│   ├── dashboard/                     # User dashboard templates
│   ├── public/                        # Public-facing templates
│   └── admin/                         # Admin templates
├── assets/                            # Frontend assets
│   ├── css/                          # Stylesheets
│   ├── js/                           # JavaScript files
│   ├── images/                       # Image assets
│   ├── fonts/                        # Custom fonts
│   ├── icon-fonts/                   # Icon font packages
│   └── video/                        # Video assets
├── languages/                         # Translation files (.po, .mo)
├── admin/                            # Admin-specific files
├── public/                           # Public-facing files
└── maneli-car-inquiry.php            # Main plugin file
```

## 🔧 Core Classes

### Main Classes
- **`class-maneli-car-inquiry.php`** - Main plugin controller
- **`class-maneli-database.php`** - Database schema and operations
- **`class-maneli-loader.php`** - Hook and action loader
- **`class-maneli-activator.php`** - Plugin activation logic
- **`class-maneli-deactivator.php`** - Plugin deactivation logic

### Feature Classes
- **`class-form-handler.php`** - Handles form submissions and validation
- **`class-shortcode-handler.php`** - Registers and manages shortcodes
- **`class-dashboard-handler.php`** - User dashboard functionality
- **`class-admin-dashboard-widgets.php`** - Admin dashboard widgets
- **`class-notification-handler.php`** - Push notifications
- **`class-sms-handler.php`** - SMS integration
- **`class-telegram-handler.php`** - Telegram bot integration
- **`class-email-handler.php`** - Email notifications

### Integration Classes
- **`class-maneli-auth.php`** - Authentication and authorization
- **`class-maneli-session.php`** - Session management
- **`class-maneli-license.php`** - License verification
- **`class-credit-report-page.php`** - Credit report integration
- **`class-roles-caps.php`** - User roles and capabilities

## 🎯 Available Shortcodes

- `[maneli-inquiry-form]` - Display car inquiry form
- `[maneli-loan-calculator]` - Display loan calculator
- `[maneli-cash-inquiry]` - Display cash inquiry form
- `[maneli-installment-inquiry]` - Display installment inquiry form
- `[maneli-credit-report]` - Display credit report page
- `[maneli-dashboard]` - Display user dashboard
- `[maneli-login]` - Display login form

## 🌐 Localization

The plugin supports multiple languages:
- **English** (en_US)
- **Farsi/Persian** (fa_IR)

Translation files are located in `/languages/` directory.

## 🔐 Security

- Nonce verification for all forms
- User capability checks for admin functions
- Secure password hashing using WordPress standards
- SQL injection prevention through prepared statements
- XSS protection with proper escaping
- CSRF token validation

## 📡 API Integration

### Finotex API
The plugin integrates with Finotex API for:
- Car inquiry processing
- Loan calculation
- Installment plans
- Credit report generation

### SMS Service
- Configurable SMS provider integration
- OTP delivery for two-factor authentication
- Inquiry status notifications

### Telegram Bot
- Real-time notifications
- Two-way communication
- Inquiry updates and alerts

## 🛠️ Development

### Setting Up Development Environment

1. Clone the repository
2. Install dependencies (if any)
3. Create a `.env` file with configuration
4. Initialize the database with required tables

### Code Style

The plugin follows WordPress coding standards:
- PHP PSR-2 conventions
- WordPress PHP standards
- Proper use of WordPress hooks and filters
- Object-oriented programming principles

### Running Tests

```bash
# Run unit tests (if available)
composer test

# Run linting
composer lint
```

## 📝 Hooks and Filters

The plugin provides multiple hooks for customization:

```php
// Actions
do_action('maneli_inquiry_submitted', $inquiry_data);
do_action('maneli_user_registered', $user_id);
do_action('maneli_dashboard_loaded', $user_id);

// Filters
apply_filters('maneli_inquiry_form_fields', $fields);
apply_filters('maneli_notification_content', $content, $inquiry_id);
apply_filters('maneli_dashboard_data', $data, $user_id);
```

## 🐛 Debugging

Enable debug logging by adding to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Logs will be written to `/wp-content/debug.log`

## 📄 License

This plugin is licensed under the GPL v2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

## 👤 Author

**Arsalan Arghavan**
- Website: [arsalanarghavan.ir](https://arsalanarghavan.ir)
- Email: Contact via website

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. Before submitting:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📞 Support

For issues, feature requests, or questions:
- Open an issue on GitHub
- Contact the author through the website

## 📈 Version History

### v1.1.0
- Latest stable release
- Enhanced dashboard features
- Improved notification system
- Better error handling

### v0.2.20
- Previous stable version

## 🙏 Acknowledgments

- Built with WordPress best practices
- Integrates with Finotex API
- Community-driven development

---

**Made with ❤️ by Arsalan Arghavan**
