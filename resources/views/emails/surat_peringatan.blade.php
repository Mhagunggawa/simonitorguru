<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan {{ $tipePeringatan }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #222;">

    <p>Yth. Bapak/Ibu <strong>{{ $guru->name }}</strong>,</p>

    <p>
        Berdasarkan hasil rekapitulasi data kehadiran mengajar, kegiatan GMS, dan jadwal piket
        pada semester berjalan, melalui email ini kami menyampaikan pemberitahuan berupa
        <strong>{{ $tipePeringatan }}</strong>.
    </p>

    <p>
        Dokumen resmi terkait evaluasi kedisiplinan telah dilampirkan pada email ini.
        Silakan memeriksa dokumen tersebut untuk informasi lebih lanjut.
    </p>

    <p>
        Apabila terdapat informasi yang perlu dikonfirmasi, silakan menghubungi pihak
        <strong>Kurikulum</strong>
    </p>

    <br>

    <p>Terima kasih atas perhatian dan kerja samanya.</p>

    <p>
        Hormat kami,<br>
        <strong>Wakasek Kurikulum</strong><br>
    </p>

</body>
</html>