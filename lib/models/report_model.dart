// ============================================
// Report Model
// ============================================
class Report {
  final int id;
  final String title;
  final String type;
  final DateTime startDate;
  final DateTime endDate;
  final String format;
  final String? filePath;
  final int? fileSize;
  final Map<String, dynamic>? filters;
  final Map<String, dynamic>? summary;
  final int generatedBy;
  final String status;
  final String? errorMessage;
  final DateTime createdAt;

  Report({
    required this.id,
    required this.title,
    required this.type,
    required this.startDate,
    required this.endDate,
    required this.format,
    this.filePath,
    this.fileSize,
    this.filters,
    this.summary,
    required this.generatedBy,
    required this.status,
    this.errorMessage,
    required this.createdAt,
  });

  factory Report.fromJson(Map<String, dynamic> json) {
    return Report(
      id: json['id'],
      title: json['title'],
      type: json['type'],
      startDate: DateTime.parse(json['start_date']),
      endDate: DateTime.parse(json['end_date']),
      format: json['format'],
      filePath: json['file_path'],
      fileSize: json['file_size'],
      filters: json['filters'],
      summary: json['summary'],
      generatedBy: json['generated_by'],
      status: json['status'],
      errorMessage: json['error_message'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  bool get isPending => status == 'pending';
  bool get isProcessing => status == 'processing';
  bool get isCompleted => status == 'completed';
  bool get isFailed => status == 'failed';

  String get typeLabel {
    switch (type) {
      case 'daily':
        return 'Harian';
      case 'weekly':
        return 'Mingguan';
      case 'monthly':
        return 'Bulanan';
      case 'custom':
        return 'Custom';
      default:
        return type;
    }
  }

  String get formatLabel {
    switch (format) {
      case 'pdf':
        return 'PDF';
      case 'excel':
        return 'Excel';
      case 'csv':
        return 'CSV';
      default:
        return format.toUpperCase();
    }
  }

  String get fileSizeFormatted {
    if (fileSize == null) return '-';
    if (fileSize! < 1024) return '$fileSize B';
    if (fileSize! < 1024 * 1024) return '${(fileSize! / 1024).toStringAsFixed(2)} KB';
    return '${(fileSize! / (1024 * 1024)).toStringAsFixed(2)} MB';
  }
}

