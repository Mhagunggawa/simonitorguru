<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Edit, Plus, Trash2, Search, Filter } from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps<{
    kelas: {
        data: Array<{
            id: number;
            nama_kelas: string;
            jurusan: {
                id: number;
                nama_jurusan: string;
            };
            tingkat_kelas: {
                id: number;
                nama_tingkat: string;
            } | null;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    jurusans: Array<{
        id: number;
        nama_jurusan: string;
    }>;
    filters: {
        search?: string;
        jurusan_id?: string;
    };
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

watch([search, jurusan_id], debounce(([newSearch, newJurusanId]) => {
    router.get('/kelas', 
        { 
            search: newSearch,
            jurusan_id: newJurusanId
        }, 
        {
            preserveState: true,
            preserveScroll: true,
            replace: true
        }
    );
}, 300));

const deleteKelas = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus kelas ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/kelas/${id}`, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Kelas berhasil dihapus.', 'success');
                },
                preserveScroll: true
            });
        }
    });
};
</script>

<template>
    <Head title="Kelas" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Manajemen Kelas</h1>
            <Button as-child>
                <Link href="/kelas/create">
                    <Plus class="mr-2 h-4 w-4" /> Add Kelas
                </Link>
            </Button>
        </div>

        <div class="flex flex-row gap-2 bg-card p-4 rounded-xl border border-sidebar-border/70 shadow dark:border-sidebar-border items-center">
            <div class="relative flex-1 sm:max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search"
                    type="search" 
                    placeholder="Cari kelas..." 
                    class="w-full bg-background pl-8" 
                />
            </div>
            
            <!-- Desktop Select -->
            <div class="relative hidden sm:block w-[200px]">
                <Filter class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <select 
                    v-model="jurusan_id" 
                    class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-8 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="">Semua Jurusan</option>
                    <option v-for="jurusan in jurusans" :key="jurusan.id" :value="jurusan.id">
                        {{ jurusan.nama_jurusan }}
                    </option>
                </select>
            </div>

            <!-- Mobile Select (Icon only) -->
            <div class="relative block sm:hidden shrink-0">
                <Button variant="outline" size="icon" type="button" class="relative">
                    <Filter class="h-4 w-4 text-muted-foreground" />
                    <!-- Indikator aktif -->
                    <span v-if="jurusan_id !== ''" class="absolute top-1 right-1 h-2 w-2 rounded-full bg-primary"></span>
                    <select 
                        v-model="jurusan_id" 
                        class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                    >
                        <option value="">Semua Jurusan</option>
                        <option v-for="jurusan in jurusans" :key="jurusan.id" :value="jurusan.id">
                            {{ jurusan.nama_jurusan }}
                        </option>
                    </select>
                </Button>
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Kelas</th>
                        <th class="px-4 py-3 font-medium">Jurusan</th>
                        <th class="px-4 py-3 font-medium text-right w-[150px]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in kelas.data" :key="item.id" class="border-b border-sidebar-border/70 dark:border-sidebar-border last:border-0 hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3 font-medium">
                            <span v-if="item.tingkat_kelas" class="px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded-md text-xs mr-2">
                                {{ item.tingkat_kelas.nama_tingkat }}
                            </span>
                            {{ item.nama_kelas }}
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="item.jurusan" class="px-2 py-1 bg-secondary text-secondary-foreground rounded-md text-xs">
                                {{ item.jurusan.nama_jurusan }}
                            </span>
                            <span v-else class="text-xs text-muted-foreground italic">-</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="icon" as-child>
                                    <Link :href="`/kelas/${item.id}/edit`">
                                        <Edit class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="destructive" size="icon" @click="deleteKelas(item.id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="kelas.data.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                            No kelas found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Table -->
        <div class="md:hidden flex flex-col gap-4">
            <div 
                v-for="item in kelas.data" 
                :key="item.id" 
                class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow p-4 flex flex-col gap-3"
            >
                <div class="font-bold text-lg">
                    <span v-if="item.tingkat_kelas" class="px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded-md text-xs mr-2 align-middle">
                        {{ item.tingkat_kelas.nama_tingkat }}
                    </span>
                    <span class="align-middle">{{ item.nama_kelas }}</span>
                </div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-muted-foreground">Jurusan:</span>
                    <span v-if="item.jurusan" class="px-2 py-1 bg-secondary text-secondary-foreground rounded-md text-xs">
                        {{ item.jurusan.nama_jurusan }}
                    </span>
                    <span v-else class="italic text-muted-foreground">-</span>
                </div>

                <div class="flex gap-2 justify-end mt-2 pt-3 border-t border-border">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/kelas/${item.id}/edit`">
                            <Edit class="h-4 w-4 mr-1" /> Edit
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteKelas(item.id)">
                        <Trash2 class="h-4 w-4 mr-1" /> Hapus
                    </Button>
                </div>
            </div>

            <div v-if="kelas.data.length === 0" class="text-center p-8 bg-card rounded-xl border border-sidebar-border/70 text-muted-foreground">
                No kelas found.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="kelas.links && kelas.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-4">
            <template v-for="(link, key) in kelas.links" :key="key">
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
