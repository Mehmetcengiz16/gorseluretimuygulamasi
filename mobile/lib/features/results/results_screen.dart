import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gal/gal.dart';
import 'package:go_router/go_router.dart';
import 'package:material_symbols_icons/symbols.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

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
import '../studio/studio_draft.dart';
import 'before_after.dart';
import 'edit_tools.dart';

/// Sonuçlar & Karşılaştırma — ekrantasarimlari/sonu_lar_kar_la_t_rma
class ResultsScreen extends ConsumerStatefulWidget {
  const ResultsScreen({super.key, required this.generationId});

  final int generationId;

  @override
  ConsumerState<ResultsScreen> createState() => _ResultsScreenState();
}

class _ResultsScreenState extends ConsumerState<ResultsScreen> {
  Generation? _gen;
  Object? _error;
  Timer? _poll;
  int _selected = 0;
  bool _downloading = false;
  bool _generatingMore = false;
  bool _editing = false;
  String? _activeTool;
  final _cardBump = ValueNotifier<bool>(false);

  StudioRepository get _repo => ref.read(repositoryProvider);

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _poll?.cancel();
    _cardBump.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final g = await _repo.generation(widget.generationId);
      if (!mounted) return;
      final wasFinished = _gen?.status.isFinished ?? true;
      final firstLoad = _gen == null;
      setState(() {
        _gen = g;
        _error = null;
        if (_selected >= g.images.length) _selected = 0;
        // İlk yüklemede master varyantı seç; sonraki yenilemelerde kullanıcının seçimine dokunma.
        if (firstLoad && g.images.any((i) => i.isMaster) && !_isSelectedReady(g)) {
          _selected = g.images.indexWhere((i) => i.isMaster);
        }
      });
      if (!g.status.isFinished) {
        _poll?.cancel();
        _poll = Timer(const Duration(milliseconds: 2500), _load);
      } else if (!wasFinished) {
        ref.read(sessionProvider.notifier).refreshUser(); // iade olduysa bakiye güncellensin
        if (g.status == GenerationStatus.failed && mounted) {
          Toast.show(context, 'Üretim başarısız oldu', subtitle: 'Harcanan kredi iade edildi.', error: true);
        }
      }
    } catch (e) {
      if (mounted) setState(() => _error = e);
      _poll = Timer(const Duration(seconds: 5), _load);
    }
  }

  bool _isSelectedReady(Generation g) => _selected < g.images.length && g.images[_selected].isReady;

  GenerationImage? get _current {
    final g = _gen;
    if (g == null || g.images.isEmpty) return null;
    return g.images[_selected.clamp(0, g.images.length - 1)];
  }

  void _select(int i) {
    HapticFeedback.selectionClick();
    setState(() => _selected = i);
    _cardBump.value = true;
    Future.delayed(const Duration(milliseconds: 150), () => _cardBump.value = false);
  }

  Future<void> _toggleFavorite() async {
    final img = _current;
    if (img == null || !img.isReady) return;
    _patchImage(img.copyWith(isFavorite: !img.isFavorite));
    try {
      _patchImage(await _repo.updateImage(img.id, favorite: !img.isFavorite));
      if (mounted) Toast.show(context, img.isFavorite ? 'Favorilerden çıkarıldı' : 'Favorilere eklendi');
    } catch (e) {
      _patchImage(img);
      if (mounted) Toast.error(context, e);
    }
  }

  Future<void> _makeMaster(GenerationImage img) async {
    try {
      await _repo.updateImage(img.id, master: true);
      await _load();
      if (mounted) Toast.show(context, 'V${img.variantIndex} master olarak seçildi');
    } catch (e) {
      if (mounted) Toast.error(context, e);
    }
  }

  void _patchImage(GenerationImage img) {
    final g = _gen;
    if (g == null) return;
    setState(() {
      _gen = Generation(
        id: g.id,
        projectId: g.projectId,
        status: g.status,
        variantCount: g.variantCount,
        userPrompt: g.userPrompt,
        aspectRatio: g.aspectRatio,
        shadowEnabled: g.shadowEnabled,
        studioStyleId: g.studioStyleId,
        lightingPresetId: g.lightingPresetId,
        sceneTypeId: g.sceneTypeId,
        qualityName: g.qualityName,
        qualityKey: g.qualityKey,
        modelLabel: g.modelLabel,
        creditsCharged: g.creditsCharged,
        errorMessage: g.errorMessage,
        projectTitle: g.projectTitle,
        originalUrl: g.originalUrl,
        cutoutUrl: g.cutoutUrl,
        images: [for (final i in g.images) i.id == img.id ? img : i],
      );
    });
  }

  Future<bool> _ensureGalleryAccess() async {
    if (await Gal.hasAccess()) return true;
    return Gal.requestAccess();
  }

  Future<void> _downloadOne() async {
    final img = _current;
    if (img == null || !img.isReady) return;
    if (!await _ensureGalleryAccess()) return;
    try {
      final bytes = await _repo.downloadImage(img.url!);
      await Gal.putImageBytes(Uint8List.fromList(bytes), name: 'studioai-${_gen!.id}-v${img.variantIndex}');
      if (mounted) Toast.show(context, 'Seçili versiyon indirildi', subtitle: 'Stüdyo kalitesinde galeriye aktarıldı.');
    } catch (e) {
      if (mounted) Toast.error(context, e is ApiException ? e : 'Görsel kaydedilemedi.');
    }
  }

  Future<void> _downloadAll() async {
    final ready = _gen?.images.where((i) => i.isReady).toList() ?? const [];
    if (ready.isEmpty) return;
    if (!await _ensureGalleryAccess() || !mounted) return;
    setState(() => _downloading = true);
    Toast.show(context, '${ready.length} Varyasyon İndiriliyor...');
    try {
      for (final img in ready) {
        final bytes = await _repo.downloadImage(img.url!);
        await Gal.putImageBytes(Uint8List.fromList(bytes), name: 'studioai-${_gen!.id}-v${img.variantIndex}');
      }
      if (mounted) Toast.show(context, 'Görsel Hazırlandı', subtitle: '${ready.length} görsel galeriye aktarıldı.');
    } catch (e) {
      if (mounted) Toast.error(context, e is ApiException ? e : 'Görseller kaydedilemedi.');
    } finally {
      if (mounted) setState(() => _downloading = false);
    }
  }

  Future<void> _share() async {
    final ready = _gen?.images.where((i) => i.isReady).toList() ?? const [];
    if (ready.isEmpty) return;
    try {
      final dir = await getTemporaryDirectory();
      final files = <XFile>[];
      for (final img in ready) {
        final file = File('${dir.path}/studioai-${_gen!.id}-v${img.variantIndex}.png');
        await file.writeAsBytes(await _repo.downloadImage(img.url!));
        files.add(XFile(file.path, mimeType: 'image/png'));
      }
      await SharePlus.instance.share(ShareParams(files: files, text: _gen?.projectTitle ?? 'StudioAI stüdyo çekimi'));
    } catch (e) {
      if (mounted) Toast.error(context, e is ApiException ? e : 'Paylaşım başlatılamadı.');
    }
  }

  Future<void> _generateMore() async {
    final g = _gen;
    if (g == null || _generatingMore) return;
    setState(() => _generatingMore = true);
    try {
      final (updated, balance) = await _repo.moreVariants(g.id, 4);
      if (balance != null) ref.read(sessionProvider.notifier).updateCredits(balance);
      setState(() => _gen = updated);
      _poll?.cancel();
      _poll = Timer(const Duration(milliseconds: 2500), _load);
    } on ApiException catch (e) {
      if (!mounted) return;
      e.isProRequired ? showProSheet(context, ref, reason: e.message) : Toast.error(context, e);
    } finally {
      if (mounted) setState(() => _generatingMore = false);
    }
  }

  void _editPrompt() {
    final g = _gen;
    if (g == null) return;
    ref.read(studioDraftProvider.notifier).loadFromGeneration(g);
    context.go('/studio');
  }

  @override
  Widget build(BuildContext context) {
    final g = _gen;
    final top = MediaQuery.paddingOf(context).top + 64;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: const EditorTopBar(),
      body: g == null
          ? (_error != null
              ? EmptyState(icon: Symbols.cloud_off, title: 'Sonuçlar yüklenemedi', message: ApiException.messageOf(_error!))
              : const Center(child: Spinner(size: 28)))
          : ListView(
              padding: EdgeInsets.only(top: top, bottom: MediaQuery.paddingOf(context).bottom + 32),
              children: [
                _chips(g),
                const SizedBox(height: Space.md),
                _promptSummary(g),
                const SizedBox(height: Space.md),
                _stage(g),
                const SizedBox(height: Space.md),
                _variations(g),
                const SizedBox(height: Space.md),
                _toolbar(),
                const SizedBox(height: Space.md),
                _actions(g),
              ],
            ),
    );
  }

  Widget _chips(Generation g) {
    return SizedBox(
      height: 32,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: Space.margin),
        children: [
          _Chip(icon: Symbols.auto_awesome, label: g.modelLabel, highlighted: true),
          const SizedBox(width: Space.xs),
          const _Chip(icon: Symbols.verified, label: 'Commercial Grade'),
          const SizedBox(width: Space.xs),
          _Chip(icon: Symbols.hdr_on, label: g.qualityKey == 'ultra' ? '4K UHD Studio' : '${g.qualityName ?? 'HD'} Studio'),
        ],
      ),
    );
  }

  Widget _promptSummary(Generation g) {
    final text = (g.userPrompt?.isNotEmpty ?? false) ? g.userPrompt! : 'Seçilen stüdyo stili ve ışık ayarlarıyla üretildi.';
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: Space.margin),
      child: Container(
        padding: const EdgeInsets.all(Space.sm),
        decoration: BoxDecoration(
          color: AppColors.surfaceContainerLow,
          borderRadius: BorderRadius.circular(Radii.xl),
          boxShadow: const [BoxShadow(color: Color(0x0D000000), blurRadius: 2, offset: Offset(0, 1))],
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            const Icon(Symbols.psychology, size: 14, color: AppColors.primary),
            const SizedBox(width: 4),
            Text('PROMPT ÖZETİ', style: AppText.labelSm.wider.c(AppColors.onSurfaceVariant)),
            const Spacer(),
            Pressable(
              onTap: () {
                Clipboard.setData(ClipboardData(text: text));
                Toast.show(context, 'Prompt panoya kopyalandı');
              },
              child: Row(children: [
                const Icon(Symbols.content_copy, size: 13, color: AppColors.primary),
                const SizedBox(width: 2),
                Text('Kopyala', style: AppText.labelSm.wider.c(AppColors.primary)),
              ]),
            ),
          ]),
          const SizedBox(height: 4),
          Text('"$text"', maxLines: 2, overflow: TextOverflow.ellipsis, style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
        ]),
      ),
    );
  }

  Widget _stage(Generation g) {
    final img = _current;
    final before = g.originalUrl;
    final title = (g.projectTitle?.isNotEmpty ?? false) ? g.projectTitle! : 'Stüdyo Çekimi #${g.id}';
    final size = img?.width != null ? '${img!.width} × ${img.height} px' : 'İşleniyor';
    final tag = img == null ? '' : 'V${img.variantIndex}${img.isMaster ? ' MASTER' : ''}';

    return Padding(
      padding: const EdgeInsets.fromLTRB(Space.margin, Space.sm, Space.margin, 0),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 420),
          child: Stack(clipBehavior: Clip.none, children: [
            // Amber arka ışık
            Positioned.fill(
              left: -8,
              right: -8,
              top: -8,
              bottom: -8,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(32),
                  gradient: LinearGradient(
                    begin: Alignment.bottomCenter,
                    end: Alignment.topCenter,
                    colors: [AppColors.primaryContainer.withValues(alpha: 0.15), Colors.transparent],
                  ),
                ),
              ),
            ),
            // Kart destesi katmanları
            Positioned(
              left: 20,
              right: 20,
              top: -14,
              bottom: 12,
              child: Transform.scale(scale: 0.92, child: _deckLayer(AppColors.surfaceContainerLowest.withValues(alpha: 0.6))),
            ),
            Positioned(
              left: 10,
              right: 10,
              top: -7,
              bottom: 6,
              child: Transform.scale(scale: 0.96, child: _deckLayer(AppColors.surfaceContainerHigh.withValues(alpha: 0.8))),
            ),
            ValueListenableBuilder<bool>(
              valueListenable: _cardBump,
              builder: (_, bump, child) => AnimatedScale(scale: bump ? 0.99 : 1, duration: const Duration(milliseconds: 150), child: child),
              child: Container(
                decoration: BoxDecoration(
                  color: AppColors.surfaceContainer,
                  borderRadius: BorderRadius.circular(Radii.xxl),
                  boxShadow: const [BoxShadow(color: Color(0x66000000), blurRadius: 50, offset: Offset(0, 25))],
                ),
                clipBehavior: Clip.antiAlias,
                child: AspectRatio(
                  aspectRatio: 4 / 5,
                  child: Stack(fit: StackFit.expand, children: [
                    if (img?.isReady == true)
                      BeforeAfter(
                        beforeUrl: before,
                        afterUrl: img!.url!,
                        // 4:5 dışındaki düzenlemeler (ör. 16:9) kırpılmadan gösterilir.
                        afterFit: (img.aspectRatio ?? g.aspectRatio) == '4:5' ? BoxFit.cover : BoxFit.contain,
                      )
                    else
                      _RenderingPlaceholder(originalUrl: g.cutoutUrl ?? before, failed: img?.status == ProcessStatus.failed),
                    Positioned(
                      top: 14,
                      left: 14,
                      child: IgnorePointer(
                        child: Glass(
                          color: AppColors.surfaceContainerLowest.withValues(alpha: 0.8),
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          child: Row(mainAxisSize: MainAxisSize.min, children: [
                            const PulseDot(glow: false),
                            const SizedBox(width: 6),
                            Text(img?.isReady == true ? 'Canlı Karşılaştırma' : 'Render Ediliyor', style: AppText.labelSm),
                          ]),
                        ),
                      ),
                    ),
                    Positioned(
                      top: 14,
                      right: 14,
                      child: Row(children: [
                        _GlassIcon(icon: img?.isFavorite == true ? Symbols.star : Symbols.star, filled: img?.isFavorite == true, onTap: _toggleFavorite),
                        const SizedBox(width: Space.sm),
                        _GlassIcon(icon: Symbols.arrow_downward, onTap: _downloadOne),
                      ]),
                    ),
                    Positioned(
                      left: 0,
                      right: 0,
                      bottom: 0,
                      child: IgnorePointer(
                        child: Container(
                          padding: const EdgeInsets.all(14),
                          decoration: const BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.bottomCenter,
                              end: Alignment.topCenter,
                              colors: [AppColors.surfaceContainerLowest, Color(0xCC0E0E11), Colors.transparent],
                            ),
                          ),
                          child: Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
                            Expanded(
                              child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
                                Text(title, style: AppText.titleMd.bold, maxLines: 1, overflow: TextOverflow.ellipsis),
                                Row(children: [
                                  const Icon(Symbols.aspect_ratio, size: 13, color: AppColors.secondary),
                                  const SizedBox(width: 4),
                                  Flexible(
                                    child: Text('${img?.editLabel != null ? '${img!.editLabel} • ' : ''}${ratioLabel(img?.aspectRatio ?? g.aspectRatio)} • $size',
                                        style: AppText.labelSm.c(AppColors.onSurfaceVariant), overflow: TextOverflow.ellipsis),
                                  ),
                                ]),
                              ]),
                            ),
                            if (tag.isNotEmpty)
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(color: AppColors.primaryContainer, borderRadius: BorderRadius.circular(Radii.lg)),
                                child: Text(tag, style: AppText.labelMd.bold.wider.c(AppColors.onPrimaryContainer)),
                              ),
                          ]),
                        ),
                      ),
                    ),
                  ]),
                ),
              ),
            ),
          ]),
        ),
      ),
    );
  }

  Widget _deckLayer(Color color) => DecoratedBox(
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(Radii.xxl),
          boxShadow: const [BoxShadow(color: Color(0x33000000), blurRadius: 10, offset: Offset(0, 4))],
        ),
      );

  Widget _variations(Generation g) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: Space.margin),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Row(children: [
          Text('STÜDYO VARYASYONLARI', style: AppText.labelMd.semi.wider.c(AppColors.onSurfaceVariant)),
          const Spacer(),
          Text('${g.readyCount} / ${g.variantCount} Hazır', style: AppText.labelSm.c(AppColors.primary)),
        ]),
        const SizedBox(height: Space.xs),
        SizedBox(
          height: 72,
          child: ListView(
            scrollDirection: Axis.horizontal,
            clipBehavior: Clip.none,
            padding: const EdgeInsets.symmetric(vertical: 4),
            children: [
              for (var i = 0; i < g.images.length; i++) ...[
                _VariantThumb(
                  image: g.images[i],
                  selected: i == _selected,
                  onTap: () => _select(i),
                  onLongPress: g.images[i].isReady && !g.images[i].isMaster ? () => _makeMaster(g.images[i]) : null,
                ),
                const SizedBox(width: Space.sm),
              ],
              Pressable(
                onTap: g.status.isFinished ? _generateMore : null,
                child: Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(color: AppColors.surfaceContainerHigh, borderRadius: BorderRadius.circular(Radii.xl)),
                  child: _generatingMore
                      ? const Center(child: Spinner())
                      : Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                          Icon(Symbols.add, size: 24, color: g.status.isFinished ? AppColors.primary : AppColors.outlineVariant),
                          const SizedBox(height: 2),
                          Text('+4 Üret', style: AppText.labelSm.c(AppColors.onSurfaceVariant)),
                        ]),
                ),
              ),
            ],
          ),
        ),
      ]),
    );
  }

  /// Seçili varyantı araçla düzenler: sonuç yeni varyant olarak eklenir ve seçilir.
  Future<void> _applyTool(EditTool tool) async {
    final img = _current;
    final g = _gen;
    if (g == null || img == null || !img.isReady) {
      Toast.show(context, 'Önce hazır bir varyant seç', error: true);
      return;
    }
    setState(() => _activeTool = tool.key);

    final quality = ref.read(catalogProvider).value?.qualityLevels.where((q) => q.key == g.qualityKey).firstOrNull;
    final picked = await showEditToolSheet(context, tool, credits: quality?.creditsFor(1) ?? 1, currentRatio: img.aspectRatio ?? g.aspectRatio);
    if (picked == null || !mounted) {
      if (mounted) setState(() => _activeTool = null);
      return;
    }

    setState(() => _editing = true);
    try {
      final (updated, balance) = await _repo.editImage(img.id, tool.key, picked.$1, note: picked.$2);
      if (balance != null) ref.read(sessionProvider.notifier).updateCredits(balance);
      if (!mounted) return;
      setState(() {
        _gen = updated;
        _selected = updated.images.length - 1; // yeni varyantı göster
      });
      Toast.show(context, '${tool.title} uygulanıyor', subtitle: 'V${updated.images.last.variantIndex} olarak eklenecek.');
      _poll?.cancel();
      _poll = Timer(const Duration(milliseconds: 2000), _load);
    } on ApiException catch (e) {
      if (!mounted) return;
      e.isProRequired ? showProSheet(context, ref, reason: e.message) : Toast.error(context, e);
    } finally {
      if (mounted) {
        setState(() {
          _editing = false;
          _activeTool = null;
        });
      }
    }
  }

  Widget _toolbar() {
    final ready = _current?.isReady == true && !_editing;
    final tools = <(IconData, String, VoidCallback, String)>[
      (Symbols.title, 'Prompt Düzenle', _editPrompt, 'prompt'),
      for (final t in editTools) (t.icon, t.title, () => _applyTool(t), t.key),
    ];
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: Space.margin),
      child: Container(
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(
          color: AppColors.surfaceContainerHigh,
          borderRadius: BorderRadius.circular(Radii.xxl),
          boxShadow: AppColors.cardShadow,
        ),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceAround, children: [
          for (var i = 0; i < tools.length; i++)
            Builder(builder: (context) {
              final (icon, label, onTap, key) = tools[i];
              // Tasarımdaki gibi "T" varsayılan aktif; bir araç açıkken o araç vurgulanır.
              final active = _activeTool == null ? i == 0 : _activeTool == key;
              final enabled = i == 0 || ready;
              return Tooltip(
                message: label,
                child: Pressable(
                  onTap: enabled ? onTap : () => Toast.show(context, _editing ? 'Düzenleme başlatılıyor...' : 'Önce hazır bir varyant seç'),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 160),
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: active ? AppColors.surfaceContainerLowest : Colors.transparent,
                      borderRadius: BorderRadius.circular(Radii.xl),
                      boxShadow: active ? const [BoxShadow(color: Color(0x0D000000), blurRadius: 2, offset: Offset(0, 1))] : null,
                    ),
                    child: _editing && _activeTool == key
                        ? const Center(child: Spinner(size: 18, stroke: 2))
                        : Icon(icon, size: 20, color: active ? AppColors.primary : (enabled ? AppColors.onSurfaceVariant : AppColors.outlineVariant)),
                  ),
                ),
              );
            }),
        ]),
      ),
    );
  }

  Widget _actions(Generation g) {
    final anyReady = g.images.any((i) => i.isReady);
    return Padding(
      padding: const EdgeInsets.fromLTRB(Space.margin, Space.xs, Space.margin, 0),
      child: Column(children: [
        AmberButton(
          height: 56,
          radius: Radii.xxl,
          glowOpacity: 0.28,
          loading: _downloading,
          onPressed: anyReady ? _downloadAll : null,
          child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            _downloading ? const Spinner(size: 20, color: AppColors.onPrimaryContainer) : const Icon(Symbols.download, size: 22, fill: 1),
            const SizedBox(width: Space.sm),
            Text(g.qualityKey == 'ultra' ? 'Tümünü 4K HDR İndir' : 'Tümünü İndir', style: AppText.headlineMd.bold.c(AppColors.onPrimaryContainer)),
          ]),
        ),
        const SizedBox(height: Space.xs),
        Row(children: [
          Expanded(
            child: _SecondaryAction(
              icon: Symbols.storefront,
              iconColor: AppColors.secondary,
              label: "Shopify'a Aktar",
              onTap: () => Toast.show(context, 'Shopify entegrasyonu yakında', subtitle: 'Şimdilik "Kataloğa Paylaş" ile dışa aktarabilirsin.'),
            ),
          ),
          const SizedBox(width: Space.xs),
          Expanded(
            child: _SecondaryAction(icon: Symbols.share, iconColor: AppColors.tertiary, label: 'Kataloğa Paylaş', onTap: anyReady ? _share : null),
          ),
        ]),
      ]),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({required this.icon, required this.label, this.highlighted = false});

  final IconData icon;
  final String label;
  final bool highlighted;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: highlighted ? AppColors.surfaceContainerHigh : AppColors.surfaceContainer,
        borderRadius: BorderRadius.circular(Radii.full),
      ),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, size: 15, fill: highlighted ? 1 : 0, color: highlighted ? AppColors.primary : AppColors.onSurfaceVariant),
        const SizedBox(width: 6),
        Text(label, style: AppText.labelMd.c(highlighted ? AppColors.primary : AppColors.onSurfaceVariant)),
      ]),
    );
  }
}

class _GlassIcon extends StatelessWidget {
  const _GlassIcon({required this.icon, this.onTap, this.filled = false});

  final IconData icon;
  final VoidCallback? onTap;
  final bool filled;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      scale: 0.9,
      onTap: onTap,
      child: Glass(
        color: AppColors.surfaceContainerLowest.withValues(alpha: 0.8),
        shadow: const [BoxShadow(color: Color(0x1A000000), blurRadius: 6, offset: Offset(0, 4))],
        child: SizedBox(width: 40, height: 40, child: Icon(icon, size: 20, fill: filled ? 1 : 0, color: filled ? AppColors.primary : AppColors.onSurface)),
      ),
    );
  }
}

class _VariantThumb extends StatelessWidget {
  const _VariantThumb({required this.image, required this.selected, required this.onTap, this.onLongPress});

  final GenerationImage image;
  final bool selected;
  final VoidCallback onTap;
  final VoidCallback? onLongPress;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      onLongPress: onLongPress,
      child: AnimatedScale(
        scale: selected ? 1.05 : 1,
        duration: const Duration(milliseconds: 150),
        child: Container(
          width: 64,
          height: 64,
          padding: const EdgeInsets.all(2),
          decoration: BoxDecoration(
            color: selected ? AppColors.primaryContainer : AppColors.surfaceContainerHigh,
            borderRadius: BorderRadius.circular(Radii.xl),
            boxShadow: selected ? const [BoxShadow(color: Color(0x1A000000), blurRadius: 6, offset: Offset(0, 4))] : null,
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: Stack(fit: StackFit.expand, children: [
              if (image.isReady)
                NetImage(image.thumbUrl ?? image.url)
              else
                ColoredBox(
                  color: AppColors.surfaceContainer,
                  child: Center(
                    child: image.status == ProcessStatus.failed
                        ? const Icon(Symbols.broken_image, size: 20, color: AppColors.error)
                        : const Spinner(size: 18, stroke: 2),
                  ),
                ),
              Positioned(
                right: 2,
                bottom: 2,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  decoration: BoxDecoration(color: AppColors.surfaceContainerLowest.withValues(alpha: 0.9), borderRadius: BorderRadius.circular(Radii.sm)),
                  child: Text('V${image.variantIndex}', style: AppText.labelSm.bold.copyWith(fontSize: 9, color: selected ? AppColors.primary : AppColors.onSurfaceVariant)),
                ),
              ),
              if (image.isFavorite)
                const Positioned(top: 2, left: 2, child: Icon(Symbols.star, size: 12, fill: 1, color: AppColors.primaryContainer)),
              if (image.editTool != null)
                Positioned(
                  top: 2,
                  right: 2,
                  child: Container(
                    width: 16,
                    height: 16,
                    decoration: BoxDecoration(color: AppColors.surfaceContainerLowest.withValues(alpha: 0.9), shape: BoxShape.circle),
                    child: const Icon(Symbols.auto_fix_high, size: 10, color: AppColors.primary),
                  ),
                ),
            ]),
          ),
        ),
      ),
    );
  }
}

class _SecondaryAction extends StatelessWidget {
  const _SecondaryAction({required this.icon, required this.iconColor, required this.label, this.onTap});

  final IconData icon;
  final Color iconColor;
  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: Space.md, vertical: 12),
        decoration: BoxDecoration(
          color: AppColors.surfaceContainer,
          borderRadius: BorderRadius.circular(Radii.xl),
          boxShadow: const [BoxShadow(color: Color(0x0D000000), blurRadius: 2, offset: Offset(0, 1))],
        ),
        child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
          Icon(icon, size: 18, color: iconColor),
          const SizedBox(width: 6),
          Flexible(child: Text(label, style: AppText.labelLg, overflow: TextOverflow.ellipsis)),
        ]),
      ),
    );
  }
}

/// Varyant hazırlanırken: soluk orijinal + amber ilerleme animasyonu.
class _RenderingPlaceholder extends StatelessWidget {
  const _RenderingPlaceholder({this.originalUrl, required this.failed});

  final String? originalUrl;
  final bool failed;

  @override
  Widget build(BuildContext context) {
    return Stack(fit: StackFit.expand, children: [
      const ColoredBox(color: AppColors.surfaceContainerLowest),
      Opacity(opacity: 0.35, child: NetImage(originalUrl, fit: BoxFit.contain)),
      const Positioned.fill(child: Skeleton(radius: 0)),
      Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          failed ? const Icon(Symbols.broken_image, size: 36, color: AppColors.error) : const Spinner(size: 36, stroke: 3),
          const SizedBox(height: 12),
          Text(failed ? 'Bu varyant üretilemedi' : 'Stüdyo ışıkları ayarlanıyor...', style: AppText.titleMd.bold),
          const SizedBox(height: 4),
          Text(failed ? 'Kredisi otomatik iade edildi.' : 'Genellikle 15-40 saniye sürer', style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
        ]),
      ),
    ]);
  }
}
