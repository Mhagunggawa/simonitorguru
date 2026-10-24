<!DOCTYPE html>
<html>
<head>
    <title>Laporan Tindak Lanjut Kedisiplinan Guru</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: bold; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 2px; }
        p.subtitle { text-align: center; font-size: 11px; color: #666; margin-top: 0; margin-bottom: 15px; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-weight: bold; font-size: 10px; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        .badge-orange { background-color: #ffedd5; color: #c2410c; }
        .badge-success { background-color: #d1fae5; color: #047857; }
    </style>
</head>
<body>
    <h2>Laporan Tindak Lanjut Kedisiplinan Guru</h2>
    <p class="subtitle">Rekapitulasi Ketidakhadiran (Alpha) & Surat Peringatan (SP)</p>
    
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th>Nama Guru</th>
                <th>Tipe Guru</th>
                <th class="text-center" style="width: 80px;">Total Alpha</th>
                <th style="width: 140px;">Status Tindak Lanjut</th>
                <th>Surat Peringatan (SP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($laporan as $index => $item)
            @php
                $spHistory = $item['sp_history'] ?? [];
                $hasSp3 = collect($spHistory)->contains('tipe_peringatan', 'SP 3');
                $hasSp2 = collect($spHistory)->contains('tipe_peringatan', 'SP 2');
                $hasSp1 = collect($spHistory)->contains('tipe_peringatan', 'SP 1');
                $spList = collect($spHistory)->pluck('tipe_peringatan')->join(', ');
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $item['nama'] ?? '-' }}</strong></td>
                <td>{{ $item['tipe_guru'] ?? '-' }}</td>
                <td class="text-center">
                    @if(($item['total_alpha'] ?? 0) > 0)
                        <span style="color: #dc2626; font-weight: bold;">{{ $item['total_alpha'] }}x Alpha</span>
                    @else
                        <span style="color: #059669;">Disiplin (0)</span>
                    @endif
                </td>
                <td>
                    @if($hasSp3)
                        <span class="badge badge-danger">Terkena SP 3</span>
                    @elseif($hasSp2)
                        <span class="badge badge-orange">Terkena SP 2</span>
                    @elseif($hasSp1)
                        <span class="badge badge-warning">Terkena SP 1</span>
                    @elseif(($item['total_alpha'] ?? 0) === 1)
                        <span class="badge badge-warning">Teguran Lisan</span>
                    @elseif(($item['total_alpha'] ?? 0) >= 2)
                        <span style="color: #666; font-style: italic;">Perlu Tindakan Wakasek</span>
                    @else
                        <span class="badge badge-success">Disiplin</span>
                    @endif
                </td>
                <td>{{ $spList ?: '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 15px; color: #666;">Tidak ada data guru yang ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
