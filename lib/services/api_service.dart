import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/app_logger.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  late Dio _dio;
  late Dio _externalDio; // ✅ Untuk Python API

  // ✅ FIX: Base URL harus jelas
  static const String laravelBaseUrl = 'http://10.32.249.186:8000/api';
  static const String pythonBaseUrl = 'http://10.32.249.186:8001';

  void initialize() {
    // Dio untuk Laravel API
    _dio = Dio(
      BaseOptions(
        baseUrl: laravelBaseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    // ✅ Dio terpisah untuk Python API (tanpa auth)
    _externalDio = Dio(
      BaseOptions(
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    // Add interceptors untuk Laravel API
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Add token to headers
          final token = await getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          
          // ✅ Log request
          AppLogger.debug('=== REQUEST ===');
          AppLogger.debug('URL: ${options.baseUrl}${options.path}');
          AppLogger.debug('Method: ${options.method}');
          AppLogger.debug('Headers: ${options.headers}');
          AppLogger.debug('Data: ${options.data}');
          
          return handler.next(options);
        },
        onResponse: (response, handler) {
          // ✅ Log response
          AppLogger.debug('=== RESPONSE ===');
          AppLogger.debug('Status: ${response.statusCode}');
          AppLogger.debug('Data: ${response.data}');
          
          return handler.next(response);
        },
        onError: (error, handler) async {
          // ✅ Log error
          AppLogger.error('=== ERROR ===');
          AppLogger.error('URL: ${error.requestOptions.uri}');
          AppLogger.error('Status: ${error.response?.statusCode}');
          AppLogger.error('Message: ${error.message}');
          AppLogger.error('Data: ${error.response?.data}');
          
          if (error.response?.statusCode == 401) {
            await removeToken();
          }
          return handler.next(error);
        },
      ),
    );
  }

  // Token Management
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token');
  }

  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', token);
  }

  Future<void> removeToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
  }

  // Generic HTTP Methods (Laravel API)
  Future<Response> get(
    String path, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      return await _dio.get(path, queryParameters: queryParameters);
    } catch (e) {
      rethrow;
    }
  }

  Future<Response> post(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      return await _dio.post(
        path,
        data: data,
        queryParameters: queryParameters,
      );
    } catch (e) {
      rethrow;
    }
  }

  Future<Response> put(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      return await _dio.put(path, data: data, queryParameters: queryParameters);
    } catch (e) {
      rethrow;
    }
  }

  Future<Response> delete(
    String path, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      return await _dio.delete(path, queryParameters: queryParameters);
    } catch (e) {
      rethrow;
    }
  }

  // ✅ FIX: Method untuk panggil Python API
  Future<Response> postExternal(
    String url, {
    required Map<String, dynamic> data,
  }) async {
    try {
      AppLogger.debug('=== EXTERNAL REQUEST ===');
      AppLogger.debug('URL: $url');
      AppLogger.debug('Data: $data');
      
      final response = await _externalDio.post(url, data: data);
      
      AppLogger.debug('=== EXTERNAL RESPONSE ===');
      AppLogger.debug('Status: ${response.statusCode}');
      AppLogger.debug('Data: ${response.data}');
      
      return response;
    } catch (e) {
      AppLogger.error('External API error: $e');
      rethrow;
    }
  }

  Future<Response> uploadFile(
    String path, {
    required String filePath,
    required String fieldName,
    Map<String, dynamic>? data,
  }) async {
    try {
      FormData formData = FormData.fromMap({
        ...?data,
        fieldName: await MultipartFile.fromFile(
          filePath,
          filename: filePath.split('/').last,
        ),
      });

      return await _dio.post(path, data: formData);
    } catch (e) {
      rethrow;
    }
  }

  // Error Handler
  String handleError(dynamic error) {
    if (error is DioException) {
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
          return 'Koneksi timeout. Cek koneksi internet Anda.';
        case DioExceptionType.badResponse:
          final message = error.response?.data['message'];
          return message ?? 'Terjadi kesalahan pada server (${error.response?.statusCode})';
        case DioExceptionType.cancel:
          return 'Request dibatalkan';
        case DioExceptionType.connectionError:
          return 'Tidak dapat terhubung ke server. Pastikan server berjalan.';
        default:
          return 'Tidak dapat terhubung ke server';
      }
    }
    return 'Terjadi kesalahan: ${error.toString()}';
  }
}