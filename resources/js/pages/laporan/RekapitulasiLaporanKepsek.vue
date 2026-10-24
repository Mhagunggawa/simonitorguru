<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, 
    ChevronDown, 
    ChevronUp, 
    Eye, 
    Clock, 
    Calendar, 
    Download, 
    FileSpreadsheet,
    ShieldCheck,
    GraduationCap,
    MapPin,
    BookOpen
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription
} from '@/components/ui/dialog';

const props = defineProps<{
    mode: 'gms' | 'piket';
    laporan: any;
    filters: {
        search?: string;
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

const currentMode = ref(props.mode);
const search = ref(props.filters.search || '');
const filterType = ref(props.filters.filter_type || 'minggu');
const filterSemester = ref(props.filters.filter_semester || (new Date().getMonth() >= 6 ? 'Ganjil' : 'Genap'));
const filterTahun = ref(props.filters.filter_tahun || new Date().getFullYear());
const filterBulan = ref(props.filters.filter_bulan || new Date().getMonth() + 1);
const filterMinggu = ref(props.filters.filter_minggu || 1);
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const selectedItem = ref<any>(null);
const isDetailModalOpen = ref(false);
const isExportModalOpen = ref(false);

const openDetail = (item: any) => {
    selectedItem.value = item;
    isDetailModalOpen.value = true;
};

const form = useForm({
    tipe: props.mode,
    periode: 'bulanan', // bulanan | semester
    bulan: new Date().toISOString().slice(0, 7), // YYYY-MM
    semester: 'ganjil', // ganjil | genap
    tahun_ajaran: new Date().getFullYear().toString(),
});

const submitExport = () => {
    const queryParams = new URLSearchParams(form.data() as any).toString();
    window.location.href = `/kepsek/laporan/export?${queryParams}`;
};

// Filter fetching
watch([search, filterType, filterSemester, filterTahun, startDate, endDate, currentMode], debounce(() => {
    const params: Record<string, string | number> = { mode: currentMode.value };
    if (search.value) params.search = search.value;
    
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
    
    router.get('/kepsek/laporan', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

const setMode = (mode: 'gms' | 'piket') => {
    currentMode.value = mode;
    form.tipe = mode;
};

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
    <Head title="Rekapitulasi Laporan" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-xl md:text-2xl font-bold tracking-tight">Rekapitulasi Laporan</h1>
                <p class="text-xs md:text-sm text-muted-foreground">Monitoring kehadiran absensi GMS dan Piket.</p>
            </div>
            <div class="w-full sm:w-auto">
                <Button @click="isExportModalOpen = true" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white">
                    <Download class="w-4 h-4" /> Export Berkala
                </Button>
            </div>
        </div>

        <div class="flex border-b border-border mb-2">
            <button 
                @click="setMode('gms')"
                class="flex-1 sm:flex-none text-center px-4 py-2.5 font-medium text-sm border-b-2 transition-colors"
                :class="currentMode === 'gms' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
            >
                Mode GMS
            </button>
            <button 
                @click="setMode('piket')"
                class="flex-1 sm:flex-none text-center px-4 py-2.5 font-medium text-sm border-b-2 transition-colors"
                :class="currentMode === 'piket' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
            >
                Mode Piket
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-col md:flex-row gap-3 bg-card p-3 md:p-4 rounded-xl border border-sidebar-border/70 shadow-sm dark:border-sidebar-border mb-4">
            <div class="relative w-full md:w-1/3 lg:w-1/4">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search" 
                    :placeholder="currentMode === 'gms' ? 'Cari nama guru GMS...' : 'Cari guru, mapel, kelas...'" 
                    class="pl-8 w-full bg-background text-sm" 
                />
            </div>
            
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2 flex-1">
                <select v-model="filterType" class="col-span-2 sm:col-span-1 h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <option value="semua">Semua Data</option>
                    <option value="minggu">Per Minggu</option>
                    <option value="bulan">Per Bulan</option>
                    <option value="semester">Per Semester</option>
                    <option value="tahun">Per Tahun</option>
                    <option value="kustom">Kustom</option>
                </select>

                <template v-if="filterType === 'semester'">
                    <select v-model="filterSemester" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option value="Ganjil">Semester Ganjil</option>
                        <option value="Genap">Semester Genap</option>
                    </select>
                </template>

                <template v-if="['minggu', 'bulan'].includes(filterType)">
                    <select v-model="filterBulan" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </template>

                <template v-if="filterType === 'minggu'">
                    <select v-model="filterMinggu" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option value="1">Minggu ke-1</option>
                        <option value="2">Minggu ke-2</option>
                        <option value="3">Minggu ke-3</option>
                        <option value="4">Minggu ke-4</option>
                        <option value="5">Minggu ke-5</option>
                        <option value="6">Minggu ke-6</option>
                    </select>
                </template>

                <template v-if="['semester', 'tahun', 'minggu', 'bulan'].includes(filterType)">
                    <select v-model="filterTahun" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </template>

                <template v-if="filterType === 'kustom'">
                    <div class="col-span-2 flex items-center gap-1.5 w-full sm:w-auto">
                        <Input type="date" v-model="startDate" title="Tanggal Mulai" class="flex-1 sm:w-32 bg-background text-xs md:text-sm" />
                        <span class="text-muted-foreground text-xs">-</span>
                        <Input type="date" v-model="endDate" title="Tanggal Sampai" class="flex-1 sm:w-32 bg-background text-xs md:text-sm" />
                    </div>
                </template>
            </div>
        </div>

        <!-- Data Content -->
        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm dark:border-sidebar-border overflow-hidden">
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                        <tr v-if="currentMode === 'gms'">
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Nama Guru</th>
                            <th class="px-4 py-3 font-medium">Waktu Absen</th>
                            <th class="px-4 py-3 font-medium text-right">Aksi</th>
                        </tr>
                        <tr v-else>
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Guru Piket (Petugas)</th>
                            <th class="px-4 py-3 font-medium">Guru Mengajar (Di Kelas)</th>
                            <th class="px-4 py-3 font-medium">Kelas & Jam</th>
                            <th class="px-4 py-3 font-medium">Status Kehadiran</th>
                            <th class="px-4 py-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                        <!-- Mode GMS -->
                        <template v-if="currentMode === 'gms'">
                            <tr v-for="item in laporan.data" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 text-foreground font-medium">
                                        <Calendar class="w-3.5 h-3.5 text-muted-foreground" />
                                        {{ formatDate(item.tanggal) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-medium text-foreground">{{ item.user?.name || '-' }}</td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    <div class="flex items-center gap-1.5">
                                        <Clock class="w-4 h-4" />
                                        {{ item.waktu_absen }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button variant="outline" size="sm" @click="openDetail(item)" class="h-8 shadow-xs">
                                        <Eye class="w-3.5 h-3.5 mr-1" /> Detail
                                    </Button>
                                </td>
                            </tr>
                        </template>
                        
                        <!-- Mode Piket -->
                        <template v-else>
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
                                    <div class="flex flex-col">
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
                                    <div class="flex flex-col">
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
                        </template>

                        <tr v-if="laporan.data.length === 0">
                            <td :colspan="currentMode === 'gms' ? 4 : 6" class="px-4 py-8 text-center text-muted-foreground italic">
                                Tidak ada data laporan yang ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card List View -->
            <div class="block md:hidden divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                <template v-if="currentMode === 'gms'">
                    <div 
                        v-for="item in laporan.data" 
                        :key="'mob-gms-' + item.id" 
                        class="p-4 space-y-3 bg-card hover:bg-muted/30 transition-colors"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-medium">
                                <Calendar class="w-3.5 h-3.5 text-primary" />
                                {{ formatDate(item.tanggal) }}
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                                <Clock class="w-3 h-3" />
                                {{ item.waktu_absen }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm shrink-0">
                                {{ (item.user?.name || 'G').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-sm text-foreground truncate">
                                    {{ item.user?.name || '-' }}
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    Absensi Guru Mata Sekolah (GMS)
                                </p>
                            </div>
                        </div>

                        <Button 
                            variant="outline" 
                            size="sm" 
                            @click="openDetail(item)" 
                            class="w-full h-9 flex items-center justify-center gap-1.5 text-xs font-medium shadow-xs"
                        >
                            <Eye class="w-3.5 h-3.5 text-primary" /> Lihat Detail Laporan
                        </Button>
                    </div>
                </template>

                <template v-else>
                    <div 
                        v-for="item in laporan.data" 
                        :key="'mob-piket-' + item.id" 
                        class="p-4 space-y-3 bg-card hover:bg-muted/30 transition-colors"
                    >
                        <!-- Header Bar: Date + Status Badges -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-foreground">
                                <Calendar class="w-3.5 h-3.5 text-primary" />
                                {{ formatDate(item.jadwal_piket?.tanggal_piket) }}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span 
                                    class="px-2 py-0.5 rounded-full text-[11px] font-bold border"
                                    :class="getStatusBadgeClass(item.status_kehadiran)"
                                >
                                    {{ item.status_kehadiran || '-' }}
                                </span>
                                <span 
                                    v-if="item.is_terlambat" 
                                    class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-700"
                                >
                                    Terlambat
                                </span>
                            </div>
                        </div>

                        <!-- Guru Mengajar & Mapel -->
                        <div class="p-2.5 rounded-lg bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30">
                            <div class="flex items-start gap-2">
                                <GraduationCap class="w-4 h-4 text-emerald-600 dark:text-emerald-400 mt-0.5 shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 block">Guru Mengajar di Kelas</span>
                                    <p class="font-semibold text-xs text-foreground truncate">
                                        {{ item.jadwal_mapel?.guru?.name || 'Belum Ditentukan' }}
                                    </p>
                                    <p class="text-[11px] text-muted-foreground truncate flex items-center gap-1 mt-0.5">
                                        <BookOpen class="w-3 h-3" />
                                        {{ item.jadwal_mapel?.mataPelajaran?.nama_mapel || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 2-column: Kelas & Jam + Guru Piket -->
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2 rounded-lg bg-muted/40 border border-border/50">
                                <span class="text-[10px] text-muted-foreground block font-medium">Kelas & Jam:</span>
                                <p class="font-semibold text-foreground truncate mt-0.5">
                                    {{ item.jadwal_mapel?.kelas?.nama_kelas || '-' }}
                                </p>
                                <span class="text-[10px] text-muted-foreground flex items-center gap-1 mt-0.5">
                                    <Clock class="w-3 h-3 text-muted-foreground" />
                                    Jam ke-{{ getJamStr(item.jadwal_mapel?.jam_pelajarans) }}
                                </span>
                            </div>

                            <div class="p-2 rounded-lg bg-blue-50/40 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30">
                                <span class="text-[10px] text-blue-700 dark:text-blue-400 block font-medium">Petugas Piket:</span>
                                <p class="font-semibold text-foreground truncate mt-0.5">
                                    {{ item.jadwal_piket?.guru_piket?.name || '-' }}
                                </p>
                                <span class="text-[10px] text-muted-foreground flex items-center gap-1 mt-0.5 truncate">
                                    <MapPin class="w-3 h-3 text-blue-600 dark:text-blue-400 shrink-0" />
                                    {{ item.jadwal_piket?.titik?.nama_titik || '-' }}
                                </span>
                            </div>
                        </div>

                        <Button 
                            variant="outline" 
                            size="sm" 
                            @click="openDetail(item)" 
                            class="w-full h-9 flex items-center justify-center gap-1.5 text-xs font-medium shadow-xs"
                        >
                            <Eye class="w-3.5 h-3.5 text-primary" /> Lihat Detail Monitoring
                        </Button>
                    </div>
                </template>

                <div v-if="laporan.data.length === 0" class="p-8 text-center text-sm text-muted-foreground italic">
                    Tidak ada data laporan yang ditemukan.
                </div>
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
        <Dialog :open="isDetailModalOpen" @update:open="isDetailModalOpen = $event">
            <DialogContent class="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold">
                        Detail Laporan {{ currentMode === 'gms' ? 'GMS' : 'Monitoring Piket' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ currentMode === 'gms' ? 'Informasi detail absensi Guru Mata Sekolah.' : 'Informasi pemantauan kehadiran kelas oleh guru piket.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2" v-if="selectedItem">
                    <!-- Foto Bukti / Dokumentasi -->
                    <div class="flex flex-col items-center justify-center" v-if="selectedItem.foto_bukti || selectedItem.foto_dokumentasi">
                        <img 
                            :src="`/storage/${selectedItem.foto_bukti || selectedItem.foto_dokumentasi}`" 
                            alt="Foto Bukti" 
                            class="w-full h-auto max-h-72 object-cover rounded-xl border border-border shadow-xs"
                        >
                        <span class="text-xs text-muted-foreground mt-1.5">
                            {{ currentMode === 'gms' ? 'Foto Bukti Absensi GMS' : 'Foto Dokumentasi Pemantauan Piket' }}
                        </span>
                    </div>
                    
                    <!-- Content Mode GMS -->
                    <template v-if="currentMode === 'gms'">
                        <div class="grid grid-cols-3 gap-2 text-sm border-b border-border/50 pb-2">
                            <span class="text-muted-foreground col-span-1">Nama Guru</span>
                            <span class="font-medium text-foreground col-span-2">{{ selectedItem.user?.name || '-' }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-sm border-b border-border/50 pb-2">
                            <span class="text-muted-foreground col-span-1">Tanggal</span>
                            <span class="font-medium text-foreground col-span-2">{{ formatDate(selectedItem.tanggal) }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-sm border-b border-border/50 pb-2">
                            <span class="text-muted-foreground col-span-1">Waktu Absen</span>
                            <span class="font-medium text-foreground col-span-2 flex items-center gap-1.5">
                                <Clock class="w-3.5 h-3.5 text-muted-foreground" />
                                {{ selectedItem.waktu_absen }}
                            </span>
                        </div>
                    </template>

                    <!-- Content Mode Piket -->
                    <template v-else>
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

        <!-- Export Modal -->
        <Dialog :open="isExportModalOpen" @update:open="isExportModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Unduh Rekapitulasi Laporan</DialogTitle>
                    <DialogDescription>Pilih parameter laporan yang ingin diunduh (Excel).</DialogDescription>
                </DialogHeader>
                
                <form @submit.prevent="submitExport" class="space-y-6 py-4">
                    <div class="space-y-2">
                        <Label>Tipe Laporan</Label>
                        <select v-model="form.tipe" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <option value="gms">Absensi Guru Mata Sekolah (GMS)</option>
                            <option value="piket">Kehadiran Guru Piket</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label>Periode</Label>
                        <select v-model="form.periode" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <option value="bulanan">Bulanan</option>
                            <option value="semester">Per Semester</option>
                        </select>
                    </div>

                    <div v-if="form.periode === 'bulanan'" class="space-y-2">
                        <Label>Pilih Bulan</Label>
                        <Input type="month" v-model="form.bulan" required />
                    </div>

                    <div v-if="form.periode === 'semester'" class="space-y-4">
                        <div class="space-y-2">
                            <Label>Pilih Semester</Label>
                            <select v-model="form.semester" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="ganjil">Ganjil</option>
                                <option value="genap">Genap</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label>Tahun Ajaran (Tahun Mulai)</Label>
                            <Input type="number" v-model="form.tahun_ajaran" required placeholder="Contoh: 2026" min="2000" max="2100" />
                        </div>
                    </div>

                    <Button type="submit" class="w-full flex gap-2">
                        <FileSpreadsheet class="w-4 h-4" />
                        Unduh Rekap Excel (.xlsx)
                    </Button>
                </form>
            </DialogContent>
        </Dialog>

    </div>
</template>
