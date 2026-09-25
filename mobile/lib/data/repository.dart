import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/network/api_client.dart';
import 'models.dart';

Map<String, dynamic> _data(Map<String, dynamic> res) => Map<String, dynamic>.from(res['data'] as Map);

List<T> _items<T>(Map<String, dynamic> res, T Function(Map<String, dynamic>) f) =>
    (res['data'] as List? ?? const []).whereType<Map>().map((e) => f(Map<String, dynamic>.from(e))).toList();

/// Tüm REST uç noktaları tek yerde (bkz. PROJE_DOKUMANI.md §6).
class StudioRepository {
  StudioRepository(this._api);

  final ApiClient _api;

  // ---------- Kimlik ----------
  Future<(String, AppUser)> login(String email, String password) async {
    final res = await _api.post('/auth/login', data: {'email': email, 'password': password, 'device_name': 'mobile'});
    return (res['token'] as String, AppUser.fromJson(Map<String, dynamic>.from(res['user'] as Map)));
  }

  Future<(String, AppUser)> register(String name, String email, String password) async {
    final res = await _api.post('/auth/register', data: {'name': name, 'email': email, 'password': password, 'device_name': 'mobile'});
    return (res['token'] as String, AppUser.fromJson(Map<String, dynamic>.from(res['user'] as Map)));
  }

  Future<void> forgotPassword(String email) => _api.post('/auth/forgot-password', data: {'email': email});

  Future<void> logout() => _api.post('/auth/logout');

  Future<AppUser> me() async => AppUser.fromJson(_data(await _api.get('/me')));

  Future<List<CreditTransaction>> creditTransactions() async => _items(await _api.get('/me/credit-transactions'), CreditTransaction.fromJson);

  // ---------- Katalog ----------
  Future<AppConfig> config() async => AppConfig.fromJson(_data(await _api.get('/app/config')));

  Future<Catalog> catalog() async => Catalog.fromJson(_data(await _api.get('/catalog')));

  Future<List<TemplateItem>> templates({String? category, String? search}) async => _items(
        await _api.get('/templates', query: {
          'category': ?category,
          if (search != null && search.isNotEmpty) 'search': search,
        }),
        TemplateItem.fromJson,
      );

  Future<(bool, int)> toggleLike(int templateId) async {
    final d = _data(await _api.post('/templates/$templateId/like'));
    return (d['liked'] == true, (d['likes_count'] as num).toInt());
  }

  // ---------- Projeler ----------
  Future<List<Project>> projects() async => _items(await _api.get('/projects'), Project.fromJson);

  Future<Project> project(int id) async => Project.fromJson(_data(await _api.get('/projects/$id')));

  Future<Project> createProject(String filePath, {int? templateId, void Function(double)? onProgress}) async => Project.fromJson(_data(
        await _api.upload('/projects', filePath, fields: {'template_id': ?templateId}, onProgress: onProgress),
      ));

  Future<Project> replaceImage(int projectId, String filePath) async => Project.fromJson(_data(await _api.upload('/projects/$projectId/image', filePath)));

  Future<Project> updateProject(int id, {int? sceneTypeId, bool? shadowEnabled, String? title}) async => Project.fromJson(_data(
        await _api.patch('/projects/$id', data: {
          'scene_type_id': ?sceneTypeId,
          'shadow_enabled': ?shadowEnabled,
          'title': ?title,
        }),
      ));

  Future<void> cutout(int projectId, {bool precise = false}) => _api.post('/projects/$projectId/cutout', data: {'precise': precise});

  Future<void> deleteProject(int id) => _api.delete('/projects/$id');

  // ---------- Üretim ----------
  Future<String> enhancePrompt(String prompt, {int? styleId, int? lightingId}) async => _data(await _api.post('/prompt/enhance', data: {
        'prompt': prompt,
        'studio_style_id': ?styleId,
        'lighting_preset_id': ?lightingId,
      }))['prompt'] as String;

  Future<(Generation, int?)> startGeneration(int projectId, Map<String, dynamic> body) async {
    final res = await _api.post('/projects/$projectId/generations', data: body);
    final meta = res['meta'] is Map ? res['meta'] as Map : null;
    return (Generation.fromJson(_data(res)), (meta?['credit_balance'] as num?)?.toInt());
  }

  Future<Generation> generation(int id) async => Generation.fromJson(_data(await _api.get('/generations/$id')));

  Future<(Generation, int?)> moreVariants(int generationId, int count) async {
    final res = await _api.post('/generations/$generationId/more', data: {'count': count});
    final meta = res['meta'] is Map ? res['meta'] as Map : null;
    return (Generation.fromJson(_data(res)), (meta?['credit_balance'] as num?)?.toInt());
  }

  Future<GenerationImage> updateImage(int imageId, {bool? favorite, bool? master}) async => GenerationImage.fromJson(_data(
        await _api.patch('/generation-images/$imageId', data: {
          'is_favorite': ?favorite,
          if (master == true) 'is_master': true,
        }),
      ));

  /// Sonuç ekranı aracı: seçili varyanttan düzenlenmiş yeni varyant üretir.
  Future<(Generation, int?)> editImage(int imageId, String tool, String option, {String? note}) async {
    final res = await _api.post('/generation-images/$imageId/edit', data: {'tool': tool, 'option': option, 'note': ?note});
    final meta = res['meta'] is Map ? res['meta'] as Map : null;
    return (Generation.fromJson(_data(res)), (meta?['credit_balance'] as num?)?.toInt());
  }

  Future<List<int>> downloadImage(String url) => _api.downloadBytes(url);
}

final repositoryProvider = Provider<StudioRepository>((ref) => StudioRepository(ref.watch(apiClientProvider)));
