# RentFinder SL - Complete Rental Property Management System

A comprehensive web application for managing rental properties in Sri Lanka, connecting tenants with property owners through a secure and user-friendly platform with integrated payment processing and admin management.

## 🚀 Features

### For Tenants
- **User Registration & Login** with SMS OTP verification
- **Property Search & Filtering** by location, price, type, and amenities
- **Property Visit Requests** with scheduling and confirmation
- **Rental Agreement Management** with digital contracts
- **Secure Payment Processing** via PayHere integration
- **Payment History & Tracking** with downloadable receipts
- **Recurring Payment Setup** for automatic rent payments

### For Property Owners
- **Property Listing & Management** with photo uploads and detailed descriptions
- **Visit Request Management** with confirmation and scheduling
- **Rental Agreement Creation** with customizable terms
- **Payment Settlement Tracking** with commission management
- **Property Analytics** with performance metrics
- **Tenant Communication** through the platform

### For Administrators
- **Comprehensive Admin Panel** with dashboard and analytics
- **User Management** with role-based access control
- **Property Verification** and approval system
- **Payment Monitoring** with transaction tracking
- **Commission Management** with automated calculations
- **System Analytics** with detailed reports
- **Content Management** for site-wide settings

## 🛠 Technology Stack

- **Backend**: PHP 7.4+ (Pure PHP, No MVC Framework)
- **Database**: MySQL 5.7+ with optimized schema
- **Frontend**: Bootstrap 5, HTML5, CSS3, JavaScript
- **Payment Gateway**: PayHere integration with webhook support
- **SMS Integration**: Dialog/Mobitel API with OTP verification
- **Security**: CSRF protection, XSS prevention, SQL injection protection
- **Responsive Design**: Mobile-first approach with Bootstrap

## 📋 System Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache 2.4+ or Nginx 1.18+
- SSL Certificate (for production)
- 512MB RAM minimum
- 1GB free storage space

### PHP Extensions
- PDO MySQL
- GD (for image processing)
- cURL
- OpenSSL
- JSON
- mbstring

## 🚀 Quick Start

### 1. Installation
```bash
# Clone the repository
git clone https://github.com/yourusername/rentfinder-sl.git

# Navigate to project directory
cd rentfinder-sl

# Set up web server (XAMPP/WAMP/LAMP)
# Place files in web server directory
```

### 2. Database Setup
```bash
# Run the database setup script
php setup_database.php

# Or manually import the SQL file
mysql -u username -p database_name < database_schema.sql
```

### 3. Configuration
```php
// Update config/database.php
$host = 'localhost';
$dbname = 'rentfinder_sl';
$username = 'your_username';
$password = 'your_password';
```

### 4. PayHere Integration
```php
// Configure PayHere in includes/payhere.php
$payhereConfig = [
    'merchant_id' => 'YOUR_MERCHANT_ID',
    'merchant_secret' => 'YOUR_MERCHANT_SECRET',
    'sandbox_mode' => true, // Set to false for production
];
```

### 5. SMS Configuration
```php
// Set up SMS provider in includes/functions.php
// Configure Dialog or Mobitel API credentials
```

## 📱 Responsive Design

The application is fully responsive and optimized for:
- **Desktop** (1200px+)
- **Tablet** (768px - 1199px)
- **Mobile** (320px - 767px)

## 💳 Payment Integration

### PayHere Features
- **Secure Payment Processing** with SSL encryption
- **Multiple Payment Methods** (Credit/Debit cards, Bank transfers)
- **Recurring Payments** for monthly rent
- **Webhook Integration** for real-time payment updates
- **Payment Verification** with hash validation
- **Transaction History** with detailed logging

### Supported Payment Methods
- Visa/Mastercard credit and debit cards
- Local bank transfers
- Mobile payments
- Digital wallets

## 🔐 Security Features

- **SMS OTP Verification** for account security
- **Password Hashing** using PHP's PASSWORD_DEFAULT
- **CSRF Protection** with token validation
- **Input Sanitization** to prevent XSS attacks
- **SQL Injection Prevention** using prepared statements
- **Session Management** with secure cookies
- **File Upload Security** with type and size validation

## 📊 Admin Panel Features

### Dashboard
- **Real-time Statistics** with user, property, and payment counts
- **Revenue Tracking** with monthly and yearly reports
- **Recent Activity** monitoring
- **System Health** indicators

### User Management
- **User Registration** approval
- **Role Management** (Tenant, Owner, Admin)
- **Account Status** control
- **User Analytics** and reporting

### Property Management
- **Property Verification** and approval
- **Listing Management** with bulk operations
- **Photo Moderation** and approval
- **Location Verification** and mapping

### Payment Management
- **Transaction Monitoring** in real-time
- **Payment Reconciliation** and reporting
- **Commission Tracking** and calculations
- **Refund Processing** and management

## 📈 Analytics & Reporting

### User Analytics
- Registration trends and patterns
- User engagement metrics
- Geographic distribution
- Role-based statistics

### Property Analytics
- Listing performance metrics
- Search and view statistics
- Conversion rates
- Popular locations and price ranges

### Financial Analytics
- Revenue tracking and forecasting
- Commission calculations
- Payment success rates
- Transaction volume analysis

## 🔧 Configuration Options

### Application Settings
- Site name and branding
- Email templates and notifications
- SMS provider configuration
- Payment gateway settings
- File upload limits and types

### Security Settings
- Session timeout configuration
- Password policy enforcement
- OTP expiry and attempt limits
- Rate limiting for API endpoints

## 📚 Documentation

- **User Guide**: Complete user manual for tenants and property owners
- **Technical Setup**: Detailed installation and configuration guide
- **API Documentation**: Integration guides for developers
- **Troubleshooting**: Common issues and solutions

## 🚀 Deployment

### Production Checklist
- [ ] SSL certificate installed
- [ ] Database credentials updated
- [ ] PayHere production credentials configured
- [ ] SMS provider production API keys set
- [ ] File permissions configured
- [ ] Error logging enabled
- [ ] Backup procedures implemented

### Performance Optimization
- Database indexing for faster queries
- Image optimization and compression
- CDN integration for static assets
- Caching implementation
- Database query optimization

## 🧪 Testing

### Test Coverage
- Unit tests for core functions
- Integration tests for payment processing
- User acceptance testing
- Security vulnerability testing
- Performance testing

### Test Data
- Sample users (tenants, owners, admin)
- Test properties with various configurations
- Mock payment transactions
- Test SMS and email notifications

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Contact Information
- **Email**: support@rentfinder.lk
- **Phone**: +94 11 234 5678
- **Website**: https://rentfinder.lk

### Business Hours
- **Monday - Friday**: 9:00 AM - 6:00 PM
- **Saturday**: 9:00 AM - 1:00 PM
- **Sunday**: Closed

### Emergency Support
- **24/7 Hotline**: +94 11 234 5679
- **Critical Issues**: Available 24/7

## 🗺 Roadmap

### Version 2.0 (Planned)
- Mobile app development
- Advanced search filters
- Property comparison tool
- Enhanced analytics dashboard
- Multi-language support

### Version 2.1 (Future)
- AI-powered property recommendations
- Virtual property tours
- Blockchain-based contracts
- IoT integration for smart properties

## 🙏 Acknowledgments

- Bootstrap team for the excellent CSS framework
- PayHere for payment gateway integration
- Dialog and Mobitel for SMS services
- Open source community for various libraries and tools

---

**RentFinder SL** - Making rental property management simple, secure, and efficient in Sri Lanka.