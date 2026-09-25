import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TokenStorage {
  static const _key = 'auth_token';
  final _storage = const FlutterSecureStorage();
  String? _cache;

  Future<String?> read() async => _cache ??= await _storage.read(key: _key);

  Future<void> write(String token) async {
    _cache = token;
    await _storage.write(key: _key, value: token);
  }

  Future<void> clear() async {
    _cache = null;
    await _storage.delete(key: _key);
  }
}
