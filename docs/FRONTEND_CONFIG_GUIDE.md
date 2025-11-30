# Frontend URL Configuration Guide

## Overview
The backend now uses a centralized configuration for the frontend URL instead of hardcoded values or direct `env()` calls in controllers.

## Configuration Files

### 1. Environment Variables (`.env`)
```env
# Frontend Configuration
FRONTEND_URL=http://localhost:3000
FRONTEND_PAYMENT_CALLBACK_PATH=/payment/callback
```

### 2. Config File (`config/frontend.php`)
```php
return [
    'url' => env('FRONTEND_URL', 'http://localhost:3000'),
    'payment_callback_path' => env('FRONTEND_PAYMENT_CALLBACK_PATH', '/payment/callback'),
];
```

## Usage in Controllers

### ✅ Correct Way (Using Config)
```php
// Get frontend URL
$frontendUrl = config('frontend.url');

// Get payment callback URL
$callbackPath = config('frontend.payment_callback_path');
$callbackUrl = rtrim($frontendUrl, '/') . $callbackPath;
```

### ❌ Incorrect Way (Direct env() call)
```php
// DON'T DO THIS in controllers
$frontendUrl = env('FRONTEND_URL');
```

## Benefits

1. **Centralized Configuration**: All frontend-related settings in one place
2. **Config Caching**: Works properly with `php artisan config:cache`
3. **Easier Testing**: Can override config values in tests
4. **Type Safety**: Config files are cached and validated
5. **Default Values**: Fallback values defined in config file

## Current Implementation

The following areas now use the frontend config:

### 1. Paystack Payment Callbacks
**File**: `app/Http/Controllers/Api/CheckoutController.php`

```php
private function initializePaystackPayment(Order $order, $user): array
{
    $frontendUrl = config('frontend.url');
    $callbackPath = config('frontend.payment_callback_path');
    $callbackUrl = $frontendUrl 
        ? rtrim($frontendUrl, '/') . $callbackPath
        : route('payment.callback');
    
    // Use $callbackUrl in Paystack initialization
}
```

### 2. Sanctum Stateful Domains
**File**: `config/sanctum.php`

Automatically includes the frontend URL host in stateful domains for cookie-based authentication.

## Environment-Specific Setup

### Local Development
```env
FRONTEND_URL=http://localhost:3000
```

### Staging
```env
FRONTEND_URL=https://staging.yourdomain.com
```

### Production
```env
FRONTEND_URL=https://yourdomain.com
```

## CORS Configuration

If you need to configure CORS for the frontend, you can add a CORS middleware or update `config/cors.php` to use:

```php
'allowed_origins' => [
    config('frontend.url'),
    'http://localhost:3000',
],
```

## Additional Frontend Config Examples

You can extend `config/frontend.php` with more settings:

```php
return [
    'url' => env('FRONTEND_URL', 'http://localhost:3000'),
    'payment_callback_path' => env('FRONTEND_PAYMENT_CALLBACK_PATH', '/payment/callback'),
    
    // Additional settings
    'password_reset_url' => env('FRONTEND_PASSWORD_RESET_URL', '/reset-password'),
    'email_verification_url' => env('FRONTEND_EMAIL_VERIFY_URL', '/verify-email'),
    'app_name' => env('FRONTEND_APP_NAME', 'EasyGear'),
];
```

## Testing

When writing tests, you can override the frontend URL:

```php
public function test_payment_callback_uses_frontend_url()
{
    config(['frontend.url' => 'https://test-frontend.com']);
    
    // Your test code here
}
```

## Deployment Checklist

- [ ] Update `FRONTEND_URL` in `.env` to production URL
- [ ] Run `php artisan config:cache` after deploying
- [ ] Verify Paystack callback URL is correct
- [ ] Test payment flow end-to-end
- [ ] Update CORS settings if needed
- [ ] Update Sanctum stateful domains

## Quick Commands

```bash
# Clear and cache config
php artisan config:clear
php artisan config:cache

# View current config
php artisan tinker
>>> config('frontend.url')
```

## Troubleshooting

### Issue: Config changes not reflecting
**Solution**: Clear config cache
```bash
php artisan config:clear
```

### Issue: Paystack callback going to wrong URL
**Solution**: Check `.env` file and ensure `FRONTEND_URL` is set correctly
```bash
cat .env | grep FRONTEND_URL
```

### Issue: CORS errors from frontend
**Solution**: Verify frontend URL matches the origin in CORS config and Sanctum stateful domains
