import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../models/schedule_model.dart';

class ScheduleCard extends StatelessWidget {
  final Schedule schedule;
  final VoidCallback? onTap;

  const ScheduleCard({
    super.key,
    required this.schedule,
    this.onTap,
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
                  Expanded(
                    child: Text(
                      schedule.title,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  _buildStatusChip(),
                ],
              ),
              const SizedBox(height: 12),

              // Date
              _buildInfoRow(
                Icons.calendar_today,
                DateFormat('EEEE, dd MMM yyyy', 'id_ID').format(schedule.date),
              ),
              const SizedBox(height: 6),

              // Time
              _buildInfoRow(
                Icons.access_time,
                '${schedule.startTime} - ${schedule.endTime}',
              ),

              // Location
              if (schedule.location != null && schedule.location!.isNotEmpty) ...[
                const SizedBox(height: 6),
                _buildInfoRow(
                  Icons.location_on,
                  schedule.location!,
                ),
              ],

              // Participants count
              if (schedule.participants != null && schedule.participants!.isNotEmpty) ...[
                const SizedBox(height: 6),
                _buildInfoRow(
                  Icons.people,
                  '${schedule.participants!.length} peserta',
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 16, color: Colors.grey[600]),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: TextStyle(
              fontSize: 13,
              color: Colors.grey[700],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildStatusChip() {
    Color color;
    String label;

    switch (schedule.status) {
      case 'scheduled':
        color = Colors.blue;
        label = 'Terjadwal';
        break;
      case 'ongoing':
        color = Colors.green;
        label = 'Berlangsung';
        break;
      case 'completed':
        color = Colors.grey;
        label = 'Selesai';
        break;
      case 'cancelled':
        color = Colors.red;
        label = 'Dibatalkan';
        break;
      default:
        color = Colors.grey;
        label = schedule.status;
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
}