<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import { Eye, EyeOff } from '@lucide/vue';

const page = usePage<any>();

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
        tipe_guru_id: number | null;
        status_pegawai: string | null;
    };
    tipeGurus: Array<{
        id: number;
        nama_tipe: string;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'User Management',
                href: '/users',
            },
            {
                title: 'Edit User',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role: props.user.role,
    tipe_guru_id: props.user.tipe_guru_id,
    status_pegawai: props.user.status_pegawai,
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.put(`/users/${props.user.id}`, {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Data pengguna berhasil diperbarui.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Edit User" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Edit User</h1>
            <Button variant="outline" as-child>
                <Link href="/users">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="name">Name</Label>
                    <Input id="name" type="text" v-model="form.name" required autofocus />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input id="email" type="email" v-model="form.email" required />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="space-y-2">
                    <Label for="role">Role</Label>
                    <Select v-model="form.role" @update:modelValue="(val) => { 
                        if (!['guru', 'wakasek', 'kepsek'].includes(val as string)) form.tipe_guru_id = null; 
                        if (['admin', 'super admin'].includes(val as string)) form.status_pegawai = null;
                    }" :disabled="user.role === 'super admin'">
                        <SelectTrigger>
                            <SelectValue placeholder="Select a role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="guru">Guru</SelectItem>
                            <SelectItem value="wakasek">Wakasek</SelectItem>
                            <SelectItem v-if="page.props.auth?.user?.role === 'super admin' && user.role !== 'super admin'" value="admin">Admin</SelectItem>
                            <SelectItem value="kepsek">Kepala Sekolah</SelectItem>
                            <SelectItem v-if="user.role === 'super admin'" value="super admin">Super Admin</SelectItem>
                        </SelectContent>
                    </Select>  
                    <InputError :message="form.errors.role" />
                </div>

                <div class="space-y-2" v-if="['guru', 'wakasek', 'kepsek'].includes(form.role)">
                    <Label for="tipe_guru_id">Tipe Guru</Label>
                    <Select v-model="form.tipe_guru_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih Tipe Guru" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="tipe in tipeGurus" :key="tipe.id" :value="tipe.id">
                                {{ tipe.nama_tipe }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.tipe_guru_id" />
                </div>

                <div class="space-y-2" v-if="['guru', 'wakasek', 'kepsek'].includes(form.role)">
                    <Label for="status_pegawai">Status Pegawai</Label>
                    <Select v-model="form.status_pegawai">
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih Status Pegawai" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pns">PNS</SelectItem>
                            <SelectItem value="p3k">P3K</SelectItem>
                            <SelectItem value="p3k paruh waktu">P3K Paruh Waktu</SelectItem>
                            <SelectItem value="gtt">Guru Tidak Tetap (GTT)</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status_pegawai" />
                </div>

                <div class="space-y-2">
                    <Label for="password">Password (Leave blank to keep current)</Label>
                    <div class="relative">
                        <Input id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password" />
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <Eye v-if="!showPassword" class="w-4 h-4" />
                            <EyeOff v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="space-y-2">
                    <Label for="password_confirmation">Confirm Password</Label>
                    <div class="relative">
                        <Input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" v-model="form.password_confirmation" />
                        <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <Eye v-if="!showPasswordConfirmation" class="w-4 h-4" />
                            <EyeOff v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="form.reset()">Reset</Button>
                    <Button type="submit" :disabled="form.processing">Save Changes</Button>
                </div>
            </form>
        </div>
    </div>
</template>
