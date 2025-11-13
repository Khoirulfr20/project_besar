import 'package:flutter/material.dart';

// App Constants
class AppConstants {
  // App Info
  static const String appName = 'Smart Attendance';
  static const String appVersion = '1.0.0';

  // API
  static const String apiBaseUrl = 'http://192.168.1.100:8000/api';
  static const int apiTimeout = 30;

  // Pagination
  static const int itemsPerPage = 10;

  // Face Recognition
  static const double faceConfidenceThreshold = 0.75;
  static const int minFaceSamples = 3;
  static const int maxImageSize = 5 * 1024 * 1024; // 5MB

  // Time Format
  static const String dateFormat = 'yyyy-MM-dd';
  static const String timeFormat = 'HH:mm';
  static const String dateTimeFormat = 'yyyy-MM-dd HH:mm:ss';
  static const String displayDateFormat = 'dd/MM/yyyy';
  static const String displayDateFormatLong = 'dd MMMM yyyy';
}

// User Roles
enum UserRole {
  admin,
  pimpinan,
  anggota;

  String get value => name;
}

// Attendance Status
enum AttendanceStatus {
  present('Hadir', Colors.green),
  late('Terlambat', Colors.orange),
  absent('Tidak Hadir', Colors.red),
  excused('Izin', Colors.blue),
  leave('Cuti', Colors.purple);

  final String label;
  final Color color;

  const AttendanceStatus(this.label, this.color);
}

// Schedule Status
enum ScheduleStatus {
  scheduled('Terjadwal', Colors.blue),
  ongoing('Berlangsung', Colors.green),
  completed('Selesai', Colors.grey),
  cancelled('Dibatalkan', Colors.red);

  final String label;
  final Color color;

  const ScheduleStatus(this.label, this.color);
}

// Schedule Type
enum ScheduleType {
  meeting('Meeting'),
  training('Training'),
  event('Event'),
  other('Lainnya');

  final String label;

  const ScheduleType(this.label);
}

// Report Type
enum ReportType {
  daily('Harian'),
  weekly('Mingguan'),
  monthly('Bulanan'),
  custom('Custom');

  final String label;

  const ReportType(this.label);
}

// Report Format
enum ReportFormat {
  pdf('PDF', Icons.picture_as_pdf, Colors.red),
  excel('Excel', Icons.table_chart, Colors.green),
  csv('CSV', Icons.description, Colors.blue);

  final String label;
  final IconData icon;
  final Color color;

  const ReportFormat(this.label, this.icon, this.color);
}

// Storage Keys
class StorageKeys {
  static const String token = 'token';
  static const String user = 'user';
  static const String faceData = 'face_data';
  static const String settings = 'settings';
}

// Routes
class Routes {
  static const String splash = '/';
  static const String login = '/login';
  static const String anggotaDashboard = '/anggota-dashboard';
  static const String pimpinanDashboard = '/pimpinan-dashboard';
  static const String adminDashboard = '/admin-dashboard';
  static const String notifications = '/notifications';
  static const String scheduleView = '/schedule-view';
  static const String scheduleCreate = '/schedule-create';
  static const String history = '/history';
  static const String faceCapture = '/face-capture';
  static const String faceRegistration = '/face-registration';
}
