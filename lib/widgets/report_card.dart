import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../models/report_model.dart';

class ReportCard extends StatelessWidget {
  final Report report;
  final VoidCallback? onTap;
  final VoidCallback? onDownload;

  const ReportCard({
    super.key,
    required this.report,
    this.onTap,
    this.onDownload,
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
          child: Row(
            children: [
              // Icon
              CircleAvatar(
                radius: 24,
                backgroundColor: _getFormatColor().withValues(alpha: 0.15),
                child: Icon(
                  _getFormatIcon(),
                  color: _getFormatColor(),
                  size: 24,
                ),
              ),
              const SizedBox(width: 16),

              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      report.title,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${DateFormat('dd MMM').format(report.startDate)} - ${DateFormat('dd MMM yyyy').format(report.endDate)}',
                      style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        _buildChip(report.typeLabel, Colors.blue),
                        const SizedBox(width: 6),
                        _buildChip(report.formatLabel, Colors.green),
                        const SizedBox(width: 6),
                        _buildStatusChip(),
                      ],
                    ),
                    if (report.fileSize != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        report.fileSizeFormatted,
                        style: TextStyle(fontSize: 11, color: Colors.grey[500]),
                      ),
                    ],
                  ],
                ),
              ),

              // Download button
              if (report.isCompleted && onDownload != null)
                IconButton(
                  icon: const Icon(Icons.download),
                  color: Theme.of(context).primaryColor,
                  onPressed: onDownload,
                )
              else if (report.isProcessing)
                const SizedBox(
                  width: 24,
                  height: 24,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildChip(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  Widget _buildStatusChip() {
    Color color;
    String label;

    if (report.isCompleted) {
      color = Colors.green;
      label = 'Selesai';
    } else if (report.isProcessing) {
      color = Colors.orange;
      label = 'Proses';
    } else if (report.isFailed) {
      color = Colors.red;
      label = 'Gagal';
    } else {
      color = Colors.grey;
      label = 'Pending';
    }

    return _buildChip(label, color);
  }

  IconData _getFormatIcon() {
    switch (report.format) {
      case 'pdf':
        return Icons.picture_as_pdf;
      case 'excel':
        return Icons.table_chart;
      case 'csv':
        return Icons.description;
      default:
        return Icons.file_present;
    }
  }

  Color _getFormatColor() {
    switch (report.format) {
      case 'pdf':
        return Colors.red;
      case 'excel':
        return Colors.green;
      case 'csv':
        return Colors.blue;
      default:
        return Colors.grey;
    }
  }
}
