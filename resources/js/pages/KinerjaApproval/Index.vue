<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { UserCircle, ClipboardCheck, AlertCircle, Clock, CheckCircle2, XCircle } from "@lucide/vue";

const props = defineProps<{
    kinerjas: any[];
}>();

const form = useForm({});

const approve = (id: number) => {
    Swal.fire({
        title: 'Konfirmasi Persetujuan',
        text: 'Apakah Anda yakin ingin menyetujui form kinerja ini? Pihak terkait akan dapat melanjutkan ke tahap berikutnya.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#66BB6A',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Setujui',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(`/kinerja-approval/${id}/approve`);
        }
    });
};

const openRejectModal = (kinerja: any) => {
    const isTahap2 = kinerja.status_tahap2 === 'submitted';
    const tahapLabel = isTahap2 ? 'Tahap 2 (Tindak Lanjut & Refleksi)' : 'Tahap 1 (Rencana & Observasi)';
    
    Swal.fire({
        title: 'Tolak & Minta Revisi Form Kinerja',
        html: `
            <div style="text-align: left; font-size: 13px; color: #334155; margin-top: 8px;">
                <p style="margin-bottom: 12px; font-size: 13px; color: #64748b; line-height: 1.5;">
                    Silakan tentukan pihak yang diminta untuk merevisi formulir <strong>${tahapLabel}</strong> dan sertakan catatan perbaikan.
                </p>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1e293b;">
                    Pihak yang Harus Merevisi:
                </label>
                <select id="swal-revisi-target" style="width: 100%; margin: 0 0 16px 0; height: 38px; font-size: 13px; padding: 6px 12px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; background-color: #fff; color: #1e293b;">
                    <option value="guru">Guru yang Diamati (${kinerja.guru?.name || 'Guru'})</option>
                    <option value="observer">Observer / Pengamat (${kinerja.observer?.name || 'Observer'})</option>
                    <option value="keduanya">Keduanya (Guru & Observer Perlu Diskusi)</option>
                </select>

                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1e293b;">
                    Catatan Revisi & Alasan Penolakan:
                </label>
                <textarea id="swal-catatan-revisi" rows="3" placeholder="Tuliskan catatan perbaikan atau alasan penolakan secara spesifik..." style="width: 100%; font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; font-family: inherit; resize: vertical; color: #1e293b;"></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Tolak & Kirim Revisi',
        cancelButtonText: 'Batal',
        focusConfirm: false,
        preConfirm: () => {
            const targetEl = document.getElementById('swal-revisi-target') as HTMLSelectElement;
            const catatanEl = document.getElementById('swal-catatan-revisi') as HTMLTextAreaElement;

            const target = targetEl?.value;
            const catatan = catatanEl?.value;

            if (!target) {
                Swal.showValidationMessage('Silakan pilih pihak yang diminta untuk revisi');
                return false;
            }
            if (!catatan || catatan.trim().length < 3) {
                Swal.showValidationMessage('Catatan revisi minimal 3 karakter');
                return false;
            }

            const isTahap2 = kinerja.status_tahap2 === 'submitted';
            const tahapNumber = isTahap2 ? 2 : 1;
            return { revisi_target: target, catatan_revisi: catatan.trim(), tahap: tahapNumber };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            router.post(`/kinerja-approval/${kinerja.id}/reject`, result.value, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Ditolak',
                        text: 'Form telah ditolak dan notifikasi email permintaan revisi telah dikirimkan.',
                        confirmButtonColor: '#66BB6A'
                    });
                }
            });
        }
    });
};

const showAlert = (message: string) => {
    Swal.fire('Informasi', message, 'info');
};

let refreshInterval: ReturnType<typeof setInterval>;
onMounted(() => {
    refreshInterval = setInterval(() => {
        router.reload({ only: ['kinerjas'], preserveScroll: true, preserveState: true });
    }, 15000); // 15 seconds
});
onUnmounted(() => {
    clearInterval(refreshInterval);
});
</script>

<template>
    <Head title="Persetujuan Kinerja" />
    <div class="p-6 md:p-8 space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-4 dark:border-slate-800">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <ClipboardCheck class="w-6 h-6 text-slate-700 dark:text-slate-300" />
                    Persetujuan Observasi Kinerja
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm md:text-base">
                    Daftar form pengamatan kinerja yang memerlukan persetujuan.
                </p>
            </div>
        </div>
        
        <!-- Grid Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" v-if="kinerjas.length > 0">
            <Card v-for="kinerja in kinerjas" :key="kinerja.id" class="shadow-sm border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950" style="border-top: 4px solid #66BB6A;">
                <div class="p-3 md:p-4 flex flex-col gap-3">
                    
                    <!-- Kiri: Info Guru & Observer + Badge -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-500 shrink-0">
                                <UserCircle class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate" title="Guru yang dinilai">{{ kinerja.guru?.name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                    Pengamat: <span class="font-medium text-slate-700 dark:text-slate-300">{{ kinerja.observer?.name }}</span>
                                </p>
                                <div class="flex items-center gap-1 mt-0.5 text-[11px] text-slate-400">
                                    <Clock class="w-3 h-3" />
                                    {{ kinerja.periode }}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Badge Status -->
                        <Badge v-if="kinerja.status_tahap1 === 'pending'" variant="outline" class="text-[10px] bg-amber-50 text-amber-700 border-amber-200 font-medium px-1.5 py-0 shrink-0 whitespace-nowrap">
                            Menunggu ACC Tahap 1
                        </Badge>
                        <Badge v-else-if="kinerja.status_tahap2 === 'submitted'" variant="outline" class="text-[10px] bg-amber-50 text-amber-700 border-amber-200 font-medium px-1.5 py-0 shrink-0 whitespace-nowrap">
                            Menunggu ACC Tahap 2
                        </Badge>
                    </div>

                    <!-- Bawah: Aksi -->
                    <div class="flex items-center justify-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <Link :href="`/kinerja-approval/${kinerja.id}/show`">
                            <Button variant="outline" size="sm" class="h-7 px-3 text-xs text-slate-700 bg-white hover:bg-slate-50">
                                Lihat Dokumen
                            </Button>
                        </Link>
                        <Button 
                            variant="outline"
                            size="sm"
                            class="h-7 px-3 text-xs text-rose-600 border-rose-200 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-300"
                            :disabled="form.processing"
                            @click="openRejectModal(kinerja)"
                        >
                            <XCircle class="w-3.5 h-3.5 mr-1 text-rose-600" /> Tolak
                        </Button>
                        <Button 
                            v-if="kinerja.status_tahap1 !== 'approved' || kinerja.status_tahap2 === 'submitted'" 
                            size="sm"
                            class="h-7 px-4 text-xs text-white hover:opacity-90 shadow-sm" 
                            style="background-color: #66BB6A; border-color: #66BB6A;"
                            :disabled="form.processing"
                            @click="approve(kinerja.id)"
                        >
                            {{ form.processing ? '...' : 'ACC' }}
                        </Button>
                    </div>

                </div>
            </Card>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-16 px-4 border rounded-xl bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-800">
            <ClipboardCheck class="w-12 h-12 text-slate-300 mb-4" />
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-1">Belum Ada Pengajuan</h3>
            <p class="text-center text-slate-500 dark:text-slate-400 text-sm max-w-sm">
                Belum ada form observasi yang diajukan oleh observer pada periode saat ini.
            </p>
        </div>
    </div>
</template>
