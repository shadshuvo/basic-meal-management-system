# 🍽️ Meal.us.to - Meal Management System

A comprehensive meal management system for shared living spaces! Track daily meals, manage cancellations, handle guest meals, and keep everyone organized. Designed for mostly bachelor homes🏠

## ✨ Features

- 📅 **Daily Meal Tracking** - Morning and night meal planning with calendar view
- 🚫 **Smart Meal Cancellations** - Cancel meals before deadlines with automatic restrictions
- 👥 **Guest Management** - Add guest meals for any day with admin controls
- 📊 **Monthly Summaries** - Detailed monthly reports with meal counts and statistics
- 📈 **Daily Reports** - View comprehensive daily meal status for all users
- 🛒 **Market/Bazar Management** - Log and track market expenses with date records
- 📋 **User History** - Individual meal history tracking with detailed records
- 🔐 **Admin Panel** - Full administrative controls for meal and user management
- 📱 **Responsive Design** - Optimized for mobile, tablet, and desktop
- 🔒 **Password Hashing** - Secure password generation and authentication system
- 📢 **Notice Board** - Admin-managed announcements and updates
- 🖼️ **Custom Backgrounds** - Upload and manage background images

## 🔒 Password Generation

The system includes a built-in password hashing utility:

```php
// Generate secure password hash
$password = "your_password";
$hashed = password_hash($password, PASSWORD_DEFAULT);
```

**To generate a new password hash:**
1. Use the password hashing function in PHP
2. Update the hash in `users.json`
3. Secure authentication with bcrypt hashing

## 🚀 Quick Start

1. **Setup Files**
   ```bash
   # Upload all files to your web server
   # Set proper permissions
   chmod 755 data/ market_data/ img/
   chmod 644 users.json
   ```

2. **Default Admin Access**
   ```
   Username: admin
   Password: Check users.json for hashed password
   ```

3. **Access Points**
   - 🏠 **Main Login**: `index.php`
   - 📊 **Dashboard**: `dashboard.php`
   - 👔 **Admin Panel**: `admin.php`
   - 📈 **Reports**: `monthly_summary.php`

## 📁 Project Structure

```
📦 meal.us.to/
├── 🏠 index.php              # Login & authentication
├── 📊 dashboard.php          # Main user dashboard
├── 👔 admin.php              # Admin control panel
├── 📈 monthly_summary.php    # Monthly meal reports
├── 📅 daily_summary.php      # Daily meal overview
├── 🛒 market.php             # Market expense tracking
├── 👤 user_history.php       # Individual user history
├── 🔐 users.json             # User credentials & roles
├── 🎨 style.css              # Styling & responsive design
├── 📁 data/                  # Monthly meal data storage
├── 📁 market_data/           # Market expense records
├── 📁 img/                   # Background images
└── 📄 README.md              # This documentation
```

## 🎯 How It Works

### ⏰ Meal Deadlines
- **Morning Meals**: Must cancel before **10:00 AM**
- **Night Meals**: Must cancel before **6:00 PM**
- **Automatic Restrictions**: System prevents late cancellations

### 👥 User Roles
- 👑 **Admin**: Full system access, user management, meal controls
- 👤 **Regular User**: Personal meal management, view reports

### 📊 Data Management
- ✅ **Active Meals**: Confirmed daily meals
- ❌ **Cancelled Meals**: User-cancelled with timestamps
- 👥 **Guest Meals**: Additional meals for visitors
- 💰 **Market Data**: Expense tracking with dates

## 🛠️ Admin Features

### 👑 Administrative Controls
- ➕ **Add Guest Meals** - For any user on any date
- 🚫 **Cancel User Meals** - Override user meal status
- 👤 **User Management** - Add/modify user accounts
- 🖼️ **Image Upload** - Custom background management
- 📢 **Notice Management** - System-wide announcements
- 📊 **Full Reports** - Complete system analytics

### 🔧 System Management
- 📅 **Calendar Overview** - Monthly meal grid view
- 📈 **Statistics Dashboard** - User activity tracking
- 🔒 **Security Controls** - Password and access management

## 📱 User Interface

### 🎨 Responsive Design
- 📱 **Mobile Optimized** - Touch-friendly interface
- 📊 **Tablet Compatible** - Perfect tablet experience
- 💻 **Desktop Full-Featured** - Complete functionality

### 🌈 Visual Features
- 🖼️ **Dynamic Backgrounds** - Admin-uploadable images
- 📅 **Interactive Calendar** - Click-to-manage meals
- 🎯 **Status Indicators** - Clear meal status display
- 📊 **Data Visualization** - Charts and summaries

## 🔐 Security Features

### 🛡️ Authentication
- 🔒 **Password Hashing** - Bcrypt secure hashing
- 🔑 **Session Management** - Secure login sessions
- 👮 **Role-Based Access** - Admin vs user permissions
- ⏰ **Deadline Enforcement** - Time-based restrictions

### 📋 Data Protection
- 📁 **Structured Storage** - JSON-based data management
- 🔒 **Access Controls** - File permission security
- 📝 **Audit Trail** - Action logging and tracking

## 🚀 Installation Guide

1. **Server Requirements**
   - PHP 7.4+ with JSON support
   - Web server (Apache/Nginx)
   - File write permissions

2. **Setup Steps**
   ```bash
   # 1. Upload files to web directory
   # 2. Set permissions
   chmod 755 data/ market_data/ img/
   chmod 644 users.json *.php *.css
   
   # 3. Access via web browser
   # 4. Login with admin credentials
   ```

3. **Initial Configuration**
   - Update `users.json` with your users
   - Set admin password using password hashing
   - Configure notice board messages
   - Upload custom background images

## 📖 Usage Guide

### 👤 For Regular Users
1. **Login** with your credentials
2. **View Calendar** - See monthly meal overview
3. **Cancel Meals** - Before deadline restrictions
4. **Check History** - Review your meal records
5. **View Reports** - Monthly summaries and statistics

### 👑 For Administrators
1. **Access Admin Panel** - Full system control
2. **Manage Users** - Add guests, cancel meals
3. **Upload Content** - Backgrounds and notices
4. **Generate Reports** - Comprehensive analytics
5. **Monitor System** - User activity and data

## 🛠️ Customization

### 🎨 Theming
- Edit `style.css` for custom styling
- Upload background images via admin panel
- Modify color schemes and layouts

### ⚙️ Configuration
- Update meal deadlines in PHP files
- Modify user roles in `users.json`
- Adjust report formats and data display

## 📊 Data Structure

### 📁 File Organization
- **`data/`** - Monthly meal data (JSON format)
- **`market_data/`** - Market expense records
- **`img/`** - Background image storage
- **`users.json`** - User accounts and authentication

### 📋 Data Format
```json
{
  "users": {
    "username": {
      "password": "hashed_password",
      "role": "admin|user"
    }
  }
}
```

## 🎉 Getting Started

1. **🔐 Login** with your credentials
2. **📅 Browse** the monthly meal calendar
3. **🚫 Cancel** meals before deadlines
4. **👥 Add** guest meals as needed
5. **📊 Review** monthly summaries
6. **🛒 Track** market expenses
7. **📈 Monitor** your meal history

## 🤝 Support & Maintenance

- 📧 **Issues**: Check file permissions and PHP errors
- 🔄 **Updates**: Regular backup of data folders
- 🛡️ **Security**: Keep PHP updated and secure file permissions
- 📝 **Logs**: Monitor system for any authentication issues

---

## 📝 Technical Notes

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **Session Security**: Secure session management with role verification
- **Data Storage**: JSON-based file system for simplicity
- **Responsive Design**: CSS Grid and Flexbox for all devices
- **Error Handling**: Graceful error management throughout system

---

*🍽️ Made with ❤️ for shared living spaces - Keep your meals organized and your house happy! ✨*