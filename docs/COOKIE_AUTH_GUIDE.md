# Cookie-Based Authentication Guide

## Overview

The EasyGear API now uses **HTTP-only cookies** for storing authentication tokens instead of returning them in the response body. This approach provides enhanced security by preventing XSS attacks, as JavaScript cannot access HTTP-only cookies.

## How It Works

### 1. **Token Storage**
- Access tokens are stored in HTTP-only cookies named `access_token`
- Cookies are automatically sent with every request to the API
- No need to manually include `Authorization` headers

### 2. **Security Features**
- **HTTP-only**: JavaScript cannot access the token
- **Secure**: Cookies are sent only over HTTPS in production
- **SameSite**: Prevents CSRF attacks
- **Automatic expiration**: Tokens expire after 2 hours (configurable)

### 3. **Middleware**
- `CookieTokenMiddleware` automatically extracts tokens from cookies
- Sets the `Authorization` header for Sanctum processing
- Works seamlessly with existing Sanctum authentication

## API Endpoints

### Public Endpoints

#### Register User
```http
POST /api/v1/register
Content-Type: application/json

{
  "name": "John Doe",
  "username": "johndoe", 
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone_number": "+2348012345678",
  "role": "customer"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "username": "johndoe",
      "email": "john@example.com",
      "role": "customer",
      "phone_number": "+2348012345678",
      "is_active": true,
      "addresses": []
    }
  }
}
```

**Cookie Set:** `access_token` (HTTP-only, 2 hours expiration)

#### Login User
```http
POST /api/v1/login
Content-Type: application/json

{
  "login": "johndoe",  // Can be email or username
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "username": "johndoe",
      "email": "john@example.com",
      "role": "customer"
    }
  }
}
```

**Cookie Set:** `access_token` (HTTP-only, 2 hours expiration)

### Protected Endpoints

All protected endpoints automatically read the token from cookies - no Authorization header needed!

#### Get User Profile
```http
GET /api/v1/profile
```

#### Update Profile
```http
PUT /api/v1/profile
Content-Type: application/json

{
  "name": "John Updated",
  "phone_number": "+2348087654321"
}
```

#### Change Password
```http
POST /api/v1/change-password
Content-Type: application/json

{
  "current_password": "password123",
  "new_password": "newpassword123",
  "new_password_confirmation": "newpassword123"
}
```

#### Refresh Token
```http
POST /api/v1/refresh-token
```

**Response:**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "user": { ... }
  }
}
```

**Cookie Updated:** New `access_token` with fresh expiration

#### Logout
```http
POST /api/v1/logout
```

**Cookie Cleared:** `access_token` is removed

#### Logout All Devices
```http
POST /api/v1/logout-all
```

**Cookie Cleared:** `access_token` is removed

## Frontend Integration

### JavaScript/Fetch API

```javascript
// Login
const login = async (credentials) => {
  const response = await fetch('/api/v1/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'include', // Important: Include cookies
    body: JSON.stringify(credentials)
  });
  
  return response.json();
};

// Make authenticated requests
const getProfile = async () => {
  const response = await fetch('/api/v1/profile', {
    method: 'GET',
    credentials: 'include', // Important: Include cookies
  });
  
  return response.json();
};

// Logout
const logout = async () => {
  const response = await fetch('/api/v1/logout', {
    method: 'POST',
    credentials: 'include', // Important: Include cookies
  });
  
  return response.json();
};
```

### Axios Configuration

```javascript
// Configure Axios to always send cookies
axios.defaults.withCredentials = true;

// Or per request
const response = await axios.get('/api/v1/profile', {
  withCredentials: true
});
```

### React/Vue.js Integration

```javascript
// Set up global axios configuration
import axios from 'axios';

axios.defaults.baseURL = 'http://127.0.0.1:8000';
axios.defaults.withCredentials = true;

// Now all requests will include cookies automatically
const api = {
  async login(credentials) {
    return axios.post('/api/v1/login', credentials);
  },
  
  async getProfile() {
    return axios.get('/api/v1/profile');
  },
  
  async logout() {
    return axios.post('/api/v1/logout');
  }
};
```

## Configuration

### Environment Variables

```env
# Session Configuration
SESSION_LIFETIME=120  # Cookie lifetime in minutes
SESSION_DOMAIN=null   # Set to your domain in production

# Sanctum Configuration  
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
SANCTUM_TOKEN_EXPIRATION=120  # Token expiration in minutes
```

### Production Considerations

1. **HTTPS Required**: Set `SESSION_SECURE_COOKIE=true` in production
2. **Domain Configuration**: Set proper `SESSION_DOMAIN` for your production domain
3. **CORS Configuration**: Ensure CORS allows credentials for your frontend domain

```env
# Production settings
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,app.yourdomain.com
```

## Benefits of Cookie-Based Authentication

### 🔒 **Enhanced Security**
- **XSS Protection**: HTTP-only cookies can't be accessed by JavaScript
- **CSRF Protection**: SameSite attribute prevents cross-site attacks
- **Automatic HTTPS**: Secure flag ensures cookies only sent over HTTPS

### 🎯 **Better User Experience**
- **No Token Management**: Frontend doesn't need to store/manage tokens
- **Automatic Refresh**: Tokens can be refreshed seamlessly
- **Session Persistence**: Users stay logged in across browser sessions

### 🛠️ **Developer Benefits**
- **Simpler Frontend Code**: No need to include Authorization headers
- **Automatic Inclusion**: Cookies are sent automatically with requests
- **Standard Web Security**: Follows established web security patterns

## Migration from Bearer Tokens

If you're migrating from the previous bearer token system:

### Before (Bearer Token)
```javascript
const token = localStorage.getItem('access_token');
const response = await fetch('/api/v1/profile', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

### After (Cookie-Based)
```javascript
const response = await fetch('/api/v1/profile', {
  credentials: 'include'  // This is all you need!
});
```

## Testing with cURL

```bash
# Login and save cookies
curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"login": "admin", "password": "admin123"}' \
  -c cookies.txt

# Use cookies for authenticated request
curl -X GET http://127.0.0.1:8000/api/v1/profile \
  -b cookies.txt

# Logout (clears cookies)
curl -X POST http://127.0.0.1:8000/api/v1/logout \
  -b cookies.txt \
  -c cookies.txt
```

This cookie-based authentication system provides a more secure and user-friendly approach to API authentication while maintaining full compatibility with the existing Laravel Sanctum infrastructure.
