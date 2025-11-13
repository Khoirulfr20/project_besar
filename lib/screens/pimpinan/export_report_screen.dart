import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../services/report_service.dart';
import '../../models/report_model.dart';

class ExportReportScreen extends StatefulWidget {
  const ExportReportScreen({super.key});

  @override
  State<ExportReportScreen> createState() => _ExportReportScreenState();
}

class _ExportReportScreenState extends State<ExportReportScreen> {
  final ReportService _service = ReportService();
  List<Report> _reports = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadReports();
  }

  Future<void> _loadReports() async {
    setState(() => _isLoading = true);
    try {
      _reports = await _service.getReports();
    } catch (e) {
      _showError('Gagal memuat laporan');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Export Laporan')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _showGenerateDialog,
        icon: const Icon(Icons.add),
        label: const Text('Buat Laporan'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _reports.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: _loadReports,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _reports.length,
                    itemBuilder: (context, index) =>
                        _buildReportCard(_reports[index]),
                  ),
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.description, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text('Belum ada laporan', style: TextStyle(color: Colors.grey[600])),
          const SizedBox(height: 8),
          ElevatedButton.icon(
            onPressed: _showGenerateDialog,
            icon: const Icon(Icons.add),
            label: const Text('Buat Laporan'),
          ),
        ],
      ),
    );
  }

  Widget _buildReportCard(Report report) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor:
              _getStatusColor(report.status).withValues(alpha: 0.2),
          child: Icon(_getFormatIcon(report.format),
              color: _getStatusColor(report.status)),
        ),
        title: Text(report.title),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
                '${DateFormat('dd MMM').format(report.startDate)} - ${DateFormat('dd MMM yyyy').format(report.endDate)}'),
            Row(
              children: [
                Chip(
                    label: Text(report.typeLabel,
                        style: const TextStyle(fontSize: 11)),
                    padding: EdgeInsets.zero),
                const SizedBox(width: 4),
                Chip(
                    label: Text(report.formatLabel,
                        style: const TextStyle(fontSize: 11)),
                    padding: EdgeInsets.zero),
              ],
            ),
          ],
        ),
        trailing: report.isCompleted
            ? IconButton(
                icon: const Icon(Icons.download),
                onPressed: () => _downloadReport(report))
            : const CircularProgressIndicator(),
      ),
    );
  }

  IconData _getFormatIcon(String format) {
    switch (format) {
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

  Color _getStatusColor(String status) {
    switch (status) {
      case 'completed':
        return Colors.green;
      case 'processing':
        return Colors.orange;
      case 'failed':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  Future<void> _downloadReport(Report report) async {
    try {
      final url = await _service.getDownloadUrl(report.id);
      if (url != null) {
        _showSuccess('Download dimulai');
      }
    } catch (e) {
      _showError('Gagal download laporan');
    }
  }

  void _showGenerateDialog() {
    showDialog(
      context: context,
      builder: (context) => const _GenerateReportDialog(),
    ).then((result) {
      if (result == true) _loadReports();
    });
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

class _GenerateReportDialog extends StatefulWidget {
  const _GenerateReportDialog();

  @override
  State<_GenerateReportDialog> createState() => _GenerateReportDialogState();
}

class _GenerateReportDialogState extends State<_GenerateReportDialog> {
  final ReportService _service = ReportService();
  final _titleController = TextEditingController();
  String _type = 'monthly';
  String _format = 'pdf';
  final DateTime _startDate = DateTime.now().subtract(const Duration(days: 30));
  final DateTime _endDate = DateTime.now();
  bool _isGenerating = false;

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Buat Laporan Baru'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
                controller: _titleController,
                decoration: const InputDecoration(labelText: 'Judul Laporan')),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              initialValue: _type,
              decoration: const InputDecoration(labelText: 'Tipe'),
              items: const [
                DropdownMenuItem(value: 'daily', child: Text('Harian')),
                DropdownMenuItem(value: 'weekly', child: Text('Mingguan')),
                DropdownMenuItem(value: 'monthly', child: Text('Bulanan')),
                DropdownMenuItem(value: 'custom', child: Text('Custom')),
              ],
              onChanged: (v) => setState(() => _type = v!),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              initialValue: _format,
              decoration: const InputDecoration(labelText: 'Format'),
              items: const [
                DropdownMenuItem(value: 'pdf', child: Text('PDF')),
                DropdownMenuItem(value: 'excel', child: Text('Excel')),
                DropdownMenuItem(value: 'csv', child: Text('CSV')),
              ],
              onChanged: (v) => setState(() => _format = v!),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Batal')),
        ElevatedButton(
          onPressed: _isGenerating ? null : _generateReport,
          child: _isGenerating
              ? const CircularProgressIndicator()
              : const Text('Generate'),
        ),
      ],
    );
  }

  Future<void> _generateReport() async {
    if (_titleController.text.isEmpty) return;

    setState(() => _isGenerating = true);
    try {
      await _service.generateReport(
        title: _titleController.text,
        type: _type,
        startDate: DateFormat('yyyy-MM-dd').format(_startDate),
        endDate: DateFormat('yyyy-MM-dd').format(_endDate),
        format: _format,
      );
      Navigator.pop(context, true);
    } catch (e) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text('Gagal: $e')));
    } finally {
      setState(() => _isGenerating = false);
    }
  }
}
