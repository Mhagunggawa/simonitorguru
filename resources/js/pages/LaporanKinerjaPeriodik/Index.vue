<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Info, BarChart3, Search, Calendar as CalendarIcon, Image as ImageIcon, BookOpen, Clock, Flag, Users } from "@lucide/vue";
import { ref, computed } from "vue";

const props = defineProps<{
    data: any[];
    available_years: (string|number)[];
    filters: {
        month: string;
        year: string;
    };
}>();

const selectedMonth = ref(props.filters.month);
const selectedYear = ref(props.filters.year);
const searchQuery = ref('');

const months = [
    { value: '01', label: 'Januari' },
    { value: '02', label: 'Februari' },
    { value: '03', label: 'Maret' },
    { value: '04', label: 'April' },
    { value: '05', label: 'Mei' },
    { value: '06', label: 'Juni' },
    { value: '07', label: 'Juli' },
    { value: '08', label: 'Agustus' },
    { value: '09', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const applyFilter = () => {
    router.get('/laporan-kinerja-periodik', {
        month: selectedMonth.value,
        year: selectedYear.value
    }, { preserveState: true });
};

const filteredData = computed(() => {
    if (!searchQuery.value) return props.data;
    const q = searchQuery.value.toLowerCase();
    return props.data.filter(u => u.name.toLowerCase().includes(q));
});

// Ringkasan Akumulasi Total
const summaryTotals = computed(() => {
    const list = filteredData.value;
    return {
        totalGuru: list.length,
        kelas: {
            hadir: list.reduce((acc, u) => acc + (u.kelas?.hadir || 0), 0),
            izin: list.reduce((acc, u) => acc + (u.kelas?.izin || 0), 0),
            sakit: list.reduce((acc, u) => acc + (u.kelas?.sakit || 0), 0),
            alpha: list.reduce((acc, u) => acc + (u.kelas?.alpha || 0), 0),
        },
        gms: {
            hadir: list.reduce((acc, u) => acc + (u.gms?.hadir || 0), 0),
            izin: list.reduce((acc, u) => acc + (u.gms?.izin || 0), 0),
            sakit: list.reduce((acc, u) => acc + (u.gms?.sakit || 0), 0),
            alpha: list.reduce((acc, u) => acc + (u.gms?.alpha || 0), 0),
        },
        upacara: {
            hadir: list.reduce((acc, u) => acc + (u.upacara?.hadir || 0), 0),
            izin: list.reduce((acc, u) => acc + (u.upacara?.izin || 0), 0),
            sakit: list.reduce((acc, u) => acc + (u.upacara?.sakit || 0), 0),
            alpha: list.reduce((acc, u) => acc + (u.upacara?.alpha || 0), 0),
        }
    };
});

// Modal state
const isModalOpen = ref(false);
const modalTitle = ref('');
const modalDetails = ref<any[]>([]);
const modalType = ref(''); // 'kelas', 'gms', 'upacara'

const openModal = (teacherName: string, typeName: string, typeId: string, details: any[]) => {
    modalTitle.value = `Detail ${typeName} - ${teacherName}`;
    modalDetails.value = details || [];
    modalType.value = typeId;
    isModalOpen.value = true;
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'Hadir':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300';
        case 'Izin':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-300';
        case 'Sakit':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300';
        case 'Alpha':
        case 'Kosong':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300';
    }
};
</script>

<template>
    <Head title="Laporan Kinerja Periodik" />
    <div class="p-3 md:p-6 space-y-4 md:space-y-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4 md:pb-6 dark:border-slate-800">
            <div>
                <h1 class="text-xl md:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5 md:gap-3">
                    <div class="p-2 md:p-2.5 rounded-xl bg-blue-100 dark:bg-blue-900/30">
                        <BarChart3 class="w-5 h-5 md:w-7 md:h-7 text-blue-600 dark:text-blue-400" />
                    </div>
                    <span>Laporan Kinerja Periodik</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1.5 max-w-3xl text-xs md:text-sm leading-relaxed">
                    Rekapitulasi kehadiran mengajar di kelas, absensi GMS, dan absensi upacara dalam satu periode bulanan.
                </p>
            </div>
            
            <div class="flex items-center justify-between sm:justify-start gap-2 bg-white dark:bg-slate-900 p-1.5 md:p-2 rounded-xl shadow-xs border border-slate-200 dark:border-slate-800 w-full sm:w-auto">
                <Select v-model="selectedMonth" @update:model-value="applyFilter">
                    <SelectTrigger class="flex-1 sm:w-[140px] border-0 shadow-none focus:ring-0 text-xs md:text-sm">
                        <SelectValue placeholder="Pilih Bulan" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</SelectItem>
                    </SelectContent>
                </Select>
                <div class="w-px h-6 bg-slate-200 dark:bg-slate-700"></div>
                <Select v-model="selectedYear" @update:model-value="applyFilter">
                    <SelectTrigger class="w-[90px] md:w-[100px] border-0 shadow-none focus:ring-0 text-xs md:text-sm">
                        <SelectValue placeholder="Pilih Tahun" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="y in available_years" :key="y" :value="y.toString()">{{ y }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Metric Summary Cards (2 cols on mobile, 4 on lg) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 md:gap-4">
            <!-- Total Guru -->
            <Card class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                <CardContent class="p-3 md:p-4 flex items-center gap-2.5 md:gap-3.5">
                    <div class="w-9 h-9 md:w-11 md:h-11 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center justify-center shrink-0">
                        <Users class="w-4 h-4 md:w-5 md:h-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] md:text-xs font-medium text-slate-500 dark:text-slate-400 truncate">Total Guru</p>
                        <p class="text-base md:text-xl font-bold text-slate-900 dark:text-white mt-0.5">{{ summaryTotals.totalGuru }} Org</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Kehadiran di Kelas -->
            <Card class="border-blue-200 dark:border-blue-900/40 bg-blue-50/30 dark:bg-blue-950/20 shadow-xs">
                <CardContent class="p-3 md:p-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] md:text-xs font-semibold text-blue-800 dark:text-blue-300 flex items-center gap-1 truncate">
                            <BookOpen class="w-3.5 h-3.5 text-blue-600 shrink-0" /> Mengajar Kelas
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] md:text-xs font-bold pt-0.5">
                        <span class="text-emerald-700 dark:text-emerald-400">H: {{ summaryTotals.kelas.hadir }}</span>
                        <span class="text-blue-700 dark:text-blue-400">I: {{ summaryTotals.kelas.izin }}</span>
                        <span class="text-amber-700 dark:text-amber-400">S: {{ summaryTotals.kelas.sakit }}</span>
                        <span class="text-rose-700 dark:text-rose-400">A: {{ summaryTotals.kelas.alpha }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Absensi GMS -->
            <Card class="border-indigo-200 dark:border-indigo-900/40 bg-indigo-50/30 dark:bg-indigo-950/20 shadow-xs">
                <CardContent class="p-3 md:p-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] md:text-xs font-semibold text-indigo-800 dark:text-indigo-300 flex items-center gap-1 truncate">
                            <Clock class="w-3.5 h-3.5 text-indigo-600 shrink-0" /> Absensi GMS
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] md:text-xs font-bold pt-0.5">
                        <span class="text-emerald-700 dark:text-emerald-400">H: {{ summaryTotals.gms.hadir }}</span>
                        <span class="text-blue-700 dark:text-blue-400">I: {{ summaryTotals.gms.izin }}</span>
                        <span class="text-amber-700 dark:text-amber-400">S: {{ summaryTotals.gms.sakit }}</span>
                        <span class="text-rose-700 dark:text-rose-400">A: {{ summaryTotals.gms.alpha }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Absensi Upacara -->
            <Card class="border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 shadow-xs">
                <CardContent class="p-3 md:p-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] md:text-xs font-semibold text-emerald-800 dark:text-emerald-300 flex items-center gap-1 truncate">
                            <Flag class="w-3.5 h-3.5 text-emerald-600 shrink-0" /> Absensi Upacara
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] md:text-xs font-bold pt-0.5">
                        <span class="text-emerald-700 dark:text-emerald-400">H: {{ summaryTotals.upacara.hadir }}</span>
                        <span class="text-blue-700 dark:text-blue-400">I: {{ summaryTotals.upacara.izin }}</span>
                        <span class="text-amber-700 dark:text-amber-400">S: {{ summaryTotals.upacara.sakit }}</span>
                        <span class="text-rose-700 dark:text-rose-400">A: {{ summaryTotals.upacara.alpha }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Main Content Card -->
        <Card class="shadow-sm border-slate-200 dark:border-slate-800 overflow-hidden">
            <CardHeader class="p-3.5 md:p-6 pb-3 md:pb-4 border-b dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <CardTitle class="text-base md:text-lg">Tabel Rekapitulasi Kehadiran Guru</CardTitle>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Klik pada angka status untuk melihat rincian riwayat, tanggal, kelas, dan foto bukti.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative flex-1 sm:w-[260px]">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search class="h-4 w-4 text-slate-400" />
                            </div>
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Cari nama guru..." 
                                class="flex h-9 md:h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-xs md:text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-800 dark:bg-slate-950 dark:ring-offset-slate-950 dark:placeholder:text-slate-400 dark:focus-visible:ring-slate-300 pl-9" 
                            />
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="mt-3 pt-2.5 border-t border-slate-200/80 dark:border-slate-800 flex flex-wrap items-center gap-3 md:gap-4 text-xs text-slate-600 dark:text-slate-400">
                    <span class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[10px] md:text-[11px]">Keterangan:</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> <strong class="text-emerald-700 dark:text-emerald-400">H</strong> = Hadir</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> <strong class="text-blue-700 dark:text-blue-400">I</strong> = Izin</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> <strong class="text-amber-700 dark:text-amber-400">S</strong> = Sakit</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> <strong class="text-rose-700 dark:text-rose-400">A</strong> = Alpha</span>
                </div>
            </CardHeader>

            <CardContent class="p-0">
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <!-- Table Head with Sub-headers -->
                        <thead class="text-xs text-slate-700 uppercase bg-slate-100/90 dark:bg-slate-900/90 dark:text-slate-300">
                            <!-- Baris 1: Kategori Utama -->
                            <tr class="border-b dark:border-slate-800">
                                <th scope="col" rowspan="2" class="px-2 py-2 text-center w-10 border-r dark:border-slate-800 font-bold text-[11px]">No</th>
                                <th scope="col" rowspan="2" class="px-3 py-2 min-w-[170px] max-w-[240px] border-r dark:border-slate-800 font-bold text-xs">Nama Guru</th>
                                <th scope="col" rowspan="2" class="px-2 py-2 text-center min-w-[90px] border-r dark:border-slate-800 font-bold text-[11px]">Pangkat / Status</th>
                                
                                <!-- Kolom Group 1: Kehadiran di Kelas (Pantauan Piket) -->
                                <th scope="col" colspan="4" class="px-2 py-1.5 text-center bg-blue-50/90 dark:bg-blue-950/50 text-blue-900 dark:text-blue-200 border-r-2 border-slate-300 dark:border-slate-700 font-bold text-xs">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <BookOpen class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                        <span>Kehadiran di Kelas</span>
                                    </div>
                                </th>

                                <!-- Kolom Group 2: Absensi GMS -->
                                <th scope="col" colspan="4" class="px-2 py-1.5 text-center bg-indigo-50/90 dark:bg-indigo-950/50 text-indigo-900 dark:text-indigo-200 border-r-2 border-slate-300 dark:border-slate-700 font-bold text-xs">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <Clock class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                        <span>Absensi GMS</span>
                                    </div>
                                </th>

                                <!-- Kolom Group 3: Absensi Upacara -->
                                <th scope="col" colspan="4" class="px-2 py-1.5 text-center bg-emerald-50/90 dark:bg-emerald-950/50 text-emerald-900 dark:text-emerald-200 font-bold text-xs">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <Flag class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                        <span>Absensi Upacara</span>
                                    </div>
                                </th>
                            </tr>

                            <!-- Baris 2: Sub-Header H, I, S, A -->
                            <tr class="border-b dark:border-slate-800 text-[11px] font-black">
                                <!-- Kehadiran di Kelas -->
                                <th class="px-1 py-1.5 text-center text-emerald-700 dark:text-emerald-400 bg-blue-50/50 dark:bg-blue-950/30 border-r border-slate-200 dark:border-slate-800 w-8" title="Hadir">H</th>
                                <th class="px-1 py-1.5 text-center text-blue-700 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-950/30 border-r border-slate-200 dark:border-slate-800 w-8" title="Izin">I</th>
                                <th class="px-1 py-1.5 text-center text-amber-700 dark:text-amber-400 bg-blue-50/50 dark:bg-blue-950/30 border-r border-slate-200 dark:border-slate-800 w-8" title="Sakit">S</th>
                                <th class="px-1 py-1.5 text-center text-rose-700 dark:text-rose-400 bg-blue-50/50 dark:bg-blue-950/30 border-r-2 border-slate-300 dark:border-slate-700 w-8" title="Alpha">A</th>

                                <!-- Absensi GMS -->
                                <th class="px-1 py-1.5 text-center text-emerald-700 dark:text-emerald-400 bg-indigo-50/50 dark:bg-indigo-950/30 border-r border-slate-200 dark:border-slate-800 w-8" title="Hadir">H</th>
                                <th class="px-1 py-1.5 text-center text-blue-700 dark:text-blue-400 bg-indigo-50/50 dark:bg-indigo-950/30 border-r border-slate-200 dark:border-slate-800 w-8" title="Izin">I</th>
                                <th class="px-1 py-1.5 text-center text-amber-700 dark:text-amber-400 bg-indigo-50/50 dark:bg-indigo-950/30 border-r border-slate-200 dark:border-slate-800 w-8" title="Sakit">S</th>
                                <th class="px-1 py-1.5 text-center text-rose-700 dark:text-rose-400 bg-indigo-50/50 dark:bg-indigo-950/30 border-r-2 border-slate-300 dark:border-slate-700 w-8" title="Alpha">A</th>

                                <!-- Absensi Upacara -->
                                <th class="px-1 py-1.5 text-center text-emerald-700 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/30 border-r border-slate-200 dark:border-slate-800 w-8" title="Hadir">H</th>
                                <th class="px-1 py-1.5 text-center text-blue-700 dark:text-blue-400 bg-emerald-50/50 dark:bg-emerald-950/30 border-r border-slate-200 dark:border-slate-800 w-8" title="Izin">I</th>
                                <th class="px-1 py-1.5 text-center text-amber-700 dark:text-amber-400 bg-emerald-50/50 dark:bg-emerald-950/30 border-r border-slate-200 dark:border-slate-800 w-8" title="Sakit">S</th>
                                <th class="px-1 py-1.5 text-center text-rose-700 dark:text-rose-400 bg-emerald-50/50 dark:bg-emerald-950/30 w-8" title="Alpha">A</th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr v-for="(user, index) in filteredData" :key="user.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-2 py-2 text-center text-slate-500 border-r dark:border-slate-800 font-medium text-xs">{{ index + 1 }}</td>
                                
                                <td class="px-3 py-2 font-semibold text-slate-900 dark:text-slate-100 border-r dark:border-slate-800 text-xs md:text-sm truncate max-w-[240px]">
                                    {{ user.name }}
                                </td>
                                
                                <td class="px-2 py-2 text-center border-r dark:border-slate-800">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ user.pangkat }}
                                    </span>
                                </td>

                                <!-- 1. Kehadiran di Kelas (H, I, S, A) -->
                                <!-- H -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r border-slate-200 dark:border-slate-800 text-xs"
                                    :class="user.kelas.hadir > 0 ? 'text-emerald-700 dark:text-emerald-400 cursor-pointer hover:bg-emerald-50/80 dark:hover:bg-emerald-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.kelas.total > 0 ? 'Klik untuk melihat rincian riwayat kelas' : ''"
                                    @click="user.kelas.total > 0 && openModal(user.name, 'Kehadiran di Kelas (Pantauan Piket)', 'kelas', user.kelas.detail)"
                                >
                                    {{ user.kelas.hadir }}
                                </td>
                                <!-- I -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r border-slate-200 dark:border-slate-800 text-xs"
                                    :class="user.kelas.izin > 0 ? 'text-blue-700 dark:text-blue-400 cursor-pointer hover:bg-blue-50/80 dark:hover:bg-blue-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.kelas.total > 0 ? 'Klik untuk melihat rincian riwayat kelas' : ''"
                                    @click="user.kelas.total > 0 && openModal(user.name, 'Kehadiran di Kelas (Pantauan Piket)', 'kelas', user.kelas.detail)"
                                >
                                    <span v-if="user.kelas.izin > 0" class="inline-block px-1 py-0.2 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-[11px]">{{ user.kelas.izin }}</span>
                                    <span v-else>0</span>
                                </td>
                                <!-- S -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r border-slate-200 dark:border-slate-800 text-xs"
                                    :class="user.kelas.sakit > 0 ? 'text-amber-700 dark:text-amber-400 cursor-pointer hover:bg-amber-50/80 dark:hover:bg-amber-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.kelas.total > 0 ? 'Klik untuk melihat rincian riwayat kelas' : ''"
                                    @click="user.kelas.total > 0 && openModal(user.name, 'Kehadiran di Kelas (Pantauan Piket)', 'kelas', user.kelas.detail)"
                                >
                                    <span v-if="user.kelas.sakit > 0" class="inline-block px-1 py-0.2 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-[11px]">{{ user.kelas.sakit }}</span>
                                    <span v-else>0</span>
                                </td>
                                <!-- A -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r-2 border-slate-300 dark:border-slate-700 text-xs"
                                    :class="user.kelas.alpha > 0 ? 'text-rose-700 dark:text-rose-400 cursor-pointer hover:bg-rose-50/80 dark:hover:bg-rose-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.kelas.total > 0 ? 'Klik untuk melihat rincian riwayat kelas' : ''"
                                    @click="user.kelas.total > 0 && openModal(user.name, 'Kehadiran di Kelas (Pantauan Piket)', 'kelas', user.kelas.detail)"
                                >
                                    <span v-if="user.kelas.alpha > 0" class="inline-block px-1 py-0.2 rounded bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300 text-[11px] font-black">{{ user.kelas.alpha }}</span>
                                    <span v-else>0</span>
                                </td>

                                <!-- 2. Absensi GMS (H, I, S, A) -->
                                <!-- H -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r border-slate-200 dark:border-slate-800 text-xs"
                                    :class="user.gms.hadir > 0 ? 'text-emerald-700 dark:text-emerald-400 cursor-pointer hover:bg-emerald-50/80 dark:hover:bg-emerald-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.gms.total > 0 ? 'Klik untuk melihat rincian riwayat GMS' : ''"
                                    @click="user.gms.total > 0 && openModal(user.name, 'Absensi GMS', 'gms', user.gms.detail)"
                                >
                                    {{ user.gms.hadir }}
                                </td>
                                <!-- I -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r border-slate-200 dark:border-slate-800 text-xs"
                                    :class="user.gms.izin > 0 ? 'text-blue-700 dark:text-blue-400 cursor-pointer hover:bg-blue-50/80 dark:hover:bg-blue-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.gms.total > 0 ? 'Klik untuk melihat rincian riwayat GMS' : ''"
                                    @click="user.gms.total > 0 && openModal(user.name, 'Absensi GMS', 'gms', user.gms.detail)"
                                >
                                    <span v-if="user.gms.izin > 0" class="inline-block px-1 py-0.2 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-[11px]">{{ user.gms.izin }}</span>
                                    <span v-else>0</span>
                                </td>
                                <!-- S -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r border-slate-200 dark:border-slate-800 text-xs"
                                    :class="user.gms.sakit > 0 ? 'text-amber-700 dark:text-amber-400 cursor-pointer hover:bg-amber-50/80 dark:hover:bg-amber-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.gms.total > 0 ? 'Klik untuk melihat rincian riwayat GMS' : ''"
                                    @click="user.gms.total > 0 && openModal(user.name, 'Absensi GMS', 'gms', user.gms.detail)"
                                >
                                    <span v-if="user.gms.sakit > 0" class="inline-block px-1 py-0.2 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-[11px]">{{ user.gms.sakit }}</span>
                                    <span v-else>0</span>
                                </td>
                                <!-- A -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r-2 border-slate-300 dark:border-slate-700 text-xs"
                                    :class="user.gms.alpha > 0 ? 'text-rose-700 dark:text-rose-400 cursor-pointer hover:bg-rose-50/80 dark:hover:bg-rose-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.gms.total > 0 ? 'Klik untuk melihat rincian riwayat GMS' : ''"
                                    @click="user.gms.total > 0 && openModal(user.name, 'Absensi GMS', 'gms', user.gms.detail)"
                                >
                                    <span v-if="user.gms.alpha > 0" class="inline-block px-1 py-0.2 rounded bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300 text-[11px] font-black">{{ user.gms.alpha }}</span>
                                    <span v-else>0</span>
                                </td>

                                <!-- 3. Absensi Upacara (H, I, S, A) -->
                                <!-- H -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r border-slate-200 dark:border-slate-800 text-xs"
                                    :class="user.upacara.hadir > 0 ? 'text-emerald-700 dark:text-emerald-400 cursor-pointer hover:bg-emerald-50/80 dark:hover:bg-emerald-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.upacara.total > 0 ? 'Klik untuk melihat rincian riwayat Upacara' : ''"
                                    @click="user.upacara.total > 0 && openModal(user.name, 'Absensi Upacara', 'upacara', user.upacara.detail)"
                                >
                                    {{ user.upacara.hadir }}
                                </td>
                                <!-- I -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r border-slate-200 dark:border-slate-800 text-xs"
                                    :class="user.upacara.izin > 0 ? 'text-blue-700 dark:text-blue-400 cursor-pointer hover:bg-blue-50/80 dark:hover:bg-blue-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.upacara.total > 0 ? 'Klik untuk melihat rincian riwayat Upacara' : ''"
                                    @click="user.upacara.total > 0 && openModal(user.name, 'Absensi Upacara', 'upacara', user.upacara.detail)"
                                >
                                    <span v-if="user.upacara.izin > 0" class="inline-block px-1 py-0.2 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-[11px]">{{ user.upacara.izin }}</span>
                                    <span v-else>0</span>
                                </td>
                                <!-- S -->
                                <td 
                                    class="px-1 py-2 text-center font-bold border-r border-slate-200 dark:border-slate-800 text-xs"
                                    :class="user.upacara.sakit > 0 ? 'text-amber-700 dark:text-amber-400 cursor-pointer hover:bg-amber-50/80 dark:hover:bg-amber-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.upacara.total > 0 ? 'Klik untuk melihat rincian riwayat Upacara' : ''"
                                    @click="user.upacara.total > 0 && openModal(user.name, 'Absensi Upacara', 'upacara', user.upacara.detail)"
                                >
                                    <span v-if="user.upacara.sakit > 0" class="inline-block px-1 py-0.2 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-[11px]">{{ user.upacara.sakit }}</span>
                                    <span v-else>0</span>
                                </td>
                                <!-- A -->
                                <td 
                                    class="px-1 py-2 text-center font-bold text-xs"
                                    :class="user.upacara.alpha > 0 ? 'text-rose-700 dark:text-rose-400 cursor-pointer hover:bg-rose-50/80 dark:hover:bg-rose-950/40' : 'text-slate-300 dark:text-slate-600'"
                                    :title="user.upacara.total > 0 ? 'Klik untuk melihat rincian riwayat Upacara' : ''"
                                    @click="user.upacara.total > 0 && openModal(user.name, 'Absensi Upacara', 'upacara', user.upacara.detail)"
                                >
                                    <span v-if="user.upacara.alpha > 0" class="inline-block px-1 py-0.2 rounded bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300 text-[11px] font-black">{{ user.upacara.alpha }}</span>
                                    <span v-else>0</span>
                                </td>
                            </tr>
                            
                            <tr v-if="filteredData.length === 0">
                                <td colspan="15" class="px-6 py-12 text-center text-slate-500">
                                    Tidak ada data guru yang cocok dengan pencarian.
                                </td>
                            </tr>
                        </tbody>

                        <!-- Table Footer Total Summary -->
                        <tfoot v-if="filteredData.length > 0" class="bg-slate-100/90 dark:bg-slate-900 font-extrabold text-xs text-slate-800 dark:text-slate-200 border-t-2 border-slate-300 dark:border-slate-700">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right uppercase tracking-wider font-extrabold border-r dark:border-slate-800 text-xs">
                                    Total Akumulasi:
                                </td>
                                <!-- Kelas H, I, S, A -->
                                <td class="px-1 py-2 text-center text-emerald-700 dark:text-emerald-400 border-r border-slate-200 dark:border-slate-800 text-xs">{{ summaryTotals.kelas.hadir }}</td>
                                <td class="px-1 py-2 text-center text-blue-700 dark:text-blue-400 border-r border-slate-200 dark:border-slate-800 text-xs">{{ summaryTotals.kelas.izin }}</td>
                                <td class="px-1 py-2 text-center text-amber-700 dark:text-amber-400 border-r border-slate-200 dark:border-slate-800 text-xs">{{ summaryTotals.kelas.sakit }}</td>
                                <td class="px-1 py-2 text-center text-rose-700 dark:text-rose-400 border-r-2 border-slate-300 dark:border-slate-700 text-xs">{{ summaryTotals.kelas.alpha }}</td>

                                <!-- GMS H, I, S, A -->
                                <td class="px-1 py-2 text-center text-emerald-700 dark:text-emerald-400 border-r border-slate-200 dark:border-slate-800 text-xs">{{ summaryTotals.gms.hadir }}</td>
                                <td class="px-1 py-2 text-center text-blue-700 dark:text-blue-400 border-r border-slate-200 dark:border-slate-800 text-xs">{{ summaryTotals.gms.izin }}</td>
                                <td class="px-1 py-2 text-center text-amber-700 dark:text-amber-400 border-r border-slate-200 dark:border-slate-800 text-xs">{{ summaryTotals.gms.sakit }}</td>
                                <td class="px-1 py-2 text-center text-rose-700 dark:text-rose-400 border-r-2 border-slate-300 dark:border-slate-700 text-xs">{{ summaryTotals.gms.alpha }}</td>

                                <!-- Upacara H, I, S, A -->
                                <td class="px-1 py-2 text-center text-emerald-700 dark:text-emerald-400 border-r border-slate-200 dark:border-slate-800 text-xs">{{ summaryTotals.upacara.hadir }}</td>
                                <td class="px-1 py-2 text-center text-blue-700 dark:text-blue-400 border-r border-slate-200 dark:border-slate-800 text-xs">{{ summaryTotals.upacara.izin }}</td>
                                <td class="px-1 py-2 text-center text-amber-700 dark:text-amber-400 border-r border-slate-200 dark:border-slate-800 text-xs">{{ summaryTotals.upacara.sakit }}</td>
                                <td class="px-1 py-2 text-center text-rose-700 dark:text-rose-400 text-xs">{{ summaryTotals.upacara.alpha }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Mobile & Tablet Card List View -->
                <div class="block lg:hidden divide-y divide-slate-200 dark:divide-slate-800">
                    <div 
                        v-for="(user, index) in filteredData" 
                        :key="'mob-periodik-' + user.id" 
                        class="p-3.5 space-y-3 bg-white dark:bg-slate-900 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                    >
                        <!-- Teacher Info Row -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ index + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-sm text-slate-900 dark:text-white truncate">
                                        {{ user.name }}
                                    </h3>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 shrink-0">
                                {{ user.pangkat }}
                            </span>
                        </div>

                        <!-- 3 Attendance Category Rows -->
                        <div class="space-y-2 text-xs">
                            <!-- 1. Kelas (Piket) -->
                            <div class="p-2.5 rounded-lg bg-blue-50/40 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30 flex items-center justify-between gap-2">
                                <span class="font-semibold text-blue-900 dark:text-blue-300 flex items-center gap-1.5 shrink-0 text-xs">
                                    <BookOpen class="w-3.5 h-3.5 text-blue-600" /> Kelas:
                                </span>
                                <div class="flex items-center gap-1">
                                    <button 
                                        @click="user.kelas.total > 0 && openModal(user.name, 'Kehadiran di Kelas (Pantauan Piket)', 'kelas', user.kelas.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.kelas.hadir > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 cursor-pointer' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        H: {{ user.kelas.hadir }}
                                    </button>
                                    <button 
                                        @click="user.kelas.total > 0 && openModal(user.name, 'Kehadiran di Kelas (Pantauan Piket)', 'kelas', user.kelas.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.kelas.izin > 0 ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-300 cursor-pointer' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        I: {{ user.kelas.izin }}
                                    </button>
                                    <button 
                                        @click="user.kelas.total > 0 && openModal(user.name, 'Kehadiran di Kelas (Pantauan Piket)', 'kelas', user.kelas.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.kelas.sakit > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 cursor-pointer' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        S: {{ user.kelas.sakit }}
                                    </button>
                                    <button 
                                        @click="user.kelas.total > 0 && openModal(user.name, 'Kehadiran di Kelas (Pantauan Piket)', 'kelas', user.kelas.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.kelas.alpha > 0 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 cursor-pointer font-black' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        A: {{ user.kelas.alpha }}
                                    </button>
                                </div>
                            </div>

                            <!-- 2. GMS -->
                            <div class="p-2.5 rounded-lg bg-indigo-50/40 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 flex items-center justify-between gap-2">
                                <span class="font-semibold text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5 shrink-0 text-xs">
                                    <Clock class="w-3.5 h-3.5 text-indigo-600" /> GMS:
                                </span>
                                <div class="flex items-center gap-1">
                                    <button 
                                        @click="user.gms.total > 0 && openModal(user.name, 'Absensi GMS', 'gms', user.gms.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.gms.hadir > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 cursor-pointer' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        H: {{ user.gms.hadir }}
                                    </button>
                                    <button 
                                        @click="user.gms.total > 0 && openModal(user.name, 'Absensi GMS', 'gms', user.gms.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.gms.izin > 0 ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-300 cursor-pointer' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        I: {{ user.gms.izin }}
                                    </button>
                                    <button 
                                        @click="user.gms.total > 0 && openModal(user.name, 'Absensi GMS', 'gms', user.gms.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.gms.sakit > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 cursor-pointer' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        S: {{ user.gms.sakit }}
                                    </button>
                                    <button 
                                        @click="user.gms.total > 0 && openModal(user.name, 'Absensi GMS', 'gms', user.gms.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.gms.alpha > 0 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 cursor-pointer font-black' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        A: {{ user.gms.alpha }}
                                    </button>
                                </div>
                            </div>

                            <!-- 3. Upacara -->
                            <div class="p-2.5 rounded-lg bg-emerald-50/40 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 flex items-center justify-between gap-2">
                                <span class="font-semibold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5 shrink-0 text-xs">
                                    <Flag class="w-3.5 h-3.5 text-emerald-600" /> Upacara:
                                </span>
                                <div class="flex items-center gap-1">
                                    <button 
                                        @click="user.upacara.total > 0 && openModal(user.name, 'Absensi Upacara', 'upacara', user.upacara.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.upacara.hadir > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 cursor-pointer' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        H: {{ user.upacara.hadir }}
                                    </button>
                                    <button 
                                        @click="user.upacara.total > 0 && openModal(user.name, 'Absensi Upacara', 'upacara', user.upacara.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.upacara.izin > 0 ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-300 cursor-pointer' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        I: {{ user.upacara.izin }}
                                    </button>
                                    <button 
                                        @click="user.upacara.total > 0 && openModal(user.name, 'Absensi Upacara', 'upacara', user.upacara.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.upacara.sakit > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 cursor-pointer' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        S: {{ user.upacara.sakit }}
                                    </button>
                                    <button 
                                        @click="user.upacara.total > 0 && openModal(user.name, 'Absensi Upacara', 'upacara', user.upacara.detail)"
                                        class="px-2 py-1 rounded text-center font-bold min-w-[28px] transition-transform active:scale-95"
                                        :class="user.upacara.alpha > 0 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 cursor-pointer font-black' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600'"
                                    >
                                        A: {{ user.upacara.alpha }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="filteredData.length === 0" class="p-8 text-center text-sm text-slate-500 italic">
                        Tidak ada data guru yang cocok dengan pencarian.
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Detail Modal -->
        <Dialog v-model:open="isModalOpen">
            <DialogContent class="sm:max-w-[550px] max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-base md:text-lg font-bold flex items-center gap-2">
                        <Info class="w-5 h-5 text-blue-600" />
                        {{ modalTitle }}
                    </DialogTitle>
                    <DialogDescription>
                        Rincian riwayat data kehadiran, jadwal, status, dan bukti dokumentasi.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="mt-4 space-y-3">
                    <div v-if="modalDetails.length === 0" class="text-center py-8 text-slate-400 text-sm">
                        Belum ada catatan riwayat kehadiran untuk kategori ini pada periode yang dipilih.
                    </div>
                    
                    <div 
                        v-for="(item, i) in modalDetails" 
                        :key="i" 
                        class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex flex-col gap-2.5"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300 font-semibold text-sm">
                                <CalendarIcon class="w-4 h-4 text-slate-400" />
                                {{ item.tanggal }}
                            </div>
                            <div class="flex items-center gap-2">
                                <span v-if="item.waktu && item.waktu !== '-'" class="text-xs text-slate-500 bg-white dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                                    {{ item.waktu }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" :class="getStatusBadgeClass(item.status)">
                                    {{ item.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Informasi Khusus Kehadiran di Kelas (Kelas & Mapel) -->
                        <div v-if="modalType === 'kelas'" class="text-xs text-slate-600 dark:text-slate-400 grid grid-cols-2 gap-2 bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                            <div>
                                <span class="text-muted-foreground block text-[10px] uppercase font-bold">Kelas:</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ item.kelas }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block text-[10px] uppercase font-bold">Mata Pelajaran:</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ item.mapel }}</span>
                            </div>
                        </div>

                        <!-- Keterangan jika ada -->
                        <div v-if="item.keterangan" class="text-xs text-slate-600 dark:text-slate-300 bg-amber-50 dark:bg-amber-950/20 p-2.5 rounded-lg border border-amber-200 dark:border-amber-900/30">
                            <span class="font-semibold text-amber-800 dark:text-amber-300">Catatan:</span> {{ item.keterangan }}
                        </div>
                        
                        <!-- Bukti Foto jika ada -->
                        <div v-if="item.foto" class="mt-1">
                            <div class="rounded-lg overflow-hidden border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-1">
                                <img :src="item.foto" alt="Bukti Kehadiran" class="w-full h-auto object-contain max-h-52 rounded" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-5 flex justify-end border-t pt-3">
                    <Button variant="outline" @click="isModalOpen = false">Tutup</Button>
                </div>
            </DialogContent>
        </Dialog>

    </div>
</template>
