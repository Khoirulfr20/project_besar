import 'package:logger/logger.dart';

class AppLogger {
  static final Logger _logger = Logger(
    printer: PrettyPrinter(
      methodCount: 0,
      errorMethodCount: 5,
      lineLength: 80,
      colors: true,
      printEmojis: true,
      dateTimeFormat: DateTimeFormat.onlyTimeAndSinceStart,
    ),
  );

  // Debug (for development)
  static void debug(String message) {
    _logger.d(message);
  }

  // Info (general info)
  static void info(String message) {
    _logger.i(message);
  }

  // Warning
  static void warning(String message) {
    _logger.w(message);
  }

  // Error
  static void error(String message, [dynamic error, StackTrace? stackTrace]) {
    _logger.e(message, error: error, stackTrace: stackTrace);
  }

  // Success (custom)
  static void success(String message) {
    _logger.i('✅ $message');
  }
}
