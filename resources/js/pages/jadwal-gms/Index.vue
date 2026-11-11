<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, Clock, Settings, X, CalendarClock } from '@lucide/vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import Swal from 'sweetalert2';

const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            tipe_guru: {
                nama_tipe: string;
            };
            jadwal_gms: Array<{
                id: number;
                hari: string;
            }>;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
    };
    pengaturan?: {
        waktu_mulai: string;
        waktu_selesai: string;
        jam_normal_mulai?: string;
        jam_normal_selesai?: string;
        jam_terlambat_mulai?: string;
        jam_terlambat_selesai?: string;
    } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Jadwal GMS',
                href: '/jadwal-gms',
            },
        ],
    },
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get('/jadwal-gms', { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

// Pengaturan Time bounds
// Remove seconds for time input, e.g. "07:15:00" -> "07:15"
const formatTimeForInput = (timeString?: string) => {
    if (!timeString) return '';
    return timeString.substring(0, 5);
};

const formPengaturan = useForm({
    waktu_mulai: formatTimeForInput(props.pengaturan?.waktu_mulai) || '07:00',
    waktu_selesai: formatTimeForInput(props.pengaturan?.waktu_selesai) || '08:00',
    jam_normal_mulai: formatTimeForInput(props.pengaturan?.jam_normal_mulai) || formatTimeForInput(props.pengaturan?.waktu_mulai) || '07:00',
    jam_normal_selesai: formatTimeForInput(props.pengaturan?.jam_normal_selesai) || '07:15',
    jam_terlambat_mulai: formatTimeForInput(props.pengaturan?.jam_terlambat_mulai) || '07:16',
    jam_terlambat_selesai: formatTimeForInput(props.pengaturan?.jam_terlambat_selesai) || formatTimeForInput(props.pengaturan?.waktu_selesai) || '08:00',
});

const isDialogOpen = ref(false);

const submitPengaturan = () => {
    formPengaturan.post('/jadwal-gms/pengaturan', {
        preserveScroll: true,
        onSuccess: () => {
            isDialogOpen.value = false;
            Swal.fire({
                title: 'Berhasil!',
                text: 'Pengaturan jam absensi GMS (Normal & Terlambat) berhasil diperbarui.',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors);
            const errorMsg = errorMessages.length > 0 ? errorMessages[0] : 'Terjadi kesalahan validasi pengaturan waktu.';
            
            Swal.fire({
                title: 'Validasi Waktu Gagal!',
                text: errorMsg as string,
                icon: 'error',
                confirmButtonColor: '#3085d6',
            });
        }
    });
};
</script>

<template>
    <Head title="Jadwal GMS" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Penjadwalan GMS</h1>
                <p class="text-muted-foreground">Kelola jadwal giliran absensi GMS untuk setiap guru.</p>
            </div>
        </div>

        <div class="flex flex-row gap-2 bg-card p-4 rounded-xl border border-sidebar-border/70 shadow dark:border-sidebar-border items-center justify-between">
            <div class="relative flex-1 sm:max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search"
                    type="search" 
                    placeholder="Cari guru..." 
                    class="w-full bg-background pl-8" 
                />
            </div>

            <!-- Dialog for Pengaturan -->
            <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                    <Button variant="default" class="shrink-0 gap-2">
                        <Settings class="h-4 w-4" /> 
                        <span class="hidden sm:inline">Pengaturan Jam</span>
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[520px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle class="text-lg font-bold">Pengaturan Waktu Absensi GMS</DialogTitle>
                        <DialogDescription class="text-xs">
                            Atur jam operasional sesi absensi serta rentang waktu kedatangan normal (Hadir) dan terlambat (Hadir Terlambat).
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4 py-3">
                        <!-- BAGIAN 1: SESI UTAMA (JAM MULAI & JAM TUTUP) -->
                        <div class="p-3.5 rounded-xl border border-sidebar-border/80 bg-muted/30 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                    1. Batas Operasional Sesi GMS
                                </span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-primary/10 text-primary font-semibold">
                                    Jam Sesi
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <label for="waktu_mulai" class="text-xs font-semibold text-foreground">Jam Mulai (Buka Sesi)</label>
                                    <Input 
                                        id="waktu_mulai" 
                                        type="time" 
                                        v-model="formPengaturan.waktu_mulai" 
                                        class="h-9 text-sm" 
                                        required
                                    />
                                    <p class="text-[10px] text-muted-foreground">Waktu awal sesi absensi dibuka</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label for="waktu_selesai" class="text-xs font-semibold text-foreground">Jam Tutup (Selesai Sesi)</label>
                                    <Input 
                                        id="waktu_selesai" 
                                        type="time" 
                                        v-model="formPengaturan.waktu_selesai" 
                                        class="h-9 text-sm" 
                                        required
                                    />
                                    <p class="text-[10px] text-muted-foreground">Waktu akhir sesi absensi ditutup</p>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN 2: RENTANG DATANG NORMAL -->
                        <div class="p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                                    2. Rentang Jam Datang Normal
                                </span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 font-bold">
                                    Status: Hadir
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <label for="jam_normal_mulai" class="text-xs font-semibold text-foreground">Mulai Datang Normal</label>
                                    <Input 
                                        id="jam_normal_mulai" 
                                        type="time" 
                                        v-model="formPengaturan.jam_normal_mulai" 
                                        class="h-9 text-sm bg-background" 
                                        required
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label for="jam_normal_selesai" class="text-xs font-semibold text-foreground">Selesai Datang Normal</label>
                                    <Input 
                                        id="jam_normal_selesai" 
                                        type="time" 
                                        v-model="formPengaturan.jam_normal_selesai" 
                                        class="h-9 text-sm bg-background" 
                                        required
                                    />
                                </div>
                            </div>
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400">
                                Guru yang absen pada rentang ini akan tercatat dengan status <strong>Hadir</strong> (Tepat Waktu).
                            </p>
                        </div>

                        <!-- BAGIAN 3: RENTANG DATANG TERLAMBAT -->
                        <div class="p-3.5 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/50 dark:bg-amber-950/20 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300">
                                    3. Rentang Jam Datang Terlambat
                                </span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 font-bold">
                                    Status: Hadir (Terlambat)
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <label for="jam_terlambat_mulai" class="text-xs font-semibold text-foreground">Mulai Datang Terlambat</label>
                                    <Input 
                                        id="jam_terlambat_mulai" 
                                        type="time" 
                                        v-model="formPengaturan.jam_terlambat_mulai" 
                                        class="h-9 text-sm bg-background" 
                                        required
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label for="jam_terlambat_selesai" class="text-xs font-semibold text-foreground">Selesai Datang Terlambat</label>
                                    <Input 
                                        id="jam_terlambat_selesai" 
                                        type="time" 
                                        v-model="formPengaturan.jam_terlambat_selesai" 
                                        class="h-9 text-sm bg-background" 
                                        required
                                    />
                                </div>
                            </div>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400">
                                Guru yang absen pada rentang ini akan tercatat dengan status <strong>Hadir (Terlambat)</strong>.
                            </p>
                        </div>

                        <!-- RINGKASAN VALIDASI -->
                        <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30 text-blue-900 dark:text-blue-200 border border-blue-200 dark:border-blue-900 text-xs space-y-1">
                            <p class="font-semibold flex items-center gap-1.5">
                                <Clock class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                Ketentuan Rentang Waktu:
                            </p>
                            <p class="leading-relaxed text-[11px]">
                                Jam normal dan jam terlambat <strong>harus berada di dalam rentang Jam Mulai ({{ formPengaturan.waktu_mulai }}) dan Jam Tutup ({{ formPengaturan.waktu_selesai }})</strong>.
                            </p>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0 pt-2 border-t border-border">
                        <Button variant="outline" @click="isDialogOpen = false" type="button" class="cursor-pointer">Batal</Button>
                        <Button type="button" @click="submitPengaturan" :disabled="formPengaturan.processing" class="cursor-pointer font-semibold">
                            {{ formPengaturan.processing ? 'Menyimpan...' : 'Simpan Pengaturan Jam' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Guru</th>
                        <th class="px-4 py-3 font-medium">Tipe Guru</th>
                        <th class="px-4 py-3 font-medium">Jadwal Hari</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users.data" :key="user.id" class="border-b border-sidebar-border/70 dark:border-sidebar-border last:border-0 hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3">{{ user.tipe_guru?.nama_tipe || '-' }}</td>
                        <td class="px-4 py-3">
                            <div v-if="user.jadwal_gms.length === 0" class="text-muted-foreground text-sm italic">
                                Belum ada jadwal
                            </div>
                            <div v-else class="flex flex-wrap gap-1">
                                <span v-for="jadwal in user.jadwal_gms" :key="jadwal.id" class="px-2 py-1 bg-primary/10 text-primary rounded-md text-xs font-medium">
                                    {{ jadwal.hari }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="`/jadwal-gms/${user.id}/edit`">
                                    <CalendarClock class="w-4 h-4 mr-2" /> Atur Jadwal
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    
                    <tr v-if="users.data.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                            Tidak ada guru yang memiliki hak akses GMS (atau tidak ada data sesuai pencarian).
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Table -->
        <div class="md:hidden flex flex-col gap-4">
            <div 
                v-for="user in users.data" 
                :key="user.id" 
                class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow p-4 flex flex-col gap-3"
            >
                <div class="font-bold text-lg border-b pb-2">{{ user.name }}</div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-muted-foreground">Tipe Guru:</span>
                    <span>{{ user.tipe_guru?.nama_tipe || '-' }}</span>
                </div>
                
                <div class="flex flex-col gap-1 mt-1 text-sm">
                    <span class="font-medium text-muted-foreground">Jadwal Hari:</span>
                    <div v-if="user.jadwal_gms.length === 0" class="text-muted-foreground italic">
                        Belum ada jadwal
                    </div>
                    <div v-else class="flex flex-wrap gap-1 mt-1">
                        <span v-for="jadwal in user.jadwal_gms" :key="jadwal.id" class="px-2 py-1 bg-primary/10 text-primary rounded-md text-xs font-medium">
                            {{ jadwal.hari }}
                        </span>
                    </div>
                </div>

                <div class="flex justify-end mt-2 pt-3 border-t border-border">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/jadwal-gms/${user.id}/edit`">
                            <CalendarClock class="w-4 h-4 mr-2" /> Atur Jadwal
                        </Link>
                    </Button>
                </div>
            </div>

            <div v-if="users.data.length === 0" class="text-center p-8 bg-card rounded-xl border border-sidebar-border/70 text-muted-foreground">
                Tidak ada guru yang memiliki hak akses GMS.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="users.links && users.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-4">
            <template v-for="(link, key) in users.links" :key="key">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-4 py-2 border rounded-md text-sm font-medium transition-colors"
                    :class="[
                        link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-background hover:bg-muted text-foreground'
                    ]"
                    v-html="link.label"
                    preserve-scroll
                />
                <span 
                    v-else
                    class="px-4 py-2 border rounded-md text-sm font-medium text-muted-foreground bg-muted/50"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
