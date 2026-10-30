<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Edit, Plus, Trash2, Search, ShieldCheck, GraduationCap, Users } from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref, watch, onMounted } from 'vue';
import debounce from 'lodash/debounce';

const page = usePage<any>();

interface UserItem {
    id: number;
    name: string;
    email: string;
    role: string;
    tipe_guru?: {
        id: number;
        nama_tipe: string;
    };
    status_pegawai?: string | null;
}

const props = defineProps<{
    adminUsers: UserItem[];
    guruUsers: {
        data: UserItem[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        role?: string;
    };
    totalAdmins: number;
    totalGurus: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'User Management',
                href: '/users',
            },
        ],
    },
});

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || 'semua');

watch([search, role], debounce(([searchValue, roleValue]) => {
    router.get('/users', { 
        search: searchValue || undefined, 
        role: roleValue !== 'semua' ? roleValue : undefined 
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

const deleteUser = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus Pengguna',
        text: 'Apakah Anda yakin ingin menghapus pengguna ini? Seluruh riwayat jadwal mengajar, piket, absensi, dan evaluasi terkait akan ikut terhapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus Semua!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/users/${id}`, {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Pengguna beserta seluruh riwayat terkait berhasil dihapus.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kendala saat menghapus data pengguna.',
                        confirmButtonColor: '#ef4444'
                    });
                },
                preserveScroll: true
            });
        }
    });
};

const getRoleBadgeClass = (role: string) => {
    switch (role) {
        case 'super admin':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        case 'admin':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'wakasek':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'kepsek':
            return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800';
        case 'guru':
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
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
            title: 'Terjadi Kendala',
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
    <Head title="User Management" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <!-- Header Page with ONLY 1 Single Add User Button -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-primary/10 text-primary">
                        <Users class="w-6 h-6" />
                    </div>
                    Manajemen Pengguna
                </h1>
            </div>

            <!-- Single Add User Button -->
            <Button as-child class="bg-[#79AE6F] hover:bg-[#79AE6F]/90 text-white shadow-xs gap-1.5 self-start sm:self-auto">
                <Link href="/users/create">
                    <Plus class="w-4 h-4" /> Add User
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
                    placeholder="Cari user (nama, email)..." 
                    class="w-full bg-background pl-8 text-sm" 
                />
            </div>
            
            <div class="w-full sm:w-auto flex items-center gap-2 justify-end">
                <span class="text-xs font-semibold text-muted-foreground whitespace-nowrap">Filter Role:</span>
                <select 
                    v-model="role"
                    class="w-full sm:w-auto h-9 rounded-md border border-input bg-background px-3 py-1 text-xs font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <option value="semua">Semua Role</option>
                    <option value="super admin">Super Admin</option>
                    <option value="admin">Admin</option>
                    <option value="kepsek">Kepala Sekolah</option>
                    <option value="wakasek">Wakil Kepala Sekolah</option>
                    <option value="guru">Guru</option>
                </select>
            </div>
        </div>

        <!-- ================= SECTION 1: TABEL ATAS (ADMIN & SUPER ADMIN) ================= -->
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400">
                        <ShieldCheck class="w-4 h-4" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Administrator &amp; Super Admin</h2>
                        <p class="text-xs text-muted-foreground">Akun dengan hak akses pengelolaan konfigurasi sistem</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                    {{ adminUsers.length }} Administrator
                </span>
            </div>

            <!-- Desktop Table 1: Admin / Super Admin -->
            <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-xs dark:border-sidebar-border overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border text-slate-600 dark:text-slate-300">
                        <tr>
                            <th class="px-4 py-3 font-bold w-14 text-center">No</th>
                            <th class="px-6 py-3 font-bold">Nama Pengguna</th>
                            <th class="px-6 py-3 font-bold">Email</th>
                            <th class="px-6 py-3 font-bold text-center w-36">Role</th>
                            <th class="px-6 py-3 font-bold text-right w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                        <tr v-for="(user, index) in adminUsers" :key="user.id" class="hover:bg-muted/40 transition-colors">
                            <td class="px-4 py-3.5 text-center text-xs font-medium text-muted-foreground">
                                {{ index + 1 }}
                            </td>
                            <td class="px-6 py-3.5 font-semibold text-slate-900 dark:text-slate-100">
                                {{ user.name }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600 dark:text-slate-300 text-xs">
                                {{ user.email }}
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <Badge 
                                    variant="outline"
                                    :class="getRoleBadgeClass(user.role)"
                                    class="text-xs font-bold uppercase tracking-wider px-2.5 py-0.5"
                                >
                                    {{ user.role }}
                                </Badge>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="flex justify-end gap-1.5">
                                    <Button 
                                        v-if="page.props.auth?.user?.role === 'super admin' || user.id === page.props.auth?.user?.id" 
                                        variant="outline" 
                                        size="icon" 
                                        class="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/50" 
                                        as-child
                                    >
                                        <Link :href="`/users/${user.id}/edit`" title="Edit Pengguna">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button 
                                        v-if="page.props.auth?.user?.role === 'super admin'" 
                                        variant="destructive" 
                                        size="icon" 
                                        class="h-8 w-8" 
                                        @click="deleteUser(user.id)" 
                                        :disabled="user.role === 'super admin'"
                                        :title="user.role === 'super admin' ? 'Super Admin tidak dapat dihapus' : 'Hapus Pengguna'"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="adminUsers.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                                <p class="text-sm font-medium">Tidak ada data administrator yang ditemukan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View 1: Admin / Super Admin -->
            <div class="md:hidden flex flex-col gap-2.5">
                <div 
                    v-for="(user, index) in adminUsers" 
                    :key="user.id" 
                    class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow-xs p-3.5 flex flex-col gap-2"
                >
                    <div class="flex justify-between items-start gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] font-bold flex items-center justify-center">
                                {{ index + 1 }}
                            </span>
                            <div>
                                <div class="font-bold text-sm text-slate-900 dark:text-slate-100">{{ user.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                            </div>
                        </div>
                        <Badge 
                            variant="outline"
                            :class="getRoleBadgeClass(user.role)"
                            class="text-[10px] font-bold uppercase tracking-wider"
                        >
                            {{ user.role }}
                        </Badge>
                    </div>
                    
                    <div v-if="page.props.auth?.user?.role === 'super admin' || user.id === page.props.auth?.user?.id" class="flex gap-2 justify-end mt-1 pt-2 border-t border-border">
                        <Button variant="outline" size="sm" as-child class="h-7 text-xs">
                            <Link :href="`/users/${user.id}/edit`">
                                <Edit class="h-3.5 w-3.5 mr-1" /> Edit
                            </Link>
                        </Button>
                        <Button 
                            v-if="page.props.auth?.user?.role === 'super admin' && user.role !== 'super admin'" 
                            variant="destructive" 
                            size="sm" 
                            class="h-7 text-xs"
                            @click="deleteUser(user.id)"
                        >
                            <Trash2 class="h-3.5 w-3.5 mr-1" /> Hapus
                        </Button>
                    </div>
                </div>

                <div v-if="adminUsers.length === 0" class="text-center p-6 bg-card rounded-xl border border-sidebar-border/70 text-muted-foreground text-xs">
                    Tidak ada data administrator yang ditemukan.
                </div>
            </div>
        </div>

        <!-- ================= SECTION 2: TABEL BAWAH (GURU & TENAGA PENDIDIK) ================= -->
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <GraduationCap class="w-4 h-4" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Data Guru &amp; Tenaga Pendidik</h2>
                        <p class="text-xs text-muted-foreground">Daftar akun guru pengajar, wakil kepala sekolah, dan kepala sekolah</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    Total: {{ totalGurus }} Guru
                </span>
            </div>

            <!-- Desktop Table 2: Guru & Tenaga Pendidik -->
            <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-xs dark:border-sidebar-border overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border text-slate-600 dark:text-slate-300">
                        <tr>
                            <th class="px-4 py-3 font-bold w-14 text-center">No</th>
                            <th class="px-6 py-3 font-bold">Nama Lengkap</th>
                            <th class="px-6 py-3 font-bold">Email</th>
                            <th class="px-6 py-3 font-bold text-center w-36">Role</th>
                            <th class="px-6 py-3 font-bold text-center w-36">Tipe Guru</th>
                            <th class="px-6 py-3 font-bold text-center w-36">Status Pegawai</th>
                            <th class="px-6 py-3 font-bold text-right w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                        <tr v-for="(user, index) in guruUsers.data" :key="user.id" class="hover:bg-muted/40 transition-colors">
                            <td class="px-4 py-3.5 text-center text-xs font-medium text-muted-foreground">
                                {{ (guruUsers.from || 1) + index }}
                            </td>
                            <td class="px-6 py-3.5 font-semibold text-slate-900 dark:text-slate-100">
                                {{ user.name }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600 dark:text-slate-300 text-xs">
                                {{ user.email }}
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <Badge 
                                    variant="outline"
                                    :class="getRoleBadgeClass(user.role)"
                                    class="text-xs font-bold uppercase tracking-wider px-2.5 py-0.5"
                                >
                                    {{ user.role }}
                                </Badge>
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <span v-if="user.tipe_guru" class="text-xs border px-2.5 py-1 rounded-md bg-muted/30 font-medium">
                                    {{ user.tipe_guru.nama_tipe }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground italic">Belum diatur</span>
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <span v-if="user.status_pegawai" class="text-xs border px-2.5 py-1 rounded-md bg-muted/30 uppercase font-semibold">
                                    {{ user.status_pegawai }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground italic">Belum diatur</span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="flex justify-end gap-1.5">
                                    <Button variant="outline" size="icon" class="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/50" as-child>
                                        <Link :href="`/users/${user.id}/edit`" title="Edit Guru">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="destructive" size="icon" class="h-8 w-8" @click="deleteUser(user.id)" title="Hapus Guru">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="guruUsers.data.length === 0">
                            <td colspan="7" class="px-6 py-10 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Users class="w-8 h-8 text-slate-300" />
                                    <p class="text-sm font-medium">Tidak ada data guru yang cocok dengan kriteria pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View 2: Guru & Tenaga Pendidik -->
            <div class="md:hidden flex flex-col gap-2.5">
                <div 
                    v-for="(user, index) in guruUsers.data" 
                    :key="user.id" 
                    class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow-xs p-4 flex flex-col gap-2.5"
                >
                    <div class="flex justify-between items-start gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] font-bold flex items-center justify-center">
                                {{ (guruUsers.from || 1) + index }}
                            </span>
                            <div>
                                <div class="font-bold text-sm text-slate-900 dark:text-slate-100">{{ user.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                            </div>
                        </div>
                        <Badge :class="getRoleBadgeClass(user.role)" variant="outline" class="text-[10px] font-bold uppercase tracking-wider">
                            {{ user.role }}
                        </Badge>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-100 dark:border-slate-800">
                        <div>
                            <span class="text-muted-foreground block text-[10px] uppercase font-semibold">Tipe Guru</span>
                            <span v-if="user.tipe_guru" class="font-medium text-slate-800 dark:text-slate-200">
                                {{ user.tipe_guru.nama_tipe }}
                            </span>
                            <span v-else class="italic text-muted-foreground">Belum diatur</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[10px] uppercase font-semibold">Status Pegawai</span>
                            <span v-if="user.status_pegawai" class="font-semibold uppercase text-slate-800 dark:text-slate-200">
                                {{ user.status_pegawai }}
                            </span>
                            <span v-else class="italic text-muted-foreground">Belum diatur</span>
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end pt-2 border-t border-border">
                        <Button variant="outline" size="sm" as-child class="h-7 text-xs">
                            <Link :href="`/users/${user.id}/edit`">
                                <Edit class="h-3.5 w-3.5 mr-1" /> Edit
                            </Link>
                        </Button>
                        <Button variant="destructive" size="sm" class="h-7 text-xs" @click="deleteUser(user.id)">
                            <Trash2 class="h-3.5 w-3.5 mr-1" /> Hapus
                        </Button>
                    </div>
                </div>

                <div v-if="guruUsers.data.length === 0" class="text-center p-8 bg-card rounded-xl border border-sidebar-border/70 text-muted-foreground text-xs">
                    Tidak ada data guru yang cocok dengan pencarian.
                </div>
            </div>

            <!-- Pagination khusus Tabel Guru -->
            <div v-if="guruUsers.links && guruUsers.links.length > 3" class="flex flex-wrap items-center justify-between gap-2 pt-2">
                <span class="text-xs text-muted-foreground">
                    Menampilkan {{ guruUsers.from || 0 }} - {{ guruUsers.to || 0 }} dari {{ guruUsers.total }} guru
                </span>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in guruUsers.links" :key="i">
                        <button
                            v-if="link.url"
                            type="button"
                            @click="router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="link.active 
                                ? 'bg-[#79AE6F] text-white font-bold' 
                                : 'bg-card text-muted-foreground hover:bg-muted border border-sidebar-border/70'"
                            v-html="link.label"
                        />
                        <span 
                            v-else 
                            class="px-2 py-1 text-xs text-muted-foreground/50" 
                            v-html="link.label" 
                        />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
