# Security Commands Quick Reference

## 🔑 Key Generation & Rotation

### Generate Application Key (Do this first!)
```bash
php artisan key:generate
```

### Generate New Sanctum Secret
```bash
php artisan sanctum:prune-expired --hours=24
```

## 🔒 Permission Management

### Set Correct File Permissions (Production)
```bash
# Make web server owner
sudo chown -R www-data:www-data /path/to/app

# Set directory permissions
sudo find /path/to/app -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /path/to/app -type f -exec chmod 644 {} \;

# Storage and cache need write permissions
sudo chmod -R 775 /path/to/app/storage
sudo chmod -R 775 /path/to/app/bootstrap/cache
```

### For Development (Local)
```bash
chmod -R 755 storage bootstrap/cache
```

## 🛡️ Security Audit Commands

### Check for Vulnerable Dependencies
```bash
composer audit
composer outdated
```

### Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimize for Production
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔐 Database Security

### Create Limited Database User
```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create user
CREATE USER 'appuser'@'localhost' IDENTIFIED BY 'strong_password_here';

-- Grant limited permissions (no DROP, CREATE, ALTER)
GRANT SELECT, INSERT, UPDATE, DELETE ON database_name.* TO 'appuser'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify
SHOW GRANTS FOR 'appuser'@'localhost';

-- Exit
EXIT;
```

### Database Backup
```bash
# Backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore
mysql -u username -p database_name < backup_20260206_120000.sql
```

## 📊 Security Monitoring

### Check Failed Login Attempts
```bash
# View logs
tail -f storage/logs/laravel.log | grep "failed"

# Count failed attempts
grep "failed login" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l
```

### Monitor Active Sessions
```php
// In tinker
php artisan tinker

// Check active sessions
DB::table('sessions')->count();

// Get recent sessions
DB::table('sessions')->orderBy('last_activity', 'desc')->limit(10)->get();
```

### Check API Rate Limit Hits
```bash
# View throttle logs
grep "rate limit" storage/logs/laravel.log
```

## 🔄 Token & Session Management

### Revoke All User Tokens
```php
// In controller or tinker
$user = User::find($userId);
$user->tokens()->delete();
```

### Clear All Sessions
```bash
php artisan session:clear

# Or truncate sessions table
php artisan tinker
DB::table('sessions')->truncate();
```

### Force Logout All Users
```bash
php artisan tinker
DB::table('sessions')->truncate();
DB::table('personal_access_tokens')->truncate();
```

## 🚨 Emergency Security Breach Response

### Step 1: Immediate Actions
```bash
# 1. Put app in maintenance mode
php artisan down --secret="emergency-access-token"

# 2. Rotate application key (logs everyone out)
php artisan key:generate

# 3. Clear all tokens and sessions
php artisan tinker
DB::table('personal_access_tokens')->truncate();
DB::table('sessions')->truncate();

# 4. Change database password
# Connect to MySQL and run:
# ALTER USER 'appuser'@'localhost' IDENTIFIED BY 'new_strong_password';

# 5. Update .env with new DB password
nano .env

# 6. Clear all caches
php artisan cache:clear
php artisan config:clear
```

### Step 2: Investigation
```bash
# Check recent access logs
tail -n 1000 storage/logs/laravel.log

# Check web server logs
tail -n 1000 /var/log/nginx/access.log
tail -n 1000 /var/log/nginx/error.log

# Check for suspicious files
find . -type f -name "*.php" -mtime -1

# Check database modifications
# Connect to MySQL and check:
# SELECT * FROM users ORDER BY updated_at DESC LIMIT 50;
```

### Step 3: Recovery
```bash
# 1. Deploy security fix
git pull origin main

# 2. Update dependencies
composer update

# 3. Run security audit
composer audit

# 4. Bring app back online
php artisan up
```

## 🔍 Security Testing

### Test Rate Limiting
```bash
# Send multiple requests quickly
for i in {1..10}; do
  curl -X POST http://localhost:8000/api/v1/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@example.com","password":"password"}'
done
```

### Test CORS Headers
```bash
curl -I http://localhost:8000/api/v1/products
```

### Test Security Headers
```bash
curl -I https://yourdomain.com/api/v1/products | grep -E "(X-Frame-Options|X-Content-Type-Options|Strict-Transport)"
```

### Test HTTPS Redirect
```bash
curl -I http://yourdomain.com
# Should see 301 redirect to https://
```

## 📦 Dependency Updates

### Update All Dependencies Safely
```bash
# Backup first!
cp composer.lock composer.lock.backup

# Update with security fixes only
composer update --with-dependencies

# If issues, rollback
mv composer.lock.backup composer.lock
composer install
```

### Check for Security Updates
```bash
composer outdated | grep security
```

## 🔐 SSL/TLS Certificate

### Generate Let's Encrypt Certificate
```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Renew Certificate
```bash
sudo certbot renew --dry-run
sudo certbot renew
```

### Check Certificate Expiry
```bash
echo | openssl s_client -servername yourdomain.com -connect yourdomain.com:443 2>/dev/null | openssl x509 -noout -dates
```

## 🔧 Web Server Security

### Nginx - Reload Configuration
```bash
sudo nginx -t  # Test configuration
sudo systemctl reload nginx
```

### Apache - Reload Configuration
```bash
sudo apachectl configtest
sudo systemctl reload apache2
```

### Check Open Ports
```bash
sudo netstat -tulpn | grep LISTEN
# Or
sudo ss -tulpn | grep LISTEN
```

### Firewall Rules (UFW)
```bash
# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# SSH (change 22 if using different port)
sudo ufw allow 22/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

## 📝 Logging & Monitoring

### Watch Logs in Real-Time
```bash
tail -f storage/logs/laravel.log
```

### Search Logs for Errors
```bash
grep "ERROR" storage/logs/laravel-$(date +%Y-%m-%d).log
```

### Check Disk Space
```bash
df -h
du -sh storage/logs/*
```

### Clean Old Logs
```bash
# Delete logs older than 30 days
find storage/logs -name "*.log" -type f -mtime +30 -delete
```

## 🔄 Scheduled Security Tasks

### Add to Crontab (Server)
```bash
crontab -e

# Add these lines:
# Clear expired password resets daily
0 2 * * * cd /path/to/app && php artisan auth:clear-resets

# Backup database daily
0 3 * * * /path/to/backup_script.sh

# Check for security updates weekly
0 4 * * 0 cd /path/to/app && composer outdated > /tmp/composer_outdated.txt
```

## 🚀 Quick Production Deploy

```bash
#!/bin/bash
# deploy.sh

cd /path/to/app

# Maintenance mode
php artisan down

# Pull latest code
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Bring back online
php artisan up

echo "Deployment complete!"
```

---

## 📞 Emergency Contacts

- **Hosting Provider**: [provider support]
- **Domain Registrar**: [registrar support]
- **SSL Certificate**: [cert provider support]
- **Security Team**: [your security team contact]

---

**Pro Tip**: Bookmark this file and keep it accessible during emergencies!
