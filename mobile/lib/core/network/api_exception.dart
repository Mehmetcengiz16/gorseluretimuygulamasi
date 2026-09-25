import 'package:dio/dio.dart';

/// Sunucunun `{ message, code, errors }` hata gövdesinin istemci karşılığı.
class ApiException implements Exception {
  ApiException(this.message, {this.code = 'UNKNOWN', this.status, this.errors = const {}});

  final String message;
  final String code;
  final int? status;
  final Map<String, dynamic> errors;

  bool get isUnauthenticated => code == 'UNAUTHENTICATED' || status == 401;
  bool get isInsufficientCredits => code == 'INSUFFICIENT_CREDITS';
  bool get isProRequired => code == 'PRO_REQUIRED';

  factory ApiException.fromDio(DioException e) {
    final data = e.response?.data;
    if (data is Map && data['message'] is String) {
      return ApiException(
        data['message'] as String,
        code: (data['code'] as String?) ?? 'HTTP_${e.response?.statusCode}',
        status: e.response?.statusCode,
        errors: data['errors'] is Map ? Map<String, dynamic>.from(data['errors'] as Map) : const {},
      );
    }

    return switch (e.type) {
      DioExceptionType.connectionTimeout ||
      DioExceptionType.sendTimeout ||
      DioExceptionType.receiveTimeout =>
        ApiException('Sunucu yanıt vermedi, bağlantını kontrol et.', code: 'TIMEOUT'),
      DioExceptionType.connectionError => ApiException('Sunucuya bağlanılamadı. İnternet bağlantını kontrol et.', code: 'NETWORK'),
      _ => ApiException('Beklenmeyen bir hata oluştu (${e.response?.statusCode ?? '-'}).', status: e.response?.statusCode),
    };
  }

  static String messageOf(Object error) => error is ApiException ? error.message : 'Beklenmeyen bir hata oluştu.';

  @override
  String toString() => message;
}
