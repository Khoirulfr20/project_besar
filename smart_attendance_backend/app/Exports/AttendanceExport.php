<?php

// ============================================
// File: app/Exports/AttendanceExport.php
// ============================================
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AttendanceExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithEvents
{
    protected $attendances;
    protected $statistics;
    protected $startDate;
    protected $endDate;

    public function __construct($attendances, $statistics, $startDate, $endDate)
    {
        $this->attendances = $attendances;
        $this->statistics = $statistics;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Data yang akan di-export
     */
    public function collection()
    {
        return $this->attendances->map(function($att, $index) {
            return [
                'no' => $index + 1,
                'tanggal' => $att->date->format('d/m/Y'),
                'nama' => $att->user->name,
                'id_karyawan' => $att->user->employee_id,
                'departemen' => $att->user->department ?? '-',
                'check_in' => $att->check_in_time ?? '-',
                'check_out' => $att->check_out_time ?? '-',
                'durasi' => $att->work_duration 
                    ? floor($att->work_duration / 60) . 'j ' . ($att->work_duration % 60) . 'm'
                    : '-',
                'status' => $this->getStatusLabel($att->status),
            ];
        });
    }

    /**
     * Header kolom
     */
    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Nama',
            'ID Karyawan',
            'Departemen',
            'Check-In',
            'Check-Out',
            'Durasi Kerja',
            'Status',
        ];
    }

    /**
     * Styling untuk sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk header (row 1)
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Nama sheet
     */
    public function title(): string
    {
        return 'Laporan Kehadiran';
    }

    /**
     * Events untuk styling tambahan
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Get highest row dan column
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                // Border untuk semua data
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Center align untuk kolom No
                $sheet->getStyle('A2:A' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Center align untuk kolom Check-in, Check-out, Status
                $sheet->getStyle('F2:F' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G2:G' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('I2:I' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Tambahkan summary di bawah tabel
                $summaryRow = $highestRow + 2;
                
                $sheet->setCellValue('A' . $summaryRow, 'RINGKASAN STATISTIK');
                $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
                
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'Total Data: ' . $this->statistics['total']);
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'Hadir: ' . $this->statistics['present']);
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'Terlambat: ' . $this->statistics['late']);
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'Izin: ' . $this->statistics['excused']);
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'Tidak Hadir: ' . $this->statistics['absent']);
                $summaryRow++;
                $sheet->setCellValue('A' . $summaryRow, 'Cuti: ' . $this->statistics['leave']);
                
                // Tambahkan periode laporan
                $summaryRow += 2;
                $sheet->setCellValue('A' . $summaryRow, 'Periode: ' . date('d/m/Y', strtotime($this->startDate)) . ' - ' . date('d/m/Y', strtotime($this->endDate)));
                $sheet->getStyle('A' . $summaryRow)->getFont()->setItalic(true);
            },
        ];
    }

    /**
     * Helper untuk convert status ke label Indonesia
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'present' => 'Hadir',
            'late' => 'Terlambat',
            'absent' => 'Tidak Hadir',
            'excused' => 'Izin',
            'leave' => 'Cuti',
        ];
        
        return $labels[$status] ?? $status;
    }
}