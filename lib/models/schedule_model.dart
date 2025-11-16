import 'package:smart_attendance/models/user_model.dart';

class Schedule {
  final int id;
  final String title;
  final String? description;
  final DateTime date;
  final String startTime;
  final String endTime;
  final String? location;
  final String type;
  final String status;
  final int createdBy;
  final bool isActive;
  final User? creator;
  final List<User>? participants;
  final DateTime createdAt;
  final DateTime updatedAt;

  Schedule({
    required this.id,
    required this.title,
    this.description,
    required this.date,
    required this.startTime,
    required this.endTime,
    this.location,
    required this.type,
    required this.status,
    required this.createdBy,
    required this.isActive,
    this.creator,
    this.participants,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Schedule.fromJson(Map<String, dynamic> json) {
    return Schedule(
      id: json['id'],
      title: json['title'],
      description: json['description'],
      date: DateTime.parse(json['date']),
      startTime: json['start_time'],
      endTime: json['end_time'],
      location: json['location'],
      type: json['type'],
      status: json['status'],
      createdBy: json['created_by'],
      isActive: json['is_active'] ?? true,
      creator: json['creator'] != null ? User.fromJson(json['creator']) : null,
      participants: json['participants'] != null
          ? (json['participants'] as List).map((e) => User.fromJson(e)).toList()
          : null,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'date': date.toIso8601String().split('T')[0],
      'start_time': startTime,
      'end_time': endTime,
      'location': location,
      'type': type,
      'status': status,
      'created_by': createdBy,
      'is_active': isActive,
    };
  }

  bool get isScheduled => status == 'scheduled';
  bool get isOngoing => status == 'ongoing';
  bool get isCompleted => status == 'completed';
  bool get isCancelled => status == 'cancelled';

  bool get isPast {
    final now = DateTime.now();
    final scheduleDateTime = DateTime(
      date.year,
      date.month,
      date.day,
      int.parse(endTime.split(':')[0]),
      int.parse(endTime.split(':')[1]),
    );
    return now.isAfter(scheduleDateTime);
  }

  bool get isToday {
    final now = DateTime.now();
    return date.year == now.year &&
        date.month == now.month &&
        date.day == now.day;
  }

  String get typeLabel {
    switch (type) {
      case 'meeting':
        return 'Meeting';
      case 'training':
        return 'Training';
      case 'event':
        return 'Event';
      default:
        return 'Other';
    }
  }

  String get statusLabel {
    switch (status) {
      case 'scheduled':
        return 'Terjadwal';
      case 'ongoing':
        return 'Berlangsung';
      case 'completed':
        return 'Selesai';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return status;
    }
  }

  getDurationInMinutes() {}
}
