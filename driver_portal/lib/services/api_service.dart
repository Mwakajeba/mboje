import 'package:dio/dio.dart';
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/api_config.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  final Dio _dio = Dio();
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  bool _init = false;

  void init() {
    if (_init) return;
    _init = true;
    _dio.options.baseUrl = ApiConfig.baseUrl;
    _dio.options.connectTimeout = const Duration(seconds: 30);
    _dio.options.receiveTimeout = const Duration(seconds: 30);
    _dio.options.headers['Content-Type'] = 'application/json';
    _dio.options.headers['Accept'] = 'application/json';
    _dio.options.responseType = ResponseType.plain;
    _dio.options.validateStatus = (status) => status! < 500;

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        if (_tempToken != null) {
          options.headers['Authorization'] = 'Bearer $_tempToken';
          return handler.next(options);
        }
        final token = await _storage.read(key: 'driver_auth_token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          _storage.delete(key: 'driver_auth_token');
        }
        return handler.next(error);
      },
    ));
  }

  /// Update base URL (e.g. after user sets Server URL on Android).
  void updateBaseUrl(String url) {
    _dio.options.baseUrl = url;
  }

  /// Use a temporary token (e.g. reset token) for one request.
  void setTempToken(String? token) {
    _tempToken = token;
  }

  String? _tempToken;

  Future<Response> get(String endpoint, {Map<String, dynamic>? queryParameters}) async {
    try {
      final response = await _dio.get(endpoint, queryParameters: queryParameters);
      return _parseResponse(response);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response> post(String endpoint, {dynamic data}) async {
    try {
      final response = await _dio.post(endpoint, data: data);
      final result = _parseResponse(response);
      _clearTempToken();
      return result;
    } on DioException catch (e) {
      _clearTempToken();
      throw _handleError(e);
    }
  }

  Future<Response> put(String endpoint, {dynamic data}) async {
    try {
      final response = await _dio.put(endpoint, data: data);
      return _parseResponse(response);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  void _clearTempToken() {
    _tempToken = null;
  }

  Response _parseResponse(Response response) {
    if (response.data is String) {
      final s = response.data as String;
      if (s.contains('<!DOCTYPE') || s.contains('<html') || s.trim().startsWith('<')) {
        throw DioException(
          requestOptions: response.requestOptions,
          response: Response(
            requestOptions: response.requestOptions,
            statusCode: response.statusCode ?? 500,
            data: {'success': false, 'message': 'Server returned an error page.'},
          ),
          type: DioExceptionType.badResponse,
        );
      }
      try {
        return Response(
          requestOptions: response.requestOptions,
          statusCode: response.statusCode,
          data: jsonDecode(s),
          headers: response.headers,
        );
      } catch (_) {
        throw DioException(
          requestOptions: response.requestOptions,
          response: Response(
            requestOptions: response.requestOptions,
            statusCode: response.statusCode,
            data: {'success': false, 'message': 'Invalid response.'},
          ),
          type: DioExceptionType.badResponse,
        );
      }
    }
    return response;
  }

  dynamic _handleError(DioException error) {
    if (error.response != null) {
      final d = error.response?.data;
      if (d is Map && d.containsKey('message')) return d['message'];
      return 'Server error: ${error.response?.statusCode}';
    }
    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.connectionError) {
      return 'Connection error. Check your internet.';
    }
    return 'Network error.';
  }

  Future<void> saveToken(String token) async {
    await _storage.write(key: 'driver_auth_token', value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: 'driver_auth_token');
  }

  Future<void> removeToken() async {
    await _storage.delete(key: 'driver_auth_token');
  }
}
