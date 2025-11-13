import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../services/schedule_service.dart';
import '../../services/user_service.dart';
import '../../models/user_model.dart';
import '../../widgets/common/custom_button.dart';
import '../../widgets/common/custom_textfield.dart';

class ScheduleCreateScreen extends StatefulWidget {
  const ScheduleCreateScreen({super.key});

  @override
  State<ScheduleCreateScreen> createState() => _ScheduleCreateScreenState();
}

class _ScheduleCreateScreenState extends State<ScheduleCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descController = TextEditingController();
  final _locationController = TextEditingController();
  final ScheduleService _scheduleService = ScheduleService();
  final UserService _userService = UserService();

  DateTime _selectedDate = DateTime.now();
  TimeOfDay _startTime = const TimeOfDay(hour: 9, minute: 0);
  TimeOfDay _endTime = const TimeOfDay(hour: 17, minute: 0);
  String _selectedType = 'meeting';
  List<User> _allUsers = [];
  List<int> _selectedUserIds = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadUsers();
  }

  Future<void> _loadUsers() async {
    try {
      _allUsers = await _userService.getUsers();
      setState(() {});
    } catch (e) {
      _showError('Gagal memuat daftar pengguna');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Buat Jadwal Baru')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            CustomTextField(
              controller: _titleController,
              label: 'Judul Kegiatan',
              hintText: 'Masukkan judul',
              prefixIcon: Icons.title,
              validator: (v) => v?.isEmpty ?? true ? 'Judul wajib diisi' : null,
            ),
            const SizedBox(height: 16),
            CustomTextField(
              controller: _descController,
              label: 'Deskripsi',
              hintText: 'Masukkan deskripsi',
              prefixIcon: Icons.description,
              maxLines: 3,
            ),
            const SizedBox(height: 16),
            _buildDatePicker(),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                    child: _buildTimePicker('Jam Mulai', _startTime,
                        (time) => setState(() => _startTime = time))),
                const SizedBox(width: 16),
                Expanded(
                    child: _buildTimePicker('Jam Selesai', _endTime,
                        (time) => setState(() => _endTime = time))),
              ],
            ),
            const SizedBox(height: 16),
            CustomTextField(
              controller: _locationController,
              label: 'Lokasi',
              hintText: 'Masukkan lokasi',
              prefixIcon: Icons.location_on,
            ),
            const SizedBox(height: 16),
            _buildTypeDropdown(),
            const SizedBox(height: 16),
            _buildParticipantSelector(),
            const SizedBox(height: 24),
            CustomButton(
              text: 'Simpan Jadwal',
              onPressed: _submitSchedule,
              isLoading: _isLoading,
              isBlock: true,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDatePicker() {
    return InkWell(
      onTap: () async {
        final date = await showDatePicker(
          context: context,
          initialDate: _selectedDate,
          firstDate: DateTime.now(),
          lastDate: DateTime.now().add(const Duration(days: 365)),
        );
        if (date != null) setState(() => _selectedDate = date);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: 'Tanggal',
          prefixIcon: const Icon(Icons.calendar_today),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
        ),
        child: Text(DateFormat('dd MMMM yyyy', 'id_ID').format(_selectedDate)),
      ),
    );
  }

  Widget _buildTimePicker(
      String label, TimeOfDay time, Function(TimeOfDay) onChanged) {
    return InkWell(
      onTap: () async {
        final picked =
            await showTimePicker(context: context, initialTime: time);
        if (picked != null) onChanged(picked);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
        ),
        child: Text(time.format(context)),
      ),
    );
  }

  Widget _buildTypeDropdown() {
    return DropdownButtonFormField<String>(
      initialValue: _selectedType,
      decoration: InputDecoration(
        labelText: 'Tipe Kegiatan',
        prefixIcon: const Icon(Icons.category),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
      ),
      items: const [
        DropdownMenuItem(value: 'meeting', child: Text('Meeting')),
        DropdownMenuItem(value: 'training', child: Text('Training')),
        DropdownMenuItem(value: 'event', child: Text('Event')),
        DropdownMenuItem(value: 'other', child: Text('Lainnya')),
      ],
      onChanged: (v) => setState(() => _selectedType = v!),
    );
  }

  Widget _buildParticipantSelector() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Peserta',
                style: TextStyle(fontWeight: FontWeight.w500)),
            Text('${_selectedUserIds.length} dipilih',
                style: TextStyle(color: Colors.grey[600])),
          ],
        ),
        const SizedBox(height: 8),
        Card(
          child: Column(
            children: _allUsers.map((user) {
              final isSelected = _selectedUserIds.contains(user.id);
              return CheckboxListTile(
                value: isSelected,
                onChanged: (checked) {
                  setState(() {
                    if (checked ?? false) {
                      _selectedUserIds.add(user.id);
                    } else {
                      _selectedUserIds.remove(user.id);
                    }
                  });
                },
                title: Text(user.name),
                subtitle: Text(user.email),
                secondary: CircleAvatar(
                  backgroundImage:
                      user.photo != null ? NetworkImage(user.photo!) : null,
                  child: user.photo == null ? Text(user.name[0]) : null,
                ),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }

  Future<void> _submitSchedule() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final schedule = await _scheduleService.createSchedule(
        title: _titleController.text,
        description: _descController.text.isEmpty ? null : _descController.text,
        date: DateFormat('yyyy-MM-dd').format(_selectedDate),
        startTime:
            '${_startTime.hour.toString().padLeft(2, '0')}:${_startTime.minute.toString().padLeft(2, '0')}',
        endTime:
            '${_endTime.hour.toString().padLeft(2, '0')}:${_endTime.minute.toString().padLeft(2, '0')}',
        location:
            _locationController.text.isEmpty ? null : _locationController.text,
        type: _selectedType,
        participantIds: _selectedUserIds.isEmpty ? null : _selectedUserIds,
      );

      if (schedule != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Jadwal berhasil dibuat'),
              backgroundColor: Colors.green),
        );
        Navigator.pop(context, true);
      } else {
        throw Exception('Gagal membuat jadwal');
      }
    } catch (e) {
      _showError(e.toString());
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }

  @override
  void dispose() {
    _titleController.dispose();
    _descController.dispose();
    _locationController.dispose();
    super.dispose();
  }
}
