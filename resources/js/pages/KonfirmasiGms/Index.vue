<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { 
    Users, 
    Calendar, 
    Clock, 
    CheckCircle2, 
    XCircle, 
    AlertCircle, 
    AlertTriangle, 
    Search, 
    FileText, 
    Camera, 
    Check, 
    Filter,
    ShieldAlert
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface TeacherItem {
    user_id: number;
    name: string;
    email: string;
    tipe_guru: string;
    status_pegawai: string;
    is_confirmed: boolean;
    status: 'Hadir' | 'Izin' | 'Sakit' | 'Alpha' | 'Belum Absen';
    waktu_absen: string;
    foto_bukti: string | null;
    keterangan: string | null;
    confirmed_by: string | null;
    is_manual: boolean;
    absensi_id: number | null;
}

const props = defineProps<{
    teachers: TeacherItem[];
    stats: {
        total_jadwal: number;
        hadir_mandiri: number;
        hadir_manual: number;
        total_hadir: number;
        izin: number;
        sakit: number;
        alpha: number;
        belum_absen: number;
    };
    selectedDate: string;
    namaHari: string;
    formattedDate: string;
    sessionInfo: {
        waktu_mulai: string;
        waktu_selesai: string;
        is_today: boolean;
        is_session_ended: boolean;
        is_session_active: boolean;
        is_before_session: boolean;
    };
    filters: {
        search: string;
        tanggal: string;
    };
}>();

const search = ref(props.filters.search || '');
const dateInput = ref(props.selectedDate);
const activeTab = ref<'semua' | 'belum' | 'sudah'>('semua');

// Selected teacher for single confirmation modal
const isConfirmModalOpen = ref(false);
const selectedTeacher = ref<TeacherItem | null>(null);
const confirmErrorMessage = ref('');

const confirmForm = useForm({
    user_id: 0,
    tanggal: props.selectedDate,
    status: 'Alpha' as 'Alpha' | 'Izin' | 'Sakit' | 'Hadir',
    keterangan: '',
});

// Photo preview modal
const isPhotoModalOpen = ref(false);
const photoPreviewUrl = ref<string | null>(null);
const photoTeacherName = ref<string>('');

const openPhotoModal = (teacher: TeacherItem) => {
    if (teacher.foto_bukti) {
        photoPreviewUrl.value = `/storage/${teacher.foto_bukti}`;
        photoTeacherName.value = teacher.name;
        isPhotoModalOpen.value = true;
    }
};

const handleDateChange = () => {
    router.get('/konfirmasi-gms', {
        tanggal: dateInput.value,
        search: search.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleSearch = () => {
    router.get('/konfirmasi-gms', {
        tanggal: dateInput.value,
        search: search.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filteredTeachers = computed(() => {
    return props.teachers.filter(teacher => {
        // Tab filter
        if (activeTab.value === 'belum' && teacher.is_confirmed) return false;
        if (activeTab.value === 'sudah' && !teacher.is_confirmed) return false;

        // Search filter
        if (search.value) {
            const query = search.value.toLowerCase();
            return teacher.name.toLowerCase().includes(query) || teacher.email.toLowerCase().includes(query);
        }
        return true;
    });
});

const openConfirmModal = (teacher: TeacherItem) => {
    selectedTeacher.value = teacher;
    confirmForm.user_id = teacher.user_id;
    confirmForm.tanggal = props.selectedDate;
    confirmForm.status = teacher.is_confirmed && teacher.status !== 'Belum Absen' ? teacher.status : 'Alpha';
    confirmForm.keterangan = teacher.keterangan || '';
    confirmErrorMessage.value = '';
    isConfirmModalOpen.value = true;
};

const submitConfirmation = () => {
    confirmErrorMessage.value = '';

    if (confirmForm.status === 'Hadir' && !confirmForm.keterangan.trim()) {
        confirmErrorMessage.value = 'Untuk konfirmasi status Hadir secara manual, harap sertakan alasan / keterangan mengapa guru tidak absen mandiri.';
        return;
    }

    confirmForm.post('/konfirmasi-gms', {
        preserveScroll: true,
        onSuccess: () => {
            isConfirmModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Disimpan',
                text: `Status kehadiran untuk ${selectedTeacher.value?.name} telah diperbarui sebagai ${confirmForm.status}.`,
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (err: any) => {
            confirmErrorMessage.value = err.keterangan || err.status || 'Terjadi kesalahan sistem saat menyimpan konfirmasi.';
        }
    });
};

// Bulk Confirmation (Tandai Semua Belum Absen sebagai Alpha)
const unconfirmedTeachers = computed(() => props.teachers.filter(t => !t.is_confirmed));

const confirmBulkAlpha = () => {
    if (unconfirmedTeachers.value.length === 0) return;

    Swal.fire({
        title: 'Konfirmasi Massal Alpha?',
        html: `Tindakan ini akan menandai <b>${unconfirmedTeachers.value.length} guru</b> yang belum absen pada tanggal ini sebagai <b>Alpha</b>.<br><br><span class="text-xs text-muted-foreground">Status masing-masing guru tetap dapat diubah kemudian jika diperlukan.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Tandai Alpha Semua',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/konfirmasi-gms/bulk', {
                tanggal: props.selectedDate,
                user_ids: unconfirmedTeachers.value.map(t => t.user_id),
                status: 'Alpha',
                keterangan: 'Dikonfirmasi Alpha otomatis (waktu habis tanpa absensi)',
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dikonfirmasi',
                        text: 'Semua guru yang belum absen telah ditandai sebagai Alpha.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Konfirmasi Absensi GMS" />

    <div class="flex h-full flex-1 flex-col gap-4 md:gap-5 p-3 md:p-6 max-w-[1600px] mx-auto w-full">
        <!-- Top Bar Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4">
            <div>
                <h1 class="text-xl md:text-3xl font-bold tracking-tight text-foreground flex items-center gap-2 md:gap-2.5">
                    <CheckCircle2 class="w-6 h-6 md:w-8 md:h-8 text-emerald-600 dark:text-emerald-400" />
                    Konfirmasi Absensi GMS
                </h1>
                <p class="text-xs md:text-sm text-muted-foreground mt-0.5 md:mt-1">
                    Verifikasi dan tetapkan status kehadiran guru terjadwal GMS yang belum melakukan absensi mandiri.
                </p>
            </div>

            <!-- Date Picker Selector -->
            <div class="flex items-center gap-2 md:gap-3 bg-card border border-border p-2 rounded-2xl shadow-xs w-full md:w-auto">
                <div class="flex items-center gap-2 px-2 md:px-3 text-xs md:text-sm font-medium text-muted-foreground shrink-0">
                    <Calendar class="w-4 h-4 text-emerald-600" />
                    <span>Tanggal:</span>
                </div>
                <Input 
                    type="date" 
                    v-model="dateInput" 
                    @change="handleDateChange"
                    class="h-9 w-full md:w-44 rounded-xl bg-background border-border font-medium text-xs md:text-sm" 
                />
            </div>
        </div>

        <!-- Banner Status Sesi Waktu -->
        <div 
            class="rounded-2xl p-3.5 md:p-5 border transition-all flex flex-col md:flex-row md:items-center justify-between gap-3.5 md:gap-4 shadow-xs"
            :class="[
                sessionInfo.is_session_ended 
                    ? 'bg-amber-50/70 border-amber-200 dark:bg-amber-950/20 dark:border-amber-900/50' 
                    : sessionInfo.is_session_active 
                        ? 'bg-blue-50/70 border-blue-200 dark:bg-blue-950/20 dark:border-blue-900/50'
                        : 'bg-slate-50 border-slate-200 dark:bg-slate-900/20 dark:border-slate-800'
            ]"
        >
            <div class="flex items-start gap-3">
                <div 
                    class="p-2 md:p-2.5 rounded-xl shrink-0 mt-0.5"
                    :class="[
                        sessionInfo.is_session_ended 
                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' 
                            : sessionInfo.is_session_active 
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                                : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                    ]"
                >
                    <Clock class="w-4 h-4 md:w-5 md:h-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-sm md:text-base text-foreground">
                            Jadwal GMS Hari {{ namaHari }}, {{ formattedDate }}
                        </h3>
                        <span 
                            class="px-2 py-0.5 rounded-full text-[11px] md:text-xs font-semibold"
                            :class="[
                                sessionInfo.is_session_ended 
                                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' 
                                    : sessionInfo.is_session_active 
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 animate-pulse'
                                        : 'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300'
                            ]"
                        >
                            {{ 
                                sessionInfo.is_session_ended 
                                    ? (sessionInfo.is_today ? 'Waktu Absensi Selesai' : 'Sesi Selesai (Lampau)') 
                                    : sessionInfo.is_session_active 
                                        ? 'Sesi Absensi Mandiri Sedang Berlangsung' 
                                        : 'Belum Masuk Jam Sesi' 
                            }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1 leading-relaxed">
                        Jam Sesi GMS: <strong class="text-foreground">{{ sessionInfo.waktu_mulai }} - {{ sessionInfo.waktu_selesai }} WITA</strong>.
                        <template v-if="sessionInfo.is_session_ended">
                            Waktu absensi mandiri telah berakhir. Guru yang belum absen dapat dikonfirmasi kehadirannya.
                        </template>
                        <template v-else-if="sessionInfo.is_session_active">
                            Guru masih dapat melakukan foto absensi mandiri melalui aplikasi masing-masing.
                        </template>
                        <template v-else>
                            Sesi absensi mandiri hari ini belum dimulai.
                        </template>
                    </p>
                </div>
            </div>

            <div v-if="stats.belum_absen > 0" class="shrink-0 flex items-center w-full md:w-auto">
                <Button 
                    variant="destructive" 
                    size="sm" 
                    @click="confirmBulkAlpha"
                    class="rounded-xl shadow-xs text-xs font-semibold gap-1.5 w-full md:w-auto h-9"
                >
                    <ShieldAlert class="w-4 h-4" />
                    <span>Tandai Semua Belum Absen sebagai Alpha ({{ stats.belum_absen }})</span>
                </Button>
            </div>
        </div>

        <!-- Bento Grid Statistik Kehadiran (3 kolom di HP, 6 kolom di desktop) -->
        <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-6 gap-2 md:gap-3.5">
            <!-- Total Jadwal -->
            <div class="rounded-2xl border border-border bg-card p-3 md:p-4 shadow-xs">
                <p class="text-[10px] md:text-xs font-semibold text-muted-foreground">Total Jadwal</p>
                <h4 class="text-lg md:text-2xl font-bold mt-0.5 md:mt-1 text-foreground">{{ stats.total_jadwal }}</h4>
                <p class="text-[10px] md:text-[11px] text-muted-foreground mt-0.5 truncate">Guru Terjadwal</p>
            </div>

            <!-- Belum Absen -->
            <div 
                class="rounded-2xl border p-3 md:p-4 shadow-xs transition-all"
                :class="stats.belum_absen > 0 ? 'border-amber-400/60 bg-amber-50/50 dark:bg-amber-950/20' : 'border-border bg-card'"
            >
                <div class="flex items-center justify-between">
                    <p class="text-[10px] md:text-xs font-semibold text-amber-700 dark:text-amber-400">Belum Konfirmasi</p>
                    <AlertCircle v-if="stats.belum_absen > 0" class="w-3.5 h-3.5 text-amber-600 shrink-0" />
                </div>
                <h4 class="text-lg md:text-2xl font-bold mt-0.5 md:mt-1" :class="stats.belum_absen > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-foreground'">
                    {{ stats.belum_absen }}
                </h4>
                <p class="text-[10px] md:text-[11px] text-muted-foreground mt-0.5 truncate">Perlu Tindakan</p>
            </div>

            <!-- Hadir -->
            <div class="rounded-2xl border border-border bg-card p-3 md:p-4 shadow-xs">
                <p class="text-[10px] md:text-xs font-semibold text-emerald-600 dark:text-emerald-400">Hadir</p>
                <h4 class="text-lg md:text-2xl font-bold mt-0.5 md:mt-1 text-emerald-700 dark:text-emerald-300">{{ stats.total_hadir }}</h4>
                <p class="text-[10px] md:text-[11px] text-muted-foreground mt-0.5 truncate">{{ stats.hadir_mandiri }} foto • {{ stats.hadir_manual }} mnl</p>
            </div>

            <!-- Izin -->
            <div class="rounded-2xl border border-border bg-card p-3 md:p-4 shadow-xs">
                <p class="text-[10px] md:text-xs font-semibold text-blue-600 dark:text-blue-400">Izin</p>
                <h4 class="text-lg md:text-2xl font-bold mt-0.5 md:mt-1 text-blue-700 dark:text-blue-300">{{ stats.izin }}</h4>
                <p class="text-[10px] md:text-[11px] text-muted-foreground mt-0.5 truncate">Surat / Tugas</p>
            </div>

            <!-- Sakit -->
            <div class="rounded-2xl border border-border bg-card p-3 md:p-4 shadow-xs">
                <p class="text-[10px] md:text-xs font-semibold text-amber-600 dark:text-amber-400">Sakit</p>
                <h4 class="text-lg md:text-2xl font-bold mt-0.5 md:mt-1 text-amber-700 dark:text-amber-300">{{ stats.sakit }}</h4>
                <p class="text-[10px] md:text-[11px] text-muted-foreground mt-0.5 truncate">Kondisi Sakit</p>
            </div>

            <!-- Alpha -->
            <div class="rounded-2xl border border-border bg-card p-3 md:p-4 shadow-xs">
                <p class="text-[10px] md:text-xs font-semibold text-red-600 dark:text-red-400">Alpha</p>
                <h4 class="text-lg md:text-2xl font-bold mt-0.5 md:mt-1 text-red-700 dark:text-red-400">{{ stats.alpha }}</h4>
                <p class="text-[10px] md:text-[11px] text-muted-foreground mt-0.5 truncate">Tanpa Ket.</p>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-card border border-border p-3 md:p-4 rounded-2xl shadow-xs flex flex-col md:flex-row items-center justify-between gap-3 md:gap-4">
            <!-- Tabs Filter (Scrollable on mobile) -->
            <div class="flex items-center p-1 rounded-xl bg-muted/60 border border-border/40 w-full md:w-auto overflow-x-auto max-w-full">
                <button 
                    @click="activeTab = 'semua'"
                    class="px-3 md:px-4 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap"
                    :class="activeTab === 'semua' ? 'bg-background shadow-xs text-foreground' : 'text-muted-foreground hover:text-foreground'"
                >
                    Semua ({{ props.teachers.length }})
                </button>
                <button 
                    @click="activeTab = 'belum'"
                    class="px-3 md:px-4 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 justify-center whitespace-nowrap"
                    :class="activeTab === 'belum' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 shadow-xs font-bold' : 'text-muted-foreground hover:text-foreground'"
                >
                    <span>Belum Dikonfirmasi</span>
                    <span v-if="stats.belum_absen > 0" class="px-1.5 py-0.2 rounded-full bg-amber-500 text-white text-[10px]">
                        {{ stats.belum_absen }}
                    </span>
                </button>
                <button 
                    @click="activeTab = 'sudah'"
                    class="px-3 md:px-4 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap"
                    :class="activeTab === 'sudah' ? 'bg-background shadow-xs text-foreground' : 'text-muted-foreground hover:text-foreground'"
                >
                    Sudah / Hadir ({{ props.teachers.length - stats.belum_absen }})
                </button>
            </div>

            <!-- Search Field -->
            <div class="relative w-full md:w-72">
                <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search" 
                    @keyup.enter="handleSearch"
                    placeholder="Cari nama atau email..." 
                    class="pl-9 h-10 rounded-xl bg-background border-border text-xs md:text-sm w-full" 
                />
            </div>
        </div>

        <!-- Data Guru Container -->
        <div class="rounded-2xl border border-border bg-card shadow-xs overflow-hidden">
            <!-- 1. TAMPILAN DESKTOP: TABLE VIEW (hidden md:block) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40 border-b border-border text-xs uppercase text-muted-foreground font-semibold">
                        <tr>
                            <th class="py-3.5 px-4 w-12 text-center">#</th>
                            <th class="py-3.5 px-4">Nama Guru</th>
                            <th class="py-3.5 px-4">Tipe Guru</th>
                            <th class="py-3.5 px-4">Status Kehadiran</th>
                            <th class="py-3.5 px-4">Waktu / Metode</th>
                            <th class="py-3.5 px-4">Bukti / Keterangan</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr 
                            v-for="(teacher, index) in filteredTeachers" 
                            :key="teacher.user_id"
                            class="hover:bg-muted/30 transition-colors"
                            :class="!teacher.is_confirmed ? 'bg-amber-50/20 dark:bg-amber-950/5' : ''"
                        >
                            <td class="py-3.5 px-4 text-center text-xs text-muted-foreground">{{ index + 1 }}</td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-foreground text-sm">{{ teacher.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ teacher.email }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-md bg-muted text-muted-foreground">
                                    {{ teacher.tipe_guru }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span 
                                    v-if="teacher.status === 'Hadir'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                                >
                                    <Check class="w-3.5 h-3.5" />
                                    <span>Hadir</span>
                                </span>
                                <span 
                                    v-else-if="teacher.status === 'Izin'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300"
                                >
                                    <FileText class="w-3.5 h-3.5" />
                                    <span>Izin</span>
                                </span>
                                <span 
                                    v-else-if="teacher.status === 'Sakit'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
                                >
                                    <AlertTriangle class="w-3.5 h-3.5" />
                                    <span>Sakit</span>
                                </span>
                                <span 
                                    v-else-if="teacher.status === 'Alpha'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300"
                                >
                                    <XCircle class="w-3.5 h-3.5" />
                                    <span>Alpha</span>
                                </span>
                                <span 
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700"
                                >
                                    <Clock class="w-3.5 h-3.5 text-amber-500" />
                                    <span>Belum Absen</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-muted-foreground">
                                <div v-if="teacher.is_confirmed" class="flex flex-col gap-0.5">
                                    <span class="font-medium text-foreground">{{ teacher.waktu_absen }} WITA</span>
                                    <span v-if="teacher.is_manual" class="text-[10px] text-blue-600 dark:text-blue-400">
                                        Konfirmasi Manual
                                    </span>
                                    <span v-else class="text-[10px] text-emerald-600 dark:text-emerald-400">
                                        Absen Mandiri
                                    </span>
                                </div>
                                <span v-else class="text-muted-foreground/60 italic">-</span>
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <!-- Jika ada foto bukti -->
                                <div v-if="teacher.foto_bukti" class="flex items-center gap-2">
                                    <button 
                                        @click="openPhotoModal(teacher)" 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 transition-colors font-medium text-xs"
                                    >
                                        <Camera class="w-3.5 h-3.5" />
                                        <span>Lihat Foto</span>
                                    </button>
                                </div>
                                <!-- Jika ada catatan keterangan -->
                                <div v-else-if="teacher.keterangan" class="max-w-xs truncate text-muted-foreground" :title="teacher.keterangan">
                                    <span>{{ teacher.keterangan }}</span>
                                    <div v-if="teacher.confirmed_by" class="text-[10px] text-slate-400 mt-0.5">
                                        Oleh: {{ teacher.confirmed_by }}
                                    </div>
                                </div>
                                <span v-else class="text-muted-foreground/50 italic">-</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <Button 
                                    size="sm" 
                                    :variant="teacher.is_confirmed ? 'outline' : 'default'"
                                    @click="openConfirmModal(teacher)"
                                    class="rounded-xl text-xs font-semibold h-8 px-3"
                                    :class="!teacher.is_confirmed ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs' : ''"
                                >
                                    {{ teacher.is_confirmed ? 'Ubah Status' : 'Konfirmasi' }}
                                </Button>
                            </td>
                        </tr>

                        <tr v-if="filteredTeachers.length === 0">
                            <td colspan="7" class="py-12 text-center text-muted-foreground italic">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Users class="w-8 h-8 opacity-20" />
                                    <span>Tidak ada data guru terjadwal GMS untuk filter ini.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 2. TAMPILAN HP / MOBILE: CARD LIST VIEW (block md:hidden) -->
            <div class="block md:hidden p-3 space-y-3 bg-slate-50/50 dark:bg-slate-950/40">
                <div 
                    v-for="(teacher, index) in filteredTeachers" 
                    :key="teacher.user_id"
                    class="p-4 rounded-2xl border bg-white dark:bg-slate-900 shadow-xs space-y-3 transition-all"
                    :class="[
                        !teacher.is_confirmed 
                            ? 'border-amber-300/80 bg-amber-50/20 dark:bg-amber-950/10' 
                            : 'border-border/80'
                    ]"
                >
                    <!-- Header Kartu: No, Nama, Tipe Guru & Status Badge -->
                    <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-border/60">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-[10px] flex items-center justify-center shrink-0">
                                {{ index + 1 }}
                            </span>
                            <div class="min-w-0">
                                <h4 class="font-bold text-foreground text-sm leading-snug truncate">
                                    {{ teacher.name }}
                                </h4>
                                <span class="inline-block text-[10px] font-medium px-1.5 py-0.2 rounded bg-muted text-muted-foreground mt-0.5">
                                    {{ teacher.tipe_guru }}
                                </span>
                            </div>
                        </div>

                        <!-- Status Badge di Pojok Kanan -->
                        <div class="shrink-0">
                            <span 
                                v-if="teacher.status === 'Hadir'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300/80"
                            >
                                <Check class="w-3 h-3" /> Hadir
                            </span>
                            <span 
                                v-else-if="teacher.status === 'Izin'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-300/80"
                            >
                                <FileText class="w-3 h-3" /> Izin
                            </span>
                            <span 
                                v-else-if="teacher.status === 'Sakit'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300/80"
                            >
                                <AlertTriangle class="w-3 h-3" /> Sakit
                            </span>
                            <span 
                                v-else-if="teacher.status === 'Alpha'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 border border-red-300/80"
                            >
                                <XCircle class="w-3 h-3" /> Alpha
                            </span>
                            <span 
                                v-else
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-amber-300/60"
                            >
                                <Clock class="w-3 h-3 text-amber-500" /> Belum
                            </span>
                        </div>
                    </div>

                    <!-- Informasi Detail Kehadiran / Bukti -->
                    <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-xl border border-border/50">
                        <!-- Waktu & Metode -->
                        <div class="space-y-0.5">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Waktu / Metode:</span>
                            <div v-if="teacher.is_confirmed" class="font-medium text-slate-800 dark:text-slate-200">
                                <span>{{ teacher.waktu_absen }} WITA</span>
                                <span class="block text-[10px]" :class="teacher.is_manual ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400'">
                                    {{ teacher.is_manual ? 'Konfirmasi Manual' : 'Absen Mandiri' }}
                                </span>
                            </div>
                            <span v-else class="text-slate-400 italic">Belum tercatat</span>
                        </div>

                        <!-- Bukti / Dokumentasi -->
                        <div class="space-y-0.5">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Foto / Bukti:</span>
                            <div v-if="teacher.foto_bukti">
                                <button 
                                    @click="openPhotoModal(teacher)" 
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 text-[11px] font-semibold"
                                >
                                    <Camera class="w-3 h-3" />
                                    <span>Lihat Foto</span>
                                </button>
                            </div>
                            <div v-else-if="teacher.confirmed_by" class="text-[11px] text-blue-600 dark:text-blue-400">
                                Manual oleh {{ teacher.confirmed_by }}
                            </div>
                            <span v-else class="text-slate-400 italic">Tanpa foto</span>
                        </div>

                        <!-- Keterangan Alasan jika ada -->
                        <div v-if="teacher.keterangan" class="col-span-2 pt-1 border-t border-border/40 text-xs">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Keterangan:</span>
                            <p class="text-slate-800 dark:text-slate-200 italic mt-0.5 leading-relaxed">
                                "{{ teacher.keterangan }}"
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Aksi Konfirmasi Mobile -->
                    <Button 
                        @click="openConfirmModal(teacher)" 
                        class="w-full rounded-xl text-xs font-bold h-10 shadow-xs gap-2 transition-all active:scale-98"
                        :class="!teacher.is_confirmed 
                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white' 
                            : 'bg-white dark:bg-slate-800 border border-border text-foreground hover:bg-muted'"
                        :variant="teacher.is_confirmed ? 'outline' : 'default'"
                    >
                        <CheckCircle2 v-if="!teacher.is_confirmed" class="w-4 h-4" />
                        <span>{{ teacher.is_confirmed ? 'Ubah Status Kehadiran' : 'Konfirmasi Kehadiran' }}</span>
                    </Button>
                </div>

                <!-- Empty State Mobile -->
                <div v-if="filteredTeachers.length === 0" class="py-12 px-4 text-center bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-border">
                    <Users class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                    <p class="font-bold text-foreground text-sm">Tidak ada guru ditemukan</p>
                    <p class="text-xs text-muted-foreground mt-1 max-w-xs mx-auto">
                        Tidak ada guru terjadwal GMS yang cocok dengan filter atau kata kunci pencarian.
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Kehadiran -->
        <Dialog :open="isConfirmModalOpen" @update:open="isConfirmModalOpen = $event">
            <DialogContent class="sm:max-w-[480px] max-h-[90vh] overflow-y-auto rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-xl font-bold flex items-center gap-2">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600" />
                        Konfirmasi Kehadiran GMS
                    </DialogTitle>
                    <DialogDescription>
                        Atur status kehadiran untuk guru terpilih pada jadwal GMS.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedTeacher" class="space-y-4 py-2">
                    <!-- Info Guru -->
                    <div class="p-3.5 rounded-xl bg-muted/50 border border-border/60">
                        <p class="text-xs text-muted-foreground">Guru Terpilih:</p>
                        <h4 class="font-bold text-foreground text-base mt-0.5">{{ selectedTeacher.name }}</h4>
                        <div class="flex items-center gap-3 text-xs text-muted-foreground mt-1">
                            <span>Hari: <strong>{{ namaHari }}</strong></span>
                            <span>•</span>
                            <span>Tanggal: <strong>{{ formattedDate }}</strong></span>
                        </div>
                    </div>

                    <!-- Pilihan Status -->
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-foreground uppercase tracking-wide">
                            Pilih Status Kehadiran <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <!-- Alpha -->
                            <label 
                                class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="confirmForm.status === 'Alpha' ? 'border-red-500 bg-red-50 text-red-800 dark:bg-red-950/40 dark:text-red-200 font-bold ring-2 ring-red-500/20' : 'border-border hover:bg-muted/40'"
                            >
                                <input type="radio" value="Alpha" v-model="confirmForm.status" class="sr-only" />
                                <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0" :class="confirmForm.status === 'Alpha' ? 'border-red-600 bg-red-600' : 'border-muted-foreground'">
                                    <div v-if="confirmForm.status === 'Alpha'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                </div>
                                <span class="text-sm">Alpha</span>
                            </label>

                            <!-- Izin -->
                            <label 
                                class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="confirmForm.status === 'Izin' ? 'border-blue-500 bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-200 font-bold ring-2 ring-blue-500/20' : 'border-border hover:bg-muted/40'"
                            >
                                <input type="radio" value="Izin" v-model="confirmForm.status" class="sr-only" />
                                <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0" :class="confirmForm.status === 'Izin' ? 'border-blue-600 bg-blue-600' : 'border-muted-foreground'">
                                    <div v-if="confirmForm.status === 'Izin'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                </div>
                                <span class="text-sm">Izin</span>
                            </label>

                            <!-- Sakit -->
                            <label 
                                class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="confirmForm.status === 'Sakit' ? 'border-amber-500 bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200 font-bold ring-2 ring-amber-500/20' : 'border-border hover:bg-muted/40'"
                            >
                                <input type="radio" value="Sakit" v-model="confirmForm.status" class="sr-only" />
                                <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0" :class="confirmForm.status === 'Sakit' ? 'border-amber-600 bg-amber-600' : 'border-muted-foreground'">
                                    <div v-if="confirmForm.status === 'Sakit'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                </div>
                                <span class="text-sm">Sakit</span>
                            </label>

                            <!-- Hadir -->
                            <label 
                                class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="confirmForm.status === 'Hadir' ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200 font-bold ring-2 ring-emerald-500/20' : 'border-border hover:bg-muted/40'"
                            >
                                <input type="radio" value="Hadir" v-model="confirmForm.status" class="sr-only" />
                                <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0" :class="confirmForm.status === 'Hadir' ? 'border-emerald-600 bg-emerald-600' : 'border-muted-foreground'">
                                    <div v-if="confirmForm.status === 'Hadir'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                </div>
                                <span class="text-sm">Hadir (Manual)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Input Keterangan -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-foreground flex items-center justify-between">
                            <span>
                                Keterangan / Alasan
                                <span v-if="confirmForm.status === 'Hadir'" class="text-red-500 font-bold">(Wajib)</span>
                                <span v-else class="text-muted-foreground font-normal">(Opsional)</span>
                            </span>
                            <span v-if="confirmForm.status === 'Hadir'" class="text-[11px] text-amber-600 dark:text-amber-400 font-normal">
                                Wajib sertakan alasan hadir manual
                            </span>
                        </label>
                        <textarea 
                            v-model="confirmForm.keterangan"
                            rows="3"
                            :placeholder="confirmForm.status === 'Hadir' ? 'Contoh: Guru hadir di lokasi piket namun perangkat kamera handphone bermasalah...' : 'Tuliskan alasan izin / sakit / keterangan lainnya jika ada...'"
                            class="w-full rounded-xl border border-input bg-background p-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        ></textarea>
                    </div>

                    <!-- Error Alert jika ada -->
                    <div v-if="confirmErrorMessage" class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-start gap-2.5 dark:bg-red-950/40 dark:border-red-900/60 dark:text-red-300">
                        <AlertCircle class="w-4 h-4 text-red-600 dark:text-red-400 mt-0.5 shrink-0" />
                        <span class="leading-relaxed">{{ confirmErrorMessage }}</span>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isConfirmModalOpen = false" class="rounded-xl" :disabled="confirmForm.processing">
                        Batal
                    </Button>
                    <Button 
                        @click="submitConfirmation" 
                        class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold flex items-center gap-2"
                        :disabled="confirmForm.processing"
                    >
                        <span v-if="confirmForm.processing" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span>{{ confirmForm.processing ? 'Menyimpan...' : 'Simpan Konfirmasi' }}</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Lihat Foto Bukti -->
        <Dialog :open="isPhotoModalOpen" @update:open="isPhotoModalOpen = $event">
            <DialogContent class="sm:max-w-md rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold flex items-center gap-2">
                        <Camera class="w-5 h-5 text-emerald-600" />
                        Foto Bukti Kehadiran GMS
                    </DialogTitle>
                    <DialogDescription>
                        Dokumentasi absensi mandiri guru {{ photoTeacherName }}
                    </DialogDescription>
                </DialogHeader>
                <div v-if="photoPreviewUrl" class="my-2 rounded-xl overflow-hidden border border-border shadow-inner bg-slate-950 flex items-center justify-center max-h-[450px]">
                    <img :src="photoPreviewUrl" alt="Foto Bukti GMS" class="w-full h-full object-contain" />
                </div>
                <DialogFooter>
                    <Button @click="isPhotoModalOpen = false" class="w-full rounded-xl">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
