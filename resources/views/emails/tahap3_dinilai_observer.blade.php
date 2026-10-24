<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan: Penilaian Refleksi Tindak Lanjut Tahap 3 Telah Selesai - Menunggu Verifikasi</title>
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
                                Penilaian Refleksi Tindak Lanjut Selesai (Tahap 3)
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
                                Yth. Bapak/Ibu <strong>Wakasek Kurikulum / Tim Manajemen Sekolah</strong>,
                            </p>

                            <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                                Kami informasikan bahwa proses penilaian refleksi tindak lanjut observasi kinerja guru untuk <strong>Formulir Tahap 3</strong> telah selesai dinilai oleh Observer dan kini menunggu persetujuan (ACC):
                            </p>

                            <!-- Detail Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table width="100%" cellpadding="4" cellspacing="0" style="font-size: 13px; color: #065f46;">
                                            <tr>
                                                <td width="35%" style="font-weight: 600;">Nama Guru:</td>
                                                <td style="color: #047857; font-weight: 700;">{{ $guru->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Pengamat / Observer:</td>
                                                <td style="color: #047857; font-weight: 700;">{{ $observer->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Periode:</td>
                                                <td style="color: #047857;">{{ $periode }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Status Tahap 3:</td>
                                                <td>
                                                    <span style="display: inline-block; background-color: #d1fae5; color: #047857; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700;">
                                                        Menunggu Verifikasi (ACC)
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 14px; color: #475569; margin-bottom: 28px;">
                                Mohon untuk memeriksa hasil refleksi guru, penilaian observer, dan catatan peningkatan kinerja guru tersebut melalui halaman Persetujuan Kinerja di bawah ini:
                            </p>

                            <!-- Action Button -->
                            <div style="text-align: center; margin-bottom: 32px;">
                                <a href="{{ url('/kinerja-approval') }}" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 8px; box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);">
                                    Tinjau & Beri Persetujuan Tahap 3 &rarr;
                                </a>
                            </div>

                            <div style="border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 12px; color: #94a3b8;">
                                <p style="margin: 0;">
                                    Email ini dikirim secara otomatis oleh sistem SI-MONITORGURU setelah Observer menyelesaikan pengisian formulir Tahap 3. Harap tidak membalas email ini.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f1f5f9; padding: 16px 28px; text-align: center; font-size: 12px; color: #64748b;">
                            &copy; {{ date('Y') }} SI-MONITORGURU. Hak cipta dilindungi undang-undang.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
