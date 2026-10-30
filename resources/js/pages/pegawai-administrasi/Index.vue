<script setup lang="ts">
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';
import { 
    Users, 
    Plus, 
    Trash2, 
    Edit, 
    Search, 
    Upload, 
    Download, 
    FileSpreadsheet, 
    UserCheck,
    CheckCircle2
} from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref, watch, onMounted } from 'vue';
import debounce from 'lodash/debounce';

const page = usePage<any>();

const props = defineProps<{
    pegawais: {
        data: Array<{
            id: number;
            nama: string;
            created_at: string;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
    };
    totalCount: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Pegawai Administrasi',
                href: '/pegawai-administrasi',
            },
        ],
    },
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get('/pegawai-administrasi', { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

// Form Tambah
const isAddModalOpen = ref(false);
const formAdd = useForm({
    nama: '',
});

const openAddModal = () => {
    formAdd.reset();
    formAdd.clearErrors();
    isAddModalOpen.value = true;
};

const submitAdd = () => {
    if (!formAdd.nama.trim()) {
        formAdd.setError('nama', 'Nama pegawai wajib diisi.');
        return;
    }

    formAdd.post('/pegawai-administrasi', {
        onSuccess: () => {
            isAddModalOpen.value = false;
            formAdd.reset();
        },
    });
};

// Form Edit
const isEditModalOpen = ref(false);
const editingPegawai = ref<{ id: number; nama: string } | null>(null);
const formEdit = useForm({
    nama: '',
});

const openEditModal = (pegawai: { id: number; nama: string }) => {
    editingPegawai.value = pegawai;
    formEdit.nama = pegawai.nama;
    formEdit.clearErrors();
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    if (!editingPegawai.value) return;
    if (!formEdit.nama.trim()) {
        formEdit.setError('nama', 'Nama pegawai wajib diisi.');
        return;
    }

    formEdit.put(`/pegawai-administrasi/${editingPegawai.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingPegawai.value = null;
        },
    });
};

// Form Import Excel
const isImportModalOpen = ref(false);
const importFile = ref<File | null>(null);
const formImport = useForm<{ file: File | null }>({
    file: null,
});

const openImportModal = () => {
    importFile.value = null;
    formImport.reset();
    formImport.clearErrors();
    isImportModalOpen.value = true;
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        importFile.value = target.files[0];
        formImport.file = target.files[0];
    }
};

const submitImport = () => {
    if (!formImport.file) {
        formImport.setError('file', 'Silakan pilih file Excel terlebih dahulu.');
        return;
    }

    formImport.post('/pegawai-administrasi/import', {
        forceFormData: true,
        onSuccess: () => {
            isImportModalOpen.value = false;
            formImport.reset();
            importFile.value = null;
        },
    });
};

// Hapus Pegawai
const deletePegawai = (pegawai: { id: number; nama: string }) => {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        html: `Apakah Anda yakin ingin menghapus data pegawai <b>${pegawai.nama}</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/pegawai-administrasi/${pegawai.id}`, {
                preserveScroll: true,
            });
        }
    });
};

// Format Tanggal
const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

// Flash Messages
const showFlashMessage = (flash: any) => {
    if (flash?.success) {
        Swal.fire({
            title: 'Berhasil!',
            text: flash.success,
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    } else if (flash?.error) {
        Swal.fire({
            title: 'Terjadi Kesalahan',
            text: flash.error,
            icon: 'error',
            confirmButtonText: 'Tutup'
        });
    }
};

onMounted(() => {
    showFlashMessage(page.props.flash);
});

watch(() => page.props.flash, (flash) => {
    showFlashMessage(flash);
}, { deep: true });
</script>

<template>
    <Head title="Pegawai Administrasi" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                        <UserCheck class="w-6 h-6" />
                    </div>
                    Pegawai Administrasi
                </h1>
                <p class="text-sm text-muted-foreground mt-0.5">
                    Kelola data pegawai administrasi untuk absensi upacara bendera dan keperluan sekolah
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <Button 
                    type="button" 
                    variant="outline" 
                    @click="openImportModal"
                    class="border-emerald-300 dark:border-emerald-700 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 shadow-xs gap-1.5"
                >
                    <Upload class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    Import Excel
                </Button>
                <Button 
                    type="button" 
                    @click="openAddModal"
                    class="bg-[#79AE6F] hover:bg-[#79AE6F]/90 text-white shadow-xs gap-1.5"
                >
                    <Plus class="w-4 h-4" />
                    Tambah Pegawai
                </Button>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-card p-4 rounded-xl border border-sidebar-border/70 shadow dark:border-sidebar-border">
            <div class="relative w-full max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search"
                    type="search" 
                    placeholder="Cari nama pegawai administrasi..." 
                    class="w-full bg-background pl-8 text-sm" 
                />
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-muted-foreground">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    Total: {{ totalCount }} Pegawai
                </span>
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-muted/50 border-b border-sidebar-border/70 dark:border-sidebar-border text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-6 py-3.5 font-bold w-16 text-center">No</th>
                        <th class="px-6 py-3.5 font-bold">Nama Pegawai Administrasi</th>
                        <th class="px-6 py-3.5 font-bold w-48 text-center">Tanggal Terdaftar</th>
                        <th class="px-6 py-3.5 font-bold w-36 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr 
                        v-for="(pegawai, index) in pegawais.data" 
                        :key="pegawai.id"
                        class="hover:bg-muted/40 transition-colors"
                    >
                        <td class="px-6 py-4 text-center font-medium text-muted-foreground">
                            {{ (pegawais.from || 1) + index }}
                        </td>
                        <td class="px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">
                            {{ pegawai.nama }}
                        </td>
                        <td class="px-6 py-4 text-center text-xs text-muted-foreground">
                            {{ formatDate(pegawai.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <Button 
                                    type="button" 
                                    variant="ghost" 
                                    size="sm" 
                                    @click="openEditModal(pegawai)"
                                    class="h-8 w-8 p-0 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/50"
                                    title="Edit Nama"
                                >
                                    <Edit class="w-4 h-4" />
                                </Button>
                                <Button 
                                    type="button" 
                                    variant="ghost" 
                                    size="sm" 
                                    @click="deletePegawai(pegawai)"
                                    class="h-8 w-8 p-0 text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/50"
                                    title="Hapus Pegawai"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="pegawais.data.length === 0">
                        <td colspan="4" class="px-6 py-12 text-center text-muted-foreground">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <Users class="w-8 h-8 text-slate-300" />
                                <p class="text-sm font-medium">Belum ada data pegawai administrasi.</p>
                                <p class="text-xs text-slate-400">Silakan tambahkan secara manual atau impor dari file Excel.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="block md:hidden space-y-2.5">
            <div 
                v-for="(pegawai, index) in pegawais.data" 
                :key="pegawai.id"
                class="p-4 rounded-xl border border-sidebar-border/70 bg-card shadow-xs flex items-center justify-between gap-3"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold flex items-center justify-center shrink-0">
                        {{ (pegawais.from || 1) + index }}
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate">{{ pegawai.nama }}</h3>
                        <p class="text-[11px] text-muted-foreground mt-0.5">Terdaftar: {{ formatDate(pegawai.created_at) }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <Button 
                        type="button" 
                        variant="ghost" 
                        size="sm" 
                        @click="openEditModal(pegawai)"
                        class="h-8 w-8 p-0 text-blue-600"
                    >
                        <Edit class="w-4 h-4" />
                    </Button>
                    <Button 
                        type="button" 
                        variant="ghost" 
                        size="sm" 
                        @click="deletePegawai(pegawai)"
                        class="h-8 w-8 p-0 text-rose-600"
                    >
                        <Trash2 class="w-4 h-4" />
                    </Button>
                </div>
            </div>

            <div v-if="pegawais.data.length === 0" class="p-8 text-center text-muted-foreground border rounded-xl bg-card">
                <Users class="w-8 h-8 mx-auto mb-2 text-slate-300" />
                <p class="text-sm font-medium">Belum ada data pegawai administrasi.</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pegawais.links && pegawais.links.length > 3" class="flex flex-wrap items-center justify-between gap-2 pt-2">
            <span class="text-xs text-muted-foreground">
                Menampilkan {{ pegawais.from || 0 }} - {{ pegawais.to || 0 }} dari {{ pegawais.total }} data
            </span>
            <div class="flex flex-wrap items-center gap-1">
                <template v-for="(link, i) in pegawais.links" :key="i">
                    <button
                        v-if="link.url"
                        type="button"
                        @click="router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active 
                            ? 'bg-[#79AE6F] text-white font-bold' 
                            : 'bg-card text-muted-foreground hover:bg-muted border border-sidebar-border/70'"
                        v-html="link.label"
                    />
                    <span 
                        v-else 
                        class="px-2 py-1 text-xs text-muted-foreground/50" 
                        v-html="link.label" 
                    />
                </template>
            </div>
        </div>

        <!-- Modal Tambah Pegawai -->
        <Dialog :open="isAddModalOpen" @update:open="isAddModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Plus class="w-5 h-5 text-[#79AE6F]" />
                        Tambah Pegawai Administrasi
                    </DialogTitle>
                    <DialogDescription>
                        Masukkan nama lengkap pegawai administrasi yang akan didaftarkan ke sistem.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="nama-tambah" class="text-sm font-medium">Nama Lengkap Pegawai</Label>
                        <Input 
                            id="nama-tambah"
                            v-model="formAdd.nama"
                            placeholder="Contoh: Budi Santoso, S.Kom."
                            :class="formAdd.errors.nama ? 'border-red-500 ring-1 ring-red-500' : ''"
                            @keyup.enter="submitAdd"
                        />
                        <p v-if="formAdd.errors.nama" class="text-xs text-red-500 font-medium">{{ formAdd.errors.nama }}</p>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="isAddModalOpen = false" :disabled="formAdd.processing">
                        Batal
                    </Button>
                    <Button 
                        type="button" 
                        @click="submitAdd" 
                        :disabled="formAdd.processing || !formAdd.nama.trim()"
                        class="bg-[#79AE6F] hover:bg-[#79AE6F]/90 text-white"
                    >
                        {{ formAdd.processing ? 'Menyimpan...' : 'Simpan Pegawai' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Edit Pegawai -->
        <Dialog :open="isEditModalOpen" @update:open="isEditModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Edit class="w-5 h-5 text-blue-600" />
                        Edit Pegawai Administrasi
                    </DialogTitle>
                    <DialogDescription>
                        Perbarui nama pegawai administrasi.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="nama-edit" class="text-sm font-medium">Nama Lengkap Pegawai</Label>
                        <Input 
                            id="nama-edit"
                            v-model="formEdit.nama"
                            placeholder="Nama pegawai..."
                            :class="formEdit.errors.nama ? 'border-red-500 ring-1 ring-red-500' : ''"
                            @keyup.enter="submitEdit"
                        />
                        <p v-if="formEdit.errors.nama" class="text-xs text-red-500 font-medium">{{ formEdit.errors.nama }}</p>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="isEditModalOpen = false" :disabled="formEdit.processing">
                        Batal
                    </Button>
                    <Button 
                        type="button" 
                        @click="submitEdit" 
                        :disabled="formEdit.processing || !formEdit.nama.trim()"
                        class="bg-blue-600 hover:bg-blue-700 text-white"
                    >
                        {{ formEdit.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Import Excel -->
        <Dialog :open="isImportModalOpen" @update:open="isImportModalOpen = $event">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <FileSpreadsheet class="w-5 h-5 text-emerald-600" />
                        Import Data Pegawai dari Excel
                    </DialogTitle>
                    <DialogDescription>
                        Unggah file Excel (.xlsx / .xls) atau CSV yang berisi daftar nama pegawai administrasi.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <!-- Template Download Box -->
                    <div class="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 flex items-start justify-between gap-3">
                        <div class="text-xs text-emerald-900 dark:text-emerald-200 leading-relaxed">
                            <p class="font-semibold">Format Pengisian Excel:</p>
                            <p class="mt-0.5 opacity-90">File cukup memuat 1 kolom berisi nama pegawai (misal header "Nama Pegawai" atau langsung baris nama).</p>
                        </div>
                        <a 
                            href="/pegawai-administrasi/template" 
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold whitespace-nowrap transition-colors shrink-0 shadow-xs"
                        >
                            <Download class="w-3.5 h-3.5" />
                            Unduh Template
                        </a>
                    </div>

                    <!-- File Input Box -->
                    <div class="space-y-2">
                        <Label for="file-upload" class="text-sm font-medium">Pilih File Excel / CSV</Label>
                        <div class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl p-5 text-center hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                            <Upload class="w-8 h-8 text-slate-400 mx-auto mb-2" />
                            <input 
                                id="file-upload"
                                type="file" 
                                accept=".xlsx,.xls,.csv"
                                @change="handleFileChange"
                                class="hidden" 
                            />
                            <label for="file-upload" class="cursor-pointer">
                                <span class="font-semibold text-emerald-600 hover:text-emerald-700 text-sm">
                                    {{ importFile ? importFile.name : 'Pilih file dari komputer' }}
                                </span>
                                <p class="text-xs text-muted-foreground mt-1">Format didukung: .xlsx, .xls, .csv (Maks. 5MB)</p>
                            </label>
                        </div>
                        <p v-if="formImport.errors.file" class="text-xs text-red-500 font-medium">{{ formImport.errors.file }}</p>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="isImportModalOpen = false" :disabled="formImport.processing">
                        Batal
                    </Button>
                    <Button 
                        type="button" 
                        @click="submitImport" 
                        :disabled="formImport.processing || !importFile"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white"
                    >
                        {{ formImport.processing ? 'Mengimpor...' : 'Mulai Import' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

