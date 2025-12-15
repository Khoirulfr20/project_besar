import 'package:smart_attendance/models/user_model.dart';
import 'package:smart_attendance/models/schedule_model.dart';

class Attendance {
  final int id;
  final int userId;
  final int? scheduleId;
  final DateTime date;
  final String? checkInTime;
  final String? checkInPhoto;
  final double? checkInConfidence;
  final String? checkInLocation;
  final String? checkInDevice;
  final String? checkOutTime;
  final String? checkOutPhoto;
  final double? checkOutConfidence;
  final String? checkOutLocation;
  final String? checkOutDevice;
  final String status;
  final int? workDuration;
  final String? notes;
  final int? approvedBy;
  final DateTime? approvedAt;
  final User? user;
  final Schedule? schedule;
  final DateTime createdAt;
  final DateTime updatedAt;

  Attendance({
    required this.id,
    required this.userId,
    this.scheduleId,
    required this.date,
    this.checkInTime,
    this.checkInPhoto,
    this.checkInConfidence,
    this.checkInLocation,
    this.checkInDevice,
    this.checkOutTime,
    this.checkOutPhoto,
    this.checkOutConfidence,
    this.checkOutLocation,
    this.checkOutDevice,
    required this.status,
    this.workDuration,
    this.notes,
    this.approvedBy,
    this.approvedAt,
    this.user,
    this.schedule,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Attendance.fromJson(Map<String, dynamic> json) {
    return Attendance(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      userId: json['user_id'] is String
          ? int.parse(json['user_id'])
          : json['user_id'],
      scheduleId: json['schedule_id'] != null
          ? (json['schedule_id'] is String
              ? int.parse(json['schedule_id'])
              : json['schedule_id'])
          : null,
      date: DateTime.parse(json['date']),
      checkInTime: json['check_in_time'],
      checkInPhoto: json['check_in_photo'],
      checkInConfidence: json['check_in_confidence'] != null
          ? (json['check_in_confidence'] is String
              ? double.parse(json['check_in_confidence'])
              : json['check_in_confidence'].toDouble())
          : null,
      checkInLocation: json['check_in_location'],
      checkInDevice: json['check_in_device'],
      checkOutTime: json['check_out_time'],
      checkOutPhoto: json['check_out_photo'],
      checkOutConfidence: json['check_out_confidence'] != null
          ? (json['check_out_confidence'] is String
              ? double.parse(json['check_out_confidence'])
              : json['check_out_confidence'].toDouble())
          : null,
      checkOutLocation: json['check_out_location'],
      checkOutDevice: json['check_out_device'],
      status: json['status'],
      workDuration: json['work_duration'] != null
          ? (json['work_duration'] is String
              ? int.parse(json['work_duration'])
              : json['work_duration'])
          : null,
      notes: json['notes'],
      approvedBy: json['approved_by'] != null
          ? (json['approved_by'] is String
              ? int.parse(json['approved_by'])
              : json['approved_by'])
          : null,
      approvedAt: json['approved_at'] != null
          ? DateTime.parse(json['approved_at'])
          : null,
      user: json['user'] != null ? User.fromJson(json['user']) : null,
      schedule:
          json['schedule'] != null ? Schedule.fromJson(json['schedule']) : null,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'schedule_id': scheduleId,
      'date': date.toIso8601String().split('T')[0],
      'check_in_time': checkInTime,
      'check_in_confidence': checkInConfidence,
      'check_out_time': checkOutTime,
      'check_out_confidence': checkOutConfidence,
      'status': status,
      'work_duration': workDuration,
      'notes': notes,
    };
  }

  bool get hasCheckedIn => checkInTime != null;
  bool get hasCheckedOut => checkOutTime != null;
  bool get isComplete => hasCheckedIn && hasCheckedOut;
  bool get isApproved => approvedBy != null;
  bool get isPresent => status == 'present';
  bool get isLate => status == 'late';
  bool get isAbsent => status == 'absent';
  bool get isExcused => status == 'excused';
  bool get isLeave => status == 'leave';

  String get statusLabel {
    switch (status) {
      case 'present':
        return 'Hadir';
      case 'late':
        return 'Terlambat';
      case 'absent':
        return 'Tidak Hadir';
      case 'excused':
        return 'Izin';
      case 'leave':
        return 'Cuti';
      default:
        return status;
    }
  }

  String get workDurationFormatted {
    if (workDuration == null) return '-';
    final hours = workDuration! ~/ 60;
    final minutes = workDuration! % 60;
    return '$hours jam $minutes menit';
  }
}
