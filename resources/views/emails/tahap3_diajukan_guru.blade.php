<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan: Refleksi Pasca Tindak Lanjut (Tahap 3) Telah Diajukan Guru</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 32px 28px; text-align: left;">
                            <div style="font-size: 28px; line-height: 1; margin-bottom: 8px;">📢</div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.02em;">
                                Refleksi Pasca Tindak Lanjut Guru (Tahap 3)
                            </h1>
                            <p style="margin: 6px 0 0; color: #d1fae5; font-size: 13px;">
                                SI-MONITORGURU • Sistem Observasi & Monitoring Kinerja
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 28px;">
                            <p style="margin-top: 0; font-size: 15px; color: #334155;">
                                Yth. Bapak/Ibu <strong>{{ $observer->name }}</strong> (Pengamat / Observer),
                            </p>

                            <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                                Melalui email ini kami informasikan bahwa rekan guru berikut telah selesai mengisi dan mengajukan formulir <strong>Refleksi Pasca Tindak Lanjut Observasi Kinerja (Tahap 3)</strong>:
                            </p>

                            <!-- Detail Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table width="100%" cellpadding="4" cellspacing="0" style="font-size: 13px; color: #065f46;">
                                            <tr>
                                                <td width="35%" style="font-weight: 600;">Nama Guru:</td>
                                                <td style="color: #064e3b; font-weight: 700;">{{ $guru->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Periode:</td>
                                                <td style="color: #064e3b;">{{ $periode }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Status Tahap 3:</td>
                                                <td>
                                                    <span style="display: inline-block; background-color: #fef3c7; color: #92400e; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700;">
                                                        Menunggu Penilaian Observer
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 14px; color: #334155; margin-bottom: 20px;">
                                <strong>Tindakan yang Diperlukan:</strong><br>
                                Harap Bapak/Ibu segera membuka formulir untuk mengisi <strong>Jadwal Pelaksanaan Diskusi</strong>, memberikan <strong>Penilaian Kesadaran Refleksi</strong>, <strong>Upaya Kompetensi</strong>, <strong>Peningkatan Kinerja</strong>, serta <strong>Catatan Kualitatif Observer</strong>.
                            </p>

                            <!-- Motivation Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fefce8; border-left: 4px solid #eab308; border-radius: 8px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 13px; font-style: italic; color: #854d0e; line-height: 1.5;">
                                            🤝 <strong>Kata Motivasi untuk Pengamat:</strong><br>
                                            "Apresiasi dan umpan balik yang konstruktif dari Pengamat adalah dorongan terbaik bagi guru untuk terus berinovasi dalam praktik pembelajaran di kelas!"
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 28px 0 12px;">
                                <a href="{{ url('/kinerja/' . $guru->id . '/tahap-3') }}" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3);">
                                    Buka Formulir Tahap 3 &rarr;
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
