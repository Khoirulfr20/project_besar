import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../services/attendance_service.dart';
import '../../services/user_service.dart';
import '../../models/attendance_model.dart';
import '../../models/user_model.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final AttendanceService _attendanceService = AttendanceService();
  final UserService _userService = UserService();

  List<Attendance> _attendances = [];
  List<User> _users = [];
  bool _isLoading = false;

  final DateTime _startDate = DateTime.now().subtract(const Duration(days: 30));
  final DateTime _endDate = DateTime.now();
  int? _selectedUserId;
  String? _selectedStatus;

  @override
  void initState() {
    super.initState();
    _loadUsers();
    _loadHistory();
  }

  Future<void> _loadUsers() async {
    try {
      _users = await _userService.getUsers();
      setState(() {});
    } catch (e) {
      _showError('Gagal memuat user');
    }
  }

  Future<void> _loadHistory() async {
    setState(() => _isLoading = true);
    try {
      _attendances = await _attendanceService.getAttendances(
        userId: _selectedUserId,
        startDate: DateFormat('yyyy-MM-dd').format(_startDate),
        endDate: DateFormat('yyyy-MM-dd').format(_endDate),
        status: _selectedStatus,
      );
    } catch (e) {
      _showError('Gagal memuat histori');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Histori Kehadiran'),
        actions: [
          IconButton(
              icon: const Icon(Icons.filter_list), onPressed: _showFilterDialog)
        ],
      ),
      body: Column(
        children: [
          _buildFilterInfo(),
          Expanded(child: _buildHistoryList()),
        ],
      ),
    );
  }

  Widget _buildFilterInfo() {
    return Card(
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.calendar_today, size: 16),
                const SizedBox(width: 8),
                Text(
                    '${DateFormat('dd/MM/yy').format(_startDate)} - ${DateFormat('dd/MM/yy').format(_endDate)}'),
              ],
            ),
            if (_selectedUserId != null) ...[
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.person, size: 16),
                  const SizedBox(width: 8),
                  Text(_users.firstWhere((u) => u.id == _selectedUserId).name),
                ],
              ),
            ],
            if (_selectedStatus != null) ...[
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.filter_alt, size: 16),
                  const SizedBox(width: 8),
                  Text(_getStatusLabel(_selectedStatus!)),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildHistoryList() {
    if (_isLoading) return const Center(child: CircularProgressIndicator());
    if (_attendances.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.history, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text('Tidak ada data', style: TextStyle(color: Colors.grey[600])),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadHistory,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _attendances.length,
        itemBuilder: (context, index) =>
            _buildAttendanceCard(_attendances[index]),
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
            Text(DateFormat('dd MMM yyyy').format(attendance.date)),
            Text(
                'In: ${attendance.checkInTime ?? '-'} | Out: ${attendance.checkOutTime ?? '-'}'),
          ],
        ),
        trailing: _buildStatusBadge(attendance.status),
        onTap: () => _showDetail(attendance),
      ),
    );
  }

  Widget _buildStatusBadge(String status) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: _getStatusColor(status).withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(_getStatusLabel(status),
          style: TextStyle(fontSize: 11, color: _getStatusColor(status))),
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

  String _getStatusLabel(String status) {
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

  void _showDetail(Attendance attendance) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(attendance.user?.name ?? '',
                style:
                    const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            _buildDetailRow(
                'Tanggal', DateFormat('dd MMMM yyyy').format(attendance.date)),
            _buildDetailRow('Check-in', attendance.checkInTime ?? '-'),
            _buildDetailRow('Check-out', attendance.checkOutTime ?? '-'),
            if (attendance.workDuration != null)
              _buildDetailRow('Durasi', attendance.workDurationFormatted),
            _buildDetailRow('Status', _getStatusLabel(attendance.status)),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
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
    await showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Filter'),
        content: StatefulBuilder(
          builder: (context, setDialogState) => Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<int?>(
                initialValue: _selectedUserId,
                decoration: const InputDecoration(labelText: 'User'),
                items: [
                  const DropdownMenuItem(value: null, child: Text('Semua')),
                  ..._users.map((u) =>
                      DropdownMenuItem(value: u.id, child: Text(u.name))),
                ],
                onChanged: (v) => setDialogState(() => _selectedUserId = v),
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String?>(
                initialValue: _selectedStatus,
                decoration: const InputDecoration(labelText: 'Status'),
                items: const [
                  DropdownMenuItem(value: null, child: Text('Semua')),
                  DropdownMenuItem(value: 'present', child: Text('Hadir')),
                  DropdownMenuItem(value: 'late', child: Text('Terlambat')),
                  DropdownMenuItem(value: 'absent', child: Text('Tidak Hadir')),
                ],
                onChanged: (v) => setDialogState(() => _selectedStatus = v),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Batal')),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _loadHistory();
            },
            child: const Text('Terapkan'),
          ),
        ],
      ),
    );
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message), backgroundColor: Colors.red));
  }
}
