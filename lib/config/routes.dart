import 'package:flutter/material.dart';
import '../screens/auth/splash_screen.dart';
import '../screens/auth/login_screen.dart';
import '../screens/anggota/anggota_dashboard.dart';
import '../screens/anggota/schedule_view_screen.dart';
import '../screens/anggota/history_screen.dart' as anggota;
import '../screens/pimpinan/pimpinan_dashboard.dart';
import '../screens/pimpinan/history_screen.dart' as pimpinan;
import '../screens/common/notifications_screen.dart';
import '../screens/face/face_capture_screen.dart';
import '../screens/face/face_registration_screen.dart';

class Routes {
  static const String splash = '/';
  static const String login = '/login';
  static const String anggotaDashboard = '/anggota-dashboard';
  static const String pimpinanDashboard = '/pimpinan-dashboard';
  static const String notifications = '/notifications';
  static const String scheduleView = '/schedule-view';
  static const String anggotaHistory = '/anggota-history';
  static const String attendanceReport = '/attendance-report';
  static const String exportReport = '/export-report';
  static const String pimpinanHistory = '/pimpinan-history';
  static const String faceCapture = '/face-capture';
  static const String faceRegistration = '/face-registration';
}

class AppRoutes {
  static Route<dynamic> generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case Routes.splash:
        return MaterialPageRoute(builder: (_) => const SplashScreen());

      case Routes.login:
        return MaterialPageRoute(builder: (_) => const LoginScreen());

      case Routes.anggotaDashboard:
        return MaterialPageRoute(builder: (_) => const AnggotaDashboard());

      case Routes.pimpinanDashboard:
        return MaterialPageRoute(builder: (_) => const PimpinanDashboard());

      case Routes.notifications:
        return MaterialPageRoute(builder: (_) => const NotificationsScreen());

      case Routes.scheduleView:
        return MaterialPageRoute(builder: (_) => const ScheduleViewScreen());

      case Routes.anggotaHistory:
        return MaterialPageRoute(builder: (_) => const anggota.HistoryScreen());

      case Routes.pimpinanHistory:
        return MaterialPageRoute(
          builder: (_) => const pimpinan.HistoryScreen(),
        );

      case Routes.faceCapture:
        final args = settings.arguments as Map<String, dynamic>?;
        return MaterialPageRoute(
          builder: (_) =>
              FaceCaptureScreen(isCheckOut: args?['isCheckOut'] ?? false),
        );

      case Routes.faceRegistration:
        final args = settings.arguments as Map<String, dynamic>;
        return MaterialPageRoute(
          builder: (_) => FaceRegistrationScreen(userId: args['userId']),
        );

      default:
        return MaterialPageRoute(
          builder: (_) => Scaffold(
            body: Center(child: Text('Route ${settings.name} tidak ditemukan')),
          ),
        );
    }
  }
}
