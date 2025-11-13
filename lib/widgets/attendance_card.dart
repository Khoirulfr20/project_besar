import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../models/attendance_model.dart';

class AttendanceCard extends StatelessWidget {
  final Attendance attendance;
  final VoidCallback? onTap;
  final bool showUser;

  const AttendanceCard({
    super.key,
    required this.attendance,
    this.onTap,
    this.showUser = false,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Row(
                children: [
                  if (showUser && attendance.user != null) ...[
                    CircleAvatar(
                      radius: 20,
                      backgroundImage: attendance.user!.photo != null
                          ? NetworkImage(attendance.user!.photo!)
                          : null,
                      child: attendance.user!.photo == null
                          ? Text(attendance.user!.name[0])
                          : null,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        attendance.user!.name,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ] else
                    Expanded(
                      child: Text(
                        DateFormat(
                          'EEEE, dd MMM yyyy',
                          'id_ID',
                        ).format(attendance.date),
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  _buildStatusBadge(),
                ],
              ),
              const SizedBox(height: 12),

              // Check-in & Check-out
              Row(
                children: [
                  Expanded(
                    child: _buildTimeInfo(
                      'Check-in',
                      attendance.checkInTime ?? '-',
                      Icons.login,
                      Colors.green,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: _buildTimeInfo(
                      'Check-out',
                      attendance.checkOutTime ?? '-',
                      Icons.logout,
                      Colors.orange,
                    ),
                  ),
                ],
              ),

              // Work duration
              if (attendance.workDuration != null) ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    Icon(Icons.timer, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 8),
                    Text(
                      'Durasi: ${attendance.workDurationFormatted}',
                      style: TextStyle(fontSize: 13, color: Colors.grey[700]),
                    ),
                  ],
                ),
              ],

              // Confidence scores
              if (attendance.checkInConfidence != null ||
                  attendance.checkOutConfidence != null) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    if (attendance.checkInConfidence != null)
                      _buildConfidenceBadge(
                        'In',
                        attendance.checkInConfidence!,
                      ),
                    if (attendance.checkOutConfidence != null) ...[
                      const SizedBox(width: 8),
                      _buildConfidenceBadge(
                        'Out',
                        attendance.checkOutConfidence!,
                      ),
                    ],
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTimeInfo(String label, String time, IconData icon, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 16, color: color),
            const SizedBox(width: 4),
            Text(
              label,
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          time,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }

  Widget _buildStatusBadge() {
    Color color;
    String label;

    switch (attendance.status) {
      case 'present':
        color = Colors.green;
        label = 'Hadir';
        break;
      case 'late':
        color = Colors.orange;
        label = 'Terlambat';
        break;
      case 'absent':
        color = Colors.red;
        label = 'Tidak Hadir';
        break;
      case 'excused':
        color = Colors.blue;
        label = 'Izin';
        break;
      case 'leave':
        color = Colors.purple;
        label = 'Cuti';
        break;
      default:
        color = Colors.grey;
        label = attendance.status;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  Widget _buildConfidenceBadge(String label, double confidence) {
    final percent = (confidence * 100).toStringAsFixed(0);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text('$label: $percent%', style: const TextStyle(fontSize: 10)),
    );
  }
}
