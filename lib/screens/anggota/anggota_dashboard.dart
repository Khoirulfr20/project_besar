import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/auth_provider.dart';
import '../../providers/attendance_provider.dart';
import '../../providers/schedule_provider.dart';
import 'schedule_view_screen.dart';
import '../face/face_capture_screen.dart';

class AnggotaDashboard extends StatefulWidget {
  const AnggotaDashboard({super.key});

  @override
  State<AnggotaDashboard> createState() => _AnggotaDashboardState();
}

class _AnggotaDashboardState extends State<AnggotaDashboard> {
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
      scheduleProvider.getMySchedules(today: true),
    ]);
  }

  void _onItemTapped(int index) {
    setState(() => _selectedIndex = index);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: (_selectedIndex == 0 || _selectedIndex == 3)
          ? AppBar(
              title:
                  Text(_selectedIndex == 0 ? 'Dashboard Anggota' : 'Profile'),
              actions: [
                IconButton(
                  icon: const Icon(Icons.notifications_outlined),
                  onPressed: () {
                    Navigator.pushNamed(context, '/notifications');
                  },
                ),
              ],
            )
          : null,
      body: _buildBody(),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: _onItemTapped,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home_outlined),
            activeIcon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.calendar_today_outlined),
            activeIcon: Icon(Icons.calendar_today),
            label: 'Jadwal',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.face_retouching_natural_outlined),
            activeIcon: Icon(Icons.face_retouching_natural),
            label: 'Absen',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person_outline),
            activeIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    switch (_selectedIndex) {
      case 0:
        return _buildHome();
      case 1:
        return _buildSchedules();
      case 2:
        return _buildAttendance();
      case 3:
        return _buildProfile();
      default:
        return _buildHome();
    }
  }

  Widget _buildHome() {
    final user = Provider.of<AuthProvider>(context).user;
    final todayAttendance =
        Provider.of<AttendanceProvider>(context).todayAttendance;

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Welcome Card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 30,
                        backgroundImage: user?.photo != null
                            ? NetworkImage(user!.photo!)
                            : null,
                        child: user?.photo == null
                            ? const Icon(Icons.person, size: 30)
                            : null,
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Halo, ${user?.name ?? ''}',
                              style: Theme.of(context).textTheme.titleLarge,
                            ),
                            Text(
                              user?.position ?? '',
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
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
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Attendance Status Card
          Card(
            color: todayAttendance?.hasCheckedIn ?? false
                ? Colors.green[50]
                : Colors.orange[50],
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Icon(
                    todayAttendance?.hasCheckedIn ?? false
                        ? Icons.check_circle
                        : Icons.access_time,
                    size: 48,
                    color: todayAttendance?.hasCheckedIn ?? false
                        ? Colors.green
                        : Colors.orange,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    todayAttendance?.hasCheckedIn ?? false
                        ? 'Sudah Absen Hari Ini'
                        : 'Belum Absen Hari Ini',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  if (todayAttendance?.hasCheckedIn ?? false) ...[
                    const SizedBox(height: 8),
                    Text('Check-in: ${todayAttendance!.checkInTime}'),
                    if (todayAttendance.hasCheckedOut)
                      Text('Check-out: ${todayAttendance.checkOutTime}'),
                  ],
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Quick Actions
          Row(
            children: [
              Expanded(
                child: _buildQuickAction(
                  icon: Icons.face_retouching_natural,
                  label: 'Absen',
                  color: Colors.blue,
                  onTap: () => setState(() => _selectedIndex = 2),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildQuickAction(
                  icon: Icons.calendar_today,
                  label: 'Jadwal',
                  color: Colors.purple,
                  onTap: () => setState(() => _selectedIndex = 1),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Today's Schedule
          Text(
            'Jadwal Hari Ini',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
          const SizedBox(height: 8),
          Consumer<ScheduleProvider>(
            builder: (context, provider, child) {
              if (provider.isLoading) {
                return const Center(child: CircularProgressIndicator());
              }

              final todaySchedules =
                  provider.schedules.where((s) => s.isToday).toList();

              if (todaySchedules.isEmpty) {
                return const Card(
                  child: Padding(
                    padding: EdgeInsets.all(16),
                    child: Center(
                      child: Text('Tidak ada jadwal hari ini'),
                    ),
                  ),
                );
              }

              return Column(
                children: todaySchedules.map((schedule) {
                  return Card(
                    child: ListTile(
                      leading: const CircleAvatar(
                        child: Icon(Icons.event),
                      ),
                      title: Text(schedule.title),
                      subtitle: Text(
                        '${schedule.startTime} - ${schedule.endTime}',
                      ),
                      trailing: Chip(
                        label: Text(
                          schedule.statusLabel,
                          style: const TextStyle(fontSize: 10),
                        ),
                      ),
                    ),
                  );
                }).toList(),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildQuickAction({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Icon(icon, size: 32, color: color),
              const SizedBox(height: 8),
              Text(
                label,
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSchedules() {
    // Menampilkan screen Jadwal yang sudah ada (punya AppBar sendiri)
    return const ScheduleViewScreen();
  }

  Widget _buildAttendance() {
    // Menampilkan screen Face Capture untuk absensi (punya AppBar sendiri)
    return const FaceCaptureScreen();
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
        Text(
          user?.name ?? '',
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.titleLarge,
        ),
        Text(
          user?.position ?? '',
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.bodyMedium,
        ),
        const SizedBox(height: 24),
        Card(
          child: Column(
            children: [
              ListTile(
                leading: const Icon(Icons.badge_outlined),
                title: const Text('ID Karyawan'),
                subtitle: Text(user?.employeeId ?? ''),
              ),
              ListTile(
                leading: const Icon(Icons.email_outlined),
                title: const Text('Email'),
                subtitle: Text(user?.email ?? ''),
              ),
              ListTile(
                leading: const Icon(Icons.phone_outlined),
                title: const Text('Telepon'),
                subtitle: Text(user?.phone ?? '-'),
              ),
              ListTile(
                leading: const Icon(Icons.business_outlined),
                title: const Text('Departemen'),
                subtitle: Text(user?.department ?? '-'),
              ),
              const Divider(),
              ListTile(
                leading: const Icon(Icons.logout, color: Colors.red),
                title:
                    const Text('Logout', style: TextStyle(color: Colors.red)),
                onTap: _handleLogout,
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
            child: const Text('Batal'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Logout'),
          ),
        ],
      ),
    );

    if (confirmed ?? false) {
      // ignore: use_build_context_synchronously
      await Provider.of<AuthProvider>(context, listen: false).logout();
      // ignore: use_build_context_synchronously
      Navigator.pushReplacementNamed(context, '/login');
    }
  }
}
