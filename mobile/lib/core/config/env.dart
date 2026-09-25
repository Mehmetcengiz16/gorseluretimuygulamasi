/// Derleme zamanı yapılandırması: `flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8000/api/v1`
abstract final class Env {
  /// Android emulator'da bilgisayarın localhost'u 10.0.2.2'dir.
  static const apiBaseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.0.2.2:8000/api/v1');

  static const appVersion = '1.0.0';
}
