<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Edit, Plus, Trash2, Search } from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps<{
    jamPelajarans: {
        data: Array<{
            id: number;
            urutan_jam: number;
            waktu_mulai: string;
            waktu_selesai: string;
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
                title: 'Jam Pelajaran',
                href: '/jam-pelajaran',
            },
        ],
    },
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get('/jam-pelajaran', { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

const deleteJam = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus jam pelajaran ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/jam-pelajaran/${id}`, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Jam pelajaran berhasil dihapus.', 'success');
                },
                preserveScroll: true
            });
        }
    });
};
</script>

<template>
    <Head title="Jam Pelajaran" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Manajemen Jam Pelajaran</h1>
            <Button as-child>
                <Link href="/jam-pelajaran/create">
                    <Plus class="mr-2 h-4 w-4" /> Add Jam
                </Link>
            </Button>
        </div>

        <div class="flex justify-between items-center bg-card p-4 rounded-xl border border-sidebar-border/70 shadow dark:border-sidebar-border">
            <div class="relative w-full max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search"
                    type="search" 
                    placeholder="Cari jam pelajaran..." 
                    class="w-full bg-background pl-8" 
                />
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium text-center">Jam Ke</th>
                        <th class="px-4 py-3 font-medium">Waktu Mulai</th>
                        <th class="px-4 py-3 font-medium">Waktu Selesai</th>
                        <th class="px-4 py-3 font-medium text-right w-[150px]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="jam in jamPelajarans.data" :key="jam.id" class="border-b border-sidebar-border/70 dark:border-sidebar-border last:border-0 hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3 font-bold text-center">{{ jam.urutan_jam }}</td>
                        <td class="px-4 py-3 font-mono text-muted-foreground">{{ jam.waktu_mulai }}</td>
                        <td class="px-4 py-3 font-mono text-muted-foreground">{{ jam.waktu_selesai }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="icon" as-child>
                                    <Link :href="`/jam-pelajaran/${jam.id}/edit`">
                                        <Edit class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="destructive" size="icon" @click="deleteJam(jam.id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="jamPelajarans.data.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada data jam pelajaran.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Table -->
        <div class="md:hidden flex flex-col gap-4">
            <div 
                v-for="jam in jamPelajarans.data" 
                :key="jam.id" 
                class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow p-4 flex flex-col gap-2"
            >
                <div class="flex items-center justify-between border-b pb-2">
                    <span class="font-bold text-lg">Jam Ke-{{ jam.urutan_jam }}</span>
                </div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-muted-foreground">Waktu Mulai:</span>
                    <span class="font-mono bg-muted/50 px-2 py-0.5 rounded-md border">{{ jam.waktu_mulai }}</span>
                </div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-muted-foreground">Waktu Selesai:</span>
                    <span class="font-mono bg-muted/50 px-2 py-0.5 rounded-md border">{{ jam.waktu_selesai }}</span>
                </div>

                <div class="flex gap-2 justify-end mt-2 pt-3 border-t border-border">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/jam-pelajaran/${jam.id}/edit`">
                            <Edit class="h-4 w-4 mr-1" /> Edit
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteJam(jam.id)">
                        <Trash2 class="h-4 w-4 mr-1" /> Hapus
                    </Button>
                </div>
            </div>

            <div v-if="jamPelajarans.data.length === 0" class="text-center p-8 bg-card rounded-xl border border-sidebar-border/70 text-muted-foreground">
                Belum ada data jam pelajaran.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="jamPelajarans.links && jamPelajarans.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-4">
            <template v-for="(link, key) in jamPelajarans.links" :key="key">
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
