<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

defineProps<{
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
                title: 'Create Jadwal Piket',
                href: '/jadwal-piket/create',
            },
        ],
    },
});

const form = useForm({
    tanggal_piket: '',
    titik_id: '',
    guru_piket_id: '',
    jam_pelajaran_ids: [] as number[]
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

    form.post('/jadwal-piket', {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Jadwal piket baru berhasil ditambahkan.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            form.reset();
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
    <Head title="Create Jadwal Piket" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Create Jadwal Piket</h1>
            <Button variant="outline" as-child>
                <Link href="/jadwal-piket">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="tanggal_piket">Tanggal Piket</Label>
                        <Input id="tanggal_piket" type="date" v-model="form.tanggal_piket" required autofocus />
                        <InputError :message="form.errors.tanggal_piket" />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label>Jam Pelajaran</Label>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <label v-for="jp in jamPelajarans" :key="jp.id" class="flex items-center space-x-2 cursor-pointer border p-2 rounded-md hover:bg-muted">
                                <input type="checkbox" :value="jp.id" v-model="form.jam_pelajaran_ids" class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" />
                                <span class="text-sm">Jam ke-{{ jp.urutan_jam }} ({{ formatTime(jp.waktu_mulai) }} - {{ formatTime(jp.waktu_selesai) }})</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.jam_pelajaran_ids" class="mt-2" />
                    </div>

                    <div class="space-y-2">
                        <Label for="titik_id">Titik Lokasi Piket</Label>
                        <Select v-model="form.titik_id">
                            <SelectTrigger class="h-11 text-base">
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
                        <Label for="guru_piket_id">Guru Piket</Label>
                        <Select v-model="form.guru_piket_id">
                            <SelectTrigger class="h-11 text-base">
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

                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="form.reset()">Reset</Button>
                    <Button type="submit" :disabled="form.processing">Create Jadwal Piket</Button>
                </div>
            </form>
        </div>
    </div>
</template>
