<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';
import { 
    Clock, 
    Calendar, 
    MapPin, 
    Search, 
    CheckCircle2, 
    AlertCircle, 
    Users, 
    ShieldCheck,
    Hourglass
} from '@lucide/vue';

interface IzinItem {
    id: number;
    jadwal_piket_id: number;
    guru_piket: { name: string };
    jam_pelajaran: { urutan_jam: number; waktu_mulai: string; waktu_selesai: string };
    jadwal_piket: { 
        tanggal_piket: string;
        titik: { nama_titik: string };
    };
    status: string;
    created_at: string;
}

const props = defineProps<{
    izinPikets: {
        data: IzinItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters?: {
        search?: string;
    };
}>();

const search = ref(props.filters?.search || '');
const form = useForm({});

watch(search, debounce((newVal: string) => {
    router.get('/izin-piket', { search: newVal }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(new Date(dateString));
};

const formatTime = (timeString: string) => {
    if (!timeString) return '-';
    return timeString.substring(0, 5);
};

const approve = (izin: IzinItem) => {
    Swal.fire({
        title: 'Beri Akses Laporan?',
        html: `Apakah Anda yakin ingin menyetujui izin pelaporan susulan untuk <b>${izin.guru_piket.name}</b>?<br><br><span class="text-xs text-muted-foreground">Setelah disetujui, guru piket dapat segera menginput laporan kehadiran yang terlambat.</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Beri Akses',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(`/izin-piket/${izin.id}/approve`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Akses Diberikan!',
                        text: `Izin laporan susulan untuk ${izin.guru_piket.name} telah disetujui.`,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat menyetujui izin.',
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Keterlambatan - Izin Laporan Susulan" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-3 md:p-6 space-y-2">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-card p-4 md:p-5 rounded-2xl border border-sidebar-border/70 shadow-xs dark:border-sidebar-border">
            <div>
                <h1 class="text-xl md:text-2xl font-bold tracking-tight text-foreground flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400">
                        <Clock class="w-5 h-5 md:w-6 md:h-6" />
                    </div>
                    Izin Laporan Keterlambatan
                </h1>
                <p class="text-xs md:text-sm text-muted-foreground mt-1">
                    Daftar Guru Piket yang terlambat melakukan laporan dan membutuhkan persetujuan akses susulan.
                </p>
            </div>

            <!-- Search Bar & Counter -->
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="search" 
                        placeholder="Cari nama guru..." 
                        class="pl-9 h-10 w-full bg-background rounded-xl text-xs md:text-sm"
                    />
                </div>
                <div class="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-semibold whitespace-nowrap">
                    <Hourglass class="w-3.5 h-3.5" />
                    <span>{{ izinPikets.total }} Menunggu</span>
                </div>
            </div>
        </div>

        <!-- Data Container -->
        <div class="rounded-2xl border border-sidebar-border/70 bg-card text-card-foreground shadow-xs dark:border-sidebar-border overflow-hidden">
            <!-- 1. TAMPILAN DESKTOP: TABLE VIEW (hidden md:block) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3.5 w-12 text-center font-bold">No</th>
                            <th class="px-4 py-3.5 font-bold">Guru Piket</th>
                            <th class="px-4 py-3.5 font-bold">Tanggal & Titik</th>
                            <th class="px-4 py-3.5 font-bold">Jam Pelajaran</th>
                            <th class="px-4 py-3.5 text-center font-bold">Status</th>
                            <th class="px-4 py-3.5 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                        <tr 
                            v-for="(izin, index) in izinPikets.data" 
                            :key="izin.id" 
                            class="hover:bg-muted/40 transition-colors"
                        >
                            <td class="px-4 py-4 text-center text-muted-foreground font-medium text-xs">
                                {{ (izinPikets.from || 1) + index }}
                            </td>
                            <td class="px-4 py-4 font-semibold text-foreground">
                                {{ izin.guru_piket?.name || '-' }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-1.5 text-sm font-medium text-foreground">
                                    <Calendar class="w-3.5 h-3.5 text-muted-foreground" />
                                    <span>{{ formatDate(izin.jadwal_piket?.tanggal_piket) }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
                                    <MapPin class="w-3 h-3 text-emerald-500" />
                                    <span>{{ izin.jadwal_piket?.titik?.nama_titik || '-' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-semibold text-foreground">
                                    Jam ke-{{ izin.jam_pelajaran?.urutan_jam }}
                                </span>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground mt-0.5">
                                    <Clock class="w-3 h-3" />
                                    <span>{{ formatTime(izin.jam_pelajaran?.waktu_mulai) }} - {{ formatTime(izin.jam_pelajaran?.waktu_selesai) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                    <Hourglass class="w-3 h-3 animate-spin" />
                                    Menunggu
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <Button 
                                    @click="approve(izin)" 
                                    size="sm" 
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs gap-1.5"
                                    :disabled="form.processing"
                                >
                                    <CheckCircle2 class="w-4 h-4" />
                                    Beri Akses
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="izinPikets.data.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <ShieldCheck class="w-10 h-10 text-emerald-500/70" />
                                    <p class="font-medium text-foreground text-sm">Tidak ada permintaan izin keterlambatan yang menunggu persetujuan.</p>
                                    <p class="text-xs text-muted-foreground">Semua guru piket telah melapor tepat waktu atau izin sudah disetujui.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 2. TAMPILAN HP / MOBILE: CARD LIST VIEW (block md:hidden) -->
            <div class="block md:hidden p-3 space-y-3 bg-slate-50/50 dark:bg-slate-950/40">
                <div 
                    v-for="(izin, index) in izinPikets.data" 
                    :key="izin.id"
                    class="p-4 rounded-2xl border border-sidebar-border/80 bg-white dark:bg-slate-900 shadow-xs space-y-3 transition-all"
                >
                    <!-- Header Kartu: No, Nama Guru & Status Badge -->
                    <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-border/60">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-[10px] flex items-center justify-center shrink-0">
                                {{ (izinPikets.from || 1) + index }}
                            </span>
                            <span class="font-bold text-slate-900 dark:text-slate-100 text-sm leading-snug line-clamp-1">
                                {{ izin.guru_piket?.name || '-' }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-300/80 shrink-0">
                            <Hourglass class="w-3 h-3" /> Menunggu
                        </span>
                    </div>

                    <!-- Detail Info Grid -->
                    <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-xl border border-border/50">
                        <!-- Tanggal -->
                        <div class="space-y-0.5">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Tanggal Piket:</span>
                            <div class="flex items-center gap-1 text-slate-800 dark:text-slate-200 font-medium">
                                <Calendar class="w-3.5 h-3.5 text-muted-foreground shrink-0" />
                                <span>{{ formatDate(izin.jadwal_piket?.tanggal_piket) }}</span>
                            </div>
                        </div>

                        <!-- Titik Pantau -->
                        <div class="space-y-0.5">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Titik Pantau:</span>
                            <div class="flex items-center gap-1 text-slate-800 dark:text-slate-200 font-medium">
                                <MapPin class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                <span class="truncate">{{ izin.jadwal_piket?.titik?.nama_titik || '-' }}</span>
                            </div>
                        </div>

                        <!-- Jam Pelajaran -->
                        <div class="col-span-2 pt-1 border-t border-border/40 space-y-0.5">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Jam Pelajaran:</span>
                            <div class="flex items-center justify-between text-slate-800 dark:text-slate-200 font-medium">
                                <span class="font-bold text-foreground">Jam ke-{{ izin.jam_pelajaran?.urutan_jam }}</span>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                    <Clock class="w-3.5 h-3.5" />
                                    <span>{{ formatTime(izin.jam_pelajaran?.waktu_mulai) }} - {{ formatTime(izin.jam_pelajaran?.waktu_selesai) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Mobile Touch-Friendly -->
                    <Button 
                        @click="approve(izin)" 
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold h-10 shadow-xs gap-2 transition-all active:scale-98"
                        :disabled="form.processing"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        <span>Beri Akses Laporan Susulan</span>
                    </Button>
                </div>

                <!-- Empty State Mobile -->
                <div v-if="izinPikets.data.length === 0" class="py-12 px-4 text-center bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-border">
                    <ShieldCheck class="w-10 h-10 text-emerald-500/70 mx-auto mb-2" />
                    <p class="font-bold text-foreground text-sm">Tidak ada permintaan izin</p>
                    <p class="text-xs text-muted-foreground mt-1 max-w-xs mx-auto">
                        Semua laporan keterlambatan telah disetujui atau tidak ada guru piket yang membutuhkan izin susulan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Pagination Section -->
        <div v-if="izinPikets.links?.length > 3" class="flex flex-wrap justify-center gap-1 mt-4">
            <template v-for="(link, k) in izinPikets.links" :key="k">
                <div 
                    v-if="link.url === null" 
                    class="px-3 py-2 text-xs md:text-sm text-muted-foreground bg-card border border-border/50 rounded-xl opacity-50 cursor-not-allowed" 
                    v-html="link.label"
                >
                </div>
                <button 
                    v-else
                    @click="router.get(link.url, {}, { preserveScroll: true, preserveState: true })"
                    class="px-3 py-2 text-xs md:text-sm rounded-xl transition-colors border font-medium"
                    :class="link.active ? 'bg-primary text-primary-foreground border-primary font-bold' : 'bg-card border-border/50 hover:bg-slate-50 text-foreground dark:hover:bg-slate-800'"
                    v-html="link.label"
                >
                </button>
            </template>
        </div>
    </div>
</template>
