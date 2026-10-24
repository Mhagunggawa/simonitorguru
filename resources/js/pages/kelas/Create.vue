<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

defineProps<{
    jurusans: Array<{
        id: number;
        nama_jurusan: string;
    }>;
    tingkatKelas: Array<{
        id: number;
        nama_tingkat: string;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Kelas',
                href: '/kelas',
            },
            {
                title: 'Create Kelas',
                href: '/kelas/create',
            },
        ],
    },
});

const form = useForm({
    nama_kelas: '',
    jurusan_id: '',
    tingkat_kelas_id: '',
});

const submit = () => {
    form.post('/kelas', {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Kelas baru berhasil ditambahkan.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            form.reset();
        }
    });
};
</script>

<template>
    <Head title="Create Kelas" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Create Kelas</h1>
            <Button variant="outline" as-child>
                <Link href="/kelas">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="tingkat_kelas_id">Tingkat Kelas</Label>
                        <Select v-model="form.tingkat_kelas_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Tingkat Kelas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="tk in tingkatKelas" :key="tk.id" :value="tk.id.toString()">
                                    {{ tk.nama_tingkat }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.tingkat_kelas_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="nama_kelas">Nama Kelas</Label>
                        <Input id="nama_kelas" type="text" v-model="form.nama_kelas" required autofocus />
                        <InputError :message="form.errors.nama_kelas" />
                    </div>

                    <div class="space-y-2">
                        <Label for="jurusan_id">Jurusan</Label>
                        <Select v-model="form.jurusan_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Jurusan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="jurusan in jurusans" :key="jurusan.id" :value="jurusan.id.toString()">
                                    {{ jurusan.nama_jurusan }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.jurusan_id" />
                    </div>
                </div>

                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="form.reset()">Reset</Button>
                    <Button type="submit" :disabled="form.processing">Create Kelas</Button>
                </div>
            </form>
        </div>
    </div>
</template>
