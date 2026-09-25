import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models.dart';

/// Devam eden stüdyo çekiminin ayarları: dekupe → sahne → ayarlar → render akışı boyunca taşınır.
class StudioDraft {
  const StudioDraft({
    this.projectId,
    this.templateId,
    this.sceneTypeId,
    this.shadowEnabled = true,
    this.prompt = '',
    this.studioStyleId,
    this.lightingPresetId,
    this.qualityKey = 'ultra',
    this.variantCount = 4,
  });

  final int? projectId;
  final int? templateId;
  final int? sceneTypeId;
  final bool shadowEnabled;
  final String prompt;
  final int? studioStyleId;
  final int? lightingPresetId;
  final String qualityKey;
  final int variantCount;

  StudioDraft copyWith({
    int? projectId,
    int? templateId,
    int? sceneTypeId,
    bool? shadowEnabled,
    String? prompt,
    int? studioStyleId,
    bool clearStyle = false,
    int? lightingPresetId,
    String? qualityKey,
    int? variantCount,
  }) =>
      StudioDraft(
        projectId: projectId ?? this.projectId,
        templateId: templateId ?? this.templateId,
        sceneTypeId: sceneTypeId ?? this.sceneTypeId,
        shadowEnabled: shadowEnabled ?? this.shadowEnabled,
        prompt: prompt ?? this.prompt,
        studioStyleId: clearStyle ? null : (studioStyleId ?? this.studioStyleId),
        lightingPresetId: lightingPresetId ?? this.lightingPresetId,
        qualityKey: qualityKey ?? this.qualityKey,
        variantCount: variantCount ?? this.variantCount,
      );
}

class StudioDraftController extends Notifier<StudioDraft> {
  @override
  StudioDraft build() => const StudioDraft();

  /// Yeni proje başladığında; şablondan geldiyse ayarları ön-doldurur.
  void startProject(Project project, {TemplateItem? template}) {
    final t = template ?? project.template;
    state = StudioDraft(
      projectId: project.id,
      templateId: t?.id,
      sceneTypeId: project.sceneTypeId ?? t?.sceneTypeId,
      shadowEnabled: project.shadowEnabled,
      prompt: t?.defaultPrompt ?? state.prompt,
      studioStyleId: t?.studioStyleId ?? state.studioStyleId,
      lightingPresetId: t?.lightingPresetId ?? state.lightingPresetId,
      qualityKey: t?.qualityKey ?? state.qualityKey,
      variantCount: state.variantCount,
    );
  }

  /// "Hazır Set" / Keşfet'ten şablon uygulandığında.
  void applyTemplate(TemplateItem t) {
    state = state.copyWith(
      templateId: t.id,
      sceneTypeId: t.sceneTypeId,
      prompt: t.defaultPrompt,
      studioStyleId: t.studioStyleId,
      lightingPresetId: t.lightingPresetId,
      qualityKey: t.qualityKey,
    );
  }

  /// Sonuçlar ekranından "Prompt Düzenle" ile geri dönüldüğünde önceki ayarları yükler.
  void loadFromGeneration(Generation g) {
    state = state.copyWith(
      projectId: g.projectId,
      prompt: g.userPrompt ?? '',
      studioStyleId: g.studioStyleId,
      lightingPresetId: g.lightingPresetId,
      sceneTypeId: g.sceneTypeId,
      shadowEnabled: g.shadowEnabled,
      qualityKey: g.qualityKey,
      variantCount: g.variantCount > 8 ? 8 : g.variantCount,
    );
  }

  void setScene(int id) => state = state.copyWith(sceneTypeId: id);
  void setShadow(bool v) => state = state.copyWith(shadowEnabled: v);
  void setPrompt(String v) => state = state.copyWith(prompt: v);
  void setStyle(int? id) => state = id == null ? state.copyWith(clearStyle: true) : state.copyWith(studioStyleId: id);
  void setLighting(int id) => state = state.copyWith(lightingPresetId: id);
  void setQuality(String key) => state = state.copyWith(qualityKey: key);
  void setVariants(int v) => state = state.copyWith(variantCount: v);
}

final studioDraftProvider = NotifierProvider<StudioDraftController, StudioDraft>(StudioDraftController.new);
