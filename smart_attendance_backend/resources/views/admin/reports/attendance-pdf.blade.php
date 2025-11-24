<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kehadiran</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Laporan Kehadiran</h1>
    <p>Periode: {{ $startDate }} - {{ $endDate }}</p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>Waktu Masuk</th>
                <th>Waktu Keluar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $index => $attendance)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $attendance->date }}</td>
                <td>{{ $attendance->user->name ?? '-' }}</td>
                <td>{{ $attendance->check_in ?? '-' }}</td>
                <td>{{ $attendance->check_out ?? '-' }}</td>
                <td>{{ $attendance->status ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>