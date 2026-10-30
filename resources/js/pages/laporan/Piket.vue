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
    RotateCcw
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

const openAccordionId = ref<number | null>(null);
const toggleAccordion = (id: number) => {
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

const getJamStr = (jamPelajarans?: any[]) => {
    if (!jamPelajarans || jamPelajarans.length === 0) return '-';
    return jamPelajarans.map((j: any) => j.urutan_jam).join(', ');
};
</script>

<template>
    <Head title="Laporan Rekapitulasi Piket" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Laporan Rekapitulasi Piket</h1>
                <p class="text-sm text-muted-foreground">Monitoring kehadiran guru di kelas oleh guru piket berdasarkan jadwal.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-col md:flex-row gap-3 bg-card p-4 rounded-xl border border-sidebar-border/70 shadow-sm dark:border-sidebar-border">
            <div class="relative w-full md:w-1/3 lg:w-1/4">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari guru piket, guru mengajar, mapel, kelas..." class="pl-8 w-full bg-background" />
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
                    class="h-10 text-xs px-2.5"
                >
                    <RotateCcw class="h-3.5 w-3.5 mr-1" /> Reset
                </Button>
            </div>

            <div class="flex gap-2 w-full md:w-auto ml-auto">
                <a 
                    :href="exportPdfUrl" 
                    target="_blank" 
                    class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-3.5 py-2 shadow-xs transition-colors"
                    title="Export PDF Laporan Piket Sesuai Filter"
                >
                    <FileText class="w-4 h-4 text-rose-500" />
                    <span>PDF</span>
                </a>
                <a 
                    :href="exportExcelUrl" 
                    target="_blank" 
                    class="inline-flex items-center gap-1.5 justify-center whitespace-nowrap rounded-md text-sm font-medium border border-emerald-200 bg-background text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 h-10 px-3.5 py-2 shadow-xs transition-colors"
                    title="Export Excel Laporan Piket Sesuai Filter"
                >
                    <FileSpreadsheet class="w-4 h-4 text-emerald-600" />
                    <span>Excel</span>
                </a>
            </div>
        </div>

        <!-- Data Content -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm dark:border-sidebar-border overflow-hidden">
            <!-- Desktop Table View -->
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Guru Piket (Petugas)</th>
                        <th class="px-4 py-3 font-medium">Guru Mengajar (Di Kelas)</th>
                        <th class="px-4 py-3 font-medium">Kelas & Jam</th>
                        <th class="px-4 py-3 font-medium">Status Kehadiran</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="item in laporan.data" :key="item.id" class="hover:bg-muted/40 transition-colors">
                        <!-- Tanggal -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 text-foreground font-medium">
                                <Calendar class="w-3.5 h-3.5 text-muted-foreground" />
                                {{ formatDate(item.jadwal_piket?.tanggal_piket) }}
                            </div>
                        </td>

                        <!-- Guru Piket (Petugas) -->
                        <td class="px-4 py-3">
                            <div class="flex flex-col">
                                <span class="font-semibold text-foreground flex items-center gap-1.5">
                                    <ShieldCheck class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                    {{ item.jadwal_piket?.guru_piket?.name || '-' }}
                                </span>
                                <span class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5 ml-5">
                                    <MapPin class="w-3 h-3 text-muted-foreground" />
                                    Titik: {{ item.jadwal_piket?.titik?.nama_titik || '-' }}
                                </span>
                            </div>
                        </td>

                        <!-- Guru Mengajar (Di Kelas) -->
                        <td class="px-4 py-3">
                            <template v-if="item.is_piket_petugas">
                                <span class="text-xs text-muted-foreground italic font-normal">—</span>
                            </template>
                            <div v-else class="flex flex-col">
                                <span class="font-semibold text-foreground flex items-center gap-1.5">
                                    <GraduationCap class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                    {{ item.jadwal_mapel?.guru?.name || 'Belum Ditentukan' }}
                                </span>
                                <span class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5 ml-5">
                                    <BookOpen class="w-3 h-3 text-muted-foreground" />
                                    {{ item.jadwal_mapel?.mataPelajaran?.nama_mapel || '-' }}
                                </span>
                            </div>
                        </td>

                        <!-- Kelas & Jam -->
                        <td class="px-4 py-3">
                            <template v-if="item.is_piket_petugas">
                                <span class="text-xs text-muted-foreground flex items-center gap-1">
                                    <Clock class="w-3.5 h-3.5 text-muted-foreground" />
                                    Piket Jam ke-{{ getJamStr(item.jadwal_piket?.jam_pelajarans) }}
                                </span>
                            </template>
                            <div v-else class="flex flex-col">
                                <span class="font-medium text-foreground">
                                    {{ item.jadwal_mapel?.kelas?.nama_kelas || '-' }}
                                    <span v-if="item.jadwal_mapel?.kelas?.jurusan" class="text-xs text-muted-foreground">
                                        ({{ item.jadwal_mapel?.kelas?.jurusan?.nama_jurusan }})
                                    </span>
                                </span>
                                <span class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                                    <Clock class="w-3 h-3 text-muted-foreground" />
                                    Jam ke-{{ getJamStr(item.jadwal_mapel?.jam_pelajarans) }}
                                </span>
                            </div>
                        </td>

                        <!-- Status Kehadiran -->
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span 
                                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                    :class="getStatusBadgeClass(item.status_kehadiran)"
                                >
                                    {{ item.status_kehadiran || '-' }}
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

                        <!-- Aksi -->
                        <td class="px-4 py-3 text-right">
                            <Button variant="outline" size="sm" @click="openDetail(item)" class="h-8 shadow-xs">
                                <Eye class="w-3.5 h-3.5 mr-1" /> Detail
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="laporan.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground italic">
                            Tidak ada data laporan yang ditemukan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Accordion View -->
        <div class="md:hidden flex flex-col gap-3">
            <div 
                v-for="item in laporan.data" 
                :key="item.id" 
                class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm overflow-hidden"
            >
                <!-- Accordion Header -->
                <button 
                    @click="toggleAccordion(item.id)" 
                    class="flex items-start justify-between p-4 hover:bg-muted/40 transition-colors text-left w-full gap-3"
                >
                    <div class="flex flex-col gap-2 flex-1">
                        <!-- Guru Piket badge & name -->
                        <div>
                            <span class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                <ShieldCheck class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                Guru Piket:
                            </span>
                            <span class="font-bold text-foreground text-sm">
                                {{ item.jadwal_piket?.guru_piket?.name || '-' }}
                            </span>
                        </div>

                        <!-- Guru Mengajar badge & name -->
                        <div v-if="!item.is_piket_petugas">
                            <span class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                <GraduationCap class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                Guru Mengajar:
                            </span>
                            <span class="font-medium text-foreground text-sm">
                                {{ item.jadwal_mapel?.guru?.name || 'Belum Ditentukan' }}
                            </span>
                            <span class="text-xs text-muted-foreground block">
                                {{ item.jadwal_mapel?.mataPelajaran?.nama_mapel || '-' }} • {{ item.jadwal_mapel?.kelas?.nama_kelas || '-' }}
                            </span>
                        </div>
                        <div v-else>
                            <span class="text-xs text-muted-foreground italic font-normal">Tidak ada pantauan kelas (Status Piket: {{ item.status_kehadiran }})</span>
                        </div>

                        <!-- Status badge -->
                        <div class="flex items-center gap-1.5 mt-1">
                            <span 
                                class="px-2 py-0.5 rounded-full text-[11px] font-semibold border"
                                :class="getStatusBadgeClass(item.status_kehadiran)"
                            >
                                {{ item.status_kehadiran || '-' }}
                                <span v-if="item.is_piket_petugas" class="text-[10px] opacity-80">(Piket)</span>
                            </span>
                            <span 
                                v-if="item.is_terlambat" 
                                class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                            >
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
                        <Calendar class="w-4 h-4" />
                        <span>Tanggal: {{ formatDate(item.jadwal_piket?.tanggal_piket) }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <MapPin class="w-4 h-4" />
                        <span>Titik Piket: <strong class="text-foreground">{{ item.jadwal_piket?.titik?.nama_titik || '-' }}</strong></span>
                    </div>
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <Clock class="w-4 h-4" />
                        <span>Jam Pelajaran: <strong class="text-foreground">Jam ke-{{ item.is_piket_petugas ? getJamStr(item.jadwal_piket?.jam_pelajarans) : getJamStr(item.jadwal_mapel?.jam_pelajarans) }}</strong></span>
                    </div>
                    <div v-if="item.jadwal_mapel" class="flex items-start gap-2 text-muted-foreground">
                        <BookOpen class="w-4 h-4 mt-0.5" />
                        <span>
                            Mapel: <strong class="text-foreground">{{ item.jadwal_mapel?.mataPelajaran?.nama_mapel || '-' }}</strong>
                            ({{ item.jadwal_mapel?.kelas?.nama_kelas || '-' }} - {{ item.jadwal_mapel?.kelas?.jurusan?.nama_jurusan || '-' }})
                        </span>
                    </div>

                    <div v-if="item.keterangan" class="p-2.5 rounded-md bg-background border border-border/60 text-xs">
                        <span class="font-medium text-muted-foreground block mb-0.5">Catatan/Keterangan:</span>
                        <p class="text-foreground">{{ item.keterangan }}</p>
                    </div>

                    <Button variant="default" size="sm" class="w-full mt-2" @click="openDetail(item)">
                        <Eye class="w-4 h-4 mr-1.5" /> Lihat Detail Lengkap
                    </Button>
                </div>
            </div>

            <div v-if="laporan.data.length === 0" class="p-8 text-center text-muted-foreground italic rounded-xl border border-dashed">
                Tidak ada data laporan yang ditemukan.
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
                      class="px-3 py-2 text-sm rounded-md transition-colors border"
                      :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card border-border/50 hover:bg-slate-50 text-foreground dark:hover:bg-slate-800'"
                      v-html="link.label">
                </button>
            </template>
        </div>

        <!-- Detail Modal -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold">Detail Laporan Monitoring Piket</DialogTitle>
                    <DialogDescription>
                        Informasi lengkap pemantauan kehadiran kelas oleh petugas guru piket.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2" v-if="selectedItem">
                    <!-- Foto Dokumentasi -->
                    <div class="flex flex-col items-center justify-center" v-if="selectedItem.foto_dokumentasi">
                        <img 
                            :src="`/storage/${selectedItem.foto_dokumentasi}`" 
                            alt="Foto Dokumentasi Piket" 
                            class="w-full h-auto max-h-72 object-cover rounded-xl border border-border shadow-xs"
                        >
                        <span class="text-xs text-muted-foreground mt-1.5">Foto Dokumentasi Pemantauan</span>
                    </div>

                    <!-- Informasi Waktu Pemantauan -->
                    <div class="flex items-center justify-between p-3 rounded-lg bg-muted/40 border border-border/60 text-sm">
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <Calendar class="w-4 h-4 text-primary" />
                            <span class="font-medium">Tanggal Pemantauan:</span>
                        </div>
                        <span class="font-bold text-foreground">
                            {{ formatDate(selectedItem.jadwal_piket?.tanggal_piket) }}
                        </span>
                    </div>

                    <!-- Kasus 1: Petugas Piket (Izin, Sakit, atau Alpha) -->
                    <div v-if="selectedItem.is_piket_petugas" class="space-y-4">
                        <div class="p-4 rounded-xl border border-blue-200/80 bg-blue-50/40 dark:border-blue-900/40 dark:bg-blue-950/20 space-y-3">
                            <div class="flex items-center justify-between pb-2 border-b border-blue-200/60 dark:border-blue-800/40">
                                <span class="font-semibold text-xs uppercase tracking-wider text-blue-700 dark:text-blue-400 flex items-center gap-2">
                                    <ShieldCheck class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                    Data Petugas Guru Piket
                                </span>
                                <span 
                                    class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                    :class="getStatusBadgeClass(selectedItem.status_kehadiran)"
                                >
                                    {{ selectedItem.status_kehadiran }} Piket
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-xs text-muted-foreground block">Nama Petugas:</span>
                                    <span class="font-bold text-foreground">{{ selectedItem.jadwal_piket?.guru_piket?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs text-muted-foreground block">Titik Penugasan:</span>
                                    <span class="font-medium text-foreground flex items-center gap-1">
                                        <MapPin class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                        {{ selectedItem.jadwal_piket?.titik?.nama_titik || '-' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-xs text-muted-foreground block">Jam Piket yang Ditugaskan:</span>
                                    <span class="font-medium text-foreground flex items-center gap-1">
                                        <Clock class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                        Jam ke-{{ getJamStr(selectedItem.jadwal_piket?.jam_pelajarans) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl border border-border/70 bg-card space-y-2">
                            <span class="text-xs font-semibold text-muted-foreground block">
                                Keterangan / Alasan Petugas Piket:
                            </span>
                            <div class="p-3 rounded-lg bg-muted/40 border border-border/60 text-sm whitespace-pre-wrap text-foreground">
                                {{ selectedItem.keterangan || 'Tidak ada catatan keterangan tambahan.' }}
                            </div>
                        </div>
                    </div>

                    <!-- Kasus 2: Pemantauan Kelas Normal -->
                    <template v-else>
                        <!-- Grid 2 Kolom: Guru Piket vs Guru Mengajar -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <!-- Kartu Guru Piket -->
                            <div class="p-3.5 rounded-xl border border-blue-200/80 bg-blue-50/40 dark:border-blue-900/40 dark:bg-blue-950/20 space-y-2.5">
                                <div class="flex items-center gap-2 pb-1.5 border-b border-blue-200/60 dark:border-blue-800/40">
                                    <ShieldCheck class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                    <span class="font-semibold text-xs uppercase tracking-wider text-blue-700 dark:text-blue-400">
                                        Guru Piket (Petugas)
                                    </span>
                                </div>

                                <div>
                                    <span class="text-xs text-muted-foreground block">Nama Petugas:</span>
                                    <span class="font-bold text-foreground text-sm">
                                        {{ selectedItem.jadwal_piket?.guru_piket?.name || '-' }}
                                    </span>
                                </div>

                                <div>
                                    <span class="text-xs text-muted-foreground block">Titik Penugasan:</span>
                                    <span class="font-medium text-foreground text-sm flex items-center gap-1">
                                        <MapPin class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                        {{ selectedItem.jadwal_piket?.titik?.nama_titik || '-' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Kartu Guru Mengajar -->
                            <div class="p-3.5 rounded-xl border border-emerald-200/80 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-950/20 space-y-2.5">
                                <div class="flex items-center gap-2 pb-1.5 border-b border-emerald-200/60 dark:border-emerald-800/40">
                                    <GraduationCap class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                    <span class="font-semibold text-xs uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                        Guru Mengajar (Di Kelas)
                                    </span>
                                </div>

                                <div>
                                    <span class="text-xs text-muted-foreground block">Nama Guru:</span>
                                    <span class="font-bold text-foreground text-sm">
                                        {{ selectedItem.jadwal_mapel?.guru?.name || 'Belum Ditentukan' }}
                                    </span>
                                </div>

                                <div>
                                    <span class="text-xs text-muted-foreground block">Mata Pelajaran:</span>
                                    <span class="font-medium text-foreground text-sm flex items-center gap-1">
                                        <BookOpen class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                        {{ selectedItem.jadwal_mapel?.mataPelajaran?.nama_mapel || '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Kelas & Jam Pelajaran -->
                        <div class="p-3.5 rounded-xl border border-border/70 bg-card space-y-2 text-sm">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <span class="text-xs text-muted-foreground block">Kelas & Jurusan:</span>
                                    <span class="font-semibold text-foreground">
                                        {{ selectedItem.jadwal_mapel?.kelas?.nama_kelas || '-' }}
                                        <span v-if="selectedItem.jadwal_mapel?.kelas?.jurusan" class="text-xs text-muted-foreground font-normal block">
                                            {{ selectedItem.jadwal_mapel?.kelas?.jurusan?.nama_jurusan }}
                                        </span>
                                    </span>
                                </div>

                                <div>
                                    <span class="text-xs text-muted-foreground block">Jam Pelajaran:</span>
                                    <span class="font-semibold text-foreground flex items-center gap-1">
                                        <Clock class="w-3.5 h-3.5 text-muted-foreground" />
                                        Jam ke-{{ getJamStr(selectedItem.jadwal_mapel?.jam_pelajarans) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Kehadiran & Catatan -->
                        <div class="p-3.5 rounded-xl border border-border/70 bg-card space-y-3">
                            <div class="flex items-center justify-between pb-2 border-b border-border/50">
                                <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                    Status Kehadiran Guru di Kelas:
                                </span>
                                <div class="flex items-center gap-2">
                                    <span 
                                        class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                        :class="getStatusBadgeClass(selectedItem.status_kehadiran)"
                                    >
                                        {{ selectedItem.status_kehadiran || '-' }}
                                    </span>
                                    <span 
                                        v-if="selectedItem.is_terlambat" 
                                        class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-700"
                                    >
                                        Terlambat
                                    </span>
                                </div>
                            </div>

                            <div>
                                <span class="text-xs font-semibold text-muted-foreground block mb-1">
                                    Catatan / Keterangan Piket:
                                </span>
                                <div class="p-3 rounded-lg bg-muted/40 border border-border/60 text-sm whitespace-pre-wrap text-foreground">
                                    {{ selectedItem.keterangan || 'Tidak ada catatan keterangan.' }}
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
