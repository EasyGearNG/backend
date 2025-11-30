# EasyGear Waitlist API Documentation

## Overview

The waitlist functionality allows potential customers to sign up before the official launch of EasyGear. This helps gauge interest and build a customer base for marketing purposes.

## Database Schema

### Waitlist Table
```sql
- id (Primary Key)
- name (VARCHAR) - Full name of the person
- email (VARCHAR, UNIQUE) - Email address  
- phone (VARCHAR) - Phone number
- joined_at (TIMESTAMP) - When they joined the waitlist
- created_at (TIMESTAMP) - Record creation time
- updated_at (TIMESTAMP) - Last update time
```

## API Endpoints

### 🌍 Public Endpoints

#### 1. Join Waitlist
Join the EasyGear waitlist for launch notifications.

```http
POST /api/v1/waitlist/join
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com", 
  "phone": "+2348012345678"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Successfully joined the waitlist! We'll notify you when EasyGear launches.",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "joined_at": "2025-09-23 13:45:30"
  }
}
```

**Validation Error Response (422):**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "email": ["This email is already on the waitlist."],
    "name": ["The name field is required."],
    "phone": ["The phone field is required."]
  }
}
```

#### 2. Check Email Status
Check if an email is already on the waitlist.

```http
POST /api/v1/waitlist/check-email
Content-Type: application/json

{
  "email": "john@example.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Email check completed",
  "data": {
    "email": "john@example.com",
    "exists": true,
    "status": "already_on_waitlist"
  }
}
```

### 🔐 Protected Endpoints (Admin Only)

#### 3. Waitlist Statistics
Get comprehensive waitlist analytics (admin access required).

```http
GET /api/v1/waitlist/stats
Authorization: Cookie-based (admin role required)
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Waitlist statistics retrieved successfully",
  "data": {
    "total_signups": 1250,
    "recent_signups": 87,
    "today_signups": 12,
    "growth_rate": 6.96
  }
}
```

## Usage Examples

### Frontend JavaScript Integration

#### Join Waitlist Form
```javascript
const joinWaitlist = async (formData) => {
  try {
    const response = await fetch('/api/v1/waitlist/join', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        name: formData.name,
        email: formData.email,
        phone: formData.phone
      })
    });

    const result = await response.json();
    
    if (result.success) {
      // Show success message
      alert(result.message);
    } else {
      // Handle validation errors
      console.error('Validation errors:', result.errors);
    }
  } catch (error) {
    console.error('Network error:', error);
  }
};
```

#### Email Availability Check
```javascript
const checkEmailAvailability = async (email) => {
  try {
    const response = await fetch('/api/v1/waitlist/check-email', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email })
    });

    const result = await response.json();
    return result.data.exists;
  } catch (error) {
    console.error('Error checking email:', error);
    return false;
  }
};
```

#### Admin Dashboard Stats
```javascript
const getWaitlistStats = async () => {
  try {
    const response = await fetch('/api/v1/waitlist/stats', {
      credentials: 'include' // Include auth cookies
    });

    const result = await response.json();
    
    if (result.success) {
      return result.data;
    }
  } catch (error) {
    console.error('Error fetching stats:', error);
  }
};
```

### React Component Example

```jsx
import React, { useState } from 'react';

const WaitlistForm = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: ''
  });
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await fetch('/api/v1/waitlist/join', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      });

      const result = await response.json();
      
      if (result.success) {
        setMessage(result.message);
        setFormData({ name: '', email: '', phone: '' });
      } else {
        setMessage('Please check your information and try again.');
      }
    } catch (error) {
      setMessage('Something went wrong. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="waitlist-form">
      <h2>Join the EasyGear Waitlist</h2>
      
      <input
        type="text"
        placeholder="Full Name"
        value={formData.name}
        onChange={(e) => setFormData({...formData, name: e.target.value})}
        required
      />
      
      <input
        type="email"
        placeholder="Email Address"
        value={formData.email}
        onChange={(e) => setFormData({...formData, email: e.target.value})}
        required
      />
      
      <input
        type="tel"
        placeholder="Phone Number"
        value={formData.phone}
        onChange={(e) => setFormData({...formData, phone: e.target.value})}
        required
      />
      
      <button type="submit" disabled={loading}>
        {loading ? 'Joining...' : 'Join Waitlist'}
      </button>
      
      {message && <p className="message">{message}</p>}
    </form>
  );
};
```

### cURL Testing Examples

#### Join Waitlist
```bash
curl -X POST http://127.0.0.1:8000/api/v1/waitlist/join \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "phone": "+2348012345678"
  }'
```

#### Check Email
```bash
curl -X POST http://127.0.0.1:8000/api/v1/waitlist/check-email \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com"
  }'
```

#### Get Stats (Admin)
```bash
# First login as admin
curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "admin",
    "password": "admin123"
  }' \
  -c cookies.txt

# Then get waitlist stats
curl -X GET http://127.0.0.1:8000/api/v1/waitlist/stats \
  -b cookies.txt
```

## Features

### ✅ **Core Functionality**
- **Simple Signup**: Just name, email, and phone required
- **Duplicate Prevention**: Email uniqueness enforced at database level
- **Validation**: Comprehensive input validation with helpful error messages
- **Timestamps**: Track when users joined for analytics

### ✅ **Admin Features**
- **Statistics Dashboard**: Total signups, recent activity, growth metrics
- **Role-Based Access**: Only admins can view statistics
- **Analytics Ready**: Data structure supports advanced analytics

### ✅ **Developer Features**
- **Race Condition Safe**: Database constraints prevent duplicate entries
- **RESTful Design**: Clean, predictable API endpoints
- **Error Handling**: Comprehensive error responses with details
- **Scalable**: Indexed for performance with large datasets

## Data Privacy & Security

- **Email Uniqueness**: Prevents spam and duplicate entries
- **Input Validation**: Sanitizes all user input
- **Admin Protection**: Statistics endpoint requires admin authentication
- **No Sensitive Data**: Only collects necessary contact information

## Integration Tips

### Frontend Best Practices
1. **Progressive Enhancement**: Make the form work without JavaScript
2. **Real-time Validation**: Check email availability as user types
3. **Success Handling**: Clear form and show confirmation message
4. **Error Display**: Show specific validation errors to users

### Marketing Integration
1. **Analytics Tracking**: Track conversion rates and source attribution
2. **Email Campaigns**: Export waitlist for pre-launch marketing
3. **A/B Testing**: Test different signup forms and messaging
4. **Social Proof**: Display signup count to encourage more signups

This waitlist system provides a solid foundation for building interest and collecting leads before your EasyGear launch! 🚀