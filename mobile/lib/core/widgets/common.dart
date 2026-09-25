import 'dart:ui';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../theme/app_colors.dart';
import '../theme/app_text.dart';

/// Tasarımdaki `active:scale-95` davranışı: basılıyken hafifçe küçülür.
class Pressable extends StatefulWidget {
  const Pressable({super.key, required this.child, this.onTap, this.scale = 0.96, this.onLongPress});

  final Widget child;
  final VoidCallback? onTap;
  final VoidCallback? onLongPress;
  final double scale;

  @override
  State<Pressable> createState() => _PressableState();
}

class _PressableState extends State<Pressable> {
  bool _down = false;

  @override
  Widget build(BuildContext context) {
    final enabled = widget.onTap != null;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTapDown: enabled ? (_) => setState(() => _down = true) : null,
      onTapUp: enabled ? (_) => setState(() => _down = false) : null,
      onTapCancel: enabled ? () => setState(() => _down = false) : null,
      onTap: widget.onTap,
      onLongPress: widget.onLongPress,
      child: AnimatedScale(
        scale: _down ? widget.scale : 1,
        duration: const Duration(milliseconds: 110),
        curve: Curves.easeOut,
        child: widget.child,
      ),
    );
  }
}

/// Ağ görseli — yüklenirken koyu zemin, hata olursa kırık görsel ikonu.
class NetImage extends StatelessWidget {
  const NetImage(this.url, {super.key, this.fit = BoxFit.cover, this.color, this.colorBlendMode});

  final String? url;
  final BoxFit fit;
  final Color? color;
  final BlendMode? colorBlendMode;

  @override
  Widget build(BuildContext context) {
    final u = url;
    if (u == null || u.isEmpty) return const ColoredBox(color: AppColors.surfaceContainerHigh);
    return CachedNetworkImage(
      imageUrl: u,
      fit: fit,
      color: color,
      colorBlendMode: colorBlendMode,
      fadeInDuration: const Duration(milliseconds: 220),
      placeholder: (_, _) => const ColoredBox(color: AppColors.surfaceContainerHigh),
      errorWidget: (_, _, _) => const ColoredBox(
        color: AppColors.surfaceContainerHigh,
        child: Center(child: Icon(Symbols.broken_image, color: AppColors.outlineVariant)),
      ),
    );
  }
}

/// StudioAI logosu (tasarımdaki amber kıvılcımlı daire).
class StudioLogo extends StatelessWidget {
  const StudioLogo({super.key, this.size = 32});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(size * 0.28)),
      alignment: Alignment.center,
      child: Container(
        width: size * 0.72,
        height: size * 0.72,
        decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: AppColors.primaryContainer, width: size * 0.06)),
        alignment: Alignment.center,
        child: Icon(Symbols.auto_awesome, size: size * 0.42, color: AppColors.primaryContainer, fill: 1),
      ),
    );
  }
}

class UserAvatar extends StatelessWidget {
  const UserAvatar({super.key, this.url, this.size = 32, this.onTap});

  final String? url;
  final double size;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      child: Container(
        width: size,
        height: size,
        decoration: const BoxDecoration(color: AppColors.primary, shape: BoxShape.circle),
        clipBehavior: Clip.antiAlias,
        alignment: Alignment.center,
        child: url != null ? NetImage(url) : Icon(Symbols.person, size: size * 0.56, color: AppColors.onPrimary),
      ),
    );
  }
}

/// Buzlu cam arka plan (backdrop-blur).
class Glass extends StatelessWidget {
  const Glass({super.key, required this.child, this.color = const Color(0xE6353438), this.blur = 12, this.borderRadius = const BorderRadius.all(Radius.circular(Radii.full)), this.padding, this.shadow});

  final Widget child;
  final Color color;
  final double blur;
  final BorderRadius borderRadius;
  final EdgeInsetsGeometry? padding;
  final List<BoxShadow>? shadow;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(borderRadius: borderRadius, boxShadow: shadow),
      child: ClipRRect(
        borderRadius: borderRadius,
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: blur, sigmaY: blur),
          child: Container(color: color, padding: padding, child: child),
        ),
      ),
    );
  }
}

/// Bölüm başlığı: amber ikon + başlık + sağda eylem.
class SectionHeader extends StatelessWidget {
  const SectionHeader({super.key, required this.icon, required this.title, this.iconColor = AppColors.primaryContainer, this.titleStyle, this.trailing});

  final IconData icon;
  final String title;
  final Color iconColor;
  final TextStyle? titleStyle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 20, color: iconColor),
        const SizedBox(width: Space.xs),
        Expanded(child: Text(title, style: titleStyle ?? AppText.titleMd.bold, overflow: TextOverflow.ellipsis)),
        ?trailing,
      ],
    );
  }
}

/// Tam genişlikte amber CTA (tasarımdaki `bg-primary-container` + amber glow).
class AmberButton extends StatelessWidget {
  const AmberButton({super.key, required this.child, this.onPressed, this.height = 52, this.radius = Radii.full, this.loading = false, this.glowOpacity = 0.32});

  final Widget child;
  final VoidCallback? onPressed;
  final double height;
  final double radius;
  final bool loading;
  final double glowOpacity;

  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null && !loading;
    return Pressable(
      scale: 0.98,
      onTap: enabled ? onPressed : null,
      child: AnimatedOpacity(
        duration: const Duration(milliseconds: 150),
        opacity: onPressed == null ? 0.5 : 1,
        child: Container(
          height: height,
          decoration: BoxDecoration(
            color: AppColors.primaryContainer,
            borderRadius: BorderRadius.circular(radius),
            boxShadow: AppColors.amberGlow(glowOpacity),
          ),
          alignment: Alignment.center,
          child: DefaultTextStyle.merge(
            style: const TextStyle(color: AppColors.onPrimaryContainer),
            child: IconTheme.merge(data: const IconThemeData(color: AppColors.onPrimaryContainer), child: child),
          ),
        ),
      ),
    );
  }
}

class Spinner extends StatelessWidget {
  const Spinner({super.key, this.size = 20, this.color = AppColors.primaryContainer, this.stroke = 2.4});

  final double size;
  final Color color;
  final double stroke;

  @override
  Widget build(BuildContext context) =>
      SizedBox(width: size, height: size, child: CircularProgressIndicator(strokeWidth: stroke, color: color));
}

/// Yanıp sönen amber nokta (`animate-pulse`).
class PulseDot extends StatefulWidget {
  const PulseDot({super.key, this.size = 8, this.glow = true});

  final double size;
  final bool glow;

  @override
  State<PulseDot> createState() => _PulseDotState();
}

class _PulseDotState extends State<PulseDot> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 1000))..repeat(reverse: true);

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: Tween(begin: 1.0, end: 0.45).animate(_c),
      child: Container(
        width: widget.size,
        height: widget.size,
        decoration: BoxDecoration(
          color: AppColors.primaryContainer,
          shape: BoxShape.circle,
          boxShadow: widget.glow ? const [BoxShadow(color: AppColors.primaryContainer, blurRadius: 8)] : null,
        ),
      ),
    );
  }
}

/// Yükleme iskeleti (shimmer).
class Skeleton extends StatefulWidget {
  const Skeleton({super.key, this.radius = Radii.xl, this.width, this.height});

  final double radius;
  final double? width;
  final double? height;

  @override
  State<Skeleton> createState() => _SkeletonState();
}

class _SkeletonState extends State<Skeleton> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 1400))..repeat();

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _c,
      builder: (_, _) => Container(
        width: widget.width,
        height: widget.height,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(widget.radius),
          gradient: LinearGradient(
            begin: Alignment(-1.5 + _c.value * 3, 0),
            end: Alignment(-0.5 + _c.value * 3, 0),
            colors: const [AppColors.surfaceContainer, AppColors.surfaceContainerHigh, AppColors.surfaceContainer],
          ),
        ),
      ),
    );
  }
}

/// Hata / boş durum kutusu.
class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.icon, required this.title, this.message, this.action});

  final IconData icon;
  final String title;
  final String? message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 48),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: const BoxDecoration(color: AppColors.surfaceContainerHigh, shape: BoxShape.circle),
            child: Icon(icon, size: 30, color: AppColors.primaryContainer),
          ),
          const SizedBox(height: 16),
          Text(title, style: AppText.titleMd.bold, textAlign: TextAlign.center),
          if (message != null) ...[
            const SizedBox(height: 6),
            Text(message!, style: AppText.bodyMd.c(AppColors.onSurfaceVariant), textAlign: TextAlign.center),
          ],
          if (action != null) ...[const SizedBox(height: 20), action!],
        ],
      ),
    );
  }
}
