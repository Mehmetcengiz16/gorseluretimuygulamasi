import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text.dart';
import '../../core/widgets/common.dart';

/// Sonuç ekranı düzenleme araçları — sunucudaki App\Support\EditTools ile aynı anahtarlar.
class EditTool {
  const EditTool(this.key, this.icon, this.title, this.subtitle, this.options, {this.allowNote = false});

  final String key;
  final IconData icon;
  final String title;
  final String subtitle;
  final List<EditOption> options;
  final bool allowNote;
}

class EditOption {
  const EditOption(this.key, this.label, this.hint, this.icon);

  final String key;
  final String label;
  final String hint;
  final IconData icon;
}

const editTools = <EditTool>[
  EditTool('retouch', Symbols.auto_fix_high, 'Kusursuzlaştır / Rötuş', 'Toz, çizik ve lekeleri temizler; detayları öne çıkarır.', [
    EditOption('clean', 'Genel Temizlik', 'Toz, çizik, parmak izi', Symbols.cleaning_services),
    EditOption('reflections', 'Yansıma & Parlaklık', 'Premium parlak görünüm', Symbols.flare),
    EditOption('sharpen', 'Keskinlik & Detay', 'Mikro detay, net kenar', Symbols.center_focus_strong),
  ], allowNote: true),
  EditTool('light', Symbols.wb_incandescent, 'Işık Yönünü Değiştir', 'Ana ışığın geldiği yönü değiştirir, gölgeler yeniden hesaplanır.', [
    EditOption('left', 'Soldan', 'Gölge sağa düşer', Symbols.west),
    EditOption('right', 'Sağdan', 'Gölge sola düşer', Symbols.east),
    EditOption('top', 'Üstten', 'Kısa, yumuşak gölge', Symbols.north),
    EditOption('back', 'Arkadan', 'Parlayan rim ışığı', Symbols.backlight_high),
  ]),
  EditTool('ratio', Symbols.crop, 'En/Boy Oranı', 'Arka plan doğal şekilde genişletilir, ürün kırpılmaz.', [
    EditOption('1:1', '1:1 Kare', 'Instagram, katalog', Symbols.crop_square),
    EditOption('4:5', '4:5 Portre', 'Sosyal medya akışı', Symbols.crop_portrait),
    EditOption('3:4', '3:4 Portre', 'E-ticaret ürün sayfası', Symbols.crop_3_2),
    EditOption('9:16', '9:16 Hikâye', 'Story, Reels', Symbols.crop_9_16),
    EditOption('16:9', '16:9 Yatay', 'Banner, web', Symbols.crop_16_9),
  ]),
  EditTool('color', Symbols.palette, 'Renk Sıcaklığı', 'Sahnenin renk tonunu ve atmosferini ayarlar.', [
    EditOption('warm', 'Sıcak', 'Amber, samimi tonlar', Symbols.wb_sunny),
    EditOption('neutral', 'Nötr', 'Doğru beyaz dengesi', Symbols.wb_auto),
    EditOption('cool', 'Soğuk', 'Ferah, mavimsi', Symbols.ac_unit),
    EditOption('golden', 'Altın', 'Sinematik altın grade', Symbols.auto_awesome),
  ]),
  EditTool('upscale', Symbols.high_density, '4K Çözünürlük Yükselt', 'Kompozisyonu değiştirmeden detayları artırır ve 4K çıktı üretir.', [
    EditOption('4k', '4K Ultra HD', '3840 px uzun kenar', Symbols.hdr_on),
  ]),
];

/// Seçenek sayfası. Kullanıcı "Uygula" derse (seçenek, not) döner.
Future<(String, String?)?> showEditToolSheet(BuildContext context, EditTool tool, {required int credits, String? currentRatio}) {
  return showModalBottomSheet<(String, String?)>(
    context: context,
    isScrollControlled: true,
    backgroundColor: AppColors.surfaceContainerLow,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    builder: (_) => _EditToolSheet(tool: tool, credits: credits, currentRatio: currentRatio),
  );
}

class _EditToolSheet extends StatefulWidget {
  const _EditToolSheet({required this.tool, required this.credits, this.currentRatio});

  final EditTool tool;
  final int credits;
  final String? currentRatio;

  @override
  State<_EditToolSheet> createState() => _EditToolSheetState();
}

class _EditToolSheetState extends State<_EditToolSheet> {
  late String _option = widget.tool.options
      .firstWhere((o) => widget.tool.key != 'ratio' || o.key != widget.currentRatio, orElse: () => widget.tool.options.first)
      .key;
  final _note = TextEditingController();

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final t = widget.tool;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(Space.margin, 12, Space.margin, Space.margin),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.surfaceBright, borderRadius: BorderRadius.circular(2)))),
            const SizedBox(height: Space.lg),
            Row(children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(color: AppColors.surfaceContainerLowest, borderRadius: BorderRadius.circular(Radii.xl)),
                child: Icon(t.icon, size: 22, color: AppColors.primary),
              ),
              const SizedBox(width: Space.md),
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(t.title, style: AppText.headlineMd.bold),
                  Text(t.subtitle, style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
                ]),
              ),
            ]),
            const SizedBox(height: Space.lg),
            // Seçenekler — Stüdyo Işıklandırması kartlarıyla aynı görsel dil
            GridView.count(
              crossAxisCount: t.options.length == 1 ? 1 : 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: Space.xs,
              crossAxisSpacing: Space.xs,
              childAspectRatio: t.options.length == 1 ? 5.6 : 2.75,
              padding: EdgeInsets.zero,
              children: [
                for (final o in t.options)
                  _OptionPill(
                    option: o,
                    selected: o.key == _option,
                    current: t.key == 'ratio' && o.key == widget.currentRatio,
                    onTap: () {
                      HapticFeedback.selectionClick();
                      setState(() => _option = o.key);
                    },
                  ),
              ],
            ),
            if (t.allowNote) ...[
              const SizedBox(height: Space.md),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: Space.md, vertical: Space.sm),
                decoration: BoxDecoration(color: AppColors.surfaceContainer, borderRadius: BorderRadius.circular(Radii.lg)),
                child: TextField(
                  controller: _note,
                  maxLength: 200,
                  maxLines: 2,
                  minLines: 1,
                  style: AppText.bodyMd,
                  decoration: InputDecoration(
                    isCollapsed: true,
                    filled: false,
                    border: InputBorder.none,
                    enabledBorder: InputBorder.none,
                    focusedBorder: InputBorder.none,
                    counterText: '',
                    hintText: 'İsteğe bağlı not (ör. etiketteki parlamayı azalt)',
                    hintStyle: AppText.bodyMd.c(AppColors.outlineVariant),
                  ),
                ),
              ),
            ],
            const SizedBox(height: Space.md),
            Row(children: [
              const Icon(Symbols.info, size: 14, color: AppColors.onSurfaceVariant),
              const SizedBox(width: 6),
              Expanded(
                child: Text('Seçili varyant korunur, sonuç yeni bir varyant olarak eklenir.',
                    style: AppText.labelSm.copyWith(fontWeight: FontWeight.w500, color: AppColors.onSurfaceVariant)),
              ),
              Text('${widget.credits} Kredi', style: AppText.labelSm.bold.c(AppColors.primary)),
            ]),
            const SizedBox(height: Space.md),
            AmberButton(
              onPressed: t.key == 'ratio' && _option == widget.currentRatio
                  ? null
                  : () => Navigator.pop(context, (_option, _note.text.trim().isEmpty ? null : _note.text.trim())),
              child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                const Icon(Symbols.auto_awesome, size: 22),
                const SizedBox(width: Space.sm),
                Text('Uygula', style: AppText.titleMd.bold.c(AppColors.onPrimaryContainer)),
              ]),
            ),
          ]),
        ),
      ),
    );
  }
}

class _OptionPill extends StatelessWidget {
  const _OptionPill({required this.option, required this.selected, required this.onTap, this.current = false});

  final EditOption option;
  final bool selected;
  final bool current;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final fg = selected ? AppColors.onPrimary : AppColors.onSurface;
    return Pressable(
      scale: 0.98,
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: selected ? AppColors.primary : AppColors.surfaceContainerHigh,
          borderRadius: BorderRadius.circular(Radii.lg),
          boxShadow: selected ? [BoxShadow(color: AppColors.primaryContainer.withValues(alpha: 0.22), blurRadius: 12)] : null,
        ),
        child: Row(children: [
          Icon(option.icon, size: 18, color: selected ? AppColors.onPrimary : AppColors.primaryContainer),
          const SizedBox(width: Space.sm),
          Expanded(
            child: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(option.label, maxLines: 1, overflow: TextOverflow.ellipsis, style: (selected ? AppText.labelMd.bold : AppText.labelMd.semi).c(fg)),
              Text(current ? 'Mevcut oran' : option.hint,
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
