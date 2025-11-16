import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/auth_provider.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/schedule_provider.dart';
import '../pimpinan/schedule_view_screen.dart';
import '../pimpinan/attendance_report_screen.dart';
import '../pimpinan/history_screen.dart';
import '../../screens/face/face_capture_screen.dart';

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
    if (index == 2) {
      // Face Capture di tengah - navigate langsung
      final todayAttendance =
          Provider.of<AttendanceProvider>(context, listen: false)
              .todayAttendance;
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => FaceCaptureScreen(
            isCheckOut: todayAttendance?.hasCheckedIn ?? false,
          ),
        ),
      ).then((_) => _loadData());
    } else {
      setState(() => _selectedIndex = index);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: _selectedIndex == 0
          ? AppBar(
              title: const Text('Dashboard Pimpinan'),
              actions: [
                IconButton(
                  icon: const Icon(Icons.notifications_outlined),
                  onPressed: () =>
                      Navigator.pushNamed(context, '/notifications'),
                ),
                IconButton(
                  icon: const Icon(Icons.person_outline),
                  onPressed: () => _showProfileBottomSheet(),
                ),
              ],
            )
          : null,
      body: _buildBody(),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex == 2
            ? 0
            : _selectedIndex > 2
                ? _selectedIndex - 1
                : _selectedIndex,
        onTap: _onItemTapped,
        type: BottomNavigationBarType.fixed,
        selectedFontSize: 12,
        unselectedFontSize: 11,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard),
            label: 'Dashboard',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.calendar_today),
            label: 'Jadwal',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.face_retouching_natural),
            label: 'Rekam Absen',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.analytics),
            label: 'Laporan',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.history),
            label: 'Histori',
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    switch (_selectedIndex) {
      case 0:
        return _buildDashboard();
      case 1:
        return const PimpinanScheduleViewScreen();
      case 2:
        return _buildDashboard(); // Face capture di handle di onTap
      case 3:
        return const AttendanceReportScreen();
      case 4:
        return const HistoryScreen();
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
              _buildQuickActionCard(Icons.calendar_today, 'Jadwal',
                  () => setState(() => _selectedIndex = 1)),
              _buildQuickActionCard(Icons.face_retouching_natural,
                  'Rekam\nAbsen', () => _onItemTapped(2)),
              _buildQuickActionCard(Icons.analytics, 'Laporan',
                  () => setState(() => _selectedIndex = 3)),
              _buildQuickActionCard(Icons.history, 'Histori',
                  () => setState(() => _selectedIndex = 4)),
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
                style: const TextStyle(fontSize: 12)),
          ],
        ),
      ),
    );
  }

  void _showProfileBottomSheet() {
    final user = Provider.of<AuthProvider>(context, listen: false).user;

    showDialog(
      context: context,
      builder: (context) => Dialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                    ),
                  ],
                ),
                CircleAvatar(
                  radius: 40,
                  backgroundImage:
                      user?.photo != null ? NetworkImage(user!.photo!) : null,
                  child: user?.photo == null
                      ? const Icon(Icons.person, size: 40)
                      : null,
                ),
                const SizedBox(height: 16),
                Text(user?.name ?? '',
                    style: Theme.of(context).textTheme.titleLarge),
                Text(user?.position ?? '',
                    style: Theme.of(context).textTheme.bodyMedium),
                const Divider(height: 32),
                ListTile(
                  leading: const Icon(Icons.badge),
                  title: const Text('ID Karyawan'),
                  subtitle: Text(user?.employeeId ?? ''),
                ),
                ListTile(
                  leading: const Icon(Icons.email),
                  title: const Text('Email'),
                  subtitle: Text(user?.email ?? ''),
                ),
                ListTile(
                  leading: const Icon(Icons.phone),
                  title: const Text('Telepon'),
                  subtitle: Text(user?.phone ?? '-'),
                ),
                const Divider(),
                ListTile(
                  leading: const Icon(Icons.logout, color: Colors.red),
                  title:
                      const Text('Logout', style: TextStyle(color: Colors.red)),
                  onTap: () {
                    Navigator.pop(context);
                    _handleLogout();
                  },
                ),
              ],
            ),
          ),
        ),
      ),
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
      Navigator.pushReplacementNamed(context, '/login');
    }
  }
}
