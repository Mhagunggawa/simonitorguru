<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';
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
    pengaturan?: {
        waktu_mulai: string;
        waktu_selesai: string;
        keterangan?: string | null;
    };
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

const isPengaturanOpen = ref(false);
const formPengaturan = useForm({
    waktu_mulai: props.pengaturan?.waktu_mulai || '06:30',
    waktu_selesai: props.pengaturan?.waktu_selesai || '16:00',
    keterangan: props.pengaturan?.keterangan || '',
});

const openPengaturanModal = () => {
    formPengaturan.waktu_mulai = props.pengaturan?.waktu_mulai || '06:30';
    formPengaturan.waktu_selesai = props.pengaturan?.waktu_selesai || '16:00';
    formPengaturan.keterangan = props.pengaturan?.keterangan || '';
    isPengaturanOpen.value = true;
};

const submitPengaturan = () => {
    formPengaturan.post('/jadwal-piket/pengaturan', {
        preserveScroll: true,
        onSuccess: () => {
            isPengaturanOpen.value = false;
            Swal.fire({
                title: 'Berhasil!',
                text: 'Pengaturan batas waktu pelaporan piket berhasil diperbarui.',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors);
            const errorMsg = errorMessages.length > 0 ? errorMessages[0] : 'Terjadi kesalahan validasi pengaturan waktu.';
            Swal.fire({
                title: 'Gagal Menyimpan!',
                text: errorMsg as string,
                icon: 'error'
            });
        }
    });
};

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

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-3 sm:p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Jadwal Piket</h1>
                <p class="text-xs sm:text-sm text-muted-foreground">
                    Manajemen dan monitoring pembagian tugas guru piket secara mingguan.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" class="gap-2 shadow-xs cursor-pointer border-emerald-300 dark:border-emerald-700 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60" @click="openPengaturanModal">
                    <Clock class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    <span class="hidden sm:inline">Pengaturan Batas Waktu</span>
                    <span class="sm:hidden">Batas Waktu</span>
                </Button>
                <Button as-child class="w-full sm:w-auto shadow-sm">
                    <Link href="/jadwal-piket/create">
                        <Plus class="mr-2 h-4 w-4" /> Tambah Jadwal Piket
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Filter & Search Toolbar (mirrors Laporan Piket) -->
        <div class="flex flex-col lg:flex-row gap-3 bg-card p-3.5 sm:p-4 rounded-xl border border-sidebar-border/70 shadow-sm dark:border-sidebar-border">
            <div class="relative w-full lg:w-72 xl:w-80 shrink-0">
                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground pointer-events-none" />
                <Input 
                    v-model="search" 
                    placeholder="Cari guru piket atau titik lokasi..." 
                    class="pl-9 h-10 w-full bg-background text-sm" 
                />
            </div>
            
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2 flex-1 w-full">
                <!-- Filter Type -->
                <select 
                    v-model="filterType" 
                    class="col-span-2 sm:w-auto w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring font-medium"
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
                        class="col-span-1 sm:w-auto w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option v-for="b in bulanNames" :key="b.value" :value="b.value">{{ b.label }}</option>
                    </select>
                </template>

                <!-- Week Selector -->
                <template v-if="filterType === 'minggu'">
                    <select 
                        v-model="filterMinggu" 
                        class="col-span-1 sm:w-auto w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring font-semibold text-primary"
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
                        class="col-span-1 sm:w-auto w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option value="Ganjil">Semester Ganjil</option>
                        <option value="Genap">Semester Genap</option>
                    </select>
                </template>

                <!-- Year Selector -->
                <template v-if="['semester', 'tahun', 'minggu', 'bulan'].includes(filterType)">
                    <select 
                        v-model="filterTahun" 
                        class="col-span-1 sm:w-auto w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option v-for="y in available_years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </template>

                <!-- Custom Date Range -->
                <template v-if="filterType === 'kustom'">
                    <div class="col-span-2 sm:col-span-auto flex items-center gap-1.5 w-full sm:w-auto">
                        <Input type="date" v-model="startDate" title="Tanggal Mulai" class="flex-1 sm:w-36 bg-background text-sm h-10" />
                        <span class="text-muted-foreground text-sm font-bold">-</span>
                        <Input type="date" v-model="endDate" title="Tanggal Sampai" class="flex-1 sm:w-36 bg-background text-sm h-10" />
                    </div>
                </template>

                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="resetFilter" 
                    title="Reset ke Minggu Ini"
                    class="col-span-1 sm:col-span-auto h-10 text-xs px-2.5 sm:ml-auto cursor-pointer"
                >
                    <RotateCcw class="h-3.5 w-3.5 mr-1" /> Reset
                </Button>
            </div>
        </div>

        <!-- Weekly Quick Navigator Strip -->
        <div 
            v-if="filterType === 'minggu'" 
            class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-gradient-to-r from-primary/5 via-accent/10 to-primary/5 border border-primary/20 dark:border-primary/30 p-3 sm:p-3.5 rounded-xl shadow-xs"
        >
            <div class="grid grid-cols-3 sm:flex items-center gap-1.5 sm:gap-2 w-full sm:w-auto">
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="prevWeek" 
                    class="h-9 px-2 sm:px-3 text-xs bg-background hover:bg-muted justify-center cursor-pointer"
                    title="Pindah ke Minggu Lalu"
                >
                    <ChevronLeft class="h-4 w-4 mr-0.5 sm:mr-1 shrink-0" />
                    <span class="truncate">Minggu Lalu</span>
                </Button>
                
                <Button 
                    :variant="isCurrentWeekActive ? 'default' : 'outline'" 
                    size="sm" 
                    @click="goToCurrentWeek" 
                    class="h-9 px-2 sm:px-3 text-xs font-semibold transition-all justify-center cursor-pointer"
                    title="Kembali ke Minggu Ini"
                >
                    <CalendarDays class="h-4 w-4 mr-1 shrink-0" />
                    <span class="truncate">Minggu Ini</span>
                    <span v-if="isCurrentWeekActive" class="ml-1 hidden xs:inline-block w-2 h-2 rounded-full bg-white animate-pulse"></span>
                </Button>
                
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="nextWeek" 
                    class="h-9 px-2 sm:px-3 text-xs bg-background hover:bg-muted justify-center cursor-pointer"
                    title="Pindah ke Minggu Depan"
                >
                    <span class="truncate">Minggu Depan</span>
                    <ChevronRight class="h-4 w-4 ml-0.5 sm:ml-1 shrink-0" />
                </Button>
            </div>

            <div class="flex items-center justify-between sm:justify-end gap-2 sm:gap-3 text-xs sm:text-sm pt-2 sm:pt-0 border-t sm:border-t-0 border-primary/10">
                <div v-if="activePeriodInfo" class="flex items-center gap-1.5 font-medium text-foreground bg-background/80 px-2.5 py-1.5 rounded-lg border shadow-xs text-xs sm:text-sm">
                    <Calendar class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-primary shrink-0" />
                    <span class="text-muted-foreground hidden xs:inline">Rentang:</span>
                    <span class="font-bold text-primary">{{ activePeriodInfo.short_label }}</span>
                </div>
                <div class="text-xs text-muted-foreground">
                    Total: <strong class="text-foreground font-semibold">{{ jadwalPikets.total ?? jadwalPikets.data.length }}</strong> jadwal
                </div>
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm dark:border-sidebar-border overflow-hidden">
            <div class="overflow-x-auto">
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
                                    <Button variant="outline" size="icon" as-child class="h-8 w-8 hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" title="Edit Jadwal">
                                        <Link :href="`/jadwal-piket/${item.id}/edit`">
                                            <Edit class="h-3.5 w-3.5" />
                                        </Link>
                                    </Button>
                                    <Button variant="destructive" size="icon" @click="deleteJadwal(item.id)" class="h-8 w-8 cursor-pointer" title="Hapus Jadwal">
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
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden flex flex-col gap-3">
            <div 
                v-for="(item, index) in jadwalPikets.data" 
                :key="item.id" 
                class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow-xs p-3.5 sm:p-4 flex flex-col gap-3 transition-all hover:border-primary/40"
            >
                <!-- Card Top: No, Day Badge, Date, and Location -->
                <div class="flex items-start justify-between gap-2 border-b border-border/60 pb-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-mono font-semibold text-muted-foreground bg-muted px-1.5 py-0.5 rounded">
                            #{{ (jadwalPikets.from || 1) + index }}
                        </span>
                        <span 
                            class="text-xs font-bold px-2 py-0.5 rounded-md border uppercase tracking-wide"
                            :class="getDayBadgeClass(formatDayName(item.tanggal_piket))"
                        >
                            {{ formatDayName(item.tanggal_piket) }}
                        </span>
                        <span class="font-bold text-sm text-foreground">
                            {{ formatDateOnly(item.tanggal_piket) }}
                        </span>
                    </div>
                    <div class="inline-flex items-center gap-1 text-xs font-semibold text-foreground bg-muted/80 dark:bg-muted/40 px-2 py-1 rounded-md shrink-0 border border-border/50">
                        <MapPin class="h-3.5 w-3.5 text-rose-500 shrink-0" />
                        <span class="max-w-[120px] sm:max-w-none truncate">{{ item.titik?.nama_titik || '-' }}</span>
                    </div>
                </div>
                
                <!-- Guru Piket -->
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-xs text-muted-foreground font-medium shrink-0">Guru Piket:</span>
                    <div class="flex items-center gap-1.5 text-right font-semibold text-foreground truncate ml-2">
                        <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <UserIcon class="h-3 w-3" />
                        </div>
                        <span class="truncate">{{ item.guru_piket?.name || '-' }}</span>
                    </div>
                </div>
                
                <!-- Jam Pelajaran -->
                <div class="flex items-start justify-between gap-3 text-sm bg-muted/30 p-2.5 rounded-lg border border-border/40">
                    <span class="text-xs text-muted-foreground font-medium shrink-0 mt-0.5">Jam Piket:</span>
                    <div class="text-right">
                        <div v-if="item.jam_pelajarans && item.jam_pelajarans.length">
                            <div class="inline-flex items-center gap-1 font-bold text-primary text-xs sm:text-sm">
                                <Clock class="h-3.5 w-3.5" />
                                <span>Jam ke-{{ item.jam_pelajarans.map((j) => j.urutan_jam).join(', ') }}</span>
                            </div>
                            <span class="text-[11px] sm:text-xs text-muted-foreground block font-mono mt-0.5">
                                {{ formatTime(item.jam_pelajarans[0].waktu_mulai) }} - {{ formatTime(item.jam_pelajarans[item.jam_pelajarans.length - 1].waktu_selesai) }} WIB
                            </span>
                        </div>
                        <span v-else class="text-muted-foreground text-xs">-</span>
                    </div>
                </div>
                
                <!-- Action Buttons: Full width & thumb-friendly on touchscreens -->
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-border/60">
                    <Button variant="outline" size="sm" as-child class="h-9 justify-center text-xs font-semibold gap-1.5 border-border hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer">
                        <Link :href="`/jadwal-piket/${item.id}/edit`">
                            <Edit class="h-3.5 w-3.5 text-primary" />
                            <span>Edit</span>
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteJadwal(item.id)" class="h-9 justify-center text-xs font-semibold gap-1.5 cursor-pointer">
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>Hapus</span>
                    </Button>
                </div>
            </div>
            
            <div v-if="jadwalPikets.data.length === 0" class="text-center p-8 border border-sidebar-border/70 rounded-xl bg-card text-muted-foreground shadow-xs">
                <Calendar class="h-10 w-10 mx-auto text-muted-foreground/40 mb-2" />
                <div class="font-semibold text-foreground text-sm">Tidak Ada Jadwal Piket</div>
                <p class="text-xs text-muted-foreground mt-1 max-w-xs mx-auto">
                    Tidak ditemukan jadwal piket untuk periode ini. Anda dapat menambahkan jadwal baru atau mengubah filter.
                </p>
                <Button as-child size="sm" class="mt-4">
                    <Link href="/jadwal-piket/create">
                        <Plus class="mr-1.5 h-3.5 w-3.5" /> Tambah Jadwal Baru
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="jadwalPikets.links && jadwalPikets.links.length > 3" class="flex flex-wrap justify-center gap-1 sm:gap-1.5 py-3">
            <template v-for="(link, key) in jadwalPikets.links" :key="key">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 sm:px-3.5 py-1.5 border rounded-lg text-xs font-medium transition-colors"
                    :class="[
                        link.active ? 'bg-primary text-primary-foreground border-primary shadow-xs' : 'bg-background hover:bg-muted text-foreground'
                    ]"
                    v-html="link.label"
                    preserve-scroll
                />
                <span 
                    v-else
                    class="px-3 sm:px-3.5 py-1.5 border rounded-lg text-xs font-medium text-muted-foreground bg-muted/50"
                    v-html="link.label"
                />
            </template>
        </div>

        <!-- Dialog Pengaturan Batas Waktu Piket -->
        <Dialog :open="isPengaturanOpen" @update:open="isPengaturanOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-lg">
                        <Clock class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        Pengaturan Batas Waktu Pelaporan Piket
                    </DialogTitle>
                    <DialogDescription>
                        Atur jam batas akhir bagi guru piket untuk mengisi absensi & laporan kelas hari ini.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitPengaturan" class="space-y-4 py-2">
                    <div class="p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label for="waktu_mulai" class="text-xs font-semibold text-foreground">Jam Mulai Sesi</label>
                                <Input 
                                    id="waktu_mulai" 
                                    type="time" 
                                    v-model="formPengaturan.waktu_mulai" 
                                    class="h-9 text-sm bg-background" 
                                    required 
                                />
                                <p class="text-[10px] text-muted-foreground">Waktu awal pelaporan piket dibuka</p>
                            </div>
                            <div class="space-y-1.5">
                                <label for="waktu_selesai" class="text-xs font-semibold text-foreground">Jam Batas Akhir</label>
                                <Input 
                                    id="waktu_selesai" 
                                    type="time" 
                                    v-model="formPengaturan.waktu_selesai" 
                                    class="h-9 text-sm bg-background font-bold text-emerald-700 dark:text-emerald-400" 
                                    required 
                                />
                                <p class="text-[10px] text-muted-foreground">Batas akhir pengisian form piket</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/60 dark:bg-blue-950/30 text-xs text-blue-900 dark:text-blue-300 space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                            Ketentuan Batas Waktu:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] opacity-90 pl-1">
                            <li>Guru piket wajib menyelesaikan pengisian laporan sebelum pukul <strong>{{ formPengaturan.waktu_selesai || '16:00' }} WIB</strong>.</li>
                            <li>Tersedia countdown timer di form pelaporan guru piket.</li>
                            <li>Jika melewati batas waktu, form otomatis terkunci dan guru harus mendapatkan persetujuan izin susulan dari Wakasek.</li>
                        </ul>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button type="button" variant="outline" @click="isPengaturanOpen = false">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="formPengaturan.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                            {{ formPengaturan.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>