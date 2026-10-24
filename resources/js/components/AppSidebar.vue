<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

import { Users, GraduationCap, School, MapPin, Clock, Calendar, ClipboardCheck, Briefcase, Camera, Settings, Activity, ShieldCheck, Folder, Award, CheckCircle2, AlertTriangle, Smartphone } from '@lucide/vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePwaInstall } from '@/composables/usePwaInstall';

const { installPwa } = usePwaInstall();

const page = usePage<any>();

const utamaItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
]);

const harianItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (['guru', 'wakasek'].includes(page.props.auth?.user?.role)) {
        const tipeGuru = page.props.auth?.user?.tipe_guru;
        
        if (tipeGuru?.akses_piket_kelas) {
            items.push({
                title: 'Tugas Piket Hari Ini',
                href: '/pelaporan-piket' as any,
                icon: ClipboardCheck,
            });
        }
        
        if (tipeGuru?.akses_gms) {
            items.push({
                title: 'Absensi GMS',
                href: '/absensi-gms/create' as any,
                icon: Camera,
            });
        }
    }

    // Menu Observasi Kinerja (Untuk Observer, Guru, dan Wakasek)
    if (page.props.auth?.user?.is_observer || ['guru', 'wakasek'].includes(page.props.auth?.user?.role) || page.props.auth?.user?.tipe_guru_id === 2) {
        items.push({
            title: 'Observasi Kinerja',
            href: '/kinerja' as any,
            icon: ShieldCheck,
        });
    }

    return items;
});

const masterDataItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (['admin', 'super admin'].includes(page.props.auth?.user?.role)) {
        items.push({ title: 'Users', href: '/users' as any, icon: Users });
        if (page.props.auth?.user?.role === 'super admin') {
            items.push({ title: 'Tipe Guru', href: '/tipe-guru' as any, icon: Briefcase });
        }
        items.push(
            { title: 'Mata Pelajaran', href: '/mata-pelajaran' as any, icon: BookOpen },
            { title: 'Jurusan', href: '/jurusan' as any, icon: GraduationCap },
            { title: 'Tingkat Kelas', href: '/tingkat-kelas' as any, icon: School },
            { title: 'Kelas', href: '/kelas' as any, icon: School },
            { title: 'Manajemen Titik', href: '/titik' as any, icon: MapPin },
            { title: 'Jam Pelajaran', href: '/jam-pelajaran' as any, icon: Clock }
        );
    } 
    return items;
});

const jadwalItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (['admin', 'super admin'].includes(page.props.auth?.user?.role)) {
        items.push(
            { title: 'Jadwal Mapel', href: '/jadwal-mapel' as any, icon: Calendar },
            { title: 'Jadwal Piket', href: '/jadwal-piket' as any, icon: ClipboardCheck },
            { title: 'Jadwal GMS', href: '/jadwal-gms' as any, icon: Calendar }
        );
    }
    return items;
});

const kinerjaItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (['wakasek', 'kepsek'].includes(page.props.auth?.user?.role)) {
        items.push(
            { title: 'Manajemen Kinerja Drive', href: '/kurikulum/drive-guru' as any, icon: Folder },
            { title: 'Pengaturan Observer', href: '/observers' as any, icon: ShieldCheck },
        );
    }
    return items;
});

const upacaraItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (['admin', 'super admin'].includes(page.props.auth?.user?.role)) {
        items.push({
            title: 'Absensi Upacara',
            href: '/absensi-upacara' as any,
            icon: Users,
        });
    }
    return items;
});

const konfirmasiItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (['wakasek'].includes(page.props.auth?.user?.role)) {
        items.push({
            title: 'Persetujuan Kinerja',
            href: '/kinerja-approval' as any,
            icon: ShieldCheck,
            badge: page.props.pending_approvals_count > 0 ? page.props.pending_approvals_count : undefined,
        });
    }

    if (['super admin', 'wakasek'].includes(page.props.auth?.user?.role)) {
        items.push({
            title: 'Keterlambatan Piket',
            href: '/izin-piket' as any,
            icon: ClipboardCheck,
        });
    }

    if (['super admin', 'admin', 'wakasek'].includes(page.props.auth?.user?.role) || page.props.auth?.user?.tipe_guru_id === 2) {
        items.push({
            title: 'Konfirmasi Absensi GMS',
            href: '/konfirmasi-gms' as any,
            icon: CheckCircle2,
        });
    }
    return items;
});

const laporanItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (['super admin', 'wakasek'].includes(page.props.auth?.user?.role)) {
        items.push({
            title: 'Laporan Piket',
            href: '/laporan/piket' as any,
            icon: ClipboardCheck,
        });
        items.push({
            title: 'Laporan GMS',
            href: '/laporan/gms' as any,
            icon: Camera,
        });
        items.push({
            title: 'Laporan Upacara',
            href: '/laporan-absensi-upacara' as any,
            icon: Calendar,
        });
    }

    if (page.props.auth?.user?.role === 'wakasek') {
        items.push({
            title: 'Evaluasi Tindak Lanjut',
            href: '/laporan/evaluasi' as any,
            icon: AlertTriangle,
        });
    }

    if (page.props.auth?.user?.role === 'kepsek') {
        items.push({
            title: 'Rekapitulasi Laporan',
            href: '/kepsek/laporan' as any,
            icon: BookOpen,
        });
        items.push({
            title: 'Laporan Tindak Lanjut',
            href: '/kepsek/laporan-tindak-lanjut' as any,
            icon: AlertTriangle,
        });
    }

    if (['wakasek', 'kepsek'].includes(page.props.auth?.user?.role)) {
        items.push({
            title: 'Kinerja Periodik',
            href: '/laporan-kinerja-periodik' as any,
            icon: Calendar,
        });
        items.push({
            title: 'Laporan Kinerja Tahunan',
            href: '/laporan-kinerja-tahunan' as any,
            icon: Award,
        });
    }
    return items;
});

const pengaturanItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (page.props.auth?.user?.role === 'super admin') {
        items.push(
            {
                title: 'Pengaturan Umum',
                href: '/pengaturan' as any,
                icon: Settings,
            },
            {
                title: 'Log Aktivitas',
                href: '/activity-logs' as any,
                icon: Activity,
            }
        );
    }
    return items;
});

const footerNavItems: NavItem[] = [

];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="utamaItems.length" :items="utamaItems" label="Utama" />
            <NavMain v-if="harianItems.length" :items="harianItems" label="Tugas Harian" />
            <NavMain v-if="masterDataItems.length" :items="masterDataItems" label="Master Data" />
            <NavMain v-if="jadwalItems.length" :items="jadwalItems" label="Jadwal" />
            <NavMain v-if="kinerjaItems.length" :items="kinerjaItems" label="Observasi Kinerja" />
            <NavMain v-if="konfirmasiItems.length" :items="konfirmasiItems" label="Konfirmasi / Persetujuan" />
            <NavMain v-if="upacaraItems.length" :items="upacaraItems" label="Absensi Upacara" />
            <NavMain v-if="laporanItems.length" :items="laporanItems" label="Laporan & Evaluasi" />
            <NavMain v-if="pengaturanItems.length" :items="pengaturanItems" label="Sistem" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton 
                        @click="installPwa"
                        class="text-emerald-700 bg-emerald-50 hover:bg-emerald-100 hover:text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50 font-medium transition-colors cursor-pointer"
                        title="Install / Pasang Aplikasi Simonitorguru di HP"
                    >
                        <Smartphone class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        <span>Install Aplikasi</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
