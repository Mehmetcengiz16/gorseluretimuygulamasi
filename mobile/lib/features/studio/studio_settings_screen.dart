import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../core/network/api_exception.dart';
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
import 'photo_flow.dart';
import 'studio_draft.dart';

/// Stüdyo Ayarları & Prompt ("Görsel Oluşturucu") — ekrantasarimlari/st_dyo_ayarlar_prompt
class StudioSettingsScreen extends ConsumerStatefulWidget {
  const StudioSettingsScreen({super.key});

  @override
  ConsumerState<StudioSettingsScreen> createState() => _StudioSettingsScreenState();
}

class _StudioSettingsScreenState extends ConsumerState<StudioSettingsScreen> {
  static const _maxPrompt = 500;
  final _prompt = TextEditingController();
  final _promptFocus = FocusNode();
  bool _enhancing = false;
  bool _generating = false;

  @override
  void initState() {
    super.initState();
    _prompt.text = ref.read(studioDraftProvider).prompt;
    _prompt.addListener(() {
      ref.read(studioDraftProvider.notifier).setPrompt(_prompt.text);
      setState(() {});
    });
  }

  @override
  void dispose() {
    _prompt.dispose();
    _promptFocus.dispose();
    super.dispose();
  }

  StudioDraftController get _draft => ref.read(studioDraftProvider.notifier);

  Future<void> _enhance() async {
    setState(() => _enhancing = true);
    final d = ref.read(studioDraftProvider);
    try {
      final text = await ref.read(repositoryProvider).enhancePrompt(_prompt.text, styleId: d.studioStyleId, lightingId: d.lightingPresetId);
      _prompt.text = text.length > _maxPrompt ? text.substring(0, _maxPrompt) : text;
    } catch (e) {
      if (mounted) Toast.error(context, e);
    } finally {
      if (mounted) setState(() => _enhancing = false);
    }
  }

  Future<void> _generate(Catalog catalog) async {
    final d = ref.read(studioDraftProvider);
    if (d.projectId == null) {
      await startNewShoot(context, ref);
      return;
    }
    final quality = _quality(catalog, d.qualityKey);
    if (quality == null) return;

    setState(() => _generating = true);
    try {
      final (generation, balance) = await ref.read(repositoryProvider).startGeneration(d.projectId!, {
        'user_prompt': _prompt.text.trim().isEmpty ? null : _prompt.text.trim(),
        'studio_style_id': d.studioStyleId,
        'lighting_preset_id': d.lightingPresetId,
        'quality_level_id': quality.id,
        'scene_type_id': d.sceneTypeId,
        'shadow_enabled': d.shadowEnabled,
        'aspect_ratio': '4:5',
        'variant_count': d.variantCount,
        'template_id': d.templateId,
      });
      if (balance != null) ref.read(sessionProvider.notifier).updateCredits(balance);
      if (mounted) context.push('/results/${generation.id}');
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.isProRequired) {
        showProSheet(context, ref, reason: e.message);
      } else {
        Toast.show(context, e.message, subtitle: e.isInsufficientCredits ? 'Bakiyeni Profil sekmesinden görebilirsin.' : null, error: true);
      }
    } finally {
      if (mounted) setState(() => _generating = false);
    }
  }

  bool _defaultsApplied = false;

  /// Tasarımdaki varsayılan seçim: "Lüks Ticari" stili + "Spot Işık" (yoksa ilk seçenekler).
  void _applyDefaults(Catalog c, StudioDraft d) {
    if (_defaultsApplied) return;
    _defaultsApplied = true;
    if (d.lightingPresetId != null || d.templateId != null) return;
    final light = c.lightingPresets.where((l) => l.slug == 'spot').firstOrNull ?? c.lightingPresets.firstOrNull;
    final style = c.studioStyles.where((s) => s.slug == 'luks-ticari').firstOrNull;
    Future.microtask(() {
      if (light != null) _draft.setLighting(light.id);
      if (style != null && d.studioStyleId == null) _draft.setStyle(style.id);
    });
  }

  QualityLevel? _quality(Catalog c, String key) =>
      c.qualityLevels.where((q) => q.key == key).firstOrNull ?? (c.qualityLevels.isNotEmpty ? c.qualityLevels.last : null);

  Future<void> _pickPreset() async {
    List<TemplateItem> templates;
    try {
      templates = await ref.read(repositoryProvider).templates();
    } catch (e) {
      if (mounted) Toast.error(context, e);
      return;
    }
    if (!mounted) return;
    final chosen = await showModalBottomSheet<TemplateItem>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surfaceContainerLow,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (context) => _PresetSheet(templates: templates),
    );
    if (chosen == null) return;
    if (chosen.isPro && ref.read(currentUserProvider)?.isPro != true) {
      if (mounted) showProSheet(context, ref);
      return;
    }
    _draft.applyTemplate(chosen);
    _prompt.text = chosen.defaultPrompt ?? _prompt.text;
    if (mounted) Toast.show(context, '"${chosen.title}" seti uygulandı');
  }

  void _selectVariants(int v, AppConfig? config) {
    final user = ref.read(currentUserProvider);
    if (v > (config?.maxVariantsFree ?? 4) && user?.isPro != true) {
      showProSheet(context, ref, reason: '$v varyantlık çekimler PRO üyelere özel.');
      return;
    }
    _draft.setVariants(v);
  }

  void _selectPro(CatalogItem item, VoidCallback select) {
    if (item.isPro && ref.read(currentUserProvider)?.isPro != true) {
      showProSheet(context, ref, reason: '"${item.name}" bir PRO stil.');
      return;
    }
    select();
  }

  @override
  Widget build(BuildContext context) {
    final draft = ref.watch(studioDraftProvider);
    // Şablon / yeni proje prompt'u dışarıdan değiştirdiğinde metin alanını eşitle.
    ref.listen(studioDraftProvider.select((d) => d.prompt), (_, next) {
      if (next != _prompt.text) _prompt.text = next;
    });
    final catalogAsync = ref.watch(catalogProvider);
    final config = ref.watch(appConfigProvider).value;
    final top = MediaQuery.paddingOf(context).top + 64;
    final bottom = MediaQuery.paddingOf(context).bottom + 64;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: const TabTopBar(subtitle: 'Stüdyo'),
      body: catalogAsync.when(
        loading: () => const Center(child: Spinner(size: 28)),
        error: (e, _) => EmptyState(
          icon: Symbols.cloud_off,
          title: 'Stüdyo ayarları yüklenemedi',
          message: ApiException.messageOf(e),
          action: Pressable(onTap: () => ref.invalidate(catalogProvider), child: Text('Tekrar dene', style: AppText.labelLg.c(AppColors.primary))),
        ),
        data: (catalog) {
          _applyDefaults(catalog, draft);
          final quality = _quality(catalog, draft.qualityKey);
          final credits = quality?.creditsFor(draft.variantCount) ?? draft.variantCount;
          return GestureDetector(
            onTap: () => FocusScope.of(context).unfocus(),
            child: ListView(
              padding: EdgeInsets.fromLTRB(Space.margin, top + Space.xs, Space.margin, bottom + Space.xl),
              children: [
                _subHeader(),
                const SizedBox(height: Space.lg),
                if (draft.projectId == null) ...[
                  _NoProductCard(onTap: () => startNewShoot(context, ref)),
                  const SizedBox(height: Space.lg),
                ],
                _promptCard(),
                const SizedBox(height: Space.lg),
                _styleSection(catalog.studioStyles, draft),
                const SizedBox(height: Space.lg),
                _lightingCard(catalog.lightingPresets, draft),
                const SizedBox(height: Space.lg),
                _qualityCard(catalog.qualityLevels, draft),
                const SizedBox(height: Space.lg),
                _shotsCard(config, draft, credits),
                const SizedBox(height: Space.lg + Space.xs),
                _generateButton(catalog),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _subHeader() {
    return Row(children: [
      Pressable(
        onTap: () => context.canPop() ? context.pop() : context.go('/discover'),
        child: Container(
          width: 40,
          height: 40,
          decoration: const BoxDecoration(color: AppColors.surfaceContainerHigh, shape: BoxShape.circle),
          child: const Icon(Symbols.arrow_back, size: 20, color: AppColors.onSurface),
        ),
      ),
      const SizedBox(width: Space.sm),
      Expanded(
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Görsel Oluşturucu', style: AppText.headlineMd.bold.tight),
          Text('Stüdyo Parametreleri & Ayarlar', style: AppText.labelSm.medium.c(AppColors.onSurfaceVariant)),
        ]),
      ),
      Pressable(
        onTap: _pickPreset,
        child: Container(
          height: 32,
          padding: const EdgeInsets.symmetric(horizontal: Space.sm),
          decoration: BoxDecoration(color: AppColors.surfaceContainerHigh, borderRadius: BorderRadius.circular(Radii.full)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Symbols.tune, size: 16, color: AppColors.primaryContainer),
            const SizedBox(width: 6),
            Text('Hazır Set', style: AppText.labelSm.c(AppColors.primary)),
          ]),
        ),
      ),
    ]);
  }

  Widget _promptCard() {
    return _Card(
      color: AppColors.surfaceContainerLow,
      shadow: const [BoxShadow(color: Color(0x1A000000), blurRadius: 6, offset: Offset(0, 4))],
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Row(children: [
          const Icon(Symbols.edit_note, size: 18, color: AppColors.primary),
          const SizedBox(width: 6),
          Text('Akıllı Prompt', style: AppText.labelMd.semi.c(AppColors.primary)),
          const Spacer(),
          Pressable(
            onTap: () {
              _prompt.clear();
              _promptFocus.requestFocus();
            },
            child: Text('Temizle', style: AppText.labelSm.c(AppColors.onSurfaceVariant)),
          ),
        ]),
        const SizedBox(height: Space.sm),
        Container(
          padding: const EdgeInsets.all(Space.sm),
          decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(Radii.lg)),
          child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            TextField(
              controller: _prompt,
              focusNode: _promptFocus,
              minLines: 4,
              maxLines: 6,
              style: AppText.bodyMd,
              inputFormatters: [LengthLimitingTextInputFormatter(_maxPrompt)],
              decoration: InputDecoration(
                isCollapsed: true,
                filled: false,
                border: InputBorder.none,
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                hintText: 'Ürün çekimi hayalinizi detaylandırın...',
                hintStyle: AppText.bodyMd.c(AppColors.outlineVariant),
              ),
            ),
            const SizedBox(height: Space.sm),
            Row(children: [
              Text('${_prompt.text.characters.length}/$_maxPrompt', style: AppText.labelSm.medium.c(AppColors.onSurfaceVariant)),
              const Spacer(),
              Pressable(
                onTap: _enhancing ? null : _enhance,
                child: Container(
                  height: 32,
                  padding: const EdgeInsets.symmetric(horizontal: Space.sm),
                  decoration: BoxDecoration(
                    color: AppColors.surfaceContainerHighest,
                    borderRadius: BorderRadius.circular(Radii.full),
                    boxShadow: const [BoxShadow(color: Color(0x0D000000), blurRadius: 2, offset: Offset(0, 1))],
                  ),
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    _enhancing ? const Spinner(size: 14, stroke: 2) : const Icon(Symbols.auto_fix_high, size: 16, color: AppColors.primaryContainer),
                    const SizedBox(width: 6),
                    Text("Prompt'u Geliştir", style: AppText.labelSm.semi.c(AppColors.primary)),
                  ]),
                ),
              ),
            ]),
          ]),
        ),
      ]),
    );
  }

  Widget _styleSection(List<CatalogItem> styles, StudioDraft draft) {
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      SectionHeader(
        icon: Symbols.palette,
        title: 'Stüdyo Stili',
        trailing: Pressable(
          onTap: () => _showAllStyles(styles),
          child: Row(children: [
            Text('Tümünü Gör', style: AppText.labelSm.c(AppColors.primary)),
            const Icon(Symbols.chevron_right, size: 14, color: AppColors.primary),
          ]),
        ),
      ),
      const SizedBox(height: Space.sm),
      SizedBox(
        height: 100,
        child: ListView(
          scrollDirection: Axis.horizontal,
          clipBehavior: Clip.none,
          children: [
            for (final s in styles)
              Padding(
                padding: const EdgeInsets.only(right: Space.sm),
                child: _StyleTile(
                  style: s,
                  selected: s.thumbnailUrl == null ? (draft.studioStyleId == null || draft.studioStyleId == s.id) : draft.studioStyleId == s.id,
                  onTap: () => _selectPro(s, () => _draft.setStyle(s.thumbnailUrl == null ? null : s.id)),
                ),
              ),
            _AddStyleTile(onTap: () {
              _draft.setStyle(null);
              _promptFocus.requestFocus();
              Toast.show(context, 'Özel stilini prompt alanına yaz', subtitle: 'Stil "Yok / Ham" olarak ayarlandı.');
            }),
          ],
        ),
      ),
    ]);
  }

  void _showAllStyles(List<CatalogItem> styles) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: AppColors.surfaceContainerLow,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (context) => Consumer(builder: (context, ref, _) {
        final draft = ref.watch(studioDraftProvider);
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 16),
            child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Stüdyo Stilleri', style: AppText.headlineMd.bold),
              const SizedBox(height: 16),
              Wrap(spacing: 8, runSpacing: 12, children: [
                for (final s in styles)
                  _StyleTile(
                    style: s,
                    selected: s.thumbnailUrl == null ? draft.studioStyleId == null : draft.studioStyleId == s.id,
                    onTap: () {
                      _selectPro(s, () => _draft.setStyle(s.thumbnailUrl == null ? null : s.id));
                      Navigator.pop(context);
                    },
                  ),
              ]),
            ]),
          ),
        );
      }),
    );
  }

  Widget _lightingCard(List<CatalogItem> lights, StudioDraft draft) {
    return _Card(
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        const SectionHeader(icon: Symbols.light_mode, title: 'Stüdyo Işıklandırması'),
        const SizedBox(height: Space.sm + Space.xs),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: Space.xs,
          crossAxisSpacing: Space.xs,
          childAspectRatio: 2.75,
          padding: EdgeInsets.zero,
          children: [
            for (final l in lights)
              _LightPill(light: l, selected: draft.lightingPresetId == l.id, onTap: () => _selectPro(l, () => _draft.setLighting(l.id))),
          ],
        ),
      ]),
    );
  }

  Widget _qualityCard(List<QualityLevel> levels, StudioDraft draft) {
    if (levels.isEmpty) return const SizedBox.shrink();
    final index = levels.indexWhere((q) => q.key == draft.qualityKey).clamp(0, levels.length - 1);
    return _Card(
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        SectionHeader(
          icon: Symbols.high_quality,
          title: 'Görsel Detay & Kalite',
          trailing: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(Radii.full)),
            child: Text(levels[index].name, style: AppText.labelSm.bold.c(AppColors.primary)),
          ),
        ),
        const SizedBox(height: Space.sm + 4),
        _QualitySlider(
          count: levels.length,
          index: index,
          onChanged: (i) => _selectPro(
            CatalogItem(id: levels[i].id, name: levels[i].name, isPro: levels[i].isPro),
            () => _draft.setQuality(levels[i].key),
          ),
        ),
        const SizedBox(height: 6),
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          for (var i = 0; i < levels.length; i++)
            Text(levels[i].name, style: i == index ? AppText.labelSm.semi.c(AppColors.primary) : AppText.labelSm.c(AppColors.onSurfaceVariant)),
        ]),
      ]),
    );
  }

  Widget _shotsCard(AppConfig? config, StudioDraft draft, int credits) {
    final options = config?.variantOptions ?? const [1, 2, 4, 8];
    return _Card(
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        SectionHeader(
          icon: Symbols.filter_frames,
          title: 'Çekim Sayısı (Varyant)',
          trailing: Text('$credits Kredi Harcanacak', style: AppText.labelSm.c(AppColors.onSurfaceVariant)),
        ),
        const SizedBox(height: Space.sm + Space.xs),
        Row(children: [
          for (var i = 0; i < options.length; i++) ...[
            if (i > 0) const SizedBox(width: Space.xs),
            Expanded(
              child: Pressable(
                onTap: () => _selectVariants(options[i], config),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 160),
                  height: 44,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: draft.variantCount == options[i] ? AppColors.primaryContainer : AppColors.surfaceContainerHigh,
                    borderRadius: BorderRadius.circular(Radii.lg),
                    boxShadow: draft.variantCount == options[i] ? [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.35), blurRadius: 14)] : null,
                  ),
                  child: Text('${options[i]}',
                      style: AppText.labelLg.bold.c(draft.variantCount == options[i] ? AppColors.onPrimaryContainer : AppColors.onSurface)),
                ),
              ),
            ),
          ],
        ]),
      ]),
    );
  }

  Widget _generateButton(Catalog catalog) {
    final user = ref.watch(currentUserProvider);
    return AmberButton(
      height: 56,
      loading: _generating,
      onPressed: () => _generate(catalog),
      child: _generating
          ? Row(mainAxisAlignment: MainAxisAlignment.center, children: [
              const Spinner(size: 22, color: AppColors.onPrimaryContainer),
              const SizedBox(width: Space.sm),
              Text('Render Hazırlanıyor...', style: AppText.titleMd.bold.c(AppColors.onPrimaryContainer)),
            ])
          : Row(mainAxisAlignment: MainAxisAlignment.center, children: [
              const Icon(Symbols.auto_awesome, size: 24),
              const SizedBox(width: Space.sm),
              Text('Stüdyo Çekimi Oluştur', style: AppText.titleMd.bold.c(AppColors.onPrimaryContainer)),
              if (user?.isPro ?? true) ...[
                const SizedBox(width: 4),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(color: AppColors.onPrimaryContainer.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(Radii.full)),
                  child: Text('PRO', style: AppText.labelSm.bold.c(AppColors.onPrimaryContainer)),
                ),
              ],
            ]),
    );
  }
}

class _Card extends StatelessWidget {
  const _Card({required this.child, this.color = AppColors.surfaceContainerLow, this.shadow});

  final Widget child;
  final Color color;
  final List<BoxShadow>? shadow;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(Space.md),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(Radii.xl),
        boxShadow: shadow ?? const [BoxShadow(color: Color(0x0D000000), blurRadius: 2, offset: Offset(0, 1))],
      ),
      child: child,
    );
  }
}

class _StyleTile extends StatelessWidget {
  const _StyleTile({required this.style, required this.selected, required this.onTap});

  final CatalogItem style;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isRaw = style.thumbnailUrl == null;
    return Pressable(
      onTap: onTap,
      child: SizedBox(
        width: 76,
        child: Column(children: [
          AnimatedContainer(
            duration: const Duration(milliseconds: 180),
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              color: isRaw ? AppColors.surfaceContainerHigh : AppColors.surfaceContainer,
              borderRadius: BorderRadius.circular(Radii.xl),
              boxShadow: selected && !isRaw ? [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.35), blurRadius: 16)] : null,
              border: selected && isRaw ? Border.all(color: AppColors.primaryContainer.withValues(alpha: 0.6)) : null,
            ),
            clipBehavior: Clip.antiAlias,
            child: isRaw
                ? const Icon(Symbols.block, size: 28, color: AppColors.onSurfaceVariant)
                : Stack(fit: StackFit.expand, children: [
                    NetImage(style.thumbnailUrl),
                    if (selected) ColoredBox(color: AppColors.primaryContainer.withValues(alpha: 0.12)),
                    if (style.isPro)
                      const Positioned(top: 4, left: 4, child: Icon(Symbols.workspace_premium, size: 14, color: AppColors.primaryContainer, fill: 1)),
                    if (selected)
                      Positioned(
                        bottom: 4,
                        right: 4,
                        child: Container(
                          width: 16,
                          height: 16,
                          decoration: const BoxDecoration(color: AppColors.primaryContainer, shape: BoxShape.circle),
                          child: const Icon(Symbols.check, size: 12, weight: 700, color: AppColors.onPrimaryContainer),
                        ),
                      ),
                  ]),
          ),
          const SizedBox(height: 6),
          Text(
            style.name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
            style: selected ? AppText.labelSm.bold.c(AppColors.primary) : AppText.labelSm.copyWith(fontWeight: FontWeight.w600, color: AppColors.onSurfaceVariant),
          ),
        ]),
      ),
    );
  }
}

class _AddStyleTile extends StatelessWidget {
  const _AddStyleTile({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      child: SizedBox(
        width: 76,
        child: Column(children: [
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(Radii.xl)),
            child: const Icon(Symbols.add_circle, size: 26, color: AppColors.primaryContainer),
          ),
          const SizedBox(height: 6),
          Text('+ Özel Stil', style: AppText.labelSm.copyWith(fontWeight: FontWeight.w600, color: AppColors.onSurfaceVariant)),
        ]),
      ),
    );
  }
}

class _LightPill extends StatelessWidget {
  const _LightPill({required this.light, required this.selected, required this.onTap});

  final CatalogItem light;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final fg = selected ? AppColors.onPrimary : AppColors.onSurface;
    return Pressable(
      scale: 0.98,
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: selected ? AppColors.primary : AppColors.surfaceContainerHigh,
          borderRadius: BorderRadius.circular(Radii.lg),
          boxShadow: selected ? [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.22), blurRadius: 12)] : null,
        ),
        child: Row(children: [
          Icon(symbolFor(light.icon), size: 18, color: selected ? AppColors.onPrimary : AppColors.primaryContainer),
          const SizedBox(width: Space.sm),
          Expanded(
            child: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(light.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: (selected ? AppText.labelMd.bold : AppText.labelMd.semi).c(fg)),
              Text(light.subtitle ?? '',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: AppText.labelSm.copyWith(fontWeight: FontWeight.w600, color: selected ? AppColors.onPrimary.withValues(alpha: 0.85) : AppColors.onSurfaceVariant)),
            ]),
          ),
        ]),
      ),
    );
  }
}

/// Özel kaydırıcı: koyu iz, amber dolgu, amber haleli topuz. Duraklar: %15 / %50 / %85.
class _QualitySlider extends StatelessWidget {
  const _QualitySlider({required this.count, required this.index, required this.onChanged});

  final int count;
  final int index;
  final ValueChanged<int> onChanged;

  double _pos(int i) => count <= 1 ? 0.85 : 0.15 + (0.70 * i / (count - 1));

  int _nearest(double f) {
    var best = 0;
    for (var i = 1; i < count; i++) {
      if ((_pos(i) - f).abs() < (_pos(best) - f).abs()) best = i;
    }
    return best;
  }

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(builder: (context, c) {
      final w = c.maxWidth;
      final pos = _pos(index);
      void handle(double dx) {
        final i = _nearest((dx / w).clamp(0, 1));
        if (i != index) {
          HapticFeedback.selectionClick();
          onChanged(i);
        }
      }

      return GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTapDown: (d) => handle(d.localPosition.dx),
        onHorizontalDragUpdate: (d) => handle(d.localPosition.dx),
        child: SizedBox(
          height: 24,
          child: Stack(alignment: Alignment.centerLeft, clipBehavior: Clip.none, children: [
            Container(height: 8, decoration: BoxDecoration(color: AppColors.surfaceContainerHighest, borderRadius: BorderRadius.circular(Radii.full))),
            AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              curve: Curves.easeOut,
              height: 8,
              width: w * pos,
              decoration: BoxDecoration(color: AppColors.primaryContainer, borderRadius: BorderRadius.circular(Radii.full)),
            ),
            AnimatedPositioned(
              duration: const Duration(milliseconds: 200),
              curve: Curves.easeOut,
              left: w * pos - 10,
              child: Container(
                width: 20,
                height: 20,
                decoration: BoxDecoration(
                  color: AppColors.primaryContainer,
                  shape: BoxShape.circle,
                  boxShadow: [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.8), blurRadius: 12)],
                ),
                alignment: Alignment.center,
                child: Container(width: 8, height: 8, decoration: const BoxDecoration(color: AppColors.onPrimaryContainer, shape: BoxShape.circle)),
              ),
            ),
          ]),
        ),
      );
    });
  }
}

/// Proje yokken gösterilen fotoğraf yükleme kartı (Keşfet banner'ı ile aynı dil).
class _NoProductCard extends StatelessWidget {
  const _NoProductCard({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      scale: 0.99,
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(Space.md),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(Radii.xxl),
          gradient: const LinearGradient(colors: [AppColors.surfaceContainerHigh, AppColors.surfaceContainer, AppColors.surfaceContainerHigh]),
          border: Border.all(color: AppColors.primaryContainer.withValues(alpha: 0.25)),
        ),
        child: Row(children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(color: AppColors.primaryContainer, borderRadius: BorderRadius.circular(Radii.xl), boxShadow: AppColors.amberGlow(0.35, 16, 0)),
            child: const Icon(Symbols.add_a_photo, size: 24, color: AppColors.onPrimaryContainer),
          ),
          const SizedBox(width: Space.sm),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Önce ürün fotoğrafını yükle', style: AppText.titleMd.bold),
              Text('Dekupe edip stüdyo sahnesine taşıyalım', style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
            ]),
          ),
          const Icon(Symbols.chevron_right, color: AppColors.primary),
        ]),
      ),
    );
  }
}

class _PresetSheet extends StatelessWidget {
  const _PresetSheet({required this.templates});

  final List<TemplateItem> templates;

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: 0.7,
      maxChildSize: 0.92,
      builder: (context, controller) => Column(children: [
        const SizedBox(height: 12),
        Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.surfaceBright, borderRadius: BorderRadius.circular(2))),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Row(children: [
            const Icon(Symbols.tune, color: AppColors.primaryContainer, size: 20),
            const SizedBox(width: 8),
            Text('Hazır Setler', style: AppText.headlineMd.bold),
          ]),
        ),
        Expanded(
          child: ListView.separated(
            controller: controller,
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            itemCount: templates.length,
            separatorBuilder: (_, _) => const SizedBox(height: 8),
            itemBuilder: (_, i) {
              final t = templates[i];
              return Pressable(
                onTap: () => Navigator.pop(context, t),
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(Radii.xl)),
                  child: Row(children: [
                    ClipRRect(borderRadius: BorderRadius.circular(Radii.lg), child: SizedBox(width: 56, height: 70, child: NetImage(t.coverUrl))),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(t.title, style: AppText.titleMd.semi, maxLines: 1, overflow: TextOverflow.ellipsis),
                        Text(t.subtitle ?? '', style: AppText.bodySm.c(AppColors.onSurfaceVariant), maxLines: 1, overflow: TextOverflow.ellipsis),
                      ]),
                    ),
                    if (t.isPro) const Icon(Symbols.workspace_premium, size: 18, color: AppColors.primaryContainer, fill: 1),
                    const SizedBox(width: 4),
                    const Icon(Symbols.chevron_right, color: AppColors.onSurfaceVariant),
                  ]),
                ),
              );
            },
          ),
        ),
      ]),
    );
  }
}
