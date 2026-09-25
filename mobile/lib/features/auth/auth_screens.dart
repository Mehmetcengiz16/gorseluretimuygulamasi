import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text.dart';
import '../../core/widgets/common.dart';
import '../../core/widgets/toast.dart';
import '../../data/repository.dart';
import 'session_controller.dart';

/// Açılış ekranı — oturum kontrol edilirken gösterilir.
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(color: AppColors.primaryContainer, borderRadius: BorderRadius.circular(22), boxShadow: AppColors.amberGlow(0.4, 32, 0)),
            child: const Icon(Symbols.auto_awesome, size: 38, color: AppColors.onPrimaryContainer, fill: 1),
          ),
          const SizedBox(height: 20),
          Text('StudioAI', style: AppText.headlineXl),
          const SizedBox(height: 4),
          Text('Ürününü stüdyoya taşı', style: AppText.bodyMd.c(AppColors.onSurfaceVariant)),
          const SizedBox(height: 32),
          const Spinner(),
        ]),
      ),
    );
  }
}

/// Giriş / kayıt — tasarımı henüz gelmedi; Obsidian Amber diliyle geçici ekran.
class AuthScreen extends ConsumerStatefulWidget {
  const AuthScreen({super.key, this.register = false});

  final bool register;

  @override
  ConsumerState<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends ConsumerState<AuthScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;
  bool _obscure = true;
  Map<String, dynamic> _fieldErrors = const {};

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _fieldErrors = const {});
    if (!_form.currentState!.validate()) return;
    setState(() => _loading = true);
    final session = ref.read(sessionProvider.notifier);
    try {
      widget.register
          ? await session.register(_name.text.trim(), _email.text.trim(), _password.text)
          : await session.login(_email.text.trim(), _password.text);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _fieldErrors = e.errors);
      if (e.errors.isEmpty) Toast.error(context, e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _forgot() async {
    if (_email.text.trim().isEmpty) {
      Toast.show(context, 'Önce e-posta adresini yaz', error: true);
      return;
    }
    try {
      await ref.read(repositoryProvider).forgotPassword(_email.text.trim());
      if (mounted) Toast.show(context, 'Sıfırlama bağlantısı gönderildi', subtitle: 'E-posta kutunu kontrol et.');
    } catch (e) {
      if (mounted) Toast.error(context, e);
    }
  }

  String? _serverError(String field) {
    final v = _fieldErrors[field];
    return v is List && v.isNotEmpty ? v.first.toString() : null;
  }

  @override
  Widget build(BuildContext context) {
    final reg = widget.register;
    return Scaffold(
      body: Stack(children: [
        const Positioned(top: -120, right: -80, child: _Glow()),
        SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Form(
                  key: _form,
                  child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Container(
                        width: 56,
                        height: 56,
                        decoration: BoxDecoration(color: AppColors.primaryContainer, borderRadius: BorderRadius.circular(18), boxShadow: AppColors.amberGlow(0.4, 24, 0)),
                        child: const Icon(Symbols.auto_awesome, size: 30, color: AppColors.onPrimaryContainer, fill: 1),
                      ),
                    ),
                    const SizedBox(height: 24),
                    Text(reg ? 'Hesap Oluştur' : 'Tekrar Hoş Geldin', style: AppText.headlineXl),
                    const SizedBox(height: 6),
                    Text(
                      reg ? 'Hoş geldin bonusuyla ilk stüdyo çekimlerini hemen yap.' : 'Ürün fotoğraflarını stüdyo kalitesine taşımaya devam et.',
                      style: AppText.bodyMd.c(AppColors.onSurfaceVariant),
                    ),
                    const SizedBox(height: 28),
                    if (reg) ...[
                      _Field(controller: _name, label: 'Ad Soyad', icon: Symbols.person, error: _serverError('name'), validator: (v) => (v ?? '').trim().isEmpty ? 'Adını yaz' : null),
                      const SizedBox(height: 14),
                    ],
                    _Field(
                      controller: _email,
                      label: 'E-posta',
                      icon: Symbols.mail,
                      keyboard: TextInputType.emailAddress,
                      error: _serverError('email'),
                      validator: (v) => (v ?? '').contains('@') ? null : 'Geçerli bir e-posta yaz',
                    ),
                    const SizedBox(height: 14),
                    _Field(
                      controller: _password,
                      label: 'Şifre',
                      icon: Symbols.lock,
                      obscure: _obscure,
                      error: _serverError('password'),
                      onSubmit: (_) => _submit(),
                      suffix: IconButton(
                        onPressed: () => setState(() => _obscure = !_obscure),
                        icon: Icon(_obscure ? Symbols.visibility : Symbols.visibility_off, size: 20, color: AppColors.onSurfaceVariant),
                      ),
                      validator: (v) => (v ?? '').length < (reg ? 8 : 1) ? (reg ? 'En az 8 karakter' : 'Şifreni yaz') : null,
                    ),
                    if (!reg)
                      Align(
                        alignment: Alignment.centerRight,
                        child: TextButton(onPressed: _forgot, child: Text('Şifremi unuttum', style: AppText.labelMd.c(AppColors.primary))),
                      )
                    else
                      const SizedBox(height: 20),
                    const SizedBox(height: 8),
                    AmberButton(
                      loading: _loading,
                      onPressed: _submit,
                      child: _loading
                          ? const Spinner(color: AppColors.onPrimaryContainer)
                          : Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                              Text(reg ? 'Kayıt Ol' : 'Giriş Yap', style: AppText.titleMd.bold.c(AppColors.onPrimaryContainer)),
                              const SizedBox(width: 6),
                              const Icon(Symbols.arrow_forward, size: 20),
                            ]),
                    ),
                    const SizedBox(height: 20),
                    Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                      Text(reg ? 'Zaten hesabın var mı?' : 'Hesabın yok mu?', style: AppText.bodyMd.c(AppColors.onSurfaceVariant)),
                      TextButton(
                        onPressed: () => context.go(reg ? '/login' : '/register'),
                        child: Text(reg ? 'Giriş yap' : 'Kayıt ol', style: AppText.labelLg.c(AppColors.primary)),
                      ),
                    ]),
                  ]),
                ),
              ),
            ),
          ),
        ),
      ]),
    );
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    required this.icon,
    this.obscure = false,
    this.keyboard,
    this.validator,
    this.suffix,
    this.error,
    this.onSubmit,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final bool obscure;
  final TextInputType? keyboard;
  final String? Function(String?)? validator;
  final Widget? suffix;
  final String? error;
  final ValueChanged<String>? onSubmit;

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label, style: AppText.labelMd.c(AppColors.onSurfaceVariant)),
      const SizedBox(height: 6),
      TextFormField(
        controller: controller,
        obscureText: obscure,
        keyboardType: keyboard,
        validator: validator,
        onFieldSubmitted: onSubmit,
        style: AppText.bodyLg,
        decoration: InputDecoration(
          prefixIcon: Icon(icon, size: 20, color: AppColors.onSurfaceVariant),
          suffixIcon: suffix,
          errorText: error,
        ),
      ),
    ]);
  }
}

class _Glow extends StatelessWidget {
  const _Glow();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 320,
      height: 320,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        boxShadow: [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.14), blurRadius: 120, spreadRadius: 40)],
      ),
    );
  }
}
