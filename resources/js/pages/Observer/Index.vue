<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Users, Eye, CheckCircle2, ShieldCheck, UserPlus, Search } from '@lucide/vue';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

interface User {
    id: number;
    name: string;
    role: string;
    is_observer: boolean;
    observer_id: number | null;
}

const props = defineProps<{
    teachers: User[];
    observers: User[];
}>();

const searchTeacher = ref('');
const searchObserver = ref('');

const filteredTeachers = computed(() => {
    return props.teachers.filter(t => t.name.toLowerCase().includes(searchTeacher.value.toLowerCase()));
});

const filteredObservers = computed(() => {
    return props.observers.filter(o => o.name.toLowerCase().includes(searchObserver.value.toLowerCase()));
});

const toggleObserver = (user: User) => {
    console.log("Toggle observer dipanggil untuk: ", user.name, " ID: ", user.id, " Status saat ini: ", user.is_observer);
    try {
        router.post(`/observers/${user.id}/toggle`, {
            is_observer: !user.is_observer
        }, {
            preserveScroll: true,
            onError: (errors) => {
                console.error("Inertia error saat toggle: ", errors);
            }
        });
    } catch (e) {
        console.error("Terjadi kesalahan JS saat menekan toggle: ", e);
    }
};

const assignForm = useForm({
    teacher_ids: [] as number[]
});

const selectedObserver = ref<User | null>(null);
const assignDialogOpen = ref(false);
const searchAssign = ref('');

const filteredAssignTeachers = computed(() => {
    if (!selectedObserver.value) return [];
    // Tampilkan guru-guru yang belum jadi observer (atau biarkan semua, opsional)
    // dan bukan dirinya sendiri
    let list = props.teachers.filter(t => !t.is_observer && t.id !== selectedObserver.value?.id);
    if (searchAssign.value) {
        list = list.filter(t => t.name.toLowerCase().includes(searchAssign.value.toLowerCase()));
    }
    return list;
});

const openAssignDialog = (observer: User) => {
    selectedObserver.value = observer;
    // Pre-fill
    assignForm.teacher_ids = props.teachers.filter(t => t.observer_id === observer.id).map(t => t.id);
    assignDialogOpen.value = true;
};

const saveAssignments = () => {
    if (!selectedObserver.value) return;
    assignForm.post(`/observers/${selectedObserver.value.id}/assign`, {
        onSuccess: () => {
            assignDialogOpen.value = false;
        },
        preserveScroll: true
    });
};

</script>

<template>
        <Head title="Pengaturan Observer" />

        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Pengaturan Observer</h1>
                    <p class="text-muted-foreground mt-1 text-sm">Kelola daftar guru yang menjadi observer dan tugaskan guru-guru yang akan diamati.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Kiri: Penunjukan Observer -->
                <Card class="shadow-sm border-sidebar-border/70 overflow-hidden flex flex-col h-[600px]">
                    <CardHeader class="bg-slate-50/50 dark:bg-slate-900/20 border-b border-sidebar-border/50 pb-4">
                        <div class="flex items-center gap-2 mb-1">
                            <ShieldCheck class="w-5 h-5 text-emerald-500" />
                            <CardTitle class="text-lg">Daftar Guru</CardTitle>
                        </div>
                        <CardDescription>Tunjuk guru sebagai Observer (Pengamat)</CardDescription>
                        <div class="mt-4 relative">
                            <Search class="w-4 h-4 absolute left-3 top-3 text-muted-foreground" />
                            <Input v-model="searchTeacher" placeholder="Cari nama guru..." class="pl-9 h-10" />
                        </div>
                    </CardHeader>
                    <CardContent class="p-0 overflow-y-auto flex-1">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-background z-10 shadow-sm border-b">
                                <tr>
                                    <th class="h-12 px-4 text-left font-medium text-muted-foreground">Nama Guru</th>
                                    <th class="h-12 px-4 text-center font-medium text-muted-foreground w-32">Jadikan Observer</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="teacher in filteredTeachers" :key="teacher.id" :class="teacher.is_observer ? 'bg-emerald-50/30 dark:bg-emerald-950/20' : ''">
                                    <td class="p-4 align-middle font-medium">
                                        <div class="flex items-center gap-2">
                                            {{ teacher.name }}
                                            <Badge v-if="teacher.is_observer" variant="outline" class="text-[10px] bg-emerald-100 text-emerald-700 border-emerald-200">Observer</Badge>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle text-center">
                                        <div class="flex justify-center relative">
                                            <div 
                                                @click.stop="toggleObserver(teacher)"
                                                class="w-11 h-6 rounded-full cursor-pointer relative transition-colors duration-200 ease-in-out"
                                                :class="teacher.is_observer ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
                                                role="switch"
                                                :aria-checked="teacher.is_observer"
                                            >
                                                <div 
                                                    class="absolute top-0.5 left-0.5 bg-white w-5 h-5 rounded-full shadow-sm transition-transform duration-200 ease-in-out pointer-events-none"
                                                    :class="teacher.is_observer ? 'translate-x-5' : 'translate-x-0'"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredTeachers.length === 0">
                                    <td colspan="2" class="h-24 text-center text-muted-foreground">Tidak ada data ditemukan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <!-- Kanan: Penugasan Anggota ke Observer -->
                <Card class="shadow-sm border-sidebar-border/70 overflow-hidden flex flex-col h-[600px]">
                    <CardHeader class="bg-slate-50/50 dark:bg-slate-900/20 border-b border-sidebar-border/50 pb-4">
                        <div class="flex items-center gap-2 mb-1">
                            <Eye class="w-5 h-5 text-teal-500" />
                            <CardTitle class="text-lg">Observer Aktif</CardTitle>
                        </div>
                        <CardDescription>Atur pengelompokan guru yang diamati</CardDescription>
                        <div class="mt-4 relative">
                            <Search class="w-4 h-4 absolute left-3 top-3 text-muted-foreground" />
                            <Input v-model="searchObserver" placeholder="Cari observer..." class="pl-9 h-10" />
                        </div>
                    </CardHeader>
                    <CardContent class="p-0 overflow-y-auto flex-1 p-4">
                        <div v-if="filteredObservers.length > 0" class="space-y-4">
                            <div v-for="observer in filteredObservers" :key="observer.id" class="border rounded-xl p-4 bg-background shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500"></div>
                                <div class="flex justify-between items-start pl-2">
                                    <div>
                                        <h3 class="font-bold text-base text-emerald-700 dark:text-emerald-400">{{ observer.name }}</h3>
                                        <p class="text-xs text-muted-foreground mt-1">Mengamati: <span class="font-semibold text-foreground">{{ props.teachers.filter(t => t.observer_id === observer.id).length }}</span> Guru</p>
                                    </div>
                                    <Button size="sm" variant="outline" class="h-8 gap-1.5 border-emerald-200 text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800" @click="openAssignDialog(observer)">
                                        <UserPlus class="w-3.5 h-3.5" />
                                        Kelola
                                    </Button>
                                </div>
                                
                                <div class="mt-3 pl-2 flex flex-wrap gap-1.5">
                                    <template v-for="teacher in props.teachers.filter(t => t.observer_id === observer.id)" :key="teacher.id">
                                        <Badge variant="secondary" class="text-[10px] bg-slate-100 text-slate-700 hover:bg-slate-200">{{ teacher.name }}</Badge>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div v-else class="h-full flex flex-col items-center justify-center text-muted-foreground gap-3 py-12">
                            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                <ShieldCheck class="w-6 h-6 text-slate-400" />
                            </div>
                            <p class="text-sm">Belum ada Observer yang dipilih.</p>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </div>

        <Dialog v-model:open="assignDialogOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Kelola Anggota Observasi</DialogTitle>
                    <DialogDescription>
                        Pilih guru-guru yang akan diamati oleh <strong>{{ selectedObserver?.name }}</strong>.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4">
                    <div class="relative mb-4">
                        <Search class="w-4 h-4 absolute left-3 top-3 text-muted-foreground" />
                        <Input v-model="searchAssign" placeholder="Cari nama guru..." class="pl-9 h-10" />
                    </div>
                    
                    <div class="max-h-[300px] overflow-y-auto border rounded-md divide-y p-1">
                        <label 
                            v-for="teacher in filteredAssignTeachers" 
                            :key="teacher.id"
                            class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-900/50 cursor-pointer rounded-sm"
                        >
                            <input 
                                type="checkbox" 
                                :id="`t-${teacher.id}`"
                                :value="teacher.id"
                                v-model="assignForm.teacher_ids"
                                class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-600 dark:border-gray-600 dark:bg-gray-700"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium leading-none">{{ teacher.name }}</p>
                                <p v-if="teacher.observer_id && teacher.observer_id !== selectedObserver?.id" class="text-[10px] text-amber-600 mt-1">Saat ini diobservasi oleh guru lain</p>
                            </div>
                        </label>
                        <div v-if="filteredAssignTeachers.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                            Tidak ada guru yang dapat dipilih.
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="assignDialogOpen = false" type="button">Batal</Button>
                    <Button type="button" @click="saveAssignments" :disabled="assignForm.processing">Simpan Penugasan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
</template>
