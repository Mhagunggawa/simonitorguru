<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan: Refleksi Tindak Lanjut Kinerja (Tahap 2) Telah Diajukan Guru</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 32px 28px; text-align: left;">
                            <div style="font-size: 28px; line-height: 1; margin-bottom: 8px;">📢</div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.02em;">
                                Refleksi Tindak Lanjut Guru (Tahap 2)
                            </h1>
                            <p style="margin: 6px 0 0; color: #dbeafe; font-size: 13px;">
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
                                Melalui email ini kami informasikan bahwa rekan guru berikut telah selesai mengisi dan mengajukan formulir <strong>Refleksi Tindak Lanjut Observasi Kinerja (Tahap 2)</strong>:
                            </p>

                            <!-- Detail Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table width="100%" cellpadding="4" cellspacing="0" style="font-size: 13px; color: #1e40af;">
                                            <tr>
                                                <td width="35%" style="font-weight: 600;">Nama Guru:</td>
                                                <td style="color: #172554; font-weight: 700;">{{ $guru->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Periode:</td>
                                                <td style="color: #172554;">{{ $periode }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Status Tahap 2:</td>
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
                                Harap Bapak/Ibu segera membuka formulir untuk mengisi <strong>Identitas/Jadwal Tindak Lanjut</strong>, memberikan <strong>Catatan Pengamat / Kepala Sekolah</strong>, serta menentukan <strong>Penilaian Kesadaran Refleksi Guru</strong>.
                            </p>

                            <!-- Motivation Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fefce8; border-left: 4px solid #eab308; border-radius: 8px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 13px; font-style: italic; color: #854d0e; line-height: 1.5;">
                                            🤝 <strong>Kata Motivasi untuk Pengamat:</strong><br>
                                            "Bimbingan dan dialog reflektif Bapak/Ibu bersama rekan guru adalah jembatan emas bagi peningkatan kualitas pembelajaran. Terima kasih atas dedikasi tanpa henti dalam membersamai proses pengembangan kompetensi para guru kita!"
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 28px 0 12px;">
                                <a href="{{ url('/kinerja/' . $guru->id . '/tahap-2') }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);">
                                    Buka Formulir Tindak Lanjut &rarr;
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
