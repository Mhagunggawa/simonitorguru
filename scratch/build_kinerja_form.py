import os

vue_form_path = "resources/js/pages/Kinerja/FormTahap1.vue"

vue_form_code = """<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Plus, Trash2, Video, ArrowRight, ArrowLeft } from "lucide-vue-next";

const props = defineProps<{
    guru: any;
    kinerja: any;
    currentYear: number;
}>();

// Initialize form
const form = useForm({
    tempat: props.kinerja?.tempat || '',
    tanggal: props.kinerja?.tanggal || '',
    waktu: props.kinerja?.waktu || '',
    fokuses: props.kinerja?.fokuses?.length ? props.kinerja.fokuses.map((f: any) => ({
        nama_fokus: f.nama_fokus || '',
        perilaku_dianjurkan: f.perilaku_dianjurkan || [''],
        perilaku_dihindari: f.perilaku_dihindari || [''],
        penilaian: f.penilaian || null,
        catatan: f.catatan || ''
    })) : [
        {
            nama_fokus: '',
            perilaku_dianjurkan: [''],
            perilaku_dihindari: [''],
            penilaian: null,
            catatan: ''
        }
    ],
    rekomendasi: props.kinerja?.rekomendasi || '',
    video_link: props.kinerja?.video_link || '',
});

const addFokus = () => {
    form.fokuses.push({
        nama_fokus: '',
        perilaku_dianjurkan: [''],
        perilaku_dihindari: [''],
        penilaian: null,
        catatan: ''
    });
};

const removeFokus = (index: number) => {
    form.fokuses.splice(index, 1);
};

const addDianjurkan = (fokusIndex: number) => {
    form.fokuses[fokusIndex].perilaku_dianjurkan.push('');
};

const removeDianjurkan = (fokusIndex: number, dIndex: number) => {
    form.fokuses[fokusIndex].perilaku_dianjurkan.splice(dIndex, 1);
};

const addDihindari = (fokusIndex: number) => {
    form.fokuses[fokusIndex].perilaku_dihindari.push('');
};

const removeDihindari = (fokusIndex: number, dIndex: number) => {
    form.fokuses[fokusIndex].perilaku_dihindari.splice(dIndex, 1);
};

const submit = () => {
    form.post(`/kinerja/${props.guru.id}/tahap-1`);
};
</script>

<template>
    <AppLayout>
        <Head :title="`Kinerja - ${guru.name}`" />

        <div class="max-w-5xl mx-auto p-4 md:p-8 space-y-8">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Observasi Kinerja (Tahap 1)</h1>
                <p class="text-muted-foreground mt-1">Isi form penilaian kinerja untuk guru {{ guru.name }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                
                <!-- Identitas & Jadwal -->
                <Card class="border-t-4 border-t-blue-500 shadow-md">
                    <CardHeader class="bg-blue-50/50 dark:bg-blue-900/10 border-b pb-4">
                        <CardTitle class="text-xl text-blue-700 dark:text-blue-400">Identitas & Jadwal Pelaksanaan</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                        <div class="space-y-2">
                            <Label>Nama Guru</Label>
                            <Input :value="guru.name" disabled class="bg-slate-100" />
                        </div>
                        <div class="space-y-2">
                            <Label>Nama Pengamat (Observer)</Label>
                            <Input :value="$page.props.auth.user.name" disabled class="bg-slate-100" />
                        </div>
                        <div class="space-y-2">
                            <Label>Periode</Label>
                            <Input :value="`Januari - Desember ${currentYear}`" disabled class="bg-slate-100" />
                        </div>
                        <div class="space-y-2">
                            <Label>Tempat / Lokasi</Label>
                            <Input v-model="form.tempat" placeholder="Contoh: Ruang Kelas X-1" required />
                        </div>
                        <div class="space-y-2">
                            <Label>Hari / Tanggal</Label>
                            <Input type="date" v-model="form.tanggal" required />
                        </div>
                        <div class="space-y-2">
                            <Label>Waktu</Label>
                            <Input type="time" v-model="form.waktu" required />
                        </div>
                        
                        <div class="col-span-1 md:col-span-2 space-y-2 mt-2 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700">
                            <Label class="text-base font-semibold mb-2 block">Tujuan Observasi:</Label>
                            <ul class="list-decimal pl-5 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                                <li>Guru menggunakan rubrik observasi kelas sebagai perilaku yang akan ditampilkan pada observasi kinerja.</li>
                                <li>Kepala sekolah (atau pengamat) mencatat bukti perilaku dan penilaian efektivitas perilaku yang ditampilkan guru selama observasi kinerja beserta rekomendasi tindak lanjutnya.</li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>

                <!-- Rencana Hasil Kerja (Fokus Perilaku) -->
                <Card class="border-t-4 border-t-emerald-500 shadow-md">
                    <CardHeader class="bg-emerald-50/50 dark:bg-emerald-900/10 border-b pb-4 flex flex-row items-center justify-between">
                        <div>
                            <CardTitle class="text-xl text-emerald-700 dark:text-emerald-400">Rencana Hasil Kerja</CardTitle>
                            <CardDescription>Isi fokus perilaku dan penjabarannya di bawah ini</CardDescription>
                        </div>
                        <Button type="button" variant="outline" size="sm" @click="addFokus" class="border-emerald-200 text-emerald-700 hover:bg-emerald-100">
                            <Plus class="w-4 h-4 mr-1" /> Tambah Fokus Perilaku
                        </Button>
                    </CardHeader>
                    
                    <CardContent class="p-6 space-y-8">
                        <div v-for="(fokus, fIndex) in form.fokuses" :key="fIndex" class="relative p-5 border rounded-xl bg-white dark:bg-slate-900 shadow-sm">
                            <div class="absolute -top-3 -left-3 w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-sm shadow-md">
                                {{ fIndex + 1 }}
                            </div>
                            
                            <Button v-if="form.fokuses.length > 1" type="button" variant="ghost" size="icon" @click="removeFokus(fIndex)" class="absolute top-2 right-2 text-red-500 hover:bg-red-50">
                                <Trash2 class="w-4 h-4" />
                            </Button>

                            <div class="space-y-6 mt-2">
                                <div class="space-y-2">
                                    <Label class="text-base">Fokus Perilaku</Label>
                                    <Textarea v-model="fokus.nama_fokus" placeholder="Tuliskan fokus perilaku di sini..." required class="min-h-[80px]" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Perilaku Dianjurkan -->
                                    <div class="space-y-3 bg-blue-50/30 dark:bg-blue-900/10 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                                        <Label class="text-blue-700 dark:text-blue-400 flex items-center justify-between">
                                            Perilaku yang dianjurkan
                                            <Button type="button" variant="ghost" size="sm" @click="addDianjurkan(fIndex)" class="h-6 px-2 text-xs text-blue-600">
                                                <Plus class="w-3 h-3 mr-1" /> Tambah
                                            </Button>
                                        </Label>
                                        <div v-for="(dianjurkan, dIndex) in fokus.perilaku_dianjurkan" :key="'d-'+dIndex" class="flex gap-2">
                                            <Input v-model="fokus.perilaku_dianjurkan[dIndex]" placeholder="Contoh perilaku..." required />
                                            <Button v-if="fokus.perilaku_dianjurkan.length > 1" type="button" variant="ghost" size="icon" @click="removeDianjurkan(fIndex, dIndex)" class="shrink-0 text-red-400">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </div>

                                    <!-- Perilaku Dihindari -->
                                    <div class="space-y-3 bg-rose-50/30 dark:bg-rose-900/10 p-4 rounded-lg border border-rose-100 dark:border-rose-800">
                                        <Label class="text-rose-700 dark:text-rose-400 flex items-center justify-between">
                                            Perilaku yang dihindari
                                            <Button type="button" variant="ghost" size="sm" @click="addDihindari(fIndex)" class="h-6 px-2 text-xs text-rose-600">
                                                <Plus class="w-3 h-3 mr-1" /> Tambah
                                            </Button>
                                        </Label>
                                        <div v-for="(dihindari, dhIndex) in fokus.perilaku_dihindari" :key="'dh-'+dhIndex" class="flex gap-2">
                                            <Input v-model="fokus.perilaku_dihindari[dhIndex]" placeholder="Contoh perilaku..." required />
                                            <Button v-if="fokus.perilaku_dihindari.length > 1" type="button" variant="ghost" size="icon" @click="removeDihindari(fIndex, dhIndex)" class="shrink-0 text-red-400">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Penilaian -->
                                <div class="space-y-3 pt-4 border-t">
                                    <Label class="text-base">Penilaian Observasi Kinerja</Label>
                                    <div class="flex flex-col sm:flex-row gap-4">
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" v-model="fokus.penilaian" :value="1" class="peer sr-only" />
                                            <div class="p-3 rounded-lg border-2 text-center transition-all peer-checked:bg-pink-100 peer-checked:border-pink-500 peer-checked:text-pink-800 dark:peer-checked:bg-pink-900/30 dark:peer-checked:text-pink-200 border-slate-200 hover:border-pink-200">
                                                Belum dilakukan
                                            </div>
                                        </label>
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" v-model="fokus.penilaian" :value="2" class="peer sr-only" />
                                            <div class="p-3 rounded-lg border-2 text-center transition-all peer-checked:bg-amber-100 peer-checked:border-amber-500 peer-checked:text-amber-800 dark:peer-checked:bg-amber-900/30 dark:peer-checked:text-amber-200 border-slate-200 hover:border-amber-200">
                                                Dilakukan tapi belum efektif
                                            </div>
                                        </label>
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" v-model="fokus.penilaian" :value="3" class="peer sr-only" />
                                            <div class="p-3 rounded-lg border-2 text-center transition-all peer-checked:bg-emerald-100 peer-checked:border-emerald-500 peer-checked:text-emerald-800 dark:peer-checked:bg-emerald-900/30 dark:peer-checked:text-emerald-200 border-slate-200 hover:border-emerald-200">
                                                Dilakukan dan efektif
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Catatan -->
                                <div class="space-y-2">
                                    <Label>Catatan</Label>
                                    <Textarea v-model="fokus.catatan" placeholder="Tambahkan catatan khusus untuk fokus perilaku ini..." />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Rekomendasi & Lampiran -->
                <Card class="border-t-4 border-t-indigo-500 shadow-md">
                    <CardHeader class="bg-indigo-50/50 dark:bg-indigo-900/10 border-b pb-4">
                        <CardTitle class="text-xl text-indigo-700 dark:text-indigo-400">Rekomendasi & Lampiran</CardTitle>
                    </CardHeader>
                    <CardContent class="p-6 space-y-6">
                        <div class="space-y-2">
                            <Label class="text-base">Rekomendasi Tindak Lanjut</Label>
                            <Textarea v-model="form.rekomendasi" placeholder="Berikan rekomendasi tindak lanjut bagi guru yang bersangkutan..." class="min-h-[100px]" />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-base flex items-center gap-2">
                                <Video class="w-4 h-4 text-indigo-500" />
                                Link Video Observasi (Opsional)
                            </Label>
                            <p class="text-xs text-muted-foreground mb-2">Tautkan URL ke Google Drive, YouTube, atau platform penyimpan video lainnya.</p>
                            <Input v-model="form.video_link" type="url" placeholder="https://drive.google.com/..." />
                        </div>
                    </CardContent>
                </Card>

                <!-- Aksi -->
                <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-900 p-4 rounded-lg border shadow-sm">
                    <Link href="/kinerja">
                        <Button type="button" variant="outline" class="gap-2">
                            <ArrowLeft class="w-4 h-4" /> Kembali
                        </Button>
                    </Link>
                    
                    <div class="flex gap-4">
                        <Button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Tahap 1' }}
                        </Button>
                        <Button type="button" variant="secondary" class="gap-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700" @click="() => alert('Fitur tahap 2 masih dikembangkan!')">
                            Lanjut Tahap 2 <ArrowRight class="w-4 h-4" />
                        </Button>
                    </div>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
"""
with open(vue_form_path, "w") as f:
    f.write(vue_form_code)

print("Form created.")
