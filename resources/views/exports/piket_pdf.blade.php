<!DOCTYPE html>
<html>
<head>
    <title>Laporan Rekapitulasi Piket</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: bold; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 2px; }
        p.subtitle { text-align: center; font-size: 11px; color: #666; margin-top: 0; margin-bottom: 15px; }
        .text-center { text-align: center; }
        .status-hadir { color: #059669; font-weight: bold; }
        .status-izin { color: #2563eb; font-weight: bold; }
        .status-sakit { color: #d97706; font-weight: bold; }
        .status-alpha { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Laporan Rekapitulasi Piket</h2>
    <p class="subtitle">Monitoring Kehadiran & Pelaksanaan Piket Kelas</p>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 70px;">Tanggal</th>
                <th>Guru Piket (Petugas)</th>
                <th>Titik Piket</th>
                <th>Guru Mengajar (Di Kelas)</th>
                <th>Mata Pelajaran</th>
                <th>Kelas</th>
                <th class="text-center" style="width: 50px;">Jam Ke</th>
                <th class="text-center" style="width: 60px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($laporan as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->jadwalPiket->tanggal_piket ?? '-' }}</td>
                <td><strong>{{ $item->jadwalPiket->guruPiket->name ?? '-' }}</strong></td>
                <td>{{ $item->jadwalPiket->titik->nama_titik ?? '-' }}</td>
                <td><strong>{{ $item->jadwalMapel->guru->name ?? '-' }}</strong></td>
                <td>{{ $item->jadwalMapel->mataPelajaran->nama_mapel ?? '-' }}</td>
                <td>
                    {{ $item->jadwalMapel->kelas->nama_kelas ?? '-' }}
                    @if(isset($item->jadwalMapel->kelas->jurusan))
                        <br><small style="color: #666;">({{ $item->jadwalMapel->kelas->jurusan->nama_jurusan }})</small>
                    @endif
                </td>
                <td class="text-center">
                    @if($item->jadwalMapel && $item->jadwalMapel->jamPelajarans)
                        {{ $item->jadwalMapel->jamPelajarans->pluck('urutan_jam')->join(', ') }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $statusClass = 'status-' . strtolower($item->status_kehadiran ?? '');
                    @endphp
                    <span class="{{ $statusClass }}">{{ $item->status_kehadiran ?? '-' }}</span>
                    @if($item->is_terlambat)
                        <br><small style="color: #ea580c;">(Terlambat)</small>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 20px; font-style: italic; color: #888;">
                    Tidak ada data laporan piket.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
