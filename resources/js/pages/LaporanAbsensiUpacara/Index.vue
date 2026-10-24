<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { 
    Users, 
    Calendar as CalendarIcon, 
    ChevronDown, 
    ChevronRight, 
    CheckCircle2, 
    XCircle, 
    HeartPulse, 
    FileText, 
    AlertCircle,
    CalendarDays
} from "@lucide/vue";
import { ref, watch } from "vue";

const props = defineProps<{
    reports: any[];
    filter: {
        period: string;
        period_label: string;
        month: number;
        year: number;
    };
    summary: {
        total_upacara: number;
        total_hadir: number;
        total_tidak_hadir: number;
        total_alpha: number;
        total_sakit: number;
        total_izin: number;
    };
}>();

const selectedPeriod = ref(props.filter.period);

watch(selectedPeriod, (newPeriod) => {
    if (newPeriod) {
        router.get('/laporan-absensi-upacara', { period: newPeriod }, {
            preserveState: true,
            replace: true,
        });
    }
});

// Keep track of which dates are expanded
const expandedDates = ref<Record<string, boolean>>({});

// Auto-expand all dates by default
props.reports.forEach((r, idx) => {
    expandedDates.value[r.tanggal] = idx === 0; // expand the latest date by default
});

const toggleExpand = (date: string) => {
    expandedDates.value[date] = !expandedDates.value[date];
};

const expandAll = (status: boolean) => {
    props.reports.forEach(r => {
        expandedDates.value[r.tanggal] = status;
    });
};
</script>

<template>
    <Head title="Laporan Absensi Upacara" />
    <div class="p-3 md:p-6 space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-6 dark:border-slate-800">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <CalendarIcon class="w-7 h-7" />
                    </div>
                    Laporan Absensi Upacara
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-2xl text-sm md:text-base leading-relaxed">
                    Rekapitulasi kehadiran upacara bendera guru dan wakasek periode <span class="font-bold text-slate-800 dark:text-slate-200">{{ filter.period_label }}</span>.
                </p>
            </div>

            <!-- Month Filter Input -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 p-2 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
                    <CalendarDays class="w-4 h-4 text-emerald-600 shrink-0" />
                    <Label for="filter-period" class="text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">
                        Pilih Bulan:
                    </Label>
                    <Input 
                        id="filter-period" 
                        type="month" 
                        v-model="selectedPeriod" 
                        class="w-[160px] h-9 text-xs md:text-sm font-medium bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 cursor-pointer" 
                    />
                </div>
            </div>
        </div>

        <!-- Monthly Summary Statistics -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="p-3.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
                <span class="text-xs font-semibold text-slate-500">Pelaksanaan Upacara</span>
                <p class="text-xl font-bold text-slate-900 dark:text-slate-100 mt-1">{{ summary.total_upacara }} Kali</p>
            </div>
            <div class="p-3.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 shadow-xs">
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                    <CheckCircle2 class="w-3.5 h-3.5" /> Total Hadir
                </span>
                <p class="text-xl font-bold text-emerald-700 dark:text-emerald-400 mt-1">{{ summary.total_hadir }}</p>
            </div>
            <div class="p-3.5 rounded-xl bg-rose-50/60 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800 shadow-xs">
                <span class="text-xs font-semibold text-rose-700 dark:text-rose-400 flex items-center gap-1">
                    <XCircle class="w-3.5 h-3.5" /> Total Alpha
                </span>
                <p class="text-xl font-bold text-rose-700 dark:text-rose-400 mt-1">{{ summary.total_alpha }}</p>
            </div>
            <div class="p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 shadow-xs">
                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 flex items-center gap-1">
                    <HeartPulse class="w-3.5 h-3.5" /> Total Sakit
                </span>
                <p class="text-xl font-bold text-amber-700 dark:text-amber-400 mt-1">{{ summary.total_sakit }}</p>
            </div>
            <div class="p-3.5 rounded-xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 shadow-xs">
                <span class="text-xs font-semibold text-blue-700 dark:text-blue-400 flex items-center gap-1">
                    <FileText class="w-3.5 h-3.5" /> Total Izin
                </span>
                <p class="text-xl font-bold text-blue-700 dark:text-blue-400 mt-1">{{ summary.total_izin }}</p>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
                <span class="text-xs font-semibold text-slate-500">Total Tidak Hadir</span>
                <p class="text-xl font-bold text-slate-700 dark:text-slate-300 mt-1">{{ summary.total_tidak_hadir }}</p>
            </div>
        </div>

        <!-- Controls: Expand/Collapse All (if reports > 0) -->
        <div v-if="reports.length > 0" class="flex items-center justify-between">
            <span class="text-xs text-slate-500">
                Menampilkan <strong class="text-slate-800 dark:text-slate-200">{{ reports.length }}</strong> tanggal upacara pada bulan <strong>{{ filter.period_label }}</strong>
            </span>
            <div class="flex items-center gap-2">
                <button type="button" @click="expandAll(true)" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 hover:underline">
                    Buka Semua
                </button>
                <span class="text-slate-300">&bull;</span>
                <button type="button" @click="expandAll(false)" class="text-xs font-medium text-slate-500 hover:text-slate-700 hover:underline">
                    Tutup Semua
                </button>
            </div>
        </div>

        <!-- Main List Content -->
        <Card class="shadow-sm border-slate-200 dark:border-slate-800 overflow-hidden">
            <CardContent class="p-0">
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    <div v-for="report in reports" :key="report.tanggal" class="bg-white dark:bg-slate-950 transition-colors">
                        <!-- Date Header (Clickable Accordion) -->
                        <div 
                            @click="toggleExpand(report.tanggal)"
                            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center shrink-0">
                                    <CalendarIcon class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 dark:text-slate-100 text-sm md:text-base">
                                        {{ report.tanggal_format }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <Badge variant="outline" class="bg-emerald-50 text-emerald-700 border-emerald-200 text-xs py-0">
                                            {{ report.total_hadir }} Hadir
                                        </Badge>
                                        <Badge v-if="report.total_tidak_hadir > 0" variant="outline" class="bg-rose-50 text-rose-700 border-rose-200 text-xs py-0">
                                            {{ report.total_tidak_hadir }} Tidak Hadir
                                        </Badge>
                                        <span v-if="report.total_tidak_hadir > 0" class="text-xs text-slate-400">
                                            ({{ report.total_alpha ? report.total_alpha + ' Alpha' : '' }}
                                            {{ report.total_sakit ? (report.total_alpha ? ', ' : '') + report.total_sakit + ' Sakit' : '' }}
                                            {{ report.total_izin ? ((report.total_alpha || report.total_sakit) ? ', ' : '') + report.total_izin + ' Izin' : '' }})
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-slate-400 self-end sm:self-center">
                                <ChevronDown v-if="expandedDates[report.tanggal]" class="w-5 h-5" />
                                <ChevronRight v-else class="w-5 h-5" />
                            </div>
                        </div>

                        <!-- Expanded Content (Detail Teachers) -->
                        <div v-show="expandedDates[report.tanggal]" class="bg-slate-50/50 dark:bg-slate-900/20 px-4 pb-6 pt-2 border-t border-slate-100 dark:border-slate-800 space-y-4">
                            <!-- Section: Guru Hadir -->
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5 mb-2.5">
                                    <CheckCircle2 class="w-4 h-4" />
                                    Daftar Guru Hadir ({{ report.hadir.length }})
                                </h4>
                                <ul v-if="report.hadir.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5">
                                    <li 
                                        v-for="teacher in report.hadir" 
                                        :key="teacher.id"
                                        class="flex items-center gap-2.5 p-2.5 bg-white dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs"
                                    >
                                        <div class="w-7 h-7 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center shrink-0">
                                            <CheckCircle2 class="w-4 h-4" />
                                        </div>
                                        <span class="font-medium text-xs md:text-sm text-slate-800 dark:text-slate-200 truncate">{{ teacher.name }}</span>
                                    </li>
                                </ul>
                                <p v-else class="text-xs text-slate-400 italic">Tidak ada guru yang hadir pada tanggal ini.</p>
                            </div>

                            <!-- Section: Guru Tidak Hadir -->
                            <div v-if="report.tidak_hadir && report.tidak_hadir.length > 0" class="pt-2 border-t border-slate-200/60 dark:border-slate-800">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400 flex items-center gap-1.5 mb-2.5">
                                    <XCircle class="w-4 h-4" />
                                    Daftar Guru Tidak Hadir ({{ report.tidak_hadir.length }})
                                </h4>
                                <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5">
                                    <li 
                                        v-for="teacher in report.tidak_hadir" 
                                        :key="teacher.id"
                                        class="flex items-start justify-between gap-2 p-2.5 bg-white dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs"
                                    >
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-7 h-7 rounded-full text-rose-600 bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center shrink-0">
                                                <XCircle class="w-4 h-4" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-xs md:text-sm text-slate-800 dark:text-slate-200 truncate">{{ teacher.name }}</p>
                                                <p v-if="teacher.keterangan" class="text-[11px] text-slate-400 italic truncate">{{ teacher.keterangan }}</p>
                                            </div>
                                        </div>
                                        <Badge 
                                            variant="outline" 
                                            :class="teacher.status === 'Alpha' ? 'bg-rose-50 text-rose-700 border-rose-200' : (teacher.status === 'Sakit' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200')"
                                            class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 shrink-0"
                                        >
                                            {{ teacher.status }}
                                        </Badge>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State for the Selected Month -->
                    <div v-if="reports.length === 0" class="p-12 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <CalendarIcon class="w-10 h-10 text-slate-300" />
                            <div>
                                <h3 class="font-bold text-slate-700 dark:text-slate-300 text-base">Tidak Ada Data Absensi Upacara</h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    Belum ada catatan pelaksanaan absensi upacara pada bulan <strong>{{ filter.period_label }}</strong>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
