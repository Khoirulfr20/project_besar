import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../services/report_service.dart';

class AttendanceReportScreen extends StatefulWidget {
  const AttendanceReportScreen({super.key});

  @override
  State<AttendanceReportScreen> createState() => _AttendanceReportScreenState();
}

class _AttendanceReportScreenState extends State<AttendanceReportScreen> {
  final ReportService _service = ReportService();
  Map<String, dynamic>? _summary;
  bool _isLoading = false;
  DateTime _startDate = DateTime.now().subtract(const Duration(days: 30));
  DateTime _endDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _loadSummary();
  }

  Future<void> _loadSummary() async {
    setState(() => _isLoading = true);
    try {
      _summary = await _service.getSummary(
        startDate: DateFormat('yyyy-MM-dd').format(_startDate),
        endDate: DateFormat('yyyy-MM-dd').format(_endDate),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gagal memuat data: ${e.toString()}'),
            backgroundColor: Colors.red,
            duration: const Duration(seconds: 3),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    // ✅ Hapus Scaffold dan AppBar, langsung return body
    return _isLoading
        ? const Center(child: CircularProgressIndicator())
        : ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _buildPeriodCard(),
              const SizedBox(height: 16),
              _buildStatisticsCards(),
              const SizedBox(height: 16),
              _buildAttendanceChart(),
              const SizedBox(height: 16),
              _buildAttendanceRateCard(),
            ],
          );
  }

  Widget _buildPeriodCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Periode',
                style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                    child: _buildDateButton('Dari', _startDate,
                        (date) => setState(() => _startDate = date))),
                const SizedBox(width: 8),
                Expanded(
                    child: _buildDateButton('Sampai', _endDate,
                        (date) => setState(() => _endDate = date))),
              ],
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _loadSummary,
                icon: const Icon(Icons.refresh),
                label: const Text('Tampilkan'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDateButton(
      String label, DateTime date, Function(DateTime) onDateSelected) {
    return OutlinedButton(
      onPressed: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: date,
          firstDate: DateTime(2020),
          lastDate: DateTime.now(),
        );
        if (picked != null) {
          onDateSelected(picked);
          _loadSummary();
        }
      },
      child: Column(
        children: [
          Text(label, style: const TextStyle(fontSize: 11)),
          Text(DateFormat('dd/MM/yy').format(date)),
        ],
      ),
    );
  }

  Widget _buildStatisticsCards() {
    if (_summary == null) return const SizedBox();

    return Column(
      children: [
        Row(
          children: [
            Expanded(
                child: _buildStatCard('Total User',
                    _summary!['total_users'] ?? 0, Colors.blue, Icons.people)),
            const SizedBox(width: 8),
            Expanded(
                child: _buildStatCard(
                    'Total Kehadiran',
                    _summary!['total_attendances'] ?? 0,
                    Colors.purple,
                    Icons.event_available)),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(
                child: _buildStatCard('Hadir', _summary!['present'] ?? 0,
                    Colors.green, Icons.check_circle)),
            const SizedBox(width: 8),
            Expanded(
                child: _buildStatCard('Terlambat', _summary!['late'] ?? 0,
                    Colors.orange, Icons.access_time)),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(
                child: _buildStatCard('Tidak Hadir', _summary!['absent'] ?? 0,
                    Colors.red, Icons.cancel)),
            const SizedBox(width: 8),
            Expanded(
                child: _buildStatCard('Izin', _summary!['excused'] ?? 0,
                    Colors.blue, Icons.info)),
          ],
        ),
      ],
    );
  }

  Widget _buildStatCard(String label, int value, Color color, IconData icon) {
    return Card(
      color: color.withValues(alpha: 0.1),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            Icon(icon, color: color, size: 24),
            const SizedBox(height: 4),
            Text(value.toString(),
                style: TextStyle(
                    fontSize: 20, fontWeight: FontWeight.bold, color: color)),
            Text(label,
                style: const TextStyle(fontSize: 11),
                textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _buildAttendanceChart() {
    if (_summary == null) return const SizedBox();

    final present = (_summary!['present'] ?? 0).toDouble();
    final late = (_summary!['late'] ?? 0).toDouble();
    final absent = (_summary!['absent'] ?? 0).toDouble();
    final excused = (_summary!['excused'] ?? 0).toDouble();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Grafik Kehadiran',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 24),
            SizedBox(
              height: 200,
              child: PieChart(
                PieChartData(
                  sections: [
                    PieChartSectionData(
                        value: present,
                        color: Colors.green,
                        title: 'Hadir\n${present.toInt()}',
                        radius: 80),
                    PieChartSectionData(
                        value: late,
                        color: Colors.orange,
                        title: 'Terlambat\n${late.toInt()}',
                        radius: 80),
                    PieChartSectionData(
                        value: absent,
                        color: Colors.red,
                        title: 'Alfa\n${absent.toInt()}',
                        radius: 80),
                    PieChartSectionData(
                        value: excused,
                        color: Colors.blue,
                        title: 'Izin\n${excused.toInt()}',
                        radius: 80),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAttendanceRateCard() {
    if (_summary == null) return const SizedBox();

    final rate = _summary!['attendance_rate'] ?? 0;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Tingkat Kehadiran',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            LinearProgressIndicator(
                value: rate / 100,
                minHeight: 12,
                backgroundColor: Colors.grey[300]),
            const SizedBox(height: 8),
            Text('${rate.toStringAsFixed(1)}%',
                style:
                    const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }
}
