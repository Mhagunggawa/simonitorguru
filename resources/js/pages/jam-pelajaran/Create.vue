<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Jam Pelajaran',
                href: '/jam-pelajaran',
            },
            {
                title: 'Create Jam Pelajaran',
                href: '/jam-pelajaran/create',
            },
        ],
    },
});

const form = useForm({
    urutan_jam: '',
    waktu_mulai: '',
    waktu_selesai: '',
});

const submit = () => {
    form.post('/jam-pelajaran', {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Jam Pelajaran baru berhasil ditambahkan.',
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
    <Head title="Create Jam Pelajaran" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Create Jam Pelajaran</h1>
            <Button variant="outline" as-child>
                <Link href="/jam-pelajaran">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="urutan_jam">Urutan Jam Ke-</Label>
                        <Input id="urutan_jam" type="number" min="1" v-model="form.urutan_jam" required autofocus placeholder="Contoh: 1, 2, 3" />
                        <InputError :message="form.errors.urutan_jam" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="waktu_mulai">Waktu Mulai</Label>
                            <Input id="waktu_mulai" type="time" v-model="form.waktu_mulai" required />
                            <InputError :message="form.errors.waktu_mulai" />
                        </div>
                        <div class="space-y-2">
                            <Label for="waktu_selesai">Waktu Selesai</Label>
                            <Input id="waktu_selesai" type="time" v-model="form.waktu_selesai" required />
                            <InputError :message="form.errors.waktu_selesai" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="form.reset()">Reset</Button>
                    <Button type="submit" :disabled="form.processing">Create Jam Pelajaran</Button>
                </div>
            </form>
        </div>
    </div>
</template>
