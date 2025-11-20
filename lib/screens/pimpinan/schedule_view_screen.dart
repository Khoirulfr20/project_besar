import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/schedule_provider.dart';
import '../../models/schedule_model.dart';

class PimpinanScheduleViewScreen extends StatefulWidget {
  const PimpinanScheduleViewScreen({super.key});

  @override
  State<PimpinanScheduleViewScreen> createState() =>
      _PimpinanScheduleViewScreenState();
}

class _PimpinanScheduleViewScreenState
    extends State<PimpinanScheduleViewScreen> {
  String _filter = 'upcoming';

  @override
  void initState() {
    super.initState();
    _loadSchedules();
  }

  Future<void> _loadSchedules() async {
    final provider = Provider.of<ScheduleProvider>(context, listen: false);
    if (_filter == 'upcoming') {
      await provider.getMySchedules(upcoming: true);
    } else if (_filter == 'today') {
      await provider.getMySchedules(today: true);
    } else {
      await provider.getMySchedules();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Jadwal Kegiatan'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadSchedules,
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: Column(
        children: [
          _buildFilterChips(),
          _buildStatisticsCard(),
          Expanded(child: _buildScheduleList()),
        ],
      ),
    );
  }

  Widget _buildFilterChips() {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Expanded(
            child: FilterChip(
              label: const Text('Hari Ini'),
              selected: _filter == 'today',
              onSelected: (selected) {
                setState(() => _filter = 'today');
                _loadSchedules();
              },
              selectedColor: Colors.blue.withValues(alpha: 0.2),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: FilterChip(
              label: const Text('Mendatang'),
              selected: _filter == 'upcoming',
              onSelected: (selected) {
                setState(() => _filter = 'upcoming');
                _loadSchedules();
              },
              selectedColor: Colors.blue.withValues(alpha: 0.2),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: FilterChip(
              label: const Text('Semua'),
              selected: _filter == 'all',
              onSelected: (selected) {
                setState(() => _filter = 'all');
                _loadSchedules();
              },
              selectedColor: Colors.blue.withValues(alpha: 0.2),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatisticsCard() {
    return Consumer<ScheduleProvider>(
      builder: (context, provider, child) {
        final total = provider.schedules.length;
        final today = provider.schedules.where((s) => s.isToday).length;
        final upcoming =
            provider.schedules.where((s) => !s.isPast && !s.isToday).length;

        return Card(
          margin: const EdgeInsets.symmetric(horizontal: 16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildStatItem('Total', total, Colors.blue),
                _buildStatItem('Hari Ini', today, Colors.green),
                _buildStatItem('Mendatang', upcoming, Colors.orange),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildStatItem(String label, int count, Color color) {
    return Column(
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
    );
  }

  Widget _buildScheduleList() {
    return Consumer<ScheduleProvider>(
      builder: (context, provider, child) {
        if (provider.isLoading) {
          return const Center(child: CircularProgressIndicator());
        }

        if (provider.schedules.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.calendar_today, size: 64, color: Colors.grey[400]),
                const SizedBox(height: 16),
                Text('Tidak ada jadwal',
                    style: TextStyle(color: Colors.grey[600])),
                const SizedBox(height: 8),
                Text(
                  _filter == 'today'
                      ? 'Tidak ada jadwal hari ini'
                      : _filter == 'upcoming'
                          ? 'Tidak ada jadwal mendatang'
                          : 'Belum ada jadwal',
                  style: TextStyle(fontSize: 12, color: Colors.grey[500]),
                ),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: _loadSchedules,
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: provider.schedules.length,
            itemBuilder: (context, index) {
              final schedule = provider.schedules[index];
              return _buildScheduleCard(schedule);
            },
          ),
        );
      },
    );
  }

  Widget _buildScheduleCard(Schedule schedule) {
    final isPast = schedule.isPast;
    final isToday = schedule.isToday;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: isToday ? 4 : 1,
      color: isPast ? Colors.grey[100] : null,
      child: InkWell(
        onTap: () => _showScheduleDetail(schedule),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  if (isToday) ...[
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.green,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: const Text(
                        'HARI INI',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                  ],
                  Expanded(
                    child: Text(
                      schedule.title,
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: isPast ? Colors.grey[600] : null,
                      ),
                    ),
                  ),
                  _buildStatusChip(schedule.status),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Icon(Icons.calendar_today, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Text(
                    DateFormat('EEEE, dd MMM yyyy', 'id_ID')
                        .format(schedule.date),
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  Icon(Icons.access_time, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Text(
                    '${schedule.startTime} - ${schedule.endTime}',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                  const Spacer(),
                  Icon(Icons.timer_outlined, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Text(
                    '${schedule.getDurationInMinutes()} menit',
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                  ),
                ],
              ),
              if (schedule.location != null) ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(Icons.location_on, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        schedule.location!,
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                    ),
                  ],
                ),
              ],
              const SizedBox(height: 8),
              Row(
                children: [
                  _buildTypeChip(schedule.type),
                  const SizedBox(width: 8),
                  if (schedule.participants != null)
                    Row(
                      children: [
                        Icon(Icons.people, size: 16, color: Colors.grey[600]),
                        const SizedBox(width: 4),
                        Text(
                          '${schedule.participants!.length} peserta',
                          style:
                              TextStyle(fontSize: 12, color: Colors.grey[600]),
                        ),
                      ],
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusChip(String status) {
    Color color;
    String label;
    IconData icon;

    switch (status) {
      case 'scheduled':
        color = Colors.blue;
        label = 'Terjadwal';
        icon = Icons.schedule;
        break;
      case 'ongoing':
        color = Colors.green;
        label = 'Berlangsung';
        icon = Icons.play_circle;
        break;
      case 'completed':
        color = Colors.grey;
        label = 'Selesai';
        icon = Icons.check_circle;
        break;
      case 'cancelled':
        color = Colors.red;
        label = 'Dibatalkan';
        icon = Icons.cancel;
        break;
      default:
        color = Colors.grey;
        label = status;
        icon = Icons.info;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: color,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTypeChip(String type) {
    IconData icon;
    String label;

    switch (type) {
      case 'meeting':
        icon = Icons.groups;
        label = 'Meeting';
        break;
      case 'training':
        icon = Icons.school;
        label = 'Training';
        break;
      case 'event':
        icon = Icons.event;
        label = 'Event';
        break;
      default:
        icon = Icons.more_horiz;
        label = 'Lainnya';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: Colors.grey[700]),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(fontSize: 12, color: Colors.grey[700]),
          ),
        ],
      ),
    );
  }

  void _showScheduleDetail(Schedule schedule) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.75,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Handle
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

              // Title & Status
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      schedule.title,
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  _buildStatusChip(schedule.status),
                  const SizedBox(width: 8),
                  _buildTypeChip(schedule.type),
                ],
              ),

              const Divider(height: 32),

              // Details
              _buildDetailRow(
                  Icons.calendar_today,
                  'Tanggal',
                  DateFormat('EEEE, dd MMMM yyyy', 'id_ID')
                      .format(schedule.date)),
              _buildDetailRow(Icons.access_time, 'Waktu',
                  '${schedule.startTime} - ${schedule.endTime}'),
              _buildDetailRow(Icons.timer_outlined, 'Durasi',
                  '${schedule.getDurationInMinutes()} menit'),
              if (schedule.location != null)
                _buildDetailRow(
                    Icons.location_on, 'Lokasi', schedule.location!),

              if (schedule.description != null) ...[
                const SizedBox(height: 16),
                const Text(
                  'Deskripsi:',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(schedule.description!),
                ),
              ],

              if (schedule.participants != null &&
                  schedule.participants!.isNotEmpty) ...[
                const SizedBox(height: 24),
                Row(
                  children: [
                    const Text(
                      'Peserta',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: Colors.blue.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        '${schedule.participants!.length}',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.blue,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                ...schedule.participants!.map((p) => Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.grey[100],
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 20,
                            child: Text(p.name[0]),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  p.name,
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w600),
                                ),
                                Text(
                                  p.email,
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey[600],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    )),
              ],

              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: Colors.grey[600]),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
