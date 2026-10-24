<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import Swal from 'sweetalert2';
import { onMounted } from 'vue';

const props = defineProps<{
    guru: {
        id: number;
        name: string;
        jadwal_gms: Array<{
            id: number;
            hari: string;
        }>;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Jadwal GMS',
                href: '/jadwal-gms',
            },
            {
                title: 'Atur Jadwal',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    hari: [] as string[],
});

onMounted(() => {
    form.hari = props.guru.jadwal_gms.map(j => j.hari);
});

const hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

const toggleHari = (hari: string) => {
    const index = form.hari.indexOf(hari);
    if (index > -1) {
        form.hari.splice(index, 1);
    } else {
        form.hari.push(hari);
    }
};

const submit = () => {
    form.post(`/jadwal-gms/${props.guru.id}`, {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Jadwal GMS berhasil diperbarui.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        },
    });
};
</script>

<template>
    <Head title="Atur Jadwal GMS" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Atur Jadwal GMS</h1>
            <Button variant="outline" as-child>
                <Link href="/jadwal-gms">Batal</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <div class="mb-6 pb-4 border-b">
                <h3 class="text-lg font-medium">Guru: <span class="font-bold">{{ guru.name }}</span></h3>
                <p class="text-sm text-muted-foreground mt-1">Pilih hari giliran absensi GMS untuk guru ini.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-4">
                    <div 
                        v-for="hari in hariList" 
                        :key="hari"
                        class="flex items-center space-x-3 cursor-pointer p-2 rounded-lg hover:bg-muted/50 transition-colors"
                        @click="toggleHari(hari)"
                    >
                        <Checkbox 
                            :checked="form.hari.includes(hari)" 
                            :model-value="form.hari.includes(hari)" 
                            class="pointer-events-none" 
                        />
                        <Label class="font-medium pointer-events-none">{{ hari }}</Label>
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-4 border-t">
                    <Button type="submit" :disabled="form.processing">Simpan Jadwal</Button>
                </div>
            </form>
        </div>
    </div>
</template>
