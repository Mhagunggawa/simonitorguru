<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';
import { 
    Search, 
    Folder, 
    ExternalLink, 
    Copy, 
    Check, 
    Settings, 
    Users, 
    ShieldCheck, 
    Eye, 
    EyeOff, 
    Link as LinkIcon,
    AlertCircle,
    UserCheck,
    Lock
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';

const props = defineProps<{
    teachers: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            role: string;
            tipe_guru?: {
                id: number;
                nama_tipe: string;
            };
            observer?: {
                id: number;
                name: string;
            };
            guru_drive?: {
                id: number;
                google_drive_url: string | null;
                share_to_guru: boolean;
                share_to_observer: boolean;
            };
        }>;
        links: Array<any>;
        current_page: number;
        last_page: number;
        total: number;
    };
    stats: {
        total_guru: number;
        terhubung: number;
        belum_terhubung: number;
        share_observer: number;
    };
    filters: {
        search?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');

watch([search, statusFilter], debounce(() => {
    const params: Record<string, string> = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value && statusFilter.value !== 'all') params.status = statusFilter.value;

    router.get('/kurikulum/drive-guru', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

// Modal Edit State
const isModalOpen = ref(false);
const selectedTeacher = ref<any>(null);
const copiedId = ref<number | null>(null);

const form = useForm({
    google_drive_url: '',
    share_to_guru: true,
    share_to_observer: true,
});

const openEditModal = (teacher: any) => {
    selectedTeacher.value = teacher;
    form.google_drive_url = teacher.guru_drive?.google_drive_url || '';
    form.share_to_guru = teacher.guru_drive ? Boolean(teacher.guru_drive.share_to_guru) : true;
    form.share_to_observer = teacher.guru_drive ? Boolean(teacher.guru_drive.share_to_observer) : true;
    isModalOpen.value = true;
};

const copyToClipboard = (url: string, id: number) => {
    navigator.clipboard.writeText(url);
    copiedId.value = id;
    setTimeout(() => {
        copiedId.value = null;
    }, 2000);
};

const submitForm = () => {
    if (!selectedTeacher.value) return;

    form.post(`/kurikulum/drive-guru/${selectedTeacher.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan!',
                text: `Pengaturan Google Drive untuk ${selectedTeacher.value.name} berhasil disimpan.`,
                confirmButtonColor: '#4f46e5',
                timer: 2000,
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: 'Harap periksa kembali isian formulir Anda.',
                confirmButtonColor: '#ef4444',
            });
        }
    });
};
</script>

<template>
    <Head title="Manajemen Google Drive Kinerja Guru" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6 animate-in fade-in duration-300">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-border pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <div class="p-2 bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 rounded-xl">
                        <Folder class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-extrabold tracking-tight text-foreground">
                            Manajemen Google Drive Guru
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Kelola tautan penyimpanan kurikulum dan konfigurasi hak akses drive per guru.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 md:gap-4">
            <Card class="border shadow-xs">
                <CardContent class="p-3.5 sm:p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] sm:text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Guru</p>
                        <h3 class="text-lg sm:text-2xl font-black text-foreground mt-0.5 sm:mt-1">{{ stats.total_guru }}</h3>
                        <p class="text-[10px] sm:text-[11px] text-muted-foreground mt-0.5 truncate">Guru & Tendik</p>
                    </div>
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <Users class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                </CardContent>
            </Card>

            <Card class="border shadow-xs">
                <CardContent class="p-3.5 sm:p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] sm:text-xs font-semibold text-muted-foreground uppercase tracking-wider">Drive Terhubung</p>
                        <h3 class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5 sm:mt-1">{{ stats.terhubung }}</h3>
                        <p class="text-[10px] sm:text-[11px] text-emerald-600/80 mt-0.5 truncate">Telah dialokasikan</p>
                    </div>
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <Folder class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                </CardContent>
            </Card>

            <Card class="border shadow-xs">
                <CardContent class="p-3.5 sm:p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] sm:text-xs font-semibold text-muted-foreground uppercase tracking-wider">Belum Diberikan</p>
                        <h3 class="text-lg sm:text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5 sm:mt-1">{{ stats.belum_terhubung }}</h3>
                        <p class="text-[10px] sm:text-[11px] text-amber-600/80 mt-0.5 truncate">Perlu tautan folder</p>
                    </div>
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <AlertCircle class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                </CardContent>
            </Card>

            <Card class="border shadow-xs">
                <CardContent class="p-3.5 sm:p-5 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] sm:text-xs font-semibold text-muted-foreground uppercase tracking-wider">Akses Observer</p>
                        <h3 class="text-lg sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5 sm:mt-1">{{ stats.share_observer }}</h3>
                        <p class="text-[10px] sm:text-[11px] text-indigo-600/80 mt-0.5 truncate">Dibagikan observer</p>
                    </div>
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <ShieldCheck class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Filter & Search Bar -->
        <div class="flex flex-col sm:flex-row gap-3 bg-card p-3 md:p-4 rounded-xl border border-sidebar-border/70 shadow-xs">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search" 
                    placeholder="Cari nama guru atau email..." 
                    class="pl-9 bg-background h-10 text-xs sm:text-sm" 
                />
            </div>
            
            <div class="w-full sm:w-56">
                <select 
                    v-model="statusFilter"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <option value="all">Semua Status Drive</option>
                    <option value="terhubung">Hanya Yang Terhubung</option>
                    <option value="belum">Belum Diberikan Link</option>
                </select>
            </div>
        </div>

        <!-- Data Container (Desktop Table + Mobile Cards) -->
        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-xs overflow-hidden">
            <!-- 1. Tampilan Desktop: Table View (hidden md:block) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                        <tr>
                            <th class="px-5 py-3.5 font-semibold">Nama Guru</th>
                            <th class="px-5 py-3.5 font-semibold">Tautan Google Drive</th>
                            <th class="px-5 py-3.5 font-semibold">Observer & Akses</th>
                            <th class="px-5 py-3.5 font-semibold">Akses Guru</th>
                            <th class="px-5 py-3.5 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                        <tr 
                            v-for="teacher in teachers.data" 
                            :key="teacher.id"
                            class="hover:bg-muted/40 transition-colors"
                        >
                            <!-- Nama Guru -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold flex items-center justify-center text-xs shrink-0">
                                        {{ teacher.name.charAt(0) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-foreground text-sm truncate">{{ teacher.name }}</p>
                                        <p class="text-xs text-muted-foreground truncate">{{ teacher.email }}</p>
                                        <span v-if="teacher.tipe_guru" class="inline-block mt-0.5 text-[10px] text-muted-foreground bg-muted px-1.5 py-0.5 rounded">
                                            {{ teacher.tipe_guru.nama_tipe }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Google Drive URL -->
                            <td class="px-5 py-3.5">
                                <div v-if="teacher.guru_drive?.google_drive_url" class="flex items-center gap-2">
                                    <a 
                                        :href="teacher.guru_drive.google_drive_url" 
                                        target="_blank" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800 transition-colors"
                                    >
                                        <Folder class="w-3.5 h-3.5 text-blue-600" />
                                        <span>Buka Drive</span>
                                        <ExternalLink class="w-3 h-3 ml-0.5 opacity-70" />
                                    </a>
                                    <button 
                                        type="button"
                                        @click="copyToClipboard(teacher.guru_drive.google_drive_url, teacher.id)"
                                        class="p-1.5 text-muted-foreground hover:text-foreground rounded-md hover:bg-muted transition-colors"
                                        title="Salin Link"
                                    >
                                        <Check v-if="copiedId === teacher.id" class="w-4 h-4 text-emerald-600" />
                                        <Copy v-else class="w-4 h-4" />
                                    </button>
                                </div>
                                <div v-else class="flex items-center gap-1.5 text-xs text-amber-700 dark:text-amber-400 font-medium">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    Belum Diatur
                                </div>
                            </td>

                            <!-- Observer & Akses Observer -->
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <UserCheck class="w-3.5 h-3.5 text-muted-foreground shrink-0" />
                                        <span class="font-medium text-foreground">
                                            {{ teacher.observer?.name || 'Belum Ditugaskan' }}
                                        </span>
                                    </div>
                                    <div v-if="teacher.guru_drive?.google_drive_url">
                                        <Badge 
                                            variant="outline" 
                                            class="text-[10px] font-semibold"
                                            :class="teacher.guru_drive?.share_to_observer ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30' : 'bg-slate-50 text-slate-500 border-slate-200'"
                                        >
                                            <component :is="teacher.guru_drive?.share_to_observer ? Eye : EyeOff" class="w-3 h-3 mr-1" />
                                            {{ teacher.guru_drive?.share_to_observer ? 'Akses Observer Aktif' : 'Akses Observer Terkunci' }}
                                        </Badge>
                                    </div>
                                </div>
                            </td>

                            <!-- Akses Guru Sendiri -->
                            <td class="px-5 py-3.5">
                                <div v-if="teacher.guru_drive?.google_drive_url">
                                    <Badge 
                                        variant="outline"
                                        class="text-[10px] font-semibold"
                                        :class="teacher.guru_drive?.share_to_guru ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30'"
                                    >
                                        <component :is="teacher.guru_drive?.share_to_guru ? Eye : EyeOff" class="w-3 h-3 mr-1" />
                                        {{ teacher.guru_drive?.share_to_guru ? 'Tampil di Guru' : 'Disembunyikan' }}
                                    </Badge>
                                </div>
                                <span v-else class="text-xs text-muted-foreground">-</span>
                            </td>

                            <!-- Aksi -->
                            <td class="px-5 py-3.5 text-right">
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    class="gap-1.5 h-8 text-xs font-semibold shadow-xs"
                                    @click="openEditModal(teacher)"
                                >
                                    <Settings class="w-3.5 h-3.5 text-indigo-600" />
                                    Atur Drive
                                </Button>
                            </td>
                        </tr>

                        <tr v-if="teachers.data.length === 0">
                            <td colspan="5" class="px-5 py-10 text-center text-muted-foreground italic">
                                Tidak ada data guru yang ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 2. Tampilan Mobile: Card List View (block md:hidden) -->
            <div class="block md:hidden p-3 space-y-3 bg-muted/10">
                <div 
                    v-for="teacher in teachers.data" 
                    :key="'mobile-' + teacher.id"
                    class="p-4 rounded-2xl border border-border bg-card shadow-xs space-y-3.5"
                >
                    <!-- Header Kartu: Avatar, Nama, Email, Tipe Guru & Status Drive -->
                    <div class="flex items-start justify-between gap-2.5 pb-2.5 border-b border-border/60">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold flex items-center justify-center text-xs shrink-0">
                                {{ teacher.name.charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-foreground text-sm truncate leading-snug">
                                    {{ teacher.name }}
                                </h4>
                                <p class="text-xs text-muted-foreground truncate">{{ teacher.email }}</p>
                                <span v-if="teacher.tipe_guru" class="inline-block mt-0.5 text-[10px] text-muted-foreground bg-muted px-1.5 py-0.2 rounded font-medium">
                                    {{ teacher.tipe_guru.nama_tipe }}
                                </span>
                            </div>
                        </div>

                        <!-- Status Drive Badge -->
                        <div class="shrink-0">
                            <span 
                                v-if="teacher.guru_drive?.google_drive_url"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300"
                            >
                                <Check class="w-3 h-3" /> Terhubung
                            </span>
                            <span 
                                v-else
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300"
                            >
                                <AlertCircle class="w-3 h-3" /> Belum Diatur
                            </span>
                        </div>
                    </div>

                    <!-- Link Google Drive Box (Jika Ada) -->
                    <div v-if="teacher.guru_drive?.google_drive_url" class="p-2.5 rounded-xl bg-blue-50/50 dark:bg-blue-950/30 border border-blue-200/60 dark:border-blue-900/40 flex items-center justify-between gap-2">
                        <a 
                            :href="teacher.guru_drive.google_drive_url" 
                            target="_blank" 
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300 truncate"
                        >
                            <Folder class="w-4 h-4 text-blue-600 shrink-0" />
                            <span class="truncate">Buka Folder Google Drive</span>
                            <ExternalLink class="w-3 h-3 shrink-0 opacity-70" />
                        </a>
                        <button 
                            type="button"
                            @click="copyToClipboard(teacher.guru_drive.google_drive_url, teacher.id)"
                            class="p-1.5 text-blue-700 hover:text-blue-900 rounded-md hover:bg-blue-100/60 dark:hover:bg-blue-900/40 transition-colors shrink-0"
                            title="Salin Link"
                        >
                            <Check v-if="copiedId === teacher.id" class="w-4 h-4 text-emerald-600" />
                            <Copy v-else class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Grid Info 2 Kolom: Observer & Akses Guru -->
                    <div class="grid grid-cols-2 gap-2 text-xs bg-muted/30 p-2.5 rounded-xl border border-border/50">
                        <!-- Observer -->
                        <div class="space-y-1">
                            <span class="text-[10px] uppercase font-bold text-muted-foreground block">Observer:</span>
                            <div class="flex items-center gap-1 font-medium text-foreground text-xs truncate">
                                <UserCheck class="w-3.5 h-3.5 text-muted-foreground shrink-0" />
                                <span class="truncate">{{ teacher.observer?.name || 'Belum Ada' }}</span>
                            </div>
                            <div v-if="teacher.guru_drive?.google_drive_url">
                                <span 
                                    class="inline-flex items-center text-[10px] font-semibold"
                                    :class="teacher.guru_drive?.share_to_observer ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400'"
                                >
                                    <component :is="teacher.guru_drive?.share_to_observer ? Eye : EyeOff" class="w-3 h-3 mr-1" />
                                    {{ teacher.guru_drive?.share_to_observer ? 'Akses Aktif' : 'Terkunci' }}
                                </span>
                            </div>
                        </div>

                        <!-- Akses Guru -->
                        <div class="space-y-1">
                            <span class="text-[10px] uppercase font-bold text-muted-foreground block">Akses Guru:</span>
                            <div v-if="teacher.guru_drive?.google_drive_url">
                                <Badge 
                                    variant="outline"
                                    class="text-[10px] font-semibold"
                                    :class="teacher.guru_drive?.share_to_guru ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30'"
                                >
                                    <component :is="teacher.guru_drive?.share_to_guru ? Eye : EyeOff" class="w-3 h-3 mr-1" />
                                    {{ teacher.guru_drive?.share_to_guru ? 'Tampil di Guru' : 'Disembunyikan' }}
                                </Badge>
                            </div>
                            <span v-else class="text-[11px] text-muted-foreground italic">-</span>
                        </div>
                    </div>

                    <!-- Tombol Aksi Mobile -->
                    <Button 
                        variant="outline" 
                        size="sm" 
                        class="w-full gap-2 h-9 text-xs font-bold rounded-xl border-indigo-200 text-indigo-700 bg-indigo-50/40 hover:bg-indigo-100 hover:text-indigo-800 dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300"
                        @click="openEditModal(teacher)"
                    >
                        <Settings class="w-4 h-4 text-indigo-600" />
                        Konfigurasi Link & Akses Drive
                    </Button>
                </div>

                <!-- Empty State Mobile -->
                <div v-if="teachers.data.length === 0" class="py-10 px-4 text-center bg-card rounded-2xl border border-dashed border-border">
                    <Folder class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                    <p class="font-bold text-foreground text-sm">Tidak ada data guru</p>
                    <p class="text-xs text-muted-foreground mt-1 max-w-xs mx-auto">
                        Tidak ada guru yang cocok dengan filter atau kata kunci pencarian.
                    </p>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="teachers.links && teachers.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-4">
            <template v-for="(link, k) in teachers.links" :key="k">
                <div 
                    v-if="link.url === null" 
                    class="px-3 py-1.5 text-sm text-muted-foreground bg-card border border-border/50 rounded-md opacity-50 cursor-not-allowed" 
                    v-html="link.label"
                />
                <button 
                    v-else
                    @click="router.get(link.url, {}, { preserveScroll: true, preserveState: true })"
                    class="px-3 py-1.5 text-sm rounded-md transition-colors border"
                    :class="link.active ? 'bg-primary text-primary-foreground border-primary font-bold' : 'bg-card border-border/50 hover:bg-muted text-foreground'"
                    v-html="link.label"
                />
            </template>
        </div>

        <!-- Modal Edit Google Drive & Hak Akses -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-lg font-bold">
                        <Folder class="w-5 h-5 text-indigo-600" />
                        Konfigurasi Drive & Hak Akses Guru
                    </DialogTitle>
                    <DialogDescription>
                        Atur tautan folder Google Drive resmi untuk <strong>{{ selectedTeacher?.name }}</strong> dan kelola siapa saja yang berhak membukanya.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-6 py-2">
                    <!-- URL Google Drive -->
                    <div class="space-y-2">
                        <Label class="text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-1.5">
                            <LinkIcon class="w-3.5 h-3.5 text-indigo-600" />
                            Tautan URL Google Drive Folder
                        </Label>
                        <Input 
                            v-model="form.google_drive_url" 
                            type="url"
                            placeholder="https://drive.google.com/drive/folders/..." 
                            class="bg-background text-sm font-mono"
                        />
                        <p class="text-xs text-muted-foreground">
                            Tempelkan link folder Google Drive resmi yang disiapkan Kurikulum untuk penyimpanan modul ajar, RPP, dan video observasi guru ini.
                        </p>
                    </div>

                    <!-- Kontrol Hak Akses (Toggles) -->
                    <div class="space-y-3 pt-2 border-t border-border/70">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                            <ShieldCheck class="w-4 h-4 text-emerald-600" />
                            Aturan Hak Akses (Access Control)
                        </h4>

                        <!-- Toggle Guru -->
                        <div class="flex items-start justify-between p-3.5 rounded-xl border border-border bg-muted/20 gap-3">
                            <div>
                                <Label class="font-bold text-sm cursor-pointer" @click="form.share_to_guru = !form.share_to_guru">
                                    Tampilkan di Dashboard Guru Pemilik
                                </Label>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Jika diaktifkan, {{ selectedTeacher?.name }} akan melihat tombol pintasan langsung ke folder Drive ini di Dashboard akun miliknya.
                                </p>
                            </div>
                            <input 
                                type="checkbox" 
                                v-model="form.share_to_guru" 
                                class="w-5 h-5 rounded text-indigo-600 focus:ring-indigo-500 mt-1 cursor-pointer"
                            />
                        </div>

                        <!-- Toggle Observer -->
                        <div class="flex items-start justify-between p-3.5 rounded-xl border border-border bg-muted/20 gap-3">
                            <div>
                                <Label class="font-bold text-sm cursor-pointer" @click="form.share_to_observer = !form.share_to_observer">
                                    Izinkan Observer yang Bertugas Mengakses
                                </Label>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Pengamat (Observer) yang ditugaskan menilai guru ini 
                                    <strong v-if="selectedTeacher?.observer">({{ selectedTeacher.observer.name }})</strong>
                                    dapat membuka folder drive untuk memeriksa bukti dukung saat observasi.
                                </p>
                            </div>
                            <input 
                                type="checkbox" 
                                v-model="form.share_to_observer" 
                                class="w-5 h-5 rounded text-indigo-600 focus:ring-indigo-500 mt-1 cursor-pointer"
                            />
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0 pt-2 border-t border-border">
                        <Button 
                            type="button" 
                            variant="outline" 
                            @click="isModalOpen = false"
                        >
                            Batal
                        </Button>
                        <Button 
                            type="submit" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
