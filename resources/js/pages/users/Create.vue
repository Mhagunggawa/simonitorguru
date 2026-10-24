<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Trash2, Eye, EyeOff } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

const page = usePage<any>();

defineProps<{
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
                title: 'Create User',
                href: '/users/create',
            },
        ],
    },
});

const defaultUser = () => ({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'guru',
    tipe_guru_id: null as number | null,
    status_pegawai: null as string | null,
    showPassword: false,
    showPasswordConfirmation: false,
});

const form = useForm({
    users: [defaultUser()],
});

const addUser = () => {
    form.users.push(defaultUser());
};

const removeUser = (index: number) => {
    if (form.users.length > 1) {
        form.users.splice(index, 1);
    }
};

const submit = () => {
    form.post('/users', {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Pengguna baru berhasil ditambahkan.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            form.reset();
            form.users = [defaultUser()];
        },
    });
};
</script>

<template>
    <Head title="Create User" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-4xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Create User</h1>
            <Button variant="outline" as-child>
                <Link href="/users">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <form @submit.prevent="submit" class="space-y-8">
                
                <div class="flex justify-between items-end border-b pb-2">
                    <Label class="text-base font-semibold">Daftar Pengguna</Label>
                    <Button type="button" variant="outline" size="sm" @click="addUser">
                        <Plus class="w-4 h-4 mr-2" /> Tambah Kolom Pengguna
                    </Button>
                </div>

                <div class="space-y-8">
                    <div v-for="(user, index) in form.users" :key="index" class="relative p-6 bg-gray-50/50 dark:bg-slate-800/30 rounded-xl border border-gray-100 dark:border-slate-800">
                        <div class="absolute top-4 right-4">
                            <Button 
                                v-if="form.users.length > 1"
                                type="button" 
                                variant="destructive" 
                                size="sm" 
                                @click="removeUser(index)"
                                title="Hapus pengguna ini"
                            >
                                <Trash2 class="w-4 h-4 mr-2" /> Hapus
                            </Button>
                        </div>
                        
                        <h3 class="font-medium text-sm text-gray-500 mb-4 uppercase tracking-wider">Pengguna #{{ index + 1 }}</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label :for="`name-${index}`">Name</Label>
                                <Input :id="`name-${index}`" type="text" v-model="user.name" required :autofocus="index === 0" />
                                <InputError v-if="form.errors[`users.${index}.name`]" :message="form.errors[`users.${index}.name`]" />
                            </div>

                            <div class="space-y-2">
                                <Label :for="`email-${index}`">Email</Label>
                                <Input :id="`email-${index}`" type="email" v-model="user.email" required />
                                <InputError v-if="form.errors[`users.${index}.email`]" :message="form.errors[`users.${index}.email`]" />
                            </div>

                            <div class="space-y-2">
                                <Label :for="`role-${index}`">Role</Label>
                                <Select v-model="user.role" @update:modelValue="(val) => { 
                                    if (!['guru', 'wakasek', 'kepsek'].includes(val as string)) user.tipe_guru_id = null; 
                                    if (['admin', 'super admin'].includes(val as string)) user.status_pegawai = null;
                                }">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="guru">Guru</SelectItem>
                                        <SelectItem value="wakasek">Wakil Kepala Sekolah Kurikulum</SelectItem>
                                        <SelectItem v-if="page.props.auth?.user?.role === 'super admin'" value="admin">Admin</SelectItem>
                                        <SelectItem value="kepsek">Kepala Sekolah</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError v-if="form.errors[`users.${index}.role`]" :message="form.errors[`users.${index}.role`]" />
                            </div>
                            
                            <div class="space-y-2" v-if="['guru', 'wakasek'].includes(user.role)">
                                <Label :for="`tipe_guru-${index}`">Tipe Guru</Label>
                                <Select v-model="user.tipe_guru_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih Tipe Guru" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="tipe in tipeGurus" :key="tipe.id" :value="tipe.id">
                                            {{ tipe.nama_tipe }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError v-if="form.errors[`users.${index}.tipe_guru_id`]" :message="form.errors[`users.${index}.tipe_guru_id`]" />
                            </div>
                            
                            <div class="space-y-2" v-if="['guru', 'wakasek', 'kepsek'].includes(user.role)">
                                <Label :for="`status_pegawai-${index}`">Status Pegawai</Label>
                                <Select v-model="user.status_pegawai">
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
                                <InputError v-if="form.errors[`users.${index}.status_pegawai`]" :message="form.errors[`users.${index}.status_pegawai`]" />
                            </div>
                            <div class="hidden md:block" v-else></div> <!-- Empty grid cell for alignment -->

                            <div class="space-y-2">
                                <Label :for="`password-${index}`">Password</Label>
                                <div class="relative">
                                    <Input :id="`password-${index}`" :type="user.showPassword ? 'text' : 'password'" v-model="user.password" required />
                                    <button type="button" @click="user.showPassword = !user.showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                        <Eye v-if="!user.showPassword" class="w-4 h-4" />
                                        <EyeOff v-else class="w-4 h-4" />
                                    </button>
                                </div>
                                <InputError v-if="form.errors[`users.${index}.password`]" :message="form.errors[`users.${index}.password`]" />
                            </div>

                            <div class="space-y-2">
                                <Label :for="`password_confirmation-${index}`">Confirm Password</Label>
                                <div class="relative">
                                    <Input :id="`password_confirmation-${index}`" :type="user.showPasswordConfirmation ? 'text' : 'password'" v-model="user.password_confirmation" required />
                                    <button type="button" @click="user.showPasswordConfirmation = !user.showPasswordConfirmation" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                        <Eye v-if="!user.showPasswordConfirmation" class="w-4 h-4" />
                                        <EyeOff v-else class="w-4 h-4" />
                                    </button>
                                </div>
                                <InputError v-if="form.errors[`users.${index}.password_confirmation`]" :message="form.errors[`users.${index}.password_confirmation`]" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-4 border-t">
                    <Button type="button" variant="outline" @click="() => { form.reset(); form.users = [defaultUser()]; }">Reset</Button>
                    <Button type="submit" :disabled="form.processing">Simpan Semua Pengguna</Button>
                </div>
            </form>
        </div>
    </div>
</template>
