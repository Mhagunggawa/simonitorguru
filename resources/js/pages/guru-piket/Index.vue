<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';
import { ClipboardList, AlertCircle, CheckCircle, UserX, Info, Clock, Hourglass } from '@lucide/vue';
import Swal from 'sweetalert2';
import { onMounted, watch, computed, ref } from 'vue';

const page = usePage<any>();

const props = defineProps<{
    jadwalPikets: Array<{
        id: number;
        tanggal_piket: string;
        status?: string | null;
        keterangan?: string | null;
        piket_waktu_mulai?: string | null;
        piket_waktu_selesai?: string | null;
        is_expired?: boolean;
        batas_waktu_pelaporan?: string | null;
        has_approved_izin?: boolean;
        has_pending_izin?: boolean;
        titik: { nama_titik: string };
        jam_pelajarans: Array<{ 
            id: number; 
            urutan_jam: number; 
            waktu_mulai: string; 
            waktu_selesai: string;
            total_classes?: number;
            reported_classes?: number;
            unreported_classes?: number;
            is_completed?: boolean;
            has_no_classes?: boolean;
        }>;
    }>;
    canAjukanIzinSakit?: boolean;
    activePiketId?: number | null;
    activePiketStatus?: string | null;
    activePiketKeterangan?: string | null;
    serverTime: string;
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
                title: 'Tugas Piket Hari Ini',
                href: '/pelaporan-piket',
            },
        ],
    },
});

const isModalOpen = ref(false);
const keteranganError = ref('');
const formIzin = ref({
    status: 'Izin',
    keterangan: '',
    processing: false
});

const openIzinSakitModal = () => {
    formIzin.value.status = 'Izin';
    formIzin.value.keterangan = '';
    keteranganError.value = '';
    isModalOpen.value = true;
};

const submitIzinSakit = () => {
    if (!formIzin.value.keterangan.trim()) {
        keteranganError.value = 'Silakan isi alasan atau keterangan pengajuan izin/sakit tugas piket Anda.';
        return;
    }
    keteranganError.value = '';

    if (!props.activePiketId) return;

    // Tutup modal terlebih dahulu agar tidak memblokir/menimpa interaksi SweetAlert2
    isModalOpen.value = false;

    // Beri jeda singkat agar overlay modal Radix selesai transisi keluar
    setTimeout(() => {
        Swal.fire({
            title: `Konfirmasi ${formIzin.value.status} Piket`,
            text: `Apakah Anda yakin ingin mengajukan ${formIzin.value.status} untuk tugas piket hari ini? Anda tidak perlu mengisi laporan pemantauan kelas.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Ajukan Sekarang',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                formIzin.value.processing = true;
                router.post(`/pelaporan-piket/${props.activePiketId}/izin-sakit`, {
                    status: formIzin.value.status,
                    keterangan: formIzin.value.keterangan
                }, {
                    onFinish: () => {
                        formIzin.value.processing = false;
                    }
                });
            } else {
                // Jika dibatalkan pada SweetAlert, buka kembali modal agar data tidak hilang
                isModalOpen.value = true;
            }
        });
    }, 150);
};

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

const requestIzinSusulan = (jadwalId: number) => {
    Swal.fire({
        title: 'Minta Izin Susulan?',
        text: 'Waktu pelaporan piket telah melewati batas waktu yang ditentukan. Apakah Anda ingin mengajukan izin susulan ke Wakasek agar form laporan dapat dibuka kembali?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Ajukan Izin ke Wakasek',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/pelaporan-piket/${jadwalId}/minta-izin-susulan`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Permohonan Terkirim!',
                        text: 'Permohonan izin susulan telah dikirimkan ke Wakasek. Silakan hubungi Wakasek untuk memberikan persetujuan akses.',
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false,
                    });
                }
            });
        }
    });
};

const getReportState = (jam: any, item?: any) => {
    // 1. Jika jadwal secara keseluruhan sudah melewati batas waktu yang disetting
    if (item?.is_expired) {
        return 'SESI_DITUTUP';
    }

    // 2. Waktu mulai mengikuti jam pelajaran spesifik ini (jam.waktu_mulai)
    const mulaiStr = jam?.waktu_mulai;
    // 3. Batas waktu pelaporan mengikuti batas waktu yang disetting di pengaturan piket (misal 16:00 WIB)
    const selesaiStr = item?.batas_waktu_pelaporan || props.pengaturan?.waktu_selesai || '16:00';

    if (!mulaiStr || !selesaiStr) return 'INVALID';
    
    const now = new Date(props.serverTime || new Date());
    
    let mStr = String(mulaiStr);
    if (mStr.length <= 5) mStr += ':00';
    const [jamMulai, menitMulai, detikMulai = 0] = mStr.split(':').map(Number);
    const waktuMulai = new Date(now);
    if (item?.tanggal_piket) {
        const [tglY, tglM, tglD] = item.tanggal_piket.substring(0, 10).split('-').map(Number);
        waktuMulai.setFullYear(tglY, tglM - 1, tglD);
    }
    waktuMulai.setHours(jamMulai, menitMulai, detikMulai, 0);
    
    let sStr = String(selesaiStr);
    if (sStr.length <= 5) sStr += ':00';
    const [jamSelesai, menitSelesai, detikSelesai = 0] = sStr.split(':').map(Number);
    const waktuSelesai = new Date(now);
    if (item?.tanggal_piket) {
        const [tglY, tglM, tglD] = item.tanggal_piket.substring(0, 10).split('-').map(Number);
        waktuSelesai.setFullYear(tglY, tglM - 1, tglD);
    }
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
    return props.jadwalPikets || [];
});
</script>

<template>
    <Head title="Tugas Piket Hari Ini" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <!-- Header & Action Button -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Tugas Piket Hari Ini</h1>
                <p class="text-sm text-muted-foreground mt-0.5">Pemantauan kelas dan absensi tugas piket harian</p>
            </div>
            
            <div v-if="canAjukanIzinSakit && activePiketId" class="flex items-center gap-2">
                <Button 
                    @click="openIzinSakitModal" 
                    variant="outline" 
                    class="border-amber-300 dark:border-amber-700 bg-amber-50 hover:bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/60 shadow-xs gap-2 font-medium"
                >
                    <AlertCircle class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                    Ajukan Izin / Sakit Piket
                </Button>
            </div>
            <div v-else-if="activePiketStatus && ['Izin', 'Sakit'].includes(activePiketStatus)" class="flex items-center gap-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold" :class="activePiketStatus === 'Izin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300'">
                    <CheckCircle class="w-3.5 h-3.5" />
                    Tercatat {{ activePiketStatus }} Piket Hari Ini
                </div>
            </div>
        </div>

        <!-- Banner jika guru sudah berstatus Izin / Sakit -->
        <div v-if="activePiketStatus && ['Izin', 'Sakit'].includes(activePiketStatus)" class="p-4 rounded-xl border bg-card text-card-foreground shadow-xs flex items-start gap-3" :class="activePiketStatus === 'Izin' ? 'border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20 text-blue-900 dark:text-blue-200' : 'border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 text-amber-900 dark:text-amber-200'">
            <div class="p-2 rounded-lg" :class="activePiketStatus === 'Izin' ? 'bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300' : 'bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300'">
                <UserX class="w-5 h-5" />
            </div>
            <div>
                <div class="font-bold text-sm">Status Tugas Piket Anda Hari Ini: {{ activePiketStatus }}</div>
                <p class="text-xs mt-0.5 opacity-90">Keterangan: {{ activePiketKeterangan || '-' }}</p>
                <p class="text-[11px] mt-1 opacity-75">Anda dibebaskan dari kewajiban mengisi laporan pemantauan kelas untuk jadwal ini.</p>
            </div>
        </div>

        <!-- Banner Informasi Batas Waktu Piket -->
        <div v-if="pengaturan" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-900 dark:text-emerald-200 text-xs shadow-xs">
            <div class="flex items-center gap-2">
                <Clock class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span>
                    Batas waktu pelaporan tugas piket: sampai pukul <strong>{{ pengaturan.waktu_selesai }} WIB</strong>. Pengisian setelah batas waktu memerlukan persetujuan izin susulan dari Wakasek.
                </span>
            </div>
            <div class="text-[11px] text-emerald-700 dark:text-emerald-400 font-medium pl-6 sm:pl-0 shrink-0">
                Sesi dibuka: {{ pengaturan.waktu_mulai }} WIB
            </div>
        </div>

        <!-- Desktop Table View -->
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
                                        Jam KBM: {{ formatTime(jam.waktu_mulai) }} - {{ formatTime(jam.waktu_selesai) }}
                                    </span>
                                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium ml-1">
                                        Batas Pelaporan: s/d {{ item.batas_waktu_pelaporan || pengaturan?.waktu_selesai || '16:00' }} WIB
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <!-- Jika status Izin atau Sakit -->
                                <template v-if="item.status === 'Izin' || item.status === 'Sakit'">
                                    <span 
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="item.status === 'Izin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300'"
                                    >
                                        {{ item.status }} (Bebas Lapor)
                                    </span>
                                </template>

                                <!-- Jika titik ini tidak memiliki jadwal KBM di jam tersebut -->
                                <template v-else-if="jam.has_no_classes">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap shadow-xs border border-gray-200 dark:border-slate-700">
                                        <Info class="h-3.5 w-3.5" /> Tidak Ada KBM di Titik Ini
                                    </span>
                                </template>

                                <!-- Jika semua kelas untuk jam ini sudah selesai dilaporkan -->
                                <template v-else-if="jam.is_completed">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 whitespace-nowrap shadow-xs border border-emerald-200 dark:border-emerald-800">
                                        <CheckCircle class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" /> Selesai Dilaporkan ({{ jam.total_classes }} Kelas)
                                    </span>
                                </template>
                                
                                <!-- Normal flow: Waktu masih aktif dan belum dilaporkan -->
                                <template v-else-if="getReportState(jam, item) === 'AKTIF'">
                                    <Link 
                                        :href="`/pelaporan-piket/${item.id}/create?jam_pelajaran_id=${jam.id}`" 
                                        class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 h-9 px-4 py-2 shadow-sm whitespace-nowrap bg-[#79AE6F] text-white hover:bg-[#79AE6F]/90 cursor-pointer"
                                    >
                                        <ClipboardList class="h-4 w-4 mr-2" /> Isi Laporan
                                        <span v-if="jam.unreported_classes" class="ml-1 text-xs opacity-90">({{ jam.unreported_classes }} Kelas)</span>
                                    </Link>
                                </template>

                                <!-- Sesi Ditutup / Melewati Batas Waktu -->
                                <template v-else-if="getReportState(jam, item) === 'SESI_DITUTUP'">
                                    <!-- Jika izin sudah disetujui oleh Wakasek -->
                                    <template v-if="item.has_approved_izin">
                                        <Link 
                                            :href="`/pelaporan-piket/${item.id}/create?jam_pelajaran_id=${jam.id}`" 
                                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 h-9 px-4 py-2 shadow-sm whitespace-nowrap bg-amber-500 hover:bg-amber-600 text-white cursor-pointer"
                                        >
                                            <ClipboardList class="h-4 w-4 mr-2" /> Isi Laporan Susulan
                                            <span v-if="jam.unreported_classes" class="ml-1 text-xs opacity-90">({{ jam.unreported_classes }} Kelas)</span>
                                        </Link>
                                    </template>

                                    <!-- Jika permohonan sedang menunggu persetujuan Wakasek -->
                                    <template v-else-if="item.has_pending_izin">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 px-3 py-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 whitespace-nowrap">
                                            <Hourglass class="h-3.5 w-3.5 animate-spin" /> Menunggu Persetujuan Wakasek
                                        </span>
                                    </template>

                                    <!-- Jika belum mengajukan izin susulan -->
                                    <template v-else>
                                        <Button 
                                            size="sm" 
                                            variant="outline" 
                                            @click="requestIzinSusulan(item.id)"
                                            class="border-amber-400 text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-950/50 text-xs font-semibold h-9 px-3 gap-1.5 shadow-xs cursor-pointer"
                                        >
                                            <AlertCircle class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                                            Minta Izin Susulan
                                        </Button>
                                    </template>
                                </template>
                                
                                <template v-else-if="getReportState(jam, item) === 'BELUM_MULAI'">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 dark:bg-slate-800/80 px-2.5 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap shadow-xs border border-slate-200 dark:border-slate-700">
                                        <Clock class="h-3.5 w-3.5 text-slate-500" />
                                        Belum Mulai (Mulai {{ formatTime(jam.waktu_mulai) }})
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
                        'bg-blue-500': item.status === 'Izin',
                        'bg-amber-500': item.status === 'Sakit',
                        'bg-gray-400': !item.status && jam.has_no_classes,
                        'bg-emerald-500': !item.status && jam.is_completed,
                        'bg-[#79AE6F]': !item.status && !jam.has_no_classes && !jam.is_completed && getReportState(jam, item) === 'AKTIF',
                        'bg-yellow-500': !item.status && !jam.has_no_classes && !jam.is_completed && getReportState(jam, item) === 'SESI_DITUTUP',
                        'bg-gray-300 dark:bg-slate-600': !item.status && !jam.has_no_classes && !jam.is_completed && getReportState(jam, item) === 'BELUM_MULAI'
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
                            Jam KBM
                        </span>
                        <span class="font-medium text-right">{{ formatTime(jam.waktu_mulai) }} - {{ formatTime(jam.waktu_selesai) }}</span>
                    </div>

                    <div class="flex justify-between items-center text-xs ml-2 mt-0.5 text-emerald-600 dark:text-emerald-400 font-medium">
                        <span>Batas Pelaporan Piket:</span>
                        <span>s/d {{ item.batas_waktu_pelaporan || pengaturan?.waktu_selesai || '16:00' }} WIB</span>
                    </div>
                    
                    <div class="mt-3 ml-2 pt-3 border-t border-gray-100 dark:border-slate-800/80">
                        <template v-if="item.status === 'Izin' || item.status === 'Sakit'">
                            <div 
                                class="flex items-center justify-center w-full rounded-xl py-2 px-3 text-xs font-semibold"
                                :class="item.status === 'Izin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200'"
                            >
                                Status: {{ item.status }} (Bebas Lapor)
                            </div>
                        </template>

                        <template v-else-if="jam.has_no_classes">
                            <div class="flex items-center justify-center gap-1.5 w-full rounded-xl bg-gray-50 dark:bg-slate-800 px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-slate-700">
                                <Info class="h-3.5 w-3.5" /> Tidak Ada KBM di Titik Ini
                            </div>
                        </template>

                        <template v-else-if="jam.is_completed">
                            <div class="flex items-center justify-center gap-1.5 w-full rounded-xl bg-emerald-50 dark:bg-emerald-950/40 px-4 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <CheckCircle class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" /> Selesai Dilaporkan ({{ jam.total_classes }} Kelas)
                            </div>
                        </template>

                        <template v-else-if="getReportState(jam, item) === 'AKTIF'">
                            <Link 
                                :href="`/pelaporan-piket/${item.id}/create?jam_pelajaran_id=${jam.id}`" 
                                class="flex items-center justify-center w-full rounded-xl text-sm font-semibold transition-colors h-10 px-4 py-2 shadow-sm bg-[#79AE6F] text-white hover:bg-[#79AE6F]/90 cursor-pointer"
                            >
                                <ClipboardList class="h-4 w-4 mr-2" /> Isi Laporan Sekarang
                                <span v-if="jam.unreported_classes" class="ml-1 text-xs opacity-90">({{ jam.unreported_classes }} Kelas)</span>
                            </Link>
                        </template>

                        <template v-else-if="getReportState(jam, item) === 'SESI_DITUTUP'">
                            <template v-if="item.has_approved_izin">
                                <Link 
                                    :href="`/pelaporan-piket/${item.id}/create?jam_pelajaran_id=${jam.id}`" 
                                    class="flex items-center justify-center w-full rounded-xl text-sm font-semibold transition-colors h-10 px-4 py-2 shadow-sm bg-amber-500 hover:bg-amber-600 text-white cursor-pointer"
                                >
                                    <ClipboardList class="h-4 w-4 mr-2" /> Isi Laporan Susulan
                                    <span v-if="jam.unreported_classes" class="ml-1 text-xs opacity-90">({{ jam.unreported_classes }} Kelas)</span>
                                </Link>
                            </template>
                            <template v-else-if="item.has_pending_izin">
                                <div class="flex items-center justify-center gap-1.5 w-full rounded-xl bg-amber-50 dark:bg-amber-950/40 px-4 py-2 text-xs font-semibold text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    <Hourglass class="h-3.5 w-3.5 animate-spin" /> Menunggu Persetujuan Wakasek
                                </div>
                            </template>
                            <template v-else>
                                <Button 
                                    variant="outline" 
                                    @click="requestIzinSusulan(item.id)"
                                    class="flex items-center justify-center w-full rounded-xl text-xs font-semibold h-10 border-amber-400 text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-950/50 shadow-xs cursor-pointer gap-1.5"
                                >
                                    <AlertCircle class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                    Waktu Habis - Minta Izin Susulan
                                </Button>
                            </template>
                        </template>
                        
                        <template v-else-if="getReportState(jam, item) === 'BELUM_MULAI'">
                            <div class="flex items-center justify-center gap-1.5 w-full rounded-xl bg-slate-100 dark:bg-slate-800/80 px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-xs">
                                <Clock class="h-3.5 w-3.5 text-slate-500" />
                                Belum Mulai (Mulai {{ formatTime(jam.waktu_mulai) }} WIB)
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

        <!-- Modal Dialog Pengajuan Izin/Sakit -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <AlertCircle class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        Pengajuan Izin / Sakit Tugas Piket
                    </DialogTitle>
                    <DialogDescription>
                        Pilih status ketidakhadiran dan berikan keterangan/alasan Anda untuk tugas piket hari ini.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Status Kehadiran</Label>
                        <div class="grid grid-cols-2 gap-3">
                            <button 
                                type="button"
                                @click="formIzin.status = 'Izin'"
                                class="flex items-center justify-center gap-2 p-3 rounded-xl border text-sm font-semibold transition-all cursor-pointer"
                                :class="formIzin.status === 'Izin' ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 ring-2 ring-blue-400/30' : 'border-border hover:bg-muted/50'"
                            >
                                <span class="w-3 h-3 rounded-full border-2" :class="formIzin.status === 'Izin' ? 'border-blue-600 bg-blue-600' : 'border-muted-foreground'"></span>
                                Izin
                            </button>
                            <button 
                                type="button"
                                @click="formIzin.status = 'Sakit'"
                                class="flex items-center justify-center gap-2 p-3 rounded-xl border text-sm font-semibold transition-all cursor-pointer"
                                :class="formIzin.status === 'Sakit' ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 ring-2 ring-amber-400/30' : 'border-border hover:bg-muted/50'"
                            >
                                <span class="w-3 h-3 rounded-full border-2" :class="formIzin.status === 'Sakit' ? 'border-amber-600 bg-amber-600' : 'border-muted-foreground'"></span>
                                Sakit
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="alasan-izin" class="text-sm font-medium">Keterangan / Alasan</Label>
                        <Textarea 
                            id="alasan-izin"
                            v-model="formIzin.keterangan" 
                            @input="keteranganError = ''"
                            placeholder="Contoh: Sakit demam, ada keperluan dinas luar, dll."
                            rows="4"
                            class="resize-none"
                            :class="keteranganError ? 'border-red-500 ring-1 ring-red-500' : ''"
                        />
                        <p v-if="keteranganError" class="text-xs text-red-500 font-medium">{{ keteranganError }}</p>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="isModalOpen = false" :disabled="formIzin.processing">
                        Batal
                    </Button>
                    <Button 
                        type="button" 
                        @click="submitIzinSakit" 
                        :disabled="formIzin.processing || !formIzin.keterangan.trim()"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white"
                    >
                        {{ formIzin.processing ? 'Menyimpan...' : 'Ajukan Permohonan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
