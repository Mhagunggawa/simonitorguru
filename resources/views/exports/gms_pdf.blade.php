<!DOCTYPE html>
<html>
<head>
    <title>Laporan GMS</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>Laporan GMS</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Guru</th>
                <th>Waktu Absen</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($laporan as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->tanggal ?? '-' }}</td>
                <td>{{ $item->user->name ?? '-' }}</td>
                <td>{{ $item->waktu_absen ?? '-' }}</td>
                <td>{{ $item->status ?? '-' }}</td>
                <td>{{ $item->keterangan ?? ($item->foto_bukti ? 'Absen Mandiri (Foto)' : '-') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
