import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_cropper/image_cropper.dart';
import 'package:image_picker/image_picker.dart';
import 'package:material_symbols_icons/symbols.dart';

import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text.dart';
import '../../core/widgets/common.dart';
import '../../core/widgets/toast.dart';
import '../../data/models.dart';
import '../../data/repository.dart';
import 'studio_draft.dart';

/// Kamera / galeri seçimi. Uzun kenar 2048 px'e küçültülerek yüklenir.
Future<String?> pickProductPhoto(BuildContext context) async {
  final source = await showModalBottomSheet<ImageSource>(
    context: context,
    backgroundColor: AppColors.surfaceContainerLow,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    builder: (context) => SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.surfaceBright, borderRadius: BorderRadius.circular(2)))),
            const SizedBox(height: 16),
            Text('Ürün Fotoğrafı', style: AppText.headlineMd.bold),
            const SizedBox(height: 4),
            Text('Ürünün net göründüğü, iyi aydınlatılmış bir fotoğraf seç.', style: AppText.bodySm.c(AppColors.onSurfaceVariant)),
            const SizedBox(height: 16),
            _SourceTile(icon: Symbols.photo_camera, title: 'Fotoğraf Çek', onTap: () => Navigator.pop(context, ImageSource.camera)),
            const SizedBox(height: 8),
            _SourceTile(icon: Symbols.photo_library, title: 'Galeriden Seç', onTap: () => Navigator.pop(context, ImageSource.gallery)),
          ],
        ),
      ),
    ),
  );
  if (source == null) return null;

  final file = await ImagePicker().pickImage(source: source, maxWidth: 2048, maxHeight: 2048, imageQuality: 92);
  return file?.path;
}

/// "Yeniden Kırp" — seçili görseli kırpar.
Future<String?> cropPhoto(String path) async {
  final cropped = await ImageCropper().cropImage(
    sourcePath: path,
    uiSettings: [
      AndroidUiSettings(
        toolbarTitle: 'Yeniden Kırp',
        toolbarColor: AppColors.surface,
        toolbarWidgetColor: AppColors.onSurface,
        statusBarLight: false,
        backgroundColor: AppColors.surface,
        activeControlsWidgetColor: AppColors.primaryContainer,
        lockAspectRatio: false,
      ),
      IOSUiSettings(title: 'Yeniden Kırp', doneButtonTitle: 'Bitti', cancelButtonTitle: 'Vazgeç'),
    ],
  );
  return cropped?.path;
}

/// Fotoğraf seç → proje oluştur → dekupe ekranına git.
Future<void> startNewShoot(BuildContext context, WidgetRef ref, {TemplateItem? template}) async {
  final path = await pickProductPhoto(context);
  if (path == null || !context.mounted) return;

  final progress = ValueNotifier<double>(0);
  showDialog<void>(
    context: context,
    barrierDismissible: false,
    builder: (_) => PopScope(
      canPop: false,
      child: Center(
        child: Container(
          width: 240,
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(color: AppColors.surfaceContainerHigh, borderRadius: BorderRadius.circular(Radii.xxl)),
          child: ValueListenableBuilder<double>(
            valueListenable: progress,
            builder: (_, v, _) => Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Spinner(size: 32),
                const SizedBox(height: 16),
                Material(color: Colors.transparent, child: Text('Fotoğraf yükleniyor', style: AppText.titleMd.bold)),
                const SizedBox(height: 4),
                Material(color: Colors.transparent, child: Text('%${(v * 100).round()}', style: AppText.labelMd.c(AppColors.primary))),
              ],
            ),
          ),
        ),
      ),
    ),
  );

  try {
    final project = await ref.read(repositoryProvider).createProject(path, templateId: template?.id, onProgress: (v) => progress.value = v);
    ref.read(studioDraftProvider.notifier).startProject(project, template: template);
    if (!context.mounted) return;
    Navigator.of(context, rootNavigator: true).pop();
    context.push('/editor/${project.id}');
  } catch (e) {
    if (!context.mounted) return;
    Navigator.of(context, rootNavigator: true).pop();
    Toast.error(context, e);
  }
}

class _SourceTile extends StatelessWidget {
  const _SourceTile({required this.icon, required this.title, required this.onTap});

  final IconData icon;
  final String title;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      child: Container(
        height: 56,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        decoration: BoxDecoration(color: AppColors.surfaceContainerHigh, borderRadius: BorderRadius.circular(Radii.xl)),
        child: Row(children: [
          Icon(icon, size: 22, color: AppColors.primaryContainer),
          const SizedBox(width: 12),
          Text(title, style: AppText.labelLg),
          const Spacer(),
          const Icon(Symbols.chevron_right, size: 20, color: AppColors.onSurfaceVariant),
        ]),
      ),
    );
  }
}
