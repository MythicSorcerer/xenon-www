# Xenon Forum

A complete PHP-based forum system with user authentication, admin features, and anonymous posting capabilities.

## Features

### Core Forum Features
- **User Authentication**: Registration and login system
- **Thread Creation**: Users can create discussion threads
- **Post Replies**: Reply to threads with nested discussions
- **Anonymous Posting**: Support for anonymous users
- **Notifications**: Get notified when someone replies to your threads

### Admin Features
- **Separate Admin Configuration**: Easy admin management via `admin_config.php`
- **Thread Deletion**: Admins can delete any threads
- **Post Deletion**: Admins can delete any posts
- **Admin Badges**: Visual indicators for admin users
- **No Cooldown**: Admins bypass all posting restrictions

### Enhanced Deletion Permissions
Both threads and posts follow the same deletion rules:

#### Thread Deletion Permissions
- **Admin Users**: Can delete any thread at any time
- **Logged-in Users**: Can delete their own threads
- **Anonymous Users**: Can delete their own threads from the same IP address within 10 minutes of creation
- **Smart UI**: Delete buttons show time remaining for anonymous users ("Delete Thread (Xmin left)")
- **Comprehensive Cleanup**: Thread deletion also removes all posts and notifications in the thread

#### Post Deletion Permissions
- **Admin Users**: Can delete any post at any time
- **Logged-in Users**: Can delete their own posts
- **Anonymous Users**: Can delete their own posts from the same IP address within 10 minutes of posting
- **Smart UI**: Delete buttons show time remaining for anonymous users ("Delete (Xmin left)")
- **Safe Deletion**: Uses transactions to handle database constraints properly

### Anti-Spam Features
- **IP-Based Cooldowns**: Anonymous users have posting restrictions
  - 30 seconds between thread creation
  - 5 seconds between replies
- **Registered User Cooldowns**: Reduced restrictions for logged-in users
- **Admin Exemptions**: No restrictions for admin users

### Security Features
- **SQL Injection Protection**: Prepared statements throughout
- **File Access Protection**: `.htaccess` rules protect sensitive files
- **Session Management**: Secure user sessions
- **Input Validation**: Proper sanitization of user input

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd xenon-forum
   ```

2. **Configure admin users (optional)**
   Edit `admin_config.php` and add usernames to the `$admin_usernames` array:
   ```php
   $admin_usernames = [
       'admin',
       'your_username_here'
   ];
   ```

3. **Start the server**
   ```bash
   php -S localhost:8000
   ```

4. **Access the forum**
   Open your browser to `http://localhost:8000`
   
   **The database and all required tables are automatically created on first access!**

### Manual Database Setup (Optional)
If you prefer manual setup or need to reset the database:
```bash
php init_db.php
php add_admin_features.php
php add_ip_cooldown.php
```

## File Structure

### Core Files
- `index.html` - Homepage
- `forum.php` - Main forum page with thread listing
- `thread.php` - Individual thread view with posts
- `auth.php` - User authentication (login/register)
- `notifications.php` - User notification center

### Database & Admin Files
- `db_init.php` - **Automatic database initialization (NEW!)**
- `admin_config.php` - Admin user configuration
- `add_admin_features.php` - Manual database setup for admin features
- `add_ip_cooldown.php` - Manual database setup for IP cooldowns

### Legacy Database Files
- `init_db.php` - Manual database creation (optional)
- `reset_db.php` - Reset database (development only)
- `safe_update_db.php` - Safe database updates (optional)

### Configuration
- `.htaccess` - Apache configuration and security rules
- `styles.css` - Custom styling
- `headers.php` - Common header with navigation

## Admin Management

### Adding Admins
1. Edit `admin_config.php`
2. Add usernames to the `$admin_usernames` array
3. Users must register with those exact usernames

### Admin Capabilities
- Delete any thread or post
- No posting cooldowns
- Admin badge display
- Access to all forum features

## Database Schema

### Automatic Initialization
The forum automatically creates and maintains the database schema:
- **New installations**: All tables and columns created automatically
- **Existing databases**: Missing tables/columns added automatically
- **No manual setup required**: Just access the forum and it works!

### Tables
- `users` - User accounts and authentication
- `threads` - Forum threads
- `posts` - Thread replies
- `notifications` - User notifications
- `ip_cooldowns` - Anonymous user rate limiting

### Key Columns
- `is_admin` - Admin status flag
- `is_deleted` - Soft deletion flag
- `last_post_time` - Cooldown tracking
- `ip_address` - Anonymous user tracking

## Security Considerations

### Protected Files
The following files are protected by `.htaccess`:
- `admin_config.php` - Admin configuration
- `*.sqlite` - Database files
- `add_*.php` - Setup scripts
- `*.log` - Log files

### Production Deployment
1. Remove or protect setup scripts
2. Set proper file permissions
3. Configure SSL/HTTPS
4. Review admin user list
5. Monitor log files

## Development

### Adding Features
1. Create feature branch
2. Update database schema if needed
3. Add appropriate security checks
4. Test with both admin and regular users
5. Update documentation

### Database Updates
Use the safe update pattern:
```php
// Check if column exists before adding
$result = $db->query("PRAGMA table_info(table_name)");
// Add column only if it doesn't exist
```

## License

This project is open source. See LICENSE file for details.

## Support

For issues and questions, please check the FAQ or create an issue in the repository.