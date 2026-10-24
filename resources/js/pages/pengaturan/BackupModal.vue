<script setup lang="ts">
import { Download, Database, Image, Calendar, CheckCircle2 } from '@lucide/vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { reactive, ref } from 'vue';

defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const formatLocalDate = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const today = new Date();
const form = reactive({
    mode: 'all' as 'all' | 'range',
    start_date: formatLocalDate(
        new Date(today.getFullYear(), today.getMonth(), 1),
    ),
    end_date: formatLocalDate(today),
    processing: false,
});

const downloadArchive = (data: Blob, contentDisposition?: string) => {
    const objectUrl = window.URL.createObjectURL(data);
    const link = document.createElement('a');
    const filenameMatch = contentDisposition?.match(
        /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/,
    );

    link.href = objectUrl;
    link.download = filenameMatch?.[1]?.replace(/['"]/g, '') ?? 'backup_simonitorguru.zip';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(objectUrl);
};

const readErrorMessage = async (error: unknown): Promise<string> => {
    if (!axios.isAxiosError(error) || !(error.response?.data instanceof Blob)) {
        return 'Terjadi kesalahan saat membuat backup.';
    }

    try {
        const payload = JSON.parse(await error.response.data.text());
        const validationMessage = Object.values(payload.errors ?? {}).flat()[0];

        return (
            validationMessage ??
            payload.message ??
            'Terjadi kesalahan saat membuat backup.'
        );
    } catch {
        return 'Terjadi kesalahan saat membuat backup.';
    }
};

const submitBackup = async () => {
    if (form.mode === 'range') {
        if (!form.start_date || !form.end_date || form.start_date > form.end_date) {
            await Swal.fire(
                'Periode Tidak Valid',
                'Tanggal mulai harus sebelum atau sama dengan tanggal akhir.',
                'warning',
            );
            return;
        }
    }

    form.processing = true;
    Swal.fire({
        title: 'Membuat Cadangan Data',
        text: 'Database SQL dan seluruh berkas foto/gambar sedang dikemas ke dalam ZIP. Mohon tunggu...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const payload = {
            mode: form.mode,
            ...(form.mode === 'range'
                ? { start_date: form.start_date, end_date: form.end_date }
                : {}),
        };

        const response = await axios.post('/pengaturan/backup', payload, {
            responseType: 'blob',
        });

        downloadArchive(response.data, response.headers['content-disposition']);
        emit('close');
        await Swal.fire({
            icon: 'success',
            title: 'Backup Berhasil Diunduh',
            text: 'Berkas ZIP memuat file database.sql, folder dokumentasi foto/gambar, serta petunjuk pemulihan.',
            confirmButtonColor: '#10b981',
        });
    } catch (error) {
        await Swal.fire('Backup Gagal', await readErrorMessage(error), 'error');
    } finally {
        form.processing = false;
    }
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card shadow-2xl"
        >
            <!-- Modal Header -->
            <div
                class="flex items-center justify-between border-b border-sidebar-border/50 bg-slate-50/70 p-4 dark:bg-slate-900/40"
            >
                <h3 class="flex items-center gap-2.5 text-base font-semibold text-slate-800 dark:text-slate-100">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400">
                        <Download class="h-4 w-4" />
                    </span>
                    Cadangkan Data & Media
                </h3>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div class="space-y-4 p-5">
                <!-- Info Isi Paket Backup -->
                <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/60 p-3.5 text-xs text-emerald-950 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-200">
                    <p class="font-semibold mb-1.5 flex items-center gap-1.5 text-emerald-800 dark:text-emerald-300">
                        <CheckCircle2 class="h-3.5 w-3.5" /> Berkas yang disertakan dalam paket ZIP:
                    </p>
                    <ul class="space-y-1 pl-5 list-disc text-slate-600 dark:text-slate-300">
                        <li><strong>database.sql:</strong> Seluruh isi basis data (data master, jadwal, akun, observasi, dan seluruh riwayat transaksi).</li>
                        <li><strong>/dokumentasi:</strong> Berkas foto dan gambar dokumentasi laporan/kegiatan dari penyimpanan sistem.</li>
                        <li><strong>info_backup.txt:</strong> Catatan metadata dan panduan pemulihan data (restore).</li>
                    </ul>
                </div>

                <!-- Pilihan Mode Pencadangan -->
                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Pilih Cakupan Media / Foto
                    </label>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <!-- Mode Full / Penuh -->
                        <div
                            class="relative flex cursor-pointer flex-col rounded-xl border p-3.5 transition-all select-none"
                            :class="form.mode === 'all'
                                ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-500 dark:border-emerald-500 dark:bg-emerald-950/20'
                                : 'border-slate-200 hover:border-slate-300 bg-white dark:border-slate-800 dark:bg-slate-900/50 dark:hover:border-slate-700'"
                            @click="form.mode = 'all'"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    <Image class="h-4 w-4 text-emerald-600" />
                                    Cadangkan Penuh
                                </span>
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">
                                    Disarankan
                                </span>
                            </div>
                            <p class="text-[11px] text-muted-foreground leading-relaxed mt-1">
                                Mengemas database dan <strong>semua foto/gambar</strong> tanpa batasan rentang tanggal.
                            </p>
                        </div>

                        <!-- Mode Range / Filter Tanggal -->
                        <div
                            class="relative flex cursor-pointer flex-col rounded-xl border p-3.5 transition-all select-none"
                            :class="form.mode === 'range'
                                ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-500 dark:border-emerald-500 dark:bg-emerald-950/20'
                                : 'border-slate-200 hover:border-slate-300 bg-white dark:border-slate-800 dark:bg-slate-900/50 dark:hover:border-slate-700'"
                            @click="form.mode = 'range'"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    <Calendar class="h-4 w-4 text-amber-500" />
                                    Filter Periode
                                </span>
                            </div>
                            <p class="text-[11px] text-muted-foreground leading-relaxed mt-1">
                                Database tetap lengkap, foto disaring berdasarkan rentang tanggal tertentu.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Input Tanggal (Hanya muncul jika mode range) -->
                <div v-if="form.mode === 'range'" class="space-y-3 rounded-xl border border-amber-200/60 bg-amber-50/30 p-3.5 dark:border-amber-900/40 dark:bg-amber-950/10">
                    <p class="text-xs font-medium text-amber-800 dark:text-amber-300">
                        Tentukan rentang tanggal foto/dokumentasi yang ingin disertakan:
                    </p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="space-y-1">
                            <label for="backup-start-date" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                Tanggal Mulai
                            </label>
                            <input
                                id="backup-start-date"
                                v-model="form.start_date"
                                type="date"
                                :max="form.end_date"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900"
                            />
                        </div>
                        <div class="space-y-1">
                            <label for="backup-end-date" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                Tanggal Akhir
                            </label>
                            <input
                                id="backup-end-date"
                                v-model="form.end_date"
                                type="date"
                                :min="form.start_date"
                                :max="formatLocalDate(today)"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="flex items-center justify-end gap-2.5 border-t border-sidebar-border/50 bg-slate-50/50 p-4 dark:bg-slate-900/20"
            >
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    @click="emit('close')"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="form.processing"
                    class="flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="submitBackup"
                >
                    <Download class="h-4 w-4" />
                    {{ form.mode === 'all' ? 'Cadangkan Penuh (ZIP)' : 'Cadangkan Periode (ZIP)' }}
                </button>
            </div>
        </div>
    </div>
</template>
