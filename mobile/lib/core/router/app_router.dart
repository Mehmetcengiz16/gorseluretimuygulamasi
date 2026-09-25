import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/auth_screens.dart';
import '../../features/auth/session_controller.dart';
import '../../features/discover/discover_screen.dart';
import '../../features/profile/profile_screen.dart';
import '../../features/projects/projects_screen.dart';
import '../../features/results/results_screen.dart';
import '../../features/studio/cutout_screen.dart';
import '../../features/studio/studio_settings_screen.dart';
import '../widgets/app_bars.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final session = ref.read(sessionProvider.notifier);

  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: session.refresh,
    redirect: (context, state) {
      final status = ref.read(sessionProvider).status;
      final loc = state.matchedLocation;
      final onAuth = loc == '/login' || loc == '/register';

      if (status == SessionStatus.unknown) return loc == '/splash' ? null : '/splash';
      if (status == SessionStatus.signedOut) return onAuth ? null : '/login';
      if (onAuth || loc == '/splash') return '/discover';
      return null;
    },
    routes: [
      GoRoute(path: '/splash', builder: (_, _) => const SplashScreen()),
      GoRoute(path: '/login', builder: (_, _) => const AuthScreen()),
      GoRoute(path: '/register', builder: (_, _) => const AuthScreen(register: true)),

      // Alt navigasyonlu sekmeler (tasarımdaki "mobile_tab" kabuğu)
      StatefulShellRoute.indexedStack(
        builder: (context, state, shell) => _TabShell(shell: shell),
        branches: [
          StatefulShellBranch(routes: [GoRoute(path: '/discover', builder: (_, _) => const DiscoverScreen())]),
          StatefulShellBranch(routes: [GoRoute(path: '/studio', builder: (_, _) => const StudioSettingsScreen())]),
          StatefulShellBranch(routes: [GoRoute(path: '/projects', builder: (_, _) => const ProjectsScreen())]),
          StatefulShellBranch(routes: [GoRoute(path: '/profile', builder: (_, _) => const ProfileScreen())]),
        ],
      ),

      // Editör akışı (tasarımdaki "mobile_stack" kabuğu — alt navigasyon yok)
      GoRoute(
        path: '/editor/:projectId',
        builder: (_, state) => CutoutScreen(projectId: int.parse(state.pathParameters['projectId']!)),
      ),
      GoRoute(
        path: '/results/:generationId',
        builder: (_, state) => ResultsScreen(generationId: int.parse(state.pathParameters['generationId']!)),
      ),
    ],
  );
});

class _TabShell extends StatelessWidget {
  const _TabShell({required this.shell});

  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBody: true,
      body: shell,
      bottomNavigationBar: GlassBottomNav(
        index: shell.currentIndex,
        onTap: (i) => shell.goBranch(i, initialLocation: i == shell.currentIndex),
      ),
    );
  }
}
