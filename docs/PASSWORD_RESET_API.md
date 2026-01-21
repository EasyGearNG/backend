# Password Reset API

This document describes the password reset functionality using email links.

## Overview

The password reset flow consists of two steps:
1. **Forgot Password**: User requests a password reset link
2. **Reset Password**: User uses the token from the email to set a new password

## Endpoints

### 1. Request Password Reset Link

**POST** `/api/v1/forgot-password`

Sends a password reset link to the user's email address.

#### Request Body

```json
{
  "email": "user@example.com"
}
```

#### Success Response (200)

```json
{
  "success": true,
  "message": "Password reset link sent to your email address."
}
```

#### Error Responses

**422 Validation Error**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "email": [
      "The email field is required.",
      "The email must be a valid email address.",
      "The selected email does not exist in our records."
    ]
  }
}
```

**500 Server Error**
```json
{
  "success": false,
  "message": "Unable to send reset link. Please try again."
}
```

---

### 2. Reset Password

**POST** `/api/v1/reset-password`

Resets the user's password using the token received via email.

#### Request Body

```json
{
  "token": "64-character-reset-token",
  "email": "user@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

#### Parameters

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `token` | string | Yes | Reset token from email link |
| `email` | string | Yes | User's email address |
| `password` | string | Yes | New password (min 8 characters) |
| `password_confirmation` | string | Yes | Must match password field |

#### Success Response (200)

```json
{
  "success": true,
  "message": "Password has been reset successfully."
}
```

#### Error Responses

**422 Validation Error**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "password": [
      "The password field is required.",
      "The password must be at least 8 characters.",
      "The password confirmation does not match."
    ]
  }
}
```

**400 Bad Request** (Invalid/Expired Token)
```json
{
  "success": false,
  "message": "This password reset token is invalid."
}
```

---

## Frontend Integration

### Password Reset Flow

1. **Request Reset Link**
   - User enters email on forgot password page
   - Frontend calls `/api/v1/forgot-password`
   - User receives email with reset link

2. **Reset Password Page**
   - Email link format: `{FRONTEND_URL}/reset-password?token={TOKEN}&email={EMAIL}`
   - Frontend extracts `token` and `email` from URL parameters
   - User enters new password
   - Frontend calls `/api/v1/reset-password` with token, email, and new password

### Example Frontend Implementation

```javascript
// 1. Request password reset link
async function requestPasswordReset(email) {
  const response = await fetch('http://your-api.com/api/v1/forgot-password', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email })
  });
  
  const data = await response.json();
  
  if (data.success) {
    alert('Password reset link sent! Check your email.');
  } else {
    alert(data.message);
  }
}

// 2. Reset password with token
async function resetPassword(token, email, password, passwordConfirmation) {
  const response = await fetch('http://your-api.com/api/v1/reset-password', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      token,
      email,
      password,
      password_confirmation: passwordConfirmation
    })
  });
  
  const data = await response.json();
  
  if (data.success) {
    alert('Password reset successfully! You can now login.');
    // Redirect to login page
  } else {
    alert(data.message);
  }
}

// Example: Extract token and email from URL
const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get('token');
const email = urlParams.get('email');
```

---

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# Frontend URL for password reset links
FRONTEND_URL=http://localhost:3000

# Mail Configuration (required for sending emails)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # or your mail server
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS="noreply@easygear.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Mail Configuration

For development, you can use:
- **Mailtrap**: https://mailtrap.io (email testing)
- **Log Driver**: `MAIL_MAILER=log` (emails saved to logs)

For production, configure SMTP settings for your email provider:
- Gmail, SendGrid, AWS SES, etc.

---

## Email Template

The password reset email is sent using Laravel's default notification. The email will contain:
- A link to reset the password
- The link expires after 60 minutes (configurable)

To customize the email template, publish the notification views:

```bash
php artisan vendor:publish --tag=laravel-notifications
```

Then edit: `resources/views/vendor/notifications/email.blade.php`

---

## Security Notes

1. **Token Expiration**: Reset tokens expire after 60 minutes
2. **One-Time Use**: Each token can only be used once
3. **HTTPS**: Always use HTTPS in production
4. **Rate Limiting**: Consider adding rate limiting to prevent abuse
5. **Email Verification**: Tokens are sent only to verified email addresses in the database

---

## Testing

### Using cURL

```bash
# 1. Request reset link
curl -X POST http://localhost:8000/api/v1/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com"}'

# 2. Reset password (use token from email)
curl -X POST http://localhost:8000/api/v1/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "token":"your-reset-token-here",
    "email":"user@example.com",
    "password":"newpassword123",
    "password_confirmation":"newpassword123"
  }'
```

### Using Postman

Import the collection and test both endpoints with various scenarios:
- Valid email
- Non-existent email
- Valid token
- Expired token
- Mismatched passwords
