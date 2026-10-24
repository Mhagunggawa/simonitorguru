<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat: Formulir Tahap 3 Telah Disetujui (ACC)</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 32px 28px; text-align: left;">
                            <div style="font-size: 28px; line-height: 1; margin-bottom: 8px;">🎉</div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.02em;">
                                Tahap 3 Disetujui (ACC)
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
                                Yth. <strong>{{ $guru->name }}</strong>,
                            </p>

                            <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                                Selamat! Formulir <strong>Tahap 3: Refleksi Tindak Lanjut Observasi Kinerja Guru</strong> Anda untuk periode <strong>{{ $periode }}</strong> telah diverifikasi dan <strong>disetujui (ACC)</strong> oleh pimpinan/manajemen sekolah.
                            </p>

                            <!-- Detail Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table width="100%" cellpadding="4" cellspacing="0" style="font-size: 13px; color: #166534;">
                                            <tr>
                                                <td width="35%" style="font-weight: 600;">Disetujui Oleh:</td>
                                                <td style="color: #14532d; font-weight: 700;">{{ $approver->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Periode:</td>
                                                <td style="color: #14532d;">{{ $periode }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Status Akhir:</td>
                                                <td>
                                                    <span style="display: inline-block; background-color: #dcfce7; color: #15803d; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700;">
                                                        Selesai & Disetujui Penuh
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 14px; color: #475569; margin-bottom: 28px;">
                                Seluruh rangkaian siklus observasi kinerja Anda pada periode ini telah tuntas. Anda dapat melihat kembali salinan lengkap hasil penilaian kinerja Anda melalui tautan di bawah ini:
                            </p>

                            <!-- Action Button -->
                            <div style="text-align: center; margin-bottom: 32px;">
                                <a href="{{ url('/kinerja') }}" style="display: inline-block; background-color: #10b981; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 8px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);">
                                    Lihat Ringkasan Kinerja Anda &rarr;
                                </a>
                            </div>

                            <div style="border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 12px; color: #94a3b8;">
                                <p style="margin: 0;">
                                    Email ini dikirim secara otomatis oleh SI-MONITORGURU setelah pengajuan Tahap 3 disetujui.
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
