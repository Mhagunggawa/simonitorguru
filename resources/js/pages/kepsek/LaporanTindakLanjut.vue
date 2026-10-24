<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, 
    Info, 
    FileText, 
    ExternalLink, 
    CheckCircle2, 
    Download, 
    FileSpreadsheet, 
    AlertTriangle,
    ShieldCheck
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface SPHistory {
    id: number;
    tipe_peringatan: string;
    file_dokumen?: string;
    created_at?: string;
}

interface EvaluasiGuru {
    id: number;
    nama: string;
    tipe_guru: string;
    total_alpha: number;
    alpha_details?: {
        sumber: string;
        tanggal: string;
        foto: string | null;
        kelas?: string;
        mapel?: string;
        petugas_piket?: string;
        keterangan?: string;
    }[];
    sp_history: SPHistory[];
}

const props = defineProps<{
    laporan: {
        data: EvaluasiGuru[];
        links: any[];
        total: number;
        from: number;
        to: number;
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        show_all?: string;
        filter_type?: string;
        filter_semester?: string;
        filter_tahun?: number;
        filter_bulan?: number;
        filter_minggu?: number;
        start_date?: string;
        end_date?: string;
    };
    available_years: number[];
}>();

const search = ref(props.filters.search || '');
const showAll = ref(props.filters.show_all === 'true');
const filterType = ref(props.filters.filter_type || 'minggu');
const filterSemester = ref(props.filters.filter_semester || (new Date().getMonth() >= 6 ? 'Ganjil' : 'Genap'));
const filterTahun = ref(props.filters.filter_tahun || new Date().getFullYear());
const filterBulan = ref(props.filters.filter_bulan || new Date().getMonth() + 1);
const filterMinggu = ref(props.filters.filter_minggu || 1);
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const showDetailModal = ref(false);
const selectedDetailGuru = ref<EvaluasiGuru | null>(null);

const months = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const buildQueryString = () => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (showAll.value) params.append('show_all', 'true');
    params.append('filter_type', filterType.value);

    if (filterType.value === 'semester') {
        params.append('filter_semester', filterSemester.value);
        params.append('filter_tahun', filterTahun.value.toString());
    } else if (filterType.value === 'tahun') {
        params.append('filter_tahun', filterTahun.value.toString());
    } else if (filterType.value === 'bulan') {
        params.append('filter_tahun', filterTahun.value.toString());
        params.append('filter_bulan', filterBulan.value.toString());
    } else if (filterType.value === 'minggu') {
        params.append('filter_tahun', filterTahun.value.toString());
        params.append('filter_bulan', filterBulan.value.toString());
        params.append('filter_minggu', filterMinggu.value.toString());
    } else if (filterType.value === 'kustom') {
        if (startDate.value) params.append('start_date', startDate.value);
        if (endDate.value) params.append('end_date', endDate.value);
    }
    return params.toString();
};

watch([search, showAll, filterType, filterSemester, filterTahun, filterBulan, filterMinggu, startDate, endDate], debounce(() => {
    const params: Record<string, string | number> = {};
    if (search.value) params.search = search.value;
    if (showAll.value) params.show_all = 'true';
    
    params.filter_type = filterType.value;
    if (filterType.value === 'semester') {
        params.filter_semester = filterSemester.value;
        params.filter_tahun = filterTahun.value;
    } else if (filterType.value === 'tahun') {
        params.filter_tahun = filterTahun.value;
    } else if (filterType.value === 'bulan') {
        params.filter_tahun = filterTahun.value;
        params.filter_bulan = filterBulan.value;
    } else if (filterType.value === 'minggu') {
        params.filter_tahun = filterTahun.value;
        params.filter_bulan = filterBulan.value;
        params.filter_minggu = filterMinggu.value;
    } else if (filterType.value === 'kustom') {
        if (startDate.value) params.start_date = startDate.value;
        if (endDate.value) params.end_date = endDate.value;
    }
    
    router.get('/kepsek/laporan-tindak-lanjut', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

const exportPdfUrl = computed(() => `/kepsek/laporan-tindak-lanjut/export-pdf?${buildQueryString()}`);
const exportExcelUrl = computed(() => `/kepsek/laporan-tindak-lanjut/export-excel?${buildQueryString()}`);

const openDetailModal = (guru: EvaluasiGuru) => {
    selectedDetailGuru.value = guru;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    setTimeout(() => {
        selectedDetailGuru.value = null;
    }, 300);
};

const hasSP = (guru: EvaluasiGuru, tipe: string) => {
    return guru.sp_history.some(sp => sp.tipe_peringatan === tipe);
};

const getSP = (guru: EvaluasiGuru, tipe: string): SPHistory | undefined => {
    return guru.sp_history.find(sp => sp.tipe_peringatan === tipe);
};

const formatFullDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const formatDateTime = (dateString?: string) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Laporan Tindak Lanjut Kedisiplinan Guru" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-card border rounded-xl p-5 shadow-sm">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 mb-2">
                        <ShieldCheck class="w-3.5 h-3.5" /> Laporan Eksekutif Kepala Sekolah
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-foreground">
                        Laporan Tindak Lanjut Kedisiplinan Guru
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Rekapitulasi ketidakhadiran (Alpha) dan surat peringatan (SP) yang diterbitkan oleh Wakasek.
                    </p>
                </div>

                <!-- Export Buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    <a :href="exportPdfUrl" target="_blank">
                        <Button variant="outline" size="sm" class="gap-1.5 border-red-300 text-red-600 hover:bg-red-50 hover:text-red-700 dark:border-red-800 dark:text-red-400">
                            <Download class="w-4 h-4" /> Cetak PDF
                        </Button>
                    </a>
                    <a :href="exportExcelUrl">
                        <Button variant="outline" size="sm" class="gap-1.5 border-emerald-300 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 dark:border-emerald-800 dark:text-emerald-400">
                            <FileSpreadsheet class="w-4 h-4" /> Unduh Excel
                        </Button>
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-card border rounded-xl p-4 shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="relative">
                        <Label class="text-xs mb-1.5 block">Cari Nama Guru</Label>
                        <Search class="absolute left-3 top-8 h-4 w-4 text-muted-foreground" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari guru..." 
                            class="pl-9 h-9 text-sm" 
                        />
                    </div>

                    <!-- Filter Type -->
                    <div>
                        <Label class="text-xs mb-1.5 block">Jenis Periode</Label>
                        <select 
                            v-model="filterType" 
                            class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="minggu">Mingguan</option>
                            <option value="bulan">Bulanan</option>
                            <option value="semester">Semester</option>
                            <option value="tahun">Tahunan</option>
                            <option value="kustom">Rentang Tanggal</option>
                        </select>
                    </div>

                    <!-- Dynamic Controls based on Filter Type -->
                    <template v-if="filterType === 'minggu'">
                        <div>
                            <Label class="text-xs mb-1.5 block">Bulan & Tahun</Label>
                            <div class="grid grid-cols-2 gap-2">
                                <select v-model="filterBulan" class="h-9 px-2 rounded-md border border-input bg-background text-xs">
                                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                                </select>
                                <select v-model="filterTahun" class="h-9 px-2 rounded-md border border-input bg-background text-xs">
                                    <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs mb-1.5 block">Minggu Ke-</Label>
                            <select v-model="filterMinggu" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm">
                                <option :value="1">Minggu ke-1</option>
                                <option :value="2">Minggu ke-2</option>
                                <option :value="3">Minggu ke-3</option>
                                <option :value="4">Minggu ke-4</option>
                                <option :value="5">Minggu ke-5</option>
                            </select>
                        </div>
                    </template>

                    <template v-else-if="filterType === 'bulan'">
                        <div>
                            <Label class="text-xs mb-1.5 block">Bulan</Label>
                            <select v-model="filterBulan" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm">
                                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                        <div>
                            <Label class="text-xs mb-1.5 block">Tahun</Label>
                            <select v-model="filterTahun" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm">
                                <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                    </template>

                    <template v-else-if="filterType === 'semester'">
                        <div>
                            <Label class="text-xs mb-1.5 block">Semester</Label>
                            <select v-model="filterSemester" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm">
                                <option value="Ganjil">Semester Ganjil</option>
                                <option value="Genap">Semester Genap</option>
                            </select>
                        </div>
                        <div>
                            <Label class="text-xs mb-1.5 block">Tahun</Label>
                            <select v-model="filterTahun" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm">
                                <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                    </template>

                    <template v-else-if="filterType === 'tahun'">
                        <div>
                            <Label class="text-xs mb-1.5 block">Tahun</Label>
                            <select v-model="filterTahun" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm">
                                <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                    </template>

                    <template v-else-if="filterType === 'kustom'">
                        <div>
                            <Label class="text-xs mb-1.5 block">Dari Tanggal</Label>
                            <Input v-model="startDate" type="date" class="h-9 text-sm" />
                        </div>
                        <div>
                            <Label class="text-xs mb-1.5 block">Sampai Tanggal</Label>
                            <Input v-model="endDate" type="date" class="h-9 text-sm" />
                        </div>
                    </template>
                </div>

                <div class="flex items-center space-x-2 pt-2 border-t">
                    <Checkbox id="show-all" :checked="showAll" @update:checked="(val: boolean) => showAll = val" />
                    <Label for="show-all" class="text-xs text-muted-foreground cursor-pointer">
                        Tampilkan juga seluruh guru yang disiplin (0x Alpha)
                    </Label>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block bg-card border rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="border-b bg-muted/40 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            <th class="px-4 py-3 text-center w-12">No</th>
                            <th class="px-4 py-3">Nama Guru</th>
                            <th class="px-4 py-3">Tipe Guru</th>
                            <th class="px-4 py-3 text-center">Akumulasi Alpha</th>
                            <th class="px-4 py-3">Status Kedisiplinan</th>
                            <th class="px-4 py-3">Dokumen Surat Peringatan (SP)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="(guru, index) in laporan.data" :key="guru.id" class="hover:bg-muted/20 transition-colors">
                            <td class="px-4 py-3.5 text-center text-muted-foreground font-medium">
                                {{ (laporan.current_page - 1) * 10 + index + 1 }}
                            </td>
                            <td class="px-4 py-3.5 font-medium text-foreground">
                                {{ guru.nama }}
                            </td>
                            <td class="px-4 py-3.5 text-muted-foreground">
                                {{ guru.tipe_guru }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <span v-if="guru.total_alpha > 0" class="font-bold text-red-600 dark:text-red-400">
                                        {{ guru.total_alpha }}x Alpha
                                    </span>
                                    <span v-else class="text-emerald-600 font-medium text-xs">
                                        Disiplin (0)
                                    </span>
                                    
                                    <button 
                                        v-if="guru.total_alpha > 0" 
                                        @click="openDetailModal(guru)" 
                                        title="Lihat Rincian Bukti Alpha" 
                                        class="inline-flex items-center justify-center rounded-full bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 p-1.5 transition-colors"
                                    >
                                        <Info class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div v-if="hasSP(guru, 'SP 3')">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                        <AlertTriangle class="w-3.5 h-3.5" /> Panggilan Kepsek (SP 3)
                                    </span>
                                </div>
                                <div v-else-if="hasSP(guru, 'SP 2')">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                                        <AlertTriangle class="w-3.5 h-3.5" /> Terkena SP 2
                                    </span>
                                </div>
                                <div v-else-if="hasSP(guru, 'SP 1')">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                        <AlertTriangle class="w-3.5 h-3.5" /> Terkena SP 1
                                    </span>
                                </div>
                                <div v-else-if="guru.total_alpha === 1">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        <Info class="w-3.5 h-3.5" /> Teguran Lisan
                                    </span>
                                </div>
                                <div v-else-if="guru.total_alpha >= 2">
                                    <span class="text-xs italic text-muted-foreground">
                                        Menunggu Penerbitan SP oleh Wakasek
                                    </span>
                                </div>
                                <div v-else>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        <CheckCircle2 class="w-3.5 h-3.5" /> Disiplin
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div v-if="guru.sp_history && guru.sp_history.length > 0" class="flex flex-col gap-1.5">
                                    <div v-for="sp in guru.sp_history" :key="sp.id" class="inline-flex items-center gap-2">
                                        <a 
                                            v-if="sp.file_dokumen" 
                                            :href="'/storage/' + sp.file_dokumen" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-muted hover:bg-muted/80 text-foreground border transition-colors"
                                            title="Buka / Unduh Dokumen Surat"
                                        >
                                            <FileText class="w-3.5 h-3.5 text-primary" />
                                            <span>{{ sp.tipe_peringatan }}</span>
                                            <ExternalLink class="w-3 h-3 opacity-60 ml-0.5" />
                                        </a>
                                        <span v-else class="text-xs text-muted-foreground">
                                            {{ sp.tipe_peringatan }} (Tanpa File)
                                        </span>
                                        <span v-if="sp.created_at" class="text-[11px] text-muted-foreground">
                                            ({{ formatDateTime(sp.created_at) }})
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-muted-foreground italic">
                                    Belum ada surat diterbitkan
                                </div>
                            </td>
                        </tr>
                        <tr v-if="laporan.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground italic">
                                Tidak ada data guru yang ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card Layout -->
            <div class="md:hidden flex flex-col gap-4">
                <div v-for="guru in laporan.data" :key="guru.id" class="bg-card border rounded-xl p-4 shadow-sm space-y-3">
                    <div class="flex justify-between items-start border-b pb-2">
                        <div>
                            <span class="font-bold text-foreground text-base block">{{ guru.nama }}</span>
                            <span class="text-xs text-muted-foreground">{{ guru.tipe_guru }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="guru.total_alpha > 0" class="text-sm font-bold text-red-600 dark:text-red-400">
                                {{ guru.total_alpha }}x Alpha
                            </span>
                            <span v-else class="text-xs font-medium text-emerald-600">Disiplin</span>
                            
                            <button 
                                v-if="guru.total_alpha > 0" 
                                @click="openDetailModal(guru)" 
                                title="Lihat Rincian Bukti Alpha" 
                                class="inline-flex items-center justify-center rounded-full bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 p-1.5 transition-colors"
                            >
                                <Info class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="text-xs space-y-1.5">
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground font-medium">Status Kedisiplinan:</span>
                            <span v-if="hasSP(guru, 'SP 3')" class="px-2 py-0.5 rounded bg-red-100 text-red-700 font-semibold text-[11px]">SP 3</span>
                            <span v-else-if="hasSP(guru, 'SP 2')" class="px-2 py-0.5 rounded bg-orange-100 text-orange-700 font-semibold text-[11px]">SP 2</span>
                            <span v-else-if="hasSP(guru, 'SP 1')" class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-semibold text-[11px]">SP 1</span>
                            <span v-else-if="guru.total_alpha === 1" class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-semibold text-[11px]">Teguran Lisan</span>
                            <span v-else-if="guru.total_alpha >= 2" class="text-muted-foreground italic text-[11px]">Perlu Tindakan</span>
                            <span v-else class="text-emerald-600 font-medium text-[11px]">Disiplin</span>
                        </div>

                        <div class="pt-2 border-t">
                            <span class="text-muted-foreground font-medium block mb-1.5">Dokumen Surat Peringatan:</span>
                            <div v-if="guru.sp_history && guru.sp_history.length > 0" class="flex flex-wrap gap-2">
                                <a 
                                    v-for="sp in guru.sp_history" 
                                    :key="sp.id"
                                    :href="'/storage/' + sp.file_dokumen" 
                                    target="_blank" 
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-muted border text-xs font-medium"
                                >
                                    <FileText class="w-3 h-3 text-primary" />
                                    <span>{{ sp.tipe_peringatan }}</span>
                                    <ExternalLink class="w-2.5 h-2.5 opacity-60" />
                                </a>
                            </div>
                            <span v-else class="text-muted-foreground italic text-[11px]">Belum ada dokumen surat diterbitkan.</span>
                        </div>
                    </div>
                </div>

                <div v-if="laporan.data.length === 0" class="text-center p-8 border rounded-xl bg-card text-muted-foreground italic">
                    Tidak ada data guru yang ditemukan.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="laporan.links && laporan.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-6">
                <template v-for="(link, key) in laporan.links" :key="key">
                    <button
                        v-if="link.url"
                        @click="router.get(link.url, {}, { preserveScroll: true, preserveState: true })"
                        class="px-4 py-2 border rounded-md text-sm font-medium transition-colors"
                        :class="[
                            link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-background hover:bg-muted text-foreground'
                        ]"
                        v-html="link.label"
                    />
                    <span 
                        v-else 
                        class="px-4 py-2 border rounded-md text-sm text-muted-foreground opacity-50 bg-background"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Alpha Detail Modal -->
        <Dialog :open="showDetailModal" @update:open="closeDetailModal">
            <DialogContent class="max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Detail Alpha: {{ selectedDetailGuru?.nama }}</DialogTitle>
                    <DialogDescription>
                        Rincian ketidakhadiran (Alpha) dari laporan presensi GMS maupun Piket.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div v-if="selectedDetailGuru?.alpha_details?.length" class="space-y-4">
                        <div v-for="(detail, index) in selectedDetailGuru.alpha_details" :key="index" class="p-4 border rounded-lg bg-muted/30">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
                                <div>
                                    <span class="font-semibold text-foreground">{{ formatFullDate(detail.tanggal) }}</span>
                                    <span class="text-xs text-muted-foreground block mt-0.5">Sumber Presensi: {{ detail.sumber }}</span>
                                </div>
                                <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 text-xs font-semibold rounded-md">Alpha</span>
                            </div>
                            
                            <div v-if="detail.keterangan" class="text-xs text-muted-foreground mb-3 bg-background p-2.5 rounded border">
                                {{ detail.keterangan }}
                            </div>
                            
                            <div v-if="detail.foto" class="mt-2">
                                <span class="text-xs font-medium text-muted-foreground mb-1.5 block">Bukti Dokumentasi:</span>
                                <div class="relative w-full max-w-sm rounded-md overflow-hidden border">
                                    <img :src="'/storage/' + detail.foto" alt="Dokumentasi Alpha" class="object-cover w-full h-auto" />
                                </div>
                            </div>
                            <div v-else class="text-xs italic text-muted-foreground bg-background p-2 rounded border border-dashed">
                                Tidak ada lampiran foto dokumentasi.
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-muted-foreground italic">
                        Tidak ada detail ketidakhadiran yang ditemukan.
                    </div>
                </div>
                
                <DialogFooter>
                    <Button type="button" @click="closeDetailModal">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
</template>
