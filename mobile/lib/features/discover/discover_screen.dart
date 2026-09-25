import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text.dart';
import '../../core/utils/format.dart';
import '../../core/widgets/app_bars.dart';
import '../../core/widgets/common.dart';
import '../../core/widgets/toast.dart';
import '../../data/models.dart';
import '../../data/repository.dart';
import '../auth/session_controller.dart';
import '../common/pro_sheet.dart';
import '../studio/photo_flow.dart';

/// Keşfet / Şablonlar — ekrantasarimlari/ke_fet_ablonlar
class DiscoverScreen extends ConsumerStatefulWidget {
  const DiscoverScreen({super.key});

  @override
  ConsumerState<DiscoverScreen> createState() => _DiscoverScreenState();
}

class _DiscoverScreenState extends ConsumerState<DiscoverScreen> {
  String? _category;
  String _search = '';
  Timer? _debounce;
  List<TemplateItem>? _templates;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final list = await ref.read(repositoryProvider).templates(category: _category, search: _search);
      if (mounted) {
        setState(() {
          _templates = list;
          _error = null;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  void _onSearch(String v) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      _search = v.trim();
      _load();
    });
  }

  Future<void> _toggleLike(TemplateItem t) async {
    // İyimser güncelleme
    _replace(t.copyWith(liked: !t.liked, likesCount: t.likesCount + (t.liked ? -1 : 1)));
    try {
      final (liked, count) = await ref.read(repositoryProvider).toggleLike(t.id);
      _replace(t.copyWith(liked: liked, likesCount: count));
    } catch (e) {
      _replace(t);
      if (mounted) Toast.error(context, e);
    }
  }

  void _replace(TemplateItem t) {
    setState(() => _templates = [for (final x in _templates ?? <TemplateItem>[]) x.id == t.id ? t : x]);
  }

  void _use(TemplateItem t) {
    final user = ref.read(currentUserProvider);
    if (t.isPro && user?.isPro != true) {
      showProSheet(context, ref, reason: '"${t.title}" bir PRO şablon. PRO üyelikle tüm özel setlere erişebilirsin.');
      return;
    }
    startNewShoot(context, ref, template: t);
  }

  @override
  Widget build(BuildContext context) {
    final categories = ref.watch(catalogProvider).value?.categories ?? const <CatalogItem>[];
    final top = MediaQuery.paddingOf(context).top + 64;
    final bottom = MediaQuery.paddingOf(context).bottom + 64;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: const TabTopBar(subtitle: 'Keşfet'),
      body: RefreshIndicator(
        color: AppColors.primaryContainer,
        backgroundColor: AppColors.surfaceContainerHigh,
        edgeOffset: top,
        onRefresh: _load,
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
          slivers: [
            SliverPadding(
              padding: EdgeInsets.fromLTRB(Space.margin, top + Space.xs, Space.margin, 0),
              sliver: SliverList.list(children: [
                _SearchField(onChanged: _onSearch),
                const SizedBox(height: Space.lg),
                _CategoryChips(categories: categories, selected: _category, onSelect: (slug) {
                  setState(() => _category = slug);
                  _load();
                }),
                const SizedBox(height: Space.lg),
                SectionHeader(
                  icon: Symbols.palette,
                  iconColor: AppColors.primary,
                  title: 'Popüler Stüdyo Setleri',
                  titleStyle: AppText.headlineMd.bold,
                  trailing: Pressable(
                    onTap: () {
                      setState(() => _category = null);
                      _load();
                    },
                    child: Text('Tümünü Gör', style: AppText.labelMd.medium.c(AppColors.primary)),
                  ),
                ),
                const SizedBox(height: Space.lg),
              ]),
            ),
            _grid(),
            SliverPadding(
              padding: EdgeInsets.fromLTRB(Space.margin, Space.lg, Space.margin, bottom + 24),
              sliver: SliverToBoxAdapter(child: _QuickStartBanner(onStart: () => startNewShoot(context, ref))),
            ),
          ],
        ),
      ),
    );
  }

  Widget _grid() {
    // Kart = 4:5 görsel + sabit yükseklikli gövde (başlık, alt başlık, "Kullan").
    final cardWidth = (MediaQuery.sizeOf(context).width - Space.margin * 2 - Space.gutter) / 2;
    final delegate = SliverGridDelegateWithFixedCrossAxisCount(
      crossAxisCount: 2,
      mainAxisSpacing: Space.gutter,
      crossAxisSpacing: Space.gutter,
      mainAxisExtent: cardWidth * 5 / 4 + 100,
    );
    final padding = const EdgeInsets.symmetric(horizontal: Space.margin);

    if (_error != null && _templates == null) {
      return SliverToBoxAdapter(
        child: EmptyState(
          icon: Symbols.cloud_off,
          title: 'Şablonlar yüklenemedi',
          message: _error.toString(),
          action: Pressable(onTap: _load, child: Text('Tekrar dene', style: AppText.labelLg.c(AppColors.primary))),
        ),
      );
    }
    if (_templates == null) {
      return SliverPadding(
        padding: padding,
        sliver: SliverGrid.builder(gridDelegate: delegate, itemCount: 4, itemBuilder: (_, _) => const Skeleton()),
      );
    }
    if (_templates!.isEmpty) {
      return const SliverToBoxAdapter(child: EmptyState(icon: Symbols.search_off, title: 'Sonuç bulunamadı', message: 'Farklı bir kelime ya da kategori dene.'));
    }
    return SliverPadding(
      padding: padding,
      sliver: SliverGrid.builder(
        gridDelegate: delegate,
        itemCount: _templates!.length,
        itemBuilder: (_, i) {
          final t = _templates![i];
          return TemplateCard(template: t, onLike: () => _toggleLike(t), onUse: () => _use(t));
        },
      ),
    );
  }
}

class _SearchField extends StatelessWidget {
  const _SearchField({required this.onChanged});

  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 48,
      padding: const EdgeInsets.only(left: Space.md, right: 8),
      decoration: BoxDecoration(
        color: AppColors.surfaceContainerHigh,
        borderRadius: BorderRadius.circular(Radii.full),
        boxShadow: const [BoxShadow(color: Color(0x1A000000), blurRadius: 6, offset: Offset(0, 4))],
      ),
      child: Row(children: [
        const Icon(Symbols.search, size: 20, color: AppColors.onSurfaceVariant),
        const SizedBox(width: Space.xs),
        Expanded(
          child: TextField(
            onChanged: onChanged,
            style: AppText.bodyMd,
            textInputAction: TextInputAction.search,
            decoration: InputDecoration(
              isCollapsed: true,
              filled: false,
              border: InputBorder.none,
              enabledBorder: InputBorder.none,
              focusedBorder: InputBorder.none,
              hintText: 'Stüdyo stili veya ürün ara (ör. mermer masa, neon)...',
              hintStyle: AppText.bodyMd.c(AppColors.onSurfaceVariant.withValues(alpha: 0.6)),
            ),
          ),
        ),
        Container(
          width: 32,
          height: 32,
          decoration: const BoxDecoration(color: AppColors.surfaceContainer, shape: BoxShape.circle),
          child: const Icon(Symbols.tune, size: 18, color: AppColors.onSurfaceVariant),
        ),
      ]),
    );
  }
}

class _CategoryChips extends StatelessWidget {
  const _CategoryChips({required this.categories, required this.selected, required this.onSelect});

  final List<CatalogItem> categories;
  final String? selected;
  final ValueChanged<String?> onSelect;

  // Tasarımdaki çip ikon renkleri sırasıyla.
  static const _iconColors = [AppColors.primary, AppColors.secondary, AppColors.tertiary, AppColors.primaryFixedDim, AppColors.secondaryFixed];

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 34,
      child: ListView(
        scrollDirection: Axis.horizontal,
        clipBehavior: Clip.none,
        padding: EdgeInsets.zero,
        children: [
          _chip('Tümü', 'stars', null, selected == null, AppColors.onPrimaryContainer),
          for (var i = 0; i < categories.length; i++)
            _chip(categories[i].name, categories[i].icon, categories[i].slug, selected == categories[i].slug, _iconColors[i % _iconColors.length]),
        ],
      ),
    );
  }

  Widget _chip(String label, String? icon, String? slug, bool active, Color iconColor) {
    return Padding(
      padding: const EdgeInsets.only(right: Space.xs),
      child: Pressable(
        onTap: () => onSelect(slug),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          height: 32,
          padding: const EdgeInsets.symmetric(horizontal: 14),
          decoration: BoxDecoration(
            color: active ? AppColors.primaryContainer : AppColors.surfaceContainerHigh,
            borderRadius: BorderRadius.circular(Radii.full),
            boxShadow: active ? [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.25), blurRadius: 12, offset: const Offset(0, 2))] : null,
          ),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(symbolFor(icon), size: 16, color: active ? AppColors.onPrimaryContainer : iconColor),
            const SizedBox(width: 4),
            Text(label, style: (active ? AppText.labelMd.bold : AppText.labelMd).c(active ? AppColors.onPrimaryContainer : AppColors.onSurface)),
          ]),
        ),
      ),
    );
  }
}

/// Şablon kartı (4:5 görsel, rozet, beğeni, kategori, başlık, "Kullan").
class TemplateCard extends StatelessWidget {
  const TemplateCard({super.key, required this.template, required this.onLike, required this.onUse});

  final TemplateItem template;
  final VoidCallback onLike;
  final VoidCallback onUse;

  @override
  Widget build(BuildContext context) {
    final t = template;
    return Pressable(
      scale: 0.98,
      onTap: onUse,
      child: Container(
        decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(Radii.xl), boxShadow: AppColors.cardShadow),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            AspectRatio(
              aspectRatio: 4 / 5,
              child: Stack(fit: StackFit.expand, children: [
                NetImage(t.coverUrl),
                const DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.bottomCenter,
                      end: Alignment.topCenter,
                      colors: [Color(0xE60E0E11), Colors.transparent, Colors.transparent],
                      stops: [0, 0.5, 1],
                    ),
                  ),
                ),
                if (t.badge != null) Positioned(top: 10, left: 10, child: TemplateBadge(badge: t.badge!)),
                Positioned(
                  top: 10,
                  right: 10,
                  child: Pressable(
                    onTap: onLike,
                    child: Glass(
                      color: AppColors.surfaceContainerLowest.withValues(alpha: 0.8),
                      child: SizedBox(
                        width: 28,
                        height: 28,
                        child: Icon(Symbols.favorite, size: 15, fill: t.liked ? 1 : 0, color: t.liked ? AppColors.error : AppColors.onSurfaceVariant),
                      ),
                    ),
                  ),
                ),
                Positioned(
                  left: 10,
                  right: 10,
                  bottom: 8,
                  child: Row(children: [
                    const Icon(Symbols.thumb_up, size: 13, fill: 1, color: AppColors.primary),
                    const SizedBox(width: 4),
                    Text(compactCount(t.likesCount), style: AppText.labelSm.semi),
                    const Spacer(),
                    if (t.categoryName != null)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(color: AppColors.surfaceVariant.withValues(alpha: 0.7), borderRadius: BorderRadius.circular(Radii.sm)),
                        child: Text(t.categoryName!, style: AppText.labelSm.c(AppColors.onSurfaceVariant)),
                      ),
                  ]),
                ),
              ]),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(Space.sm),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(t.title, style: AppText.titleMd.semi.copyWith(height: 1.25), maxLines: 1, overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 6),
                    Text(t.subtitle ?? '', style: AppText.bodySm.c(AppColors.onSurfaceVariant), maxLines: 1, overflow: TextOverflow.ellipsis),
                    const Spacer(),
                    Container(
                      height: 32,
                      decoration: BoxDecoration(color: AppColors.surfaceContainerHigh, borderRadius: BorderRadius.circular(Radii.lg)),
                      child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                        Text('Kullan', style: AppText.labelMd.bold),
                        const SizedBox(width: 4),
                        const Icon(Symbols.arrow_forward, size: 16, color: AppColors.onSurface),
                      ]),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class TemplateBadge extends StatelessWidget {
  const TemplateBadge({super.key, required this.badge});

  final String badge;

  @override
  Widget build(BuildContext context) {
    if (badge == 'trend' || badge == 'new') {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
        decoration: BoxDecoration(color: AppColors.secondaryContainer.withValues(alpha: 0.9), borderRadius: BorderRadius.circular(Radii.full)),
        child: Text(badge == 'trend' ? 'TREND' : 'YENİ', style: AppText.labelSm.bold.wider.c(AppColors.onSecondaryContainer)),
      );
    }
    return Glass(
      color: AppColors.surfaceContainerLowest.withValues(alpha: 0.8),
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        const Icon(Symbols.auto_awesome, size: 12, color: AppColors.primary),
        const SizedBox(width: 4),
        Text('PRO', style: AppText.labelSm.bold.wider.c(AppColors.primary)),
      ]),
    );
  }
}

/// "Tek Tıkla Yeni Ürün Çekimi" banner'ı.
class _QuickStartBanner extends StatelessWidget {
  const _QuickStartBanner({required this.onStart});

  final VoidCallback onStart;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(Space.md),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(Radii.xxl),
        gradient: const LinearGradient(colors: [AppColors.surfaceContainerHigh, AppColors.surfaceContainer, AppColors.surfaceContainerHigh]),
        boxShadow: const [BoxShadow(color: Color(0x99000000), blurRadius: 32, offset: Offset(0, 8))],
      ),
      clipBehavior: Clip.antiAlias,
      child: Stack(clipBehavior: Clip.none, children: [
        Positioned(
          top: -60,
          right: -60,
          child: Container(
            width: 128,
            height: 128,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              boxShadow: [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.2), blurRadius: 40, spreadRadius: 10)],
            ),
          ),
        ),
        Row(children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppColors.primaryContainer,
              borderRadius: BorderRadius.circular(Radii.xl),
              boxShadow: [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.35), blurRadius: 16)],
            ),
            child: const Icon(Symbols.camera, size: 26, color: AppColors.onPrimaryContainer),
          ),
          const SizedBox(width: Space.sm),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Tek Tıkla Yeni Ürün Çekimi', style: AppText.titleMd.bold, maxLines: 1, overflow: TextOverflow.ellipsis),
              Text('Fotoğrafını yükle, stüdyoya dönüştür', style: AppText.bodySm.c(AppColors.onSurfaceVariant), maxLines: 1, overflow: TextOverflow.ellipsis),
            ]),
          ),
          const SizedBox(width: Space.sm),
          Pressable(
            onTap: onStart,
            child: Container(
              height: 44,
              padding: const EdgeInsets.symmetric(horizontal: Space.md),
              decoration: BoxDecoration(
                color: AppColors.primaryContainer,
                borderRadius: BorderRadius.circular(Radii.full),
                boxShadow: AppColors.amberGlow(0.3, 16, 4),
              ),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                Text('Başlat', style: AppText.labelLg.bold.c(AppColors.onPrimaryContainer)),
                const SizedBox(width: 4),
                const Icon(Symbols.bolt, size: 18, color: AppColors.onPrimaryContainer),
              ]),
            ),
          ),
        ]),
      ]),
    );
  }
}
