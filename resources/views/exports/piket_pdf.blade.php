<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Rekapitulasi Piket Kelas</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
            size: a4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 15px;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }
        .header h2 {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 2px 0;
            color: #1e293b;
        }
        .header p {
            font-size: 9.5px;
            color: #64748b;
            margin: 0;
        }
        .divider {
            border-top: 2px solid #0f172a;
            border-bottom: 1px solid #0f172a;
            height: 2px;
            margin: 8px 0 12px 0;
        }
        .meta-box {
            width: 100%;
            margin-bottom: 12px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 9.5px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px 4px;
            border: none;
            vertical-align: top;
        }
        .meta-label {
            width: 110px;
            font-weight: bold;
            color: #334155;
        }
        .meta-colon {
            width: 10px;
            text-align: center;
        }
        .meta-value {
            color: #0f172a;
        }
        .badge-stat {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
            margin-right: 4px;
        }
        .stat-hadir { background-color: #d1fae5; color: #065f46; }
        .stat-terlambat { background-color: #ffedd5; color: #9a3412; }
        .stat-izin { background-color: #dbeafe; color: #1e40af; }
        .stat-sakit { background-color: #fef3c7; color: #92400e; }
        .stat-alpha { background-color: #fee2e2; color: #991b1b; }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #94a3b8;
            padding: 4px 6px;
            text-align: left;
            vertical-align: middle;
        }
        table.data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            font-size: 9.5px;
            text-align: center;
            text-transform: uppercase;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .status-badge {
            font-weight: bold;
            font-size: 9.5px;
        }
        .status-hadir { color: #059669; }
        .status-izin { color: #2563eb; }
        .status-sakit { color: #d97706; }
        .status-alpha { color: #dc2626; }
        .terlambat-note {
            color: #ea580c;
            font-size: 8.5px;
            font-weight: bold;
            display: block;
        }
        .piket-tag {
            color: #64748b;
            font-size: 8.5px;
            font-style: italic;
            display: block;
        }
        .signature-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
    </style>
</head>
<body>
    <!-- Kop Surat -->
    <div class="header">
        <h1>{{ $settingan->nama_instansi ?? 'SISTEM MONITORING GURU' }}</h1>
        <h2>LAPORAN REKAPITULASI PIKET KELAS</h2>
        <p>Monitoring Kehadiran & Pelaksanaan Piket Kelas Berdasarkan Jadwal</p>
    </div>
    <div class="divider"></div>

    <!-- Kotak Informasi Filter & Periode -->
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Periode Filter</td>
                <td class="meta-colon">:</td>
                <td class="meta-value"><strong>{{ $periodeText }}</strong></td>
                <td class="meta-label">Tanggal Cetak</td>
                <td class="meta-colon">:</td>
                <td class="meta-value">{{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB</td>
            </tr>
            <tr>
                <td class="meta-label">Kata Kunci</td>
                <td class="meta-colon">:</td>
                <td class="meta-value">{{ !empty($filters['search']) ? $filters['search'] : '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Ringkasan Data</td>
                <td class="meta-colon">:</td>
                <td colspan="4" class="meta-value">
                    <strong>Total: {{ count($laporan) }} Data</strong> &nbsp;|&nbsp;
                    <span class="badge-stat stat-hadir">Hadir: {{ $totalHadir ?? 0 }}</span>
                    <span class="badge-stat stat-terlambat">Terlambat: {{ $totalTerlambat ?? 0 }}</span>
                    <span class="badge-stat stat-izin">Izin: {{ $totalIzin ?? 0 }}</span>
                    <span class="badge-stat stat-sakit">Sakit: {{ $totalSakit ?? 0 }}</span>
                    <span class="badge-stat stat-alpha">Alpha: {{ $totalAlpha ?? 0 }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabel Data Rekapitulasi -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 120px;">Guru Piket (Petugas)</th>
                <th style="width: 80px;">Titik Piket</th>
                <th style="width: 120px;">Guru Mengajar (Di Kelas)</th>
                <th style="width: 110px;">Mata Pelajaran</th>
                <th style="width: 85px;">Kelas & Jurusan</th>
                <th class="text-center" style="width: 45px;">Jam Ke</th>
                <th class="text-center" style="width: 70px;">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($laporan as $index => $item)
            @php
                $jp = $item->jadwal_piket ?? $item->jadwalPiket ?? null;
                $jm = $item->jadwal_mapel ?? $item->jadwalMapel ?? null;
                $tanggalFormatted = '-';
                if (!empty($jp->tanggal_piket)) {
                    try {
                        $tanggalFormatted = \Carbon\Carbon::parse($jp->tanggal_piket)->translatedFormat('d/m/Y');
                    } catch (\Exception $e) {
                        $tanggalFormatted = $jp->tanggal_piket;
                    }
                }
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $tanggalFormatted }}</td>
                <td><strong>{{ $jp->guruPiket->name ?? '-' }}</strong></td>
                <td>{{ $jp->titik->nama_titik ?? '-' }}</td>
                <td>
                    @if($jm && $jm->guru)
                        <strong>{{ $jm->guru->name }}</strong>
                    @else
                        <span style="color: #94a3b8;">-</span>
                    @endif
                </td>
                <td>{{ $jm?->mataPelajaran?->nama_mapel ?? '-' }}</td>
                <td>
                    @if($jm && $jm->kelas)
                        {{ $jm->kelas->nama_kelas }}
                        @if(isset($jm->kelas->jurusan))
                            <br><small style="color: #64748b;">({{ $jm->kelas->jurusan->nama_jurusan }})</small>
                        @endif
                    @else
                        <span style="color: #94a3b8;">-</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($jm && $jm->jamPelajarans && $jm->jamPelajarans->count() > 0)
                        {{ $jm->jamPelajarans->pluck('urutan_jam')->join(', ') }}
                    @elseif($jp && $jp->jamPelajarans && $jp->jamPelajarans->count() > 0)
                        {{ $jp->jamPelajarans->pluck('urutan_jam')->join(', ') }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $statusClass = 'status-' . strtolower($item->status_kehadiran ?? '');
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        {{ $item->status_kehadiran ?? '-' }}
                    </span>
                    @if(!empty($item->is_piket_petugas))
                        <span class="piket-tag">(Petugas Piket)</span>
                    @endif
                    @if(!empty($item->is_terlambat))
                        <span class="terlambat-note">(Terlambat)</span>
                    @endif
                </td>
                <td>{{ $item->keterangan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 20px; font-style: italic; color: #64748b;">
                    Tidak ada data laporan piket yang sesuai dengan filter yang dipilih.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Tanda Tangan Pengesahan -->
    <table class="signature-table">
        <tr>
            <td style="width: 65%;"></td>
            <td style="width: 35%; text-align: center;">
                <p style="margin: 0 0 4px 0;">Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>