import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  late Dio _dio;

  void initialize() {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    // Add interceptors
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Add token to headers
          final token = await getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (error, handler) async {
          // Handle 401 errors (token expired)
          if (error.response?.statusCode == 401) {
            // Try to refresh token
            final refreshed = await refreshToken();
            if (refreshed) {
              // Retry the request
              final opts = error.requestOptions;
              final token = await getToken();
              opts.headers['Authorization'] = 'Bearer $token';
              final response = await _dio.fetch(opts);
              return handler.resolve(response);
            }
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

  Future<bool> refreshToken() async {
    try {
      final response = await _dio.post('/auth/refresh');
      if (response.statusCode == 200) {
        final token = response.data['data']['token'];
        await saveToken(token);
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  // Generic HTTP Methods
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
          return error.response?.data['message'] ??
              'Terjadi kesalahan pada server';
        case DioExceptionType.cancel:
          return 'Request dibatalkan';
        default:
          return 'Tidak dapat terhubung ke server';
      }
    }
    return 'Terjadi kesalahan: ${error.toString()}';
  }
}