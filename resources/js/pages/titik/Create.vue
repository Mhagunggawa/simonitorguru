<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

defineProps<{
    jurusans: Array<{
        id: number;
        nama_jurusan: string;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Titik Lokasi',
                href: '/titik',
            },
            {
                title: 'Create Titik',
                href: '/titik/create',
            },
        ],
    },
});

const form = useForm({
    nama_titik: '',
    jurusan_ids: [] as number[],
});



const submit = () => {
    form.post('/titik', {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Titik lokasi baru berhasil ditambahkan.',
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
    <Head title="Create Titik" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Create Titik Lokasi</h1>
            <Button variant="outline" as-child>
                <Link href="/titik">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="nama_titik">Nama Titik</Label>
                        <Input id="nama_titik" type="text" v-model="form.nama_titik" required autofocus />
                        <InputError :message="form.errors.nama_titik" />
                    </div>

                    <div class="space-y-3">
                        <Label>Pilih Jurusan Terkait</Label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                            <div v-for="jurusan in jurusans" :key="jurusan.id" class="flex items-center space-x-2 border p-3 rounded-md">
                                <input 
                                    type="checkbox"
                                    :id="`jurusan-${jurusan.id}`" 
                                    :value="jurusan.id"
                                    v-model="form.jurusan_ids"
                                    class="w-4 h-4 text-primary bg-background border-input rounded focus:ring-ring focus:ring-2 border shadow-sm"
                                />
                                <label 
                                    :for="`jurusan-${jurusan.id}`" 
                                    class="text-sm font-medium leading-none cursor-pointer"
                                >
                                    {{ jurusan.nama_jurusan }}
                                </label>
                            </div>
                        </div>
                        <InputError :message="form.errors.jurusan_ids" />
                    </div>
                </div>

                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="form.reset()">Reset</Button>
                    <Button type="submit" :disabled="form.processing">Create Titik</Button>
                </div>
            </form>
        </div>
    </div>
</template>
