import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text.dart';
import '../../core/widgets/common.dart';
import '../auth/session_controller.dart';

/// PRO bilgilendirme alt sayfası. Satın alma Faz 3'te eklenecek.
Future<void> showProSheet(BuildContext context, WidgetRef ref, {String? reason}) {
  final user = ref.read(currentUserProvider);
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: AppColors.surfaceContainerLow,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    builder: (context) => SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24, 12, 24, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.surfaceBright, borderRadius: BorderRadius.circular(2)))),
            const SizedBox(height: 20),
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(color: AppColors.primaryContainer, borderRadius: BorderRadius.circular(Radii.xxl), boxShadow: AppColors.amberGlow(0.35, 16, 0)),
              child: const Icon(Symbols.workspace_premium, size: 30, color: AppColors.onPrimaryContainer, fill: 1),
            ),
            const SizedBox(height: 16),
            Text(user?.isPro == true ? 'PRO üyesin' : 'StudioAI PRO', style: AppText.headlineLg.bold),
            const SizedBox(height: 6),
            Text(
              reason ??
                  (user?.isPro == true
                      ? 'Tüm PRO stiller, şablonlar ve 8 varyantlık çekimler açık.'
                      : 'PRO stiller, özel şablonlar ve tek seferde 8 varyant üretimi PRO üyelere özel.'),
              style: AppText.bodyMd.c(AppColors.onSurfaceVariant),
            ),
            const SizedBox(height: 20),
            for (final (icon, text) in const [
              (Symbols.palette, 'Tüm PRO stüdyo stilleri ve şablonlar'),
              (Symbols.filter_frames, 'Tek çekimde 8 varyant'),
              (Symbols.hdr_on, 'Ultra 4K HDR çıktılar'),
            ])
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Row(children: [
                  Icon(icon, size: 20, color: AppColors.primaryContainer),
                  const SizedBox(width: 12),
                  Expanded(child: Text(text, style: AppText.bodyMd)),
                ]),
              ),
            const SizedBox(height: 8),
            AmberButton(
              onPressed: () => Navigator.pop(context),
              child: Text(user?.isPro == true ? 'Harika' : 'Yakında Satın Alınabilir', style: AppText.titleMd.bold.c(AppColors.onPrimaryContainer)),
            ),
          ],
        ),
      ),
    ),
  );
}
