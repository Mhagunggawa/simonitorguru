<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, Info, Mail, AlertTriangle, FileText, Trash2, ExternalLink, CheckCircle2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Swal from 'sweetalert2';

interface SPHistory {
    id: number;
    tipe_peringatan: string;
    file_dokumen?: string;
    created_at?: string;
}

interface EvaluasiGuru {
    id: number;
    nama: string;
    tipe_guru: string;
    total_alpha: number;
    alpha_details?: {
        sumber: string;
        tanggal: string;
        foto: string | null;
        kelas?: string;
        mapel?: string;
        petugas_piket?: string;
        keterangan?: string;
    }[];
    sp_history: SPHistory[];
}

const props = defineProps<{
    evaluasi: any;
    filters: {
        search?: string;
        show_all?: string;
        filter_type?: string;
        filter_semester?: string;
        filter_tahun?: number;
        filter_bulan?: number;
        filter_minggu?: number;
        start_date?: string;
        end_date?: string;
    };
    available_years: number[];
}>();

const page = usePage<any>();
const userRole = page.props.auth?.user?.role || '';

const search = ref(props.filters.search || '');
const showAll = ref(props.filters.show_all === 'true');
const filterType = ref(props.filters.filter_type || 'minggu');
const filterSemester = ref(props.filters.filter_semester || (new Date().getMonth() >= 6 ? 'Ganjil' : 'Genap'));
const filterTahun = ref(props.filters.filter_tahun || new Date().getFullYear());
const filterBulan = ref(props.filters.filter_bulan || new Date().getMonth() + 1);
const filterMinggu = ref(props.filters.filter_minggu || 1);
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

watch([search, showAll, filterType, filterSemester, filterTahun, filterBulan, filterMinggu, startDate, endDate], debounce(() => {
    const params: Record<string, string | number> = {};
    if (search.value) params.search = search.value;
    if (showAll.value) params.show_all = 'true';
    
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
    
    router.get('/laporan/evaluasi', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

const showModal = ref(false);
const selectedGuru = ref<EvaluasiGuru | null>(null);
const isSubmitting = ref(false);
const formError = ref('');
const fileInput = ref<File | null>(null);

const showDetailModal = ref(false);
const selectedDetailGuru = ref<EvaluasiGuru | null>(null);

const openDetailModal = (guru: EvaluasiGuru) => {
    selectedDetailGuru.value = guru;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    setTimeout(() => {
        selectedDetailGuru.value = null;
    }, 300);
};

const formatFullDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const spForm = useForm({
    user_id: '',
    tipe_peringatan: '',
    file_dokumen: null as File | null,
});

const hasSP = (guru: EvaluasiGuru, tipe: string) => {
    return guru.sp_history.some(sp => sp.tipe_peringatan === tipe);
};

const getSP = (guru: EvaluasiGuru, tipe: string): SPHistory | undefined => {
    return guru.sp_history.find(sp => sp.tipe_peringatan === tipe);
};

const openModal = (guru: EvaluasiGuru, tipe: string) => {
    selectedGuru.value = guru;
    spForm.user_id = guru.id.toString();
    spForm.tipe_peringatan = tipe;
    spForm.file_dokumen = null;
    fileInput.value = null;
    formError.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        fileInput.value = target.files[0];
    } else {
        fileInput.value = null;
    }
};

const submitForm = () => {
    if (!fileInput.value) {
        formError.value = 'File dokumen surat peringatan wajib diunggah.';
        return;
    }
    
    // Check file size (5MB max)
    if (fileInput.value.size > 5 * 1024 * 1024) {
        formError.value = 'Ukuran file dokumen maksimal 5MB.';
        return;
    }

    formError.value = '';
    isSubmitting.value = true;
    spForm.file_dokumen = fileInput.value;

    spForm.post('/laporan/evaluasi', {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Terkirim!',
                text: `${spForm.tipe_peringatan} berhasil dikirimkan via email ke ${selectedGuru.value?.nama}.`,
                confirmButtonColor: '#10b981'
            });
        },
        onError: (errors) => {
            if (errors.file_dokumen) {
                formError.value = errors.file_dokumen;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengirim',
                    text: 'Terjadi kesalahan saat mengunggah dan mengirim surat peringatan.'
                });
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

const confirmDeleteSP = (sp: SPHistory | undefined, guru: EvaluasiGuru) => {
    if (!sp) return;

    Swal.fire({
        title: `Batalkan ${sp.tipe_peringatan}?`,
        text: `Surat peringatan untuk ${guru.nama} akan dihapus dari sistem. Guru akan kembali dapat dikirimkan ${sp.tipe_peringatan} jika diperlukan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Batalkan/Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/laporan/evaluasi/${sp.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Dibatalkan',
                        text: `${sp.tipe_peringatan} untuk ${guru.nama} telah berhasil dihapus.`,
                        confirmButtonColor: '#10b981'
                    });
                },
                onError: () => {
                    Swal.fire('Gagal', 'Terjadi kesalahan saat membatalkan SP.', 'error');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Evaluasi Kedisiplinan" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/50 dark:text-amber-400">
                        <AlertTriangle class="h-5 w-5" />
                    </span>
                    Tindak Lanjut Evaluasi Kedisiplinan
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Rekapitulasi ketidakhadiran (Alpha dari presensi Piket & GMS) dan penerbitan Surat Peringatan (SP).
                </p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-col md:flex-row gap-4 bg-card p-4 rounded-xl border border-sidebar-border/70 shadow dark:border-sidebar-border items-start md:items-center justify-between">
            <div class="flex flex-col sm:flex-row flex-wrap gap-4 flex-1 w-full items-center">
                <div class="relative flex-1 sm:max-w-xs w-full">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari nama guru..." class="pl-8 w-full bg-background" />
                </div>
                
                <div class="flex flex-wrap items-center gap-2 flex-1 w-full">
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
            </div>
            
            <div class="flex items-center space-x-2 whitespace-nowrap">
                <Checkbox id="showAll" v-model:checked="showAll" />
                <label for="showAll" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer">
                    Tampilkan Semua Guru
                </label>
            </div>
        </div>

        <!-- Desktop Table Data Content -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                        <tr>
                            <th class="px-4 py-3.5 font-semibold text-slate-700 dark:text-slate-300">Nama Guru</th>
                            <th class="px-4 py-3.5 font-semibold text-slate-700 dark:text-slate-300">Tipe Guru</th>
                            <th class="px-4 py-3.5 font-semibold text-slate-700 dark:text-slate-300">Total Alpha</th>
                            <th class="px-4 py-3.5 font-semibold text-slate-700 dark:text-slate-300">Tindak Lanjut / Status SP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="guru in evaluasi.data" :key="guru.id" class="border-b border-sidebar-border/70 dark:border-sidebar-border last:border-0 hover:bg-muted/40 transition-colors">
                            <td class="px-4 py-3.5 font-medium text-foreground">{{ guru.nama }}</td>
                            <td class="px-4 py-3.5 text-muted-foreground">{{ guru.tipe_guru }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span v-if="guru.total_alpha > 0" class="font-semibold text-red-600 dark:text-red-400">
                                        {{ guru.total_alpha }}x Alpha
                                    </span>
                                    <span v-else class="text-emerald-600 font-medium">Disiplin (0 Alpha)</span>
                                    
                                    <button v-if="guru.total_alpha > 0" @click="openDetailModal(guru)" title="Lihat Rincian Bukti Alpha" class="inline-flex items-center justify-center rounded-full bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 p-1.5 transition-colors">
                                        <Info class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div v-if="guru.total_alpha === 1" class="mb-1.5">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        <Info class="w-3.5 h-3.5" /> Teguran Lisan (1x Alpha)
                                    </span>
                                </div>
                                <div v-else-if="guru.total_alpha === 0">
                                    <span class="text-muted-foreground text-xs italic">-</span>
                                </div>

                                <div v-if="guru.total_alpha > 1" class="flex flex-col gap-2 items-start">
                                    <!-- SP 1 -->
                                    <template v-if="guru.total_alpha >= 2">
                                        <div v-if="hasSP(guru, 'SP 1')" class="inline-flex items-center gap-1.5">
                                            <a :href="'/storage/' + getSP(guru, 'SP 1')?.file_dokumen" target="_blank" class="inline-flex items-center gap-1 rounded-md bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 px-2.5 py-1 text-xs font-medium transition-colors" title="Klik untuk mengunduh/melihat berkas SP 1">
                                                <CheckCircle2 class="w-3.5 h-3.5" /> SP 1 Terkirim
                                                <ExternalLink class="w-3 h-3 ml-0.5 opacity-70" />
                                            </a>
                                            <button type="button" @click="confirmDeleteSP(getSP(guru, 'SP 1'), guru)" class="rounded p-1 text-red-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/40" title="Batalkan/Hapus SP 1">
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                        <Button v-else variant="outline" size="sm" class="h-7 text-xs border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300" @click="openModal(guru, 'SP 1')">
                                            <Mail class="w-3.5 h-3.5 mr-1" /> Kirim SP 1
                                        </Button>
                                    </template>
                                    
                                    <!-- SP 2 -->
                                    <template v-if="guru.total_alpha >= 4">
                                        <div v-if="hasSP(guru, 'SP 2')" class="inline-flex items-center gap-1.5">
                                            <a :href="'/storage/' + getSP(guru, 'SP 2')?.file_dokumen" target="_blank" class="inline-flex items-center gap-1 rounded-md bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 px-2.5 py-1 text-xs font-medium transition-colors" title="Klik untuk mengunduh/melihat berkas SP 2">
                                                <CheckCircle2 class="w-3.5 h-3.5" /> SP 2 Terkirim
                                                <ExternalLink class="w-3 h-3 ml-0.5 opacity-70" />
                                            </a>
                                            <button type="button" @click="confirmDeleteSP(getSP(guru, 'SP 2'), guru)" class="rounded p-1 text-red-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/40" title="Batalkan/Hapus SP 2">
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                        <template v-else>
                                            <Button v-if="hasSP(guru, 'SP 1')" variant="outline" size="sm" class="h-7 text-xs border-orange-300 text-orange-700 hover:bg-orange-50 dark:border-orange-700 dark:text-orange-300" @click="openModal(guru, 'SP 2')">
                                                <Mail class="w-3.5 h-3.5 mr-1" /> Kirim SP 2
                                            </Button>
                                            <span v-else class="text-[11px] text-muted-foreground italic">
                                                (Kirim SP 1 terlebih dahulu)
                                            </span>
                                        </template>
                                    </template>

                                    <!-- SP 3 -->
                                    <template v-if="guru.total_alpha >= 6">
                                        <div v-if="hasSP(guru, 'SP 3')" class="inline-flex items-center gap-1.5">
                                            <a :href="'/storage/' + getSP(guru, 'SP 3')?.file_dokumen" target="_blank" class="inline-flex items-center gap-1 rounded-md bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 px-2.5 py-1 text-xs font-medium transition-colors" title="Klik untuk mengunduh/melihat surat Panggilan Kepsek">
                                                <CheckCircle2 class="w-3.5 h-3.5" /> Panggilan Kepsek (SP 3) Terkirim
                                                <ExternalLink class="w-3 h-3 ml-0.5 opacity-70" />
                                            </a>
                                            <button type="button" @click="confirmDeleteSP(getSP(guru, 'SP 3'), guru)" class="rounded p-1 text-red-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/40" title="Batalkan/Hapus SP 3">
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                        <template v-else>
                                            <Button v-if="hasSP(guru, 'SP 2')" variant="destructive" size="sm" class="h-7 text-xs" @click="openModal(guru, 'SP 3')">
                                                <Mail class="w-3.5 h-3.5 mr-1" /> Panggilan (SP 3)
                                            </Button>
                                            <span v-else class="text-[11px] text-muted-foreground italic">
                                                (Kirim SP 2 terlebih dahulu)
                                            </span>
                                        </template>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="evaluasi.data.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-muted-foreground italic">
                                Tidak ada data guru yang ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card Layout -->
        <div class="md:hidden flex flex-col gap-4">
            <div v-for="guru in evaluasi.data" :key="guru.id" class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow p-4 flex flex-col gap-3 relative">
                <div class="font-bold text-base border-b pb-2 flex justify-between items-center">
                    <span>{{ guru.nama }}</span>
                    <div class="flex items-center gap-2">
                        <span v-if="guru.total_alpha > 0" class="text-sm font-semibold text-red-600 dark:text-red-400">{{ guru.total_alpha }}x Alpha</span>
                        <span v-else class="text-sm font-medium text-emerald-600">Disiplin</span>
                        
                        <button v-if="guru.total_alpha > 0" @click="openDetailModal(guru)" title="Lihat Detail Alpha" class="inline-flex items-center justify-center rounded-full bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 p-1.5 transition-colors">
                            <Info class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-between items-center text-xs">
                    <span class="text-muted-foreground font-medium">Tipe Guru:</span>
                    <span>{{ guru.tipe_guru }}</span>
                </div>
                
                <div class="flex flex-col gap-2 mt-1 pt-2 border-t text-xs">
                    <span class="text-muted-foreground font-medium mb-0.5">Tindak Lanjut:</span>
                    <div class="flex flex-col gap-2">
                        <div v-if="guru.total_alpha === 1">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 w-fit">
                                <Info class="w-3.5 h-3.5" /> Teguran Lisan
                            </span>
                        </div>
                        <div v-else-if="guru.total_alpha === 0">
                            <span class="text-muted-foreground text-xs italic">-</span>
                        </div>
                        <template v-if="guru.total_alpha > 1">
                            <!-- SP 1 -->
                            <template v-if="guru.total_alpha >= 2">
                                <div v-if="hasSP(guru, 'SP 1')" class="inline-flex items-center justify-between gap-1.5 bg-emerald-50 dark:bg-emerald-950/20 p-2 rounded-lg border border-emerald-200 dark:border-emerald-900/40">
                                    <a :href="'/storage/' + getSP(guru, 'SP 1')?.file_dokumen" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                        <CheckCircle2 class="w-3.5 h-3.5" /> SP 1 Terkirim
                                        <ExternalLink class="w-3 h-3 ml-0.5" />
                                    </a>
                                    <button type="button" @click="confirmDeleteSP(getSP(guru, 'SP 1'), guru)" class="p-1 text-red-500 hover:text-red-700" title="Batalkan SP 1">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <Button v-else variant="outline" size="sm" class="w-full justify-start text-xs border-amber-300 text-amber-700" @click="openModal(guru, 'SP 1')">
                                    <Mail class="w-3.5 h-3.5 mr-1.5" /> Kirim SP 1
                                </Button>
                            </template>
                            
                            <!-- SP 2 -->
                            <template v-if="guru.total_alpha >= 4">
                                <div v-if="hasSP(guru, 'SP 2')" class="inline-flex items-center justify-between gap-1.5 bg-emerald-50 dark:bg-emerald-950/20 p-2 rounded-lg border border-emerald-200 dark:border-emerald-900/40">
                                    <a :href="'/storage/' + getSP(guru, 'SP 2')?.file_dokumen" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                        <CheckCircle2 class="w-3.5 h-3.5" /> SP 2 Terkirim
                                        <ExternalLink class="w-3 h-3 ml-0.5" />
                                    </a>
                                    <button type="button" @click="confirmDeleteSP(getSP(guru, 'SP 2'), guru)" class="p-1 text-red-500 hover:text-red-700" title="Batalkan SP 2">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <template v-else>
                                    <Button v-if="hasSP(guru, 'SP 1')" variant="outline" size="sm" class="w-full justify-start text-xs border-orange-300 text-orange-700" @click="openModal(guru, 'SP 2')">
                                        <Mail class="w-3.5 h-3.5 mr-1.5" /> Kirim SP 2
                                    </Button>
                                    <span v-else class="text-[11px] text-muted-foreground italic">(Kirim SP 1 terlebih dahulu)</span>
                                </template>
                            </template>

                            <!-- SP 3 -->
                            <template v-if="guru.total_alpha >= 6">
                                <div v-if="hasSP(guru, 'SP 3')" class="inline-flex items-center justify-between gap-1.5 bg-red-50 dark:bg-red-950/20 p-2 rounded-lg border border-red-200 dark:border-red-900/40">
                                    <a :href="'/storage/' + getSP(guru, 'SP 3')?.file_dokumen" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-red-700 dark:text-red-300">
                                        <CheckCircle2 class="w-3.5 h-3.5" /> Panggilan (SP 3) Terkirim
                                        <ExternalLink class="w-3 h-3 ml-0.5" />
                                    </a>
                                    <button type="button" @click="confirmDeleteSP(getSP(guru, 'SP 3'), guru)" class="p-1 text-red-500 hover:text-red-700" title="Batalkan SP 3">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <template v-else>
                                    <Button v-if="hasSP(guru, 'SP 2')" variant="destructive" size="sm" class="w-full justify-start text-xs" @click="openModal(guru, 'SP 3')">
                                        <Mail class="w-3.5 h-3.5 mr-1.5" /> Panggilan (SP 3)
                                    </Button>
                                    <span v-else class="text-[11px] text-muted-foreground italic">(Kirim SP 2 terlebih dahulu)</span>
                                </template>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
            <div v-if="evaluasi.data.length === 0" class="text-center p-8 border border-sidebar-border/70 rounded-xl bg-card text-muted-foreground shadow-sm italic">
                Tidak ada data guru yang ditemukan.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="evaluasi.links && evaluasi.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-6">
            <template v-for="(link, key) in evaluasi.links" :key="key">
                <button
                    v-if="link.url"
                    @click="router.get(link.url, {}, { preserveScroll: true, preserveState: true })"
                    class="px-4 py-2 border rounded-md text-sm font-medium transition-colors"
                    :class="[
                        link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-background hover:bg-muted text-foreground'
                    ]"
                    v-html="link.label"
                />
                <span 
                    v-else
                    class="px-4 py-2 border rounded-md text-sm font-medium text-muted-foreground bg-muted/50"
                    v-html="link.label"
                />
            </template>
        </div>

        <!-- SP Upload Modal -->
        <Dialog :open="showModal" @update:open="closeModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Kirim {{ spForm.tipe_peringatan }}</DialogTitle>
                    <DialogDescription>
                        Unggah dokumen resmi {{ spForm.tipe_peringatan }} untuk {{ selectedGuru?.nama }}. File akan dilampirkan via Email resmi guru.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm">
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="file_dokumen">Dokumen Surat Peringatan (PDF/JPG/PNG)</Label>
                            <Input 
                                id="file_dokumen" 
                                type="file" 
                                accept=".pdf,.jpg,.jpeg,.png"
                                @change="handleFileChange" 
                                required 
                            />
                            <p class="text-xs text-red-500" v-if="formError">{{ formError }}</p>
                        </div>
                    </div>
                    
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeModal">Batal</Button>
                        <Button type="submit" :disabled="isSubmitting" class="bg-emerald-600 hover:bg-emerald-500 text-white">
                            <span v-if="isSubmitting">Mengirim...</span>
                            <span v-else>Kirim Surat & Email</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Alpha Detail Modal -->
        <Dialog :open="showDetailModal" @update:open="closeDetailModal">
            <DialogContent class="max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Detail Alpha: {{ selectedDetailGuru?.nama }}</DialogTitle>
                    <DialogDescription>
                        Rincian ketidakhadiran (Alpha) dari laporan presensi GMS maupun Piket.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div v-if="selectedDetailGuru?.alpha_details?.length" class="space-y-4">
                        <div v-for="(detail, index) in selectedDetailGuru.alpha_details" :key="index" class="p-4 border rounded-lg bg-muted/30">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
                                <div>
                                    <span class="font-semibold text-foreground">{{ formatFullDate(detail.tanggal) }}</span>
                                    <span class="text-xs text-muted-foreground block mt-0.5">Sumber Presensi: {{ detail.sumber }}</span>
                                </div>
                                <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 text-xs font-semibold rounded-md">Alpha</span>
                            </div>
                            
                            <div v-if="detail.keterangan" class="text-xs text-muted-foreground mb-3 bg-background p-2.5 rounded border">
                                {{ detail.keterangan }}
                            </div>
                            
                            <div v-if="detail.foto" class="mt-2">
                                <span class="text-xs font-medium text-muted-foreground mb-1.5 block">Bukti Dokumentasi:</span>
                                <div class="relative w-full max-w-sm rounded-md overflow-hidden border">
                                    <img :src="'/storage/' + detail.foto" alt="Dokumentasi Alpha" class="object-cover w-full h-auto" />
                                </div>
                            </div>
                            <div v-else class="text-xs italic text-muted-foreground bg-background p-2 rounded border border-dashed">
                                Tidak ada lampiran foto dokumentasi.
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-muted-foreground italic">
                        Tidak ada detail ketidakhadiran yang ditemukan.
                    </div>
                </div>
                
                <DialogFooter>
                    <Button type="button" @click="closeDetailModal">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
