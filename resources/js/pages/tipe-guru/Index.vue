<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Edit, Plus, Trash2, Check, X, Search } from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref, watch, onMounted } from 'vue';
import debounce from 'lodash/debounce';
import { usePage } from '@inertiajs/vue3';

const page = usePage<any>();

const props = defineProps<{
    tipeGurus: {
        data: Array<{
            id: number;
            nama_tipe: string;
            akses_piket_kelas: boolean;
            akses_gms: boolean;
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
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tipe Guru',
                href: '/tipe-guru',
            },
        ],
    },
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get('/tipe-guru', { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

onMounted(() => {
    if (page.props.flash?.success) {
        Swal.fire({
            title: 'Berhasil!',
            text: page.props.flash.success,
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    }
});

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        Swal.fire('Berhasil!', flash.success, 'success');
    }
}, { deep: true });

const deleteTipe = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus tipe guru ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/tipe-guru/${id}`, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Tipe guru berhasil dihapus.', 'success');
                },
                preserveScroll: true
            });
        }
    });
};
</script>

<template>
    <Head title="Tipe Guru" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Manajemen Tipe Guru</h1>
            <Button as-child>
                <Link href="/tipe-guru/create">
                    <Plus class="mr-2 h-4 w-4" /> Tambah Tipe
                </Link>
            </Button>
        </div>

        <div class="flex justify-between items-center bg-card p-4 rounded-xl border border-sidebar-border/70 shadow dark:border-sidebar-border">
            <div class="relative w-full max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search"
                    type="search" 
                    placeholder="Cari tipe guru..." 
                    class="w-full bg-background pl-8" 
                />
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Tipe</th>
                        <th class="px-4 py-3 font-medium text-center">Piket Kelas</th>
                        <th class="px-4 py-3 font-medium text-center">Absensi GMS</th>
                        <th class="px-4 py-3 font-medium text-right w-[150px]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in tipeGurus.data" :key="item.id" class="border-b border-sidebar-border/70 dark:border-sidebar-border last:border-0 hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ item.nama_tipe }}</td>
                        <td class="px-4 py-3 text-center">
                            <Badge v-if="item.akses_piket_kelas" variant="default" class="bg-green-500 hover:bg-green-600">
                                <Check class="w-3 h-3 mr-1" /> Ya
                            </Badge>
                            <Badge v-else variant="destructive">
                                <X class="w-3 h-3 mr-1" /> Tidak
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Badge v-if="item.akses_gms" variant="default" class="bg-green-500 hover:bg-green-600">
                                <Check class="w-3 h-3 mr-1" /> Ya
                            </Badge>
                            <Badge v-else variant="destructive">
                                <X class="w-3 h-3 mr-1" /> Tidak
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="icon" as-child>
                                    <Link :href="`/tipe-guru/${item.id}/edit`">
                                        <Edit class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="destructive" size="icon" @click="deleteTipe(item.id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="tipeGurus.data.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada data tipe guru.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Table -->
        <div class="md:hidden flex flex-col gap-4">
            <div 
                v-for="item in tipeGurus.data" 
                :key="item.id" 
                class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow p-4 flex flex-col gap-3"
            >
                <div class="font-bold text-lg border-b pb-2">{{ item.nama_tipe }}</div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-muted-foreground">Piket Kelas:</span>
                    <Badge v-if="item.akses_piket_kelas" variant="default" class="bg-green-500 hover:bg-green-600">
                        <Check class="w-3 h-3 mr-1" /> Ya
                    </Badge>
                    <Badge v-else variant="destructive">
                        <X class="w-3 h-3 mr-1" /> Tidak
                    </Badge>
                </div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-muted-foreground">Absensi GMS:</span>
                    <Badge v-if="item.akses_gms" variant="default" class="bg-green-500 hover:bg-green-600">
                        <Check class="w-3 h-3 mr-1" /> Ya
                    </Badge>
                    <Badge v-else variant="destructive">
                        <X class="w-3 h-3 mr-1" /> Tidak
                    </Badge>
                </div>

                <div class="flex gap-2 justify-end mt-2 pt-3 border-t border-border">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/tipe-guru/${item.id}/edit`">
                            <Edit class="h-4 w-4 mr-1" /> Edit
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteTipe(item.id)">
                        <Trash2 class="h-4 w-4 mr-1" /> Hapus
                    </Button>
                </div>
            </div>

            <div v-if="tipeGurus.data.length === 0" class="text-center p-8 bg-card rounded-xl border border-sidebar-border/70 text-muted-foreground">
                Belum ada data tipe guru.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="tipeGurus.links && tipeGurus.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-4">
            <template v-for="(link, key) in tipeGurus.links" :key="key">
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
