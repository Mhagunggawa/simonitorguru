<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Mata Pelajaran',
                href: '/mata-pelajaran',
            },
            {
                title: 'Create Mata Pelajaran',
                href: '/mata-pelajaran/create',
            },
        ],
    },
});

const form = useForm({
    mapels: [
        { nama_mapel: '' }
    ],
});

const addMapel = () => {
    form.mapels.push({ nama_mapel: '' });
};

const removeMapel = (index: number) => {
    if (form.mapels.length > 1) {
        form.mapels.splice(index, 1);
    }
};

const submit = () => {
    form.post('/mata-pelajaran', {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Mata pelajaran berhasil ditambahkan.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            form.reset();
            form.mapels = [{ nama_mapel: '' }];
        }
    });
};
</script>

<template>
    <Head title="Create Mata Pelajaran" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-3xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Create Mata Pelajaran</h1>
            <Button variant="outline" as-child>
                <Link href="/mata-pelajaran">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="space-y-4">
                    <div class="flex justify-between items-end border-b pb-2">
                        <Label class="text-base font-semibold">Daftar Mata Pelajaran</Label>
                        <Button type="button" variant="outline" size="sm" @click="addMapel">
                            <Plus class="w-4 h-4 mr-2" /> Tambah Kolom
                        </Button>
                    </div>

                    <div v-for="(mapel, index) in form.mapels" :key="index" class="flex items-start gap-4">
                        <div class="flex-1 space-y-1">
                            <Input 
                                type="text" 
                                v-model="mapel.nama_mapel" 
                                :placeholder="'Mata Pelajaran ' + (index + 1)"
                                required 
                                :autofocus="index === 0"
                            />
                            <InputError v-if="form.errors[`mapels.${index}.nama_mapel`]" :message="form.errors[`mapels.${index}.nama_mapel`]" />
                        </div>
                        
                        <Button 
                            v-if="form.mapels.length > 1"
                            type="button" 
                            variant="destructive" 
                            size="icon" 
                            @click="removeMapel(index)"
                            class="shrink-0"
                            title="Hapus baris ini"
                        >
                            <Trash2 class="w-4 h-4" />
                        </Button>
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-4 border-t">
                    <Button type="button" variant="outline" @click="() => { form.reset(); form.mapels = [{ nama_mapel: '' }]; }">Reset</Button>
                    <Button type="submit" :disabled="form.processing">Simpan Semua Mata Pelajaran</Button>
                </div>
            </form>
        </div>
    </div>
</template>
