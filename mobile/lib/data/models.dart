// API modelleri — sunucudaki app/Http/Resources sınıflarının karşılığı.

int _int(Object? v, [int fallback = 0]) => v is num ? v.toInt() : int.tryParse('$v') ?? fallback;
int? _intOrNull(Object? v) => v == null ? null : (v is num ? v.toInt() : int.tryParse('$v'));
double _double(Object? v, [double fallback = 0]) => v is num ? v.toDouble() : double.tryParse('$v') ?? fallback;
DateTime? _date(Object? v) => v is String ? DateTime.tryParse(v)?.toLocal() : null;
List<T> _list<T>(Object? v, T Function(Map<String, dynamic>) f) =>
    v is List ? v.whereType<Map>().map((e) => f(Map<String, dynamic>.from(e))).toList() : <T>[];

class AppUser {
  AppUser({required this.id, required this.name, required this.email, this.avatarUrl, required this.creditBalance, required this.isPro, this.proExpiresAt});

  final int id;
  final String name;
  final String email;
  final String? avatarUrl;
  final int creditBalance;
  final bool isPro;
  final DateTime? proExpiresAt;

  factory AppUser.fromJson(Map<String, dynamic> j) => AppUser(
        id: _int(j['id']),
        name: j['name'] as String? ?? '',
        email: j['email'] as String? ?? '',
        avatarUrl: j['avatar_url'] as String?,
        creditBalance: _int(j['credit_balance']),
        isPro: j['is_pro'] == true,
        proExpiresAt: _date(j['pro_expires_at']),
      );

  AppUser copyWith({int? creditBalance}) =>
      AppUser(id: id, name: name, email: email, avatarUrl: avatarUrl, creditBalance: creditBalance ?? this.creditBalance, isPro: isPro, proExpiresAt: proExpiresAt);
}

/// Kategori, stüdyo stili, sahne türü ve ışık ön ayarı için ortak model.
class CatalogItem {
  CatalogItem({required this.id, required this.name, this.slug, this.subtitle, this.icon, this.thumbnailUrl, this.isPro = false});

  final int id;
  final String name;
  final String? slug;
  final String? subtitle;
  final String? icon;
  final String? thumbnailUrl;
  final bool isPro;

  factory CatalogItem.fromJson(Map<String, dynamic> j) => CatalogItem(
        id: _int(j['id']),
        name: j['name'] as String? ?? '',
        slug: j['slug'] as String?,
        subtitle: j['subtitle'] as String?,
        icon: j['icon'] as String?,
        thumbnailUrl: j['thumbnail_url'] as String?,
        isPro: j['is_pro'] == true,
      );
}

class QualityLevel {
  QualityLevel({required this.id, required this.key, required this.name, required this.creditMultiplier, required this.imageSize, required this.isPro});

  final int id;
  final String key;
  final String name;
  final double creditMultiplier;
  final String imageSize;
  final bool isPro;

  int creditsFor(int variants) {
    final v = (variants * creditMultiplier).ceil();
    return v < 1 ? 1 : v;
  }

  factory QualityLevel.fromJson(Map<String, dynamic> j) => QualityLevel(
        id: _int(j['id']),
        key: j['key'] as String? ?? '',
        name: j['name'] as String? ?? '',
        creditMultiplier: _double(j['credit_multiplier'], 1),
        imageSize: j['image_size'] as String? ?? '1K',
        isPro: j['is_pro'] == true,
      );
}

class Catalog {
  Catalog({required this.categories, required this.studioStyles, required this.sceneTypes, required this.lightingPresets, required this.qualityLevels});

  final List<CatalogItem> categories;
  final List<CatalogItem> studioStyles;
  final List<CatalogItem> sceneTypes;
  final List<CatalogItem> lightingPresets;
  final List<QualityLevel> qualityLevels;

  factory Catalog.fromJson(Map<String, dynamic> j) => Catalog(
        categories: _list(j['categories'], CatalogItem.fromJson),
        studioStyles: _list(j['studio_styles'], CatalogItem.fromJson),
        sceneTypes: _list(j['scene_types'], CatalogItem.fromJson),
        lightingPresets: _list(j['lighting_presets'], CatalogItem.fromJson),
        qualityLevels: _list(j['quality_levels'], QualityLevel.fromJson),
      );
}

class AppConfig {
  AppConfig({required this.minVersion, required this.maintenance, required this.variantOptions, required this.maxVariantsFree, required this.promptMaxLength, required this.modelLabel});

  final String minVersion;
  final bool maintenance;
  final List<int> variantOptions;
  final int maxVariantsFree;
  final int promptMaxLength;
  final String modelLabel;

  factory AppConfig.fromJson(Map<String, dynamic> j) => AppConfig(
        minVersion: j['min_version'] as String? ?? '1.0.0',
        maintenance: j['maintenance'] == true,
        variantOptions: (j['variant_options'] as List?)?.map((e) => _int(e)).toList() ?? const [1, 2, 4, 8],
        maxVariantsFree: _int(j['max_variants_free'], 4),
        promptMaxLength: _int(j['prompt_max_length'], 500),
        modelLabel: j['model_label'] as String? ?? 'Studio Diffusion XL',
      );
}

class TemplateItem {
  TemplateItem({
    required this.id,
    required this.title,
    this.subtitle,
    this.coverUrl,
    this.badge,
    this.categoryName,
    this.categorySlug,
    this.studioStyleId,
    this.sceneTypeId,
    this.lightingPresetId,
    this.defaultPrompt,
    this.qualityKey,
    required this.isPro,
    required this.likesCount,
    required this.liked,
  });

  final int id;
  final String title;
  final String? subtitle;
  final String? coverUrl;
  final String? badge;
  final String? categoryName;
  final String? categorySlug;
  final int? studioStyleId;
  final int? sceneTypeId;
  final int? lightingPresetId;
  final String? defaultPrompt;
  final String? qualityKey;
  final bool isPro;
  final int likesCount;
  final bool liked;

  factory TemplateItem.fromJson(Map<String, dynamic> j) {
    final cat = j['category'] is Map ? Map<String, dynamic>.from(j['category'] as Map) : null;
    return TemplateItem(
      id: _int(j['id']),
      title: j['title'] as String? ?? '',
      subtitle: j['subtitle'] as String?,
      coverUrl: j['cover_url'] as String?,
      badge: j['badge'] as String?,
      categoryName: cat?['name'] as String?,
      categorySlug: cat?['slug'] as String?,
      studioStyleId: _intOrNull(j['studio_style_id']),
      sceneTypeId: _intOrNull(j['scene_type_id']),
      lightingPresetId: _intOrNull(j['lighting_preset_id']),
      defaultPrompt: j['default_prompt'] as String?,
      qualityKey: j['quality_key'] as String?,
      isPro: j['is_pro'] == true,
      likesCount: _int(j['likes_count']),
      liked: j['liked'] == true,
    );
  }

  TemplateItem copyWith({bool? liked, int? likesCount}) => TemplateItem(
        id: id,
        title: title,
        subtitle: subtitle,
        coverUrl: coverUrl,
        badge: badge,
        categoryName: categoryName,
        categorySlug: categorySlug,
        studioStyleId: studioStyleId,
        sceneTypeId: sceneTypeId,
        lightingPresetId: lightingPresetId,
        defaultPrompt: defaultPrompt,
        qualityKey: qualityKey,
        isPro: isPro,
        likesCount: likesCount ?? this.likesCount,
        liked: liked ?? this.liked,
      );
}

enum ProcessStatus {
  pending,
  processing,
  done,
  failed;

  static ProcessStatus parse(Object? v) => ProcessStatus.values.firstWhere((e) => e.name == v, orElse: () => ProcessStatus.pending);

  bool get isWorking => this == pending || this == processing;
}

enum GenerationStatus {
  queued,
  processing,
  completed,
  partial,
  failed;

  static GenerationStatus parse(Object? v) => GenerationStatus.values.firstWhere((e) => e.name == v, orElse: () => GenerationStatus.queued);

  bool get isFinished => this == completed || this == partial || this == failed;

  String get label => switch (this) {
        queued => 'Kuyrukta',
        processing => 'İşleniyor',
        completed => 'Tamamlandı',
        partial => 'Kısmi',
        failed => 'Başarısız',
      };
}

class Project {
  Project({
    required this.id,
    this.title,
    this.originalUrl,
    this.originalWidth,
    this.originalHeight,
    this.cutoutUrl,
    required this.cutoutStatus,
    this.cutoutError,
    this.sceneTypeId,
    required this.shadowEnabled,
    this.templateId,
    this.template,
    this.latestGenerationId,
    this.latestGenerationStatus,
    this.latestThumbUrl,
    this.generations = const [],
    this.createdAt,
  });

  final int id;
  final String? title;
  final String? originalUrl;
  final int? originalWidth;
  final int? originalHeight;
  final String? cutoutUrl;
  final ProcessStatus cutoutStatus;
  final String? cutoutError;
  final int? sceneTypeId;
  final bool shadowEnabled;
  final int? templateId;
  final TemplateItem? template;
  final int? latestGenerationId;
  final GenerationStatus? latestGenerationStatus;
  final String? latestThumbUrl;
  final List<Generation> generations;
  final DateTime? createdAt;

  factory Project.fromJson(Map<String, dynamic> j) {
    final latest = j['latest_generation'] is Map ? Map<String, dynamic>.from(j['latest_generation'] as Map) : null;
    return Project(
      id: _int(j['id']),
      title: j['title'] as String?,
      originalUrl: j['original_url'] as String?,
      originalWidth: _intOrNull(j['original_width']),
      originalHeight: _intOrNull(j['original_height']),
      cutoutUrl: j['cutout_url'] as String?,
      cutoutStatus: ProcessStatus.parse(j['cutout_status']),
      cutoutError: j['cutout_error'] as String?,
      sceneTypeId: _intOrNull(j['scene_type_id']),
      shadowEnabled: j['shadow_enabled'] != false,
      templateId: _intOrNull(j['template_id']),
      template: j['template'] is Map ? TemplateItem.fromJson(Map<String, dynamic>.from(j['template'] as Map)) : null,
      latestGenerationId: _intOrNull(latest?['id']),
      latestGenerationStatus: latest == null ? null : GenerationStatus.parse(latest['status']),
      latestThumbUrl: latest?['master_thumb_url'] as String?,
      generations: _list(j['generations'], Generation.fromJson),
      createdAt: _date(j['created_at']),
    );
  }
}

class GenerationImage {
  GenerationImage({required this.id, required this.variantIndex, required this.status, required this.isMaster, required this.isFavorite, this.url, this.thumbUrl, this.width, this.height, this.aspectRatio, this.sourceImageId, this.editTool, this.editLabel});

  final int id;
  final int variantIndex;
  final ProcessStatus status;
  final bool isMaster;
  final bool isFavorite;
  final String? url;
  final String? thumbUrl;
  final int? width;
  final int? height;
  final String? aspectRatio;
  final int? sourceImageId;
  final String? editTool;
  final String? editLabel;

  bool get isReady => status == ProcessStatus.done && url != null;

  factory GenerationImage.fromJson(Map<String, dynamic> j) => GenerationImage(
        id: _int(j['id']),
        variantIndex: _int(j['variant_index'], 1),
        status: ProcessStatus.parse(j['status']),
        isMaster: j['is_master'] == true,
        isFavorite: j['is_favorite'] == true,
        url: j['url'] as String?,
        thumbUrl: j['thumb_url'] as String?,
        width: _intOrNull(j['width']),
        height: _intOrNull(j['height']),
        aspectRatio: j['aspect_ratio'] as String?,
        sourceImageId: _intOrNull(j['source_image_id']),
        editTool: j['edit_tool'] as String?,
        editLabel: j['edit_label'] as String?,
      );

  GenerationImage copyWith({bool? isFavorite, bool? isMaster}) => GenerationImage(
        id: id,
        variantIndex: variantIndex,
        status: status,
        isMaster: isMaster ?? this.isMaster,
        isFavorite: isFavorite ?? this.isFavorite,
        url: url,
        thumbUrl: thumbUrl,
        width: width,
        height: height,
        aspectRatio: aspectRatio,
        sourceImageId: sourceImageId,
        editTool: editTool,
        editLabel: editLabel,
      );
}

class Generation {
  Generation({
    required this.id,
    required this.projectId,
    required this.status,
    required this.variantCount,
    this.userPrompt,
    required this.aspectRatio,
    required this.shadowEnabled,
    this.studioStyleId,
    this.lightingPresetId,
    this.sceneTypeId,
    this.qualityName,
    this.qualityKey,
    required this.modelLabel,
    required this.creditsCharged,
    this.errorMessage,
    this.projectTitle,
    this.originalUrl,
    this.cutoutUrl,
    this.images = const [],
  });

  final int id;
  final int projectId;
  final GenerationStatus status;
  final int variantCount;
  final String? userPrompt;
  final String aspectRatio;
  final bool shadowEnabled;
  final int? studioStyleId;
  final int? lightingPresetId;
  final int? sceneTypeId;
  final String? qualityName;
  final String? qualityKey;
  final String modelLabel;
  final int creditsCharged;
  final String? errorMessage;
  final String? projectTitle;
  final String? originalUrl;
  final String? cutoutUrl;
  final List<GenerationImage> images;

  int get readyCount => images.where((i) => i.status == ProcessStatus.done).length;

  factory Generation.fromJson(Map<String, dynamic> j) {
    final project = j['project'] is Map ? Map<String, dynamic>.from(j['project'] as Map) : null;
    final quality = j['quality'] is Map ? Map<String, dynamic>.from(j['quality'] as Map) : null;
    return Generation(
      id: _int(j['id']),
      projectId: _int(j['project_id']),
      status: GenerationStatus.parse(j['status']),
      variantCount: _int(j['variant_count'], 1),
      userPrompt: j['user_prompt'] as String?,
      aspectRatio: j['aspect_ratio'] as String? ?? '4:5',
      shadowEnabled: j['shadow_enabled'] != false,
      studioStyleId: _intOrNull(j['studio_style_id']),
      lightingPresetId: _intOrNull(j['lighting_preset_id']),
      sceneTypeId: _intOrNull(j['scene_type_id']),
      qualityName: quality?['name'] as String?,
      qualityKey: quality?['key'] as String?,
      modelLabel: j['model_label'] as String? ?? 'Studio Diffusion XL',
      creditsCharged: _int(j['credits_charged']),
      errorMessage: j['error_message'] as String?,
      projectTitle: project?['title'] as String?,
      originalUrl: project?['original_url'] as String?,
      cutoutUrl: project?['cutout_url'] as String?,
      images: _list(j['images'], GenerationImage.fromJson),
    );
  }
}

class CreditTransaction {
  CreditTransaction({required this.id, required this.typeLabel, required this.amount, required this.balanceAfter, this.description, this.createdAt});

  final int id;
  final String typeLabel;
  final int amount;
  final int balanceAfter;
  final String? description;
  final DateTime? createdAt;

  factory CreditTransaction.fromJson(Map<String, dynamic> j) => CreditTransaction(
        id: _int(j['id']),
        typeLabel: j['type_label'] as String? ?? '',
        amount: _int(j['amount']),
        balanceAfter: _int(j['balance_after']),
        description: j['description'] as String?,
        createdAt: _date(j['created_at']),
      );
}
