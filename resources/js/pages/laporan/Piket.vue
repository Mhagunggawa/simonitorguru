<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, 
    ChevronDown, 
    ChevronUp, 
    Eye, 
    Calendar, 
    Clock, 
    ShieldCheck, 
    GraduationCap, 
    MapPin, 
    BookOpen,
    AlertCircle,
    CheckCircle2,
    FileText,
    FileSpreadsheet,
    RotateCcw,
    Users,
    ImageIcon,
    ExternalLink
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription
} from '@/components/ui/dialog';

const props = defineProps<{
    laporan: any;
    filters: {
        search?: string;
        status?: string;
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

const search = ref(props.filters?.search || '');
const filterStatus = ref(props.filters?.status || 'semua');
const filterType = ref(props.filters?.filter_type || 'minggu');
const filterSemester = ref(props.filters?.filter_semester || (new Date().getMonth() >= 6 ? 'Ganjil' : 'Genap'));
const filterTahun = ref(Number(props.filters?.filter_tahun) || new Date().getFullYear());
const filterBulan = ref(Number(props.filters?.filter_bulan) || (new Date().getMonth() + 1));
const filterMinggu = ref(Number(props.filters?.filter_minggu) || 1);
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const openAccordionId = ref<string | number | null>(null);
const toggleAccordion = (id: string | number) => {
    openAccordionId.value = openAccordionId.value === id ? null : id;
};

const selectedItem = ref<any>(null);
const isModalOpen = ref(false);

const openDetail = (item: any) => {
    selectedItem.value = item;
    isModalOpen.value = true;
};

const buildQueryString = () => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (filterStatus.value && filterStatus.value !== 'semua') params.append('status', filterStatus.value);
    
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

const exportPdfUrl = computed(() => `/laporan/piket/export-pdf?${buildQueryString()}`);
const exportExcelUrl = computed(() => `/laporan/piket/export-excel?${buildQueryString()}`);

const resetFilter = () => {
    search.value = '';
    filterStatus.value = 'semua';
    filterType.value = 'minggu';
    const now = new Date();
    filterTahun.value = now.getFullYear();
    filterBulan.value = now.getMonth() + 1;
    filterMinggu.value = 1;
    startDate.value = '';
    endDate.value = '';
};

watch([search, filterStatus, filterType, filterSemester, filterTahun, filterBulan, filterMinggu, startDate, endDate], debounce(() => {
    const params: Record<string, string | number> = {};
    if (search.value) params.search = search.value;
    if (filterStatus.value && filterStatus.value !== 'semua') params.status = filterStatus.value;
    
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
    
    router.get('/laporan/piket', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusBadgeClass = (status: string) => {
    switch ((status || '').toLowerCase()) {
        case 'hadir':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800';
        case 'izin':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800';
        case 'sakit':
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800';
        case 'alpha':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700';
    }
};
</script>

<template>
    <Head title="Laporan Rekapitulasi Piket" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Laporan Rekapitulasi Piket</h1>
                <p class="text-sm text-muted-foreground">Monitoring kehadiran guru di kelas per sesi shift jam piket.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-col md:flex-row gap-3 bg-card p-4 rounded-xl border border-sidebar-border/70 shadow-sm dark:border-sidebar-border">
            <div class="relative w-full md:w-1/3 lg:w-1/4">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari guru piket, guru kelas, mapel..." class="pl-8 w-full bg-background" />
            </div>
            
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <select v-model="filterType" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
                    <option value="minggu">Per Minggu</option>
                    <option value="bulan">Per Bulan</option>
                    <option value="semester">Per Semester</option>
                    <option value="tahun">Per Tahun</option>
                    <option value="semua">Semua Data</option>
                    <option value="kustom">Kustom</option>
                </select>

                <!-- Filter Status -->
                <select v-model="filterStatus" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <option value="semua">Semua Status</option>
                    <option value="Hadir">Hadir</option>
                    <option value="Terlambat">Terlambat</option>
                    <option value="Izin">Izin</option>
                    <option value="Sakit">Sakit</option>
                    <option value="Alpha">Alpha</option>
                </select>

                <template v-if="filterType === 'semester'">
                    <select v-model="filterSemester" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option value="Ganjil">Semester Ganjil</option>
                        <option value="Genap">Semester Genap</option>
                    </select>
                </template>

                <template v-if="['minggu', 'bulan'].includes(filterType)">
                    <select v-model="filterBulan" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option :value="1">Januari</option>
                        <option :value="2">Februari</option>
                        <option :value="3">Maret</option>
                        <option :value="4">April</option>
                        <option :value="5">Mei</option>
                        <option :value="6">Juni</option>
                        <option :value="7">Juli</option>
                        <option :value="8">Agustus</option>
                        <option :value="9">September</option>
                        <option :value="10">Oktober</option>
                        <option :value="11">November</option>
                        <option :value="12">Desember</option>
                    </select>
                </template>

                <template v-if="filterType === 'minggu'">
                    <select v-model="filterMinggu" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option :value="1">Minggu ke-1</option>
                        <option :value="2">Minggu ke-2</option>
                        <option :value="3">Minggu ke-3</option>
                        <option :value="4">Minggu ke-4</option>
                        <option :value="5">Minggu ke-5</option>
                        <option :value="6">Minggu ke-6</option>
                    </select>
                </template>

                <template v-if="['semester', 'tahun', 'minggu', 'bulan'].includes(filterType)">
                    <select v-model="filterTahun" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </template>

                <template v-if="filterType === 'kustom'">
                    <Input type="date" v-model="startDate" title="Tanggal Mulai" class="w-32 bg-background" />
                    <span class="text-muted-foreground text-sm">-</span>
                    <Input type="date" v-model="endDate" title="Tanggal Sampai" class="w-32 bg-background" />
                </template>

                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="resetFilter" 
                    title="Reset Filter"
                    class="h-10 text-xs px-2.5 cursor-pointer"
                >
                    <RotateCcw class="h-3.5 w-3.5 mr-1" /> Reset
                </Button>
            </div>

            <div class="flex gap-2 w-full md:w-auto ml-auto">
                <a 
                    :href="exportPdfUrl" 
                    target="_blank" 
                    class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-3.5 py-2 shadow-xs transition-colors cursor-pointer"
                    title="Export PDF Laporan Piket Sesuai Filter"
                >
                    <FileText class="w-4 h-4 text-rose-500" />
                    <span>PDF</span>
                </a>
                <a 
                    :href="exportExcelUrl" 
                    target="_blank" 
                    class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-md text-sm font-medium border border-emerald-200 bg-background text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 h-10 px-3.5 py-2 shadow-xs transition-colors cursor-pointer"
                    title="Export Excel Laporan Piket Sesuai Filter"
                >
                    <FileSpreadsheet class="w-4 h-4 text-emerald-600" />
                    <span>Excel</span>
                </a>
            </div>
        </div>

        <!-- Data Content (Desktop Table View: 1 Row Per Session) -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium">Tanggal & Jam</th>
                        <th class="px-4 py-3 font-medium">Guru Piket (Petugas)</th>
                        <th class="px-4 py-3 font-medium">Titik Pantau</th>
                        <th class="px-4 py-3 font-medium">Hasil Pemantauan Kelas</th>
                        <th class="px-4 py-3 font-medium text-center">Foto</th>
                        <th class="px-4 py-3 font-medium">Status Piket</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="item in laporan.data" :key="item.id" class="hover:bg-muted/40 transition-colors">
                        <!-- Tanggal & Jam Sesi -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="font-semibold text-foreground flex items-center gap-1.5">
                                    <Calendar class="w-3.5 h-3.5 text-muted-foreground" />
                                    {{ formatDate(item.jadwal_piket?.tanggal_piket) }}
                                </span>
                                <span class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                                    <Clock class="w-3 h-3 text-muted-foreground" />
                                    {{ item.jam_str }}
                                </span>
                            </div>
                        </td>

                        <!-- Guru Piket (Petugas) -->
                        <td class="px-4 py-3">
                            <span class="font-semibold text-foreground flex items-center gap-1.5">
                                <ShieldCheck class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                {{ item.jadwal_piket?.guru_piket?.name || item.jadwal_piket?.guruPiket?.name || '-' }}
                            </span>
                        </td>

                        <!-- Titik Pantau -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-xs font-medium text-foreground flex items-center gap-1">
                                <MapPin class="w-3.5 h-3.5 text-muted-foreground" />
                                {{ item.jadwal_piket?.titik?.nama_titik || '-' }}
                            </span>
                        </td>

                        <!-- Hasil Pemantauan Kelas (Ringkasan Sesi) -->
                        <td class="px-4 py-3">
                            <template v-if="item.is_piket_petugas">
                                <span class="text-xs text-muted-foreground italic font-normal">
                                    Tidak ada pemantauan (Petugas Piket {{ item.status_kehadiran }})
                                </span>
                            </template>
                            <div v-else class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-primary/10 text-primary border border-primary/20">
                                        <Users class="w-3 h-3" />
                                        {{ item.total_kelas }} Kelas
                                    </span>
                                    
                                    <!-- Jika semua kelas hadir -->
                                    <span 
                                        v-if="item.hadir_count === item.total_kelas"
                                        class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700"
                                    >
                                        Semua Hadir
                                    </span>

                                    <!-- Jika ada status beragam -->
                                    <template v-else>
                                        <span v-if="item.ringkasan_status?.Hadir > 0" class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            {{ item.ringkasan_status.Hadir }} Hadir
                                        </span>
                                        <span v-if="item.ringkasan_status?.Izin > 0" class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            {{ item.ringkasan_status.Izin }} Izin
                                        </span>
                                        <span v-if="item.ringkasan_status?.Sakit > 0" class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            {{ item.ringkasan_status.Sakit }} Sakit
                                        </span>
                                        <span v-if="item.ringkasan_status?.Alpha > 0" class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            {{ item.ringkasan_status.Alpha }} Alpha
                                        </span>
                                    </template>
                                </div>

                                <!-- Mini info kelas yang tidak hadir jika ada -->
                                <div v-if="item.tidak_hadir_count > 0" class="text-[11px] text-muted-foreground flex flex-wrap gap-1">
                                    <span class="font-medium text-foreground/80">Perlu perhatian:</span>
                                    <span 
                                        v-for="c in item.classes.filter((k: any) => k.status_kehadiran !== 'Hadir')" 
                                        :key="c.id"
                                        class="inline-flex items-center gap-1 text-[11px] font-medium"
                                        :class="c.status_kehadiran === 'Izin' ? 'text-blue-600 dark:text-blue-400' : 
                                                c.status_kehadiran === 'Sakit' ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400'"
                                    >
                                        {{ c.kelas_nama }} ({{ c.status_kehadiran }})
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Thumbnail Foto Dokumentasi -->
                        <td class="px-4 py-3 text-center">
                            <div v-if="item.foto_dokumentasi" class="inline-block relative group cursor-pointer" @click="openDetail(item)">
                                <img 
                                    :src="`/storage/${item.foto_dokumentasi}`" 
                                    alt="Foto Piket" 
                                    class="w-10 h-10 object-cover rounded-lg border border-border shadow-2xs group-hover:opacity-85 transition-opacity mx-auto"
                                />
                            </div>
                            <span v-else class="text-xs text-muted-foreground">—</span>
                        </td>

                        <!-- Status Piket -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-wrap items-center gap-1">
                                <span 
                                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                    :class="getStatusBadgeClass(item.status_kehadiran)"
                                >
                                    {{ item.status_kehadiran }}
                                    <span v-if="item.is_piket_petugas" class="text-[10px] opacity-80">(Piket)</span>
                                </span>
                                <span 
                                    v-if="item.is_terlambat" 
                                    class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-700"
                                >
                                    Terlambat
                                </span>
                            </div>
                        </td>

                        <!-- Aksi (Tombol Detail) -->
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <Button variant="outline" size="sm" @click="openDetail(item)" class="h-8 shadow-xs cursor-pointer">
                                <Eye class="w-3.5 h-3.5 mr-1 text-primary" /> Detail
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="laporan.data.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-muted-foreground italic">
                            Tidak ada data laporan piket yang ditemukan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Accordion View: 1 Card Per Session -->
        <div class="md:hidden flex flex-col gap-3">
            <div 
                v-for="item in laporan.data" 
                :key="item.id" 
                class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm overflow-hidden"
            >
                <!-- Accordion Header -->
                <button 
                    @click="toggleAccordion(item.id)" 
                    class="flex items-start justify-between p-4 hover:bg-muted/40 transition-colors text-left w-full gap-3 cursor-pointer"
                >
                    <div class="flex flex-col gap-2 flex-1">
                        <!-- Guru Piket -->
                        <div>
                            <span class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                <ShieldCheck class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                Guru Piket:
                            </span>
                            <span class="font-bold text-foreground text-sm">
                                {{ item.jadwal_piket?.guru_piket?.name || item.jadwal_piket?.guruPiket?.name || '-' }}
                            </span>
                        </div>

                        <!-- Jam & Tanggal -->
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <span class="flex items-center gap-1">
                                <Calendar class="w-3.5 h-3.5" />
                                {{ formatDate(item.jadwal_piket?.tanggal_piket) }}
                            </span>
                            <span>•</span>
                            <span class="flex items-center gap-1">
                                <Clock class="w-3.5 h-3.5" />
                                {{ item.jam_str }}
                            </span>
                        </div>

                        <!-- Ringkasan Kehadiran Kelas -->
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <template v-if="item.is_piket_petugas">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold border" :class="getStatusBadgeClass(item.status_kehadiran)">
                                    Petugas Piket {{ item.status_kehadiran }}
                                </span>
                            </template>
                            <template v-else>
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-primary/10 text-primary border border-primary/20">
                                    {{ item.total_kelas }} Kelas
                                </span>
                                <span 
                                    v-if="item.hadir_count === item.total_kelas"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                                >
                                    Semua Hadir
                                </span>
                                <template v-else>
                                    <span v-if="item.ringkasan_status?.Hadir > 0" class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                        {{ item.ringkasan_status.Hadir }} Hadir
                                    </span>
                                    <span v-if="item.tidak_hadir_count > 0" class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-rose-800">
                                        {{ item.tidak_hadir_count }} Tidak Hadir
                                    </span>
                                </template>
                            </template>
                            <span v-if="item.is_terlambat" class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800">
                                Terlambat
                            </span>
                        </div>
                    </div>
                    <div class="pt-1">
                        <ChevronDown v-if="openAccordionId !== item.id" class="w-5 h-5 text-muted-foreground" />
                        <ChevronUp v-else class="w-5 h-5 text-muted-foreground" />
                    </div>
                </button>

                <!-- Accordion Body -->
                <div v-if="openAccordionId === item.id" class="p-4 bg-muted/20 border-t border-border/50 space-y-3 text-sm">
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <MapPin class="w-4 h-4 text-primary" />
                        <span>Titik Piket: <strong class="text-foreground">{{ item.jadwal_piket?.titik?.nama_titik || '-' }}</strong></span>
                    </div>

                    <div v-if="item.foto_dokumentasi" class="flex items-center gap-3 p-2 bg-background rounded-lg border border-border/60">
                        <img 
                            :src="`/storage/${item.foto_dokumentasi}`" 
                            alt="Foto Bukti" 
                            class="w-14 h-14 object-cover rounded-md border"
                        />
                        <div class="text-xs">
                            <span class="font-semibold block text-foreground">Foto Dokumentasi Sesi</span>
                            <span class="text-muted-foreground">Tercatat pada pelaporan shift ini</span>
                        </div>
                    </div>

                    <Button variant="default" size="sm" class="w-full mt-2 cursor-pointer" @click="openDetail(item)">
                        <Eye class="w-4 h-4 mr-1.5" /> Lihat Detail Seluruh Kelas
                    </Button>
                </div>
            </div>

            <div v-if="laporan.data.length === 0" class="p-8 text-center text-muted-foreground italic rounded-xl border border-dashed">
                Tidak ada data laporan piket yang ditemukan.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="laporan.links?.length > 3" class="flex flex-wrap justify-center gap-1 mt-6">
            <template v-for="(link, k) in laporan.links" :key="k">
                <div v-if="link.url === null" 
                     class="px-3 py-2 text-sm text-muted-foreground bg-card border border-border/50 rounded-md opacity-50 cursor-not-allowed" 
                     v-html="link.label">
                </div>
                <button v-else
                      @click="router.get(link.url, {}, { preserveScroll: true, preserveState: true })"
                      class="px-3 py-2 text-sm rounded-md transition-colors border cursor-pointer"
                      :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card border-border/50 hover:bg-slate-50 text-foreground dark:hover:bg-slate-800'"
                      v-html="link.label">
                </button>
            </template>
        </div>

        <!-- DETAIL MODAL: RINCIAN SELURUH KELAS PADA SESI SHIFT PIKET -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold flex items-center gap-2">
                        <ShieldCheck class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        Detail Pemantauan Sesi Piket
                    </DialogTitle>
                    <DialogDescription>
                        Rincian kehadiran seluruh kelas yang dipantau oleh guru piket pada shift ini.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2" v-if="selectedItem">
                    <!-- Sesi Info Card Header -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-muted/40 rounded-xl border border-border text-sm">
                        <div class="space-y-1">
                            <span class="text-xs text-muted-foreground block">Guru Piket (Petugas):</span>
                            <span class="font-bold text-foreground text-sm flex items-center gap-1.5">
                                <ShieldCheck class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                {{ selectedItem.jadwal_piket?.guru_piket?.name || selectedItem.jadwal_piket?.guruPiket?.name || '-' }}
                            </span>
                            <span class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                                <MapPin class="w-3.5 h-3.5" />
                                Titik: <strong class="text-foreground">{{ selectedItem.jadwal_piket?.titik?.nama_titik || '-' }}</strong>
                            </span>
                        </div>

                        <div class="space-y-1 sm:text-right">
                            <span class="text-xs text-muted-foreground block">Waktu Sesi:</span>
                            <span class="font-bold text-foreground text-sm flex items-center sm:justify-end gap-1.5">
                                <Calendar class="w-4 h-4 text-primary" />
                                {{ formatDate(selectedItem.jadwal_piket?.tanggal_piket) }}
                            </span>
                            <span class="text-xs text-muted-foreground flex items-center sm:justify-end gap-1 mt-0.5">
                                <Clock class="w-3.5 h-3.5" />
                                {{ selectedItem.jam_str }}
                            </span>
                        </div>
                    </div>

                    <!-- Kasus 1: Petugas Piket Berhalangan (Izin/Sakit/Alpha) -->
                    <div v-if="selectedItem.is_piket_petugas" class="space-y-3">
                        <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50 dark:border-amber-900/40 dark:bg-amber-950/20 text-sm space-y-2">
                            <div class="flex items-center justify-between pb-2 border-b border-amber-200/60 dark:border-amber-800/40">
                                <span class="font-semibold text-amber-800 dark:text-amber-300">Status Petugas Piket:</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border" :class="getStatusBadgeClass(selectedItem.status_kehadiran)">
                                    {{ selectedItem.status_kehadiran }}
                                </span>
                            </div>
                            <p class="text-xs text-amber-900 dark:text-amber-200">
                                Petugas guru piket tidak melakukan pemantauan kelas pada shift ini karena berstatus <strong>{{ selectedItem.status_kehadiran }}</strong>.
                            </p>
                            <div v-if="selectedItem.keterangan" class="pt-2 text-xs text-muted-foreground">
                                <strong>Keterangan:</strong> {{ selectedItem.keterangan }}
                            </div>
                        </div>
                    </div>

                    <!-- Kasus 2: Pemantauan Normal (Ada Kelas-kelas yang Dipantau) -->
                    <template v-else>
                        <!-- Foto Bukti Dokumentasi Sesi -->
                        <div v-if="selectedItem.foto_dokumentasi" class="rounded-xl border border-border p-3 bg-card flex flex-col items-center">
                            <img 
                                :src="`/storage/${selectedItem.foto_dokumentasi}`" 
                                alt="Foto Dokumentasi Sesi Piket" 
                                class="w-full max-h-72 object-contain rounded-lg shadow-xs"
                            />
                            <div class="mt-2 text-xs text-muted-foreground flex items-center gap-1.5">
                                <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                                <span>Foto Dokumentasi Sesi Shift</span>
                            </div>
                        </div>

                        <!-- Bar Ringkasan Status Sesi -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-card border border-border text-xs sm:text-sm flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <Users class="w-4 h-4 text-primary" />
                                <span class="font-medium">Total: <strong>{{ selectedItem.total_kelas }} Kelas</strong></span>
                            </div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-2 py-0.5 rounded-full font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300">
                                    {{ selectedItem.hadir_count }} Hadir
                                </span>
                                <span v-if="selectedItem.tidak_hadir_count > 0" class="px-2 py-0.5 rounded-full font-semibold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300">
                                    {{ selectedItem.tidak_hadir_count }} Tidak Hadir
                                </span>
                            </div>
                        </div>

                        <!-- TABEL RINCIAN KELAS-KELAS DI DALAM SESI -->
                        <div class="space-y-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground block">
                                Daftar Kelas yang Dipantau:
                            </span>

                            <div class="rounded-xl border border-border overflow-hidden">
                                <table class="w-full text-xs sm:text-sm text-left">
                                    <thead class="text-[11px] uppercase bg-muted/60 border-b border-border text-muted-foreground">
                                        <tr>
                                            <th class="px-3 py-2.5 font-semibold">Kelas</th>
                                            <th class="px-3 py-2.5 font-semibold">Mata Pelajaran</th>
                                            <th class="px-3 py-2.5 font-semibold">Guru Pengampu</th>
                                            <th class="px-3 py-2.5 font-semibold text-center">Status</th>
                                            <th class="px-3 py-2.5 font-semibold">Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border">
                                        <tr 
                                            v-for="c in selectedItem.classes" 
                                            :key="c.id"
                                            class="hover:bg-muted/30 transition-colors"
                                            :class="{'bg-rose-50/30 dark:bg-rose-950/10': c.status_kehadiran !== 'Hadir'}"
                                        >
                                            <!-- Kelas & Jurusan -->
                                            <td class="px-3 py-2.5 whitespace-nowrap">
                                                <div class="font-bold text-foreground">{{ c.kelas_nama }}</div>
                                                <div v-if="c.jurusan_nama" class="text-[11px] text-muted-foreground">{{ c.jurusan_nama }}</div>
                                            </td>

                                            <!-- Mata Pelajaran (Terbaca Sempurna!) -->
                                            <td class="px-3 py-2.5">
                                                <span class="font-medium text-foreground flex items-center gap-1">
                                                    <BookOpen class="w-3.5 h-3.5 text-muted-foreground shrink-0" />
                                                    {{ c.mapel_nama }}
                                                </span>
                                            </td>

                                            <!-- Guru Mengajar -->
                                            <td class="px-3 py-2.5">
                                                <span class="font-medium text-foreground flex items-center gap-1">
                                                    <GraduationCap class="w-3.5 h-3.5 text-muted-foreground shrink-0" />
                                                    {{ c.guru_nama }}
                                                </span>
                                            </td>

                                            <!-- Status Kehadiran -->
                                            <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                                <span 
                                                    class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                                    :class="getStatusBadgeClass(c.status_kehadiran)"
                                                >
                                                    {{ c.status_kehadiran }}
                                                </span>
                                            </td>

                                            <!-- Catatan Per Kelas -->
                                            <td class="px-3 py-2.5 text-xs text-muted-foreground">
                                                {{ c.keterangan || '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
