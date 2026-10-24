<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Edit, Plus, Trash2 } from '@lucide/vue';
import Swal from 'sweetalert2';

defineProps<{
    jadwalPikets: any;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Jadwal Piket',
                href: '/jadwal-piket',
            },
        ],
    },
});

const deleteJadwal = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus jadwal piket ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/jadwal-piket/${id}`, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Jadwal piket berhasil dihapus.', 'success');
                },
                onError: () => {
                    Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus jadwal piket.', 'error');
                }
            });
        }
    });
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(date);
};

const formatTime = (time: string) => time ? time.substring(0, 5) : '';
</script>

<template>
    <Head title="Jadwal Piket" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Jadwal Piket</h1>
            <Button as-child>
                <Link href="/jadwal-piket/create">
                    <Plus class="mr-2 h-4 w-4" /> Add Piket
                </Link>
            </Button>
        </div>

        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-medium">Tanggal Piket</th>
                        <th class="px-4 py-3 font-medium">Jam Piket</th>
                        <th class="px-4 py-3 font-medium">Titik Lokasi</th>
                        <th class="px-4 py-3 font-medium">Guru Piket</th>
                        <th class="px-4 py-3 font-medium text-right w-[150px]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in jadwalPikets.data" :key="item.id" class="border-b border-sidebar-border/70 dark:border-sidebar-border last:border-0 hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ formatDate(item.tanggal_piket) }}</td>
                        <td class="px-4 py-3">
                            <span v-if="item.jam_pelajarans && item.jam_pelajarans.length">
                                Jam ke-{{ item.jam_pelajarans.map((j: any) => j.urutan_jam).join(', ') }}
                                <span class="text-xs text-muted-foreground block mt-1">
                                    ({{ formatTime(item.jam_pelajarans[0].waktu_mulai) }} - {{ formatTime(item.jam_pelajarans[item.jam_pelajarans.length - 1].waktu_selesai) }})
                                </span>
                            </span>
                            <span v-else class="text-muted-foreground">-</span>
                        </td>
                        <td class="px-4 py-3">{{ item.titik?.nama_titik || '-' }}</td>
                        <td class="px-4 py-3">{{ item.guru_piket?.name || '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="icon" as-child>
                                    <Link :href="`/jadwal-piket/${item.id}/edit`">
                                        <Edit class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button variant="destructive" size="icon" @click="deleteJadwal(item.id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="jadwalPikets.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                            No jadwal piket found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Table -->
        <div class="md:hidden flex flex-col gap-4">
            <div 
                v-for="item in jadwalPikets.data" 
                :key="item.id" 
                class="bg-card text-card-foreground rounded-xl border border-sidebar-border/70 shadow p-4 flex flex-col gap-3"
            >
                <div class="font-bold text-lg border-b pb-2">{{ formatDate(item.tanggal_piket) }}</div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-muted-foreground">Guru Piket:</span>
                    <span class="font-medium text-right">{{ item.guru_piket?.name || '-' }}</span>
                </div>
                
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-muted-foreground">Titik Lokasi:</span>
                    <span class="text-right">{{ item.titik?.nama_titik || '-' }}</span>
                </div>
                
                <div class="flex flex-col gap-1 text-sm mt-1">
                    <span class="font-medium text-muted-foreground">Jam Piket:</span>
                    <span v-if="item.jam_pelajarans && item.jam_pelajarans.length">
                        Jam ke-{{ item.jam_pelajarans.map((j: any) => j.urutan_jam).join(', ') }}
                        <span class="text-xs text-muted-foreground ml-1">
                            ({{ formatTime(item.jam_pelajarans[0].waktu_mulai) }} - {{ formatTime(item.jam_pelajarans[item.jam_pelajarans.length - 1].waktu_selesai) }})
                        </span>
                    </span>
                    <span v-else class="text-muted-foreground">-</span>
                </div>
                
                <div class="flex justify-end gap-2 mt-2 pt-3 border-t">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/jadwal-piket/${item.id}/edit`">
                            <Edit class="h-4 w-4 mr-2" /> Edit
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteJadwal(item.id)">
                        <Trash2 class="h-4 w-4 mr-2" /> Hapus
                    </Button>
                </div>
            </div>
            
            <div v-if="jadwalPikets.data.length === 0" class="text-center p-8 border border-sidebar-border/70 rounded-xl bg-card text-muted-foreground shadow-sm">
                No jadwal piket found.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="jadwalPikets.links && jadwalPikets.links.length > 3" class="flex flex-wrap justify-center gap-1 mt-4">
            <template v-for="(link, key) in jadwalPikets.links" :key="key">
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
