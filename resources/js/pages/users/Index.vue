<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Edit, Plus, Trash2, Search } from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const page = usePage<any>();

const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            role: string;
            tipe_guru?: {
                id: number;
                nama_tipe: string;
            };
            status_pegawai?: string | null;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
        role?: string;
    };
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
        search: searchValue, 
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
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus Semua!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/users/${id}`, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Pengguna beserta riwayat terkait berhasil dihapus.', 'success');
                },
                onError: () => {
                    Swal.fire('Gagal!', 'Terjadi kendala saat menghapus data pengguna.', 'error');
                },
                preserveScroll: true
            });
        }
    });
};

const getRoleBadgeVariant = (role: string) => {
    switch (role) {
        case 'admin': return 'destructive';
        case 'wakasek': return 'default';
        case 'kepsek': return 'secondary';
        default: return 'secondary';
    }
};
</script>

<template>
    <Head title="User Management" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Users</h1>
            <Button as-child>
                <Link href="/users/create">
                    <Plus class="mr-2 h-4 w-4" /> Add User
                </Link>
            </Button>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center bg-card p-4 rounded-xl border border-sidebar-border/70 shadow dark:border-sidebar-border gap-4">
            <div class="relative w-full sm:max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search"
                    type="search" 
                    placeholder="Cari user (nama, email)..." 
                    class="w-full bg-background pl-8" 
                />
            </div>
            
            <div class="w-full sm:w-auto flex items-center gap-2">
                <span class="text-sm text-muted-foreground font-medium hidden sm:inline-block whitespace-nowrap">Role:</span>
                <select 
                    v-model="role"
                    class="w-full sm:w-auto h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
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

        <!-- Desktop Table -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Tipe Guru</th>
                        <th class="px-4 py-3 font-medium">Status Pegawai</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users.data" :key="user.id" class="border-b border-sidebar-border/70 dark:border-sidebar-border last:border-0 hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="getRoleBadgeVariant(user.role)" class="capitalize">
                                {{ user.role }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="user.tipe_guru" class="text-xs border px-2 py-1 rounded-md bg-muted/30">
                                {{ user.tipe_guru.nama_tipe }}
                            </span>
                            <span v-else-if="['guru', 'wakasek', 'kepsek'].includes(user.role)" class="text-xs text-muted-foreground italic">
                                Belum diatur
                            </span>
                            <span v-else class="text-xs text-muted-foreground">-</span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="user.status_pegawai" class="text-xs border px-2 py-1 rounded-md bg-muted/30 uppercase">
                                {{ user.status_pegawai }}
                            </span>
                            <span v-else-if="['guru', 'wakasek', 'kepsek'].includes(user.role)" class="text-xs text-muted-foreground italic">
                                Belum diatur
                            </span>
                            <span v-else class="text-xs text-muted-foreground">-</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="icon" as-child>
                                    <Link :href="`/users/${user.id}/edit`">
                                        <Edit class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="destructive" size="icon" @click="deleteUser(user.id)" :disabled="user.role === 'super admin'">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                            No users found.
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
                class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow p-4 flex flex-col gap-2"
            >
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-bold text-lg">{{ user.name }}</div>
                        <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                    </div>
                    <Badge :variant="getRoleBadgeVariant(user.role)" class="capitalize">
                        {{ user.role }}
                    </Badge>
                </div>
                
                <div class="text-sm flex gap-2 items-center mt-1">
                    <span class="font-medium text-muted-foreground">Tipe Guru:</span>
                    <span v-if="user.tipe_guru" class="px-2 py-0.5 rounded-md bg-muted/50 border">
                        {{ user.tipe_guru.nama_tipe }}
                    </span>
                    <span v-else-if="['guru', 'wakasek', 'kepsek'].includes(user.role)" class="italic text-muted-foreground">Belum diatur</span>
                    <span v-else class="text-muted-foreground">-</span>
                </div>

                <div class="text-sm flex gap-2 items-center mt-1">
                    <span class="font-medium text-muted-foreground">Status Pegawai:</span>
                    <span v-if="user.status_pegawai" class="px-2 py-0.5 rounded-md bg-muted/50 border uppercase">
                        {{ user.status_pegawai }}
                    </span>
                    <span v-else-if="['guru', 'wakasek', 'kepsek'].includes(user.role)" class="italic text-muted-foreground">Belum diatur</span>
                    <span v-else class="text-muted-foreground">-</span>
                </div>

                <div class="flex gap-2 justify-end mt-2 pt-3 border-t border-border">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/users/${user.id}/edit`">
                            <Edit class="h-4 w-4 mr-1" /> Edit
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteUser(user.id)" :disabled="user.role === 'super admin'">
                        <Trash2 class="h-4 w-4 mr-1" /> Hapus
                    </Button>
                </div>
            </div>

            <div v-if="users.data.length === 0" class="text-center p-8 bg-card rounded-xl border border-sidebar-border/70 text-muted-foreground">
                No users found.
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
