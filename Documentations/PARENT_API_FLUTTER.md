# Parent API Endpoints for Flutter

## Base URL
Replace `YOUR_DOMAIN` with your actual domain:
```
https://YOUR_DOMAIN/api/parent
```

For local development:
```
http://localhost:8000/api/parent
```

---

## 1. Login Endpoint

### POST `/api/parent/login`

**Description:** Authenticate a parent user and receive an access token.

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "phone": "255123456789",
  "password": "your_password"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Parent Name",
      "phone": "255123456789",
      "email": "parent@example.com",
      "role": "parent"
    }
  }
}
```

**Error Responses:**

**401 - Invalid Credentials:**
```json
{
  "success": false,
  "message": "Invalid phone number or password."
}
```

**403 - Access Denied:**
```json
{
  "success": false,
  "message": "Access denied. This account is not authorized for parent access."
}
```

**429 - Account Locked:**
```json
{
  "success": false,
  "message": "Account is temporarily locked. Please try again in X minutes."
}
```

---

## 2. Get Current User (Me)

### GET `/api/parent/me`

**Description:** Get the authenticated parent user's information.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Parent Name",
    "phone": "255123456789",
    "email": "parent@example.com",
    "role": "parent",
    "company_id": 1,
    "branch_id": 1
  }
}
```

**Error Response (401 - Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

## 3. Logout Endpoint

### POST `/api/parent/logout`

**Description:** Logout the authenticated parent user and revoke the current token.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**Error Response (401 - Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

## Flutter Implementation Example

### 1. Add HTTP package to `pubspec.yaml`:
```yaml
dependencies:
  http: ^1.1.0
  shared_preferences: ^2.2.0
```

### 2. API Service Class:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ParentApiService {
  // Replace with your actual base URL
  static const String baseUrl = 'https://YOUR_DOMAIN/api/parent';
  
  // Login
  static Future<Map<String, dynamic>> login(String phone, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'phone': phone,
          'password': password,
        }),
      );
      
      final data = jsonDecode(response.body);
      
      if (response.statusCode == 200 && data['success'] == true) {
        // Save token
        final token = data['data']['token'];
        await _saveToken(token);
        return {'success': true, 'data': data['data']};
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Login failed',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }
  
  // Get current user
  static Future<Map<String, dynamic>> getMe() async {
    try {
      final token = await _getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }
      
      final response = await http.get(
        Uri.parse('$baseUrl/me'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );
      
      final data = jsonDecode(response.body);
      
      if (response.statusCode == 200 && data['success'] == true) {
        return {'success': true, 'data': data['data']};
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to get user info',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }
  
  // Logout
  static Future<Map<String, dynamic>> logout() async {
    try {
      final token = await _getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }
      
      final response = await http.post(
        Uri.parse('$baseUrl/logout'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );
      
      final data = jsonDecode(response.body);
      
      if (response.statusCode == 200 && data['success'] == true) {
        await _deleteToken();
        return {'success': true, 'message': data['message']};
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Logout failed',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }
  
  // Token management
  static Future<void> _saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('parent_token', token);
  }
  
  static Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('parent_token');
  }
  
  static Future<void> _deleteToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('parent_token');
  }
  
  // Check if user is logged in
  static Future<bool> isLoggedIn() async {
    final token = await _getToken();
    return token != null;
  }
}
```

### 3. Usage Example:

```dart
// Login
final result = await ParentApiService.login('255123456789', 'password123');
if (result['success']) {
  print('Login successful!');
  print('Token: ${result['data']['token']}');
  print('User: ${result['data']['user']['name']}');
} else {
  print('Login failed: ${result['message']}');
}

// Get current user
final userInfo = await ParentApiService.getMe();
if (userInfo['success']) {
  print('User: ${userInfo['data']['name']}');
}

// Logout
final logoutResult = await ParentApiService.logout();
if (logoutResult['success']) {
  print('Logged out successfully');
}
```

---

## Important Notes

1. **Phone Number Format:** The API accepts phone numbers in various formats (255xxxxxxxxx, 0xxxxxxxxx, +255xxxxxxxxx) and normalizes them automatically.

2. **Token Storage:** Store the token securely. Consider using `flutter_secure_storage` for production apps instead of `shared_preferences`.

3. **Error Handling:** Always check the `success` field in the response and handle errors appropriately.

4. **Rate Limiting:** The login endpoint has rate limiting. If you get a 429 response, wait before retrying.

5. **Token Expiration:** Tokens don't expire by default, but you should implement token refresh logic if needed.

6. **Network Errors:** Always wrap API calls in try-catch blocks to handle network errors gracefully.

---

## Testing with cURL

### Login:
```bash
curl -X POST https://YOUR_DOMAIN/api/parent/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"phone":"255123456789","password":"your_password"}'
```

### Get Me:
```bash
curl -X GET https://YOUR_DOMAIN/api/parent/me \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Logout:
```bash
curl -X POST https://YOUR_DOMAIN/api/parent/logout \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

