import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../core/network/api_exception.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text.dart';
import '../../core/utils/format.dart';
import '../../core/widgets/app_bars.dart';
import '../../core/widgets/common.dart';
import '../../data/models.dart';
import '../../data/repository.dart';
import '../studio/photo_flow.dart';
import '../studio/studio_draft.dart';

final projectsProvider = FutureProvider.autoDispose<List<Project>>((ref) => ref.watch(repositoryProvider).projects());

/// Projeler — tasarımı henüz gelmedi; Keşfet kart diliyle geçici ekran.
class ProjectsScreen extends ConsumerWidget {
  const ProjectsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(projectsProvider);
    final top = MediaQuery.paddingOf(context).top + 64;
    final bottom = MediaQuery.paddingOf(context).bottom + 64;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: const TabTopBar(subtitle: 'Projeler'),
      body: RefreshIndicator(
        color: AppColors.primaryContainer,
        backgroundColor: AppColors.surfaceContainerHigh,
        edgeOffset: top,
        onRefresh: () => ref.refresh(projectsProvider.future),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
          slivers: [
            SliverPadding(
              padding: EdgeInsets.fromLTRB(Space.margin, top + Space.xs, Space.margin, Space.lg),
              sliver: SliverToBoxAdapter(
                child: SectionHeader(
                  icon: Symbols.photo_library,
                  iconColor: AppColors.primary,
                  title: 'Stüdyo Projelerim',
                  titleStyle: AppText.headlineMd.bold,
                  trailing: Pressable(
                    onTap: () => startNewShoot(context, ref),
                    child: Row(children: [
                      const Icon(Symbols.add, size: 16, color: AppColors.primary),
                      Text('Yeni', style: AppText.labelMd.c(AppColors.primary)),
                    ]),
                  ),
                ),
              ),
            ),
            ...async.when(
              loading: () => [
                SliverPadding(
                  padding: const EdgeInsets.symmetric(horizontal: Space.margin),
                  sliver: SliverGrid.builder(
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: 0.66),
                    itemCount: 4,
                    itemBuilder: (_, _) => const Skeleton(),
                  ),
                ),
              ],
              error: (e, _) => [SliverToBoxAdapter(child: EmptyState(icon: Symbols.cloud_off, title: 'Projeler yüklenemedi', message: ApiException.messageOf(e)))],
              data: (projects) => projects.isEmpty
                  ? [
                      SliverToBoxAdapter(
                        child: EmptyState(
                          icon: Symbols.photo_camera,
                          title: 'Henüz projen yok',
                          message: 'İlk ürün fotoğrafını yükle, saniyeler içinde stüdyo çekimine dönüştürelim.',
                          action: SizedBox(
                            width: 200,
                            child: AmberButton(
                              height: 44,
                              onPressed: () => startNewShoot(context, ref),
                              child: Text('Başlat', style: AppText.labelLg.bold.c(AppColors.onPrimaryContainer)),
                            ),
                          ),
                        ),
                      ),
                    ]
                  : [
                      SliverPadding(
                        padding: EdgeInsets.fromLTRB(Space.margin, 0, Space.margin, bottom + 24),
                        sliver: SliverGrid.builder(
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: 0.66),
                          itemCount: projects.length,
                          itemBuilder: (_, i) => _ProjectCard(project: projects[i]),
                        ),
                      ),
                    ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ProjectCard extends ConsumerWidget {
  const _ProjectCard({required this.project});

  final Project project;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final p = project;
    final status = p.latestGenerationStatus;
    return Pressable(
      scale: 0.98,
      onTap: () {
        if (p.latestGenerationId != null) {
          context.push('/results/${p.latestGenerationId}');
        } else {
          ref.read(studioDraftProvider.notifier).startProject(p);
          context.push('/editor/${p.id}');
        }
      },
      child: Container(
        decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(Radii.xl), boxShadow: AppColors.cardShadow),
        clipBehavior: Clip.antiAlias,
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Expanded(
            child: Stack(fit: StackFit.expand, children: [
              NetImage(p.latestThumbUrl ?? p.originalUrl),
              const DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(begin: Alignment.bottomCenter, end: Alignment.topCenter, colors: [Color(0xE60E0E11), Colors.transparent], stops: [0, 0.5]),
                ),
              ),
              Positioned(
                top: 10,
                left: 10,
                child: Glass(
                  color: AppColors.surfaceContainerLowest.withValues(alpha: 0.8),
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    if (status != null && !status.isFinished) ...[const PulseDot(size: 6), const SizedBox(width: 4)],
                    Text(trUpper(status?.label ?? 'Taslak'),
                        style: AppText.labelSm.bold.c(status == GenerationStatus.failed ? AppColors.error : AppColors.primary)),
                  ]),
                ),
              ),
            ]),
          ),
          Padding(
            padding: const EdgeInsets.all(Space.sm),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(p.title ?? 'Proje #${p.id}', style: AppText.titleMd.semi, maxLines: 1, overflow: TextOverflow.ellipsis),
              const SizedBox(height: 2),
              Text(formatDate(p.createdAt), style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
            ]),
          ),
        ]),
      ),
    );
  }
}
