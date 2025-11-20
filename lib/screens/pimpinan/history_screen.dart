import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/attendance_provider.dart';
import '../../models/attendance_model.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  DateTime _startDate = DateTime.now().subtract(const Duration(days: 30));
  DateTime _endDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    final provider = Provider.of<AttendanceProvider>(context, listen: false);
    await provider.loadMyAttendance(
      startDate: DateFormat('yyyy-MM-dd').format(_startDate),
      endDate: DateFormat('yyyy-MM-dd').format(_endDate),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Histori Kehadiran'),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFilterDialog,
          ),
        ],
      ),
      body: Column(
        children: [
          _buildDateRangeCard(),
          _buildStatisticsSummary(),
          Expanded(child: _buildHistoryList()),
        ],
      ),
    );
  }

  Widget _buildDateRangeCard() {
    return Card(
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            const Icon(Icons.calendar_today, color: Colors.blue),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                '${DateFormat('dd MMM yyyy').format(_startDate)} - ${DateFormat('dd MMM yyyy').format(_endDate)}',
                style: const TextStyle(fontWeight: FontWeight.w500),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatisticsSummary() {
    return Consumer<AttendanceProvider>(
      builder: (context, provider, child) {
        if (provider.attendances.isEmpty) return const SizedBox();

        final present = provider.attendances.where((a) => a.isPresent).length;
        final late = provider.attendances.where((a) => a.isLate).length;
        final absent = provider.attendances.where((a) => a.isAbsent).length;

        return Container(
          margin: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            children: [
              Expanded(child: _buildStatCard('Hadir', present, Colors.green)),
              const SizedBox(width: 8),
              Expanded(child: _buildStatCard('Terlambat', late, Colors.orange)),
              const SizedBox(width: 8),
              Expanded(child: _buildStatCard('Alfa', absent, Colors.red)),
            ],
          ),
        );
      },
    );
  }

  Widget _buildStatCard(String label, int count, Color color) {
    return Card(
      color: color.withValues(alpha: 0.1),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            Text(
              count.toString(),
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            const SizedBox(height: 4),
            Text(label, style: const TextStyle(fontSize: 12)),
          ],
        ),
      ),
    );
  }

  Widget _buildHistoryList() {
    return Consumer<AttendanceProvider>(
      builder: (context, provider, child) {
        if (provider.isLoading) {
          return const Center(child: CircularProgressIndicator());
        }

        if (provider.attendances.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.history, size: 64, color: Colors.grey[400]),
                const SizedBox(height: 16),
                Text('Tidak ada data kehadiran',
                    style: TextStyle(color: Colors.grey[600])),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: _loadHistory,
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: provider.attendances.length,
            itemBuilder: (context, index) {
              final attendance = provider.attendances[index];
              return _buildAttendanceCard(attendance);
            },
          ),
        );
      },
    );
  }

  Widget _buildAttendanceCard(Attendance attendance) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () => _showAttendanceDetail(attendance),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      DateFormat('EEEE, dd MMMM yyyy', 'id_ID')
                          .format(attendance.date),
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                  _buildStatusBadge(attendance.status),
                ],
              ),
              const SizedBox(height: 12),
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
              if (attendance.workDuration != null) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    Icon(Icons.timer, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Text(
                      'Durasi: ${attendance.workDurationFormatted}',
                      style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                    ),
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
            Text(label,
                style: TextStyle(fontSize: 12, color: Colors.grey[600])),
          ],
        ),
        const SizedBox(height: 4),
        Text(time, style: const TextStyle(fontWeight: FontWeight.w600)),
      ],
    );
  }

  Widget _buildStatusBadge(String status) {
    Color color;
    String label;

    switch (status) {
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
        label = status;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style:
            TextStyle(fontSize: 12, color: color, fontWeight: FontWeight.w600),
      ),
    );
  }

  void _showAttendanceDetail(Attendance attendance) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 24),
            Text(
              DateFormat('EEEE, dd MMMM yyyy', 'id_ID').format(attendance.date),
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            _buildStatusBadge(attendance.status),
            const SizedBox(height: 24),
            _buildDetailRow('Check-in', attendance.checkInTime ?? '-'),
            _buildDetailRow('Check-out', attendance.checkOutTime ?? '-'),
            if (attendance.workDuration != null)
              _buildDetailRow('Durasi Kerja', attendance.workDurationFormatted),
            if (attendance.checkInConfidence != null)
              _buildDetailRow('Confidence Check-in',
                  '${(attendance.checkInConfidence! * 100).toStringAsFixed(1)}%'),
            if (attendance.checkOutConfidence != null)
              _buildDetailRow('Confidence Check-out',
                  '${(attendance.checkOutConfidence! * 100).toStringAsFixed(1)}%'),
            if (attendance.notes != null) ...[
              const SizedBox(height: 16),
              const Text('Catatan:',
                  style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Text(attendance.notes!),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: Colors.grey[600])),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  Future<void> _showFilterDialog() async {
    final result = await showDialog<Map<String, DateTime>>(
      context: context,
      builder: (context) =>
          _FilterDialog(startDate: _startDate, endDate: _endDate),
    );

    if (result != null) {
      setState(() {
        _startDate = result['start']!;
        _endDate = result['end']!;
      });
      _loadHistory();
    }
  }
}

class _FilterDialog extends StatefulWidget {
  final DateTime startDate;
  final DateTime endDate;

  const _FilterDialog({required this.startDate, required this.endDate});

  @override
  State<_FilterDialog> createState() => _FilterDialogState();
}

class _FilterDialogState extends State<_FilterDialog> {
  late DateTime _startDate;
  late DateTime _endDate;

  @override
  void initState() {
    super.initState();
    _startDate = widget.startDate;
    _endDate = widget.endDate;
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Filter Periode'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            title: const Text('Tanggal Mulai'),
            subtitle: Text(DateFormat('dd MMM yyyy').format(_startDate)),
            trailing: const Icon(Icons.calendar_today),
            onTap: () async {
              final date = await showDatePicker(
                context: context,
                initialDate: _startDate,
                firstDate: DateTime(2020),
                lastDate: DateTime.now(),
              );
              if (date != null) setState(() => _startDate = date);
            },
          ),
          ListTile(
            title: const Text('Tanggal Akhir'),
            subtitle: Text(DateFormat('dd MMM yyyy').format(_endDate)),
            trailing: const Icon(Icons.calendar_today),
            onTap: () async {
              final date = await showDatePicker(
                context: context,
                initialDate: _endDate,
                firstDate: _startDate,
                lastDate: DateTime.now(),
              );
              if (date != null) setState(() => _endDate = date);
            },
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Batal'),
        ),
        TextButton(
          onPressed: () =>
              Navigator.pop(context, {'start': _startDate, 'end': _endDate}),
          child: const Text('Terapkan'),
        ),
      ],
    );
  }
}
