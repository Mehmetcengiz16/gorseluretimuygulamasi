import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config/env.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';

/// Oturum düştüğünde (401) dinleyiciler bilgilendirilir; SessionController girişe yönlendirir.
typedef UnauthorizedCallback = void Function();

class ApiClient {
  ApiClient(this._tokens) {
    _dio = Dio(BaseOptions(
      baseUrl: Env.apiBaseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 60),
      sendTimeout: const Duration(seconds: 120),
      headers: {'Accept': 'application/json'},
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _tokens.read();
        if (token != null) options.headers['Authorization'] = 'Bearer $token';
        handler.next(options);
      },
      onError: (e, handler) {
        if (e.response?.statusCode == 401 && !e.requestOptions.path.startsWith('/auth/')) {
          onUnauthorized?.call();
        }
        handler.next(e);
      },
    ));
  }

  final TokenStorage _tokens;
  late final Dio _dio;
  UnauthorizedCallback? onUnauthorized;

  Dio get dio => _dio;

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) =>
      _wrap(() => _dio.get(path, queryParameters: query));

  Future<Map<String, dynamic>> post(String path, {Object? data}) => _wrap(() => _dio.post(path, data: data));

  Future<Map<String, dynamic>> patch(String path, {Object? data}) => _wrap(() => _dio.patch(path, data: data));

  Future<Map<String, dynamic>> delete(String path) => _wrap(() => _dio.delete(path));

  Future<Map<String, dynamic>> upload(String path, String filePath, {String field = 'image', Map<String, dynamic>? fields, void Function(double)? onProgress}) {
    return _wrap(() async {
      final form = FormData.fromMap({
        ...?fields,
        field: await MultipartFile.fromFile(filePath, filename: filePath.split(RegExp(r'[\\/]')).last),
      });
      return _dio.post(path, data: form, onSendProgress: (sent, total) {
        if (total > 0) onProgress?.call(sent / total);
      });
    });
  }

  /// Tam çözünürlüklü görseli bayt olarak indirir (galeriye kaydetme / paylaşma).
  Future<List<int>> downloadBytes(String url) async {
    try {
      final res = await _dio.get<List<int>>(url, options: Options(responseType: ResponseType.bytes, receiveTimeout: const Duration(minutes: 2)));
      return res.data ?? const [];
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<Map<String, dynamic>> _wrap(Future<Response<dynamic>> Function() call) async {
    try {
      final res = await call();
      final data = res.data;
      if (data is Map<String, dynamic>) return data;
      return {'data': data};
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage());

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient(ref.watch(tokenStorageProvider)));
