import 'package:flutter/material.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../theme/app_colors.dart';
import '../theme/app_text.dart';

/// Sonuçlar ekranındaki toast bileşeni: altta yuvarlak kart, amber onay ikonu, başlık + alt metin.
abstract final class Toast {
  static OverlayEntry? _current;

  static void show(BuildContext context, String title, {String? subtitle, bool error = false}) {
    final overlay = Overlay.maybeOf(context, rootOverlay: true);
    if (overlay == null) return;

    _current?.remove();
    late final OverlayEntry entry;
    entry = OverlayEntry(
      builder: (_) => _ToastView(
        title: title,
        subtitle: subtitle,
        error: error,
        onDone: () {
          if (_current == entry) _current = null;
          entry.remove();
        },
      ),
    );
    _current = entry;
    overlay.insert(entry);
  }

  static void error(BuildContext context, Object e) => show(context, e.toString(), error: true);
}

class _ToastView extends StatefulWidget {
  const _ToastView({required this.title, this.subtitle, required this.error, required this.onDone});

  final String title;
  final String? subtitle;
  final bool error;
  final VoidCallback onDone;

  @override
  State<_ToastView> createState() => _ToastViewState();
}

class _ToastViewState extends State<_ToastView> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 300));

  @override
  void initState() {
    super.initState();
    _c.forward();
    Future.delayed(const Duration(milliseconds: 2600), () async {
      if (!mounted) return;
      await _c.reverse();
      widget.onDone();
    });
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.paddingOf(context).bottom + 24;
    return Positioned(
      left: 24,
      right: 24,
      bottom: bottom,
      child: SlideTransition(
        position: Tween(begin: const Offset(0, 1.2), end: Offset.zero).animate(CurvedAnimation(parent: _c, curve: Curves.easeOutCubic)),
        child: FadeTransition(
          opacity: _c,
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 384),
              child: Material(
                color: Colors.transparent,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: BoxDecoration(
                    color: AppColors.surfaceContainerHighest,
                    borderRadius: BorderRadius.circular(Radii.xxl),
                    boxShadow: const [BoxShadow(color: Color(0x99000000), blurRadius: 50, offset: Offset(0, 25))],
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 32,
                        height: 32,
                        decoration: BoxDecoration(color: widget.error ? AppColors.errorContainer : AppColors.primaryContainer, shape: BoxShape.circle),
                        child: Icon(widget.error ? Symbols.priority_high : Symbols.check, size: 18, color: widget.error ? AppColors.error : AppColors.onPrimaryContainer),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(widget.title, style: AppText.titleMd.bold, maxLines: 3, overflow: TextOverflow.ellipsis),
                            if (widget.subtitle != null) Text(widget.subtitle!, style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
