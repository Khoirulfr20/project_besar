import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:smart_attendance/screens/anggota/history_screen.dart';
import 'package:smart_attendance/screens/pimpinan/export_report_screen.dart';
import 'package:smart_attendance/screens/pimpinan/schedule_create_screen.dart';
import '../../providers/auth_provider.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/schedule_provider.dart';
import 'schedule_list_screen.dart';
import 'record_attendance_screen.dart';
import 'attendance_report_screen.dart';

class PimpinanDashboard extends StatefulWidget {
  const PimpinanDashboard({super.key});

  @override
  State<PimpinanDashboard> createState() => _PimpinanDashboardState();
}

class _PimpinanDashboardState extends State<PimpinanDashboard> {
  int _selectedIndex = 0;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    final attendanceProvider =
        Provider.of<AttendanceProvider>(context, listen: false);
    final scheduleProvider =
        Provider.of<ScheduleProvider>(context, listen: false);

    await Future.wait([
      attendanceProvider.getTodayAttendance(),
      attendanceProvider.loadStatistics(),
      scheduleProvider.getMySchedules(),
    ]);
  }

  void _onItemTapped(int index) {
    setState(() => _selectedIndex = index);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_getAppBarTitle()),
        actions: [
          // 🔹 Tombol notifikasi hanya muncul di Dashboard (index 0)
          if (_selectedIndex == 0)
            IconButton(
              icon: const Icon(Icons.notifications_outlined),
              onPressed: () => Navigator.pushNamed(context, '/notifications'),
            ),

          // 🔹 Tombol tambah (+) khusus untuk tab Jadwal (index 1)
          if (_selectedIndex == 1)
            IconButton(
              icon: const Icon(Icons.add),
              onPressed: () async {
                final result = await Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const ScheduleCreateScreen(),
                  ),
                );

                // 🔁 Jika berhasil menambah jadwal, perbarui tampilan
                if (result == true) {
                  setState(() {});
                }
              },
            ),

          // 🔹 Logout hanya muncul di tab Dashboard (index 0)
          if (_selectedIndex == 0)
            IconButton(
              icon: const Icon(Icons.logout),
              onPressed: _handleLogout,
            ),
        ],
      ),
      body: _buildBody(),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: _onItemTapped,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(
              icon: Icon(Icons.dashboard), label: 'Dashboard'),
          BottomNavigationBarItem(
              icon: Icon(Icons.calendar_today), label: 'Jadwal'),
          BottomNavigationBarItem(
              icon: Icon(Icons.checklist), label: 'Kehadiran'),
          BottomNavigationBarItem(
              icon: Icon(Icons.analytics), label: 'Laporan'),
          BottomNavigationBarItem(icon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }

  String _getAppBarTitle() {
    switch (_selectedIndex) {
      case 0:
        return 'Dashboard Pimpinan';
      case 1:
        return 'Kelola Jadwal';
      case 2:
        return 'Kelola Kehadiran';
      case 3:
        return 'Laporan Kehadiran';
      case 4:
        return 'Profile';
      default:
        return 'Dashboard Pimpinan';
    }
  }

  Widget _buildBody() {
    switch (_selectedIndex) {
      case 0:
        return _buildDashboard();
      case 1:
        return const ScheduleListScreen();
      case 2:
        return const RecordAttendanceScreen();
      case 3:
        return const AttendanceReportScreen();
      case 4:
        return _buildProfile();
      default:
        return _buildDashboard();
    }
  }

  Widget _buildDashboard() {
    final user = Provider.of<AuthProvider>(context).user;
    final statistics = Provider.of<AttendanceProvider>(context).statistics;

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Welcome Card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 30,
                    backgroundImage:
                        user?.photo != null ? NetworkImage(user!.photo!) : null,
                    child: user?.photo == null
                        ? const Icon(Icons.person, size: 30)
                        : null,
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(user?.name ?? '',
                            style: Theme.of(context).textTheme.titleLarge),
                        Text(user?.position ?? '',
                            style: Theme.of(context).textTheme.bodyMedium),
                        Text(
                          DateFormat('EEEE, dd MMMM yyyy', 'id_ID')
                              .format(DateTime.now()),
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Statistics Cards
          Text('Statistik Bulan Ini',
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                  child: _buildStatCard('Hadir',
                      statistics?.present.toString() ?? '0', Colors.green)),
              const SizedBox(width: 8),
              Expanded(
                  child: _buildStatCard('Terlambat',
                      statistics?.late.toString() ?? '0', Colors.orange)),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                  child: _buildStatCard('Izin',
                      statistics?.excused.toString() ?? '0', Colors.blue)),
              const SizedBox(width: 8),
              Expanded(
                  child: _buildStatCard('Alfa',
                      statistics?.absent.toString() ?? '0', Colors.red)),
            ],
          ),
          const SizedBox(height: 16),

          // Attendance Rate
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Tingkat Kehadiran',
                      style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  LinearProgressIndicator(
                    value: (statistics?.attendanceRate ?? 0) / 100,
                    minHeight: 10,
                    backgroundColor: Colors.grey[300],
                  ),
                  const SizedBox(height: 8),
                  Text(
                      '${statistics?.attendanceRate.toStringAsFixed(1) ?? '0'}%',
                      style: Theme.of(context)
                          .textTheme
                          .headlineSmall
                          ?.copyWith(fontWeight: FontWeight.bold)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Quick Actions
          Text('Menu Cepat',
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 3,
            crossAxisSpacing: 8,
            mainAxisSpacing: 8,
            children: [
              _buildQuickActionCard(Icons.event_note, 'Kelola\nJadwal',
                  () => setState(() => _selectedIndex = 1)),
              _buildQuickActionCard(Icons.checklist, 'Kelola\nKehadiran',
                  () => setState(() => _selectedIndex = 2)),
              _buildQuickActionCard(Icons.analytics, 'Laporan',
                  () => setState(() => _selectedIndex = 3)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String title, String value, Color color) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Text(value,
                style: TextStyle(
                    fontSize: 24, fontWeight: FontWeight.bold, color: color)),
            Text(title, style: const TextStyle(fontSize: 12)),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickActionCard(
      IconData icon, String label, VoidCallback onTap) {
    return Card(
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 32, color: Theme.of(context).primaryColor),
            const SizedBox(height: 8),
            Text(label,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 11)),
          ],
        ),
      ),
    );
  }

  Widget _buildProfile() {
    final user = Provider.of<AuthProvider>(context).user;
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Center(
          child: CircleAvatar(
            radius: 50,
            backgroundImage:
                user?.photo != null ? NetworkImage(user!.photo!) : null,
            child:
                user?.photo == null ? const Icon(Icons.person, size: 50) : null,
          ),
        ),
        const SizedBox(height: 16),
        Text(user?.name ?? '',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.titleLarge),
        Text(user?.position ?? '',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodyMedium),
        const SizedBox(height: 24),
        Card(
          child: Column(
            children: [
              ListTile(
                  leading: const Icon(Icons.badge),
                  title: const Text('ID Karyawan'),
                  subtitle: Text(user?.employeeId ?? '')),
              ListTile(
                  leading: const Icon(Icons.email),
                  title: const Text('Email'),
                  subtitle: Text(user?.email ?? '')),
              ListTile(
                  leading: const Icon(Icons.phone),
                  title: const Text('Telepon'),
                  subtitle: Text(user?.phone ?? '-')),
              ListTile(
                  leading: const Icon(Icons.business),
                  title: const Text('Departemen'),
                  subtitle: Text(user?.department ?? '-')),
            ],
          ),
        ),
        const SizedBox(height: 16),
        // Additional Menu Items
        Card(
          child: Column(
            children: [
              ListTile(
                leading: const Icon(Icons.history),
                title: const Text('Histori Kehadiran'),
                trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                onTap: () {
                  Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const HistoryScreen(),
                      ));
                },
              ),
              ListTile(
                leading: const Icon(Icons.download),
                title: const Text('Export Laporan'),
                trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                onTap: () {
                  Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const ExportReportScreen(),
                      ));
                },
              ),
            ],
          ),
        ),
      ],
    );
  }

  Future<void> _handleLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Apakah Anda yakin ingin logout?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Batal')),
          TextButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Logout')),
        ],
      ),
    );

    if (confirmed ?? false) {
      await Provider.of<AuthProvider>(context, listen: false).logout();
      if (mounted) {
        Navigator.pushReplacementNamed(context, '/login');
      }
    }
  }
}
