<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ClipboardList } from '@lucide/vue';
import Swal from 'sweetalert2';
import { onMounted, watch, computed } from 'vue';

const page = usePage<any>();

const props = defineProps<{
    jadwalPikets: Array<{
        id: number;
        tanggal_piket: string;
        titik: { nama_titik: string };
        jam_pelajarans: Array<{ id: number, urutan_jam: number, waktu_mulai: string, waktu_selesai: string }>;
    }>;
    serverTime: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tugas Piket Hari Ini',
                href: '/pelaporan-piket',
            },
        ],
    },
});

const showFlashMessage = (flash: any) => {
    if (flash?.success) {
        Swal.fire({
            title: 'Berhasil!',
            text: flash.success,
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    } else if (flash?.error) {
        Swal.fire({
            title: 'Akses Ditolak',
            text: flash.error,
            icon: 'error',
            confirmButtonText: 'Tutup'
        });
    }
};

onMounted(() => {
    showFlashMessage(page.props.flash);
});

watch(() => page.props.flash, (flash) => {
    showFlashMessage(flash);
}, { deep: true });

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(date);
};

const formatTime = (time: string) => time ? time.substring(0, 5) : '';

const getReportState = (jam: any) => {
    if (!jam || !jam.waktu_mulai || !jam.waktu_selesai) return 'INVALID';
    
    const now = new Date(props.serverTime);
    
    let mulaiStr = jam.waktu_mulai;
    if (mulaiStr.length <= 5) mulaiStr += ':00';
    const [jamMulai, menitMulai, detikMulai] = mulaiStr.split(':').map(Number);
    const waktuMulai = new Date(props.serverTime);
    waktuMulai.setHours(jamMulai, menitMulai, detikMulai, 0);
    
    let selesaiStr = jam.waktu_selesai;
    if (selesaiStr.length <= 5) selesaiStr += ':00';
    const [jamSelesai, menitSelesai, detikSelesai] = selesaiStr.split(':').map(Number);
    const waktuSelesai = new Date(props.serverTime);
    waktuSelesai.setHours(jamSelesai, menitSelesai, detikSelesai, 0);
    
    if (now < waktuMulai) {
        return 'BELUM_MULAI';
    } else if (now > waktuSelesai) {
        return 'SESI_DITUTUP';
    } else {
        return 'AKTIF';
    }
};

const activeJadwalPikets = computed(() => {
    if (!props.jadwalPikets) return [];
    return props.jadwalPikets.map(piket => {
        return {
            ...piket,
            jam_pelajarans: piket.jam_pelajarans
        };
    }).filter(piket => piket.jam_pelajarans.length > 0);
});
</script>

<template>
    <Head title="Tugas Piket Hari Ini" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Tugas Piket Hari Ini</h1>
        </div>

        <div class="hidden md:block bg-white dark:bg-slate-900 shadow-sm sm:rounded-2xl border border-gray-100 dark:border-slate-800 overflow-hidden">
            <table class="w-full text-sm text-left whitespace-nowrap">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-slate-800/50 text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Tanggal</th>
                        <th class="px-6 py-4 font-semibold">Titik Lokasi</th>
                        <th class="px-6 py-4 font-semibold">Jam Pelajaran</th>
                        <th class="px-6 py-4 font-semibold text-right w-[180px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    <template v-for="item in activeJadwalPikets" :key="item.id">
                        <tr v-for="jam in item.jam_pelajarans" :key="jam.id" class="hover:bg-[#79AE6F]/10 dark:hover:bg-slate-800/50 transition-colors duration-200 group">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">{{ formatDate(item.tanggal_piket) }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ item.titik?.nama_titik }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex w-fit items-center rounded-full bg-[#79AE6F]/10 dark:bg-[#79AE6F]/20 px-2.5 py-1 text-xs font-semibold text-[#79AE6F] dark:text-[#79AE6F] border border-[#79AE6F]/20 dark:border-[#79AE6F]/30">
                                        Jam ke-{{ jam.urutan_jam }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                                        {{ formatTime(jam.waktu_mulai) }} - {{ formatTime(jam.waktu_selesai) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <template v-if="getReportState(jam) === 'AKTIF' || getReportState(jam) === 'SESI_DITUTUP'">
                                    <Link 
                                        :href="`/pelaporan-piket/${item.id}/create?jam_pelajaran_id=${jam.id}`" 
                                        class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 h-9 px-4 py-2 shadow-sm whitespace-nowrap"
                                        :class="getReportState(jam) === 'AKTIF' ? 'bg-[#79AE6F] text-white hover:bg-[#79AE6F]/90' : 'bg-yellow-500 text-white hover:bg-yellow-600'"
                                    >
                                        <ClipboardList class="h-4 w-4 mr-2" /> {{ getReportState(jam) === 'AKTIF' ? 'Isi Laporan' : 'Isi Laporan Susulan' }}
                                    </Link>
                                </template>
                                
                                <template v-else-if="getReportState(jam) === 'BELUM_MULAI'">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap shadow-sm border border-gray-200 dark:border-slate-700">
                                        Belum Mulai
                                    </span>
                                </template>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="activeJadwalPikets.length === 0">
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mb-4 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                <p class="text-sm">Anda tidak memiliki tugas piket hari ini.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Layout -->
        <div class="md:hidden flex flex-col gap-4">
            <template v-for="item in activeJadwalPikets" :key="item.id">
                <div 
                    v-for="jam in item.jam_pelajarans" 
                    :key="jam.id" 
                    class="bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm p-4 flex flex-col gap-3 relative overflow-hidden"
                >
                    <!-- Decorative line indicating state -->
                    <div class="absolute top-0 left-0 w-1 h-full" :class="{
                        'bg-[#79AE6F]': getReportState(jam) === 'AKTIF',
                        'bg-gray-300 dark:bg-slate-600': getReportState(jam) === 'BELUM_MULAI'
                    }"></div>

                    <div class="flex justify-between items-start ml-2 border-b border-gray-50 dark:border-slate-800/50 pb-2">
                        <div class="font-bold text-base text-gray-900 dark:text-gray-100">{{ formatDate(item.tanggal_piket) }}</div>
                        <span class="inline-flex items-center rounded-full bg-[#79AE6F]/10 dark:bg-[#79AE6F]/20 px-2 py-0.5 text-xs font-semibold text-[#79AE6F] dark:text-[#79AE6F] border border-[#79AE6F]/20 dark:border-[#79AE6F]/30">
                            Jam ke-{{ jam.urutan_jam }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center text-sm ml-2">
                        <span class="font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            Titik Lokasi
                        </span>
                        <span class="font-medium text-right bg-gray-50 dark:bg-slate-800 px-2 py-1 rounded-md">{{ item.titik?.nama_titik }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center text-sm ml-2 mt-1">
                        <span class="font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Waktu
                        </span>
                        <span class="font-medium text-right">{{ formatTime(jam.waktu_mulai) }} - {{ formatTime(jam.waktu_selesai) }}</span>
                    </div>
                    
                    <div class="mt-3 ml-2 pt-3 border-t border-gray-100 dark:border-slate-800/80">
                        <template v-if="getReportState(jam) === 'AKTIF' || getReportState(jam) === 'SESI_DITUTUP'">
                            <Link 
                                :href="`/pelaporan-piket/${item.id}/create?jam_pelajaran_id=${jam.id}`" 
                                class="flex items-center justify-center w-full rounded-xl text-sm font-semibold transition-colors h-10 px-4 py-2 shadow-sm"
                                :class="getReportState(jam) === 'AKTIF' ? 'bg-[#79AE6F] text-white hover:bg-[#79AE6F]/90' : 'bg-yellow-500 text-white hover:bg-yellow-600'"
                            >
                                <ClipboardList class="h-4 w-4 mr-2" /> {{ getReportState(jam) === 'AKTIF' ? 'Isi Laporan Sekarang' : 'Isi Laporan Susulan' }}
                            </Link>
                        </template>
                        
                        <template v-else-if="getReportState(jam) === 'BELUM_MULAI'">
                            <div class="flex items-center justify-center w-full rounded-xl bg-gray-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-slate-700">
                                Sesi Belum Mulai
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            
            <div v-if="activeJadwalPikets.length === 0" class="text-center p-8 border border-gray-100 dark:border-slate-800 rounded-2xl bg-white dark:bg-slate-900 text-gray-500 dark:text-gray-400 shadow-sm">
                <div class="flex flex-col items-center justify-center">
                    <svg class="w-12 h-12 mb-4 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    <p class="text-sm">Anda tidak memiliki tugas piket hari ini.</p>
                </div>
            </div>
        </div>
    </div>
</template>
