# Xenon Forum

A comprehensive PHP-based forum system with user authentication, admin features, tagging system, search functionality, and anonymous posting capabilities.

## Features

### Core Forum Features
- **User Authentication**: Registration and login system with secure session management
- **Thread Creation**: Users can create discussion threads with tagging support
- **Post Replies**: Reply to threads with nested discussions and tag categorization
- **Anonymous Posting**: Full support for anonymous users with IP-based tracking
- **Notifications**: Real-time notification system when someone replies to your threads
- **Tagging System**: Organize content with comma-separated tags for better discoverability
- **Advanced Search**: Full-text search across threads, posts, and tags with filtering options

### Admin Features
- **Comprehensive Admin Dashboard**: Human-readable database interface with full CRUD operations
- **Separate Admin Configuration**: Easy admin management via `admin_config.php`
- **Thread & Post Management**: Admins can delete any content with enhanced permissions
- **Admin Badges**: Visual indicators for admin users throughout the forum
- **No Cooldown**: Admins bypass all posting restrictions
- **Statistics Overview**: Real-time forum metrics and activity tracking
- **User Management**: View and manage all registered users
- **Content Moderation**: Soft deletion system with comprehensive cleanup

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
- **Rate Limiting**: Automatic cooldown tracking and enforcement

### Security Features
- **SQL Injection Protection**: Prepared statements throughout
- **File Access Protection**: `.htaccess` rules protect sensitive files
- **Session Management**: Secure user sessions with proper validation
- **Input Validation**: Comprehensive sanitization of user input
- **Admin Access Control**: Secure admin authentication and authorization

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

## Complete File Structure

### Core Forum Pages
- `index.html` - Homepage with project information
- `forum.php` - Main forum page with thread listing and quick search
- `thread.php` - Individual thread view with posts and reply functionality
- `auth.php` - User authentication (login/register) system
- `notifications.php` - User notification center with reply tracking
- `search.php` - Advanced search interface with filtering options
- `admin_dashboard.php` - Comprehensive admin control panel

### Information Pages
- `news.php` - News and announcements system
- `events.php` - Events calendar and listings
- `faq.php` - Frequently asked questions
- `rules.php` - Forum rules and guidelines
- `support.php` - Support and help information
- `status.php` - Server status and system information

### Database & Configuration
- `db_init.php` - **Automatic database initialization with schema management**
- `admin_config.php` - Admin user configuration and privilege management
- `add_admin_features.php` - Manual database setup for admin features
- `add_ip_cooldown.php` - Manual database setup for IP cooldowns

### Development & Debug Tools
- `debug_db.php` - Database debugging and inspection tools
- `forum_debug.php` - Forum-specific debugging interface
- `test_notifications.php` - Notification system testing
- `apache_diagnostic.php` - Apache server diagnostics
- `server-status.php` - Server status monitoring

### Legacy & Utility Files
- `init_db.php` - Manual database creation (optional)
- `reset_db.php` - Reset database (development only)
- `safe_update_db.php` - Safe database updates (optional)
- `update_db.php` - Database update utilities
- `session_check.php` - Session validation utilities
- `notification_count.php` - Notification counting utilities

### Configuration & Assets
- `.htaccess` - Apache configuration and security rules
- `.gitignore` - Git ignore patterns
- `styles.css` - Complete cyberpunk theme styling
- `headers.php` - Dynamic header with navigation and user info
- `headers.html` - Legacy static header (deprecated)
- `DEPLOYMENT_GUIDE.md` - Production deployment instructions

### Content Directories
- `articles/` - News articles and content
  - `2025-08-01-launch.md` - Launch announcement
  - `2025-08-06-1-events.md` - Event content
  - `2025-08-06-2-events.md` - Additional event content
- `events/` - Event-specific content files
  - `2025-08-06-1.md` - Event details
  - `2025-08-06-2.md` - Additional event details
- `images/` - Static assets and media
  - `favicon.ico` - Site favicon
  - `backgrounds/` - Background images for theming
- `error/` - Custom error pages
  - `404.html` - Custom 404 error page

### Markdown Documentation
- `faq.md` - FAQ content source
- `rules.md` - Rules content source
- `support.md` - Support content source

## Admin Management

### Admin Dashboard Features
- **Statistics Overview**: Real-time forum metrics (users, threads, posts, notifications, tags)
- **User Management**: View, edit, and manage all registered users
- **Thread Management**: Comprehensive thread administration with editing capabilities
- **Post Management**: Full post moderation with content editing
- **Notification Management**: Monitor and manage user notifications
- **Tag Management**: View tag usage statistics and manage tag system

### Adding Admins
1. Edit `admin_config.php`
2. Add usernames to the `$admin_usernames` array
3. Users must register with those exact usernames
4. Admin dashboard link appears in top bar for admin users

### Admin Capabilities
- Access comprehensive admin dashboard
- Delete any thread or post
- Edit user information and content
- No posting cooldowns
- Admin badge display throughout forum
- Access to all forum features and statistics

## Database Schema

### Automatic Initialization
The forum automatically creates and maintains the database schema:
- **New installations**: All tables and columns created automatically
- **Existing databases**: Missing tables/columns added automatically
- **Schema evolution**: Automatic updates for new features
- **No manual setup required**: Just access the forum and it works!

### Core Tables
- `users` - User accounts, authentication, and profile data
- `threads` - Forum threads with metadata and tagging
- `posts` - Thread replies with content and tagging
- `notifications` - User notification system
- `ip_cooldowns` - Anonymous user rate limiting

### Tagging System Tables
- `tags` - Tag definitions and metadata
- `thread_tags` - Many-to-many relationship between threads and tags
- `post_tags` - Many-to-many relationship between posts and tags

### Key Features
- **Soft Deletion**: `is_deleted` flags for content recovery
- **Admin Tracking**: `is_admin` status flags
- **Cooldown System**: `last_post_time` for rate limiting
- **IP Tracking**: Anonymous user identification
- **Relationship Integrity**: Foreign key constraints with proper cleanup

## Search System

### Search Capabilities
- **Full-text Search**: Search across thread titles, post content, and tags
- **Advanced Filtering**: Filter by content type (threads, posts, tags)
- **Tag-based Search**: Find content by specific tags
- **Quick Search**: Instant search from forum main page
- **Result Highlighting**: Clear presentation of search results

### Search Interface
- **Quick Search Bar**: Available on main forum page
- **Advanced Search Page**: Comprehensive search with filters
- **Search Results**: Organized display with context and metadata
- **Tag Integration**: Search results show associated tags

## Tagging System

### Tag Features
- **Flexible Tagging**: Comma-separated tag input for threads and posts
- **Tag Display**: Visual tag indicators throughout the forum
- **Tag Search**: Search and filter content by tags
- **Tag Statistics**: Admin dashboard shows tag usage metrics
- **Tag Management**: Admin tools for tag oversight

### Tag Implementation
- **Many-to-many Relationships**: Proper database design for scalability
- **Automatic Tag Creation**: Tags created automatically when used
- **Tag Normalization**: Consistent tag formatting and storage
- **Tag Analytics**: Usage tracking and statistics

## Security Considerations

### Protected Files
The following files are protected by `.htaccess`:
- `admin_config.php` - Admin configuration
- `*.sqlite` - Database files
- `add_*.php` - Setup scripts
- `*.log` - Log files
- `debug_*.php` - Debug tools

### Production Deployment
1. Remove or protect development/debug files
2. Set proper file permissions (644 for files, 755 for directories)
3. Configure SSL/HTTPS
4. Review and secure admin user list
5. Monitor log files and error reporting
6. Implement backup strategy for database
7. Configure proper Apache/Nginx security headers

## Development

### Adding Features
1. Create feature branch
2. Update database schema in `db_init.php` if needed
3. Add appropriate security checks and admin permissions
4. Test with admin, registered, and anonymous users
5. Update documentation and README
6. Ensure CSS styling consistency

### Database Updates
Use the safe update pattern in `db_init.php`:
```php
// Check if column exists before adding
$result = $db->query("PRAGMA table_info(table_name)");
// Add column only if it doesn't exist
```

### CSS Styling
- **Cyberpunk Theme**: Consistent dark theme with cyan accents
- **Responsive Design**: Mobile-friendly layouts
- **Input Styling**: Unified form element appearance
- **Admin Styling**: Distinctive admin interface elements

## Troubleshooting

### Common Issues
- **White Input Boxes**: Ensure CSS classes are properly applied, not overridden by inline styles
- **Database Errors**: Check file permissions and SQLite3 extension
- **Admin Access**: Verify username matches exactly in `admin_config.php`
- **Search Issues**: Ensure database schema is up to date

### Debug Tools
- `debug_db.php` - Database inspection and debugging
- `forum_debug.php` - Forum-specific debugging
- `apache_diagnostic.php` - Server configuration diagnostics

## License

This project is open source. See LICENSE file for details.

## Support

For issues and questions, please check the FAQ or create an issue in the repository.

## Changelog

### Recent Updates
- **Admin Dashboard**: Comprehensive database management interface
- **Tagging System**: Full tagging support for threads and posts
- **Advanced Search**: Multi-criteria search with tag filtering
- **Enhanced UI**: Improved styling and user experience
- **Security Improvements**: Enhanced input validation and access control
- **Database Evolution**: Automatic schema updates and maintenance