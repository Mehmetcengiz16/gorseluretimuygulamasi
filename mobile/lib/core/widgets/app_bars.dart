import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../features/auth/session_controller.dart';
import '../../features/common/pro_sheet.dart';
import '../theme/app_colors.dart';
import '../theme/app_text.dart';
import 'common.dart';

/// Sekme ekranlarının üst barı: logo + "StudioAI / {alt başlık}" + PRO rozeti + avatar.
/// (ekrantasarimlari/ke_fet_ablonlar ve st_dyo_ayarlar_prompt başlığı)
class TabTopBar extends ConsumerWidget implements PreferredSizeWidget {
  const TabTopBar({super.key, required this.subtitle});

  final String subtitle;

  @override
  Size get preferredSize => const Size.fromHeight(64);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    return _BlurBar(
      child: Row(
        children: [
          const StudioLogo(),
          const SizedBox(width: Space.sm),
          Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('StudioAI', style: AppText.titleMd.bold.tight),
              Text(subtitle, style: AppText.labelSm.medium.c(AppColors.onSurfaceVariant)),
            ],
          ),
          const Spacer(),
          ProPill(onTap: () => showProSheet(context, ref)),
          const SizedBox(width: Space.sm),
          UserAvatar(url: user?.avatarUrl, onTap: () => context.go('/profile')),
        ],
      ),
    );
  }
}

/// Editör akışının üst barı: geri + logo + "Photo Editor" + avatar.
/// (ekrantasarimlari/r_n_y_kleme_segmentasyon ve sonu_lar_kar_la_t_rma başlığı)
class EditorTopBar extends ConsumerWidget implements PreferredSizeWidget {
  const EditorTopBar({super.key, this.onBack, this.title = 'Photo Editor'});

  final VoidCallback? onBack;
  final String title;

  @override
  Size get preferredSize => const Size.fromHeight(64);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    return _BlurBar(
      child: Row(
        children: [
          Transform.translate(
            offset: const Offset(-8, 0),
            child: Pressable(
              onTap: onBack ?? () => context.canPop() ? context.pop() : context.go('/discover'),
              child: const SizedBox(width: 44, height: 44, child: Icon(Symbols.arrow_back, size: 22, color: AppColors.onSurface)),
            ),
          ),
          const StudioLogo(size: 28),
          const SizedBox(width: Space.sm),
          Text(title, style: AppText.titleMd.bold),
          const Spacer(),
          UserAvatar(url: user?.avatarUrl, onTap: () => context.go('/profile')),
        ],
      ),
    );
  }
}

class ProPill extends StatelessWidget {
  const ProPill({super.key, this.onTap});

  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      child: Container(
        height: 36,
        padding: const EdgeInsets.symmetric(horizontal: Space.md),
        decoration: BoxDecoration(
          color: AppColors.surfaceContainerHigh,
          borderRadius: BorderRadius.circular(Radii.full),
          boxShadow: [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.18), blurRadius: 16)],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Symbols.workspace_premium, size: 18, color: AppColors.primaryContainer),
            const SizedBox(width: Space.xs),
            Text('PRO', style: AppText.labelSm.bold.wider.c(AppColors.primary)),
          ],
        ),
      ),
    );
  }
}

class _BlurBar extends StatelessWidget {
  const _BlurBar({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final top = MediaQuery.paddingOf(context).top;
    return ClipRect(
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 24, sigmaY: 24),
        child: Container(
          padding: EdgeInsets.only(top: top, left: Space.margin, right: Space.margin),
          height: 64 + top,
          decoration: BoxDecoration(
            color: AppColors.surface.withValues(alpha: 0.8),
            boxShadow: const [BoxShadow(color: Color(0x0A000000), blurRadius: 8, offset: Offset(0, 1))],
          ),
          child: child,
        ),
      ),
    );
  }
}

/// Alt navigasyon — `bg-surface-container-lowest/85 backdrop-blur-xl`, aktif öğe amber.
class GlassBottomNav extends StatelessWidget {
  const GlassBottomNav({super.key, required this.index, required this.onTap});

  final int index;
  final ValueChanged<int> onTap;

  static const _items = [
    (Symbols.grid_view, 'Keşfet'),
    (Symbols.auto_awesome, 'Stüdyo'),
    (Symbols.photo_library, 'Projeler'),
    (Symbols.manage_accounts, 'Profil'),
  ];

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.paddingOf(context).bottom;
    return DecoratedBox(
      decoration: const BoxDecoration(boxShadow: [BoxShadow(color: Color(0x80000000), blurRadius: 24, offset: Offset(0, -4))]),
      child: ClipRect(
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 24, sigmaY: 24),
          child: Container(
            color: AppColors.surfaceContainerLowest.withValues(alpha: 0.85),
            padding: EdgeInsets.only(bottom: bottom, left: Space.xs, right: Space.xs),
            height: 64 + bottom,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                for (var i = 0; i < _items.length; i++)
                  Pressable(
                    onTap: () => onTap(i),
                    child: SizedBox(
                      width: 72,
                      height: 52,
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(_items[i].$1, size: 24, fill: i == index ? 1 : 0, color: i == index ? AppColors.primaryContainer : AppColors.onSurfaceVariant),
                          const SizedBox(height: 2),
                          Text(
                            _items[i].$2,
                            style: AppText.labelSm.copyWith(
                              color: i == index ? AppColors.primaryContainer : AppColors.onSurfaceVariant,
                              fontWeight: i == index ? FontWeight.w700 : FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
