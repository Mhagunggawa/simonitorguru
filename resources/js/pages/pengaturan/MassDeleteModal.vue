<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, Trash2, ShieldCheck, CheckCircle2 } from '@lucide/vue';
import Swal from 'sweetalert2';

defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const form = useForm({
    confirmation: '',
});

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = () => {
    form.post('/pengaturan/mass-delete', {
        preserveScroll: true,
        onSuccess: () => {
            close();
            Swal.fire({
                icon: 'success',
                title: 'Data Transaksi Dibersihkan',
                text: 'Seluruh riwayat transaksi harian dan berkas bukti foto berhasil dibersihkan.',
                confirmButtonColor: '#10b981',
            });
        },
    });
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-red-500/40 bg-card shadow-2xl"
        >
            <!-- Header Modal -->
            <div
                class="flex items-center justify-between border-b border-red-200 bg-red-50/80 p-4 dark:border-red-900/30 dark:bg-red-900/20"
            >
                <h3
                    class="flex items-center gap-2.5 text-base font-semibold text-red-700 dark:text-red-400"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-900/60 dark:text-red-300">
                        <AlertTriangle class="h-4 w-4" />
                    </span>
                    Pembersihan Data Transaksi
                </h3>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-slate-400 hover:bg-red-100 hover:text-slate-600 dark:hover:bg-red-900/40 dark:hover:text-slate-200"
                    @click="close"
                >
                    ✕
                </button>
            </div>

            <!-- Body Modal -->
            <div class="space-y-4 p-5 max-h-[75vh] overflow-y-auto">
                <!-- Data Yang Dihapus -->
                <div class="rounded-xl border border-red-200/80 bg-red-50/60 p-3.5 text-xs text-red-900 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-300">
                    <p class="font-semibold mb-1.5 flex items-center gap-1.5 text-red-800 dark:text-red-400">
                        <Trash2 class="h-3.5 w-3.5" /> Data transaksi yang akan dikosongkan secara permanen:
                    </p>
                    <ul class="space-y-1 pl-5 list-disc text-slate-600 dark:text-slate-300">
                        <li>Rekap Kehadiran Piket & Presensi Mengajar Guru</li>
                        <li>Absensi Guru Magang / Studi (GMS) & Absensi Upacara</li>
                        <li>Izin Piket & Dispensasi Masuk/Keluar</li>
                        <li>Tindak Lanjut & Evaluasi Kedisiplinan</li>
                        <li>Log Catatan Riwayat Aktivitas Sistem</li>
                        <li>Seluruh berkas foto dokumentasi & bukti laporan transaksi</li>
                    </ul>
                </div>

                <!-- Data Yang AMAN -->
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/60 p-3.5 text-xs text-emerald-950 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-200">
                    <p class="font-semibold mb-1.5 flex items-center gap-1.5 text-emerald-800 dark:text-emerald-300">
                        <ShieldCheck class="h-3.5 w-3.5" /> Data penting yang TETAP AMAN & DIPERTAHANKAN:
                    </p>
                    <ul class="space-y-1 pl-5 list-disc text-slate-600 dark:text-slate-300">
                        <li><strong>Seluruh Jadwal:</strong> Jadwal Pelajaran (Mapel), Jadwal GMS, dan Jadwal Piket.</li>
                        <li><strong>Observasi Kinerja Guru:</strong> Seluruh data Kinerja Tahap 1, 2, 3, dan Guru Drive.</li>
                        <li><strong>Data Master:</strong> Akun Pengguna, Guru, Kelas, Jurusan, Mapel, Jam Pelajaran, & Titik Absensi.</li>
                        <li><strong>Identitas:</strong> Pengaturan aplikasi dan logo instansi sekolah.</li>
                    </ul>
                </div>

                <!-- Input Konfirmasi -->
                <div
                    class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50"
                >
                    <label
                        for="delete-confirmation"
                        class="mb-2 block text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >
                        Ketik kata
                        <span
                            class="font-mono font-bold tracking-wider text-red-600 dark:text-red-400"
                            >hapus</span
                        >
                        di bawah untuk mengonfirmasi:
                    </label>
                    <input
                        id="delete-confirmation"
                        v-model="form.confirmation"
                        type="text"
                        autocomplete="off"
                        placeholder="hapus"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-center font-mono text-sm tracking-widest focus:border-red-500 focus:ring-1 focus:ring-red-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900"
                        @keyup.enter="form.confirmation === 'hapus' && submit()"
                    />
                    <p
                        v-if="form.errors.confirmation"
                        class="mt-2 text-xs text-red-600"
                    >
                        {{ form.errors.confirmation }}
                    </p>
                </div>
            </div>

            <!-- Footer Modal -->
            <div
                class="flex items-center justify-end gap-2.5 border-t border-sidebar-border/50 bg-slate-50/50 p-4 dark:bg-slate-900/20"
            >
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    @click="close"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="form.processing || form.confirmation !== 'hapus'"
                    class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="submit"
                >
                    <Trash2 class="h-4 w-4" /> Bersihkan Data Transaksi
                </button>
            </div>
        </div>
    </div>
</template>
