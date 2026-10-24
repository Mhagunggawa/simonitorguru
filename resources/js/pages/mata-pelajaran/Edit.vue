<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

const props = defineProps<{
    mataPelajaran: {
        id: number;
        nama_mapel: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Mata Pelajaran',
                href: '/mata-pelajaran',
            },
            {
                title: 'Edit Mata Pelajaran',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    nama_mapel: props.mataPelajaran.nama_mapel,
});

const submit = () => {
    form.put(`/mata-pelajaran/${props.mataPelajaran.id}`, {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Data mata pelajaran berhasil diperbarui.',
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
    <Head title="Edit Mata Pelajaran" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Edit Mata Pelajaran</h1>
            <Button variant="outline" as-child>
                <Link href="/mata-pelajaran">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="nama_mapel">Nama Mata Pelajaran</Label>
                    <Input id="nama_mapel" type="text" v-model="form.nama_mapel" required autofocus />
                    <InputError :message="form.errors.nama_mapel" />
                </div>

                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="form.reset()">Reset</Button>
                    <Button type="submit" :disabled="form.processing">Save Changes</Button>
                </div>
            </form>
        </div>
    </div>
</template>
