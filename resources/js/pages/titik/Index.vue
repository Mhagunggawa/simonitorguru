<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Edit, Plus, Trash2, Search } from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps<{
    titiks: {
        data: Array<{
            id: number;
            nama_titik: string;
            jurusans: Array<{
                id: number;
                nama_jurusan: string;
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
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Manajemen Titik',
                href: '/titik',
            },
        ],
    },
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get('/titik', { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

const deleteTitik = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus titik ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/titik/${id}`, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Titik berhasil dihapus.', 'success');
                },
                preserveScroll: true
            });
        }
    });
};
</script>

<template>
    <Head title="Manajemen Titik" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Manajemen Titik</h1>
            <Button as-child>
                <Link href="/titik/create">
                    <Plus class="mr-2 h-4 w-4" /> Add Titik
                </Link>
            </Button>
        </div>

        <div class="flex justify-between items-center bg-card p-4 rounded-xl border border-sidebar-border/70 shadow dark:border-sidebar-border">
            <div class="relative w-full max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search"
                    type="search" 
                    placeholder="Cari titik..." 
                    class="w-full bg-background pl-8" 
                />
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Titik</th>
                        <th class="px-4 py-3 font-medium">Jurusan yang Dicakup</th>
                        <th class="px-4 py-3 font-medium text-right w-[150px]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="titik in titiks.data" :key="titik.id" class="border-b border-sidebar-border/70 dark:border-sidebar-border last:border-0 hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ titik.nama_titik }}</td>
                        <td class="px-4 py-3">
                            <div v-if="titik.jurusans.length > 0" class="flex flex-wrap gap-1">
                                <span v-for="jurusan in titik.jurusans" :key="jurusan.id" class="px-2 py-1 bg-secondary text-secondary-foreground rounded-md text-xs">
                                    {{ jurusan.nama_jurusan }}
                                </span>
                            </div>
                            <span v-else class="text-xs text-muted-foreground italic">Belum ada jurusan</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="icon" as-child>
                                    <Link :href="`/titik/${titik.id}/edit`">
                                        <Edit class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="destructive" size="icon" @click="deleteTitik(titik.id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="titiks.data.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada data titik.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Table -->
        <div class="md:hidden flex flex-col gap-4">
            <div 
                v-for="titik in titiks.data" 
                :key="titik.id" 
                class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow p-4 flex flex-col gap-3"
            >
                <div class="font-bold text-lg">{{ titik.nama_titik }}</div>
                
                <div class="flex flex-col gap-1 mt-1">
                    <span class="text-sm font-medium text-muted-foreground">Jurusan yang Dicakup:</span>
                    <div v-if="titik.jurusans.length > 0" class="flex flex-wrap gap-1">
                        <span v-for="jurusan in titik.jurusans" :key="jurusan.id" class="px-2 py-1 bg-secondary text-secondary-foreground rounded-md text-xs">
                            {{ jurusan.nama_jurusan }}
                        </span>
                    </div>
                    <span v-else class="text-xs text-muted-foreground italic">Belum ada jurusan</span>
                </div>

                <div class="flex gap-2 justify-end mt-2 pt-3 border-t border-border">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/titik/${titik.id}/edit`">
                            <Edit class="h-4 w-4 mr-1" /> Edit
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteTitik(titik.id)">
                        <Trash2 class="h-4 w-4 mr-1" /> Hapus
                    </Button>
                </div>
            </div>

            <div v-if="titiks.data.length === 0" class="text-center p-8 bg-card rounded-xl border border-sidebar-border/70 text-muted-foreground">
                Belum ada data titik.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="titiks.links && titiks.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-4">
            <template v-for="(link, key) in titiks.links" :key="key">
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
