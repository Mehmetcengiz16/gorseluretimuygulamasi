import 'package:flutter/material.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../core/theme/app_colors.dart';
import '../../core/widgets/common.dart';

/// Önce/sonra karşılaştırma: sol taraf sonuç (after), sağ taraf soluk orijinal (before).
/// Amber ayırıcı çizgi + sürüklenebilir tutamaç. Başlangıç konumu %70.
class BeforeAfter extends StatefulWidget {
  const BeforeAfter({super.key, required this.beforeUrl, required this.afterUrl, this.initial = 0.7, this.afterFit = BoxFit.cover});

  final String? beforeUrl;
  final String afterUrl;
  final double initial;
  final BoxFit afterFit;

  @override
  State<BeforeAfter> createState() => _BeforeAfterState();
}

class _BeforeAfterState extends State<BeforeAfter> {
  late double _split = widget.initial;

  // Tasarımdaki `grayscale contrast-75 brightness-75` filtresi.
  static const _beforeFilter = ColorFilter.matrix(<double>[
    0.1600, 0.5364, 0.0541, 0, 3.75,
    0.1600, 0.5364, 0.0541, 0, 3.75,
    0.1600, 0.5364, 0.0541, 0, 3.75,
    0, 0, 0, 1, 0,
  ]);

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(builder: (context, c) {
      final w = c.maxWidth;
      void update(double dx) => setState(() => _split = (dx / w).clamp(0.0, 1.0));

      return GestureDetector(
        behavior: HitTestBehavior.opaque,
        onHorizontalDragUpdate: (d) => update(d.localPosition.dx),
        onTapDown: (d) => update(d.localPosition.dx),
        child: Stack(fit: StackFit.expand, children: [
          const ColoredBox(color: AppColors.surfaceContainerLowest),
          if (widget.beforeUrl != null) ColorFiltered(colorFilter: _beforeFilter, child: NetImage(widget.beforeUrl)),
          ClipRect(
            clipper: _LeftClipper(_split),
            child: ColoredBox(color: AppColors.surfaceContainerLowest, child: NetImage(widget.afterUrl, fit: widget.afterFit)),
          ),
          Positioned(
            left: w * _split - 1,
            top: 0,
            bottom: 0,
            child: Container(
              width: 2,
              decoration: BoxDecoration(
                color: AppColors.primaryContainer,
                boxShadow: [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.8), blurRadius: 12)],
              ),
            ),
          ),
          Positioned(
            left: w * _split - 16,
            top: 0,
            bottom: 0,
            child: Center(
              child: Container(
                width: 32,
                height: 32,
                decoration: const BoxDecoration(
                  color: AppColors.primaryContainer,
                  shape: BoxShape.circle,
                  boxShadow: [BoxShadow(color: Color(0x40000000), blurRadius: 15, offset: Offset(0, 10))],
                ),
                child: const Icon(Symbols.drag_indicator, size: 18, color: AppColors.onPrimaryContainer),
              ),
            ),
          ),
        ]),
      );
    });
  }
}

class _LeftClipper extends CustomClipper<Rect> {
  const _LeftClipper(this.fraction);

  final double fraction;

  @override
  Rect getClip(Size size) => Rect.fromLTWH(0, 0, size.width * fraction, size.height);

  @override
  bool shouldReclip(_LeftClipper old) => old.fraction != fraction;
}
