# 🔒 Application Security Guide

## Overview
This guide covers security best practices to protect your Laravel application from common vulnerabilities and attacks.

---

## 🛡️ Security Measures Implemented

### 1. **Rate Limiting** ✅
- **Public API**: 60 requests per minute
- **Auth endpoints**: 5 requests per minute (login, register, password reset)
- **Protected routes**: Default Laravel throttling

### 2. **Security Headers** ✅
Middleware adds the following headers:
- `X-Frame-Options: DENY` - Prevents clickjacking
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `X-XSS-Protection: 1; mode=block` - XSS protection
- `Strict-Transport-Security` - Forces HTTPS (production only)
- `Content-Security-Policy` - Controls resource loading
- `Referrer-Policy` - Controls referrer information
- `Permissions-Policy` - Controls browser features

### 3. **Authentication & Authorization** ✅
- Laravel Sanctum for API authentication
- Token-based authentication with cookies
- Role-based access control (admin, vendor, user)
- Password hashing with bcrypt

### 4. **Input Validation** ✅
- Request validation on all endpoints
- Type checking and sanitization
- File upload validation

---

## 🚨 Critical Security Checklist for Production

### **Environment Configuration**

#### **.env File Security**
```bash
# ❌ NEVER commit .env files to git
# ✅ Already in .gitignore

# Set these in production:
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate with php artisan key:generate>
```

#### **Generate Application Key**
```bash
php artisan key:generate
```

#### **Secure File Permissions**
```bash
# On your production server:
chmod -R 755 /path/to/your/app
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /path/to/your/app
```

#### **Hide .env from Web Access**
Ensure your `.env` file is ABOVE the public directory or blocked by web server:

**Nginx:**
```nginx
location ~ /\.env {
    deny all;
}
```

**Apache (.htaccess in root):**
```apache
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

---

### **Database Security**

#### **Use Prepared Statements** ✅
```php
// ✅ SAFE - Already using Eloquent ORM
$users = User::where('email', $email)->get();

// ❌ DANGEROUS - Never do this
$users = DB::select("SELECT * FROM users WHERE email = '$email'");

// ✅ SAFE - If you must use raw queries
$users = DB::select("SELECT * FROM users WHERE email = ?", [$email]);
```

#### **Secure Database Credentials**
```env
# Production .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=limited_user  # NOT root!
DB_PASSWORD=<strong-random-password>
```

**Database User Permissions:**
```sql
-- Create limited database user (don't use root in production)
CREATE USER 'appuser'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON your_database.* TO 'appuser'@'localhost';
FLUSH PRIVILEGES;
```

---

### **XSS (Cross-Site Scripting) Prevention**

#### **Blade Templates** ✅
```php
// ✅ SAFE - Automatically escaped
{{ $userInput }}

// ❌ DANGEROUS - Unescaped HTML
{!! $userInput !!}

// ✅ SAFE - If you need HTML, sanitize first
{!! Purifier::clean($userInput) !!}
```

#### **API Responses**
```php
// Use SecurityHelper for additional sanitization
use App\Helpers\SecurityHelper;

$sanitized = SecurityHelper::sanitizeString($request->input('name'));
```

---

### **CSRF Protection**

#### **For Web Routes** ✅
Laravel includes CSRF protection by default for web routes.

#### **For API Routes**
- Using Sanctum token authentication (CSRF not needed)
- Tokens validated on each request

---

### **File Upload Security**

#### **Validate All Uploads**
```php
use App\Helpers\SecurityHelper;

public function uploadImage(Request $request)
{
    $file = $request->file('image');
    
    // Validate file
    $validation = SecurityHelper::validateFileUpload(
        $file,
        allowedTypes: ['jpg', 'jpeg', 'png', 'gif'],
        maxSize: 5242880 // 5MB
    );
    
    if (!$validation['valid']) {
        return response()->json([
            'success' => false,
            'errors' => $validation['errors']
        ], 422);
    }
    
    // Store with randomized filename
    $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
    $path = $file->storeAs('uploads', $filename, 'public');
    
    return response()->json(['path' => $path]);
}
```

#### **Block Executable Files**
```php
// In validation rules
'file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf|max:5120',

// NEVER allow: .php, .exe, .sh, .bat, .js, .html
```

#### **Store Outside Public Directory**
```php
// Store in storage/app (not publicly accessible)
$path = $file->store('private/uploads');

// Serve via controller with auth check
Route::get('/files/{filename}', [FileController::class, 'download'])
    ->middleware('auth:sanctum');
```

---

### **SQL Injection Prevention** ✅

#### **Always Use Eloquent or Query Builder**
```php
// ✅ SAFE - Parameterized queries
User::where('email', $email)->first();
DB::table('users')->where('email', $email)->get();
DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// ❌ NEVER DO THIS
DB::select("SELECT * FROM users WHERE email = '$email'");
```

---

### **Session Security**

#### **Configure Session Settings**
```env
# .env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true  # HTTPS only
SESSION_HTTP_ONLY=true      # No JavaScript access
SESSION_SAME_SITE=lax       # CSRF protection
```

#### **Session Configuration** (config/session.php)
```php
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'lax',
```

---

### **API Security Best Practices**

#### **1. Use HTTPS in Production** 🔐
```bash
# Force HTTPS redirect in .htaccess or nginx
# Headers middleware already includes HSTS header
```

#### **2. Token Security**
```php
// Sanctum tokens are secure by default
// Set token expiration
'expiration' => 60, // minutes

// Revoke tokens on logout
$request->user()->currentAccessToken()->delete();

// Revoke all tokens
$request->user()->tokens()->delete();
```

#### **3. CORS Configuration**
Create `config/cors.php`:
```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        // Add other allowed origins
    ],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

#### **4. API Versioning** ✅
Already implemented with `/api/v1` prefix.

---

### **Error Handling & Logging**

#### **Never Expose Sensitive Information**
```php
// ❌ BAD - Exposes stack trace
return response()->json(['error' => $e->getMessage()], 500);

// ✅ GOOD - Generic error message
return response()->json([
    'success' => false,
    'message' => 'An error occurred'
], 500);

// Log the actual error
Log::error('Payment failed', [
    'user_id' => $userId,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

#### **Mask Sensitive Data in Logs**
```php
use App\Helpers\SecurityHelper;

$data = $request->all();
$maskedData = SecurityHelper::maskSensitiveData($data);
Log::info('User action', $maskedData);
```

---

### **Dependency Security**

#### **Keep Dependencies Updated**
```bash
# Check for security vulnerabilities
composer audit

# Update dependencies
composer update

# Check for outdated packages
composer outdated
```

#### **Remove Unused Packages**
```bash
composer remove package/name
```

---

### **Server Configuration**

#### **Disable Directory Listing**
**Nginx:**
```nginx
autoindex off;
```

**Apache (.htaccess):**
```apache
Options -Indexes
```

#### **Hide Server Information**
**Nginx:**
```nginx
server_tokens off;
```

**Apache:**
```apache
ServerTokens Prod
ServerSignature Off
```

#### **Block Common Attack Patterns**
**Nginx:**
```nginx
# Block common exploits
location ~* "(eval|base64_decode|gzinflate|preg_replace|system|exec)" {
    deny all;
}

# Block SQL injection attempts
if ($query_string ~* "union.*select.*\(") {
    return 403;
}
```

---

## 🔍 Security Scanning Tools

### **1. Local Security Audit**
```bash
# PHP Security Checker
composer require --dev enlightn/security-checker
./vendor/bin/security-checker security:check

# Laravel Security Checker
php artisan security:check
```

### **2. Static Analysis**
```bash
# Install PHPStan
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse app

# Install Psalm
composer require --dev vimeo/psalm
./vendor/bin/psalm
```

### **3. Online Vulnerability Scanners**
- **Snyk**: https://snyk.io/
- **SonarCloud**: https://sonarcloud.io/
- **GitHub Security**: Enable Dependabot alerts

---

## 📋 Production Deployment Checklist

### **Before Deploying:**
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`
- [ ] Use strong database password
- [ ] Enable HTTPS/SSL certificate
- [ ] Configure firewall rules
- [ ] Set appropriate file permissions
- [ ] Enable rate limiting
- [ ] Configure CORS properly
- [ ] Set up database backups
- [ ] Configure error logging
- [ ] Review all API endpoints for auth
- [ ] Test rate limiting
- [ ] Scan for vulnerabilities
- [ ] Update all dependencies
- [ ] Remove development tools
- [ ] Configure CDN for static assets
- [ ] Set up monitoring/alerts

### **After Deploying:**
- [ ] Test all critical endpoints
- [ ] Verify HTTPS is enforced
- [ ] Check security headers
- [ ] Test rate limiting
- [ ] Monitor error logs
- [ ] Run security audit
- [ ] Test authentication flows
- [ ] Verify file upload restrictions

---

## 🚨 Common Vulnerabilities to Avoid

### **1. Mass Assignment** ✅
```php
// Protected by $fillable in models
protected $fillable = ['name', 'email'];

// ❌ NEVER use $guarded = []
```

### **2. Insecure Direct Object References (IDOR)**
```php
// ❌ BAD - Any user can access any order
Route::get('/orders/{id}', function($id) {
    return Order::find($id);
});

// ✅ GOOD - Check ownership
Route::get('/orders/{id}', function(Request $request, $id) {
    $order = Order::where('id', $id)
                  ->where('user_id', $request->user()->id)
                  ->firstOrFail();
    return $order;
});
```

### **3. Authentication Bypass**
```php
// ✅ Always verify user identity
if ($request->user()->id !== $order->user_id && !$request->user()->isAdmin()) {
    abort(403, 'Unauthorized');
}
```

### **4. Sensitive Data Exposure**
```php
// ❌ BAD - Exposes password hash
return User::all();

// ✅ GOOD - Use hidden fields
class User extends Model {
    protected $hidden = ['password', 'remember_token'];
}
```

---

## 🔐 Password Security

### **Strong Password Rules**
```php
// In validation
'password' => [
    'required',
    'string',
    'min:8',
    'regex:/[a-z]/',      // lowercase
    'regex:/[A-Z]/',      // uppercase
    'regex:/[0-9]/',      // numbers
    'regex:/[@$!%*#?&]/', // special chars
    'confirmed'
],
```

---

## 📞 Security Incident Response

### **If Breach Detected:**
1. **Immediately:**
   - Rotate all API keys and secrets
   - Reset `APP_KEY` and regenerate sessions
   - Force logout all users
   - Change database passwords

2. **Investigate:**
   - Check logs for unauthorized access
   - Identify vulnerability
   - Document the breach

3. **Fix:**
   - Patch vulnerability
   - Update dependencies
   - Deploy fix

4. **Notify:**
   - Inform affected users
   - Report to authorities if required

---

## 🔗 Additional Resources

- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [Mozilla Web Security Guidelines](https://infosec.mozilla.org/guidelines/web_security)

---

## 📝 Regular Security Maintenance

### **Weekly:**
- Review error logs
- Check failed login attempts
- Monitor API usage

### **Monthly:**
- Update dependencies (`composer update`)
- Run security audit (`composer audit`)
- Review user permissions

### **Quarterly:**
- Full security audit
- Penetration testing
- Review and update security policies

---

**Last Updated:** February 6, 2026

**Next Review:** May 6, 2026
