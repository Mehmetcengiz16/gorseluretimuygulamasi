import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/network/api_exception.dart';
import '../../data/models.dart';
import '../../data/repository.dart';

enum SessionStatus { unknown, signedOut, signedIn }

class SessionState {
  const SessionState(this.status, [this.user]);

  final SessionStatus status;
  final AppUser? user;
}

/// Oturum ve kullanıcı (kredi bakiyesi, PRO) durumu.
class SessionController extends Notifier<SessionState> {
  /// go_router yönlendirmesini tetiklemek için.
  final refresh = ValueNotifier<int>(0);

  @override
  SessionState build() {
    ref.read(apiClientProvider).onUnauthorized = () {
      if (state.status == SessionStatus.signedIn) _signOutLocally();
    };
    Future.microtask(_restore);
    return const SessionState(SessionStatus.unknown);
  }

  StudioRepository get _repo => ref.read(repositoryProvider);

  Future<void> _restore() async {
    final token = await ref.read(tokenStorageProvider).read();
    if (token == null) {
      _set(const SessionState(SessionStatus.signedOut));
      return;
    }
    try {
      _set(SessionState(SessionStatus.signedIn, await _repo.me()));
    } on ApiException catch (e) {
      if (e.isUnauthenticated) {
        await _signOutLocally();
      } else {
        // Ağ hatası: token geçerli kabul edilir, kullanıcı bilgisi sonra yenilenir.
        _set(const SessionState(SessionStatus.signedIn));
      }
    }
  }

  Future<void> login(String email, String password) async {
    final (token, user) = await _repo.login(email, password);
    await ref.read(tokenStorageProvider).write(token);
    _set(SessionState(SessionStatus.signedIn, user));
  }

  Future<void> register(String name, String email, String password) async {
    final (token, user) = await _repo.register(name, email, password);
    await ref.read(tokenStorageProvider).write(token);
    _set(SessionState(SessionStatus.signedIn, user));
  }

  Future<void> logout() async {
    try {
      await _repo.logout();
    } catch (_) {}
    await _signOutLocally();
  }

  Future<void> refreshUser() async {
    try {
      _set(SessionState(SessionStatus.signedIn, await _repo.me()));
    } catch (_) {}
  }

  void updateCredits(int balance) {
    final user = state.user;
    if (user != null) state = SessionState(state.status, user.copyWith(creditBalance: balance));
  }

  Future<void> _signOutLocally() async {
    await ref.read(tokenStorageProvider).clear();
    _set(const SessionState(SessionStatus.signedOut));
  }

  void _set(SessionState s) {
    final changed = s.status != state.status;
    state = s;
    if (changed) refresh.value++;
  }
}

final sessionProvider = NotifierProvider<SessionController, SessionState>(SessionController.new);

final currentUserProvider = Provider<AppUser?>((ref) => ref.watch(sessionProvider).user);

final appConfigProvider = FutureProvider<AppConfig>((ref) => ref.watch(repositoryProvider).config());

final catalogProvider = FutureProvider<Catalog>((ref) {
  ref.watch(sessionProvider.select((s) => s.status == SessionStatus.signedIn));
  return ref.watch(repositoryProvider).catalog();
});
