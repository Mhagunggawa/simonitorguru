<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
// import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

const props = defineProps<{
    tipeGuru: {
        id: number;
        nama_tipe: string;
        akses_piket_kelas: boolean;
        akses_gms: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tipe Guru',
                href: '/tipe-guru',
            },
            {
                title: 'Edit Tipe',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    nama_tipe: props.tipeGuru.nama_tipe,
    akses_piket_kelas: Boolean(props.tipeGuru.akses_piket_kelas),
    akses_gms: Boolean(props.tipeGuru.akses_gms),
});

// const piketModel = computed({
//     get: () => form.akses_piket_kelas,
//     set: (val) => form.akses_piket_kelas = val
// });

// const gmsModel = computed({
//     get: () => form.akses_gms,
//     set: (val) => form.akses_gms = val
// });

// const handlePiketChange = (val: any) => {
//     form.akses_piket_kelas = !!val;
// };

// const handleGmsChange = (val: any) => {
//     form.akses_gms = !!val;
// };

const submit = () => {

    console.log("Data yang mau dikirim:", form.data());

    form.put(`/tipe-guru/${props.tipeGuru.id}`, {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Tipe guru berhasil diperbarui.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        },
    });
};
</script>

<template>
    <Head title="Edit Tipe Guru" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Edit Tipe Guru</h1>
            <Button variant="outline" as-child>
                <Link href="/tipe-guru">Batal</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="nama_tipe">Nama Tipe Guru</Label>
                    <Input id="nama_tipe" type="text" v-model="form.nama_tipe" required autofocus />
                    <InputError :message="form.errors.nama_tipe" />
                </div>

                <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <h3 class="font-medium">Hak Akses Fitur</h3>
                    
                    <div class="flex items-start space-x-3 cursor-pointer" @click="form.akses_piket_kelas = !form.akses_piket_kelas">
                        <Checkbox :checked="form.akses_piket_kelas" :model-value="form.akses_piket_kelas" class="pointer-events-none mt-0.5" />
                        <div class="grid gap-1.5 leading-none pointer-events-none">
                            <Label class="font-medium">Akses Piket Kelas</Label>
                            <p class="text-sm text-muted-foreground">Berikan hak akses untuk mengisi laporan piket kelas harian.</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3 mt-4 cursor-pointer" @click="form.akses_gms = !form.akses_gms">
                        <Checkbox :checked="form.akses_gms" :model-value="form.akses_gms" class="pointer-events-none mt-0.5" />
                        <div class="grid gap-1.5 leading-none pointer-events-none">
                            <Label class="font-medium">Akses Absensi GMS</Label>
                            <p class="text-sm text-muted-foreground">Berikan hak akses untuk melakukan absensi pagi GMS (pukul 07:15 - 08:00).</p>
                        </div>
                    </div>

                </div>

                <div class="flex justify-end gap-4 pt-4 border-t">
                    <Button type="submit" :disabled="form.processing">Simpan Perubahan</Button>
                </div>
            </form>
        </div>
    </div>
</template>
