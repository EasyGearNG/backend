# 🔒 Security Audit Checklist

Use this checklist to verify your application's security before going to production and during regular security audits.

## 📋 Pre-Production Security Checklist

### **Environment Configuration**
- [ ] `APP_ENV=production` in `.env`
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` generated and unique
- [ ] `.env` file NOT in version control
- [ ] `.env.example` is up to date
- [ ] No hardcoded credentials in code
- [ ] No API keys in frontend/public code

### **HTTPS/SSL**
- [ ] Valid SSL certificate installed
- [ ] HTTP automatically redirects to HTTPS
- [ ] HSTS header enabled
- [ ] Certificate auto-renewal configured (Let's Encrypt)
- [ ] SSL Labs test passes (Grade A): https://www.ssllabs.com/ssltest/

### **Authentication & Authorization**
- [ ] Strong password requirements enforced
- [ ] Password reset functionality works
- [ ] Email verification implemented (if required)
- [ ] Account lockout after failed attempts
- [ ] Session timeout configured appropriately
- [ ] Sanctum tokens have expiration
- [ ] Role-based access control (RBAC) implemented
- [ ] Admin routes protected with `role:admin` middleware
- [ ] Vendor routes protected with `vendor` middleware
- [ ] All authenticated routes use `auth:sanctum` middleware

### **Rate Limiting**
- [ ] Rate limiting active on all public routes
- [ ] Stricter limits on auth endpoints (login, register)
- [ ] API throttling configured appropriately
- [ ] Rate limit responses return proper 429 status

### **Input Validation**
- [ ] All POST/PUT/PATCH endpoints validate input
- [ ] File uploads validated (size, type, extension)
- [ ] File MIME type checked, not just extension
- [ ] SQL injection prevented (using Eloquent/Query Builder)
- [ ] XSS prevented (proper output escaping)
- [ ] Mass assignment protection ($fillable in models)
- [ ] CSRF protection enabled (for web routes)

### **Security Headers**
- [ ] X-Frame-Options: DENY
- [ ] X-Content-Type-Options: nosniff
- [ ] X-XSS-Protection: 1; mode=block
- [ ] Strict-Transport-Security (HSTS)
- [ ] Content-Security-Policy configured
- [ ] Referrer-Policy set
- [ ] Permissions-Policy configured
- [ ] Server signature hidden

### **Database Security**
- [ ] Database user has minimal permissions (not root)
- [ ] Strong database password
- [ ] Database not accessible from public internet
- [ ] Connection uses SSL/TLS (if remote)
- [ ] Regular database backups configured
- [ ] Backup restoration tested
- [ ] Migrations work without errors

### **File Security**
- [ ] Uploaded files stored outside public directory
- [ ] File downloads require authentication
- [ ] Executable files (.php, .exe) blocked from upload
- [ ] File permissions correct (755 directories, 644 files)
- [ ] Storage and cache directories writable by web server
- [ ] Directory listing disabled
- [ ] Sensitive files (.env, composer.json) not web accessible

### **API Security**
- [ ] API authentication required where needed
- [ ] CORS configured properly
- [ ] API versioning in place
- [ ] Input sanitization on all endpoints
- [ ] Proper error handling (no stack traces in production)
- [ ] API documentation up to date
- [ ] Rate limiting on API endpoints

### **Dependencies & Updates**
- [ ] All Composer packages up to date
- [ ] No known security vulnerabilities (`composer audit`)
- [ ] Dev dependencies not installed in production
- [ ] Autoloader optimized (`--optimize-autoloader`)
- [ ] Laravel framework up to date on supported version

### **Error Handling & Logging**
- [ ] Error pages don't expose sensitive information
- [ ] Stack traces hidden in production
- [ ] Errors logged to file/service
- [ ] Log rotation configured
- [ ] Sensitive data masked in logs
- [ ] 404/500 error pages customized

### **Session Security**
- [ ] Sessions stored securely (database/redis, not file)
- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS only)
- [ ] `SESSION_HTTP_ONLY=true`
- [ ] `SESSION_SAME_SITE=lax` or `strict`
- [ ] Session lifetime appropriate (not too long)

### **Payment Security** (If applicable)
- [ ] PCI DSS compliance considered
- [ ] No credit card data stored locally
- [ ] Payment gateway webhooks verified
- [ ] Payment data encrypted in transit
- [ ] Transaction logging implemented
- [ ] Failed payment attempts monitored

### **Server Configuration**
- [ ] Firewall configured (UFW/iptables)
- [ ] Only necessary ports open (80, 443, 22)
- [ ] SSH key authentication enabled
- [ ] Root login disabled
- [ ] Fail2ban or similar installed
- [ ] Regular security updates automated
- [ ] Server monitoring in place

### **Monitoring & Alerts**
- [ ] Uptime monitoring configured
- [ ] Error tracking service connected (Sentry/Bugsnag)
- [ ] Log aggregation service configured
- [ ] Security alerts configured
- [ ] Performance monitoring in place
- [ ] Backup alerts configured

### **Compliance & Privacy**
- [ ] Privacy policy updated
- [ ] Terms of service current
- [ ] GDPR compliance considered (if applicable)
- [ ] Data retention policy defined
- [ ] User data export functionality
- [ ] User data deletion functionality
- [ ] Cookie consent banner (if required)

### **Penetration Testing**
- [ ] Manual security testing performed
- [ ] Automated security scan completed
- [ ] SQL injection tests passed
- [ ] XSS vulnerability tests passed
- [ ] CSRF tests passed
- [ ] Authentication bypass tests passed
- [ ] Authorization tests passed (IDOR)
- [ ] File upload security tested

---

## 🔍 Verification Commands

### Test HTTPS Redirect
```bash
curl -I http://yourdomain.com
# Should see 301 or 302 redirect to https://
```

### Check Security Headers
```bash
curl -I https://yourdomain.com | grep -E "(X-Frame|X-Content|Strict-Transport|X-XSS)"
```

### Test Rate Limiting
```bash
# Should be blocked after 5 attempts
for i in {1..10}; do
  curl -X POST https://yourdomain.com/api/v1/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@test.com","password":"wrong"}'
  done
```

### Check SSL Certificate
```bash
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com
```

### Verify File Permissions
```bash
ls -la /path/to/app
ls -la /path/to/app/storage
```

### Check for Vulnerable Dependencies
```bash
composer audit
```

### Verify Database Connection
```bash
php artisan tinker
DB::connection()->getPdo();
```

---

## 🚨 Security Testing Tools

### Online Tools
- **SSL Labs**: https://www.ssllabs.com/ssltest/
- **Security Headers**: https://securityheaders.com/
- **Mozilla Observatory**: https://observatory.mozilla.org/
- **Qualys SSL Scanner**: https://www.ssllabs.com/ssltest/

### Local Tools
```bash
# Install security checker
composer require --dev enlightn/security-checker

# Run security audit
./vendor/bin/security-checker security:check

# Run static analysis
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse app

# Check code quality
composer require --dev squizlabs/php_codesniffer
./vendor/bin/phpcs app
```

---

## 📊 Security Score

**Total Items**: ~100

**Calculate Your Score:**
- Count checked items
- Divide by total items
- Multiply by 100

**Score Guide:**
- **90-100%**: Excellent security posture ✅
- **80-89%**: Good, but needs improvement ⚠️
- **70-79%**: Acceptable for development, not production ⚠️
- **Below 70%**: High risk, do not deploy ❌

---

## 🔄 Regular Security Audit Schedule

### **Weekly**
- [ ] Review error logs
- [ ] Check failed login attempts
- [ ] Monitor API usage
- [ ] Review recent code changes

### **Monthly**
- [ ] Run `composer audit`
- [ ] Update dependencies
- [ ] Review user permissions
- [ ] Check backup integrity
- [ ] Review security logs

### **Quarterly**
- [ ] Full security audit using this checklist
- [ ] Penetration testing
- [ ] Review and update security policies
- [ ] Security training for team
- [ ] Update documentation

### **Annually**
- [ ] Third-party security audit
- [ ] Compliance review
- [ ] Disaster recovery drill
- [ ] Review incident response plan

---

## 📝 Audit Log

| Date | Auditor | Score | Critical Issues | Status |
|------|---------|-------|-----------------|--------|
| 2026-02-06 | [Name] | --% | -- | Initial Setup |
|  |  | % |  |  |
|  |  | % |  |  |

---

## 🔗 Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [CIS Benchmarks](https://www.cisecurity.org/cis-benchmarks/)

---

**Last Audit Date**: _________________

**Next Scheduled Audit**: _________________

**Auditor Name**: _________________

**Signature**: _________________
