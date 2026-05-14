import 'package:dio/dio.dart';
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/api_config.dart';

/// Base API Service
/// 
/// Handles all HTTP requests to the Laravel backend API
class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  final Dio _dio = Dio();
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  /// Initialize the API service
  void init() {
    // Use getter to get the correct base URL based on platform
    _dio.options.baseUrl = ApiConfig.baseUrl;
    _dio.options.connectTimeout = const Duration(seconds: 30);
    _dio.options.receiveTimeout = const Duration(seconds: 30);
    _dio.options.headers['Content-Type'] = 'application/json';
    _dio.options.headers['Accept'] = 'application/json';
    _dio.options.responseType = ResponseType.plain; // Use plain to catch HTML/JS responses
    _dio.options.followRedirects = false;
    _dio.options.validateStatus = (status) => status! < 500; // Don't throw on 4xx errors

    // Add interceptor for authentication token
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: 'auth_token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          // Handle unauthorized - token expired or invalid
          _storage.delete(key: 'auth_token');
        }
        return handler.next(error);
      },
    ));
  }

  /// GET request
  Future<Response> get(String endpoint, {Map<String, dynamic>? queryParameters}) async {
    try {
      final response = await _dio.get(
        endpoint,
        queryParameters: queryParameters,
      );
      return _parseResponse(response);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// POST request
  Future<Response> post(String endpoint, {dynamic data}) async {
    try {
      final fullUrl = '${_dio.options.baseUrl}$endpoint';
      print('=== POST REQUEST ===');
      print('URL: $fullUrl');
      if (data != null) {
        // Don't print password in logs
        final safeData = Map<String, dynamic>.from(data as Map);
        if (safeData.containsKey('password')) {
          safeData['password'] = '***';
        }
        print('Request data: $safeData');
      }
      
      final response = await _dio.post(endpoint, data: data);
      return _parseResponse(response);
    } on DioException catch (e) {
      print('DioException in POST: ${e.toString()}');
      print('Error type: ${e.type}');
      if (e.response != null) {
        print('Response status: ${e.response!.statusCode}');
        print('Response data type: ${e.response!.data.runtimeType}');
        if (e.response!.data is String) {
          final str = e.response!.data as String;
          print('Response data (first 500 chars): ${str.length > 500 ? str.substring(0, 500) : str}');
        } else {
          print('Response data: ${e.response!.data}');
        }
      } else {
        print('No response - connection error');
      }
      rethrow;
    } catch (e, stackTrace) {
      print('Non-DioException in POST: ${e.toString()}');
      print('Error type: ${e.runtimeType}');
      print('Stack trace: $stackTrace');
      rethrow;
    }
  }

  /// Parse response and handle HTML/JavaScript errors
  Response _parseResponse(Response response) {
    print('=== RESPONSE RECEIVED ===');
    print('Status Code: ${response.statusCode}');
    print('Response Type: ${response.data.runtimeType}');
    
    if (response.data is String) {
      final responseString = response.data as String;
      print('Response length: ${responseString.length}');
      
      // Check for HTML/JavaScript
      if (responseString.contains('<!DOCTYPE') || 
          responseString.contains('<html') || 
          responseString.contains('syntax error') ||
          responseString.contains('unexpected token') ||
          responseString.contains('<script>') ||
          responseString.trim().startsWith('<')) {
        print('ERROR: Server returned HTML/JavaScript instead of JSON');
        print('Response preview: ${responseString.substring(0, responseString.length > 300 ? 300 : responseString.length)}');
        
        throw DioException(
          requestOptions: response.requestOptions,
          response: Response(
            requestOptions: response.requestOptions,
            statusCode: response.statusCode ?? 500,
            statusMessage: 'Server returned HTML/JavaScript instead of JSON',
            data: {
              'success': false,
              'message': 'Server returned an error page. Please check if the API endpoint exists and the server is running correctly.',
            },
          ),
          type: DioExceptionType.badResponse,
          error: 'HTML/JavaScript response received instead of JSON',
        );
      }
      
      // Try to parse as JSON
      try {
        final jsonData = jsonDecode(responseString);
        print('JSON parsed successfully');
        // Create new response with parsed JSON
        return Response(
          requestOptions: response.requestOptions,
          statusCode: response.statusCode,
          statusMessage: response.statusMessage,
          data: jsonData,
          headers: response.headers,
        );
      } catch (e) {
        print('ERROR: Failed to parse response as JSON: $e');
        print('Response content (first 500 chars): ${responseString.length > 500 ? responseString.substring(0, 500) : responseString}');
        
        // If it's a JSON parsing error but response looks like JSON, try to extract error message
        if (responseString.contains('"message"') || responseString.contains("'message'")) {
          try {
            // Try to extract message from malformed JSON
            final messageMatch = RegExp(r'"message"\s*:\s*"([^"]+)"').firstMatch(responseString);
            if (messageMatch != null) {
              final errorMsg = messageMatch.group(1);
              throw DioException(
                requestOptions: response.requestOptions,
                response: Response(
                  requestOptions: response.requestOptions,
                  statusCode: response.statusCode ?? 500,
                  statusMessage: errorMsg,
                  data: {
                    'success': false,
                    'message': errorMsg,
                  },
                ),
                type: DioExceptionType.badResponse,
                error: errorMsg,
              );
            }
          } catch (_) {}
        }
        
        throw DioException(
          requestOptions: response.requestOptions,
          response: Response(
            requestOptions: response.requestOptions,
            statusCode: response.statusCode ?? 500,
            statusMessage: 'Invalid JSON response',
            data: {
              'success': false,
              'message': 'Server returned invalid JSON. Please check the API endpoint.',
              'error': e.toString(),
            },
          ),
          type: DioExceptionType.badResponse,
          error: 'Failed to parse JSON response: $e',
        );
      }
    }
    
    // If already parsed (shouldn't happen with ResponseType.plain, but just in case)
    return response;
  }

  /// PUT request
  Future<Response> put(String endpoint, {dynamic data, Map<String, dynamic>? queryParameters}) async {
    try {
      final response = await _dio.put(
        endpoint,
        data: data,
        queryParameters: queryParameters,
      );
      return _parseResponse(response);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// DELETE request
  Future<Response> delete(String endpoint) async {
    try {
      final response = await _dio.delete(endpoint);
      return _parseResponse(response);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Handle API errors
  String _handleError(DioException error) {
    if (error.response != null) {
      // Server responded with error
      final data = error.response?.data;
      if (data is Map && data.containsKey('message')) {
        return data['message'] as String;
      }
      return 'Server error: ${error.response?.statusCode}';
    } else if (error.type == DioExceptionType.connectionTimeout) {
      return 'Connection timeout. Please check your internet connection.';
    } else if (error.type == DioExceptionType.receiveTimeout) {
      return 'Request timeout. Please try again.';
    } else {
      return 'Network error. Please check your internet connection.';
    }
  }

  /// Save authentication token
  Future<void> saveToken(String token) async {
    await _storage.write(key: 'auth_token', value: token);
  }

  /// Get authentication token
  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }

  /// Remove authentication token
  Future<void> removeToken() async {
    await _storage.delete(key: 'auth_token');
  }
}
