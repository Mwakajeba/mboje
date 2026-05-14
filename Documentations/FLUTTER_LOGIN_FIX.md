# Flutter Login Code Fix

## Issues Found:

1. **Wrong field name**: Using `username` instead of `phone`
2. **Missing Accept header**: Should include `Accept: application/json`
3. **Error handling**: Need to parse JSON response properly

## Fixed Flutter Code:

```dart
import 'package:flutter/material.dart';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  runApp(const SmartSchoolApp());
}

class SmartSchoolApp extends StatelessWidget {
  const SmartSchoolApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SmartSchool Parent Portal',
      theme: ThemeData(
        useMaterial3: true,
        primarySwatch: Colors.blue,
        scaffoldBackgroundColor: Colors.white,
      ),
      home: const LoginPage(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool _rememberMe = false;
  bool _isLoading = false;

  void _login() async {
    if (_phoneController.text.isEmpty || _passwordController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please fill in all fields')),
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.post(
        Uri.parse('https://demo.smartsoft.co.tz/api/parent/login'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json', // IMPORTANT: This ensures JSON response
        },
        body: jsonEncode({
          'phone': _phoneController.text, // Changed from 'username' to 'phone'
          'password': _passwordController.text,
        }),
      );

      print('Status Code: ${response.statusCode}');
      print('Response Body: ${response.body}');

      // Parse the JSON response
      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200 && responseData['success'] == true) {
        // Save token for future requests
        final token = responseData['data']['token'];
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('parent_token', token);
        
        // Save user data if needed
        await prefs.setString('parent_name', responseData['data']['user']['name']);
        await prefs.setString('parent_phone', responseData['data']['user']['phone']);

        // Navigate to home
        if (mounted) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (context) => const HomePage()),
          );
        }
      } else {
        // Show error message from API
        final errorMessage = responseData['message'] ?? 'Login failed';
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(errorMessage)),
          );
        }
      }
    } catch (e) {
      print('Error: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: ${e.toString()}')),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Parent Login'),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _phoneController,
              decoration: const InputDecoration(
                labelText: 'Phone Number',
                hintText: '255123456789',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _passwordController,
              decoration: const InputDecoration(
                labelText: 'Password',
                border: OutlineInputBorder(),
              ),
              obscureText: true,
            ),
            const SizedBox(height: 16),
            CheckboxListTile(
              title: const Text('Remember me'),
              value: _rememberMe,
              onChanged: (value) {
                setState(() {
                  _rememberMe = value ?? false;
                });
              },
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _isLoading ? null : _login,
              style: ElevatedButton.styleFrom(
                minimumSize: const Size(double.infinity, 50),
              ),
              child: _isLoading
                  ? const CircularProgressIndicator()
                  : const Text('Login'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }
}

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Parent Portal'),
      ),
      body: const Center(
        child: Text('Welcome to Parent Portal'),
      ),
    );
  }
}
```

## Key Changes:

1. **Changed `username` to `phone`** - The API expects `phone` field
2. **Added `Accept: application/json` header** - This ensures the server returns JSON instead of HTML
3. **Changed TextField label** - From "Username" to "Phone Number"
4. **Added JSON parsing** - Properly parse the response to check `success` field
5. **Added token storage** - Save the token using SharedPreferences for authenticated requests
6. **Better error handling** - Show the actual error message from API
7. **Added `mounted` checks** - Prevent setState after widget disposal

## Don't forget to add to pubspec.yaml:

```yaml
dependencies:
  http: ^1.1.0
  shared_preferences: ^2.2.0
```

