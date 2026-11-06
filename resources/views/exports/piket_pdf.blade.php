<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Rekapitulasi Piket Per Sesi</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
            size: a4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
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
            font-size: 9px;
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
            font-size: 9px;
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
            font-size: 8.5px;
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
            font-size: 9px;
            text-align: center;
            text-transform: uppercase;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .status-badge {
            font-weight: bold;
            font-size: 9px;
        }
        .status-hadir { color: #059669; }
        .status-izin { color: #2563eb; }
        .status-sakit { color: #d97706; }
        .status-alpha { color: #dc2626; }
        .terlambat-note {
            color: #ea580c;
            font-size: 8.5px;
            font-weight: bold;
        }
        .piket-tag {
            color: #64748b;
            font-size: 8px;
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
        <h2>LAPORAN REKAPITULASI PIKET KELAS PER SESI</h2>
        <p>Monitoring Kehadiran & Pelaksanaan Pemantauan Kelas Berdasarkan Jadwal Shift Piket</p>
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
                <td class="meta-label">Ringkasan Sesi</td>
                <td class="meta-colon">:</td>
                <td colspan="4" class="meta-value">
                    <strong>Total: {{ count($laporan) }} Sesi Piket</strong> &nbsp;|&nbsp;
                    <span class="badge-stat stat-hadir">Hadir: {{ $totalHadir ?? 0 }}</span>
                    <span class="badge-stat stat-terlambat">Terlambat: {{ $totalTerlambat ?? 0 }}</span>
                    <span class="badge-stat stat-izin">Izin: {{ $totalIzin ?? 0 }}</span>
                    <span class="badge-stat stat-sakit">Sakit: {{ $totalSakit ?? 0 }}</span>
                    <span class="badge-stat stat-alpha">Alpha: {{ $totalAlpha ?? 0 }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabel Data Rekapitulasi Per Sesi -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 65px;">Tanggal</th>
                <th style="width: 75px;">Jam Piket</th>
                <th style="width: 110px;">Guru Piket (Titik)</th>
                <th style="width: 80px;">Kelas</th>
                <th style="width: 110px;">Mata Pelajaran</th>
                <th style="width: 110px;">Guru Mengajar (Di Kelas)</th>
                <th class="text-center" style="width: 60px;">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php $sessionNum = 0; @endphp
            @forelse($laporan as $sesi)
                @php
                    $sessionNum++;
                    $jp = $sesi->jadwal_piket ?? null;
                    $tanggalFormatted = '-';
                    if (!empty($jp?->tanggal_piket)) {
                        try {
                            $tanggalFormatted = \Carbon\Carbon::parse($jp->tanggal_piket)->translatedFormat('d/m/Y');
                        } catch (\Exception $e) {
                            $tanggalFormatted = $jp->tanggal_piket;
                        }
                    }
                    $guruPiket = $jp?->guruPiket?->name ?? ($jp?->guru_piket?->name ?? '-');
                    $titik = $jp?->titik?->nama_titik ?? '-';
                    $totalClasses = count($sesi->classes);
                @endphp

                @if(!empty($sesi->is_piket_petugas))
                    <tr style="background-color: #fffbeb;">
                        <td class="text-center">{{ $sessionNum }}</td>
                        <td>{{ $tanggalFormatted }}</td>
                        <td>{{ $sesi->jam_str }}</td>
                        <td><strong>{{ $guruPiket }}</strong><br><small style="color: #64748b;">Titik: {{ $titik }}</small></td>
                        <td colspan="3" style="font-style: italic; color: #b45309;">
                            Petugas piket tidak mengisi pemantauan kelas ({{ $sesi->status_kehadiran }})
                        </td>
                        <td class="text-center">
                            <span class="status-badge status-{{ strtolower($sesi->status_kehadiran) }}">{{ $sesi->status_kehadiran }}</span>
                            <span class="piket-tag">(Petugas)</span>
                        </td>
                        <td>{{ $sesi->keterangan ?? '-' }}</td>
                    </tr>
                @else
                    {{-- Header Sesi Bar --}}
                    <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #94a3b8;">
                        <td class="text-center">{{ $sessionNum }}</td>
                        <td>{{ $tanggalFormatted }}</td>
                        <td>{{ $sesi->jam_str }}</td>
                        <td>
                            <strong>{{ $guruPiket }}</strong><br>
                            <small style="color: #475569; font-weight: normal;">Titik: {{ $titik }}</small>
                        </td>
                        <td colspan="5">
                            <span style="color: #0f172a;">Ringkasan Shift:</span> 
                            <span style="color: #0369a1;">{{ $sesi->ringkasan_teks }}</span>
                            @if(!empty($sesi->is_terlambat))
                                <span class="terlambat-note" style="margin-left: 8px;">(Laporan Terlambat)</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Rincian Kelas di Bawah Sesi --}}
                    @foreach($sesi->classes as $cIdx => $c)
                        <tr>
                            <td style="border: none; background: #ffffff;"></td>
                            <td colspan="3" style="text-align: right; color: #94a3b8; font-size: 8px; padding-right: 8px;">
                                Kelas {{ $cIdx + 1 }} dari {{ $totalClasses }} &rarr;
                            </td>
                            <td>
                                <strong>{{ $c->kelas_nama }}</strong>
                                @if(!empty($c->jurusan_nama))
                                    <br><small style="color: #64748b;">({{ $c->jurusan_nama }})</small>
                                @endif
                            </td>
                            <td>{{ $c->mapel_nama }}</td>
                            <td>{{ $c->guru_nama }}</td>
                            <td class="text-center">
                                <span class="status-badge status-{{ strtolower($c->status_kehadiran) }}">
                                    {{ $c->status_kehadiran }}
                                </span>
                            </td>
                            <td>{{ $c->keterangan ?: '-' }}</td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; font-style: italic; color: #64748b;">
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
