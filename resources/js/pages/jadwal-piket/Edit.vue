<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

const props = defineProps<{
    jadwalPiket: {
        id: number;
        tanggal_piket: string;
        jam_pelajaran_id: number;
        titik_id: number;
        guru_piket_id: number;
    };
    titiks: Array<{ id: number, nama_titik: string }>;
    gurus: Array<{ id: number, name: string }>;
    jamPelajarans: Array<{ id: number, urutan_jam: number, waktu_mulai: string, waktu_selesai: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Jadwal Piket',
                href: '/jadwal-piket',
            },
            {
                title: 'Edit Jadwal Piket',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    tanggal_piket: props.jadwalPiket.tanggal_piket,
    titik_id: props.jadwalPiket.titik_id.toString(),
    guru_piket_id: props.jadwalPiket.guru_piket_id.toString(),
    jam_pelajaran_ids: props.jadwalPiket.jam_pelajarans.map((j: any) => j.id)
});

const formatTime = (time: string) => time ? time.substring(0, 5) : '';

const submit = () => {
    if (form.jam_pelajaran_ids.length === 0) {
        Swal.fire({
            title: 'Perhatian',
            text: 'Silakan pilih minimal satu jam pelajaran.',
            icon: 'warning'
        });
        return;
    }

    form.put(`/jadwal-piket/${props.jadwalPiket.id}`, {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Jadwal piket berhasil diperbarui.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (errors: any) => {
            if (errors.titik_id || errors.guru_piket_id || errors.jam_pelajaran_ids) {
                Swal.fire({
                    title: 'Jadwal Bentrok / Duplikat!',
                    text: errors.titik_id || errors.guru_piket_id || errors.jam_pelajaran_ids,
                    icon: 'error'
                });
            }
        }
    });
};
</script>

<template>
    <Head title="Edit Jadwal Piket" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-3 sm:p-4 md:p-6 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Edit Jadwal Piket</h1>
                <p class="text-xs sm:text-sm text-muted-foreground">Perbarui pembagian tugas piket harian</p>
            </div>
            <Button variant="outline" size="sm" as-child>
                <Link href="/jadwal-piket">Batal</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm dark:border-sidebar-border p-4 sm:p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="tanggal_piket" class="text-sm font-semibold">Tanggal Piket</Label>
                        <Input id="tanggal_piket" type="date" v-model="form.tanggal_piket" class="h-10 text-sm" required autofocus />
                        <InputError :message="form.errors.tanggal_piket" />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <div class="flex items-center justify-between">
                            <Label class="text-sm font-semibold">Jam Pelajaran</Label>
                            <span class="text-xs text-muted-foreground">{{ form.jam_pelajaran_ids.length }} dipilih</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                            <label 
                                v-for="jp in jamPelajarans" 
                                :key="jp.id" 
                                class="flex items-center space-x-2.5 cursor-pointer border p-2.5 rounded-lg hover:bg-muted transition-colors"
                                :class="form.jam_pelajaran_ids.includes(jp.id) ? 'border-primary/60 bg-primary/5' : 'border-border'"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="jp.id" 
                                    v-model="form.jam_pelajaran_ids" 
                                    class="rounded border-input text-primary shadow-xs focus:ring-primary h-4 w-4" 
                                />
                                <span class="text-xs sm:text-sm font-medium">
                                    Jam ke-{{ jp.urutan_jam }} 
                                    <span class="text-muted-foreground text-xs block sm:inline sm:ml-1 font-mono">
                                        ({{ formatTime(jp.waktu_mulai) }} - {{ formatTime(jp.waktu_selesai) }})
                                    </span>
                                </span>
                            </label>
                        </div>
                        <InputError :message="form.errors.jam_pelajaran_ids" class="mt-2" />
                    </div>

                    <div class="space-y-2">
                        <Label for="titik_id" class="text-sm font-semibold">Titik Lokasi Piket</Label>
                        <Select v-model="form.titik_id">
                            <SelectTrigger class="h-10 text-sm">
                                <SelectValue placeholder="Pilih Titik Lokasi" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="t in titiks" :key="t.id" :value="t.id.toString()">
                                    {{ t.nama_titik }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.titik_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="guru_piket_id" class="text-sm font-semibold">Guru Piket</Label>
                        <Select v-model="form.guru_piket_id">
                            <SelectTrigger class="h-10 text-sm">
                                <SelectValue placeholder="Pilih Guru Piket" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="guru in gurus" :key="guru.id" :value="guru.id.toString()">
                                    {{ guru.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.guru_piket_id" />
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2.5 sm:gap-4 pt-2 border-t border-border/60">
                    <Button type="button" variant="outline" @click="form.reset()" class="w-full sm:w-auto h-10 cursor-pointer">Reset</Button>
                    <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto h-10 font-semibold cursor-pointer">
                        Simpan Perubahan
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
