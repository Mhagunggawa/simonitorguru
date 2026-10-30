<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Edit, Plus, Trash2, Search, Filter, School, Layers, CheckCircle2 } from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref, watch, computed, onMounted } from 'vue';
import debounce from 'lodash/debounce';

const page = usePage<any>();

interface KelasItem {
    id: number;
    nama_kelas: string;
    jurusan_id: number;
    tingkat_kelas_id?: number | null;
    jurusan?: {
        id: number;
        nama_jurusan: string;
    };
    tingkat_kelas?: {
        id: number;
        nama_tingkat: string;
    } | null;
}

interface TingkatKelasItem {
    id: number;
    nama_tingkat: string;
}

interface JurusanItem {
    id: number;
    nama_jurusan: string;
}

const props = defineProps<{
    kelas: KelasItem[];
    jurusans: JurusanItem[];
    tingkatKelas: TingkatKelasItem[];
    filters: {
        search?: string;
        jurusan_id?: string;
    };
    totalCount?: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Kelas',
                href: '/kelas',
            },
        ],
    },
});

const search = ref(props.filters.search || '');
const jurusan_id = ref(props.filters.jurusan_id || '');
const activeTingkatTab = ref<number | 'semua'>('semua');

watch([search, jurusan_id], debounce(([newSearch, newJurusanId]) => {
    router.get('/kelas', 
        { 
            search: newSearch || undefined,
            jurusan_id: newJurusanId || undefined
        }, 
        {
            preserveState: true,
            preserveScroll: true,
            replace: true
        }
    );
}, 300));

// Filter kelas berdasarkan Tingkat ID
const getClassesForTingkat = (tingkatId: number) => {
    return (props.kelas || []).filter(k => k.tingkat_kelas_id === tingkatId || k.tingkat_kelas?.id === tingkatId);
};

// Filter kelas tanpa tingkat (jika ada)
const unassignedClasses = computed(() => {
    return (props.kelas || []).filter(k => !k.tingkat_kelas_id && !k.tingkat_kelas);
});

// Theme visual per tingkat kelas (X = Biru, XI = Emerald, XII = Ungu)
const getTingkatTheme = (namaTingkat: string) => {
    const t = (namaTingkat || '').toUpperCase().trim();
    if (t === 'X' || t === '10') {
        return {
            title: 'Tingkat X',
            headerIcon: 'bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400',
            badge: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800',
            countBadge: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            activeTab: 'bg-blue-600 text-white shadow-blue-600/20',
        };
    }
    if (t === 'XI' || t === '11') {
        return {
            title: 'Tingkat XI',
            headerIcon: 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400',
            badge: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
            countBadge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            activeTab: 'bg-emerald-600 text-white shadow-emerald-600/20',
        };
    }
    if (t === 'XII' || t === '12') {
        return {
            title: 'Tingkat XII',
            headerIcon: 'bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400',
            badge: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800',
            countBadge: 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            activeTab: 'bg-purple-600 text-white shadow-purple-600/20',
        };
    }
    return {
        title: `Tingkat ${namaTingkat}`,
        headerIcon: 'bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400',
        badge: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800',
        countBadge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        activeTab: 'bg-amber-600 text-white shadow-amber-600/20',
    };
};

const deleteKelas = (id: number, namaKelas: string) => {
    Swal.fire({
        title: 'Konfirmasi Hapus Kelas',
        html: `Apakah Anda yakin ingin menghapus kelas <b>${namaKelas}</b>? Seluruh data jadwal pelajaran dan riwayat terkait kelas ini akan ikut terhapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/kelas/${id}`, {
                preserveScroll: true
            });
        }
    });
};

const showFlashMessage = (flash: any) => {
    if (flash?.success) {
        Swal.fire({
            title: 'Berhasil!',
            text: flash.success,
            icon: 'success',
            timer: 2500,
            showConfirmButton: false
        });
    } else if (flash?.error) {
        Swal.fire({
            title: 'Gagal',
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
</script>

<template>
    <Head title="Manajemen Kelas" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <!-- Header Page -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-primary/10 text-primary">
                        <School class="w-6 h-6" />
                    </div>
                    Manajemen Kelas
                </h1>
            </div>

            <!-- Single Add Button -->
            <Button as-child class="bg-[#79AE6F] hover:bg-[#79AE6F]/90 text-white shadow-xs gap-1.5 self-start sm:self-auto">
                <Link href="/kelas/create">
                    <Plus class="w-4 h-4" /> Add Kelas
                </Link>
            </Button>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="flex flex-col sm:flex-row justify-between items-center bg-card p-4 rounded-xl border border-sidebar-border/70 shadow-xs dark:border-sidebar-border gap-4">
            <div class="relative w-full sm:max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search"
                    type="search" 
                    placeholder="Cari nama kelas..." 
                    class="w-full bg-background pl-8 text-sm" 
                />
            </div>
            
            <!-- Filter Jurusan -->
            <div class="w-full sm:w-auto flex items-center gap-2 justify-end">
                <Filter class="w-4 h-4 text-muted-foreground hidden sm:inline" />
                <span class="text-xs font-semibold text-muted-foreground whitespace-nowrap hidden sm:inline">Jurusan:</span>
                <select 
                    v-model="jurusan_id" 
                    class="w-full sm:w-[220px] h-9 rounded-md border border-input bg-background px-3 py-1 text-xs font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <option value="">Semua Jurusan</option>
                    <option v-for="jurusan in jurusans" :key="jurusan.id" :value="jurusan.id.toString()">
                        {{ jurusan.nama_jurusan }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Quick Tabs Navigation per Tingkat Kelas -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <button
                type="button"
                @click="activeTingkatTab = 'semua'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap cursor-pointer flex items-center gap-1.5 border"
                :class="activeTingkatTab === 'semua' 
                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-slate-900 dark:border-white shadow-xs' 
                    : 'bg-card text-muted-foreground hover:bg-muted border-border'"
            >
                <Layers class="w-3.5 h-3.5" />
                Semua Tingkat
                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTingkatTab === 'semua' ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900' : 'bg-muted text-muted-foreground'">
                    {{ kelas.length }}
                </span>
            </button>

            <button
                v-for="tk in tingkatKelas"
                :key="tk.id"
                type="button"
                @click="activeTingkatTab = tk.id"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap cursor-pointer flex items-center gap-1.5 border"
                :class="activeTingkatTab === tk.id 
                    ? `${getTingkatTheme(tk.nama_tingkat).activeTab} border-transparent shadow-xs` 
                    : 'bg-card text-muted-foreground hover:bg-muted border-border'"
            >
                <School class="w-3.5 h-3.5" />
                Tingkat {{ tk.nama_tingkat }}
                <span 
                    class="px-1.5 py-0.2 rounded-full text-[10px]"
                    :class="activeTingkatTab === tk.id ? 'bg-white/25 text-white' : 'bg-muted text-muted-foreground'"
                >
                    {{ getClassesForTingkat(tk.id).length }}
                </span>
            </button>
        </div>

        <!-- ================= 3 TABEL BERDASARKAN TINGKAT KELAS ================= -->
        <div class="space-y-8">
            <template v-for="tk in tingkatKelas" :key="tk.id">
                <div 
                    v-if="activeTingkatTab === 'semua' || activeTingkatTab === tk.id" 
                    class="flex flex-col gap-3"
                >
                    <!-- Section Title & Header per Tingkat -->
                    <div class="flex items-center justify-between border-b pb-2 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="p-1.5 rounded-lg" :class="getTingkatTheme(tk.nama_tingkat).headerIcon">
                                <School class="w-4 h-4" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">
                                    Kelas Tingkat {{ tk.nama_tingkat }}
                                </h2>
                            </div>
                        </div>

                        <span 
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                            :class="getTingkatTheme(tk.nama_tingkat).countBadge"
                        >
                            {{ getClassesForTingkat(tk.id).length }} Kelas
                        </span>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-xs dark:border-sidebar-border overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border text-slate-600 dark:text-slate-300">
                                <tr>
                                    <th class="px-4 py-3 font-bold w-14 text-center">No</th>
                                    <th class="px-6 py-3 font-bold">Nama Kelas</th>
                                    <th class="px-6 py-3 font-bold">Jurusan</th>
                                    <th class="px-6 py-3 font-bold text-center w-36">Tingkat Kelas</th>
                                    <th class="px-6 py-3 font-bold text-right w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                <tr 
                                    v-for="(item, index) in getClassesForTingkat(tk.id)" 
                                    :key="item.id" 
                                    class="hover:bg-muted/40 transition-colors"
                                >
                                    <td class="px-4 py-3.5 text-center text-xs font-medium text-muted-foreground">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-6 py-3.5 font-semibold text-slate-900 dark:text-slate-100">
                                        {{ item.nama_kelas }}
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <span v-if="item.jurusan" class="px-2.5 py-1 bg-secondary text-secondary-foreground rounded-md text-xs font-medium">
                                            {{ item.jurusan.nama_jurusan }}
                                        </span>
                                        <span v-else class="text-xs text-muted-foreground italic">-</span>
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <Badge 
                                            variant="outline" 
                                            class="text-xs font-bold uppercase tracking-wider px-2.5 py-0.5"
                                            :class="getTingkatTheme(tk.nama_tingkat).badge"
                                        >
                                            {{ tk.nama_tingkat }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-3.5 text-right">
                                        <div class="flex justify-end gap-1.5">
                                            <Button 
                                                variant="outline" 
                                                size="icon" 
                                                class="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/50" 
                                                as-child
                                            >
                                                <Link :href="`/kelas/${item.id}/edit`" title="Edit Kelas">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button 
                                                variant="destructive" 
                                                size="icon" 
                                                class="h-8 w-8" 
                                                @click="deleteKelas(item.id, item.nama_kelas)"
                                                title="Hapus Kelas"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="getClassesForTingkat(tk.id).length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                                        <p class="text-sm font-medium">Tidak ada kelas Tingkat {{ tk.nama_tingkat }} yang cocok dengan filter pencarian.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="md:hidden flex flex-col gap-2.5">
                        <div 
                            v-for="(item, index) in getClassesForTingkat(tk.id)" 
                            :key="item.id" 
                            class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow-xs p-3.5 flex flex-col gap-2"
                        >
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] font-bold flex items-center justify-center">
                                        {{ index + 1 }}
                                    </span>
                                    <div>
                                        <div class="font-bold text-sm text-slate-900 dark:text-slate-100">{{ item.nama_kelas }}</div>
                                        <div class="text-xs text-muted-foreground mt-0.5">
                                            {{ item.jurusan?.nama_jurusan || '-' }}
                                        </div>
                                    </div>
                                </div>
                                <Badge 
                                    variant="outline" 
                                    class="text-[10px] font-bold uppercase tracking-wider"
                                    :class="getTingkatTheme(tk.nama_tingkat).badge"
                                >
                                    {{ tk.nama_tingkat }}
                                </Badge>
                            </div>

                            <div class="flex gap-2 justify-end mt-1 pt-2 border-t border-border">
                                <Button variant="outline" size="sm" as-child class="h-7 text-xs">
                                    <Link :href="`/kelas/${item.id}/edit`">
                                        <Edit class="h-3.5 w-3.5 mr-1" /> Edit
                                    </Link>
                                </Button>
                                <Button 
                                    variant="destructive" 
                                    size="sm" 
                                    class="h-7 text-xs" 
                                    @click="deleteKelas(item.id, item.nama_kelas)"
                                >
                                    <Trash2 class="h-3.5 w-3.5 mr-1" /> Hapus
                                </Button>
                            </div>
                        </div>

                        <div v-if="getClassesForTingkat(tk.id).length === 0" class="text-center p-6 bg-card rounded-xl border border-sidebar-border/70 text-muted-foreground text-xs">
                            Tidak ada kelas Tingkat {{ tk.nama_tingkat }} yang ditemukan.
                        </div>
                    </div>
                </div>
            </template>

            <!-- Fallback Section jika ada kelas tanpa Tingkat Kelas -->
            <div 
                v-if="unassignedClasses.length > 0 && (activeTingkatTab === 'semua')" 
                class="flex flex-col gap-3 pt-4 border-t dark:border-slate-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-amber-700 dark:text-amber-400">Kelas Belum Ditentukan Tingkat</h2>
                        <p class="text-xs text-muted-foreground">Kelas yang belum dikaitkan dengan tingkat kelas manapun</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                        {{ unassignedClasses.length }} Kelas
                    </span>
                </div>

                <div class="rounded-xl border border-amber-200 bg-card p-4">
                    <ul class="divide-y">
                        <li v-for="item in unassignedClasses" :key="item.id" class="py-2 flex justify-between items-center text-sm">
                            <span class="font-semibold">{{ item.nama_kelas }}</span>
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="`/kelas/${item.id}/edit`">
                                    <Edit class="h-3.5 w-3.5 mr-1" /> Atur Tingkat
                                </Link>
                            </Button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
