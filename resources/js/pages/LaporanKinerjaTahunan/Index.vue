<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import {
    Award,
    Calendar,
    Search,
    Users,
    CheckCircle2,
    Clock,
    AlertCircle,
    FileText,
    ExternalLink,
    Video,
    UserCheck,
    ShieldCheck,
    Check,
    X,
    Printer,
    ChevronRight,
    Eye,
    RefreshCw,
    Sparkles,
    Filter,
    HelpCircle,
    MapPin,
    Target,
    BookOpen,
    Info
} from "@lucide/vue";

interface FokusItem {
    id: number;
    nama_fokus: string;
    perilaku_dianjurkan: string[];
    perilaku_dihindari: string[];
    penilaian: number | null;
    penilaian_label: string;
    catatan: string | null;
}

interface TeacherRow {
    id: number;
    name: string;
    email: string;
    role: string;
    status_pegawai: string;
    observer: {
        id: number;
        name: string;
    } | null;
    kinerja_id: number | null;
    tahap1: {
        has_data: boolean;
        status: string;
        status_label: string;
        tempat: string;
        tanggal: string;
        waktu: string;
        video_link: string | null;
        rekomendasi: string | null;
        fokuses: FokusItem[];
        approved_by: string | null;
        approved_at: string | null;
        catatan_revisi: string | null;
    };
    tahap2: {
        has_data: boolean;
        status: string;
        status_label: string;
        tempat_tahap2: string;
        tanggal_tahap2: string;
        waktu_tahap2: string;
        refleksi_rgtk: string | null;
        refleksi_lain: string | null;
        tujuan_tindak_lanjut: string[];
        upaya_tindak_lanjut: string[];
        kapan_tindak_lanjut: string[];
        kebutuhan_dukungan: string | null;
        pengembangan_kompetensi: string[];
        tugas_tambahan: string[];
        kesadaran_refleksi: number | null;
        kesadaran_refleksi_label: string;
        catatan_kepsek_tahap2: string | null;
    };
    tahap3: {
        has_data: boolean;
        status: string;
        status_label: string;
        tempat_tahap3: string;
        tanggal_tahap3: string;
        waktu_tahap3: string;
        tantangan_perubahan: string[];
        kesadaran_refleksi_tahap3: number | null;
        kesadaran_refleksi_tahap3_label: string;
        inspirasi_didapat: string[];
        perubahan_praktik_tahap3: string | null;
        upaya_kompetensi_tahap3: number | null;
        upaya_kompetensi_tahap3_label: string;
        rencana_mengatasi_tantangan: string[];
        peningkatan_kinerja_tahap3: number | null;
        peningkatan_kinerja_tahap3_label: string;
        catatan_observer_tahap3: string | null;
    };
    status_akhir: {
        key: string;
        label: string;
        badge: string;
    };
}

const props = defineProps<{
    data: TeacherRow[];
    available_years: number[];
    selected_year: number;
    stats: {
        total_guru: number;
        tahap1_selesai: number;
        tahap2_selesai: number;
        tahap3_selesai: number;
        sedang_proses: number;
        belum_mulai: number;
    };
}>();

// State filter
const selectedYear = ref<string>(props.selected_year.toString());
const searchQuery = ref('');
const statusFilter = ref('semua');

// Filter tahun (hanya pertahun)
const onYearChange = (year: string) => {
    selectedYear.value = year;
    router.get('/laporan-kinerja-tahunan', {
        year: year
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

// Modal states
const isTahap1ModalOpen = ref(false);
const isTahap2ModalOpen = ref(false);
const isTahap3ModalOpen = ref(false);
const activeTeacher = ref<TeacherRow | null>(null);

const openTahap1Modal = (teacher: TeacherRow) => {
    activeTeacher.value = teacher;
    isTahap1ModalOpen.value = true;
};

const openTahap2Modal = (teacher: TeacherRow) => {
    activeTeacher.value = teacher;
    isTahap2ModalOpen.value = true;
};

const openTahap3Modal = (teacher: TeacherRow) => {
    activeTeacher.value = teacher;
    isTahap3ModalOpen.value = true;
};

// Data filter & search
const filteredData = computed(() => {
    let result = props.data;

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(item => {
            const nameMatch = item.name.toLowerCase().includes(q);
            const emailMatch = item.email.toLowerCase().includes(q);
            const obsMatch = item.observer?.name.toLowerCase().includes(q) ?? false;
            return nameMatch || emailMatch || obsMatch;
        });
    }

    if (statusFilter.value !== 'semua') {
        if (statusFilter.value === 'selesai') {
            result = result.filter(item => item.status_akhir.key === 'selesai');
        } else if (statusFilter.value === 'tahap2_selesai') {
            result = result.filter(item => item.tahap2.status === 'approved');
        } else if (statusFilter.value === 'tahap1_selesai') {
            result = result.filter(item => item.tahap1.status === 'approved');
        } else if (statusFilter.value === 'proses') {
            result = result.filter(item => ['proses_tahap1', 'verifikasi_tahap1', 'proses_tahap2', 'verifikasi_tahap2', 'proses_tahap3', 'verifikasi_tahap3', 'revisi'].includes(item.status_akhir.key));
        } else if (statusFilter.value === 'belum_mulai') {
            result = result.filter(item => item.status_akhir.key === 'belum_mulai');
        }
    }

    return result;
});

// Helper badge styles
const getTahap1BadgeClass = (status: string) => {
    switch (status) {
        case 'approved':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'pending':
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
        case 'draft_observer':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'draft_guru':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800';
        case 'revisi_guru':
        case 'revisi_observer':
        case 'revisi_keduanya':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
    }
};

const getTahap2BadgeClass = (status: string) => {
    switch (status) {
        case 'approved':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'submitted':
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
        case 'menunggu_penilaian':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'draft_guru':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800';
        case 'revisi_guru':
        case 'revisi_observer':
        case 'revisi_keduanya':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
    }
};

const getTahap3BadgeClass = (status: string) => {
    switch (status) {
        case 'approved':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'submitted':
            return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800';
        case 'menunggu_penilaian':
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
        case 'draft_guru':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'revisi_guru':
        case 'revisi_observer':
        case 'revisi_keduanya':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
    }
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Laporan Kinerja Tahunan" />

    <div class="p-3.5 sm:p-5 md:p-6 space-y-4 md:space-y-6 pb-12 print:p-0 print:space-y-4">
        <!-- Top Banner Header -->
        <div class="relative overflow-hidden rounded-2xl bg-[#66BB6A] text-white p-4 md:p-8 shadow-lg print:hidden">
            <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute right-1/3 -top-10 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4 md:gap-6">
                <div>
                    <h1 class="text-xl md:text-3xl font-black tracking-tight text-white flex items-center gap-2 md:gap-3">
                        Laporan Kinerja Tahunan Guru
                    </h1>
                    <p class="text-white/90 text-xs md:text-base mt-1.5 md:mt-2 max-w-2xl leading-relaxed">
                        Rekapitulasi komprehensif pengisian observasi kinerja guru untuk Tahap 1, Tahap 2, dan status penilaian akhir dalam periode satu tahun berjalan.
                    </p>
                </div>

                <!-- Annual Filter Controls (Strictly per Year) -->
                <div class="flex items-center justify-between sm:justify-start gap-2 bg-white/20 backdrop-blur-md p-2 rounded-xl border border-white/25 shadow-inner w-full sm:w-auto">
                    <div class="flex items-center gap-1.5 px-1 text-white text-xs md:text-sm font-semibold">
                        <Calendar class="w-4 h-4 text-white shrink-0" />
                        <span>Filter:</span>
                    </div>

                    <Select :model-value="selectedYear" @update:model-value="onYearChange">
                        <SelectTrigger class="w-[120px] md:w-[130px] bg-white text-slate-900 font-semibold border-0 shadow-sm focus:ring-2 focus:ring-emerald-400 text-xs md:text-sm">
                            <SelectValue placeholder="Pilih Tahun" />
                        </SelectTrigger>
                        <SelectContent class="bg-white text-slate-900">
                            <SelectItem
                                v-for="y in available_years"
                                :key="y"
                                :value="y.toString()"
                                class="font-medium cursor-pointer"
                            >
                                Tahun {{ y }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>

        <!-- Printable Header (Only visible when printing) -->
        <div class="hidden print:block border-b-2 border-slate-900 pb-4 mb-4 text-center">
            <h2 class="text-2xl font-bold uppercase tracking-wider text-slate-900">LAPORAN KINERJA TAHUNAN GURU</h2>
            <p class="text-sm text-slate-700 mt-1">Periode Evaluasi: Januari - Desember {{ selected_year }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Dicetak pada: {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4 print:hidden">
            <!-- Total Guru -->
            <Card class="border-slate-200 dark:border-slate-800 shadow-xs hover:shadow-md transition-shadow">
                <CardContent class="p-3 md:p-3.5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] md:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Guru</p>
                        <h3 class="text-lg md:text-xl font-black text-slate-900 dark:text-white mt-0.5 md:mt-1">{{ stats.total_guru }}</h3>
                        <p class="text-[9px] md:text-[10px] text-slate-400 mt-0.5">Objek Evaluasi</p>
                    </div>
                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-blue-100 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                        <Users class="w-4 h-4 md:w-5 md:h-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Tahap 1 Selesai -->
            <Card class="border-slate-200 dark:border-slate-800 shadow-xs hover:shadow-md transition-shadow">
                <CardContent class="p-3 md:p-3.5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] md:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tahap 1 ACC</p>
                        <h3 class="text-lg md:text-xl font-black text-blue-600 dark:text-blue-400 mt-0.5 md:mt-1">{{ stats.tahap1_selesai }}</h3>
                        <p class="text-[9px] md:text-[10px] text-blue-600/80 mt-0.5">Observasi Kelas</p>
                    </div>
                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-blue-100 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                        <CheckCircle2 class="w-4 h-4 md:w-5 md:h-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Tahap 2 Selesai -->
            <Card class="border-slate-200 dark:border-slate-800 shadow-xs hover:shadow-md transition-shadow">
                <CardContent class="p-3 md:p-3.5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] md:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tahap 2 ACC</p>
                        <h3 class="text-lg md:text-xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5 md:mt-1">{{ stats.tahap2_selesai }}</h3>
                        <p class="text-[9px] md:text-[10px] text-indigo-600/80 mt-0.5">Tindak Lanjut</p>
                    </div>
                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-indigo-100 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                        <CheckCircle2 class="w-4 h-4 md:w-5 md:h-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Tahap 3 Selesai (Selesai Penuh) -->
            <Card class="border-slate-200 dark:border-slate-800 shadow-xs hover:shadow-md transition-shadow">
                <CardContent class="p-3 md:p-3.5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] md:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Selesai Penuh</p>
                        <h3 class="text-lg md:text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5 md:mt-1">{{ stats.tahap3_selesai }}</h3>
                        <p class="text-[9px] md:text-[10px] text-emerald-600/80 mt-0.5">Tahap 1, 2, 3 ACC</p>
                    </div>
                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <Award class="w-4 h-4 md:w-5 md:h-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Sedang Proses -->
            <Card class="border-slate-200 dark:border-slate-800 shadow-xs hover:shadow-md transition-shadow">
                <CardContent class="p-3 md:p-3.5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] md:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sedang Proses</p>
                        <h3 class="text-lg md:text-xl font-black text-amber-600 dark:text-amber-400 mt-0.5 md:mt-1">{{ stats.sedang_proses }}</h3>
                        <p class="text-[9px] md:text-[10px] text-amber-600/80 mt-0.5">Draft/Verifikasi</p>
                    </div>
                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-amber-100 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <Clock class="w-4 h-4 md:w-5 md:h-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Belum Mulai -->
            <Card class="border-slate-200 dark:border-slate-800 shadow-xs hover:shadow-md transition-shadow">
                <CardContent class="p-3 md:p-3.5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] md:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Belum Mulai</p>
                        <h3 class="text-lg md:text-xl font-black text-slate-600 dark:text-slate-400 mt-0.5 md:mt-1">{{ stats.belum_mulai }}</h3>
                        <p class="text-[9px] md:text-[10px] text-slate-400 mt-0.5">Belum mengisi</p>
                    </div>
                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 shrink-0">
                        <AlertCircle class="w-4 h-4 md:w-5 md:h-5" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Filter & Search Controls -->
        <Card class="border-slate-200 dark:border-slate-800 shadow-sm print:hidden">
            <CardContent class="p-3 md:p-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 md:gap-3">
                    <div class="relative flex-1">
                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Cari guru, email, atau observer..."
                            class="pl-9 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-xs md:text-sm"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium whitespace-nowrap">
                            <Filter class="w-3.5 h-3.5" />
                            Status:
                        </div>
                        <Select v-model="statusFilter">
                            <SelectTrigger class="w-full sm:w-[180px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-xs font-medium">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="selesai">Selesai Penuh (Tahap 3)</SelectItem>
                                <SelectItem value="tahap2_selesai">Tahap 2 Disetujui</SelectItem>
                                <SelectItem value="tahap1_selesai">Tahap 1 Disetujui</SelectItem>
                                <SelectItem value="proses">Sedang Berjalan</SelectItem>
                                <SelectItem value="belum_mulai">Belum Mulai</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Main Report Table -->
        <Card class="border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <CardHeader class="bg-slate-50/70 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-800 py-3 md:py-3.5 px-3 md:px-6">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <CardTitle class="text-sm md:text-base font-bold text-slate-900 dark:text-white flex items-center gap-1.5 md:gap-2">
                            <FileText class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            Daftar Hasil Kinerja Guru ({{ selected_year }})
                        </CardTitle>
                        <CardDescription class="text-[11px] md:text-xs text-slate-500 mt-0.5">
                            Klik pada tombol tahap di bawah untuk melihat rincian formulir isian guru.
                        </CardDescription>
                    </div>
                    <Badge variant="outline" class="font-normal text-[11px] md:text-xs bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 shrink-0">
                        {{ filteredData.length }} / {{ props.data.length }}
                    </Badge>
                </div>
            </CardHeader>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-100/50 dark:bg-slate-900/60 text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4 min-w-[200px]">Nama Guru</th>
                            <th class="py-3 px-4 min-w-[170px]">Observer / Penilai</th>
                            <th class="py-3 px-4 min-w-[170px] text-center">Tahap 1 (Observasi)</th>
                            <th class="py-3 px-4 min-w-[170px] text-center">Tahap 2 (Tindak Lanjut)</th>
                            <th class="py-3 px-4 min-w-[170px] text-center">Tahap 3 (Refleksi)</th>
                            <th class="py-3 px-4 min-w-[150px] text-center">Status Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <tr
                            v-for="(item, idx) in filteredData"
                            :key="item.id"
                            class="hover:bg-slate-50/80 dark:hover:bg-slate-900/30 transition-colors"
                        >
                            <!-- No -->
                            <td class="py-3 px-4 text-center text-xs font-mono text-slate-400">
                                {{ idx + 1 }}
                            </td>

                            <!-- Nama Guru -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold flex items-center justify-center text-xs flex-shrink-0">
                                        {{ item.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                            <span>{{ item.name }}</span>
                                            <span
                                                v-if="item.status_pegawai && item.status_pegawai !== '-'"
                                                class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                                            >
                                                {{ item.status_pegawai }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ item.email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Observer -->
                            <td class="py-3 px-4">
                                <div v-if="item.observer" class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300">
                                        <UserCheck class="w-3.5 h-3.5" />
                                    </div>
                                    <span class="font-medium text-slate-800 dark:text-slate-200 text-xs">
                                        {{ item.observer.name }}
                                    </span>
                                </div>
                                <div v-else class="text-xs italic text-slate-400 flex items-center gap-1.5">
                                    <HelpCircle class="w-3.5 h-3.5 text-slate-400" />
                                    <span>Belum Ditugaskan</span>
                                </div>
                            </td>

                            <!-- Tahap 1 Column -->
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex flex-col items-center gap-1.5">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 text-xs font-semibold rounded-full border shadow-2xs',
                                            getTahap1BadgeClass(item.tahap1.status)
                                        ]"
                                    >
                                        {{ item.tahap1.status_label }}
                                    </span>

                                    <!-- Clickable Button to View Tahap 1 Form Data -->
                                    <Button
                                        variant="outline"
                                        size="xs"
                                        @click="openTahap1Modal(item)"
                                        class="h-7 text-xs gap-1 border-blue-200 dark:border-blue-900/60 bg-blue-50/50 hover:bg-blue-100 dark:bg-blue-950/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-medium"
                                    >
                                        <Eye class="w-3.5 h-3.5" />
                                        <span>Isian Tahap 1</span>
                                    </Button>
                                </div>
                            </td>

                            <!-- Tahap 2 Column -->
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex flex-col items-center gap-1.5">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 text-xs font-semibold rounded-full border shadow-2xs',
                                            getTahap2BadgeClass(item.tahap2.status)
                                        ]"
                                    >
                                        {{ item.tahap2.status_label }}
                                    </span>

                                    <!-- Clickable Button to View Tahap 2 Form Data -->
                                    <Button
                                        variant="outline"
                                        size="xs"
                                        @click="openTahap2Modal(item)"
                                        class="h-7 text-xs gap-1 border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/50 hover:bg-indigo-100 dark:bg-indigo-950/30 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-medium"
                                    >
                                        <Eye class="w-3.5 h-3.5" />
                                        <span>Isian Tahap 2</span>
                                    </Button>
                                </div>
                            </td>

                            <!-- Tahap 3 Column -->
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex flex-col items-center gap-1.5">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 text-xs font-semibold rounded-full border shadow-2xs',
                                            getTahap3BadgeClass(item.tahap3.status)
                                        ]"
                                    >
                                        {{ item.tahap3.status_label }}
                                    </span>

                                    <!-- Clickable Button to View Tahap 3 Form Data -->
                                    <Button
                                        variant="outline"
                                        size="xs"
                                        @click="openTahap3Modal(item)"
                                        class="h-7 text-xs gap-1 border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/50 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-medium"
                                    >
                                        <Eye class="w-3.5 h-3.5" />
                                        <span>Isian Tahap 3</span>
                                    </Button>
                                </div>
                            </td>

                            <!-- Status Akhir -->
                            <td class="py-3 px-4 text-center">
                                <span
                                    :class="[
                                        'px-2.5 py-1 text-xs font-semibold rounded-full border inline-block',
                                        item.status_akhir.badge
                                    ]"
                                >
                                    {{ item.status_akhir.label }}
                                </span>
                            </td>
                        </tr>

                        <!-- Empty state when no data matches -->
                        <tr v-if="filteredData.length === 0">
                            <td colspan="7" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <AlertCircle class="w-10 h-10 mb-2 stroke-1 text-slate-300 dark:text-slate-600" />
                                    <p class="font-medium text-slate-700 dark:text-slate-300 text-sm">Tidak ada data guru yang sesuai.</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci pencarian atau filter status yang berbeda.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card List View -->
            <div class="block md:hidden divide-y divide-slate-200 dark:divide-slate-800">
                <div 
                    v-for="(item, idx) in filteredData" 
                    :key="'mob-tahunan-' + item.id" 
                    class="p-4 space-y-3.5 bg-white dark:bg-slate-900 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                >
                    <!-- Header: Guru Name + Status Akhir Badge -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold flex items-center justify-center text-xs shrink-0">
                                {{ item.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-sm text-slate-900 dark:text-white truncate">
                                    {{ item.name }}
                                </h3>
                                <p class="text-xs text-slate-400 truncate">{{ item.email }}</p>
                            </div>
                        </div>
                        <span
                            :class="[
                                'px-2 py-0.5 text-[11px] font-bold rounded-full border shrink-0',
                                item.status_akhir.badge
                            ]"
                        >
                            {{ item.status_akhir.label }}
                        </span>
                    </div>

                    <!-- Observer & Pegawai Row -->
                    <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-lg border border-slate-200/60 dark:border-slate-800">
                        <div>
                            <span class="text-[10px] text-muted-foreground block font-medium">Observer / Penilai:</span>
                            <span v-if="item.observer" class="font-semibold text-slate-800 dark:text-slate-200 truncate flex items-center gap-1 mt-0.5">
                                <UserCheck class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                                {{ item.observer.name }}
                            </span>
                            <span v-else class="text-slate-400 italic text-[11px] block mt-0.5">
                                Belum Ditugaskan
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-muted-foreground block font-medium">Status Pegawai:</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200 block mt-0.5">
                                {{ item.status_pegawai || '-' }}
                            </span>
                        </div>
                    </div>

                    <!-- 3 Tahap Trigger Cards -->
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <!-- Tahap 1 -->
                        <div class="p-2 rounded-lg bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30 flex flex-col justify-between items-center text-center gap-1.5">
                            <span class="text-[10px] font-bold text-blue-900 dark:text-blue-300">Tahap 1</span>
                            <span
                                :class="[
                                    'px-1.5 py-0.5 text-[10px] font-bold rounded-full border w-full truncate',
                                    getTahap1BadgeClass(item.tahap1.status)
                                ]"
                            >
                                {{ item.tahap1.status_label }}
                            </span>
                            <Button
                                variant="outline"
                                size="xs"
                                @click="openTahap1Modal(item)"
                                class="h-6 w-full text-[10px] px-1 border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-900 text-blue-700 dark:text-blue-300"
                            >
                                <Eye class="w-3 h-3 mr-0.5" /> Isian
                            </Button>
                        </div>

                        <!-- Tahap 2 -->
                        <div class="p-2 rounded-lg bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 flex flex-col justify-between items-center text-center gap-1.5">
                            <span class="text-[10px] font-bold text-indigo-900 dark:text-indigo-300">Tahap 2</span>
                            <span
                                :class="[
                                    'px-1.5 py-0.5 text-[10px] font-bold rounded-full border w-full truncate',
                                    getTahap2BadgeClass(item.tahap2.status)
                                ]"
                            >
                                {{ item.tahap2.status_label }}
                            </span>
                            <Button
                                variant="outline"
                                size="xs"
                                @click="openTahap2Modal(item)"
                                class="h-6 w-full text-[10px] px-1 border-indigo-200 dark:border-indigo-800 bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300"
                            >
                                <Eye class="w-3 h-3 mr-0.5" /> Isian
                            </Button>
                        </div>

                        <!-- Tahap 3 -->
                        <div class="p-2 rounded-lg bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 flex flex-col justify-between items-center text-center gap-1.5">
                            <span class="text-[10px] font-bold text-emerald-900 dark:text-emerald-300">Tahap 3</span>
                            <span
                                :class="[
                                    'px-1.5 py-0.5 text-[10px] font-bold rounded-full border w-full truncate',
                                    getTahap3BadgeClass(item.tahap3.status)
                                ]"
                            >
                                {{ item.tahap3.status_label }}
                            </span>
                            <Button
                                variant="outline"
                                size="xs"
                                @click="openTahap3Modal(item)"
                                class="h-6 w-full text-[10px] px-1 border-emerald-200 dark:border-emerald-800 bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-300"
                            >
                                <Eye class="w-3 h-3 mr-0.5" /> Isian
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-if="filteredData.length === 0" class="p-8 text-center text-sm text-slate-500 italic">
                    Tidak ada data guru yang cocok dengan pencarian.
                </div>
            </div>
        </Card>

        <!-- ================================================================================== -->
        <!-- MODAL DETAIL TAHAP 1 (OBSERVASI PRAKTIK KINERJA)                                 -->
        <!-- ================================================================================== -->
        <Dialog v-model:open="isTahap1ModalOpen">
            <DialogContent class="max-w-3xl max-h-[88vh] overflow-y-auto p-0 gap-0 border-slate-200 dark:border-slate-800">
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-blue-900 to-indigo-950 text-white rounded-t-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/10 backdrop-blur border border-white/20 text-blue-200 text-xs font-semibold mb-2">
                                <ShieldCheck class="w-3.5 h-3.5 text-blue-300" />
                                Formulir Tahap 1 &bull; Observasi Praktik Kinerja
                            </div>
                            <h2 class="text-xl font-bold text-white tracking-tight">
                                {{ activeTeacher?.name }}
                            </h2>
                            <p class="text-xs text-blue-200/80 mt-1 flex items-center gap-3">
                                <span>Observer: {{ activeTeacher?.observer?.name || 'Belum Ditugaskan' }}</span>
                                <span>&bull;</span>
                                <span>Periode: Tahun {{ selected_year }}</span>
                            </p>
                        </div>
                        <span
                            v-if="activeTeacher"
                            :class="[
                                'px-3 py-1 text-xs font-bold rounded-full border shadow-sm',
                                getTahap1BadgeClass(activeTeacher.tahap1.status)
                            ]"
                        >
                            {{ activeTeacher.tahap1.status_label }}
                        </span>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6">
                    <!-- Case A: Belum Mengisi Tahap 1 -->
                    <div
                        v-if="!activeTeacher?.tahap1?.has_data || activeTeacher?.tahap1?.status === 'belum_mulai'"
                        class="p-8 text-center rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-dashed border-slate-200 dark:border-slate-800"
                    >
                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mx-auto mb-3">
                            <Clock class="w-6 h-6" />
                        </div>
                        <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">Formulir Tahap 1 Belum Dimulai</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                            Guru yang bersangkutan belum melakukan pengisian Rencana Hasil Kerja (Tahap 1) untuk tahun ajaran {{ selected_year }}.
                        </p>
                    </div>

                    <!-- Case B: Sudah Mengisi Tahap 1 -->
                    <div v-else class="space-y-6">
                        <!-- 1. Pelaksanaan & Jadwal Observasi -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-slate-50/50 dark:bg-slate-900/30">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-blue-600" />
                                Informasi Pelaksanaan Observasi
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-xs text-slate-400 block font-medium">Hari / Tanggal</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                        {{ activeTeacher.tahap1.tanggal }}
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-xs text-slate-400 block font-medium">Waktu Observasi</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                        {{ activeTeacher.tahap1.waktu !== '-' ? activeTeacher.tahap1.waktu + ' WITA' : '-' }}
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-xs text-slate-400 block font-medium">Tempat / Ruang Kelas</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                        {{ activeTeacher.tahap1.tempat }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Video Observasi Link -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-blue-50/30 dark:bg-blue-950/20">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/60 text-blue-600 dark:text-blue-300 flex items-center justify-center flex-shrink-0">
                                        <Video class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-bold text-slate-800 dark:text-slate-200">Video Rekaman Observasi Praktik</h5>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ activeTeacher.tahap1.video_link ? 'Tautan video rekaman praktik kelas tersedia.' : 'Tautan video belum disertakan.' }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="activeTeacher.tahap1.video_link">
                                    <a
                                        :href="activeTeacher.tahap1.video_link"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition-colors"
                                    >
                                        <ExternalLink class="w-3.5 h-3.5" />
                                        Buka Video Observasi
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Fokus Perilaku & Hasil Penilaian -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-2">
                                <Target class="w-4 h-4 text-blue-600" />
                                Fokus Perilaku & Hasil Penilaian Observer
                            </h4>

                            <div v-if="activeTeacher.tahap1.fokuses.length" class="space-y-4">
                                <div
                                    v-for="(fokus, fIdx) in activeTeacher.tahap1.fokuses"
                                    :key="fokus.id"
                                    class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-slate-900 shadow-2xs"
                                >
                                    <!-- Fokus Header -->
                                    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">
                                                {{ fIdx + 1 }}
                                            </span>
                                            <span class="font-bold text-sm text-slate-900 dark:text-white">
                                                {{ fokus.nama_fokus }}
                                            </span>
                                        </div>

                                        <!-- Penilaian Badge -->
                                        <span
                                            v-if="fokus.penilaian"
                                            :class="[
                                                'px-2.5 py-0.5 text-xs font-bold rounded-full border',
                                                fokus.penilaian == 3 ? 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300' :
                                                fokus.penilaian == 2 ? 'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300' :
                                                'bg-rose-50 text-rose-700 border-rose-300 dark:bg-rose-950/40 dark:text-rose-300'
                                            ]"
                                        >
                                            {{ fokus.penilaian_label }}
                                        </span>
                                        <span v-else class="text-xs text-slate-400 italic">
                                            Belum dinilai
                                        </span>
                                    </div>

                                    <!-- Fokus Content -->
                                    <div class="p-4 space-y-3">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <!-- Dianjurkan -->
                                            <div class="rounded-lg bg-emerald-50/40 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-900/40 p-3">
                                                <h6 class="text-xs font-bold text-emerald-800 dark:text-emerald-400 flex items-center gap-1.5 mb-2">
                                                    <Check class="w-3.5 h-3.5 text-emerald-600" />
                                                    Perilaku yang Dianjurkan
                                                </h6>
                                                <ul class="space-y-1.5 text-xs text-slate-700 dark:text-slate-300">
                                                    <li
                                                        v-for="(p, pIdx) in fokus.perilaku_dianjurkan"
                                                        :key="pIdx"
                                                        class="flex items-start gap-1.5"
                                                    >
                                                        <span class="text-emerald-600 font-bold">&bull;</span>
                                                        <span>{{ p }}</span>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Dihindari -->
                                            <div class="rounded-lg bg-rose-50/40 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-900/40 p-3">
                                                <h6 class="text-xs font-bold text-rose-800 dark:text-rose-400 flex items-center gap-1.5 mb-2">
                                                    <X class="w-3.5 h-3.5 text-rose-600" />
                                                    Perilaku yang Dihindari
                                                </h6>
                                                <ul class="space-y-1.5 text-xs text-slate-700 dark:text-slate-300">
                                                    <li
                                                        v-for="(p, pIdx) in fokus.perilaku_dihindari"
                                                        :key="pIdx"
                                                        class="flex items-start gap-1.5"
                                                    >
                                                        <span class="text-rose-600 font-bold">&bull;</span>
                                                        <span>{{ p }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Catatan Penilai -->
                                        <div v-if="fokus.catatan" class="bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">
                                                Catatan Observer:
                                            </span>
                                            <p class="text-xs text-slate-700 dark:text-slate-300 mt-1 italic leading-relaxed">
                                                "{{ fokus.catatan }}"
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-xs text-slate-400 italic p-3 bg-slate-50 rounded-lg">
                                Tidak ada data fokus perilaku yang dicatat.
                            </div>
                        </div>

                        <!-- 4. Rekomendasi Observer -->
                        <div v-if="activeTeacher.tahap1.rekomendasi" class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-amber-50/30 dark:bg-amber-950/20">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-400 mb-2 flex items-center gap-2">
                                <Sparkles class="w-4 h-4 text-amber-600" />
                                Rekomendasi / Catatan Akhir Observer
                            </h4>
                            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed italic bg-white dark:bg-slate-900 p-3 rounded-lg border border-amber-200/50 dark:border-amber-900/30">
                                "{{ activeTeacher.tahap1.rekomendasi }}"
                            </p>
                        </div>

                        <!-- 5. Catatan Revisi jika ada -->
                        <div v-if="activeTeacher.tahap1.catatan_revisi" class="rounded-xl border border-rose-200 dark:border-rose-900/50 p-4 bg-rose-50/40 dark:bg-rose-950/20">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-rose-800 dark:text-rose-400 mb-1 flex items-center gap-2">
                                <AlertCircle class="w-4 h-4 text-rose-600" />
                                Catatan Permintaan Revisi
                            </h4>
                            <p class="text-xs text-rose-700 dark:text-rose-300 mt-1">
                                {{ activeTeacher.tahap1.catatan_revisi }}
                            </p>
                        </div>

                        <!-- 6. Persetujuan Verifikator -->
                        <div v-if="activeTeacher.tahap1.approved_by" class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 rounded-xl text-xs flex items-center justify-between text-emerald-800 dark:text-emerald-300">
                            <div class="flex items-center gap-2 font-medium">
                                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                                <span>Telah Disetujui oleh: <strong>{{ activeTeacher.tahap1.approved_by }}</strong></span>
                            </div>
                            <span v-if="activeTeacher.tahap1.approved_at" class="text-slate-500 text-[11px]">
                                {{ activeTeacher.tahap1.approved_at }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <Button variant="outline" @click="isTahap1ModalOpen = false">
                        Tutup
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- ================================================================================== -->
        <!-- MODAL DETAIL TAHAP 2 (TINDAK LANJUT & REFLEKSI GURU)                               -->
        <!-- ================================================================================== -->
        <Dialog v-model:open="isTahap2ModalOpen">
            <DialogContent class="max-w-3xl max-h-[88vh] overflow-y-auto p-0 gap-0 border-slate-200 dark:border-slate-800">
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-indigo-900 to-purple-950 text-white rounded-t-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/10 backdrop-blur border border-white/20 text-purple-200 text-xs font-semibold mb-2">
                                <Award class="w-3.5 h-3.5 text-purple-300" />
                                Formulir Tahap 2 &bull; Refleksi & Tindak Lanjut
                            </div>
                            <h2 class="text-xl font-bold text-white tracking-tight">
                                {{ activeTeacher?.name }}
                            </h2>
                            <p class="text-xs text-purple-200/80 mt-1 flex items-center gap-3">
                                <span>Observer: {{ activeTeacher?.observer?.name || 'Belum Ditugaskan' }}</span>
                                <span>&bull;</span>
                                <span>Periode: Tahun {{ selected_year }}</span>
                            </p>
                        </div>
                        <span
                            v-if="activeTeacher"
                            :class="[
                                'px-3 py-1 text-xs font-bold rounded-full border shadow-sm',
                                getTahap2BadgeClass(activeTeacher.tahap2.status)
                            ]"
                        >
                            {{ activeTeacher.tahap2.status_label }}
                        </span>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6">
                    <!-- Case A: Belum Mengisi Tahap 2 -->
                    <div
                        v-if="!activeTeacher?.tahap2?.has_data || activeTeacher?.tahap2?.status === 'belum_mulai'"
                        class="p-8 text-center rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-dashed border-slate-200 dark:border-slate-800"
                    >
                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mx-auto mb-3">
                            <Clock class="w-6 h-6" />
                        </div>
                        <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">Formulir Tahap 2 Belum Dimulai</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                            Tahap 2 (Refleksi & Rencana Tindak Lanjut) belum diisi oleh guru atau belum disetujui pada Tahap 1.
                        </p>
                    </div>

                    <!-- Case B: Sudah Mengisi Tahap 2 -->
                    <div v-else class="space-y-6">
                        <!-- 1. Jadwal Diskusi Tindak Lanjut -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-slate-50/50 dark:bg-slate-900/30">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-purple-600" />
                                Jadwal Diskusi Tindak Lanjut
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-xs text-slate-400 block font-medium">Hari / Tanggal</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                        {{ activeTeacher.tahap2.tanggal_tahap2 }}
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-xs text-slate-400 block font-medium">Waktu Diskusi</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                        {{ activeTeacher.tahap2.waktu_tahap2 !== '-' ? activeTeacher.tahap2.waktu_tahap2 + ' WITA' : '-' }}
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-xs text-slate-400 block font-medium">Tempat Diskusi</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                        {{ activeTeacher.tahap2.tempat_tahap2 }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Refleksi Guru -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                <BookOpen class="w-4 h-4 text-purple-600" />
                                Refleksi Guru
                            </h4>

                            <!-- Refleksi Berdasarkan Observasi -->
                            <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-white dark:bg-slate-900">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mb-1">
                                    Refleksi Berdasarkan Hasil Observasi Praktik:
                                </span>
                                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    {{ activeTeacher.tahap2.refleksi_rgtk || '-' }}
                                </p>
                            </div>

                            <!-- Refleksi Lainnya -->
                            <div v-if="activeTeacher.tahap2.refleksi_lain" class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-white dark:bg-slate-900">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mb-1">
                                    Refleksi Lainnya / Tantangan Tambahan:
                                </span>
                                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    {{ activeTeacher.tahap2.refleksi_lain }}
                                </p>
                            </div>
                        </div>

                        <!-- 3. Rencana Tindak Lanjut Belajar -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                <Target class="w-4 h-4 text-purple-600" />
                                Rencana Tindak Lanjut & Pilihan Belajar
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                <!-- PMM -->
                                <div class="rounded-xl border border-blue-200 dark:border-blue-900/50 p-4 bg-blue-50/20 dark:bg-blue-950/20 space-y-2">
                                    <span class="font-bold text-blue-900 dark:text-blue-300 block text-xs">
                                        Pilihan Belajar di PMM:
                                    </span>
                                    <div>
                                        <span class="text-[11px] text-slate-400 block">Tujuan Tindak Lanjut:</span>
                                        <p class="font-medium text-slate-800 dark:text-slate-200 mt-0.5">
                                            {{ activeTeacher.tahap2.tujuan_tindak_lanjut[0] || '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-[11px] text-slate-400 block">Upaya Pembelajaran:</span>
                                        <p class="font-medium text-slate-800 dark:text-slate-200 mt-0.5">
                                            {{ activeTeacher.tahap2.upaya_tindak_lanjut[0] || '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-[11px] text-slate-400 block">Periode / Kapan:</span>
                                        <p class="font-medium text-slate-800 dark:text-slate-200 mt-0.5">
                                            {{ activeTeacher.tahap2.kapan_tindak_lanjut[0] || '-' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Lainnya -->
                                <div class="rounded-xl border border-purple-200 dark:border-purple-900/50 p-4 bg-purple-50/20 dark:bg-purple-950/20 space-y-2">
                                    <span class="font-bold text-purple-900 dark:text-purple-300 block text-xs">
                                        Pilihan Belajar Lainnya:
                                    </span>
                                    <div>
                                        <span class="text-[11px] text-slate-400 block">Tujuan Tindak Lanjut:</span>
                                        <p class="font-medium text-slate-800 dark:text-slate-200 mt-0.5">
                                            {{ activeTeacher.tahap2.tujuan_tindak_lanjut[1] || '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-[11px] text-slate-400 block">Upaya Pembelajaran:</span>
                                        <p class="font-medium text-slate-800 dark:text-slate-200 mt-0.5">
                                            {{ activeTeacher.tahap2.upaya_tindak_lanjut[1] || '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-[11px] text-slate-400 block">Periode / Kapan:</span>
                                        <p class="font-medium text-slate-800 dark:text-slate-200 mt-0.5">
                                            {{ activeTeacher.tahap2.kapan_tindak_lanjut[1] || '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Kebutuhan Dukungan -->
                            <div v-if="activeTeacher.tahap2.kebutuhan_dukungan" class="rounded-xl border border-slate-200 dark:border-slate-800 p-3 bg-slate-50 dark:bg-slate-900">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
                                    Kebutuhan Dukungan yang Diharapkan:
                                </span>
                                <p class="text-xs text-slate-600 dark:text-slate-400">
                                    {{ activeTeacher.tahap2.kebutuhan_dukungan }}
                                </p>
                            </div>
                        </div>

                        <!-- 4. Penilaian Observer / Penilai Tahap 2 -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-gradient-to-br from-indigo-50/50 to-purple-50/40 dark:from-indigo-950/20 dark:to-purple-950/20">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-400 mb-3 flex items-center gap-2">
                                <ShieldCheck class="w-4 h-4 text-indigo-600" />
                                Penilaian Observer / Kepala Sekolah (Tahap 2)
                            </h4>

                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs text-slate-500 block font-medium">Tingkat Kesadaran Refleksi Guru:</span>
                                    <span
                                        :class="[
                                            'px-3 py-1 text-xs font-bold rounded-full border inline-block mt-1',
                                            activeTeacher.tahap2.kesadaran_refleksi == 3 ? 'bg-emerald-50 text-emerald-700 border-emerald-300' :
                                            activeTeacher.tahap2.kesadaran_refleksi == 2 ? 'bg-amber-50 text-amber-700 border-amber-300' :
                                            activeTeacher.tahap2.kesadaran_refleksi == 1 ? 'bg-blue-50 text-blue-700 border-blue-300' :
                                            'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ activeTeacher.tahap2.kesadaran_refleksi_label }}
                                    </span>
                                </div>

                                <div v-if="activeTeacher.tahap2.catatan_kepsek_tahap2">
                                    <span class="text-xs text-slate-500 block font-medium">Catatan / Umpan Balik Pembimbing:</span>
                                    <p class="text-xs text-slate-700 dark:text-slate-300 mt-1 italic bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                        "{{ activeTeacher.tahap2.catatan_kepsek_tahap2 }}"
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <Button variant="outline" @click="isTahap2ModalOpen = false">
                        Tutup
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- ================================================================================== -->
        <!-- MODAL DETAIL TAHAP 3 (REFLEKSI TINDAK LANJUT OBSERVASI KINERJA GURU)               -->
        <!-- ================================================================================== -->
        <Dialog v-model:open="isTahap3ModalOpen">
            <DialogContent class="max-w-3xl max-h-[88vh] overflow-y-auto p-0 gap-0 border-slate-200 dark:border-slate-800">
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-emerald-800 to-teal-950 text-white rounded-t-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/10 backdrop-blur border border-white/20 text-emerald-200 text-xs font-semibold mb-2">
                                <Award class="w-3.5 h-3.5 text-emerald-300" />
                                Formulir Tahap 3 &bull; Refleksi Tindak Lanjut Observasi Kinerja Guru
                            </div>
                            <h2 class="text-xl font-bold text-white tracking-tight">
                                {{ activeTeacher?.name }}
                            </h2>
                            <p class="text-xs text-emerald-200/80 mt-1 flex items-center gap-3">
                                <span>Observer: {{ activeTeacher?.observer?.name || 'Belum Ditugaskan' }}</span>
                                <span>&bull;</span>
                                <span>Periode: Tahun {{ selected_year }}</span>
                            </p>
                        </div>
                        <span
                            v-if="activeTeacher"
                            :class="[
                                'px-3 py-1 text-xs font-bold rounded-full border shadow-sm',
                                getTahap3BadgeClass(activeTeacher.tahap3.status)
                            ]"
                        >
                            {{ activeTeacher.tahap3.status_label }}
                        </span>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6">
                    <!-- Case A: Belum Mengisi Tahap 3 -->
                    <div
                        v-if="!activeTeacher?.tahap3?.has_data || activeTeacher?.tahap3?.status === 'belum_mulai'"
                        class="p-8 text-center rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-dashed border-slate-200 dark:border-slate-800"
                    >
                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mx-auto mb-3">
                            <Clock class="w-6 h-6" />
                        </div>
                        <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">Formulir Tahap 3 Belum Dimulai</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                            Guru dan observer belum melakukan pengisian Formulir Refleksi Tindak Lanjut (Tahap 3) untuk tahun ajaran {{ selected_year }}.
                        </p>
                    </div>

                    <!-- Case B: Sudah Mengisi Tahap 3 -->
                    <div v-else class="space-y-6">
                        <!-- 1. Div 1: Identitas & Pelaksanaan Observasi (Observer) -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-slate-50/50 dark:bg-slate-900/30">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-emerald-600" />
                                Waktu & Tempat Pelaksanaan (Observer)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-xs text-slate-400 block font-medium">Hari / Tanggal</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                        {{ activeTeacher.tahap3.tanggal_tahap3 }}
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-xs text-slate-400 block font-medium">Waktu Pelaksanaan</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                        {{ activeTeacher.tahap3.waktu_tahap3 !== '-' ? activeTeacher.tahap3.waktu_tahap3 + ' WITA' : '-' }}
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-xs text-slate-400 block font-medium">Tempat / Ruang</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                        {{ activeTeacher.tahap3.tempat_tahap3 }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 1. Div 1: Isi Penilaian Upaya Refleksi -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-white dark:bg-slate-900 space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 flex items-center gap-2 border-b pb-2 dark:border-slate-800">
                                <Sparkles class="w-4 h-4 text-emerald-600" />
                                1. Isi Penilaian Upaya Refleksi
                            </h4>

                            <!-- 3 Tantangan Paling Sulit (Guru) -->
                            <div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mb-2">
                                    3 tantangan paling sulit yang guru hadapi dalam melakukan perubahan (Diisi Guru):
                                </span>
                                <div v-if="activeTeacher.tahap3.tantangan_perubahan && activeTeacher.tahap3.tantangan_perubahan.length > 0" class="space-y-1.5">
                                    <div
                                        v-for="(tantangan, tIdx) in activeTeacher.tahap3.tantangan_perubahan"
                                        :key="tIdx"
                                        class="flex items-start gap-2 text-xs bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800"
                                    >
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
                                            #{{ tIdx + 1 }}
                                        </span>
                                        <span class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ tantangan }}</span>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-slate-400 italic">- Belum ada tantangan yang diinputkan -</p>
                            </div>

                            <!-- Penilaian Kesadaran Guru (Observer) -->
                            <div class="p-3.5 rounded-lg bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40">
                                <span class="text-xs font-bold text-emerald-900 dark:text-emerald-300 block mb-1">
                                    Penilaian Observer: Bagaimana kesadaran guru ketika berefleksi?
                                </span>
                                <div class="mt-1.5">
                                    <span
                                        :class="[
                                            'px-3 py-1 text-xs font-bold rounded-full border inline-block',
                                            activeTeacher.tahap3.kesadaran_refleksi_tahap3 ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ activeTeacher.tahap3.kesadaran_refleksi_tahap3_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Catatan Upaya Refleksi -->
                            <div v-if="activeTeacher.tahap3.catatan_refleksi_tahap3" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800">
                                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block mb-1">Catatan Observer (Upaya Refleksi):</span>
                                <p class="text-xs text-slate-600 dark:text-slate-300 italic">"{{ activeTeacher.tahap3.catatan_refleksi_tahap3 }}"</p>
                            </div>
                        </div>

                        <!-- 2. Div 2: Isi Penilaian Upaya Mempelajari -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-white dark:bg-slate-900 space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400 flex items-center gap-2 border-b pb-2 dark:border-slate-800">
                                <BookOpen class="w-4 h-4 text-blue-600" />
                                2. Isi Penilaian Upaya Mempelajari
                            </h4>

                            <!-- Inspirasi Baru (Guru) -->
                            <div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mb-2">
                                    Inspirasi baru yang guru dapatkan dari upaya tindak lanjut (Diisi Guru):
                                </span>
                                <div v-if="activeTeacher.tahap3.inspirasi_didapat && activeTeacher.tahap3.inspirasi_didapat.length > 0" class="space-y-1.5">
                                    <div
                                        v-for="(insp, iIdx) in activeTeacher.tahap3.inspirasi_didapat"
                                        :key="iIdx"
                                        class="flex items-start gap-2 text-xs bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800"
                                    >
                                        <Sparkles class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" />
                                        <span class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ insp }}</span>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-slate-400 italic">- Belum ada inspirasi yang diinputkan -</p>
                            </div>

                            <!-- Perubahan Praktik Guru (Upaya Mempelajari) -->
                            <div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mb-1">
                                    Berdasarkan inspirasi yang didapatkan, perubahan praktik guru di ruang kelas/satuan pendidikan (Diisi Guru):
                                </span>
                                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    {{ activeTeacher.tahap3.perubahan_praktik_tahap3 || '-' }}
                                </p>
                            </div>

                            <!-- Penilaian Upaya Mempelajari (Observer) -->
                            <div class="p-3.5 rounded-lg bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40">
                                <span class="text-xs font-bold text-blue-900 dark:text-blue-300 block mb-1">
                                    Penilaian Observer: Bagaimana upaya Guru mempelajari dan menguasai kompetensi yang dibutuhkan untuk peningkatan kinerja berdasarkan Rating Observasi Praktik Kinerja dan hasil refleksi?
                                </span>
                                <div class="mt-1.5">
                                    <span
                                        :class="[
                                            'px-3 py-1 text-xs font-bold rounded-full border inline-block',
                                            activeTeacher.tahap3.upaya_kompetensi_tahap3 ? 'bg-blue-100 text-blue-800 border-blue-300' : 'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ activeTeacher.tahap3.upaya_kompetensi_tahap3_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Catatan Upaya Mempelajari -->
                            <div v-if="activeTeacher.tahap3.catatan_upaya_tahap3" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800">
                                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block mb-1">Catatan Observer (Upaya Mempelajari):</span>
                                <p class="text-xs text-slate-600 dark:text-slate-300 italic">"{{ activeTeacher.tahap3.catatan_upaya_tahap3 }}"</p>
                            </div>
                        </div>

                        <!-- 3. Div 3: Isi Penilaian Peningkatan Kinerja -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-white dark:bg-slate-900 space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 flex items-center gap-2 border-b pb-2 dark:border-slate-800">
                                <Target class="w-4 h-4 text-purple-600" />
                                3. Isi Penilaian Peningkatan Kinerja
                            </h4>

                            <!-- Perubahan Praktik Guru (Peningkatan Kinerja) -->
                            <div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mb-1">
                                    Berdasarkan inspirasi yang didapatkan, perubahan praktik guru di ruang kelas/satuan pendidikan (Diisi Guru):
                                </span>
                                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                    {{ activeTeacher.tahap3.perubahan_praktik_kinerja_tahap3 || activeTeacher.tahap3.perubahan_praktik_tahap3 || '-' }}
                                </p>
                            </div>

                            <!-- Rencana Guru Mengatasi Tantangan (Guru) -->
                            <div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block mb-2">
                                    Rencana guru dalam mengatasi tantangan agar bisa memastikan perubahan terjadi (Diisi Guru):
                                </span>
                                <div v-if="activeTeacher.tahap3.rencana_mengatasi_tantangan && activeTeacher.tahap3.rencana_mengatasi_tantangan.length > 0" class="space-y-1.5">
                                    <div
                                        v-for="(rencana, rIdx) in activeTeacher.tahap3.rencana_mengatasi_tantangan"
                                        :key="rIdx"
                                        class="flex items-start gap-2 text-xs bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800"
                                    >
                                        <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                        <span class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ rencana }}</span>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-slate-400 italic">- Belum ada rencana yang diinputkan -</p>
                            </div>

                            <!-- Penilaian Peningkatan Kinerja (Observer) -->
                            <div class="p-3.5 rounded-lg bg-purple-50/50 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40">
                                <span class="text-xs font-bold text-purple-900 dark:text-purple-300 block mb-1">
                                    Penilaian Observer: Bagaimana peningkatan kinerja yang telah ditunjukkan oleh guru dan rencana perbaikan berkelanjutannya?
                                </span>
                                <div class="mt-1.5">
                                    <span
                                        :class="[
                                            'px-3 py-1 text-xs font-bold rounded-full border inline-block',
                                            activeTeacher.tahap3.peningkatan_kinerja_tahap3 ? 'bg-purple-100 text-purple-800 border-purple-300' : 'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ activeTeacher.tahap3.peningkatan_kinerja_tahap3_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Catatan Peningkatan Kinerja -->
                            <div v-if="activeTeacher.tahap3.catatan_peningkatan_tahap3 || activeTeacher.tahap3.catatan_observer_tahap3" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800">
                                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block mb-1">Catatan Observer (Peningkatan Kinerja):</span>
                                <p class="text-xs text-slate-600 dark:text-slate-300 italic">"{{ activeTeacher.tahap3.catatan_peningkatan_tahap3 || activeTeacher.tahap3.catatan_observer_tahap3 }}"</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <Button variant="outline" @click="isTahap3ModalOpen = false">
                        Tutup
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
@media print {
    body {
        background: white !important;
        color: black !important;
    }
}
</style>
