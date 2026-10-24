<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { Users, BookOpen, GraduationCap, School, ClipboardCheck, Calendar, Clock, Activity, Camera, Percent, AlertTriangle, BellRing, Folder, ExternalLink, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    counts?: {
        users: number;
        kelas: number;
        mata_pelajaran: number;
        jurusan: number;
        jadwal_piket: number;
        jadwal_gms: number;
    };
    recentUsers?: Array<{
        id: number;
        name: string;
        email: string;
        last_activity: number;
    }>;
    recentPiket?: Array<any>;
    recentGms?: Array<any>;
    kepsekData?: {
        statistik: {
            total_guru: number;
            persentase_kehadiran: number;
            sp_bulan_ini: number;
        };
        chart: {
            labels: string[];
            data: number[];
        };
    };
    wakasekData?: {
        gms_hari_ini: number;
        status_piket: {
            total: number;
            masuk: number;
        };
        pratinjau_gms: Array<any>;
        pratinjau_piket: Array<any>;
        notifikasi_evaluasi: number;
    };
    hasJadwalPiketHariIni?: boolean;
    hasJadwalGmsHariIni?: boolean;
    user?: any;
    myDrive?: {
        id: number;
        google_drive_url: string;
        keterangan?: string;
    } | null;
    observedDrives?: Array<{
        id: number;
        google_drive_url: string;
        keterangan?: string;
        guru: {
            id: number;
            name: string;
            email: string;
        };
    }>;
}>();

import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title as ChartTitle,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ChartTitle,
  Tooltip,
  Legend,
  Filler
);

const chartData = computed(() => ({
  labels: props.kepsekData?.chart.labels || [],
  datasets: [
    {
      label: 'Jumlah Alpha (GMS & Piket)',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      borderColor: 'rgba(239, 68, 68, 1)',
      borderWidth: 2,
      data: props.kepsekData?.chart.data || [],
      fill: true,
      tension: 0.4
    }
  ]
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1
      }
    }
  }
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();

const getInitials = (name: string) => {
    if (!name) return '';
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

const currentDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date());
});

const jadwal_piket_hari_ini = computed(() => {
    return props.hasJadwalPiketHariIni ?? false;
});

const has_jadwal_gms_hari_ini = computed(() => {
    return props.hasJadwalGmsHariIni ?? false;
});

const baseCards = computed(() => [
    { title: 'Users', count: props.counts?.users || 0, icon: Users, href: '/users', color: 'bg-emerald-500' },
    { title: 'Kelas', count: props.counts?.kelas || 0, icon: School, href: '/kelas', color: 'bg-green-500' },
    { title: 'Mata Pelajaran', count: props.counts?.mata_pelajaran || 0, icon: BookOpen, href: '/mata-pelajaran', color: 'bg-teal-500' },
    { title: 'Jurusan', count: props.counts?.jurusan || 0, icon: GraduationCap, href: '/jurusan', color: 'bg-lime-500' },
    { title: 'Data Jadwal Piket', count: props.counts?.jadwal_piket || 0, icon: ClipboardCheck, href: '/jadwal-piket', color: 'bg-green-600' },
    { title: 'Data Jadwal GMS', count: props.counts?.jadwal_gms || 0, icon: Calendar, href: '/jadwal-gms', color: 'bg-emerald-600' },
]);

const adminCards = computed(() => baseCards.value);

const superAdminCards = computed(() => [
    ...baseCards.value,
    { title: 'Laporan Piket Masuk', count: props.counts?.laporan_piket || 0, icon: ClipboardCheck, href: '/laporan/piket', color: 'bg-teal-600' },
    { title: 'Laporan GMS Masuk', count: props.counts?.laporan_gms || 0, icon: Camera, href: '/laporan/gms', color: 'bg-lime-600' },
]);
</script>

<template>
    <Head title="Dashboard" />

    <template v-if="$page.props.auth.user.role === 'super admin'">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 p-4 md:p-6 max-w-[1600px] mx-auto w-full">
            <!-- Area Kiri (Banner + Cards) -->
            <div class="flex-1 space-y-8 lg:w-2/3">
                
                <!-- Hero Banner -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-500 to-teal-400 shadow-xl border border-emerald-400/20">
                    <div class="absolute inset-0 bg-white/10 opacity-20 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                    <div class="relative flex items-center justify-between p-6 md:p-8 lg:px-10 lg:py-8">
                        <div class="text-white space-y-2 lg:space-y-3">
                            <h1 class="text-3xl font-bold tracking-tight md:text-4xl">Halo, {{ $page.props.auth.user.name }} 👋</h1>
                            <p class="text-emerald-50 font-medium opacity-90">{{ currentDate }}</p>
                            <p class="text-sm text-emerald-50 max-w-md mt-2 leading-relaxed">
                                Selamat datang di panel kontrol Simonitorguru. Pantau aktivitas terkini dan kelola data master dengan mudah dari sini.
                            </p>
                        </div>
                        <div class="hidden sm:block right-0 transform translate-x-4">
                            <img src="/images/iconedu.png" alt="Edu Icon" class="w-40 h-40 md:w-48 md:h-48 object-contain drop-shadow-2xl" onerror="this.style.display='none'">
                        </div>
                    </div>
                </div>

                <!-- Bento Grid Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                    <Link 
                        v-for="card in superAdminCards" 
                        :key="card.title" 
                        :href="card.href"
                        class="group relative overflow-hidden rounded-2xl bg-card border border-sidebar-border/70 p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:-translate-y-1 hover:border-emerald-500/30"
                    >
                        <div class="relative z-10 flex flex-col h-full justify-between gap-4">
                            <div class="flex items-start justify-between">
                                <div class="mt-1">
                                    <p class="text-sm font-semibold text-muted-foreground">{{ card.title }}</p>
                                    <h3 class="text-4xl font-bold tracking-tight text-slate-800 dark:text-slate-100 mt-2">{{ card.count }}</h3>
                                </div>
                                <div class="p-2 rounded-xl text-white shadow-sm" :class="card.color">
                                    <component :is="card.icon" class="w-5 h-5" />
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            <!-- Pintasan Cepat -->
                <div class="mt-8">
                    <h2 class="text-xl font-bold tracking-tight mb-4 text-foreground">Pintasan Cepat</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <Link href="/jadwal-mapel" class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-sidebar-border/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-500/30 transition-all">
                            <div class="p-2.5 rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-400">
                                <Calendar class="w-5 h-5" />
                            </div>
                            <span class="font-semibold text-sm">Jadwal Mapel</span>
                        </Link>
                        <Link href="/jadwal-piket" class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-sidebar-border/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-500/30 transition-all">
                            <div class="p-2.5 rounded-lg bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400">
                                <ClipboardCheck class="w-5 h-5" />
                            </div>
                            <span class="font-semibold text-sm">Jadwal Piket</span>
                        </Link>
                        <Link href="/jadwal-gms" class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-sidebar-border/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-500/30 transition-all">
                            <div class="p-2.5 rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                                <Clock class="w-5 h-5" />
                            </div>
                            <span class="font-semibold text-sm">Jadwal GMS</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Panel Vertikal (Kanan) -->
            <div class="flex flex-col gap-6 lg:w-1/3">
                
                <!-- Pengguna Aktif (Baru Login) -->
                <div class="rounded-2xl border border-sidebar-border/70 bg-card shadow-sm p-5 shrink-0">
                    <div class="flex items-center gap-2 mb-4">
                        <Activity class="w-5 h-5 text-emerald-500" />
                        <h2 class="font-semibold text-lg">Aktivitas Guru Terbaru</h2>
                    </div>
                    <div v-if="recentUsers && recentUsers.length > 0" class="flex flex-wrap gap-3">
                        <div 
                            v-for="user in recentUsers" 
                            :key="user.id" 
                            class="group relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold border-2 border-white shadow-sm ring-2 ring-transparent transition-all hover:ring-emerald-200 cursor-pointer dark:bg-emerald-900/40 dark:border-slate-800 dark:text-emerald-400 text-sm"
                            :title="user.name"
                        >
                            {{ getInitials(user.name) }}
                        </div>
                    </div>
                    <div v-else class="text-sm text-muted-foreground italic text-center py-2">
                        Belum ada aktivitas login terbaru.
                    </div>
                </div>

                <!-- Log Absensi Piket Kelas -->
                <div class="rounded-2xl border border-sidebar-border/70 bg-card shadow-sm p-0 overflow-hidden flex flex-col h-[350px]">
                    <div class="p-4 border-b border-sidebar-border/50 bg-slate-50/50 dark:bg-slate-900/20">
                        <div class="flex items-center gap-2">
                            <ClipboardCheck class="w-5 h-5 text-teal-500" />
                            <h2 class="font-semibold text-lg">Log Absensi Piket</h2>
                        </div>
                    </div>
                    <div class="p-0 overflow-y-auto flex-1 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                        <div v-if="recentPiket && recentPiket.length > 0" class="divide-y divide-sidebar-border/50">
                            <div v-for="log in recentPiket" :key="log.id" class="px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="font-medium text-sm">{{ log.jadwal_piket?.guru_piket?.name || 'Unknown' }}</span>
                                    <span class="text-[10px] bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-md text-muted-foreground">{{ formatDate(log.created_at) }}</span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Jadwal: {{ log.jadwal_piket?.tanggal_piket }} ({{ log.jadwal_piket?.titik?.nama_titik || 'N/A' }}) - <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ log.status_kehadiran }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center text-sm text-muted-foreground italic flex flex-col items-center gap-2">
                            <ClipboardCheck class="w-8 h-8 opacity-20" />
                            Belum ada log absensi piket.
                        </div>
                    </div>
                </div>

                <!-- Log Absensi GMS -->
                <div class="rounded-2xl border border-sidebar-border/70 bg-card shadow-sm p-0 overflow-hidden flex flex-col h-[350px]">
                    <div class="p-4 border-b border-sidebar-border/50 bg-slate-50/50 dark:bg-slate-900/20">
                        <div class="flex items-center gap-2">
                            <Camera class="w-5 h-5 text-lime-500" />
                            <h2 class="font-semibold text-lg">Log Absensi GMS</h2>
                        </div>
                    </div>
                    <div class="p-0 overflow-y-auto flex-1 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                        <div v-if="recentGms && recentGms.length > 0" class="divide-y divide-sidebar-border/50">
                            <div v-for="log in recentGms" :key="log.id" class="px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors flex items-center gap-3">
                                <div v-if="log.foto_bukti" class="h-9 w-9 shrink-0 overflow-hidden rounded-md border shadow-sm">
                                    <img :src="`/storage/${log.foto_bukti}`" class="h-full w-full object-cover" alt="Bukti">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm truncate">{{ log.user?.name || 'Unknown' }}</div>
                                    <div class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                                        <Clock class="w-3 h-3" /> {{ log.waktu_absen }}
                                    </div>
                                </div>
                                <div class="text-[10px] bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-md text-muted-foreground whitespace-nowrap">
                                    {{ new Date(log.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center text-sm text-muted-foreground italic flex flex-col items-center gap-2">
                            <Camera class="w-8 h-8 opacity-20" />
                            Belum ada log absensi GMS.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </template>

    <template v-else-if="$page.props.auth.user.role === 'admin'">
        <div class="flex flex-col gap-6 lg:gap-8 p-4 md:p-6 max-w-[1600px] mx-auto w-full">
            <!-- Area Utama (Full Width untuk Admin) -->
            <div class="flex-1 space-y-8 w-full">
                
                <!-- Hero Banner -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-500 to-teal-400 shadow-xl border border-emerald-400/20">
                    <div class="absolute inset-0 bg-white/10 opacity-20 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                    <div class="relative flex items-center justify-between p-6 md:p-8 lg:px-10 lg:py-8">
                        <div class="text-white space-y-2 lg:space-y-3">
                            <h1 class="text-3xl font-bold tracking-tight md:text-4xl">Halo, {{ $page.props.auth.user.name }} 👋</h1>
                            <p class="text-emerald-50 font-medium opacity-90">{{ currentDate }}</p>
                            <p class="text-sm text-emerald-50 max-w-md mt-2 leading-relaxed">
                                Selamat datang di panel Administrator Simonitorguru. Kelola data master dan atur jadwal kegiatan akademik dengan mudah dari sini.
                            </p>
                        </div>
                        <div class="hidden sm:block right-0 transform translate-x-4">
                            <img src="/images/iconedu.png" alt="Edu Icon" class="w-40 h-40 md:w-48 md:h-48 object-contain drop-shadow-2xl" onerror="this.style.display='none'">
                        </div>
                    </div>
                </div>

                <!-- Bento Grid Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-5">
                    <Link 
                        v-for="card in adminCards" 
                        :key="card.title" 
                        :href="card.href"
                        class="group relative overflow-hidden rounded-2xl bg-card border border-sidebar-border/70 p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:-translate-y-1 hover:border-emerald-500/30"
                    >
                        <div class="relative z-10 flex flex-col h-full justify-between gap-4">
                            <div class="flex items-start justify-between">
                                <div class="mt-1">
                                    <p class="text-sm font-semibold text-muted-foreground">{{ card.title }}</p>
                                    <h3 class="text-4xl font-bold tracking-tight text-slate-800 dark:text-slate-100 mt-2">{{ card.count }}</h3>
                                </div>
                                <div class="p-2 rounded-xl text-white shadow-sm" :class="card.color">
                                    <component :is="card.icon" class="w-5 h-5" />
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
                
                <!-- Pintasan Cepat -->
                <div class="mt-8">
                    <h2 class="text-xl font-bold tracking-tight mb-4 text-foreground">Pintasan Cepat Jadwal</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <Link href="/jadwal-mapel" class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-sidebar-border/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-500/30 transition-all">
                            <div class="p-2.5 rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-400">
                                <Calendar class="w-5 h-5" />
                            </div>
                            <span class="font-semibold text-sm">Jadwal Mapel</span>
                        </Link>
                        <Link href="/jadwal-piket" class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-sidebar-border/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-500/30 transition-all">
                            <div class="p-2.5 rounded-lg bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400">
                                <ClipboardCheck class="w-5 h-5" />
                            </div>
                            <span class="font-semibold text-sm">Jadwal Piket</span>
                        </Link>
                        <Link href="/jadwal-gms" class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-sidebar-border/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-500/30 transition-all">
                            <div class="p-2.5 rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                                <Clock class="w-5 h-5" />
                            </div>
                            <span class="font-semibold text-sm">Jadwal GMS</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template v-else-if="$page.props.auth.user.role === 'wakasek'">
        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-[1600px] mx-auto w-full">
            <!-- Hero Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-500 to-teal-400 shadow-xl border border-emerald-400/20">
                <div class="absolute inset-0 bg-white/10 opacity-20 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                <div class="relative flex items-center justify-between p-6 md:p-10">
                    <div class="text-white space-y-2">
                        <h1 class="text-3xl font-bold tracking-tight md:text-4xl">Halo, {{ $page.props.auth.user.name }} 👋</h1>
                        <p class="text-emerald-50 font-medium opacity-90">{{ currentDate }}</p>
                        <p class="text-sm text-emerald-50 max-w-md mt-2 leading-relaxed">
                            Selamat datang di panel Wakasek. Pantau kedisiplinan dan pelaporan harian dari dashboard ini.
                        </p>
                    </div>
                    <div class="hidden sm:block right-0 transform translate-x-4">
                        <img src="/images/iconedu.png" alt="Edu Icon" class="w-32 h-32 md:w-40 md:h-40 object-contain drop-shadow-2xl" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>

            <!-- Kartu Statistik Wakasek -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-2">
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-lime-100 text-lime-600 dark:bg-lime-900/30 dark:text-lime-400">
                            <Camera class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Absensi GMS Masuk</p>
                            <h3 class="text-2xl font-bold mt-1">{{ wakasekData?.gms_hari_ini || 0 }} <span class="text-xs font-normal text-muted-foreground">Hari ini</span></h3>
                        </div>
                    </div>
                </div>
                
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <ClipboardCheck class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Laporan Piket</p>
                            <h3 class="text-2xl font-bold mt-1">{{ wakasekData?.status_piket?.masuk || 0 }} / {{ wakasekData?.status_piket?.total || 0 }} <span class="text-xs font-normal text-muted-foreground">Selesai</span></h3>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                            <BellRing class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Notifikasi Evaluasi</p>
                            <h3 class="text-2xl font-bold mt-1">{{ wakasekData?.notifikasi_evaluasi || 0 }} <span class="text-xs font-normal text-muted-foreground">Butuh Tindakan</span></h3>
                        </div>
                    </div>
                </div>
                
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm flex flex-col justify-center gap-2">
                    <Link href="/laporan/evaluasi" class="w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-emerald-600 text-white hover:bg-emerald-700 h-10 px-4 py-2">
                        Buka Evaluasi
                    </Link>
                </div>
            </div>

            <!-- Pratinjau Laporan -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-2">
                <!-- Log Absensi GMS -->
                <div class="rounded-2xl border border-sidebar-border/70 bg-card shadow-sm p-0 overflow-hidden flex flex-col h-[400px]">
                    <div class="p-4 border-b border-sidebar-border/50 bg-slate-50/50 dark:bg-slate-900/20 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <Camera class="w-5 h-5 text-lime-500" />
                            <h2 class="font-semibold text-lg">Pratinjau GMS</h2>
                        </div>
                        <Link href="/laporan/gms" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Lihat Semua</Link>
                    </div>
                    <div class="p-0 overflow-y-auto flex-1">
                        <div v-if="wakasekData?.pratinjau_gms?.length" class="divide-y divide-sidebar-border/50">
                            <div v-for="log in wakasekData.pratinjau_gms" :key="log.id" class="px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors flex items-center gap-4">
                                <div v-if="log.foto_bukti" class="h-10 w-10 shrink-0 overflow-hidden rounded-md border shadow-sm">
                                    <img :src="`/storage/${log.foto_bukti}`" class="h-full w-full object-cover" alt="Bukti">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm truncate">{{ log.user?.name || 'Unknown' }}</div>
                                    <div class="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                                        <Clock class="w-3.5 h-3.5" /> {{ log.waktu_absen }}
                                    </div>
                                </div>
                                <div class="px-2.5 py-1 rounded-md text-xs font-medium" :class="log.status === 'Alpha' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'">
                                    {{ log.status }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center text-sm text-muted-foreground italic">
                            Belum ada laporan GMS terbaru.
                        </div>
                    </div>
                </div>

                <!-- Log Absensi Piket -->
                <div class="rounded-2xl border border-sidebar-border/70 bg-card shadow-sm p-0 overflow-hidden flex flex-col h-[400px]">
                    <div class="p-4 border-b border-sidebar-border/50 bg-slate-50/50 dark:bg-slate-900/20 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <ClipboardCheck class="w-5 h-5 text-teal-500" />
                            <h2 class="font-semibold text-lg">Pratinjau Piket</h2>
                        </div>
                        <Link href="/laporan/piket" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Lihat Semua</Link>
                    </div>
                    <div class="p-0 overflow-y-auto flex-1">
                        <div v-if="wakasekData?.pratinjau_piket?.length" class="divide-y divide-sidebar-border/50">
                            <div v-for="log in wakasekData.pratinjau_piket" :key="log.id" class="px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                <div class="flex justify-between items-start mb-1.5">
                                    <span class="font-medium text-sm">{{ log.jadwal_piket?.guru_piket?.name || 'Unknown' }}</span>
                                    <span class="text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-md text-muted-foreground">{{ formatDate(log.created_at) }}</span>
                                </div>
                                <div class="text-sm text-muted-foreground flex justify-between">
                                    <span>{{ log.jadwal_piket?.titik?.nama_titik || 'N/A' }}</span>
                                    <span class="font-medium" :class="log.status_kehadiran === 'Alpha' ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">{{ log.status_kehadiran }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center text-sm text-muted-foreground italic">
                            Belum ada laporan Piket terbaru.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template v-else-if="$page.props.auth.user.role === 'guru'">
        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-5xl mx-auto w-full mt-4">
            <!-- Hero Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-500 to-teal-400 shadow-xl border border-emerald-400/20">
                <div class="absolute inset-0 bg-white/10 opacity-20 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                <div class="relative flex items-center justify-between p-6 md:p-10">
                    <div class="text-white space-y-2">
                        <h1 class="text-3xl font-bold tracking-tight md:text-4xl">Halo, {{ $page.props.auth.user.name }} 👋</h1>
                        <p class="text-emerald-50 font-medium opacity-90">{{ currentDate }}</p>
                        <p class="text-sm text-emerald-50 max-w-md mt-2 leading-relaxed">
                            Selamat datang di aplikasi Simonitorguru. 
                            Anda memiliki tugas piket {{ jadwal_piket_hari_ini ? 'hari ini' : 'besok' }} dan jadwal GMS {{ has_jadwal_gms_hari_ini ? 'hari ini' : 'besok' }}.
                        </p>
                        <div v-if="myDrive?.google_drive_url" class="pt-3">
                            <a 
                                :href="myDrive.google_drive_url" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-white/20 hover:bg-white/30 text-white font-semibold text-sm backdrop-blur-md transition-all shadow-md hover:scale-[1.02] border border-white/30 group"
                            >
                                <Folder class="w-4 h-4 text-emerald-100 group-hover:scale-110 transition-transform" />
                                <span>Pintasan Google Drive Saya</span>
                                <ExternalLink class="w-3.5 h-3.5 opacity-80" />
                            </a>
                        </div>
                    </div>
                    <div class="hidden sm:block right-0 transform translate-x-4">
                        <img src="/images/iconedu.png" alt="Edu Icon" class="w-32 h-32 md:w-40 md:h-40 object-contain drop-shadow-2xl" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                <!-- Widget GMS (Kondisional) -->
                <div v-if="user?.tipe_guru?.akses_gms" class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="w-12 h-12 bg-lime-100 text-lime-600 rounded-xl flex items-center justify-center mb-4 dark:bg-lime-900/30 dark:text-lime-400">
                            <Camera class="w-6 h-6" />
                        </div>
                        <h2 class="text-xl font-bold mb-2">Absensi GMS</h2>
                        <p class="text-sm text-muted-foreground mb-6">Lakukan absensi Guru Mata Sekolah Anda untuk hari ini. Pastikan mengambil foto di lokasi yang tepat.</p>
                    </div>
                    <Link href="/absensi-gms/create" class="w-full inline-flex items-center justify-center whitespace-nowrap rounded-lg text-sm font-medium transition-colors bg-emerald-600 text-white hover:bg-emerald-700 h-11 px-8">
                        Ambil Absen GMS
                    </Link>
                </div>
                
                <!-- Widget Piket (Kondisional) -->
                <div v-if="jadwal_piket_hari_ini" class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm border-emerald-500/50 bg-emerald-50/50 dark:bg-emerald-950/20 flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center dark:bg-emerald-900/30 dark:text-emerald-400">
                                <ClipboardCheck class="w-6 h-6" />
                            </div>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full dark:bg-emerald-900/50 dark:text-emerald-400">Hari ini</span>
                        </div>
                        <h2 class="text-xl font-bold mb-2">Tugas Piket Hari Ini</h2>
                        <p class="text-sm text-muted-foreground mb-6">Anda memiliki jadwal untuk mengontrol titik piket hari ini. Segera lakukan pelaporan kehadiran kelas.</p>
                    </div>
                    <Link href="/pelaporan-piket" class="w-full inline-flex items-center justify-center whitespace-nowrap rounded-lg text-sm font-medium transition-colors border-2 border-emerald-600 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 h-11 px-8">
                        Buka Laporan Piket
                    </Link>
                </div>
            </div>

            <!-- Widget Google Drive Kinerja Guru -->
            <div class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm hover:shadow-md transition-all" :class="myDrive?.google_drive_url ? 'border-blue-200 dark:border-blue-900/50 bg-gradient-to-r from-blue-50/30 via-card to-card dark:from-blue-950/20' : ''">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center dark:bg-blue-950/60 dark:text-blue-400 shrink-0 shadow-sm">
                            <Folder class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-bold text-foreground">Folder Google Drive Kinerja Saya</h2>
                                <span v-if="myDrive?.google_drive_url" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    Aktif & Diizinkan
                                </span>
                                <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                    Dalam Persiapan
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground mt-1">
                                Folder resmi dari Wakasek Kurikulum untuk pengunggahan Modul Ajar, Perangkat Pembelajaran, dan Video Rekaman Observasi Praktik Mengajar.
                            </p>
                            <div v-if="myDrive?.keterangan" class="mt-2.5 p-2.5 rounded-lg bg-blue-50/60 dark:bg-blue-950/20 text-xs text-blue-900 dark:text-blue-300 border border-blue-100 dark:border-blue-900">
                                <strong>Catatan Kurikulum:</strong> {{ myDrive.keterangan }}
                            </div>
                        </div>
                    </div>
                    <div class="shrink-0 flex items-center">
                        <a 
                            v-if="myDrive?.google_drive_url" 
                            :href="myDrive.google_drive_url" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="w-full md:w-auto inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-semibold transition-all bg-blue-600 text-white hover:bg-blue-700 h-11 px-6 shadow-sm hover:shadow"
                        >
                            <span>Buka Google Drive Saya</span>
                            <ExternalLink class="w-4 h-4" />
                        </a>
                        <div v-else class="text-xs text-muted-foreground italic px-3 py-2 bg-muted rounded-lg">
                            Tautan drive belum disediakan atau belum diizinkan oleh pihak Kurikulum
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pintasan Cepat Guru -->
            <div>
                <h2 class="text-lg font-bold tracking-tight mb-3 text-foreground">Pintasan Cepat</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <a 
                        v-if="myDrive?.google_drive_url"
                        :href="myDrive.google_drive_url" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-blue-200/80 dark:border-blue-900/50 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-blue-500/50 transition-all group"
                    >
                        <div class="p-2.5 rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400 group-hover:scale-105 transition-transform">
                            <Folder class="w-5 h-5" />
                        </div>
                        <div class="min-w-0">
                            <span class="font-semibold text-sm block truncate">Google Drive Saya</span>
                            <span class="text-xs text-blue-600 dark:text-blue-400 font-medium flex items-center gap-1">Buka Tautan <ExternalLink class="w-3 h-3" /></span>
                        </div>
                    </a>

                    <Link href="/kinerja" class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-sidebar-border/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-500/30 transition-all group">
                        <div class="p-2.5 rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                            <ClipboardCheck class="w-5 h-5" />
                        </div>
                        <div class="min-w-0">
                            <span class="font-semibold text-sm block truncate">Kinerja Guru</span>
                            <span class="text-xs text-muted-foreground">Observasi & Tahapan</span>
                        </div>
                    </Link>

                    <Link href="/jadwal-mapel" class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-sidebar-border/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-teal-500/30 transition-all group">
                        <div class="p-2.5 rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-400 group-hover:scale-105 transition-transform">
                            <Calendar class="w-5 h-5" />
                        </div>
                        <div class="min-w-0">
                            <span class="font-semibold text-sm block truncate">Jadwal Mapel</span>
                            <span class="text-xs text-muted-foreground">Jadwal Mengajar</span>
                        </div>
                    </Link>

                    <Link href="/pelaporan-piket" class="flex items-center gap-3 p-4 rounded-2xl bg-card border border-sidebar-border/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-amber-500/30 transition-all group">
                        <div class="p-2.5 rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400 group-hover:scale-105 transition-transform">
                            <Clock class="w-5 h-5" />
                        </div>
                        <div class="min-w-0">
                            <span class="font-semibold text-sm block truncate">Laporan Piket</span>
                            <span class="text-xs text-muted-foreground">Kontrol & Absensi</span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Widget Drive Guru Binaaan untuk Observer -->
            <div v-if="observedDrives && observedDrives.length > 0" class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-border/60 pb-3">
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="w-5 h-5 text-emerald-600" />
                        <h3 class="font-bold text-base text-foreground">Drive Pembelajaran Guru Binaaan (Observer)</h3>
                    </div>
                    <span class="text-xs text-muted-foreground">{{ observedDrives.length }} Guru Binaaan</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="obs in observedDrives" :key="obs.id" class="p-3.5 rounded-xl border border-border bg-muted/20 flex flex-col justify-between gap-3">
                        <div>
                            <p class="font-bold text-sm text-foreground">{{ obs.guru?.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ obs.guru?.email }}</p>
                        </div>
                        <a 
                            :href="obs.google_drive_url" 
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-1.5 text-xs font-semibold py-2 px-3 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 transition-colors"
                        >
                            <Folder class="w-3.5 h-3.5" />
                            <span>Buka Folder Drive</span>
                            <ExternalLink class="w-3 h-3" />
                        </a>
                    </div>
                </div>
            </div>
            
        </div>
    </template>

    <template v-else-if="$page.props.auth.user.role === 'kepsek'">
        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-6xl mx-auto w-full mt-4">
            <!-- Hero Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-500 to-teal-400 shadow-xl border border-emerald-400/20">
                <div class="absolute inset-0 bg-white/10 opacity-20 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                <div class="relative flex items-center justify-between p-6 md:p-10">
                    <div class="text-white space-y-2">
                        <h1 class="text-3xl font-bold tracking-tight md:text-4xl">Selamat Datang, {{ $page.props.auth.user.name }} 👋</h1>
                        <p class="text-emerald-50 font-medium opacity-90">{{ currentDate }}</p>
                        <p class="text-sm text-emerald-50 max-w-md mt-2 leading-relaxed">
                            Pantau tingkat kedisiplinan dan tinjau rekapitulasi pelaporan secara berkala melalui Dasbor Kepala Sekolah.
                        </p>
                    </div>
                    <div class="hidden sm:block right-0 transform translate-x-4">
                        <img src="/images/iconedu.png" alt="Edu Icon" class="w-32 h-32 md:w-40 md:h-40 object-contain drop-shadow-2xl" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>

            <!-- Kartu Statistik Makro -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Guru -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <Users class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Tenaga Pendidik</p>
                            <h3 class="text-2xl font-bold mt-1">{{ kepsekData?.statistik.total_guru }} <span class="text-sm font-normal text-muted-foreground">Guru</span></h3>
                        </div>
                    </div>
                </div>

                <!-- Persentase Kehadiran -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <Percent class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Persentase Kehadiran (GMS)</p>
                            <h3 class="text-2xl font-bold mt-1">{{ kepsekData?.statistik.persentase_kehadiran }}% <span class="text-sm font-normal text-muted-foreground">Bulan ini</span></h3>
                        </div>
                    </div>
                </div>

                <!-- Total SP -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Surat Peringatan (SP)</p>
                            <h3 class="text-2xl font-bold mt-1">{{ kepsekData?.statistik.sp_bulan_ini }} <span class="text-sm font-normal text-muted-foreground">Terkirim bulan ini</span></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafik Tren -->
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-lg font-bold">Tren Indisipliner (6 Bulan Terakhir)</h2>
                    <p class="text-sm text-muted-foreground">Grafik akumulasi absensi 'Alpha' pada GMS dan Piket.</p>
                </div>
                <div class="h-[300px] w-full">
                    <Line :data="chartData" :options="chartOptions" />
                </div>
            </div>
        </div>
    </template>
</template>

