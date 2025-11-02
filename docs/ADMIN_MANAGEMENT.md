# Admin User Management

This document explains how to create and manage site administrator accounts on your production server.

## Commands Available

### Create Admin User
```bash
php artisan admin:create
```

**Interactive Mode:**
The command will prompt you for all required information:
- Full Name
- Email Address  
- Username
- Password

**Non-Interactive Mode:**
You can also pass all parameters directly:
```bash
php artisan admin:create \
  --name="Your Name" \
  --email="admin@yourdomain.com" \
  --username="admin" \
  --password="secure-password-here"
```

### List Admin Users
```bash
php artisan admin:list
```
Shows all current site administrators with their details.

## Production Server Usage

### Step 1: Connect to Your Server
```bash
# SSH to your server (if you have SSH access)
ssh your-username@your-server.com

# OR use your hosting provider's terminal/console
```

### Step 2: Navigate to Project Directory
```bash
cd /path/to/your/gatherly/project
```

### Step 3: Create Admin User
```bash
# Interactive creation (recommended)
php artisan admin:create

# OR with parameters
php artisan admin:create \
  --name="Site Administrator" \
  --email="admin@yourdomain.com" \
  --username="admin" \
  --password="YourSecurePassword123!"
```

### Step 4: Verify Creation
```bash
php artisan admin:list
```

## Security Best Practices

1. **Strong Passwords**: Use passwords with at least 12 characters, including uppercase, lowercase, numbers, and symbols
2. **Unique Email**: Use a dedicated admin email address
3. **Limit Admin Accounts**: Only create admin accounts when necessary
4. **Regular Audits**: Periodically run `php artisan admin:list` to review admin accounts
5. **Remove Unused Accounts**: Delete admin accounts that are no longer needed

## Troubleshooting

### Database Connection Issues
Make sure your `.env` file has correct database credentials:
```
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=your-db-name
DB_USERNAME=your-db-username
DB_PASSWORD=your-db-password
```

### Permission Issues
Ensure your web server has write permissions to storage and cache directories:
```bash
chmod -R 775 storage bootstrap/cache
```

### Command Not Found
If the command doesn't appear, clear the cache:
```bash
php artisan config:clear
php artisan cache:clear
```

## Example Output

### Creating an Admin:
```
🔐 Creating Site Administrator Account

 Full Name:
 > John Administrator

 Email Address:
 > admin@example.com

 Username:
 > admin

 Password:
 > 

+------------------+-------------------+
| Field            | Value             |
+------------------+-------------------+
| Name             | John Administrator|
| Email            | admin@example.com |
| Username         | admin             |
| Admin Privileges | ✅ Yes            |
+------------------+-------------------+

 Create this admin user? (yes/no) [yes]:
 > 

✅ Admin user created successfully!
   • ID: 1
   • Name: John Administrator
   • Email: admin@example.com
   • Username: admin
   • Admin: ✅ Yes

🔒 Please store the login credentials securely and consider enabling 2FA.
```

### Listing Admins:
```
🔐 Site Administrator Users

+----+-------------------+-------------------+----------+---------------------+
| ID | Name              | Email             | Username | Created             |
+----+-------------------+-------------------+----------+---------------------+
| 1  | John Administrator| admin@example.com | admin    | 2025-11-02 15:30:45 |
+----+-------------------+-------------------+----------+---------------------+

✅ Found 1 administrator(s)
```