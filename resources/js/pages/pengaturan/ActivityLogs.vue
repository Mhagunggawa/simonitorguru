<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, 
    Eye, 
    Activity, 
    Clock, 
    Globe, 
    Filter,
    ArrowRight,
    Info,
    FileText,
    Database,
    Laptop,
    CheckCircle2
} from '@lucide/vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogDescription,
    DialogFooter 
} from '@/components/ui/dialog';

const props = defineProps<{
    logs: {
        data: Array<{
            id: number;
            user_id: number | null;
            action: string;
            description: string | null;
            properties: any;
            ip_address: string | null;
            created_at: string;
            user?: {
                id: number;
                name: string;
                role: string;
                email?: string;
            } | null;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        total: number;
    };
    filters: {
        search?: string;
        action?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedAction = ref(props.filters.action || 'ALL');

const applyFilters = () => {
    router.get('/activity-logs', { 
        search: search.value || undefined,
        action: selectedAction.value !== 'ALL' ? selectedAction.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(search, debounce(() => {
    applyFilters();
}, 350));

watch(selectedAction, () => {
    applyFilters();
});

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

const formatKeyName = (key: string): string => {
    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, char => char.toUpperCase());
};

const formatValue = (key: string, val: any): string => {
    if (val === null || val === undefined) return 'null';
    if (['password', 'remember_token'].includes(key.toLowerCase())) {
        return '••••••••';
    }
    if (typeof val === 'boolean') {
        return val ? 'True' : 'False';
    }
    if (typeof val === 'object') {
        return JSON.stringify(val);
    }
    return String(val);
};

// Modal Detail State
const isDetailModalOpen = ref(false);
const selectedLog = ref<any>(null);

const openDetailModal = (log: any) => {
    selectedLog.value = log;
    isDetailModalOpen.value = true;
};

// Computed Helpers for Detail
const changedFields = computed(() => {
    if (!selectedLog.value?.properties) return [];
    const props = selectedLog.value.properties;
    if (props.new || props.old) {
        const keys = new Set([
            ...Object.keys(props.old || {}),
            ...Object.keys(props.new || {})
        ]);
        return Array.from(keys);
    }
    return [];
});

const hasPropertiesData = (log: any): boolean => {
    if (!log || !log.properties) return false;
    const p = log.properties;
    if (p.old && Object.keys(p.old).length > 0) return true;
    if (p.new && Object.keys(p.new).length > 0) return true;
    if (p.attributes && Object.keys(p.attributes).length > 0) return true;
    if (typeof p === 'object' && Object.keys(p).length > 0) return true;
    return false;
};
</script>

<template>
    <Head title="Log Aktivitas" />

    <div class="flex h-full flex-1 flex-col gap-4 md:gap-5 p-3 md:p-6 max-w-[1600px] mx-auto w-full">
        <!-- Top Bar Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h1 class="text-xl md:text-3xl font-bold tracking-tight text-foreground flex items-center gap-2.5">
                    <Activity class="w-6 h-6 md:w-8 md:h-8 text-primary" />
                    Log Aktivitas Sistem
                </h1>
                <p class="text-xs md:text-sm text-muted-foreground mt-0.5 md:mt-1">
                    Jejak rekam transparan perubahan data, eksekusi aksi, dan audit trail seluruh sistem.
                </p>
            </div>
            
            <div class="flex items-center gap-2 text-xs md:text-sm bg-card border border-border px-3.5 py-2 rounded-2xl shadow-xs self-start md:self-auto">
                <Database class="w-4 h-4 text-primary shrink-0" />
                <span class="text-muted-foreground">Total Rekaman:</span>
                <span class="font-bold text-foreground">{{ props.logs.total }} Log</span>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="flex flex-col md:flex-row gap-3 bg-card p-3 md:p-4 rounded-2xl border border-border shadow-xs items-center justify-between">
            <!-- Search Bar -->
            <div class="relative w-full md:w-80 lg:w-96">
                <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search" 
                    placeholder="Cari pengguna, aksi, deskripsi, atau IP..." 
                    class="pl-9 h-10 rounded-xl bg-background border-border text-xs md:text-sm w-full" 
                />
            </div>

            <!-- Action Filter Tabs (Scrollable on mobile) -->
            <div class="flex items-center p-1 rounded-xl bg-muted/60 border border-border/40 w-full md:w-auto overflow-x-auto max-w-full gap-1">
                <button 
                    @click="selectedAction = 'ALL'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap"
                    :class="selectedAction === 'ALL' ? 'bg-background shadow-xs text-foreground font-bold' : 'text-muted-foreground hover:text-foreground'"
                >
                    Semua Aksi
                </button>
                <button 
                    @click="selectedAction = 'CREATE'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="selectedAction === 'CREATE' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 shadow-xs font-bold' : 'text-muted-foreground hover:text-foreground'"
                >
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    CREATE
                </button>
                <button 
                    @click="selectedAction = 'UPDATE'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="selectedAction === 'UPDATE' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 shadow-xs font-bold' : 'text-muted-foreground hover:text-foreground'"
                >
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    UPDATE
                </button>
                <button 
                    @click="selectedAction = 'DELETE'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                    :class="selectedAction === 'DELETE' ? 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 shadow-xs font-bold' : 'text-muted-foreground hover:text-foreground'"
                >
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    DELETE
                </button>
            </div>
        </div>

        <!-- 1. TAMPILAN DESKTOP: TABLE VIEW (hidden md:block) -->
        <div class="hidden md:block rounded-2xl border border-border bg-card shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40 border-b border-border text-xs uppercase text-muted-foreground font-semibold">
                        <tr>
                            <th class="py-3.5 px-4 w-44">Waktu</th>
                            <th class="py-3.5 px-4">Pengguna</th>
                            <th class="py-3.5 px-4 w-28">Aksi</th>
                            <th class="py-3.5 px-4">Deskripsi Aktivitas</th>
                            <th class="py-3.5 px-4 w-36">Alamat IP</th>
                            <th class="py-3.5 px-4 w-32 text-center">Rincian Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr v-for="log in props.logs.data" :key="log.id" class="hover:bg-muted/30 transition-colors">
                            <!-- Waktu -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-xs text-muted-foreground">
                                <div class="flex items-center gap-1.5">
                                    <Clock class="w-3.5 h-3.5 text-slate-400" />
                                    <span>{{ formatDate(log.created_at) }}</span>
                                </div>
                            </td>

                            <!-- Pengguna -->
                            <td class="py-3.5 px-4 font-medium">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs uppercase shrink-0">
                                        {{ log.user?.name?.charAt(0) || 'S' }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-foreground leading-snug truncate">
                                            {{ log.user?.name || 'Sistem' }}
                                        </div>
                                        <div class="text-xs text-muted-foreground flex items-center gap-1.5 mt-0.5">
                                            <span class="px-1.5 py-0.2 rounded bg-muted text-[10px] uppercase font-semibold">
                                                {{ log.user?.role || 'System' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Aksi Badge -->
                            <td class="py-3.5 px-4">
                                <span 
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border"
                                    :class="{
                                        'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900/50': log.action === 'CREATE',
                                        'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900/50': log.action === 'UPDATE',
                                        'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-300 dark:border-red-900/50': log.action === 'DELETE',
                                        'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700': !['CREATE','UPDATE','DELETE'].includes(log.action)
                                    }"
                                >
                                    {{ log.action }}
                                </span>
                            </td>

                            <!-- Deskripsi -->
                            <td class="py-3.5 px-4">
                                <div class="max-w-md truncate text-foreground font-medium text-xs md:text-sm" :title="log.description || '-'">
                                    {{ log.description || '-' }}
                                </div>
                            </td>

                            <!-- IP Address -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-mono">
                                    <Globe class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                    <span>{{ log.ip_address || '127.0.0.1' }}</span>
                                </div>
                            </td>

                            <!-- Tombol Rincian / Detail -->
                            <td class="py-3.5 px-4 text-center">
                                <Button 
                                    size="sm" 
                                    variant="outline"
                                    @click="openDetailModal(log)"
                                    class="rounded-xl text-xs font-semibold h-8 px-2.5 gap-1.5 transition-all"
                                    :class="hasPropertiesData(log) ? 'border-primary/40 text-primary hover:bg-primary/10' : 'text-muted-foreground hover:text-foreground'"
                                >
                                    <Eye class="w-3.5 h-3.5" />
                                    <span>Lihat Detail</span>
                                </Button>
                            </td>
                        </tr>

                        <!-- Empty State Desktop -->
                        <tr v-if="props.logs.data.length === 0">
                            <td colspan="6" class="py-12 text-center text-muted-foreground italic">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Activity class="w-8 h-8 opacity-20" />
                                    <span>Tidak ada catatan log aktivitas yang ditemukan.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. TAMPILAN HP / MOBILE: CARD LIST VIEW (block md:hidden) -->
        <div class="block md:hidden space-y-3">
            <div 
                v-for="log in props.logs.data" 
                :key="log.id"
                class="bg-card text-card-foreground rounded-2xl border border-border p-4 shadow-xs space-y-3"
            >
                <!-- Card Header: User Info & Waktu -->
                <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-border/60">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs shrink-0">
                            {{ log.user?.name?.charAt(0) || 'S' }}
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-foreground text-sm truncate">
                                {{ log.user?.name || 'Sistem' }}
                            </h4>
                            <div class="text-[10px] text-muted-foreground flex items-center gap-1.5">
                                <span class="px-1.5 py-0.2 rounded bg-muted font-medium">
                                    {{ log.user?.role || 'System' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Badge Aksi -->
                    <span 
                        class="px-2 py-0.5 rounded-full text-[11px] font-bold border shrink-0"
                        :class="{
                            'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300': log.action === 'CREATE',
                            'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300': log.action === 'UPDATE',
                            'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-300': log.action === 'DELETE',
                            'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300': !['CREATE','UPDATE','DELETE'].includes(log.action)
                        }"
                    >
                        {{ log.action }}
                    </span>
                </div>

                <!-- Info Deskripsi -->
                <div class="space-y-1">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Aktivitas:</div>
                    <div class="text-sm font-medium text-foreground bg-muted/40 p-2.5 rounded-xl border border-border/40">
                        {{ log.description || '-' }}
                    </div>
                </div>

                <!-- Meta Info (Waktu & IP) -->
                <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground pt-1">
                    <div class="flex items-center gap-1.5">
                        <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <span class="truncate">{{ formatDate(log.created_at) }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 justify-end font-mono">
                        <Globe class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <span>{{ log.ip_address || '127.0.0.1' }}</span>
                    </div>
                </div>

                <!-- Tombol Lihat Rincian Mobile -->
                <Button 
                    size="sm" 
                    variant="outline"
                    @click="openDetailModal(log)"
                    class="w-full rounded-xl text-xs font-bold h-9 mt-1 gap-1.5 border-primary/30 text-primary hover:bg-primary/10"
                >
                    <Eye class="w-4 h-4" />
                    <span>Lihat Rincian Perubahan Data</span>
                </Button>
            </div>

            <!-- Empty State Mobile -->
            <div v-if="props.logs.data.length === 0" class="py-12 px-4 text-center bg-card rounded-2xl border border-dashed border-border">
                <Activity class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                <p class="font-bold text-foreground text-sm">Tidak ada log aktivitas</p>
                <p class="text-xs text-muted-foreground mt-1 max-w-xs mx-auto">
                    Belum ada riwayat aktivitas yang sesuai dengan kriteria pencarian.
                </p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="props.logs.links && props.logs.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-2 md:mt-4">
            <template v-for="(link, key) in props.logs.links" :key="key">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 md:px-4 py-2 border rounded-xl text-xs md:text-sm font-semibold transition-all"
                    :class="[
                        link.active ? 'bg-primary text-primary-foreground border-primary shadow-xs' : 'bg-card hover:bg-muted text-foreground border-border'
                    ]"
                    v-html="link.label"
                    preserve-scroll
                />
                <span 
                    v-else
                    class="px-3 md:px-4 py-2 border rounded-xl text-xs md:text-sm font-medium text-muted-foreground bg-muted/30 border-border/50 opacity-60"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>

    <!-- MODAL DETAIL LOG AKTIVITAS -->
    <Dialog :open="isDetailModalOpen" @update:open="isDetailModalOpen = $event">
        <DialogContent class="sm:max-w-[650px] max-h-[88vh] overflow-y-auto rounded-2xl p-4 md:p-6">
            <DialogHeader class="border-b border-border/60 pb-3 md:pb-4">
                <div class="flex items-center justify-between gap-2 pr-6">
                    <DialogTitle class="text-lg md:text-xl font-bold flex items-center gap-2">
                        <FileText class="w-5 h-5 text-primary" />
                        Rincian Log Aktivitas
                    </DialogTitle>
                    <span 
                        v-if="selectedLog"
                        class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                        :class="{
                            'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300': selectedLog.action === 'CREATE',
                            'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300': selectedLog.action === 'UPDATE',
                            'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-300': selectedLog.action === 'DELETE',
                            'bg-slate-100 text-slate-700 border-slate-200': !['CREATE','UPDATE','DELETE'].includes(selectedLog.action)
                        }"
                    >
                        {{ selectedLog.action }}
                    </span>
                </div>
                <DialogDescription class="text-xs md:text-sm text-muted-foreground mt-1">
                    Detail perubahan dan atribut data yang terekam pada sistem.
                </DialogDescription>
            </DialogHeader>

            <div v-if="selectedLog" class="space-y-4 py-3">
                <!-- Info Grid Pelaksana & Waktu -->
                <div class="grid grid-cols-2 gap-3 bg-muted/30 p-3 md:p-4 rounded-xl border border-border text-xs">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-muted-foreground block">Pelaksana / User:</span>
                        <div class="font-bold text-foreground text-sm mt-0.5">
                            {{ selectedLog.user?.name || 'Sistem' }}
                        </div>
                        <div class="text-muted-foreground mt-0.5">
                            Role: <span class="font-medium text-foreground uppercase">{{ selectedLog.user?.role || '-' }}</span>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] uppercase font-bold text-muted-foreground block">Waktu Kejadian:</span>
                        <div class="font-medium text-foreground mt-0.5">
                            {{ formatDate(selectedLog.created_at) }}
                        </div>
                        <div class="text-muted-foreground mt-0.5 flex items-center gap-1 font-mono">
                            <Laptop class="w-3.5 h-3.5 text-slate-400" />
                            <span>{{ selectedLog.ip_address || '127.0.0.1' }}</span>
                        </div>
                    </div>

                    <div class="col-span-2 pt-2 border-t border-border/40">
                        <span class="text-[10px] uppercase font-bold text-muted-foreground block">Deskripsi:</span>
                        <div class="text-foreground font-medium mt-0.5">
                            {{ selectedLog.description || '-' }}
                        </div>
                    </div>
                </div>

                <!-- Bagian Rincian Data Berdasarkan Aksi -->
                <div>
                    <h4 class="text-xs md:text-sm font-bold text-foreground mb-2 flex items-center gap-1.5">
                        <Database class="w-4 h-4 text-primary" />
                        <span>Data yang Terpengaruh:</span>
                    </h4>

                    <!-- 1. KASUS UPDATE (Komparasi Nilai Lama vs Nilai Baru) -->
                    <div 
                        v-if="selectedLog.action === 'UPDATE' && selectedLog.properties && (selectedLog.properties.old || selectedLog.properties.new)" 
                        class="space-y-2.5"
                    >
                        <div class="rounded-xl border border-border overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-muted/60 text-muted-foreground font-semibold border-b border-border">
                                    <tr>
                                        <th class="py-2.5 px-3 text-left w-1/3">Field / Kolom</th>
                                        <th class="py-2.5 px-3 text-left w-1/3 text-rose-700 dark:text-rose-400">Data Sebelumnya (Lama)</th>
                                        <th class="py-2.5 px-3 text-left w-1/3 text-emerald-700 dark:text-emerald-400">Data Diperbarui (Baru)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/60">
                                    <tr 
                                        v-for="field in changedFields" 
                                        :key="field"
                                        class="hover:bg-muted/20 transition-colors"
                                    >
                                        <!-- Field Name -->
                                        <td class="py-2.5 px-3 font-semibold text-foreground align-top">
                                            {{ formatKeyName(field) }}
                                            <span class="block text-[10px] font-mono text-muted-foreground font-normal">{{ field }}</span>
                                        </td>

                                        <!-- Nilai Lama -->
                                        <td class="py-2.5 px-3 align-top text-rose-800 dark:text-rose-300 bg-rose-50/40 dark:bg-rose-950/20 font-medium break-all">
                                            {{ formatValue(field, selectedLog.properties.old?.[field]) }}
                                        </td>

                                        <!-- Nilai Baru -->
                                        <td class="py-2.5 px-3 align-top text-emerald-800 dark:text-emerald-300 bg-emerald-50/40 dark:bg-emerald-950/20 font-bold break-all">
                                            {{ formatValue(field, selectedLog.properties.new?.[field]) }}
                                        </td>
                                    </tr>

                                    <tr v-if="changedFields.length === 0">
                                        <td colspan="3" class="py-6 text-center text-muted-foreground italic">
                                            Tidak ada kolom yang tercatat mengalami perubahan nilai.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. KASUS CREATE / PENAMBAHAN DATA BARU -->
                    <div 
                        v-else-if="selectedLog.properties && selectedLog.properties.attributes" 
                        class="rounded-xl border border-blue-200/80 bg-blue-50/20 dark:border-blue-900/40 dark:bg-blue-950/10 overflow-hidden"
                    >
                        <table class="w-full text-xs">
                            <thead class="bg-blue-100/40 dark:bg-blue-900/30 text-blue-900 dark:text-blue-200 font-semibold border-b border-blue-200/60">
                                <tr>
                                    <th class="py-2 px-3 text-left w-1/3">Field / Atribut</th>
                                    <th class="py-2 px-3 text-left">Nilai Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/50">
                                <tr 
                                    v-for="(val, key) in selectedLog.properties.attributes" 
                                    :key="key"
                                    class="hover:bg-muted/20"
                                >
                                    <td class="py-2 px-3 font-semibold text-foreground align-top">
                                        {{ formatKeyName(String(key)) }}
                                        <span class="block text-[10px] font-mono text-muted-foreground font-normal">{{ key }}</span>
                                    </td>
                                    <td class="py-2 px-3 font-medium text-foreground align-top break-all">
                                        {{ formatValue(String(key), val) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 3. KASUS DATA FORMAT LAINNYA / FALLBACK JSON -->
                    <div 
                        v-else-if="selectedLog.properties && Object.keys(selectedLog.properties).length > 0" 
                        class="rounded-xl border border-border bg-muted/30 p-3 overflow-x-auto"
                    >
                        <pre class="text-xs font-mono text-muted-foreground whitespace-pre-wrap">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
                    </div>

                    <!-- 4. KASUS TIDAK ADA DATA PROPERTIES -->
                    <div 
                        v-else 
                        class="p-4 rounded-xl border border-dashed border-border text-center text-xs text-muted-foreground bg-muted/20"
                    >
                        <Info class="w-5 h-5 mx-auto mb-1 text-slate-400" />
                        <span>Aktivitas ini tidak mencatat rincian perubahan atribut terpisah.</span>
                    </div>
                </div>
            </div>

            <DialogFooter class="border-t border-border/60 pt-3 md:pt-4">
                <Button 
                    variant="outline" 
                    @click="isDetailModalOpen = false"
                    class="w-full sm:w-auto rounded-xl text-xs font-semibold"
                >
                    Tutup
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
