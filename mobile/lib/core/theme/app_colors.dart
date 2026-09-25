import 'package:flutter/material.dart';

/// Obsidian Amber renk token'ları — ekrantasarimlari/*/code.html içindeki Tailwind yapılandırmasıyla birebir.
abstract final class AppColors {
  static const surface = Color(0xFF131316);
  static const surfaceDim = Color(0xFF131316);
  static const surfaceBright = Color(0xFF39393C);
  static const surfaceContainerLowest = Color(0xFF0E0E11);
  static const surfaceContainerLow = Color(0xFF1B1B1E);
  static const surfaceContainer = Color(0xFF1F1F22);
  static const surfaceContainerHigh = Color(0xFF2A2A2D);
  static const surfaceContainerHighest = Color(0xFF353438);
  static const surfaceVariant = Color(0xFF353438);

  static const onSurface = Color(0xFFE4E1E6);
  static const onSurfaceVariant = Color(0xFFD5C4AB);
  static const outline = Color(0xFF9E8F78);
  static const outlineVariant = Color(0xFF514532);

  static const primary = Color(0xFFFFDCA1);
  static const onPrimary = Color(0xFF412D00);
  static const primaryContainer = Color(0xFFFFB800);
  static const onPrimaryContainer = Color(0xFF6B4C00);
  static const primaryFixedDim = Color(0xFFFFBA20);

  static const secondary = Color(0xFFFFB955);
  static const onSecondary = Color(0xFF452B00);
  static const secondaryContainer = Color(0xFFDC9100);
  static const onSecondaryContainer = Color(0xFF4F3100);
  static const secondaryFixed = Color(0xFFFFDDB4);

  static const tertiary = Color(0xFFFFDAB6);
  static const tertiaryContainer = Color(0xFFFFB662);

  static const error = Color(0xFFFFB4AB);
  static const onError = Color(0xFF690005);
  static const errorContainer = Color(0xFF93000A);

  /// Amber ışıma — birincil CTA ve seçili durumlar.
  static List<BoxShadow> amberGlow([double opacity = 0.32, double blur = 24, double dy = 8]) => [
        BoxShadow(color: primaryContainer.withValues(alpha: opacity), blurRadius: blur, offset: Offset(0, dy)),
      ];

  static const deepShadow = [BoxShadow(color: Color(0x80000000), blurRadius: 32, offset: Offset(0, 12))];
  static const cardShadow = [BoxShadow(color: Color(0x40000000), blurRadius: 15, offset: Offset(0, 10))];
}

/// Tailwind boşluk ölçeği (tasarımdaki `space-*`, `margin`, `gutter`).
abstract final class Space {
  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 12.0;
  static const lg = 20.0;
  static const xl = 28.0;
  static const margin = 16.0;
  static const gutter = 12.0;
}

/// Tasarımdaki Tailwind yarıçapları: DEFAULT 4, lg 8, xl 12, 2xl 16.
abstract final class Radii {
  static const sm = 4.0;
  static const lg = 8.0;
  static const xl = 12.0;
  static const xxl = 16.0;
  static const full = 999.0;
}
