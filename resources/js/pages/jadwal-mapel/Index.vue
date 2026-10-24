<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Plus } from '@lucide/vue';
import { ref, computed } from 'vue';

const props = defineProps<{
    jadwalMapels: Array<{
        id: number;
        hari: string;
        kelas_id: number;
        mata_pelajaran: { nama_mapel: string };
        guru: { name: string };
        jam_pelajarans: Array<{ id: number, urutan_jam: number, waktu_mulai: string, waktu_selesai: string }>;
    }>;
    kelas: Array<{ id: number; nama_kelas: string }>;
    jamPelajarans: Array<{ id: number; urutan_jam: number; waktu_mulai: string; waktu_selesai: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Jadwal Mapel',
                href: '/jadwal-mapel',
            },
        ],
    },
});

const selectedKelas = ref<number | null>(null);

const hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

const getJadwal = (jamId: number, hari: string) => {
    if (!selectedKelas.value) return null;
    
    // Find jadwal that matches kelas, hari, and contains the jamId
    return props.jadwalMapels.find(j => 
        j.kelas_id === selectedKelas.value && 
        j.hari === hari &&
        j.jam_pelajarans.some(jam => jam.id === jamId)
    );
};
</script>

<template>
    <Head title="Jadwal Mata Pelajaran" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Jadwal Mata Pelajaran</h1>
            <Button as-child>
                <Link href="/jadwal-mapel/create">
                    <Plus class="mr-2 h-4 w-4" /> Kelola Jadwal
                </Link>
            </Button>
        </div>

        <div class="bg-white dark:bg-slate-900 shadow-sm sm:rounded-xl border border-gray-100 dark:border-slate-800 p-4">
            <div class="max-w-xs">
                <label for="kelas" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Kelas</label>
                <select 
                    id="kelas" 
                    v-model="selectedKelas"
                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option :value="null">-- Pilih Kelas --</option>
                    <option v-for="k in kelas" :key="k.id" :value="k.id">
                        {{ k.nama_kelas }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!selectedKelas" class="flex flex-col items-center justify-center p-12 bg-white dark:bg-slate-900 shadow-sm sm:rounded-xl border border-gray-100 dark:border-slate-800 text-center">
            <svg class="w-16 h-16 text-gray-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Silakan pilih kelas</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pilih kelas pada dropdown di atas untuk melihat jadwal pelajarannya.</p>
        </div>

        <!-- Matriks Table -->
        <div v-else class="bg-white dark:bg-slate-900 shadow-sm sm:rounded-xl border border-gray-100 dark:border-slate-800 overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-slate-800/50 text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-3 font-semibold border-r border-gray-100 dark:border-slate-800 w-[120px] text-center bg-gray-100 dark:bg-slate-800">
                            Jam \ Hari
                        </th>
                        <th v-for="hari in hariList" :key="hari" class="px-4 py-3 font-semibold text-center border-r border-gray-100 dark:border-slate-800 last:border-0 min-w-[150px]">
                            {{ hari }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    <tr v-for="jam in jamPelajarans" :key="jam.id" class="hover:bg-blue-50/30 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-4 py-3 border-r border-gray-100 dark:border-slate-800 text-center bg-gray-50/50 dark:bg-slate-800/20">
                            <div class="font-semibold text-gray-700 dark:text-gray-300">Jam {{ jam.urutan_jam }}</div>
                            <div class="text-[10px] text-gray-500">{{ jam.waktu_mulai.substring(0,5) }} - {{ jam.waktu_selesai.substring(0,5) }}</div>
                        </td>
                        <td v-for="hari in hariList" :key="hari" class="px-4 py-3 border-r border-gray-100 dark:border-slate-800 last:border-0 text-center align-middle">
                            <template v-if="getJadwal(jam.id, hari)">
                                <div class="flex flex-col items-center justify-center p-2 rounded-lg bg-indigo-50/50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/30">
                                    <span class="font-bold text-indigo-700 dark:text-indigo-400 text-xs mb-1">
                                        {{ getJadwal(jam.id, hari)?.mata_pelajaran?.nama_mapel }}
                                    </span>
                                    <span class="text-[11px] text-gray-600 dark:text-gray-400">
                                        {{ getJadwal(jam.id, hari)?.guru?.name }}
                                    </span>
                                </div>
                            </template>
                            <template v-else>
                                <span class="text-gray-300 dark:text-slate-600 text-xs">-</span>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
