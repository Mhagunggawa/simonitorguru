<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, ChevronDown, ChevronUp, Eye, Clock, Calendar, ShieldCheck, FileText, UserCheck } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
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
        status?: string;
    };
    available_years: number[];
}>();

const search = ref(props.filters.search || '');
const filterType = ref(props.filters.filter_type || 'minggu');
const filterStatus = ref(props.filters.status || 'semua');
const filterSemester = ref(props.filters.filter_semester || (new Date().getMonth() >= 6 ? 'Ganjil' : 'Genap'));
const filterTahun = ref(props.filters.filter_tahun || new Date().getFullYear());
const filterBulan = ref(props.filters.filter_bulan || new Date().getMonth() + 1);
const filterMinggu = ref(props.filters.filter_minggu || 1);
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

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

watch([search, filterType, filterStatus, filterSemester, filterTahun, filterBulan, filterMinggu, startDate, endDate], debounce(() => {
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
    
    router.get('/laporan/gms', params, {
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

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'Hadir':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800';
        case 'Izin':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800';
        case 'Sakit':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800';
        case 'Alpha':
            return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-200 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
    }
};
</script>

<template>
    <Head title="Laporan Absensi GMS" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Laporan Absensi GMS</h1>
                <p class="text-sm text-muted-foreground">Monitoring kehadiran absensi GMS harian dan hasil konfirmasi kehadiran.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-col md:flex-row gap-3 bg-card p-4 rounded-xl border border-sidebar-border/70 shadow dark:border-sidebar-border">
            <div class="relative w-full md:w-1/3 lg:w-1/4">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari nama guru..." class="pl-8 w-full bg-background" />
            </div>
            
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <select v-model="filterStatus" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring font-medium">
                    <option value="semua">Semua Status</option>
                    <option value="Hadir">Hadir</option>
                    <option value="Izin">Izin</option>
                    <option value="Sakit">Sakit</option>
                    <option value="Alpha">Alpha</option>
                </select>

                <select v-model="filterType" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
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
                    <Input type="date" v-model="startDate" title="Tanggal Mulai" class="w-32 bg-background" />
                    <span class="text-muted-foreground text-sm">-</span>
                    <Input type="date" v-model="endDate" title="Tanggal Sampai" class="w-32 bg-background" />
                </template>
            </div>

            <div class="flex gap-2 w-full md:w-auto ml-auto">
                <a :href="`/laporan/gms/export-pdf?search=${search}&status=${filterStatus}&filter_type=${filterType}&filter_semester=${filterSemester}&filter_tahun=${filterTahun}&filter_bulan=${filterBulan}&filter_minggu=${filterMinggu}&start_date=${startDate}&end_date=${endDate}`" target="_blank" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                    PDF
                </a>
                <a :href="`/laporan/gms/export-excel?search=${search}&status=${filterStatus}&filter_type=${filterType}&filter_semester=${filterSemester}&filter_tahun=${filterTahun}&filter_bulan=${filterBulan}&filter_minggu=${filterMinggu}&start_date=${startDate}&end_date=${endDate}`" target="_blank" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-emerald-200 bg-background text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 h-10 px-4 py-2">
                    Excel
                </a>
            </div>
        </div>

        <!-- Data Content -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <!-- Desktop Table View -->
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Nama Guru</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Waktu Absen</th>
                        <th class="px-4 py-3 font-medium">Keterangan / Konfirmasi</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in laporan.data" :key="item.id" class="border-b border-sidebar-border/70 dark:border-sidebar-border last:border-0 hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(item.tanggal) }}</td>
                        <td class="px-4 py-3 font-medium text-foreground">{{ item.user?.name || '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold', getStatusBadge(item.status || 'Hadir')]">
                                {{ item.status || 'Hadir' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <Clock class="w-4 h-4" />
                                {{ item.waktu_absen || '-' }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                <span v-if="item.keterangan" class="text-xs text-foreground font-normal line-clamp-1" :title="item.keterangan">
                                    {{ item.keterangan }}
                                </span>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span v-if="item.confirmed_by_user || item.confirmed_by" class="inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                                        <ShieldCheck class="w-3.5 h-3.5 text-blue-500" />
                                        Oleh: {{ item.confirmed_by_user?.name || item.confirmed_by?.name || 'Admin' }}
                                    </span>
                                    <span v-else-if="item.foto_bukti" class="inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                                        <UserCheck class="w-3.5 h-3.5 text-emerald-500" />
                                        Mandiri
                                    </span>
                                    <span v-else-if="!item.keterangan" class="text-xs text-muted-foreground italic">-</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <Button variant="outline" size="sm" @click="openDetail(item)">
                                <Eye class="w-4 h-4 mr-1.5" /> Detail
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="laporan.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground italic">
                            Tidak ada data absensi GMS yang ditemukan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Accordion View -->
        <div class="md:hidden flex flex-col gap-4">
            <div v-for="item in laporan.data" :key="item.id" class="rounded-xl border border-sidebar-border/70 bg-card shadow overflow-hidden">
                <!-- Accordion Header -->
                <button @click="toggleAccordion(item.id)" class="flex items-center justify-between p-4 hover:bg-slate-50 dark:hover:bg-slate-900/20 transition-colors text-left w-full">
                    <div class="flex flex-col gap-1">
                        <span class="font-semibold text-foreground text-sm">{{ item.user?.name || '-' }}</span>
                        <div class="flex items-center gap-2">
                            <span :class="['px-2 py-0.5 w-fit rounded-md text-[10px] font-medium', getStatusBadge(item.status || 'Hadir')]">
                                {{ item.status || 'Hadir' }}
                            </span>
                            <span class="text-xs text-muted-foreground">{{ formatDate(item.tanggal) }}</span>
                        </div>
                    </div>
                    <ChevronDown v-if="openAccordionId !== item.id" class="w-5 h-5 text-muted-foreground" />
                    <ChevronUp v-else class="w-5 h-5 text-muted-foreground" />
                </button>
                <!-- Accordion Body -->
                <div v-if="openAccordionId === item.id" class="p-4 bg-slate-50/50 dark:bg-slate-900/10 border-t border-border/50 space-y-3">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Calendar class="w-4 h-4" />
                        <span>{{ formatDate(item.tanggal) }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Clock class="w-4 h-4" />
                        <span>{{ item.waktu_absen || '-' }}</span>
                    </div>
                    <div v-if="item.keterangan" class="flex items-start gap-2 text-sm text-foreground">
                        <FileText class="w-4 h-4 text-muted-foreground mt-0.5 shrink-0" />
                        <span>{{ item.keterangan }}</span>
                    </div>
                    <div v-if="item.confirmed_by_user || item.confirmed_by" class="flex items-center gap-2 text-xs text-muted-foreground">
                        <ShieldCheck class="w-4 h-4 text-blue-500" />
                        <span>Dikonfirmasi oleh: {{ item.confirmed_by_user?.name || item.confirmed_by?.name || 'Admin' }}</span>
                    </div>
                    <Button variant="default" size="sm" class="w-full bg-blue-600 hover:bg-blue-700 mt-2" @click="openDetail(item)">
                        Lihat Detail
                    </Button>
                </div>
            </div>
            <div v-if="laporan.data.length === 0" class="p-8 text-center text-muted-foreground italic rounded-xl border border-dashed">
                Tidak ada data absensi GMS yang ditemukan.
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
                      :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card border-border/50 hover:bg-slate-50 text-foreground'"
                      v-html="link.label">
                </button>
            </template>
        </div>

        <!-- Detail Modal -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-md max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Detail Absensi GMS</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-4" v-if="selectedItem">
                    <!-- Foto Bukti jika ada -->
                    <div class="flex flex-col items-center justify-center mb-4" v-if="selectedItem.foto_bukti">
                        <img :src="`/storage/${selectedItem.foto_bukti}`" alt="Foto Bukti" class="w-full h-auto max-h-64 object-cover rounded-lg border border-border shadow-sm">
                    </div>
                    <!-- Info jika tanpa foto bukti (dikonfirmasi admin) -->
                    <div v-else class="p-3 bg-muted/40 border border-muted rounded-lg text-xs text-muted-foreground flex items-center gap-2">
                        <ShieldCheck class="w-4 h-4 text-primary shrink-0" />
                        <span>Data kehadiran ini dicatat melalui konfirmasi admin tanpa foto bukti.</span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-sm border-b border-border/50 pb-2">
                        <span class="text-muted-foreground col-span-1">Nama Guru</span>
                        <span class="font-medium text-foreground col-span-2">{{ selectedItem.user?.name || '-' }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-sm border-b border-border/50 pb-2">
                        <span class="text-muted-foreground col-span-1">Tanggal</span>
                        <span class="font-medium text-foreground col-span-2">{{ formatDate(selectedItem.tanggal) }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-sm border-b border-border/50 pb-2">
                        <span class="text-muted-foreground col-span-1">Status Kehadiran</span>
                        <span class="font-medium text-foreground col-span-2">
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold', getStatusBadge(selectedItem.status || 'Hadir')]">
                                {{ selectedItem.status || 'Hadir' }}
                            </span>
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-sm border-b border-border/50 pb-2">
                        <span class="text-muted-foreground col-span-1">Waktu Absen</span>
                        <span class="font-medium text-foreground col-span-2 flex items-center gap-1.5">
                            <Clock class="w-3.5 h-3.5 text-muted-foreground" />
                            {{ selectedItem.waktu_absen || '-' }}
                        </span>
                    </div>
                    <div v-if="selectedItem.keterangan" class="grid grid-cols-3 gap-2 text-sm border-b border-border/50 pb-2">
                        <span class="text-muted-foreground col-span-1">Keterangan</span>
                        <span class="font-medium text-foreground col-span-2 text-xs leading-relaxed">{{ selectedItem.keterangan }}</span>
                    </div>
                    <div v-if="selectedItem.confirmed_by_user || selectedItem.confirmed_by" class="grid grid-cols-3 gap-2 text-sm border-b border-border/50 pb-2">
                        <span class="text-muted-foreground col-span-1">Dikonfirmasi Oleh</span>
                        <span class="font-medium text-foreground col-span-2 text-xs flex items-center gap-1 text-primary">
                            <ShieldCheck class="w-3.5 h-3.5" />
                            {{ selectedItem.confirmed_by_user?.name || selectedItem.confirmed_by?.name || 'Admin' }}
                        </span>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
