import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';
import 'app_text.dart';

abstract final class AppTheme {
  static ThemeData dark() {
    const scheme = ColorScheme.dark(
      surface: AppColors.surface,
      surfaceDim: AppColors.surfaceDim,
      surfaceBright: AppColors.surfaceBright,
      surfaceContainerLowest: AppColors.surfaceContainerLowest,
      surfaceContainerLow: AppColors.surfaceContainerLow,
      surfaceContainer: AppColors.surfaceContainer,
      surfaceContainerHigh: AppColors.surfaceContainerHigh,
      surfaceContainerHighest: AppColors.surfaceContainerHighest,
      onSurface: AppColors.onSurface,
      onSurfaceVariant: AppColors.onSurfaceVariant,
      outline: AppColors.outline,
      outlineVariant: AppColors.outlineVariant,
      primary: AppColors.primaryContainer,
      onPrimary: AppColors.onPrimaryContainer,
      primaryContainer: AppColors.primaryContainer,
      onPrimaryContainer: AppColors.onPrimaryContainer,
      secondary: AppColors.secondary,
      onSecondary: AppColors.onSecondary,
      secondaryContainer: AppColors.secondaryContainer,
      onSecondaryContainer: AppColors.onSecondaryContainer,
      tertiary: AppColors.tertiary,
      error: AppColors.error,
      onError: AppColors.onError,
      errorContainer: AppColors.errorContainer,
    );

    final base = ThemeData(useMaterial3: true, colorScheme: scheme, brightness: Brightness.dark);

    return base.copyWith(
      scaffoldBackgroundColor: AppColors.surface,
      textTheme: GoogleFonts.plusJakartaSansTextTheme(base.textTheme).apply(
        bodyColor: AppColors.onSurface,
        displayColor: AppColors.onSurface,
      ),
      splashFactory: InkSparkle.splashFactory,
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        elevation: 0,
        systemOverlayStyle: SystemUiOverlayStyle.light,
      ),
      textSelectionTheme: TextSelectionThemeData(
        cursorColor: AppColors.primaryContainer,
        selectionColor: AppColors.primaryContainer.withValues(alpha: 0.3),
        selectionHandleColor: AppColors.primaryContainer,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surfaceContainer,
        hintStyle: AppText.bodyMd.c(AppColors.onSurfaceVariant.withValues(alpha: 0.6)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(Radii.xl), borderSide: const BorderSide(color: Color(0x14FFFFFF))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(Radii.xl), borderSide: const BorderSide(color: Color(0x14FFFFFF))),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(Radii.xl), borderSide: const BorderSide(color: AppColors.primaryContainer)),
        errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(Radii.xl), borderSide: const BorderSide(color: AppColors.error)),
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(color: AppColors.primaryContainer),
      bottomSheetTheme: const BottomSheetThemeData(backgroundColor: AppColors.surfaceContainerLow, surfaceTintColor: Colors.transparent),
      dialogTheme: const DialogThemeData(backgroundColor: AppColors.surfaceContainerLow, surfaceTintColor: Colors.transparent),
      pageTransitionsTheme: const PageTransitionsTheme(builders: {
        TargetPlatform.android: CupertinoPageTransitionsBuilder(),
        TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
      }),
    );
  }
}
