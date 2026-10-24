<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan: Video Observasi Diunggah - Menunggu Verifikasi</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); padding: 32px 28px; text-align: left;">
                            <div style="font-size: 28px; line-height: 1; margin-bottom: 8px;">📢</div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.02em;">
                                Pemberitahuan Verifikasi Observasi Kinerja
                            </h1>
                            <p style="margin: 6px 0 0; color: #ede9fe; font-size: 13px;">
                                SI-MONITORGURU • Sistem Observasi & Monitoring Kinerja
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 28px;">
                            <p style="margin-top: 0; font-size: 15px; color: #334155;">
                                Yth. Bapak/Ibu <strong>Wakasek Kurikulum / Tim Manajemen Sekolah</strong>,
                            </p>

                            <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                                Kami informasikan bahwa guru berikut telah selesai melengkapi dan mengunggah bukti video rekaman observasi pembelajaran untuk <strong>Formulir Observasi Kinerja Tahap 1</strong>:
                            </p>

                            <!-- Detail Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table width="100%" cellpadding="4" cellspacing="0" style="font-size: 13px; color: #6d28d9;">
                                            <tr>
                                                <td width="35%" style="font-weight: 600;">Nama Guru:</td>
                                                <td style="color: #4c1d95; font-weight: 700;">{{ $guru->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Pengamat / Observer:</td>
                                                <td style="color: #4c1d95; font-weight: 700;">{{ $observer->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Periode:</td>
                                                <td style="color: #4c1d95;">{{ $periode }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Status Saat Ini:</td>
                                                <td>
                                                    <span style="display: inline-block; background-color: #ede9fe; color: #6d28d9; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700;">
                                                        Menunggu Verifikasi (ACC)
                                                    </span>
                                                </td>
                                            </tr>
                                            @if(!empty($videoLink))
                                            <tr>
                                                <td style="font-weight: 600;">Link Video Observasi:</td>
                                                <td>
                                                    <a href="{{ $videoLink }}" target="_blank" style="color: #7c3aed; font-weight: 600; word-break: break-all; text-decoration: underline;">
                                                        Buka Tautan Video &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 14px; color: #334155; margin-bottom: 24px;">
                                Mohon kesediaan Bapak/Ibu untuk meninjau formulir kinerja yang telah disusun oleh guru beserta hasil pengamatan dan rekomendasi yang diberikan oleh pengamat <strong>{{ $observer->name }}</strong>, lalu melakukan persetujuan (ACC) pada menu <strong>Persetujuan Kinerja</strong> agar proses dapat dilanjutkan ke Tahap 2.
                            </p>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 28px 0 12px;">
                                <a href="{{ url('/kinerja-approval') }}" style="display: inline-block; background-color: #7c3aed; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(124, 58, 237, 0.3);">
                                    Buka Menu Persetujuan Kinerja &rarr;
                                </a>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 28px; text-align: center; font-size: 12px; color: #64748b;">
                            <p style="margin: 0;">
                                Email otomatis dari <strong>{{ config('app.name', 'SI-MONITORGURU') }}</strong>. Mohon tidak membalas email ini secara langsung.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
