import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';

/// Tasarım sistemi tipografisi — Plus Jakarta Sans. `letterSpacing` = em × fontSize.
abstract final class AppText {
  static TextStyle _base(double size, double height, FontWeight weight, [double em = 0]) => GoogleFonts.plusJakartaSans(
        fontSize: size,
        height: height / size,
        fontWeight: weight,
        letterSpacing: em * size,
        color: AppColors.onSurface,
      );

  static final headlineXl = _base(32, 40, FontWeight.w700, -0.02);
  static final headlineLg = _base(24, 32, FontWeight.w600, -0.015);
  static final headlineMd = _base(20, 28, FontWeight.w600, -0.01);
  static final titleMd = _base(16, 22, FontWeight.w600, -0.005);
  static final bodyLg = _base(16, 24, FontWeight.w400);
  static final bodyMd = _base(14, 20, FontWeight.w400);
  static final bodySm = _base(12, 16, FontWeight.w400);
  static final labelLg = _base(14, 18, FontWeight.w600, 0.01);
  static final labelMd = _base(12, 16, FontWeight.w600, 0.02);
  static final labelSm = _base(10, 14, FontWeight.w700, 0.04);
}

extension TextStyleX on TextStyle {
  TextStyle c(Color color) => copyWith(color: color);
  TextStyle get bold => copyWith(fontWeight: FontWeight.w700);
  TextStyle get semi => copyWith(fontWeight: FontWeight.w600);
  TextStyle get medium => copyWith(fontWeight: FontWeight.w500);

  /// Tailwind `tracking-wider` (0.05em).
  TextStyle get wider => copyWith(letterSpacing: (fontSize ?? 14) * 0.05);

  /// Tailwind `tracking-tight` (-0.025em).
  TextStyle get tight => copyWith(letterSpacing: (fontSize ?? 14) * -0.025);
}

/// Dart'ın toUpperCase'i Türkçe i/ı dönüşümünü yapmaz.
String trUpper(String s) => s.replaceAll('i', 'İ').replaceAll('ı', 'I').toUpperCase();
