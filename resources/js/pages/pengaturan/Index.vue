<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Settings,
    Image as ImageIcon,
    Save,
    Download,
    Trash2,
    AlertTriangle,
    CheckCircle,
    Database,
} from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import BackupModal from './BackupModal.vue';
import MassDeleteModal from './MassDeleteModal.vue';

const props = defineProps<{
    settingan: {
        id: number;
        nama_aplikasi: string;
        nama_instansi: string;
        logo_aplikasi: string | null;
        last_backup_date: string | null;
        last_mass_delete_date: string | null;
    };
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// --- Identity Form ---
const identityForm = useForm({
    nama_aplikasi: props.settingan?.nama_aplikasi || '',
    nama_instansi: props.settingan?.nama_instansi || '',
    logo: null as File | null,
});

const logoPreview = ref<string | null>(
    props.settingan?.logo_aplikasi
        ? `/storage/${props.settingan.logo_aplikasi}`
        : null,
);

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        identityForm.logo = target.files[0];
        logoPreview.value = URL.createObjectURL(target.files[0]);
    }
};

const saveIdentity = () => {
    identityForm.post('/pengaturan/identity', {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Identitas aplikasi berhasil diperbarui.',
                confirmButtonColor: '#10b981',
            });
        },
    });
};

const showBackupModal = ref(false);
const showDeleteModal = ref(false);

const closeModals = () => {
    showBackupModal.value = false;
    showDeleteModal.value = false;
};
</script>

<template>
    <Head title="Pengaturan Aplikasi" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="mb-2 flex items-center gap-3">
            <div
                class="rounded-xl bg-emerald-100 p-3 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400"
            >
                <Settings class="h-6 w-6" />
            </div>
            <div>
                <h1
                    class="text-2xl font-bold text-slate-800 dark:text-slate-100"
                >
                    Pengaturan Aplikasi
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kustomisasi identitas aplikasi dan kelola manajemen
                    penyimpanan data terpadu.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Bagian Kiri: Kustomisasi Identitas -->
            <div
                class="flex flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card shadow-sm"
            >
                <div
                    class="border-b border-sidebar-border/50 bg-slate-50/50 p-5 dark:bg-slate-900/20"
                >
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <ImageIcon class="h-5 w-5 text-indigo-500" />
                        Identitas & Logo
                    </h2>
                </div>
                <div class="flex-1 p-5">
                    <form @submit.prevent="saveIdentity" class="space-y-5">
                        <div class="space-y-1.5">
                            <label
                                class="text-sm font-medium text-slate-700 dark:text-slate-300"
                                >Nama Aplikasi</label
                            >
                            <input
                                v-model="identityForm.nama_aplikasi"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900"
                                placeholder="Contoh: Simonitorguru"
                            />
                            <div
                                v-if="identityForm.errors.nama_aplikasi"
                                class="text-xs text-red-500"
                            >
                                {{ identityForm.errors.nama_aplikasi }}
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label
                                class="text-sm font-medium text-slate-700 dark:text-slate-300"
                                >Nama Instansi</label
                            >
                            <input
                                v-model="identityForm.nama_instansi"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900"
                                placeholder="Contoh: SMA Negeri 1 ..."
                            />
                        </div>

                        <div class="space-y-2">
                            <label
                                class="text-sm font-medium text-slate-700 dark:text-slate-300"
                                >Logo Aplikasi</label
                            >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-900"
                                >
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        class="h-full w-full object-contain p-2"
                                        alt="Logo Preview"
                                    />
                                    <ImageIcon
                                        v-else
                                        class="h-8 w-8 text-slate-300"
                                    />
                                </div>
                                <div class="flex-1">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="handleLogoChange"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-900/30 dark:file:text-emerald-400"
                                    />
                                </div>
                            </div>
                            <div
                                v-if="identityForm.errors.logo"
                                class="text-xs text-red-500"
                            >
                                {{ identityForm.errors.logo }}
                            </div>
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="identityForm.processing"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                            >
                                <Save class="h-4 w-4" /> Simpan Identitas
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bagian Kanan: Manajemen Ruang Penyimpanan -->
            <div
                class="flex flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card shadow-sm"
            >
                <div
                    class="border-b border-sidebar-border/50 bg-slate-50/50 p-5 dark:bg-slate-900/20"
                >
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <Database class="h-5 w-5 text-amber-500" />
                        Penyimpanan & Pencadangan
                    </h2>
                </div>
                <div class="flex flex-1 flex-col gap-6 p-5">
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50"
                    >
                        <h3
                            class="mb-1 flex items-center gap-2 text-sm font-medium"
                        >
                            <CheckCircle class="h-4 w-4 text-emerald-500" />
                            Pencadangan Data & Media (ZIP)
                        </h3>
                        <p
                            class="mb-4 text-xs leading-relaxed text-muted-foreground"
                        >
                            Unduh arsip ZIP lengkap yang memuat database.sql secara utuh beserta seluruh berkas foto dokumentasi, gambar, dan bukti lampiran kegiatan operasional.
                        </p>
                        <button
                            @click="showBackupModal = true"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 dark:bg-slate-200 dark:text-slate-900 dark:hover:bg-slate-300"
                        >
                            <Download class="h-4 w-4" /> Cadangkan Sekarang
                        </button>
                        <div
                            class="mt-3 text-center text-[10px] text-slate-400"
                        >
                            Pencadangan terakhir:
                            {{
                                props.settingan?.last_backup_date
                                    ? formatDate(
                                          props.settingan.last_backup_date,
                                      )
                                    : 'Belum pernah'
                            }}
                        </div>
                    </div>

                    <div
                        class="mt-auto rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/10"
                    >
                        <h3
                            class="mb-1 flex items-center gap-2 text-sm font-medium text-red-700 dark:text-red-400"
                        >
                            <AlertTriangle class="h-4 w-4" /> Pembersihan Data Transaksi
                        </h3>
                        <p
                            class="mb-4 text-xs leading-relaxed text-red-600/80 dark:text-red-400/80"
                        >
                            Bersihkan seluruh data transaksi harian (presensi piket/mengajar, absensi GMS/upacara, izin, log, dan foto bukti) secara permanen. Seluruh jadwal, data master, akun, dan kinerja guru tetap aman.
                        </p>
                        <button
                            @click="showDeleteModal = true"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                        >
                            <Trash2 class="h-4 w-4" /> Bersihkan Data Transaksi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <BackupModal :show="showBackupModal" @close="closeModals" />

    <MassDeleteModal :show="showDeleteModal" @close="closeModals" />
</template>
