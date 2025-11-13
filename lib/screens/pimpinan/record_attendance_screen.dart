import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../services/attendance_service.dart';
import '../../models/attendance_model.dart';

class RecordAttendanceScreen extends StatefulWidget {
  const RecordAttendanceScreen({super.key});

  @override
  State<RecordAttendanceScreen> createState() => _RecordAttendanceScreenState();
}

class _RecordAttendanceScreenState extends State<RecordAttendanceScreen> {
  final AttendanceService _service = AttendanceService();
  List<Attendance> _attendances = [];
  bool _isLoading = false;
  DateTime _selectedDate = DateTime.now();
  String _filterStatus = 'all';

  @override
  void initState() {
    super.initState();
    _loadAttendances();
  }

  Future<void> _loadAttendances() async {
    setState(() => _isLoading = true);
    try {
      _attendances = await _service.getAttendances(
        date: DateFormat('yyyy-MM-dd').format(_selectedDate),
        status: _filterStatus != 'all' ? _filterStatus : null,
      );
    } catch (e) {
      if (mounted) _showError('Gagal memuat data');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _loadAttendances,
      child: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(child: _buildDateCard()),
          SliverToBoxAdapter(child: _buildFilterBar()),
          SliverToBoxAdapter(child: _buildStatistics()),
          _isLoading
              ? const SliverFillRemaining(
                  child: Center(child: CircularProgressIndicator()),
                )
              : _attendances.isEmpty
                  ? SliverFillRemaining(child: _buildEmptyState())
                  : SliverPadding(
                      padding: const EdgeInsets.all(16),
                      sliver: SliverList(
                        delegate: SliverChildBuilderDelegate(
                          (context, index) =>
                              _buildAttendanceCard(_attendances[index]),
                          childCount: _attendances.length,
                        ),
                      ),
                    ),
        ],
      ),
    );
  }

  Widget _buildDateCard() {
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
                DateFormat('EEEE, dd MMMM yyyy', 'id_ID').format(_selectedDate),
                style: const TextStyle(fontWeight: FontWeight.w500),
              ),
            ),
            IconButton(
              icon: const Icon(Icons.edit_calendar),
              onPressed: _selectDate,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterBar() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          _buildFilterChip('Semua', 'all'),
          _buildFilterChip('Hadir', 'present'),
          _buildFilterChip('Terlambat', 'late'),
          _buildFilterChip('Tidak Hadir', 'absent'),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(label),
        selected: _filterStatus == value,
        onSelected: (selected) {
          setState(() => _filterStatus = value);
          _loadAttendances();
        },
      ),
    );
  }

  Widget _buildStatistics() {
    final present = _attendances.where((a) => a.status == 'present').length;
    final late = _attendances.where((a) => a.status == 'late').length;
    final absent = _attendances.where((a) => a.status == 'absent').length;

    return Container(
      margin: const EdgeInsets.all(16),
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
  }

  Widget _buildStatCard(String label, int count, Color color) {
    return Card(
      color: color.withValues(alpha: 0.1),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            Text(count.toString(),
                style: TextStyle(
                    fontSize: 24, fontWeight: FontWeight.bold, color: color)),
            const SizedBox(height: 4),
            Text(label, style: const TextStyle(fontSize: 12)),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.checklist, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text('Tidak ada data kehadiran',
              style: TextStyle(color: Colors.grey[600])),
        ],
      ),
    );
  }

  Widget _buildAttendanceCard(Attendance attendance) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundImage: attendance.user?.photo != null
              ? NetworkImage(attendance.user!.photo!)
              : null,
          child: attendance.user?.photo == null
              ? Text(attendance.user?.name[0] ?? 'U')
              : null,
        ),
        title: Text(attendance.user?.name ?? 'Unknown'),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
                'In: ${attendance.checkInTime ?? '-'} | Out: ${attendance.checkOutTime ?? '-'}'),
            if (attendance.workDuration != null)
              Text('Durasi: ${attendance.workDurationFormatted}'),
          ],
        ),
        trailing: PopupMenuButton(
          child: Chip(
            label: Text(attendance.statusLabel,
                style: const TextStyle(fontSize: 11)),
            backgroundColor:
                _getStatusColor(attendance.status).withValues(alpha: 0.2),
          ),
          itemBuilder: (context) => [
            const PopupMenuItem(value: 'present', child: Text('Hadir')),
            const PopupMenuItem(value: 'late', child: Text('Terlambat')),
            const PopupMenuItem(value: 'absent', child: Text('Tidak Hadir')),
            const PopupMenuItem(value: 'excused', child: Text('Izin')),
            const PopupMenuItem(value: 'leave', child: Text('Cuti')),
          ],
          onSelected: (value) => _updateStatus(attendance.id, value),
        ),
        onTap: () => _showAttendanceDetail(attendance),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'present':
        return Colors.green;
      case 'late':
        return Colors.orange;
      case 'absent':
        return Colors.red;
      case 'excused':
        return Colors.blue;
      case 'leave':
        return Colors.purple;
      default:
        return Colors.grey;
    }
  }

  Future<void> _updateStatus(int id, String status) async {
    try {
      await _service.updateStatus(attendanceId: id, status: status);
      if (mounted) _showSuccess('Status berhasil diubah');
      _loadAttendances();
    } catch (e) {
      if (mounted) _showError('Gagal mengubah status');
    }
  }

  void _showAttendanceDetail(Attendance attendance) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.6,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(attendance.user?.name ?? '',
                  style: const TextStyle(
                      fontSize: 20, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              _buildDetailRow('Check-in', attendance.checkInTime ?? '-'),
              _buildDetailRow('Check-out', attendance.checkOutTime ?? '-'),
              if (attendance.workDuration != null)
                _buildDetailRow('Durasi', attendance.workDurationFormatted),
              _buildDetailRow('Status', attendance.statusLabel),
              if (attendance.checkInConfidence != null)
                _buildDetailRow('Confidence In',
                    '${(attendance.checkInConfidence! * 100).toStringAsFixed(1)}%'),
              if (attendance.checkOutConfidence != null)
                _buildDetailRow('Confidence Out',
                    '${(attendance.checkOutConfidence! * 100).toStringAsFixed(1)}%'),
            ],
          ),
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

  Future<void> _selectDate() async {
    final date = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (date != null) {
      setState(() => _selectedDate = date);
      _loadAttendances();
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message), backgroundColor: Colors.red));
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message), backgroundColor: Colors.green));
  }
}
