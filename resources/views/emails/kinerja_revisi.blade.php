<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan: Formulir Observasi Kinerja Memerlukan Revisi</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); padding: 32px 28px; text-align: left;">
                            <div style="font-size: 28px; line-height: 1; margin-bottom: 8px;">📢</div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.02em;">
                                Formulir Observasi Kinerja Perlu Direvisi
                            </h1>
                            <p style="margin: 6px 0 0; color: #ffe4e6; font-size: 13px;">
                                SI-MONITORGURU • Sistem Observasi & Monitoring Kinerja
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 28px;">
                            <p style="margin-top: 0; font-size: 15px; color: #334155;">
                                Yth. Bapak/Ibu <strong>{{ $recipient->name }}</strong>,
                            </p>

                            @if($target === 'keduanya')
                                @if($role === 'guru')
                                    <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                                        Pemberitahuan bahwa <strong>Formulir Observasi Kinerja (Tahap {{ $tahap }})</strong> Anda untuk periode <strong>{{ $periode }}</strong> telah ditinjau dan <strong>ditolak untuk direvisi</strong>. Mohon untuk mengecek formulir Anda serta melakukan diskusi kembali dengan observer (<strong>{{ $observer->name ?? 'Observer' }}</strong>) dan menyertakan keterangan perbaikan setelahnya.
                                    </p>
                                @else
                                    <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                                        Pemberitahuan bahwa <strong>Formulir Observasi Kinerja (Tahap {{ $tahap }})</strong> untuk guru <strong>{{ $guru->name }}</strong> pada periode <strong>{{ $periode }}</strong> telah ditinjau dan <strong>memerlukan revisi bersama</strong>. Mohon untuk segera melakukan koordinasi dan diskusi kembali bersama guru bersangkutan untuk menyelaraskan perbaikan yang diperlukan.
                                    </p>
                                @endif
                            @elseif($role === 'guru')
                                <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                                    Pemberitahuan bahwa <strong>Formulir Observasi Kinerja (Tahap {{ $tahap }})</strong> yang Anda isi pada periode <strong>{{ $periode }}</strong> telah ditinjau dan <strong>ditolak untuk direvisi</strong>. Mohon untuk segera mengubah atau mengedit isi form tersebut dan menyimpannya kembali.
                                </p>
                            @else
                                <p style="font-size: 14px; color: #475569; margin-bottom: 20px;">
                                    Pemberitahuan bahwa formulir penilaian observasi kinerja (Tahap {{ $tahap }}) untuk guru <strong>{{ $guru->name }}</strong> pada periode <strong>{{ $periode }}</strong> telah ditinjau dan <strong>ditolak untuk direvisi</strong>. Mohon untuk segera mengedit dan menyimpannya kembali.
                                </p>
                            @endif

                            <!-- Detail Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table width="100%" cellpadding="4" cellspacing="0" style="font-size: 13px; color: #881337;">
                                            <tr>
                                                <td width="35%" style="font-weight: 600;">Guru Dinilai:</td>
                                                <td style="color: #9f1239; font-weight: 700;">{{ $guru->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Observer / Pengamat:</td>
                                                <td style="color: #9f1239;">{{ $observer->name ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Tahap:</td>
                                                <td style="color: #9f1239;">Tahap {{ $tahap }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Sisi Revisi:</td>
                                                <td>
                                                    <span style="display: inline-block; background-color: #ffe4e6; color: #be123c; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700;">
                                                        @if($target === 'guru')
                                                            Guru yang Diamati
                                                        @elseif($target === 'observer')
                                                            Observer / Pengamat
                                                        @else
                                                            Keduanya (Guru & Observer)
                                                        @endif
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Ditinjau Oleh:</td>
                                                <td style="color: #9f1239;">{{ $rejecter->name ?? 'Wakasek Kurikulum' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Catatan Revisi Box -->
                            <div style="background-color: #fff7ed; border-left: 4px solid #ea580c; border-radius: 8px; padding: 16px 18px; margin-bottom: 24px;">
                                <h4 style="margin: 0 0 6px; font-size: 13px; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.03em;">
                                    📝 Catatan Revisi & Keterangan Penolakan:
                                </h4>
                                <p style="margin: 0; font-size: 14px; color: #7c2d12; line-height: 1.6; white-space: pre-line;">{{ $catatanRevisi ?: 'Harap periksa kembali isian formulir dan diskusikan poin-poin yang belum sesuai.' }}</p>
                            </div>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 28px 0 20px;">
                                <a href="{{ url('/kinerja/' . $guru->id . '/tahap-' . $tahap) }}" style="display: inline-block; background-color: #e11d48; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(225, 29, 72, 0.3);">
                                    Buka Formulir untuk Mengedit &rarr;
                                </a>
                            </div>

                            <!-- Motivation Note -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 8px; margin-top: 24px; border: 1px dashed #cbd5e1;">
                                <tr>
                                    <td style="padding: 12px 16px;">
                                        <p style="margin: 0; font-size: 12px; font-style: italic; color: #64748b; line-height: 1.5;">
                                            💡 <em>Perbaikan dan refleksi merupakan sarana konstruktif untuk meningkatkan mutu pembelajaran dan kompetensi pendidik. Terima kasih atas kerja sama dan dedikasi Bapak/Ibu.</em>
                                        </p>
                                    </td>
                                </tr>
                            </table>

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
