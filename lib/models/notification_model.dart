// ============================================
// Notification Model
// ============================================
class NotificationModel {
  final int id;
  final int userId;
  final String title;
  final String message;
  final String type;
  final Map<String, dynamic>? data;
  final bool isRead;
  final DateTime? readAt;
  final DateTime createdAt;

  NotificationModel({
    required this.id,
    required this.userId,
    required this.title,
    required this.message,
    required this.type,
    this.data,
    required this.isRead,
    this.readAt,
    required this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'],
      userId: json['user_id'],
      title: json['title'],
      message: json['message'],
      type: json['type'],
      data: json['data'],
      isRead: json['is_read'] ?? false,
      readAt: json['read_at'] != null ? DateTime.parse(json['read_at']) : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'title': title,
      'message': message,
      'type': type,
      'data': data,
      'is_read': isRead,
      'read_at': readAt?.toIso8601String(),
      'created_at': createdAt.toIso8601String(),
    };
  }

  bool get isScheduleType => type == 'schedule';
  bool get isAttendanceType => type == 'attendance';
  bool get isApprovalType => type == 'approval';
  bool get isReminderType => type == 'reminder';
  bool get isSystemType => type == 'system';

  String get typeLabel {
    switch (type) {
      case 'schedule':
        return 'Jadwal';
      case 'attendance':
        return 'Kehadiran';
      case 'approval':
        return 'Persetujuan';
      case 'reminder':
        return 'Pengingat';
      case 'system':
        return 'Sistem';
      default:
        return type;
    }
  }

  NotificationModel copyWith({bool? isRead, DateTime? readAt}) {
    return NotificationModel(
      id: id,
      userId: userId,
      title: title,
      message: message,
      type: type,
      data: data,
      isRead: isRead ?? this.isRead,
      readAt: readAt ?? this.readAt,
      createdAt: createdAt,
    );
  }
}

// ============================================
// Attendance Statistics Model
// ============================================
class AttendanceStatistics {
  final int totalDays;
  final int present;
  final int late;
  final int absent;
  final int excused;
  final int leave;
  final double attendanceRate;
  final int averageWorkDuration;

  AttendanceStatistics({
    required this.totalDays,
    required this.present,
    required this.late,
    required this.absent,
    required this.excused,
    required this.leave,
    required this.attendanceRate,
    required this.averageWorkDuration,
  });

  factory AttendanceStatistics.fromJson(Map<String, dynamic> json) {
    return AttendanceStatistics(
      totalDays: json['total_days'] ?? 0,
      present: json['present'] ?? 0,
      late: json['late'] ?? 0,
      absent: json['absent'] ?? 0,
      excused: json['excused'] ?? 0,
      leave: json['leave'] ?? 0,
      attendanceRate: (json['attendance_rate'] ?? 0).toDouble(),
      averageWorkDuration: json['average_work_duration'] ?? 0,
    );
  }

  String get averageWorkDurationFormatted {
    final hours = averageWorkDuration ~/ 60;
    final minutes = averageWorkDuration % 60;
    return '$hours jam $minutes menit';
  }
}
