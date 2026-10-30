<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogDescription, 
    DialogFooter 
} from "@/components/ui/dialog";
import { 
    Users, 
    CheckCircle2, 
    XCircle, 
    Search, 
    Calendar as CalendarIcon, 
    Save, 
    AlertCircle, 
    HelpCircle, 
    HeartPulse, 
    FileText, 
    Edit3,
    RotateCcw
} from "@lucide/vue";
import { ref, watch, computed } from "vue";
import Swal from 'sweetalert2';

interface TeacherAttendance {
    id?: number;
    user_id?: number | null;
    pegawai_administrasi_id?: number | null;
    id_ref: string;
    type: 'guru' | 'pegawai';
    type_label: string;
    name: string;
    status: 'Hadir' | 'Alpha' | 'Sakit' | 'Izin' | null;
    is_hadir: boolean;
    is_tidak_hadir: boolean;
    keterangan: string;
    is_already_saved: boolean;
}

const props = defineProps<{
    date: string;
    teachers: any[];
    totalTeachers?: number;
    totalPegawai?: number;
}>();

const selectedDate = ref(props.date);
const searchQuery = ref('');
const activeStatusFilter = ref<'semua' | 'belum' | 'hadir' | 'tidak_hadir'>('semua');

// Helper to initialize local attendance state
const initList = (teachers: any[]): TeacherAttendance[] => {
    return teachers.map(t => {
        const isHadir = t.status === 'Hadir' || (t.is_hadir && !t.status);
        const isTidakHadir = ['Alpha', 'Sakit', 'Izin'].includes(t.status);
        return {
            id: t.id,
            user_id: t.user_id ?? null,
            pegawai_administrasi_id: t.pegawai_administrasi_id ?? null,
            id_ref: t.id_ref || (t.user_id ? `guru-${t.user_id}` : `pegawai-${t.pegawai_administrasi_id}`),
            type: t.type || (t.user_id ? 'guru' : 'pegawai'),
            type_label: t.type_label || (t.user_id ? 'Guru' : 'Pegawai Administrasi'),
            name: t.name,
            status: t.status || (isHadir ? 'Hadir' : null),
            is_hadir: isHadir,
            is_tidak_hadir: isTidakHadir,
            keterangan: t.keterangan || '',
            is_already_saved: !!t.is_already_saved,
        };
    });
};

const attendanceList = ref<TeacherAttendance[]>(initList(props.teachers));

// Modal State
const isModalOpen = ref(false);
const activeTeacher = ref<TeacherAttendance | null>(null);
const selectedReason = ref<'Alpha' | 'Sakit' | 'Izin'>('Alpha');
const customNote = ref('');

const form = useForm({
    date: props.date,
    attendances: []
});

watch(selectedDate, (newDate) => {
    router.get('/absensi-upacara', { date: newDate }, {
        preserveState: true,
        replace: true,
        onSuccess: (page) => {
            selectedDate.value = newDate;
            attendanceList.value = initList((page.props.teachers as any[]) || []);
        }
    });
});

const filteredTeachers = computed(() => {
    return attendanceList.value.filter(t => {
        // Tab Status Filter
        if (activeStatusFilter.value === 'belum' && t.status) return false;
        if (activeStatusFilter.value === 'hadir' && t.status !== 'Hadir') return false;
        if (activeStatusFilter.value === 'tidak_hadir' && (!t.status || t.status === 'Hadir')) return false;

        // Search Query
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            return t.name.toLowerCase().includes(query);
        }
        return true;
    });
});

const resetSingleTeacher = (teacher: TeacherAttendance) => {
    teacher.status = null;
    teacher.is_hadir = false;
    teacher.is_tidak_hadir = false;
    teacher.keterangan = '';
};

// Summary Counts
const countHadir = computed(() => attendanceList.value.filter(t => t.status === 'Hadir').length);
const countAlpha = computed(() => attendanceList.value.filter(t => t.status === 'Alpha').length);
const countSakit = computed(() => attendanceList.value.filter(t => t.status === 'Sakit').length);
const countIzin = computed(() => attendanceList.value.filter(t => t.status === 'Izin').length);
const countTidakHadir = computed(() => countAlpha.value + countSakit.value + countIzin.value);
const countBelum = computed(() => attendanceList.value.filter(t => !t.status).length);

// Handlers for checkboxes
const handleHadirClick = (teacher: TeacherAttendance) => {
    if (teacher.status === 'Hadir') {
        // Toggle off
        teacher.status = null;
        teacher.is_hadir = false;
        teacher.is_tidak_hadir = false;
        teacher.keterangan = '';
    } else {
        // Mark as Hadir
        teacher.status = 'Hadir';
        teacher.is_hadir = true;
        teacher.is_tidak_hadir = false;
        teacher.keterangan = '';
    }
};

const handleTidakHadirClick = (teacher: TeacherAttendance) => {
    // Langsung buka modal pemilihan keterangan
    openReasonModal(teacher);
};

const openReasonModal = (teacher: TeacherAttendance) => {
    activeTeacher.value = teacher;
    selectedReason.value = (teacher.status && teacher.status !== 'Hadir') ? teacher.status : 'Alpha';
    customNote.value = teacher.keterangan || '';
    isModalOpen.value = true;
};

const confirmKetidakhadiran = () => {
    if (!activeTeacher.value) return;

    const teacher = activeTeacher.value;
    const reason = selectedReason.value;
    const note = customNote.value;

    // Tutup modal terlebih dahulu agar tidak menghalangi SweetAlert
    isModalOpen.value = false;

    // Beri jeda singkat agar overlay modal Radix selesai transisi keluar
    setTimeout(() => {
        Swal.fire({
            title: 'Konfirmasi Ketidakhadiran',
            html: `Apakah Anda yakin menandai <b>${teacher.name}</b> sebagai <b>${reason.toUpperCase()}</b> pada upacara hari ini?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: reason === 'Alpha' ? '#ef4444' : (reason === 'Sakit' ? '#f59e0b' : '#3b82f6'),
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Tetapkan',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                teacher.status = reason;
                teacher.is_hadir = false;
                teacher.is_tidak_hadir = true;
                teacher.keterangan = note;

                Swal.fire({
                    icon: 'success',
                    title: 'Keterangan Ditetapkan!',
                    text: `${teacher.name} berhasil ditandai sebagai ${reason}.`,
                    timer: 1500,
                    showConfirmButton: false,
                });
            }
        });
    }, 150);
};

const resetKetidakhadiran = () => {
    if (!activeTeacher.value) return;
    const teacher = activeTeacher.value;

    isModalOpen.value = false;

    setTimeout(() => {
        Swal.fire({
            title: 'Kosongkan Keterangan?',
            html: `Hapus status <b>${teacher.status}</b> untuk <b>${teacher.name}</b>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Kosongkan',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                teacher.status = null;
                teacher.is_hadir = false;
                teacher.is_tidak_hadir = false;
                teacher.keterangan = '';

                Swal.fire({
                    icon: 'info',
                    title: 'Dikosongkan',
                    text: `Status kehadiran ${teacher.name} telah direset.`,
                    timer: 1500,
                    showConfirmButton: false,
                });
            }
        });
    }, 150);
};

// Batch marks
const markAllHadir = () => {
    Swal.fire({
        title: 'Hadirkan Semua?',
        text: 'Semua guru dan pegawai yang belum memiliki keterangan akan ditandai sebagai Hadir.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hadirkan Semua',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            filteredTeachers.value.forEach(t => {
                if (!t.status) {
                    t.status = 'Hadir';
                    t.is_hadir = true;
                    t.is_tidak_hadir = false;
                }
            });
        }
    });
};

const resetAll = () => {
    Swal.fire({
        title: 'Kosongkan Semua?',
        text: 'Semua centang kehadiran dan ketidakhadiran yang sedang ditampilkan akan dikosongkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Kosongkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            filteredTeachers.value.forEach(t => {
                t.status = null;
                t.is_hadir = false;
                t.is_tidak_hadir = false;
                t.keterangan = '';
            });
        }
    });
};

// Form submit
const submit = () => {
    Swal.fire({
        title: 'Simpan Absensi Upacara?',
        html: `Data kehadiran upacara tanggal <b>${selectedDate.value}</b> akan disimpan ke sistem.<br><br>
               <span class="text-xs text-slate-500">
               Total Hadir: ${countHadir.value} | Tidak Hadir: ${countTidakHadir.value} (Alpha: ${countAlpha.value}, Sakit: ${countSakit.value}, Izin: ${countIzin.value})
               </span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Simpan Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.date = selectedDate.value;
            form.attendances = attendanceList.value.map(a => ({
                user_id: a.user_id,
                pegawai_administrasi_id: a.pegawai_administrasi_id,
                status: a.status,
                is_hadir: a.status === 'Hadir',
                keterangan: a.keterangan
            })) as any;

            form.post('/absensi-upacara', {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan!',
                        text: 'Absensi upacara berhasil disimpan.',
                        showConfirmButton: false,
                        timer: 2000
                    });
                },
                onError: (errors) => {
                    console.error('Validation errors:', errors);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: 'Terjadi kesalahan saat menyimpan data absensi upacara.',
                        confirmButtonColor: '#ef4444',
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Absensi Upacara" />
    <div class="p-3 md:p-6 space-y-4 md:space-y-6 pb-24 md:pb-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-4 md:pb-6 dark:border-slate-800">
            <div>
                <h1 class="text-xl md:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5 md:gap-3">
                    <div class="p-2 md:p-2.5 rounded-xl bg-emerald-100 dark:bg-emerald-950/50">
                        <Users class="w-5 h-5 md:w-7 md:h-7 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    Absensi Upacara Bendera
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 md:mt-2 max-w-2xl text-xs md:text-base leading-relaxed">
                    Kelola kehadiran dan ketidakhadiran upacara bendera untuk guru, wakasek, dan pegawai administrasi. Centang hadir atau tetapkan alasan jika tidak hadir.
                </p>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                <div class="relative flex-1 md:flex-initial">
                    <Label for="date" class="sr-only">Tanggal</Label>
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <CalendarIcon class="h-4 w-4 text-slate-400" />
                    </div>
                    <Input id="date" type="date" v-model="selectedDate" class="pl-10 w-full md:w-[200px] h-10 text-xs md:text-sm" />
                </div>
                <Button 
                    @click="submit" 
                    :disabled="form.processing" 
                    class="bg-emerald-600 hover:bg-emerald-700 text-white gap-2 shadow-sm transition-all h-10 shrink-0 font-bold"
                >
                    <Save class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ form.processing ? 'Menyimpan...' : 'Simpan Absensi' }}</span>
                    <span class="sm:hidden">{{ form.processing ? '...' : 'Simpan' }}</span>
                </Button>
            </div>
        </div>

        <!-- Summary Stats Cards (2 baris x 3 kolom di HP, 6 kolom di desktop) -->
        <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-6 gap-2 md:gap-3">
            <div class="p-2.5 md:p-3 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
                <span class="text-[10px] md:text-xs font-semibold text-slate-500">Total Guru</span>
                <p class="text-lg md:text-xl font-bold text-slate-900 dark:text-slate-100 mt-0.5">{{ attendanceList.length }}</p>
            </div>
            <div class="p-2.5 md:p-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 shadow-xs">
                <span class="text-[10px] md:text-xs font-semibold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                    <CheckCircle2 class="w-3 h-3 md:w-3.5 md:h-3.5" /> Hadir
                </span>
                <p class="text-lg md:text-xl font-bold text-emerald-700 dark:text-emerald-400 mt-0.5">{{ countHadir }}</p>
            </div>
            <div class="p-2.5 md:p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
                <span class="text-[10px] md:text-xs font-semibold text-slate-500 flex items-center gap-1">
                    <HelpCircle class="w-3 h-3 md:w-3.5 md:h-3.5" /> Belum
                </span>
                <p class="text-lg md:text-xl font-bold text-slate-600 dark:text-slate-300 mt-0.5">{{ countBelum }}</p>
            </div>
            <div class="p-2.5 md:p-3 rounded-xl bg-rose-50/60 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800 shadow-xs">
                <span class="text-[10px] md:text-xs font-semibold text-rose-700 dark:text-rose-400 flex items-center gap-1">
                    <XCircle class="w-3 h-3 md:w-3.5 md:h-3.5" /> Alpha
                </span>
                <p class="text-lg md:text-xl font-bold text-rose-700 dark:text-rose-400 mt-0.5">{{ countAlpha }}</p>
            </div>
            <div class="p-2.5 md:p-3 rounded-xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 shadow-xs">
                <span class="text-[10px] md:text-xs font-semibold text-amber-700 dark:text-amber-400 flex items-center gap-1">
                    <HeartPulse class="w-3 h-3 md:w-3.5 md:h-3.5" /> Sakit
                </span>
                <p class="text-lg md:text-xl font-bold text-amber-700 dark:text-amber-400 mt-0.5">{{ countSakit }}</p>
            </div>
            <div class="p-2.5 md:p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 shadow-xs">
                <span class="text-[10px] md:text-xs font-semibold text-blue-700 dark:text-blue-400 flex items-center gap-1">
                    <FileText class="w-3 h-3 md:w-3.5 md:h-3.5" /> Izin
                </span>
                <p class="text-lg md:text-xl font-bold text-blue-700 dark:text-blue-400 mt-0.5">{{ countIzin }}</p>
            </div>
        </div>

        <!-- Main Content (Card Wrapper) -->
        <Card class="shadow-sm border-slate-200 dark:border-slate-800 overflow-hidden">
            <CardHeader class="p-3.5 md:p-6 pb-3 border-b dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/20">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <CardTitle class="text-base md:text-lg">Daftar Kehadiran Upacara</CardTitle>
                        <CardDescription class="text-xs md:text-sm">
                            Pilih status kehadiran guru pada jadwal upacara.
                        </CardDescription>
                    </div>

                    <!-- Search Input -->
                    <div class="w-full sm:w-[240px]">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-4 w-4 text-slate-400" />
                            </div>
                            <Input v-model="searchQuery" placeholder="Cari nama guru..." class="pl-9 bg-white dark:bg-slate-950 h-9 text-xs md:text-sm w-full" />
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs & Quick Action Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mt-3 pt-2.5 border-t border-slate-200/60 dark:border-slate-800">
                    <!-- Filter Status Tabs (Scrollable on small screens) -->
                    <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-900 rounded-xl overflow-x-auto max-w-full">
                        <button
                            type="button"
                            @click="activeStatusFilter = 'semua'"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition-all"
                            :class="activeStatusFilter === 'semua' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                        >
                            Semua ({{ attendanceList.length }})
                        </button>
                        <button
                            type="button"
                            @click="activeStatusFilter = 'belum'"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition-all"
                            :class="activeStatusFilter === 'belum' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                        >
                            Belum ({{ countBelum }})
                        </button>
                        <button
                            type="button"
                            @click="activeStatusFilter = 'hadir'"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition-all text-emerald-700 dark:text-emerald-400"
                            :class="activeStatusFilter === 'hadir' ? 'bg-white dark:bg-slate-800 shadow-xs font-bold' : 'opacity-80'"
                        >
                            Hadir ({{ countHadir }})
                        </button>
                        <button
                            type="button"
                            @click="activeStatusFilter = 'tidak_hadir'"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition-all text-rose-700 dark:text-rose-400"
                            :class="activeStatusFilter === 'tidak_hadir' ? 'bg-white dark:bg-slate-800 shadow-xs font-bold' : 'opacity-80'"
                        >
                            Tidak Hadir ({{ countTidakHadir }})
                        </button>
                    </div>

                    <!-- Quick Batch Actions -->
                    <div class="flex items-center gap-2">
                        <Button type="button" variant="outline" size="sm" @click="markAllHadir" class="text-xs h-8 text-emerald-700 hover:text-emerald-800 hover:bg-emerald-50 border-emerald-200">
                            <CheckCircle2 class="w-3.5 h-3.5 mr-1 text-emerald-600" />
                            <span class="hidden sm:inline">Hadirkan Semua yang Belum</span>
                            <span class="sm:hidden">Semua Hadir</span>
                        </Button>
                        <Button type="button" variant="outline" size="sm" @click="resetAll" class="text-xs h-8 text-slate-600 hover:text-rose-700 hover:bg-rose-50 border-slate-200">
                            <RotateCcw class="w-3.5 h-3.5 mr-1" />
                            <span class="hidden sm:inline">Kosongkan Semua</span>
                            <span class="sm:hidden">Reset</span>
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <CardContent class="p-0">
                <!-- 1. TAMPILAN DESKTOP: TABLE VIEW (hidden md:block) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-600 uppercase bg-slate-100/70 dark:bg-slate-900 border-b dark:border-slate-800">
                            <tr>
                                <th scope="col" class="px-4 py-3.5 font-bold w-14 text-center">No</th>
                                <th scope="col" class="px-6 py-3.5 font-bold">Nama Lengkap Guru / Pegawai</th>
                                <th scope="col" class="px-6 py-3.5 font-bold text-center w-36">
                                    <div class="flex items-center justify-center gap-1.5 text-emerald-700 dark:text-emerald-400">
                                        <CheckCircle2 class="w-4 h-4" />
                                        <span>Hadir Upacara</span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3.5 font-bold text-center w-48">
                                    <div class="flex items-center justify-center gap-1.5 text-rose-700 dark:text-rose-400">
                                        <XCircle class="w-4 h-4" />
                                        <span>Tidak Hadir Upacara</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr 
                                v-for="(teacher, index) in filteredTeachers" 
                                :key="teacher.id_ref" 
                                class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-900/50"
                                :class="{
                                    'bg-emerald-50/20 dark:bg-emerald-950/10': teacher.status === 'Hadir',
                                    'bg-rose-50/25 dark:bg-rose-950/15': teacher.status === 'Alpha',
                                    'bg-amber-50/25 dark:bg-amber-950/15': teacher.status === 'Sakit',
                                    'bg-blue-50/25 dark:bg-blue-950/15': teacher.status === 'Izin'
                                }"
                            >
                                <td class="px-4 py-4 text-center text-slate-500 font-medium">{{ index + 1 }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-slate-900 dark:text-slate-100 text-sm md:text-base">
                                                {{ teacher.name }}
                                            </span>
                                            <Badge 
                                                variant="outline" 
                                                class="text-[10px] font-semibold py-0 px-2"
                                                :class="teacher.type === 'pegawai' 
                                                    ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800' 
                                                    : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'"
                                            >
                                                {{ teacher.type_label }}
                                            </Badge>
                                        </div>
                                        <span v-if="teacher.keterangan" class="text-xs text-slate-500 italic truncate max-w-xs" :title="teacher.keterangan">
                                            Catatan: {{ teacher.keterangan }}
                                        </span>
                                    </div>
                                </td>
                                
                                <!-- Kolom 1: Hadir Upacara (Checkbox Hijau) -->
                                <td class="px-6 py-4 text-center align-middle">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <input 
                                            type="checkbox"
                                            :id="`check-hadir-${teacher.id_ref}`"
                                            :checked="teacher.status === 'Hadir'"
                                            @change="handleHadirClick(teacher)"
                                            class="w-5 h-5 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 focus:ring-offset-0 cursor-pointer accent-emerald-600 transition-transform active:scale-95"
                                        />
                                        <Badge 
                                            v-if="teacher.status === 'Hadir'" 
                                            variant="outline" 
                                            class="text-[10px] font-semibold bg-emerald-100/70 text-emerald-800 border-emerald-300 uppercase px-1.5 py-0"
                                        >
                                            Hadir
                                        </Badge>
                                    </div>
                                </td>

                                <!-- Kolom 2: Tidak Hadir Upacara (Checkbox Merah) -->
                                <td class="px-6 py-4 text-center align-middle">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <input 
                                            type="checkbox"
                                            :id="`check-tidakhadir-${teacher.id_ref}`"
                                            :checked="teacher.is_tidak_hadir"
                                            @click.prevent="handleTidakHadirClick(teacher)"
                                            class="w-5 h-5 text-rose-600 rounded border-slate-300 focus:ring-rose-500 focus:ring-offset-0 cursor-pointer accent-rose-600 transition-transform active:scale-95"
                                        />
                                        
                                        <!-- Badge Keterangan Tidak Hadir (Clickable to change reason) -->
                                        <button 
                                            v-if="teacher.is_tidak_hadir" 
                                            type="button"
                                            @click="handleTidakHadirClick(teacher)"
                                            class="cursor-pointer group flex items-center gap-1"
                                            title="Klik untuk ubah keterangan"
                                        >
                                            <Badge 
                                                variant="outline" 
                                                :class="{
                                                    'bg-rose-100 text-rose-800 border-rose-300 group-hover:bg-rose-200': teacher.status === 'Alpha',
                                                    'bg-amber-100 text-amber-800 border-amber-300 group-hover:bg-amber-200': teacher.status === 'Sakit',
                                                    'bg-blue-100 text-blue-800 border-blue-300 group-hover:bg-blue-200': teacher.status === 'Izin'
                                                }"
                                                class="text-[10px] font-bold uppercase tracking-wider px-2 py-0 transition-colors"
                                            >
                                                {{ teacher.status }}
                                                <Edit3 class="w-2.5 h-2.5 ml-1 opacity-70 group-hover:opacity-100" />
                                            </Badge>
                                        </button>
                                        <span v-else class="text-[11px] text-slate-400">
                                            -
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredTeachers.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Users class="w-8 h-8 text-slate-300" />
                                        <p class="text-sm font-medium">Tidak ada data guru atau pegawai yang cocok dengan filter pencarian.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 2. TAMPILAN HP / MOBILE: CARD LIST VIEW (block md:hidden) -->
                <div class="block md:hidden p-3 space-y-2.5 bg-slate-50/50 dark:bg-slate-950/40">
                    <div 
                        v-for="(teacher, index) in filteredTeachers" 
                        :key="teacher.id_ref"
                        class="p-3.5 rounded-2xl border transition-all shadow-xs bg-white dark:bg-slate-900"
                        :class="{
                            'border-emerald-300/90 bg-emerald-50/30 dark:bg-emerald-950/20 dark:border-emerald-800': teacher.status === 'Hadir',
                            'border-rose-300/90 bg-rose-50/30 dark:bg-rose-950/20 dark:border-rose-800': teacher.status === 'Alpha',
                            'border-amber-300/90 bg-amber-50/30 dark:bg-amber-950/20 dark:border-amber-800': teacher.status === 'Sakit',
                            'border-blue-300/90 bg-blue-50/30 dark:bg-blue-950/20 dark:border-blue-800': teacher.status === 'Izin',
                            'border-slate-200 dark:border-slate-800': !teacher.status
                        }"
                    >
                        <!-- Header Kartu: No Urut, Nama Guru & Badge Status -->
                        <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-[10px] flex items-center justify-center shrink-0">
                                    {{ index + 1 }}
                                </span>
                                <div class="flex flex-col min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-bold text-slate-900 dark:text-slate-100 text-sm leading-snug">
                                            {{ teacher.name }}
                                        </span>
                                        <Badge 
                                            variant="outline" 
                                            class="text-[9px] font-semibold py-0 px-1.5"
                                            :class="teacher.type === 'pegawai' 
                                                ? 'bg-blue-50 text-blue-700 border-blue-200' 
                                                : 'bg-slate-100 text-slate-600 border-slate-200'"
                                        >
                                            {{ teacher.type_label }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Badge di Pojok Kanan -->
                            <div class="shrink-0 flex items-center gap-1">
                                <span 
                                    v-if="teacher.status === 'Hadir'" 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300"
                                >
                                    <CheckCircle2 class="w-3 h-3" /> Hadir
                                </span>
                                <span 
                                    v-else-if="teacher.status === 'Alpha'" 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-300"
                                >
                                    <XCircle class="w-3 h-3" /> Alpha
                                </span>
                                <span 
                                    v-else-if="teacher.status === 'Sakit'" 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300"
                                >
                                    <HeartPulse class="w-3 h-3" /> Sakit
                                </span>
                                <span 
                                    v-else-if="teacher.status === 'Izin'" 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-300"
                                >
                                    <FileText class="w-3 h-3" /> Izin
                                </span>
                                <span 
                                    v-else 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                                >
                                    <HelpCircle class="w-3 h-3" /> Belum
                                </span>

                                <!-- Quick Reset Button if already marked -->
                                <button
                                    v-if="teacher.status"
                                    type="button"
                                    @click="resetSingleTeacher(teacher)"
                                    class="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                    title="Reset ke status belum absen"
                                >
                                    <RotateCcw class="w-3 h-3" />
                                </button>
                            </div>
                        </div>

                        <!-- Catatan jika ada -->
                        <div v-if="teacher.keterangan" class="my-2 p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 flex items-start gap-1.5">
                            <Edit3 class="w-3 h-3 text-slate-400 mt-0.5 shrink-0" />
                            <span class="italic leading-relaxed">Catatan: {{ teacher.keterangan }}</span>
                        </div>

                        <!-- Tombol Aksi Touch-Friendly Ukuran Jari Jempol -->
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <!-- Tombol 1: Hadir -->
                            <button
                                type="button"
                                @click="handleHadirClick(teacher)"
                                class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl font-bold text-xs transition-all active:scale-95 shadow-xs"
                                :class="teacher.status === 'Hadir' 
                                    ? 'bg-emerald-600 text-white shadow-emerald-600/20' 
                                    : 'bg-white dark:bg-slate-800 border border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40'"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                <span>{{ teacher.status === 'Hadir' ? 'Hadir (Aktif)' : 'Hadir' }}</span>
                            </button>

                            <!-- Tombol 2: Tidak Hadir / Ubah Alasan -->
                            <button
                                type="button"
                                @click="handleTidakHadirClick(teacher)"
                                class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl font-bold text-xs transition-all active:scale-95 shadow-xs"
                                :class="teacher.is_tidak_hadir 
                                    ? (teacher.status === 'Alpha' 
                                        ? 'bg-rose-600 text-white' 
                                        : (teacher.status === 'Sakit' 
                                            ? 'bg-amber-600 text-white' 
                                            : 'bg-blue-600 text-white'))
                                    : 'bg-white dark:bg-slate-800 border border-rose-300 dark:border-rose-800 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40'"
                            >
                                <template v-if="teacher.is_tidak_hadir">
                                    <Edit3 class="w-3.5 h-3.5" />
                                    <span>Ubah ({{ teacher.status }})</span>
                                </template>
                                <template v-else>
                                    <XCircle class="w-4 h-4" />
                                    <span>Tidak Hadir</span>
                                </template>
                            </button>
                        </div>
                    </div>

                    <!-- Empty State Mobile -->
                    <div v-if="filteredTeachers.length === 0" class="py-10 text-center bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                        <Users class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                        <p class="text-sm font-medium text-slate-500">Tidak ada data guru atau pegawai yang cocok dengan filter.</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- STICKY BOTTOM ACTION BAR KHUSUS HP (Memudahkan simpan kapan saja di lapangan) -->
        <div class="block md:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-3 shadow-lg">
            <div class="flex items-center justify-between gap-3 max-w-md mx-auto">
                <div class="text-xs text-slate-600 dark:text-slate-300 leading-tight">
                    <p class="font-bold text-slate-900 dark:text-white">
                        Hadir: <span class="text-emerald-600 font-extrabold">{{ countHadir }}</span> / {{ attendanceList.length }}
                    </p>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Belum: <span class="font-semibold">{{ countBelum }}</span> | Absen: <span class="font-semibold">{{ countTidakHadir }}</span>
                    </p>
                </div>
                <Button 
                    @click="submit" 
                    :disabled="form.processing" 
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs h-10 px-5 rounded-xl shadow-md gap-2"
                >
                    <Save class="w-4 h-4" />
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Absensi' }}</span>
                </Button>
            </div>
        </div>

        <!-- MODAL KETERANGAN TIDAK HADIR UPACARA -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-md max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-rose-100 dark:bg-rose-950 text-rose-600">
                            <XCircle class="w-5 h-5" />
                        </div>
                        Keterangan Tidak Hadir Upacara
                    </DialogTitle>
                    <DialogDescription>
                        Pilih alasan ketidakhadiran upacara bendera untuk guru berikut.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <!-- Info Guru -->
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-400">Guru yang Tidak Hadir:</span>
                            <p class="font-bold text-slate-900 dark:text-slate-100 text-sm md:text-base mt-0.5">
                                {{ activeTeacher?.name }}
                            </p>
                        </div>
                        <Badge variant="outline" class="text-xs">
                            {{ selectedDate }}
                        </Badge>
                    </div>

                    <!-- Pilihan Alasan (Alpha, Sakit, Izin) -->
                    <div class="space-y-2.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Pilih Alasan Ketidakhadiran:
                        </Label>

                        <div class="grid grid-cols-1 gap-2.5">
                            <!-- Option 1: Alpha -->
                            <label 
                                class="flex items-center justify-between p-3.5 rounded-xl border-2 cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-900"
                                :class="selectedReason === 'Alpha' ? 'border-rose-500 bg-rose-50/40 dark:bg-rose-950/30' : 'border-slate-200 dark:border-slate-800'"
                            >
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="radio" 
                                        name="alasan" 
                                        value="Alpha" 
                                        v-model="selectedReason" 
                                        class="text-rose-600 focus:ring-rose-500 h-4 w-4" 
                                    />
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                            Alpha
                                            <span class="text-xs font-normal text-slate-500">(Tanpa Keterangan)</span>
                                        </p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            Guru tidak hadir tanpa konfirmasi atau pemberitahuan.
                                        </p>
                                    </div>
                                </div>
                                <div class="w-3 h-3 rounded-full bg-rose-500" v-if="selectedReason === 'Alpha'"></div>
                            </label>

                            <!-- Option 2: Sakit -->
                            <label 
                                class="flex items-center justify-between p-3.5 rounded-xl border-2 cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-900"
                                :class="selectedReason === 'Sakit' ? 'border-amber-500 bg-amber-50/40 dark:bg-amber-950/30' : 'border-slate-200 dark:border-slate-800'"
                            >
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="radio" 
                                        name="alasan" 
                                        value="Sakit" 
                                        v-model="selectedReason" 
                                        class="text-amber-600 focus:ring-amber-500 h-4 w-4" 
                                    />
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                            Sakit
                                            <span class="text-xs font-normal text-slate-500">(Kondisi Kesehatan)</span>
                                        </p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            Guru berhalangan hadir dikarenakan sakit / surat keterangan.
                                        </p>
                                    </div>
                                </div>
                                <div class="w-3 h-3 rounded-full bg-amber-500" v-if="selectedReason === 'Sakit'"></div>
                            </label>

                            <!-- Option 3: Izin -->
                            <label 
                                class="flex items-center justify-between p-3.5 rounded-xl border-2 cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-900"
                                :class="selectedReason === 'Izin' ? 'border-blue-500 bg-blue-50/40 dark:bg-blue-950/30' : 'border-slate-200 dark:border-slate-800'"
                            >
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="radio" 
                                        name="alasan" 
                                        value="Izin" 
                                        v-model="selectedReason" 
                                        class="text-blue-600 focus:ring-blue-500 h-4 w-4" 
                                    />
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                            Izin
                                            <span class="text-xs font-normal text-slate-500">(Dinas Luar / Keperluan)</span>
                                        </p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            Guru telah menyampaikan surat izin atau tugas kedinasan.
                                        </p>
                                    </div>
                                </div>
                                <div class="w-3 h-3 rounded-full bg-blue-500" v-if="selectedReason === 'Izin'"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Catatan Tambahan (Opsional) -->
                    <div class="space-y-1.5 pt-1">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Catatan Tambahan (Opsional)
                        </Label>
                        <Input 
                            v-model="customNote" 
                            placeholder="Contoh: Sakit demam / Izin rapat dinas MKKS" 
                            class="text-xs md:text-sm"
                        />
                    </div>
                </div>

                <DialogFooter class="flex flex-row justify-between items-center gap-2 pt-2">
                    <div>
                        <Button 
                            v-if="activeTeacher?.is_tidak_hadir"
                            type="button" 
                            variant="ghost" 
                            @click="resetKetidakhadiran" 
                            class="text-xs h-9 text-rose-600 hover:text-rose-700 hover:bg-rose-50 gap-1 px-2.5"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            Kosongkan
                        </Button>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button type="button" variant="outline" @click="isModalOpen = false" class="text-xs h-9">
                            Batal
                        </Button>
                        <Button 
                            type="button" 
                            @click="confirmKetidakhadiran" 
                            class="text-white text-xs h-9 gap-1.5"
                            :class="selectedReason === 'Alpha' ? 'bg-rose-600 hover:bg-rose-700' : (selectedReason === 'Sakit' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-blue-600 hover:bg-blue-700')"
                        >
                            <CheckCircle2 class="w-4 h-4" />
                            Pilih & Konfirmasi
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
