# Project Context: Simonitorguru

## Deskripsi Proyek
**Simonitorguru** adalah sistem informasi manajemen sekolah yang dirancang untuk memantau kedisiplinan dan kehadiran guru di kelas secara *real-time*. Sistem ini mendigitalisasi proses rekapitulasi manual menjadi form digital yang efisien, mencegah kelas kosong, dan memberikan laporan langsung kepada manajemen sekolah (Wakil Kepala Sekolah).

## Teknologi yang Digunakan
*   **Backend:** Laravel
*   **Frontend:** Vue.js (menggunakan Starter Kit UI / Tailwind)
*   **Database:** MySQL
*   **Notifikasi & Alert:** SweetAlert2 (untuk dialog konfirmasi hapus dan pesan sukses).

## Peran Pengguna (Roles)
Sistem ini menggunakan satu entitas `users` dengan 3 hak akses (`role`) yang berbeda:

1.  **Admin**
    *   Bertindak sebagai pusat manajemen aplikasi.
    *   Mengelola Master Data: User (Guru/Staff), Jurusan, Titik, Kelas, Mata Pelajaran, dan Jam Pelajaran.
    *   Menginput dan mengelola Jadwal Mata Pelajaran (rutin mingguan).
    *   Menginput dan menugaskan Jadwal Guru Piket harian (menugaskan user Guru ke suatu Titik pada tanggal tertentu).

2.  **Wakasek (Wakil Kepala Sekolah)**
    *   Memiliki hak akses pantau (Read-Only) tingkat eksekutif.
    *   Melihat dasbor statistik kehadiran guru harian secara *real-time*.
    *   Melihat laporan guru yang tidak hadir (Sakit/Izin/Alpha) atau kelas yang kosong.
    *   Memantau kepatuhan Guru Piket dalam memberikan laporan (apakah sudah lapor atau terlambat/belum mengisi).

3.  **Guru (Termasuk Guru Piket)**
    *   Sebagai *user* reguler yang juga bisa ditugaskan menjadi Guru Piket (Assignment, bukan Role permanen).
    *   **Tugas Guru Piket:** Berkeliling pada 1 "Titik" (yang membawahi 2 "Jurusan") setiap jam pelajaran berlangsung.
    *   **Fitur Guru Piket:** Mengisi form rekap kehadiran guru yang sedang mengajar di kelas tersebut berdasarkan jadwal.
    *   Pengisian form harus efisien, sistem otomatis memunculkan jadwal di jam tersebut, guru piket tinggal klik status kehadiran dan mengambil foto bukti.

## Aturan Bisnis & Logika Sistem
1.  **Pembagian Area Pantau:** Sekolah dibagi menjadi beberapa **Titik**. Satu Titik mengawasi **2 Jurusan**. Satu Jurusan memiliki banyak **Kelas**.
2.  **Validitas Data (Live Capture):** Form pelaporan oleh Guru Piket mengharuskan input **Foto Dokumentasi**. Foto ini **wajib** menggunakan kamera langsung (*live capture* menggunakan atribut `capture="environment"`) dan tidak boleh mengunggah file dari galeri *smartphone*.
3.  **Sistem Pengingat (Notifikasi):** Terdapat sistem pengecekan otomatis (*Cron Job*). Jika pada waktu tertentu (misal: 10 menit setelah suatu Jam Pelajaran selesai) Guru Piket belum mengisi rekap kehadiran, sistem akan mengirimkan pengingat via Email kepada Guru Piket tersebut.

## Struktur Database (Skema Migration)

1.  **users**
    *   id, name, email, email_verified_at, password, role [enum: 'admin', 'wakasek', 'guru'], remember_token, timestamps.
2.  **jurusans**
    *   id, nama_jurusan, timestamps.
3.  **titiks**
    *   id, nama_titik, timestamps.
4.  **titik_jurusan** (Pivot)
    *   id, titik_id, jurusan_id.
5.  **kelas**
    *   id, jurusan_id, nama_kelas, timestamps.
6.  **mata_pelajarans**
    *   id, nama_mapel, timestamps.
7.  **jam_pelajarans**
    *   id, urutan_jam, waktu_mulai, waktu_selesai, timestamps.
8.  **jadwal_mapels** (Jadwal Rutin)
    *   id, hari [enum], jam_pelajaran_id, kelas_id, mata_pelajaran_id, guru_id (foreign ke users), timestamps.
9.  **jadwal_pikets** (Penugasan Harian)
    *   id, tanggal_piket (date), titik_id, guru_piket_id (foreign ke users), timestamps.
10. **rekap_kehadirans** (Laporan Transaksional)
    *   id, jadwal_mapel_id, jadwal_piket_id, status_kehadiran [enum: 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Kosong'], foto_dokumentasi (string/path), keterangan (text, nullable), timestamps.

## Instruksi untuk AI Assistant (Antigravity)
Saat menerima *prompt* untuk mengerjakan proyek ini:
1.  Selalu rujuk skema database di atas saat membuat Controller, Model, dan Eloquent Query.
2.  Kerjakan secara modular (jangan membuat semuanya dalam satu output).
3.  Fokus pada pembuatan *Routing* (auth middleware per role) terlebih dahulu, lalu Controller, dan terakhir antarmuka Vue (Pages/Components).
4.  Pastikan antarmuka Vue dirancang responsif, terutama untuk form Guru Piket yang akan diakses melalui *smartphone/perangkat lainnya*.
5.  **Implementasi SweetAlert2:** Gunakan `sweetalert2` untuk seluruh aksi form. Tampilkan *toast* atau *alert* sukses setelah berhasil menyimpan data di halaman Create/Edit. Pada halaman Index, gunakan modal konfirmasi *warning* dari SweetAlert2 sebelum memproses penghapusan data.