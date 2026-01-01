class AppConfig {
  // API Configuration
  static const String baseUrl = 
  //'http://10.0.2.2:8000/api';
  'http://10.32.249.186:8000/api';
  //'http://192.168.202.209:8000/api'; // Ganti dengan IP server Laravel Anda

  // App Information
  static const String appName = 'Smart Attendance';
  static const String appVersion = '1.0.0';

  // Face Recognition SettingsR
  static const double faceConfidenceThreshold = 0.75;
  static const int minFaceSamples = 3;

  // Date Format
  static const String dateFormat = 'yyyy-MM-dd';
  static const String timeFormat = 'HH:mm';
  static const String dateTimeFormat = 'yyyy-MM-dd HH:mm:ss';
  static const String displayDateFormat = 'dd/MM/yyyy';
  static const String displayTimeFormat = 'HH:mm';

  // Pagination
  static const int itemsPerPage = 10;

  // Image Settings
  static const int maxImageSize = 5 * 1024 * 1024; // 5MB
  static const int imageQuality = 85;

  // Role Types
  static const String roleAdmin = 'admin';
  static const String rolePimpinan = 'pimpinan';
  static const String roleAnggota = 'anggota';

  // Attendance Status
  static const String statusPresent = 'present';
  static const String statusLate = 'late';
  static const String statusAbsent = 'absent';
  static const String statusExcused = 'excused';
  static const String statusLeave = 'leave';

  // Schedule Status
  static const String scheduleScheduled = 'scheduled';
  static const String scheduleOngoing = 'ongoing';
  static const String scheduleCompleted = 'completed';
  static const String scheduleCancelled = 'cancelled';

  // Schedule Types
  static const String typeMeeting = 'meeting';
  static const String typeTraining = 'training';
  static const String typeEvent = 'event';
  static const String typeOther = 'other';

  // Report Types
  static const String reportDaily = 'daily';
  static const String reportWeekly = 'weekly';
  static const String reportMonthly = 'monthly';
  static const String reportCustom = 'custom';

  // Report Formats
  static const String formatPdf = 'pdf';
  static const String formatExcel = 'excel';
  static const String formatCsv = 'csv';

  // Storage Keys
  static const String keyToken = 'token';
  static const String keyUser = 'user';
  static const String keyFaceData = 'face_data';
  static const String keySettings = 'settings';
}
