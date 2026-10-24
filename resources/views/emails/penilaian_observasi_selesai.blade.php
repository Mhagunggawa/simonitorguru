<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan: Penilaian Observasi Kinerja Telah Selesai</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); padding: 32px 28px; text-align: left;">
                            <div style="font-size: 28px; line-height: 1; margin-bottom: 8px;">📢</div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.02em;">
                                Pemberitahuan Observasi Kinerja Guru
                            </h1>
                            <p style="margin: 6px 0 0; color: #fef3c7; font-size: 13px;">
                                SI-MONITORGURU • Sistem Observasi & Monitoring Kinerja
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 28px;">
                            <p style="margin-top: 0; font-size: 15px; color: #334155;">
                                Yth. Bapak/Ibu <strong>{{ $guru->name }}</strong>,
                            </p>

                            <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                                Kami informasikan bahwa pengamat / observer Anda, Bapak/Ibu <strong>{{ $observer->name }}</strong>, telah selesai melaksanakan pengamatan kelas dan memberikan <strong>Penilaian Observasi Kinerja serta Rekomendasi (Tahap 1)</strong> terhadap praktik pembelajaran Anda.
                            </p>

                            <!-- Detail Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table width="100%" cellpadding="4" cellspacing="0" style="font-size: 13px; color: #92400e;">
                                            <tr>
                                                <td width="35%" style="font-weight: 600;">Nama Pengamat:</td>
                                                <td style="color: #78350f; font-weight: 700;">{{ $observer->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Periode:</td>
                                                <td style="color: #78350f;">{{ $periode }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Status Tahap:</td>
                                                <td>
                                                    <span style="display: inline-block; background-color: #dbeafe; color: #1e40af; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700;">
                                                        Menunggu Unggah Video Observasi
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 14px; color: #334155; margin-bottom: 20px;">
                                <strong>Langkah Selanjutnya:</strong><br>
                                Harap segera mengirimkan dan mengunggah link Google Drive dari video rekaman observasi pembelajaran Anda ke folder Google Drive yang telah disediakan oleh pihak <strong>Kurikulum</strong>, kemudian tautkan link drive video tersebut pada formulir kinerja Anda untuk diajukan ke proses verifikasi (ACC).
                            </p>

                            <!-- Motivation Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #ecfdf5; border-left: 4px solid #10b981; border-radius: 8px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 13px; font-style: italic; color: #065f46; line-height: 1.5;">
                                            ✨ <strong>Kata Motivasi untuk Guru:</strong><br>
                                            "Terima kasih atas kerja keras, antusiasme, dan dedikasi luar biasa yang telah Bapak/Ibu tunjukkan di dalam kelas. Setiap observasi adalah kesempatan emas untuk terus tumbuh, berinovasi, dan menginspirasi murid-murid tercinta. Bangga atas dedikasi tanpa lelah Bapak/Ibu demi masa depan generasi penerus bangsa!"
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 28px 0 12px;">
                                <a href="{{ url('/kinerja/' . $guru->id . '/tahap-1') }}" style="display: inline-block; background-color: #d97706; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);">
                                    Unggah Link Video Observasi &rarr;
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
