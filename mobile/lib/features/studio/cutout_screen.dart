import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:material_symbols_icons/symbols.dart';
import 'package:path_provider/path_provider.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text.dart';
import '../../core/widgets/app_bars.dart';
import '../../core/widgets/common.dart';
import '../../core/widgets/toast.dart';
import '../../data/models.dart';
import '../../data/repository.dart';
import '../auth/session_controller.dart';
import 'photo_flow.dart';
import 'studio_draft.dart';

/// Ön Yükleme & Segmentasyon — ekrantasarimlari/r_n_y_kleme_segmentasyon
class CutoutScreen extends ConsumerStatefulWidget {
  const CutoutScreen({super.key, required this.projectId});

  final int projectId;

  @override
  ConsumerState<CutoutScreen> createState() => _CutoutScreenState();
}

class _CutoutScreenState extends ConsumerState<CutoutScreen> {
  Project? _project;
  Timer? _poll;
  bool _showOriginal = false;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }

  Future<void> _refresh() async {
    try {
      final p = await ref.read(repositoryProvider).project(widget.projectId);
      if (!mounted) return;
      setState(() => _project = p);
      final draft = ref.read(studioDraftProvider);
      if (draft.projectId != p.id) ref.read(studioDraftProvider.notifier).startProject(p);
      _schedulePoll(p);
    } catch (e) {
      if (mounted) Toast.error(context, e);
    }
  }

  void _schedulePoll(Project p) {
    _poll?.cancel();
    if (p.cutoutStatus.isWorking) _poll = Timer(const Duration(milliseconds: 2000), _refresh);
  }

  Future<void> _runCutout({bool precise = false}) async {
    setState(() => _busy = true);
    try {
      await ref.read(repositoryProvider).cutout(widget.projectId, precise: precise);
      await _refresh();
    } catch (e) {
      if (mounted) Toast.error(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _replace({required bool crop}) async {
    String? path;
    if (crop) {
      // Mevcut orijinali indirip kırpıcıya ver.
      final url = _project?.originalUrl;
      if (url == null) return;
      setState(() => _busy = true);
      try {
        final bytes = await ref.read(repositoryProvider).downloadImage(url);
        final file = File('${(await getTemporaryDirectory()).path}/crop-${DateTime.now().millisecondsSinceEpoch}.jpg');
        await file.writeAsBytes(bytes);
        path = await cropPhoto(file.path);
      } catch (e) {
        if (mounted) Toast.error(context, e);
      } finally {
        if (mounted) setState(() => _busy = false);
      }
    } else {
      path = await pickProductPhoto(context);
    }
    if (path == null || !mounted) return;

    setState(() => _busy = true);
    try {
      final p = await ref.read(repositoryProvider).replaceImage(widget.projectId, path);
      setState(() => _project = p);
      _schedulePoll(p);
    } catch (e) {
      if (mounted) Toast.error(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _continue() async {
    final draft = ref.read(studioDraftProvider);
    try {
      await ref.read(repositoryProvider).updateProject(widget.projectId, sceneTypeId: draft.sceneTypeId, shadowEnabled: draft.shadowEnabled);
    } catch (_) {
      // Ayarlar üretim isteğinde de gönderildiği için sessizce devam edilir.
    }
    if (mounted) context.go('/studio');
  }

  @override
  Widget build(BuildContext context) {
    final p = _project;
    final draft = ref.watch(studioDraftProvider);
    final scenes = ref.watch(catalogProvider).value?.sceneTypes ?? const <CatalogItem>[];
    final top = MediaQuery.paddingOf(context).top + 64;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: EditorTopBar(onBack: () => context.canPop() ? context.pop() : context.go('/discover')),
      body: ListView(
        padding: EdgeInsets.only(top: top, bottom: MediaQuery.paddingOf(context).bottom + 32),
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(Space.margin, Space.sm, Space.margin, Space.md),
            child: StepperBar(current: 1),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: Space.margin),
            child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              _preview(p),
              const SizedBox(height: Space.md + Space.xs),
              SectionHeader(
                icon: Symbols.palette,
                title: 'Hızlı Sahne Türü',
                titleStyle: AppText.headlineMd.bold,
                trailing: Text(trUpper('${scenes.length} Stil Mevcut'), style: AppText.labelSm.wider.c(AppColors.onSurfaceVariant)),
              ),
              const SizedBox(height: Space.md),
            ]),
          ),
          SizedBox(
            height: 150,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: Space.margin),
              clipBehavior: Clip.none,
              itemCount: scenes.isEmpty ? 4 : scenes.length,
              separatorBuilder: (_, _) => const SizedBox(width: Space.sm),
              itemBuilder: (_, i) {
                if (scenes.isEmpty) return const Skeleton(width: 112);
                final s = scenes[i];
                return _SceneChip(scene: s, selected: draft.sceneTypeId == s.id, onTap: () => ref.read(studioDraftProvider.notifier).setScene(s.id));
              },
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(Space.margin, Space.sm, Space.margin, 0),
            child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              _ShadowToggle(value: draft.shadowEnabled, onChanged: ref.read(studioDraftProvider.notifier).setShadow),
              const SizedBox(height: Space.md + Space.sm),
              AmberButton(
                onPressed: p == null ? null : _continue,
                child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                  Flexible(child: Text('Işık & Prompt Ayarlarına Geç', style: AppText.headlineMd.bold.c(AppColors.onPrimaryContainer), overflow: TextOverflow.ellipsis)),
                  const SizedBox(width: Space.xs),
                  const Icon(Symbols.arrow_forward, size: 20),
                ]),
              ),
            ]),
          ),
        ],
      ),
    );
  }

  Widget _preview(Project? p) {
    final status = p?.cutoutStatus ?? ProcessStatus.pending;
    final done = status == ProcessStatus.done && p?.cutoutUrl != null;
    final imageUrl = (_showOriginal || !done) ? p?.originalUrl : p?.cutoutUrl;

    final (label, color) = switch (status) {
      ProcessStatus.done => ('AI Dekupe Tamamlandı', AppColors.primary),
      ProcessStatus.failed => ('Dekupe Başarısız', AppColors.error),
      _ => ('AI Dekupe İşleniyor', AppColors.primary),
    };

    return AspectRatio(
      aspectRatio: 4 / 5,
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.surfaceContainerLow,
          borderRadius: BorderRadius.circular(Radii.xl),
          boxShadow: const [BoxShadow(color: Color(0x66000000), blurRadius: 50, offset: Offset(0, 25))],
        ),
        clipBehavior: Clip.antiAlias,
        child: Stack(children: [
          // Nokta deseni (radial-gradient #ffdca1 0.75px / 16px, %15 opaklık)
          const Positioned.fill(child: CustomPaint(painter: _DotGridPainter())),
          // Ürün + amber hale
          Center(
            child: FractionallySizedBox(
              widthFactor: 0.78,
              heightFactor: 0.82,
              child: Stack(clipBehavior: Clip.none, alignment: Alignment.center, children: [
                Positioned.fill(
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(Radii.xxl),
                      boxShadow: [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.2), blurRadius: 40)],
                    ),
                  ),
                ),
                if (imageUrl != null)
                  Positioned.fill(
                    child: DecoratedBox(
                      decoration: const BoxDecoration(boxShadow: [BoxShadow(color: Color(0xA6000000), blurRadius: 24, offset: Offset(0, 16))]),
                      child: _ZoomableImage(url: imageUrl),
                    ),
                  ),
                if (status.isWorking) const Positioned.fill(child: _ScanOverlay()),
                if (done)
                  Positioned(
                    bottom: -8,
                    child: Glass(
                      color: AppColors.surfaceContainerHighest.withValues(alpha: 0.9),
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                      shadow: const [BoxShadow(color: Color(0x40000000), blurRadius: 15, offset: Offset(0, 10))],
                      child: Row(mainAxisSize: MainAxisSize.min, children: [
                        const Icon(Symbols.auto_fix_high, size: 14, color: AppColors.primary),
                        const SizedBox(width: 4),
                        Text('%99.8 Doğruluk Payı', style: AppText.labelSm.c(AppColors.onSurfaceVariant)),
                      ]),
                    ),
                  ),
              ]),
            ),
          ),
          // Durum rozeti
          Positioned(
            top: Space.md,
            left: Space.md,
            child: Glass(
              color: AppColors.surfaceContainerHigh.withValues(alpha: 0.9),
              padding: const EdgeInsets.symmetric(horizontal: Space.sm, vertical: 4),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                status == ProcessStatus.failed
                    ? const Icon(Symbols.error, size: 10, color: AppColors.error, fill: 1)
                    : const PulseDot(),
                const SizedBox(width: Space.xs),
                Text(trUpper(label), style: AppText.labelSm.bold.wider.c(color)),
              ]),
            ),
          ),
          // Katman (orijinal/dekupe) ve yakınlaştırma
          Positioned(
            top: Space.md,
            right: Space.md,
            child: Row(children: [
              _CircleAction(icon: Symbols.layers, active: _showOriginal, onTap: done ? () => setState(() => _showOriginal = !_showOriginal) : null),
              const SizedBox(width: Space.xs),
              _CircleAction(icon: Symbols.zoom_in, onTap: imageUrl == null ? null : () => _openZoom(imageUrl)),
            ]),
          ),
          // Araç çubuğu
          Positioned(
            left: Space.md,
            right: Space.md,
            bottom: Space.md,
            child: Glass(
              color: AppColors.surfaceContainerHigh.withValues(alpha: 0.9),
              padding: const EdgeInsets.all(6),
              shadow: const [BoxShadow(color: Color(0x40000000), blurRadius: 15, offset: Offset(0, 10))],
              child: Row(children: [
                _ToolButton(icon: Symbols.crop, iconColor: AppColors.secondary, label: 'Yeniden Kırp', onTap: _busy ? null : () => _replace(crop: true)),
                const SizedBox(width: Space.xs),
                _ToolButton(
                  icon: Symbols.draw,
                  label: 'Kenar Düzelt',
                  active: true,
                  busy: _busy || status.isWorking,
                  onTap: _busy || status.isWorking ? null : () => _runCutout(precise: true),
                ),
                const SizedBox(width: Space.xs),
                _ToolButton(icon: Symbols.swap_horiz, iconColor: AppColors.tertiary, label: 'Değiştir', onTap: _busy ? null : () => _replace(crop: false)),
              ]),
            ),
          ),
          if (status == ProcessStatus.failed)
            Positioned(
              left: 24,
              right: 24,
              top: 56,
              child: Text(p?.cutoutError ?? 'Dekupe yapılamadı. "Kenar Düzelt" ile tekrar deneyebilirsin.',
                  textAlign: TextAlign.center, style: AppText.bodySm.c(AppColors.error), maxLines: 3, overflow: TextOverflow.ellipsis),
            ),
        ]),
      ),
    );
  }

  void _openZoom(String url) {
    Navigator.of(context).push(PageRouteBuilder<void>(
      opaque: false,
      barrierColor: Colors.black87,
      pageBuilder: (_, _, _) => GestureDetector(
        onTap: () => Navigator.pop(context),
        child: InteractiveViewer(maxScale: 5, child: Center(child: NetImage(url, fit: BoxFit.contain))),
      ),
    ));
  }
}

/// 3 adımlı ilerleme çubuğu: Ürün & Dekupe → Sahne → Render.
class StepperBar extends StatelessWidget {
  const StepperBar({super.key, required this.current});

  final int current;

  @override
  Widget build(BuildContext context) {
    const steps = ['Ürün & Dekupe', 'Sahne', 'Render'];
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: Space.md, vertical: Space.sm),
      decoration: BoxDecoration(
        color: AppColors.surfaceContainer,
        borderRadius: BorderRadius.circular(Radii.full),
        boxShadow: const [BoxShadow(color: Color(0x0D000000), blurRadius: 2, offset: Offset(0, 1))],
      ),
      child: Row(children: [
        for (var i = 0; i < steps.length; i++) ...[
          if (i > 0)
            Expanded(
              child: Center(
                child: Container(width: 24, height: 2, decoration: BoxDecoration(color: AppColors.surfaceVariant, borderRadius: BorderRadius.circular(1))),
              ),
            ),
          Opacity(
            opacity: i + 1 == current ? 1 : 0.6,
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              Container(
                width: 24,
                height: 24,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: i + 1 == current ? AppColors.primaryContainer : AppColors.surfaceVariant,
                  boxShadow: i + 1 == current ? [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.35), blurRadius: 12)] : null,
                ),
                alignment: Alignment.center,
                child: Text('${i + 1}', style: AppText.labelSm.bold.c(i + 1 == current ? AppColors.onPrimaryContainer : AppColors.onSurface)),
              ),
              const SizedBox(width: Space.xs),
              Text(steps[i], style: i + 1 == current ? AppText.labelMd.bold.c(AppColors.primary) : AppText.labelMd),
            ]),
          ),
        ],
      ]),
    );
  }
}

class _SceneChip extends StatelessWidget {
  const _SceneChip({required this.scene, required this.selected, required this.onTap});

  final CatalogItem scene;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        width: 112,
        padding: const EdgeInsets.all(Space.sm),
        decoration: BoxDecoration(
          color: selected ? AppColors.surfaceContainerHighest : AppColors.surfaceContainer,
          borderRadius: BorderRadius.circular(Radii.xl),
          boxShadow: selected ? [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.18), blurRadius: 16, offset: const Offset(0, 4))] : null,
        ),
        child: Column(children: [
          AspectRatio(
            aspectRatio: 1,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(Radii.lg),
              child: Stack(fit: StackFit.expand, children: [
                NetImage(scene.thumbnailUrl),
                if (selected)
                  Positioned(
                    top: 4,
                    right: 4,
                    child: Container(
                      width: 20,
                      height: 20,
                      decoration: const BoxDecoration(color: AppColors.primaryContainer, shape: BoxShape.circle),
                      child: const Icon(Symbols.check, size: 14, color: AppColors.onPrimaryContainer),
                    ),
                  ),
              ]),
            ),
          ),
          const SizedBox(height: Space.sm),
          Text(
            scene.name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
            style: selected ? AppText.labelMd.bold.c(AppColors.primary) : AppText.labelMd.c(AppColors.onSurfaceVariant),
          ),
        ]),
      ),
    );
  }
}

class _ShadowToggle extends StatelessWidget {
  const _ShadowToggle({required this.value, required this.onChanged});

  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      scale: 0.99,
      onTap: () => onChanged(!value),
      child: Container(
        padding: const EdgeInsets.all(Space.md),
        decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(Radii.xl)),
        child: Row(children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(color: AppColors.secondaryContainer.withValues(alpha: 0.2), shape: BoxShape.circle),
            child: const Icon(Symbols.auto_awesome, size: 22, color: AppColors.secondary),
          ),
          const SizedBox(width: Space.sm),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Gölge & Yansıma Ekle', style: AppText.titleMd.bold),
              Text('Sahneye uygun gerçekçi temas gölgesi oluşturur', style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
            ]),
          ),
          const SizedBox(width: Space.sm),
          AmberSwitch(value: value),
        ]),
      ),
    );
  }
}

/// Tasarımdaki 44×24 anahtar: açıkken amber zemin + koyu topuz.
class AmberSwitch extends StatelessWidget {
  const AmberSwitch({super.key, required this.value});

  final bool value;

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 180),
      width: 44,
      height: 24,
      padding: const EdgeInsets.all(2),
      decoration: BoxDecoration(color: value ? AppColors.primaryContainer : AppColors.surfaceVariant, borderRadius: BorderRadius.circular(Radii.full)),
      child: AnimatedAlign(
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeOut,
        alignment: value ? Alignment.centerRight : Alignment.centerLeft,
        child: Container(
          width: 20,
          height: 20,
          decoration: BoxDecoration(color: value ? AppColors.onPrimaryContainer : AppColors.onSurfaceVariant, shape: BoxShape.circle),
        ),
      ),
    );
  }
}

class _CircleAction extends StatelessWidget {
  const _CircleAction({required this.icon, this.onTap, this.active = false});

  final IconData icon;
  final VoidCallback? onTap;
  final bool active;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      child: Glass(
        color: AppColors.surfaceContainerHigh.withValues(alpha: 0.9),
        shadow: const [BoxShadow(color: Color(0x1A000000), blurRadius: 6, offset: Offset(0, 4))],
        child: SizedBox(width: 36, height: 36, child: Icon(icon, size: 19, color: active ? AppColors.primary : AppColors.onSurface)),
      ),
    );
  }
}

class _ToolButton extends StatelessWidget {
  const _ToolButton({required this.icon, required this.label, this.onTap, this.active = false, this.iconColor, this.busy = false});

  final IconData icon;
  final String label;
  final VoidCallback? onTap;
  final bool active;
  final Color? iconColor;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Pressable(
        onTap: onTap,
        child: Container(
          constraints: const BoxConstraints(minHeight: 44),
          padding: const EdgeInsets.symmetric(horizontal: Space.sm, vertical: 6),
          decoration: BoxDecoration(
            color: active ? AppColors.surfaceVariant : Colors.transparent,
            borderRadius: BorderRadius.circular(Radii.full),
            boxShadow: active ? const [BoxShadow(color: Color(0x0D000000), blurRadius: 2, offset: Offset(0, 1))] : null,
          ),
          child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            busy ? const Spinner(size: 14, stroke: 2) : Icon(icon, size: 16, color: active ? AppColors.primary : iconColor),
            const SizedBox(width: 4),
            Flexible(
              child: Text(label,
                  textAlign: TextAlign.center, maxLines: 2, style: AppText.labelMd.c(active ? AppColors.primary : AppColors.onSurface)),
            ),
          ]),
        ),
      ),
    );
  }
}

class _ZoomableImage extends StatelessWidget {
  const _ZoomableImage({required this.url});

  final String url;

  @override
  Widget build(BuildContext context) => NetImage(url, fit: BoxFit.contain);
}

/// Dekupe sürerken ürünün üzerinde gezinen amber tarama çizgisi.
class _ScanOverlay extends StatefulWidget {
  const _ScanOverlay();

  @override
  State<_ScanOverlay> createState() => _ScanOverlayState();
}

class _ScanOverlayState extends State<_ScanOverlay> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 1800))..repeat(reverse: true);

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _c,
      builder: (_, _) => Align(
        alignment: Alignment(0, -1 + _c.value * 2),
        child: Container(
          height: 3,
          decoration: BoxDecoration(
            color: AppColors.primaryContainer,
            boxShadow: [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.8), blurRadius: 16, spreadRadius: 2)],
          ),
        ),
      ),
    );
  }
}

class _DotGridPainter extends CustomPainter {
  const _DotGridPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()..color = AppColors.primary.withValues(alpha: 0.15);
    for (double y = 8; y < size.height; y += 16) {
      for (double x = 8; x < size.width; x += 16) {
        canvas.drawCircle(Offset(x, y), 0.75, paint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
