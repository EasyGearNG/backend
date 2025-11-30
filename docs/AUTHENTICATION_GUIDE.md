# 🔑 Authentication & Bearer Token Guide

## ✅ Updated: Token Now Returned in Response

The `AuthController` has been updated to return the Bearer token directly in the response body for both registration and login endpoints.

---

## 📡 API Endpoints

### 1. Register (Sign Up)

**Endpoint:** `POST /api/v1/register`

**Request Body:**
```json
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

**Response (201 Created):**
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
      "phone_number": "+2348012345678",
      "role": "customer",
      "is_active": true,
      "created_at": "2025-10-11T10:30:00.000000Z",
      "updated_at": "2025-10-11T10:30:00.000000Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz123456789",
    "token_type": "Bearer"
  }
}
```

### 2. Login

**Endpoint:** `POST /api/v1/login`

**Request Body:**
```json
{
  "login": "john@example.com",
  "password": "password123"
}
```

**Note:** `login` can be either email or username

**Response (200 OK):**
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
      "phone_number": "+2348012345678",
      "role": "customer",
      "is_active": true,
      "created_at": "2025-10-11T10:30:00.000000Z",
      "updated_at": "2025-10-11T10:30:00.000000Z"
    },
    "token": "2|zyxwvutsrqponmlkjihgfedcba987654321",
    "token_type": "Bearer"
  }
}
```

---

## 🔧 Using the Token in Postman

### Method 1: Quick Copy-Paste

1. **Send login or register request**
2. **Copy the token from the response:**
   ```
   data.token: "1|abcdefghijklmnopqrstuvwxyz123456789"
   ```
3. **In your next request:**
   - Go to **Authorization** tab
   - Select **Bearer Token**
   - Paste the token (without "Bearer" prefix)

### Method 2: Auto-Save with Postman Script

**In the Login/Register request, add to the "Tests" tab:**

```javascript
// Auto-save token to environment variable
if (pm.response.code === 200 || pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.success && jsonData.data.token) {
        pm.environment.set("token", jsonData.data.token);
        console.log("Token saved:", jsonData.data.token);
    }
}
```

Now the token will be automatically saved after login/registration!

**In other requests, use:**
- Authorization: Bearer Token
- Token: `{{token}}`

---

## 📝 Complete Postman Workflow

### Step 1: Create Environment
1. Click **Environments** (top right)
2. Click **Create Environment**
3. Name it: "Afiemo Local"
4. Add variable:
   - **Variable:** `token`
   - **Type:** default
   - **Initial Value:** (leave empty)
5. Save

### Step 2: Setup Login Request

**Request:**
```
Method: POST
URL: http://localhost:8000/api/v1/login
Headers:
  - Content-Type: application/json
  - Accept: application/json
Body (raw JSON):
{
  "login": "john@example.com",
  "password": "password123"
}
```

**Tests Tab (auto-save token):**
```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("token", jsonData.data.token);
}
```

### Step 3: Use Token in Cart Request

**Request:**
```
Method: POST
URL: http://localhost:8000/api/v1/cart/add
Authorization:
  - Type: Bearer Token
  - Token: {{token}}
Headers:
  - Content-Type: application/json
  - Accept: application/json
Body (raw JSON):
{
  "product_id": 1,
  "quantity": 2
}
```

---

## 🔐 Complete Example Workflow

### 1. Register New User
```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "customer"
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": { ... },
    "token": "1|xyz123...",
    "token_type": "Bearer"
  }
}
```

### 2. Save Token
```bash
TOKEN="1|xyz123..."
```

### 3. Use Token to Add to Cart
```bash
curl -X POST http://localhost:8000/api/v1/cart/add \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 2
  }'
```

---

## 🎯 Frontend Integration

### JavaScript/Fetch Example

```javascript
// Login and save token
async function login(email, password) {
  try {
    const response = await fetch('http://localhost:8000/api/v1/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        login: email,
        password: password
      })
    });

    const data = await response.json();
    
    if (data.success) {
      // Save token to localStorage
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
      
      console.log('Login successful!');
      console.log('Token:', data.data.token);
      
      return data.data;
    } else {
      console.error('Login failed:', data.message);
    }
  } catch (error) {
    console.error('Login error:', error);
  }
}

// Use token in subsequent requests
async function addToCart(productId, quantity) {
  const token = localStorage.getItem('token');
  
  const response = await fetch('http://localhost:8000/api/v1/cart/add', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      product_id: productId,
      quantity: quantity
    })
  });

  const data = await response.json();
  return data;
}

// Usage
await login('john@example.com', 'password123');
await addToCart(1, 2);
```

### React Example

```javascript
import { useState, useEffect } from 'react';

function useAuth() {
  const [token, setToken] = useState(null);
  const [user, setUser] = useState(null);

  useEffect(() => {
    // Load token from localStorage on mount
    const savedToken = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    
    if (savedToken) setToken(savedToken);
    if (savedUser) setUser(JSON.parse(savedUser));
  }, []);

  const login = async (email, password) => {
    const response = await fetch('/api/v1/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ login: email, password })
    });

    const data = await response.json();
    
    if (data.success) {
      setToken(data.data.token);
      setUser(data.data.user);
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
    }
    
    return data;
  };

  const logout = () => {
    setToken(null);
    setUser(null);
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  };

  return { token, user, login, logout };
}

// Usage in component
function App() {
  const { token, user, login } = useAuth();

  const handleLogin = async () => {
    await login('john@example.com', 'password123');
  };

  return (
    <div>
      {token ? (
        <p>Logged in as {user?.name}</p>
      ) : (
        <button onClick={handleLogin}>Login</button>
      )}
    </div>
  );
}
```

---

## 📋 Token Information

### Token Format
```
{id}|{hash}
Example: 1|abcdefghijklmnopqrstuvwxyz123456789
```

### Token Properties
- **Type:** Laravel Sanctum Personal Access Token
- **Lifetime:** Configurable (default: no expiration)
- **Storage:** Returned in response + HTTP-only cookie
- **Security:** Use HTTPS in production

### Using the Token
```http
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789
```

**Important:** Include the word `Bearer` followed by a space, then the token!

---

## 🔒 Security Best Practices

1. **HTTPS Only in Production** - Never use Bearer tokens over HTTP
2. **Store Securely** - Use localStorage for web apps (or better: use the HTTP-only cookie)
3. **Never Log Tokens** - Don't console.log tokens in production
4. **Token Rotation** - Consider implementing token refresh
5. **Logout Properly** - Clear tokens on logout

---

## 🚨 Troubleshooting

### Error: "Unauthenticated"
✅ Check you included `Bearer` before the token
✅ Verify token hasn't been revoked
✅ Check Authorization header format

### Error: "Token is invalid"
✅ Copy the complete token from the response
✅ Don't modify the token
✅ Login again to get a fresh token

### Token Not Saved in Postman
✅ Add the auto-save script to the Tests tab
✅ Select the correct environment
✅ Check Console for errors

---

## 📖 API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    "user": { ... },
    "token": "1|xyz...",
    "token_type": "Bearer"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }
}
```

---

## ✅ What Changed

### Before
```json
{
  "data": {
    "user": { ... }
    // No token in response!
  }
}
```

### After (Now)
```json
{
  "data": {
    "user": { ... },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

---

## 🎉 You're Ready!

Now when you login or register, you'll receive the token directly in the response. Just copy it and use it for all authenticated requests!

**Quick Steps:**
1. Login/Register
2. Copy `data.token` from response
3. Use as `Authorization: Bearer {token}`
4. Start making authenticated requests!

---

For more information, see:
- `CART_CHECKOUT_API.md` - Cart and checkout endpoints
- `IMPLEMENTATION_COMPLETE.md` - Complete system overview
