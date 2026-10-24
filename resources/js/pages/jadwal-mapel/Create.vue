<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Save, ArrowLeft, Loader2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps<{
    kelas: Array<{ id: number; nama_kelas: string }>;
    mataPelajarans: Array<{ id: number; nama_mapel: string }>;
    jamPelajarans: Array<{ id: number; urutan_jam: number; waktu_mulai: string; waktu_selesai: string }>;
    gurus: Array<{ id: number; name: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Jadwal Mapel',
                href: '/jadwal-mapel',
            },
            {
                title: 'Kelola Jadwal (Bulk)',
                href: '/jadwal-mapel/create',
            },
        ],
    },
});

const hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

const form = useForm({
    kelas_id: '' as number | '',
    hari: '' as string,
    jadwal: props.jamPelajarans.map(jam => ({
        jam_pelajaran_id: jam.id,
        mata_pelajaran_id: '' as number | '',
        guru_id: '' as number | ''
    }))
});

const isFetching = ref(false);

watch([() => form.kelas_id, () => form.hari], async ([newKelas, newHari]) => {
    if (newKelas && newHari) {
        isFetching.value = true;
        form.jadwal = props.jamPelajarans.map(jam => ({
            jam_pelajaran_id: jam.id,
            mata_pelajaran_id: '',
            guru_id: ''
        }));
        
        try {
            const response = await axios.get('/jadwal-mapel/fetch', {
                params: {
                    kelas_id: newKelas,
                    hari: newHari
                }
            });
            
            const existingJadwals = response.data;
            
            // Populate form with existing data
            existingJadwals.forEach((jadwal: any) => {
                jadwal.jam_pelajarans.forEach((jamPelajaran: any) => {
                    const row = form.jadwal.find(j => j.jam_pelajaran_id === jamPelajaran.id);
                    if (row) {
                        row.mata_pelajaran_id = jadwal.mata_pelajaran_id;
                        row.guru_id = jadwal.guru_id;
                    }
                });
            });
            
        } catch (error) {
            console.error("Error fetching existing schedule", error);
            Swal.fire('Error', 'Gagal memuat jadwal yang sudah ada.', 'error');
        } finally {
            isFetching.value = false;
        }
    } else {
        // Clear if not fully selected
        form.jadwal = props.jamPelajarans.map(jam => ({
            jam_pelajaran_id: jam.id,
            mata_pelajaran_id: '',
            guru_id: ''
        }));
    }
});

const submit = () => {
    form.post('/jadwal-mapel', {
        onSuccess: () => {
            Swal.fire('Berhasil!', 'Jadwal berhasil disimpan.', 'success');
        },
        onError: () => {
            Swal.fire('Gagal!', 'Pastikan form diisi dengan benar.', 'error');
        }
    });
};

const setIstirahat = (index: number) => {
    form.jadwal[index].mata_pelajaran_id = '';
    form.jadwal[index].guru_id = '';
};
</script>

<template>
    <Head title="Kelola Jadwal Mapel" />

    <div class="flex flex-col gap-6 p-4 max-w-5xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Kelola Jadwal Mapel</h1>
            <Button variant="outline" as-child>
                <Link href="/jadwal-mapel">
                    <ArrowLeft class="mr-2 h-4 w-4" /> Kembali
                </Link>
            </Button>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            
            <!-- Filters Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white dark:bg-slate-900 shadow-sm sm:rounded-xl border border-gray-100 dark:border-slate-800 p-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">1. Pilih Kelas</label>
                    <select 
                        v-model="form.kelas_id" 
                        required
                        class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        :class="{'border-red-500': form.errors.kelas_id}"
                    >
                        <option value="" disabled>-- Pilih Kelas --</option>
                        <option v-for="k in kelas" :key="k.id" :value="k.id">
                            {{ k.nama_kelas }}
                        </option>
                    </select>
                    <p v-if="form.errors.kelas_id" class="text-sm text-red-500 mt-1">{{ form.errors.kelas_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">2. Pilih Hari</label>
                    <select 
                        v-model="form.hari" 
                        required
                        class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        :class="{'border-red-500': form.errors.hari}"
                    >
                        <option value="" disabled>-- Pilih Hari --</option>
                        <option v-for="h in hariList" :key="h" :value="h">
                            {{ h }}
                        </option>
                    </select>
                    <p v-if="form.errors.hari" class="text-sm text-red-500 mt-1">{{ form.errors.hari }}</p>
                </div>
            </div>

            <!-- Loader -->
            <div v-if="isFetching" class="flex flex-col items-center justify-center py-12 text-blue-600">
                <Loader2 class="h-8 w-8 animate-spin mb-4" />
                <p>Memuat data jadwal...</p>
            </div>

            <!-- Bulk Form Section -->
            <div v-if="!isFetching && form.kelas_id && form.hari" class="bg-white dark:bg-slate-900 shadow-sm sm:rounded-xl border border-gray-100 dark:border-slate-800 overflow-hidden">
                <div class="bg-gray-50 dark:bg-slate-800/50 p-4 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-gray-200">Input Jadwal</h2>
                        <p class="text-sm text-gray-500">Biarkan kosong jika jam pelajaran istirahat/tidak ada KBM.</p>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="(jam, index) in jamPelajarans" :key="jam.id" class="flex flex-col sm:flex-row gap-4 items-start sm:items-center py-4 border-b border-gray-100 dark:border-slate-800 last:border-0">
                            
                            <!-- Waktu Column -->
                            <div class="w-full sm:w-1/4 flex flex-col">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Jam ke-{{ jam.urutan_jam }}</span>
                                <span class="text-xs text-gray-500">{{ jam.waktu_mulai.substring(0,5) }} - {{ jam.waktu_selesai.substring(0,5) }}</span>
                            </div>
                            
                            <!-- Dropdowns -->
                            <div class="w-full sm:w-3/4 grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3">
                                <div>
                                    <select 
                                        v-model="form.jadwal[index].mata_pelajaran_id"
                                        class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-1 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    >
                                        <option value="">-- Kosong / Istirahat --</option>
                                        <option v-for="mapel in mataPelajarans" :key="mapel.id" :value="mapel.id">
                                            {{ mapel.nama_mapel }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div>
                                    <select 
                                        v-model="form.jadwal[index].guru_id"
                                        :disabled="!form.jadwal[index].mata_pelajaran_id"
                                        class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-1 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50 disabled:bg-gray-100 dark:disabled:bg-gray-800"
                                    >
                                        <option value="">-- Pilih Guru --</option>
                                        <option v-for="guru in gurus" :key="guru.id" :value="guru.id">
                                            {{ guru.name }}
                                        </option>
                                    </select>
                                </div>
                                
                                <Button type="button" variant="outline" size="sm" @click="setIstirahat(index)" class="h-9">
                                    Clear
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-slate-800/50 p-4 border-t border-gray-100 dark:border-slate-800 flex justify-end">
                    <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        Simpan Jadwal Hari Ini
                    </Button>
                </div>
            </div>
            
            <div v-else-if="!form.kelas_id || !form.hari" class="flex flex-col items-center justify-center p-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-xl">
                <p class="text-gray-500">Pilih Kelas dan Hari di atas untuk mulai mengisi jadwal.</p>
            </div>
            
        </form>
    </div>
</template>
