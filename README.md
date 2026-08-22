# MCPIL Pharmaceutical Laboratory Management System

A comprehensive web-based operational management system for MCPIL Pharmaceutical Laboratory, built with PHP and MySQL.

## Features

### Core Modules
1. **Dashboard** - Overview with statistics and recent activities
2. **Purchase Order Management** - Create and manage purchase orders
3. **Purchase Invoice System** - Generate and track invoices
4. **Employee Profile Management** - Manage employee information
5. **Attendance Monitoring** - Track employee attendance
6. **Delivery Tracking** - Real-time delivery monitoring
7. **Delivery History** - Historical delivery records and analytics
8. **Reports System** - Generate comprehensive reports

### Key Features
- **User Authentication** - Secure login and registration system
- **Role-based Access** - Admin, Manager, and Employee roles
- **Real-time Dashboard** - Live statistics and updates
- **Responsive Design** - Works on desktop, tablet, and mobile
- **Data Analytics** - Comprehensive reporting and insights
- **Modern UI** - Clean, professional interface with Bootstrap 5

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP (for local development)

## Installation Instructions

### 1. Setup Database
1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database named `mcpillab`
4. Import the `database.sql` file located in the project root

### 2. Configure Application
1. Copy the project files to `C:\xampp\htdocs\mcpillab\`
2. Update database configuration in `config.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'mcpillab');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### 3. Access the Application
1. Open your web browser
2. Navigate to: `http://localhost/mcpillab`
3. Login with default admin credentials:
   - Username: `admin`
   - Password: `admin123`

## Default Users

### Administrator Account
- **Username**: admin
- **Password**: admin123
- **Role**: admin
- **Access**: Full system access

### Sample Employees
The system includes 3 sample employees:
1. Alice Williams (Lab Technician)
2. Bob Johnson (QC Manager)
3. Carol Davis (Purchasing Officer)

## User Roles and Permissions

### Administrator
- Full system access
- User management
- System configuration
- All module access

### Manager
- Purchase order approval
- Employee management
- Report generation
- Delivery tracking

### Employee
- View assigned tasks
- Attendance marking
- Basic reporting
- Limited access

## Module Descriptions

### Dashboard
- Real-time statistics
- Recent purchase orders
- Active deliveries
- Today's attendance
- Quick navigation

### Purchase Order Management
- Create new purchase orders
- Add multiple items per order
- Supplier management
- Order status tracking
- PO generation with unique numbers

### Purchase Invoice System
- Generate invoices from approved POs
- Tax calculation
- Payment status tracking
- Invoice numbering
- Supplier integration

### Employee Profile Management
- Add/edit employee information
- Department management
- Position tracking
- Salary information
- Employee ID generation

### Attendance Monitoring
- Daily attendance recording
- Bulk attendance entry
- Check-in/check-out tracking
- Attendance reports
- Status management (Present, Absent, Late, Half-day)

### Delivery Tracking
- Create delivery records
- Real-time status updates
- Tracking number management
- Carrier information
- Delivery timeline

### Delivery History
- Historical delivery records
- Advanced filtering
- Performance analytics
- Export capabilities
- Statistical insights

### Reports System
- Purchase order reports
- Attendance reports
- Financial summaries
- Custom date ranges
- Export to Excel/PDF

## File Structure

```
mcpillab/
├── config.php              # Database and application configuration
├── functions.php           # Core functions and utilities
├── index.php               # Login and registration page
├── dashboard.php           # Main dashboard
├── purchase_order.php      # Purchase order management
├── purchase_invoice.php    # Invoice management
├── employee_profile.php    # Employee management
├── attendance.php          # Attendance monitoring
├── delivery_tracking.php   # Delivery tracking
├── delivery_history.php    # Delivery history
├── reports.php             # Reports and analytics
├── logout.php              # Logout functionality
├── database.sql            # Database schema and sample data
└── README.md               # This file
```

## Security Features

- Password hashing with PHP's built-in functions
- SQL injection prevention with prepared statements
- Session management
- Input sanitization
- Role-based access control

## Browser Compatibility

- Google Chrome (Recommended)
- Mozilla Firefox
- Microsoft Edge
- Safari

## Support

For technical support or questions:
1. Check the error logs in XAMPP
2. Verify database connection settings
3. Ensure all PHP extensions are enabled
4. Check file permissions

## License

This project is developed for MCPIL Pharmaceutical Laboratory internal use.

---

**Note**: This system is designed specifically for pharmaceutical laboratory operations and includes industry-specific features and workflows.
# mcpillab-system
