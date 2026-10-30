<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, 
    Plus, 
    Edit, 
    Trash2, 
    Calendar, 
    Clock, 
    MapPin, 
    User as UserIcon,
    ChevronLeft, 
    ChevronRight,
    RotateCcw,
    CalendarDays
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Swal from 'sweetalert2';

interface JamPelajaranItem {
    id: number;
    urutan_jam: number;
    waktu_mulai: string;
    waktu_selesai: string;
}

interface JadwalPiketItem {
    id: number;
    tanggal_piket: string;
    titik_id: number;
    guru_piket_id: number;
    titik?: { id: number; nama_titik: string };
    guru_piket?: { id: number; name: string };
    jam_pelajarans?: JamPelajaranItem[];
}

const props = defineProps<{
    jadwalPikets: {
        data: JadwalPiketItem[];
        links: any[];
        total: number;
        from: number;
        to: number;
        current_page: number;
        last_page: number;
    };
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
    activePeriodInfo?: {
        start_date: string;
        end_date: string;
        start_formatted: string;
        end_formatted: string;
        range_label: string;
        short_label: string;
    } | null;
    currentWeekInfo?: {
        tahun: number;
        bulan: number;
        minggu: number;
    };
    totalPiketAll?: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Jadwal Piket',
                href: '/jadwal-piket',
            },
        ],
    },
});

const search = ref(props.filters?.search || '');
const filterType = ref(props.filters?.filter_type || 'minggu');
const filterSemester = ref(props.filters?.filter_semester || (new Date().getMonth() >= 6 ? 'Ganjil' : 'Genap'));
const filterTahun = ref(Number(props.filters?.filter_tahun) || new Date().getFullYear());
const filterBulan = ref(Number(props.filters?.filter_bulan) || (new Date().getMonth() + 1));
const filterMinggu = ref(Number(props.filters?.filter_minggu) || 1);
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const bulanNames = [
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

const isCurrentWeekActive = computed(() => {
    if (filterType.value !== 'minggu' || !props.currentWeekInfo) return false;
    return filterTahun.value === props.currentWeekInfo.tahun &&
           filterBulan.value === props.currentWeekInfo.bulan &&
           filterMinggu.value === props.currentWeekInfo.minggu;
});

const applyFilters = () => {
    const params: Record<string, string | number> = {};
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
    
    router.get('/jadwal-piket', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch([search, filterType, filterSemester, filterTahun, filterBulan, filterMinggu, startDate, endDate], debounce(applyFilters, 300));

const prevWeek = () => {
    if (filterType.value !== 'minggu') {
        filterType.value = 'minggu';
    }
    if (filterMinggu.value > 1) {
        filterMinggu.value--;
    } else {
        if (filterBulan.value > 1) {
            filterBulan.value--;
        } else {
            filterBulan.value = 12;
            filterTahun.value--;
        }
        filterMinggu.value = 5;
    }
};

const nextWeek = () => {
    if (filterType.value !== 'minggu') {
        filterType.value = 'minggu';
    }
    if (filterMinggu.value < 5) {
        filterMinggu.value++;
    } else {
        if (filterBulan.value < 12) {
            filterBulan.value++;
        } else {
            filterBulan.value = 1;
            filterTahun.value++;
        }
        filterMinggu.value = 1;
    }
};

const goToCurrentWeek = () => {
    filterType.value = 'minggu';
    if (props.currentWeekInfo) {
        filterTahun.value = props.currentWeekInfo.tahun;
        filterBulan.value = props.currentWeekInfo.bulan;
        filterMinggu.value = props.currentWeekInfo.minggu;
    } else {
        const now = new Date();
        filterTahun.value = now.getFullYear();
        filterBulan.value = now.getMonth() + 1;
        filterMinggu.value = 1;
    }
};

const resetFilter = () => {
    search.value = '';
    goToCurrentWeek();
};

const deleteJadwal = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus jadwal piket ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/jadwal-piket/${id}`, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Terhapus!',
                        text: 'Jadwal piket berhasil dihapus.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                onError: () => {
                    Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus jadwal piket.', 'error');
                }
            });
        }
    });
};

const formatDayName = (dateStr: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { weekday: 'long' }).format(date);
};

const formatDateOnly = (dateStr: string) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    }).format(date);
};

const formatTime = (time: string) => time ? time.substring(0, 5) : '';

const getDayBadgeClass = (dayName: string) => {
    switch (dayName.toLowerCase()) {
        case 'senin':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'selasa':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        case 'rabu':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800';
        case 'kamis':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'jumat':
            return 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 border-teal-200 dark:border-teal-800';
        case 'sabtu':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
};
</script>

<template>
    <Head title="Jadwal Piket" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Jadwal Piket</h1>
                <p class="text-sm text-muted-foreground">
                    Manajemen dan monitoring pembagian tugas guru piket secara mingguan.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button as-child class="shadow-sm">
                    <Link href="/jadwal-piket/create">
                        <Plus class="mr-2 h-4 w-4" /> Tambah Jadwal Piket
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Filter & Search Toolbar (mirrors Laporan Piket) -->
        <div class="flex flex-col lg:flex-row gap-3 bg-card p-4 rounded-xl border border-sidebar-border/70 shadow-sm dark:border-sidebar-border">
            <div class="relative w-full lg:w-1/3">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search" 
                    placeholder="Cari guru piket atau titik lokasi..." 
                    class="pl-8 w-full bg-background" 
                />
            </div>
            
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Filter Type -->
                <select 
                    v-model="filterType" 
                    class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring font-medium"
                >
                    <option value="minggu">Per Minggu (Aktif)</option>
                    <option value="bulan">Per Bulan</option>
                    <option value="semester">Per Semester</option>
                    <option value="tahun">Per Tahun</option>
                    <option value="semua">Semua Data</option>
                    <option value="kustom">Kustom Tanggal</option>
                </select>

                <!-- Month Selector -->
                <template v-if="['minggu', 'bulan'].includes(filterType)">
                    <select 
                        v-model="filterBulan" 
                        class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option v-for="b in bulanNames" :key="b.value" :value="b.value">{{ b.label }}</option>
                    </select>
                </template>

                <!-- Week Selector -->
                <template v-if="filterType === 'minggu'">
                    <select 
                        v-model="filterMinggu" 
                        class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring font-semibold text-primary"
                    >
                        <option :value="1">Minggu ke-1</option>
                        <option :value="2">Minggu ke-2</option>
                        <option :value="3">Minggu ke-3</option>
                        <option :value="4">Minggu ke-4</option>
                        <option :value="5">Minggu ke-5</option>
                        <option :value="6">Minggu ke-6</option>
                    </select>
                </template>

                <!-- Semester Selector -->
                <template v-if="filterType === 'semester'">
                    <select 
                        v-model="filterSemester" 
                        class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option value="Ganjil">Semester Ganjil</option>
                        <option value="Genap">Semester Genap</option>
                    </select>
                </template>

                <!-- Year Selector -->
                <template v-if="['semester', 'tahun', 'minggu', 'bulan'].includes(filterType)">
                    <select 
                        v-model="filterTahun" 
                        class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </template>

                <!-- Custom Date Range -->
                <template v-if="filterType === 'kustom'">
                    <Input type="date" v-model="startDate" title="Tanggal Mulai" class="w-36 bg-background text-sm" />
                    <span class="text-muted-foreground text-sm font-bold">-</span>
                    <Input type="date" v-model="endDate" title="Tanggal Sampai" class="w-36 bg-background text-sm" />
                </template>

                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="resetFilter" 
                    title="Reset ke Minggu Ini"
                    class="h-10 text-xs px-2.5 ml-auto sm:ml-0"
                >
                    <RotateCcw class="h-3.5 w-3.5 mr-1" /> Reset
                </Button>
            </div>
        </div>

        <!-- Weekly Quick Navigator Strip -->
        <div 
            v-if="filterType === 'minggu'" 
            class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-gradient-to-r from-primary/5 via-accent/10 to-primary/5 border border-primary/20 dark:border-primary/30 p-3.5 rounded-xl shadow-xs"
        >
            <div class="flex items-center gap-2">
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="prevWeek" 
                    class="h-9 px-3 bg-background hover:bg-muted"
                >
                    <ChevronLeft class="h-4 w-4 mr-1" /> Minggu Lalu
                </Button>
                
                <Button 
                    :variant="isCurrentWeekActive ? 'default' : 'outline'" 
                    size="sm" 
                    @click="goToCurrentWeek" 
                    class="h-9 px-3 font-medium transition-all"
                >
                    <CalendarDays class="h-4 w-4 mr-1.5" /> Minggu Ini
                    <span v-if="isCurrentWeekActive" class="ml-1.5 inline-block w-2 h-2 rounded-full bg-white animate-pulse"></span>
                </Button>
                
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="nextWeek" 
                    class="h-9 px-3 bg-background hover:bg-muted"
                >
                    Minggu Depan <ChevronRight class="h-4 w-4 ml-1" />
                </Button>
            </div>

            <div class="flex items-center gap-3 text-sm">
                <div v-if="activePeriodInfo" class="flex items-center gap-1.5 font-medium text-foreground bg-background/80 px-3 py-1.5 rounded-lg border shadow-xs">
                    <Calendar class="h-4 w-4 text-primary" />
                    <span>Rentang:</span>
                    <span class="font-bold text-primary">{{ activePeriodInfo.short_label }}</span>
                </div>
                <div class="text-xs text-muted-foreground hidden md:inline-block">
                    Total: <strong class="text-foreground">{{ jadwalPikets.total ?? jadwalPikets.data.length }}</strong> jadwal
                </div>
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/60 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-semibold w-12 text-center">No</th>
                        <th class="px-4 py-3 font-semibold">Hari & Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Jam Piket</th>
                        <th class="px-4 py-3 font-semibold">Titik Lokasi</th>
                        <th class="px-4 py-3 font-semibold">Guru Piket</th>
                        <th class="px-4 py-3 font-semibold text-right w-[120px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr 
                        v-for="(item, index) in jadwalPikets.data" 
                        :key="item.id" 
                        class="hover:bg-muted/40 transition-colors"
                    >
                        <td class="px-4 py-3.5 text-center text-xs text-muted-foreground font-mono">
                            {{ (jadwalPikets.from || 1) + index }}
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span 
                                    class="text-xs font-bold px-2 py-0.5 rounded-md border uppercase tracking-wider"
                                    :class="getDayBadgeClass(formatDayName(item.tanggal_piket))"
                                >
                                    {{ formatDayName(item.tanggal_piket) }}
                                </span>
                                <span class="font-medium text-foreground text-sm">
                                    {{ formatDateOnly(item.tanggal_piket) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <div v-if="item.jam_pelajarans && item.jam_pelajarans.length" class="flex flex-col gap-0.5">
                                <div class="inline-flex items-center gap-1 font-semibold text-foreground">
                                    <Clock class="h-3.5 w-3.5 text-primary" />
                                    <span>Jam ke-{{ item.jam_pelajarans.map((j) => j.urutan_jam).join(', ') }}</span>
                                </div>
                                <span class="text-xs text-muted-foreground font-mono">
                                    {{ formatTime(item.jam_pelajarans[0].waktu_mulai) }} - {{ formatTime(item.jam_pelajarans[item.jam_pelajarans.length - 1].waktu_selesai) }} WIB
                                </span>
                            </div>
                            <span v-else class="text-muted-foreground">-</span>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="inline-flex items-center gap-1.5 font-medium text-foreground">
                                <MapPin class="h-3.5 w-3.5 text-rose-500 shrink-0" />
                                <span>{{ item.titik?.nama_titik || '-' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="inline-flex items-center gap-2 font-medium text-foreground">
                                <div class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                    <UserIcon class="h-3.5 w-3.5" />
                                </div>
                                <span>{{ item.guru_piket?.name || '-' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <div class="flex justify-end gap-1.5">
                                <Button variant="outline" size="icon" as-child class="h-8 w-8 hover:bg-primary/10 hover:text-primary transition-colors" title="Edit Jadwal">
                                    <Link :href="`/jadwal-piket/${item.id}/edit`">
                                        <Edit class="h-3.5 w-3.5" />
                                    </Link>
                                </Button>
                                <Button variant="destructive" size="icon" @click="deleteJadwal(item.id)" class="h-8 w-8" title="Hapus Jadwal">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="jadwalPikets.data.length === 0">
                        <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <Calendar class="h-10 w-10 text-muted-foreground/40" />
                                <div class="text-base font-semibold text-foreground">Tidak Ada Jadwal Piket</div>
                                <p class="text-xs max-w-sm text-muted-foreground">
                                    Tidak ditemukan jadwal piket untuk filter/periode ini. Anda dapat menambahkan jadwal piket baru atau mengubah filter periode.
                                </p>
                                <Button as-child size="sm" class="mt-2">
                                    <Link href="/jadwal-piket/create">
                                        <Plus class="mr-1.5 h-3.5 w-3.5" /> Tambah Jadwal Baru
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden flex flex-col gap-3">
            <div 
                v-for="item in jadwalPikets.data" 
                :key="item.id" 
                class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow-xs p-4 flex flex-col gap-3"
            >
                <div class="flex items-center justify-between border-b pb-2.5">
                    <div class="flex items-center gap-2">
                        <span 
                            class="text-xs font-bold px-2 py-0.5 rounded-md border uppercase"
                            :class="getDayBadgeClass(formatDayName(item.tanggal_piket))"
                        >
                            {{ formatDayName(item.tanggal_piket) }}
                        </span>
                        <span class="font-bold text-sm text-foreground">
                            {{ formatDateOnly(item.tanggal_piket) }}
                        </span>
                    </div>
                    <div class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground bg-muted px-2 py-0.5 rounded">
                        <MapPin class="h-3 w-3 text-rose-500" />
                        <span>{{ item.titik?.nama_titik || '-' }}</span>
                    </div>
                </div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="text-xs text-muted-foreground font-medium">Guru Piket:</span>
                    <span class="font-semibold text-foreground text-right">{{ item.guru_piket?.name || '-' }}</span>
                </div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="text-xs text-muted-foreground font-medium">Jam Pelajaran:</span>
                    <div class="text-right">
                        <div v-if="item.jam_pelajarans && item.jam_pelajarans.length">
                            <span class="font-semibold text-primary">Jam ke-{{ item.jam_pelajarans.map((j) => j.urutan_jam).join(', ') }}</span>
                            <span class="text-xs text-muted-foreground block">
                                ({{ formatTime(item.jam_pelajarans[0].waktu_mulai) }} - {{ formatTime(item.jam_pelajarans[item.jam_pelajarans.length - 1].waktu_selesai) }})
                            </span>
                        </div>
                        <span v-else class="text-muted-foreground">-</span>
                    </div>
                </div>
                
                <div class="flex justify-end gap-2 mt-1 pt-3 border-t">
                    <Button variant="outline" size="sm" as-child class="h-8">
                        <Link :href="`/jadwal-piket/${item.id}/edit`">
                            <Edit class="h-3.5 w-3.5 mr-1.5" /> Edit
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteJadwal(item.id)" class="h-8">
                        <Trash2 class="h-3.5 w-3.5 mr-1.5" /> Hapus
                    </Button>
                </div>
            </div>
            
            <div v-if="jadwalPikets.data.length === 0" class="text-center p-8 border border-sidebar-border/70 rounded-xl bg-card text-muted-foreground shadow-xs">
                <Calendar class="h-8 w-8 mx-auto text-muted-foreground/40 mb-2" />
                <div class="font-semibold text-foreground text-sm">Tidak Ada Jadwal Piket</div>
                <p class="text-xs text-muted-foreground mt-1">Tidak ditemukan jadwal untuk periode ini.</p>
                <Button as-child size="sm" class="mt-3">
                    <Link href="/jadwal-piket/create">
                        <Plus class="mr-1.5 h-3.5 w-3.5" /> Tambah Jadwal
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="jadwalPikets.links && jadwalPikets.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-2">
            <template v-for="(link, key) in jadwalPikets.links" :key="key">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3.5 py-1.5 border rounded-md text-xs font-medium transition-colors"
                    :class="[
                        link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-background hover:bg-muted text-foreground'
                    ]"
                    v-html="link.label"
                    preserve-scroll
                />
                <span 
                    v-else
                    class="px-3.5 py-1.5 border rounded-md text-xs font-medium text-muted-foreground bg-muted/50"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>