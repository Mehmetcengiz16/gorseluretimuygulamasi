import 'package:flutter/widgets.dart';
import 'package:material_symbols_icons/symbols.dart';

/// 2400 → "2.4k", 940 → "940" (tasarımdaki beğeni sayıları).
String compactCount(int n) {
  if (n < 1000) return '$n';
  final v = n / 1000;
  return '${v.toStringAsFixed(v >= 10 ? 0 : 1).replaceAll('.0', '')}k';
}

/// "4:5" → "4:5 Portre"
String ratioLabel(String ratio) => switch (ratio) {
      '1:1' => '1:1 Kare',
      '16:9' => '16:9 Yatay',
      '9:16' => '9:16 Hikâye',
      _ => '$ratio Portre',
    };

String formatDate(DateTime? d) {
  if (d == null) return '';
  const months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
  return '${d.day} ${months[d.month - 1]} ${d.year}';
}

/// Admin panelden gelen Material Symbols ikon adlarını IconData'ya çevirir.
IconData symbolFor(String? name) => switch (name) {
      'stars' => Symbols.stars,
      'spa' => Symbols.spa,
      'fragrance' => Symbols.fragrance,
      'apparel' => Symbols.apparel,
      'devices' => Symbols.devices,
      'diamond' => Symbols.diamond,
      'eco' => Symbols.eco,
      'face' => Symbols.face,
      'local_drink' => Symbols.local_drink,
      'watch' => Symbols.watch,
      'checkroom' => Symbols.checkroom,
      'restaurant' => Symbols.restaurant,
      'chair' => Symbols.chair,
      'toys' => Symbols.toys,
      'pets' => Symbols.pets,
      'sports_esports' => Symbols.sports_esports,
      'wb_twilight' => Symbols.wb_twilight,
      'highlight' => Symbols.highlight,
      'wb_sunny' => Symbols.wb_sunny,
      'flare' => Symbols.flare,
      'light_mode' => Symbols.light_mode,
      'wb_incandescent' => Symbols.wb_incandescent,
      'backlight_high' => Symbols.backlight_high,
      'nightlight' => Symbols.nightlight,
      _ => Symbols.category,
    };
