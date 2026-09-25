import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text.dart';
import '../../core/utils/format.dart';
import '../../core/widgets/app_bars.dart';
import '../../core/widgets/common.dart';
import '../../data/models.dart';
import '../../data/repository.dart';
import '../auth/session_controller.dart';
import '../common/pro_sheet.dart';

final creditHistoryProvider = FutureProvider.autoDispose<List<CreditTransaction>>((ref) => ref.watch(repositoryProvider).creditTransactions());

/// Profil — tasarımı henüz gelmedi; Obsidian Amber kart diliyle geçici ekran.
class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    final history = ref.watch(creditHistoryProvider);
    final top = MediaQuery.paddingOf(context).top + 64;
    final bottom = MediaQuery.paddingOf(context).bottom + 64;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: const TabTopBar(subtitle: 'Profil'),
      body: RefreshIndicator(
        color: AppColors.primaryContainer,
        edgeOffset: top,
        onRefresh: () async {
          await ref.read(sessionProvider.notifier).refreshUser();
          ref.invalidate(creditHistoryProvider);
        },
        child: ListView(
          padding: EdgeInsets.fromLTRB(Space.margin, top + Space.sm, Space.margin, bottom + 24),
          children: [
            Container(
              padding: const EdgeInsets.all(Space.md + 4),
              decoration: BoxDecoration(color: AppColors.surfaceContainerLow, borderRadius: BorderRadius.circular(Radii.xxl)),
              child: Row(children: [
                UserAvatar(url: user?.avatarUrl, size: 56),
                const SizedBox(width: Space.md),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(user?.name ?? '', style: AppText.headlineMd.bold),
                    Text(user?.email ?? '', style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
                  ]),
                ),
              ]),
            ),
            const SizedBox(height: Space.md),
            Row(children: [
              Expanded(
                child: _StatCard(icon: Symbols.toll, label: 'Kredi Bakiyesi', value: '${user?.creditBalance ?? 0}', highlight: true),
              ),
              const SizedBox(width: Space.md),
              Expanded(
                child: Pressable(
                  onTap: () => showProSheet(context, ref),
                  child: _StatCard(
                    icon: Symbols.workspace_premium,
                    label: 'Üyelik',
                    value: user?.isPro == true ? 'PRO' : 'Ücretsiz',
                    sub: user?.proExpiresAt != null && user!.isPro ? '${formatDate(user.proExpiresAt)} tarihine kadar' : 'PRO\'ya geç',
                  ),
                ),
              ),
            ]),
            const SizedBox(height: Space.lg),
            SectionHeader(icon: Symbols.receipt_long, title: 'Kredi Geçmişi', titleStyle: AppText.headlineMd.bold),
            const SizedBox(height: Space.sm),
            history.when(
              loading: () => const Padding(padding: EdgeInsets.all(24), child: Center(child: Spinner())),
              error: (e, _) => Text(e.toString(), style: AppText.bodySm.c(AppColors.error)),
              data: (items) => items.isEmpty
                  ? Padding(padding: const EdgeInsets.all(24), child: Text('Henüz hareket yok', style: AppText.bodyMd.c(AppColors.onSurfaceVariant)))
                  : Container(
                      decoration: BoxDecoration(color: AppColors.surfaceContainerLow, borderRadius: BorderRadius.circular(Radii.xl)),
                      child: Column(children: [
                        for (var i = 0; i < items.length && i < 20; i++) ...[
                          if (i > 0) const Divider(height: 1, color: Color(0x14FFFFFF)),
                          _TransactionTile(t: items[i]),
                        ],
                      ]),
                    ),
            ),
            const SizedBox(height: Space.xl),
            Pressable(
              onTap: () async {
                final ok = await showDialog<bool>(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: Text('Çıkış yap', style: AppText.headlineMd.bold),
                    content: Text('Hesabından çıkmak istediğine emin misin?', style: AppText.bodyMd.c(AppColors.onSurfaceVariant)),
                    actions: [
                      TextButton(onPressed: () => Navigator.pop(context, false), child: Text('Vazgeç', style: AppText.labelLg.c(AppColors.onSurfaceVariant))),
                      TextButton(onPressed: () => Navigator.pop(context, true), child: Text('Çıkış Yap', style: AppText.labelLg.c(AppColors.error))),
                    ],
                  ),
                );
                if (ok == true) await ref.read(sessionProvider.notifier).logout();
              },
              child: Container(
                height: 52,
                decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(Radii.full)),
                child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                  const Icon(Symbols.logout, size: 20, color: AppColors.error),
                  const SizedBox(width: 8),
                  Text('Çıkış Yap', style: AppText.labelLg.c(AppColors.error)),
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.icon, required this.label, required this.value, this.sub, this.highlight = false});

  final IconData icon;
  final String label;
  final String value;
  final String? sub;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 112,
      padding: const EdgeInsets.all(Space.md + 2),
      decoration: BoxDecoration(
        color: AppColors.surfaceContainerLow,
        borderRadius: BorderRadius.circular(Radii.xxl),
        boxShadow: highlight ? [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.12), blurRadius: 24)] : null,
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Icon(icon, size: 18, color: AppColors.primaryContainer),
          const SizedBox(width: 6),
          Text(label, style: AppText.labelMd.c(AppColors.onSurfaceVariant)),
        ]),
        const Spacer(),
        Text(value, style: AppText.headlineLg.bold.c(highlight ? AppColors.primaryContainer : AppColors.onSurface)),
        if (sub != null) Text(sub!, style: AppText.bodySm.c(AppColors.onSurfaceVariant), maxLines: 1, overflow: TextOverflow.ellipsis),
      ]),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  const _TransactionTile({required this.t});

  final CreditTransaction t;

  @override
  Widget build(BuildContext context) {
    final positive = t.amount >= 0;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: Space.md, vertical: Space.md),
      child: Row(children: [
        Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(color: AppColors.surfaceContainerHigh, borderRadius: BorderRadius.circular(Radii.xl)),
          child: Icon(positive ? Symbols.add : Symbols.auto_awesome, size: 18, color: positive ? AppColors.primaryContainer : AppColors.onSurfaceVariant),
        ),
        const SizedBox(width: Space.md),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(t.description ?? t.typeLabel, style: AppText.labelLg, maxLines: 1, overflow: TextOverflow.ellipsis),
            Text('${t.typeLabel} · ${formatDate(t.createdAt)}', style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
          ]),
        ),
        Text('${positive ? '+' : ''}${t.amount}', style: AppText.titleMd.bold.c(positive ? AppColors.primaryContainer : AppColors.onSurface)),
      ]),
    );
  }
}
