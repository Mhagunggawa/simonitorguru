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
                title: 'Jurusan',
                href: '/jurusan',
            },
            {
                title: 'Create Jurusan',
                href: '/jurusan/create',
            },
        ],
    },
});

const form = useForm({
    nama_jurusan: '',
});

const submit = () => {
    form.post('/jurusan', {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Jurusan baru berhasil ditambahkan.',
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
    <Head title="Create Jurusan" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Create Jurusan</h1>
            <Button variant="outline" as-child>
                <Link href="/jurusan">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="nama_jurusan">Nama Jurusan</Label>
                    <Input id="nama_jurusan" type="text" v-model="form.nama_jurusan" required autofocus />
                    <InputError :message="form.errors.nama_jurusan" />
                </div>

                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="form.reset()">Reset</Button>
                    <Button type="submit" :disabled="form.processing">Create Jurusan</Button>
                </div>
            </form>
        </div>
    </div>
</template>
