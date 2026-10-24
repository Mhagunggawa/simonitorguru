<script setup lang="ts">
import { watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { UserCircle, ClipboardEdit, AlertCircle, FileCheck2, ArrowRight, ShieldCheck, Video, Clock, XCircle, Folder, ExternalLink, Lock } from "@lucide/vue";
import Swal from 'sweetalert2';

const props = defineProps<{
    teachers: any[];
    myKinerja?: any;
    myObserver?: any;
    myDrive?: {
        url: string;
    } | null;
    isObserver?: boolean;
    isGuru?: boolean;
}>();

const page = usePage();
watch(() => (page.props as any).flash, (flash: any) => {
    if (flash?.error) {
        Swal.fire({
            icon: 'error',
            title: 'Akses Ditolak',
            text: flash.error,
            confirmButtonColor: '#e11d48'
        });
    } else if (flash?.success) {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: flash.success,
            confirmButtonColor: '#10b981'
        });
    }
}, { immediate: true, deep: true });

const getStatusBadge = (k: any) => {
    if (!k || !k.status_tahap1 || k.status_tahap1 === 'draft_guru') {
        return { label: 'Tahap 1: Belum Mengisi Rencana', variant: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:border-rose-800 dark:text-rose-400' };
    }
    if (k.status_tahap1 === 'revisi_guru') {
        return { label: 'Tahap 1: Perlu Revisi Guru', variant: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/40 dark:border-rose-800 dark:text-rose-300' };
    }
    if (k.status_tahap1 === 'revisi_observer') {
        return { label: 'Tahap 1: Perlu Revisi Observer', variant: 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:border-amber-800 dark:text-amber-300' };
    }
    if (k.status_tahap1 === 'revisi_keduanya') {
        return { label: 'Tahap 1: Perlu Revisi Kedua Pihak', variant: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/40 dark:border-rose-800 dark:text-rose-300' };
    }
    if (k.status_tahap1 === 'menunggu_penilaian') {
        return { label: 'Tahap 1: Menunggu Penilaian Observer', variant: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-400' };
    }
    if (k.status_tahap1 === 'menunggu_video') {
        return { label: 'Tahap 1: Menunggu Upload Video', variant: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400' };
    }
    if (k.status_tahap1 === 'pending') {
        return { label: 'Tahap 1: Menunggu ACC Wakasek/Admin', variant: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:border-purple-800 dark:text-purple-400' };
    }
    if (k.status_tahap1 === 'approved') {
        if (k.status_tahap2 === 'revisi_guru') {
            return { label: 'Tahap 2: Perlu Revisi Guru', variant: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/40 dark:border-rose-800 dark:text-rose-300' };
        }
        if (k.status_tahap2 === 'revisi_observer') {
            return { label: 'Tahap 2: Perlu Revisi Observer', variant: 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:border-amber-800 dark:text-amber-300' };
        }
        if (k.status_tahap2 === 'revisi_keduanya') {
            return { label: 'Tahap 2: Perlu Revisi Kedua Pihak', variant: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/40 dark:border-rose-800 dark:text-rose-300' };
        }
        if (k.status_tahap2 === 'approved') {
            return { label: 'Tahap 2 Selesai & Disetujui', variant: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-400' };
        }
        if (k.status_tahap2 === 'submitted') {
            return { label: 'Tahap 2: Menunggu ACC Wakasek', variant: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:border-purple-800 dark:text-purple-400' };
        }
        if (k.status_tahap2 === 'menunggu_penilaian') {
            return { label: 'Tahap 2: Menunggu Penilaian Observer', variant: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-400' };
        }
        if (k.status_tahap2 === 'approved') {
            if (k.status_tahap3 === 'revisi_guru') {
                return { label: 'Tahap 3: Perlu Revisi Guru', variant: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/40 dark:border-rose-800 dark:text-rose-300' };
            }
            if (k.status_tahap3 === 'revisi_observer') {
                return { label: 'Tahap 3: Perlu Revisi Observer', variant: 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:border-amber-800 dark:text-amber-300' };
            }
            if (k.status_tahap3 === 'revisi_keduanya') {
                return { label: 'Tahap 3: Perlu Revisi Kedua Pihak', variant: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/40 dark:border-rose-800 dark:text-rose-300' };
            }
            if (k.status_tahap3 === 'approved') {
                return { label: 'Tahap 3 Selesai (Observasi Lengkap)', variant: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-400' };
            }
            if (k.status_tahap3 === 'submitted') {
                return { label: 'Tahap 3: Menunggu ACC Wakasek', variant: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:border-purple-800 dark:text-purple-400' };
            }
            if (k.status_tahap3 === 'menunggu_penilaian') {
                return { label: 'Tahap 3: Menunggu Penilaian Observer', variant: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-400' };
            }
            if (k.status_tahap3 === 'draft_guru') {
                return { label: 'Tahap 3: Draf Refleksi Guru', variant: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400' };
            }
            return { label: 'Tahap 2 Selesai - Isi Tahap 3', variant: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-400' };
        }
        return { label: 'Tahap 1 Selesai - Isi Refleksi Tahap 2', variant: 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:border-indigo-800 dark:text-indigo-400' };
    }
    return { label: 'Belum Dimulai', variant: 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900/30 dark:border-slate-800 dark:text-slate-400' };
};

const handleAksesTahap2 = (k: any) => {
    if (!k || k.status_tahap1 !== 'approved' || ['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(k.status_tahap1) || k.revisi_tahap === 1) {
        if (['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(k?.status_tahap1) || k?.revisi_tahap === 1) {
            const targetText = k?.revisi_target === 'guru' 
                ? 'Guru yang Diamati' 
                : (k?.revisi_target === 'observer' ? 'Observer / Pengamat' : 'Kedua Belah Pihak (Guru & Observer)');
            
            Swal.fire({
                icon: 'warning',
                title: 'Tahap 1 Memerlukan Revisi',
                html: `
                    <div style="text-align: left; font-size: 13px; color: #334155;">
                        <p style="margin-bottom: 8px;">
                            Formulir Observasi Kinerja <strong>Tahap 1</strong> saat ini berstatus 
                            <strong style="color: #e11d48;">Revisi (${targetText})</strong> dan belum selesai diperbaiki.
                        </p>
                        <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px;">
                            <span style="font-weight: 600; color: #9f1239; display: block; font-size: 12px;">Catatan Peninjau:</span>
                            <span style="color: #be123c; font-style: italic;">"${k?.catatan_revisi || 'Harap periksa isian formulir'}"</span>
                        </div>
                        <p style="color: #64748b; font-size: 12px; margin: 0; line-height: 1.5;">
                            Selama Formulir Tahap 1 belum diselesaikan revisinya dan belum disetujui (ACC) kembali oleh Wakasek, Anda <strong>tetap tidak akan bisa lanjut atau mengakses Formulir Tahap 2</strong>.
                        </p>
                    </div>
                `,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Selesaikan Revisi Tahap 1'
            });
            return;
        }

        if (k?.status_tahap1 === 'pending') {
            Swal.fire({
                icon: 'info',
                title: 'Menunggu Persetujuan Wakasek',
                html: `
                    <div style="text-align: left; font-size: 13px; color: #334155;">
                        <p style="margin-bottom: 8px;">
                            Formulir Tahap 1 sedang dalam antrean verifikasi dan <strong>belum disetujui (ACC) kembali oleh Wakasek / Admin</strong>.
                        </p>
                        <p style="color: #64748b; font-size: 12px; margin: 0; line-height: 1.5;">
                            Selama belum diverifikasi dan disetujui kembali oleh Wakasek, Anda <strong>tetap tidak dapat melanjutkan atau mengakses Formulir Tahap 2</strong>.
                        </p>
                    </div>
                `,
                confirmButtonColor: '#6366f1',
                confirmButtonText: 'Mengerti'
            });
            return;
        }

        let statusMsg = 'Formulir Observasi Kinerja Tahap 1 belum lengkap atau belum selesai.';
        if (!k || k.status_tahap1 === 'draft_guru') {
            statusMsg = 'Anda belum mengisi Rencana Hasil Kerja & Video Observasi Tahap 1.';
        } else if (k.status_tahap1 === 'menunggu_penilaian') {
            statusMsg = 'Sedang menunggu Observer memberikan penilaian observasi kelas.';
        }

        Swal.fire({
            icon: 'warning',
            title: 'Tahap 1 Belum Disetujui',
            html: `
                <div style="text-align: left; font-size: 13px; color: #334155;">
                    <p style="margin-bottom: 8px;">${statusMsg}</p>
                    <p style="color: #64748b; font-size: 12px; margin: 0; line-height: 1.5;">
                        Anda baru dapat melanjutkan ke Tahap 2 (Tindak Lanjut) setelah Formulir Tahap 1 resmi disetujui (ACC) oleh Wakasek / Admin.
                    </p>
                </div>
            `,
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Mengerti'
        });
        return;
    }
};

const handleAksesTahap3 = (k: any) => {
    if (!k || k.status_tahap2 !== 'approved' || ['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(k.status_tahap2) || k.revisi_tahap === 2) {
        if (['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(k?.status_tahap2) || k?.revisi_tahap === 2) {
            Swal.fire({
                icon: 'warning',
                title: 'Tahap 2 Memerlukan Revisi',
                html: `
                    <div style="text-align: left; font-size: 13px; color: #334155;">
                        <p style="margin-bottom: 8px;">
                            Formulir Tindak Lanjut <strong>Tahap 2</strong> saat ini masih dalam perbaikan/revisi.
                        </p>
                        <p style="color: #64748b; font-size: 12px; margin: 0; line-height: 1.5;">
                            Anda baru dapat melanjutkan ke Tahap 3 (Refleksi Tindak Lanjut) setelah Formulir Tahap 2 diselesaikan dan disetujui (ACC) oleh Wakasek / Admin.
                        </p>
                    </div>
                `,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Selesaikan Tahap 2 Dulu'
            });
            return;
        }

        if (k?.status_tahap2 === 'submitted') {
            Swal.fire({
                icon: 'info',
                title: 'Tahap 2 Menunggu Verifikasi',
                html: `
                    <div style="text-align: left; font-size: 13px; color: #334155;">
                        <p style="margin-bottom: 8px;">
                            Formulir Tindak Lanjut Tahap 2 sedang dalam antrean verifikasi dan <strong>belum disetujui (ACC) oleh Wakasek</strong>.
                        </p>
                        <p style="color: #64748b; font-size: 12px; margin: 0; line-height: 1.5;">
                            Anda baru dapat mengakses Formulir Tahap 3 setelah Formulir Tahap 2 disetujui.
                        </p>
                    </div>
                `,
                confirmButtonColor: '#6366f1',
                confirmButtonText: 'Mengerti'
            });
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Tahap 2 Belum Selesai',
            html: `
                <div style="text-align: left; font-size: 13px; color: #334155;">
                    <p style="margin-bottom: 8px;">
                        Formulir Observasi Kinerja Tahap 2 belum selesai atau belum disetujui.
                    </p>
                    <p style="color: #64748b; font-size: 12px; margin: 0; line-height: 1.5;">
                        Anda baru dapat mengakses Formulir Tahap 3 setelah Formulir Tahap 1 dan Tahap 2 resmi disetujui (ACC) oleh Wakasek / Admin.
                    </p>
                </div>
            `,
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Mengerti'
        });
        return;
    }
};
</script>

<template>
    <Head title="Observasi Kinerja" />
    <div class="p-3.5 md:p-8 max-w-7xl mx-auto space-y-5 md:space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4 border-b pb-4 md:pb-6 dark:border-slate-800">
            <div>
                <h1 class="text-xl md:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5 md:gap-3">
                    <div class="p-2 md:p-2.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl">
                        <ClipboardEdit class="w-5 h-5 md:w-7 md:h-7 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <span>Observasi Kinerja Guru</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1.5 max-w-2xl text-xs md:text-base leading-relaxed">
                    Pelaksanaan observasi kinerja guru secara terstruktur: Pengisian Rencana Kerja & Bukti Video (Guru), Penilaian Observasi (Observer), dan Verifikasi.
                </p>
            </div>
        </div>

        <!-- Section 1: Observasi Kinerja Saya (Hanya Untuk Guru) -->
        <div v-if="isGuru" class="space-y-3 md:space-y-4">
            <div class="flex items-center gap-2">
                <UserCircle class="w-5 h-5 text-emerald-600" />
                <h2 class="text-lg md:text-xl font-bold text-slate-900 dark:text-white">Observasi Kinerja Saya</h2>
            </div>

            <Card class="border-t-4 border-t-emerald-500 shadow-sm md:shadow-md bg-white dark:bg-slate-950">
                <CardHeader class="p-4 md:p-6 pb-2 md:pb-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 md:gap-3">
                        <div>
                            <CardTitle class="text-base md:text-lg">{{ $page.props.auth.user.name }}</CardTitle>
                            <CardDescription class="mt-0.5 md:mt-1 flex items-center gap-1.5 text-xs">
                                <span>Pengamat (Observer):</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">
                                    {{ myObserver?.name || myKinerja?.observer?.name || 'Belum Ditentukan' }}
                                </span>
                            </CardDescription>
                        </div>
                        <div>
                            <Badge variant="outline" :class="getStatusBadge(myKinerja).variant" class="px-2.5 py-0.5 text-xs font-semibold">
                                {{ getStatusBadge(myKinerja).label }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-4 md:p-6 pt-0 md:pt-0 space-y-3 md:space-y-4">
                    <div class="p-3.5 md:p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border text-xs md:text-sm text-slate-600 dark:text-slate-300 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="space-y-1 max-w-2xl">
                            <!-- REVISI TAHAP 1 -->
                            <p v-if="myKinerja?.status_tahap1 === 'revisi_guru'" class="text-rose-600 dark:text-rose-400 font-medium flex items-center gap-2">
                                <AlertCircle class="w-4 h-4 shrink-0 text-rose-600" />
                                <span>Formulir Tahap 1 memerlukan revisi dari Anda (Guru). Catatan: "<em>{{ myKinerja.catatan_revisi }}</em>". Harap segera perbaiki.</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap1 === 'revisi_observer'" class="text-amber-600 dark:text-amber-400 font-medium flex items-center gap-2">
                                <Clock class="w-4 h-4 shrink-0 text-amber-600" />
                                <span>Formulir Tahap 1 sedang dalam perbaikan oleh Observer Anda. Menunggu observer mengajukan revisi.</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap1 === 'revisi_keduanya'" class="text-rose-600 dark:text-rose-400 font-medium flex items-center gap-2">
                                <AlertCircle class="w-4 h-4 shrink-0 text-rose-600" />
                                <span>Formulir Tahap 1 memerlukan diskusi dan perbaikan ulang kedua pihak (Guru & Observer). Catatan: "<em>{{ myKinerja.catatan_revisi }}</em>".</span>
                            </p>
                            <!-- REVISI TAHAP 2 -->
                            <p v-else-if="myKinerja?.status_tahap1 === 'approved' && myKinerja?.status_tahap2 === 'revisi_guru'" class="text-rose-600 dark:text-rose-400 font-medium flex items-center gap-2">
                                <AlertCircle class="w-4 h-4 shrink-0 text-rose-600" />
                                <span>Formulir Tindak Lanjut (Tahap 2) memerlukan perbaikan dari Anda. Catatan: "<em>{{ myKinerja.catatan_revisi }}</em>".</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap1 === 'approved' && myKinerja?.status_tahap2 === 'revisi_observer'" class="text-amber-600 dark:text-amber-400 font-medium flex items-center gap-2">
                                <Clock class="w-4 h-4 shrink-0 text-amber-600" />
                                <span>Formulir Tindak Lanjut (Tahap 2) sedang dalam perbaikan oleh Observer Anda.</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap1 === 'approved' && myKinerja?.status_tahap2 === 'revisi_keduanya'" class="text-rose-600 dark:text-rose-400 font-medium flex items-center gap-2">
                                <AlertCircle class="w-4 h-4 shrink-0 text-rose-600" />
                                <span>Formulir Tindak Lanjut (Tahap 2) memerlukan perbaikan ulang kedua belah pihak. Catatan: "<em>{{ myKinerja.catatan_revisi }}</em>".</span>
                            </p>
                            <!-- REVISI TAHAP 3 -->
                            <p v-else-if="myKinerja?.status_tahap2 === 'approved' && myKinerja?.status_tahap3 === 'revisi_guru'" class="text-rose-600 dark:text-rose-400 font-medium flex items-center gap-2">
                                <AlertCircle class="w-4 h-4 shrink-0 text-rose-600" />
                                <span>Formulir Refleksi Tindak Lanjut (Tahap 3) memerlukan perbaikan dari Anda (Guru). Catatan: "<em>{{ myKinerja.catatan_revisi }}</em>". Harap segera perbaiki.</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap2 === 'approved' && myKinerja?.status_tahap3 === 'revisi_observer'" class="text-amber-600 dark:text-amber-400 font-medium flex items-center gap-2">
                                <Clock class="w-4 h-4 shrink-0 text-amber-600" />
                                <span>Formulir Tahap 3 sedang dalam perbaikan oleh Observer Anda.</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap2 === 'approved' && myKinerja?.status_tahap3 === 'revisi_keduanya'" class="text-rose-600 dark:text-rose-400 font-medium flex items-center gap-2">
                                <AlertCircle class="w-4 h-4 shrink-0 text-rose-600" />
                                <span>Formulir Tahap 3 memerlukan perbaikan ulang kedua belah pihak. Catatan: "<em>{{ myKinerja.catatan_revisi }}</em>".</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap3 === 'approved'" class="text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-2">
                                <FileCheck2 class="w-4 h-4 shrink-0" />
                                <span>Seluruh rangkaian observasi kinerja (Tahap 1, 2, dan 3) telah selesai dan disetujui penuh!</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap3 === 'submitted'" class="text-purple-600 dark:text-purple-400 font-medium flex items-center gap-2">
                                <FileCheck2 class="w-4 h-4 shrink-0" />
                                <span>Formulir Tahap 3 telah dinilai oleh observer dan sedang menunggu Verifikasi (ACC) oleh Wakasek / Admin.</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap3 === 'menunggu_penilaian'" class="text-amber-600 dark:text-amber-400 font-medium flex items-center gap-2">
                                <Clock class="w-4 h-4 shrink-0" />
                                <span>Formulir Refleksi Tahap 3 telah dikirim. Menunggu penilaian observer.</span>
                            </p>
                            <p v-else-if="myKinerja?.status_tahap2 === 'approved'" class="text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-2">
                                <FileCheck2 class="w-4 h-4 shrink-0" />
                                <span>Formulir Tahap 2 telah disetujui (ACC). Silakan lanjutkan pengisian Refleksi Tindak Lanjut (Tahap 3).</span>
                            </p>
                            <!-- REGULER FLOW -->
                            <p v-else-if="!myKinerja || !myKinerja.status_tahap1 || myKinerja.status_tahap1 === 'draft_guru'" class="text-rose-600 dark:text-rose-400 font-medium flex items-center gap-2">
                                <AlertCircle class="w-4 h-4 shrink-0" />
                                Anda belum mengisi Rencana Hasil Kerja & Video Observasi. Silakan isi fokus perilaku dan sertakan tautan video observasi terlebih dahulu.
                            </p>
                            <p v-else-if="myKinerja.status_tahap1 === 'menunggu_penilaian'" class="text-amber-600 dark:text-amber-400 font-medium flex items-center gap-2">
                                <Clock class="w-4 h-4 shrink-0" />
                                Rencana kerja & video observasi Anda telah terkirim. Menunggu observer melakukan observasi dan penilaian.
                            </p>
                            <p v-else-if="myKinerja.status_tahap1 === 'pending'" class="text-purple-600 dark:text-purple-400 font-medium flex items-center gap-2">
                                <FileCheck2 class="w-4 h-4 shrink-0" />
                                Form observasi telah dinilai oleh observer dan sedang menunggu Verifikasi (ACC) oleh Wakasek / Admin.
                            </p>
                            <p v-else-if="myKinerja.status_tahap1 === 'approved'" class="text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-2">
                                <FileCheck2 class="w-4 h-4 shrink-0" />
                                Observasi Kinerja Tahap 1 Anda telah disetujui (ACC). Anda dapat melanjutkan ke Tahap 2 (Tindak Lanjut).
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2 w-full md:w-auto shrink-0">
                            <!-- Pintasan Cepat Drive Kinerja Pribadi -->
                            <a v-if="myDrive?.url" :href="myDrive.url" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto">
                                <Button variant="outline" class="w-full sm:w-auto gap-1.5 text-xs md:text-sm border-indigo-200 text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100 hover:text-indigo-800 dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300 shadow-xs">
                                    <Folder class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                    <span>Drive Kinerja</span>
                                    <ExternalLink class="w-3.5 h-3.5" />
                                </Button>
                            </a>

                            <Link :href="`/kinerja/${$page.props.auth.user.id}/tahap-1`" class="w-full sm:w-auto">
                                <Button 
                                    class="w-full sm:w-auto gap-2 text-xs md:text-sm shadow-sm"
                                    :class="myKinerja?.revisi_tahap === 2 ? 'bg-slate-700 hover:bg-slate-800 text-white' : ((['revisi_guru', 'revisi_keduanya'].includes(myKinerja?.status_tahap1) || myKinerja?.revisi_tahap === 1) ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white')"
                                >
                                    <ClipboardEdit class="w-4 h-4" />
                                    {{ 
                                        !myKinerja ? 'Mulai Isi Rencana Kerja' : 
                                        (myKinerja.revisi_tahap === 2 ? 'Lihat Tahap 1' :
                                        ((['revisi_guru', 'revisi_keduanya'].includes(myKinerja.status_tahap1) || myKinerja.revisi_tahap === 1) ? 'Perbaiki Form Tahap 1' :
                                        (myKinerja.status_tahap1 === 'revisi_observer' ? 'Lihat Form Tahap 1' :
                                        'Buka Form Tahap 1')))
                                    }}
                                </Button>
                            </Link>

                            <!-- Tombol Tahap 2 HANYA AKTIF jika Tahap 1 SUDAH DISETUJUI dan BUKAN DALAM REVISI TAHAP 1 -->
                            <template v-if="myKinerja?.status_tahap1 === 'approved' && !['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(myKinerja?.status_tahap1) && myKinerja?.revisi_tahap !== 1">
                                <Link :href="`/kinerja/${$page.props.auth.user.id}/tahap-2`" class="w-full sm:w-auto">
                                    <Button 
                                        variant="secondary" 
                                        class="w-full sm:w-auto gap-2 text-xs md:text-sm shadow-xs"
                                        :class="(['revisi_guru', 'revisi_keduanya'].includes(myKinerja?.status_tahap2) || myKinerja?.revisi_tahap === 2) ? 'bg-rose-600 text-white hover:bg-rose-700 shadow-sm' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'"
                                    >
                                        {{ (['revisi_guru', 'revisi_keduanya'].includes(myKinerja.status_tahap2) || myKinerja.revisi_tahap === 2) ? 'Perbaiki Form Tahap 2' : 'Form Tahap 2' }}
                                        <ArrowRight class="w-4 h-4" />
                                    </Button>
                                </Link>
                            </template>
                            <template v-else>
                                <Button 
                                    type="button" 
                                    variant="secondary" 
                                    class="w-full sm:w-auto gap-2 text-xs md:text-sm bg-slate-100 text-slate-500 hover:bg-slate-200 opacity-90 cursor-pointer"
                                    @click="handleAksesTahap2(myKinerja)"
                                >
                                    Tahap 2 (Terkunci)
                                    <Clock class="w-4 h-4" />
                                </Button>
                            </template>

                            <!-- Tombol Tahap 3 HANYA AKTIF jika Tahap 2 SUDAH DISETUJUI dan BUKAN DALAM REVISI TAHAP 2 -->
                            <template v-if="myKinerja?.status_tahap2 === 'approved' && !['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(myKinerja?.status_tahap2) && myKinerja?.revisi_tahap !== 2">
                                <Link :href="`/kinerja/${$page.props.auth.user.id}/tahap-3`" class="w-full sm:w-auto">
                                    <Button 
                                        variant="secondary" 
                                        class="w-full sm:w-auto gap-2 text-xs md:text-sm shadow-xs"
                                        :class="(['revisi_guru', 'revisi_keduanya'].includes(myKinerja?.status_tahap3) || myKinerja?.revisi_tahap === 3) ? 'bg-rose-600 text-white hover:bg-rose-700 shadow-sm' : (myKinerja?.status_tahap3 === 'approved' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200')"
                                    >
                                        {{ (['revisi_guru', 'revisi_keduanya'].includes(myKinerja?.status_tahap3) || myKinerja?.revisi_tahap === 3) ? 'Perbaiki Form Tahap 3' : (myKinerja?.status_tahap3 === 'approved' ? 'Lihat Tahap 3' : 'Form Tahap 3') }}
                                        <ArrowRight class="w-4 h-4" />
                                    </Button>
                                </Link>
                            </template>
                            <template v-else>
                                <Button 
                                    type="button" 
                                    variant="secondary" 
                                    class="w-full sm:w-auto gap-2 text-xs md:text-sm bg-slate-100 text-slate-500 hover:bg-slate-200 opacity-90 cursor-pointer"
                                    @click="handleAksesTahap3(myKinerja)"
                                >
                                    Tahap 3 (Terkunci)
                                    <Clock class="w-4 h-4" />
                                </Button>
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
        
        <!-- Section 2: Daftar Guru yang Diobservasi (Untuk Observer) -->
        <div v-if="isObserver" class="space-y-4 pt-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <ShieldCheck class="w-5 h-5 text-blue-600" />
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Daftar Guru yang Anda Amati (Observer)</h2>
                </div>
                <Badge class="bg-blue-100 text-blue-700 hover:bg-blue-100">{{ teachers.length }} Guru Ditugaskan</Badge>
            </div>

            <!-- Grid Cards Observer -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" v-if="teachers.length > 0">
                <Card v-for="teacher in teachers" :key="teacher.id" class="group relative overflow-hidden transition-all duration-300 hover:shadow-xl hover:shadow-emerald-500/10 hover:-translate-y-1 border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-950/50 backdrop-blur-sm">
                    <div class="absolute top-0 left-0 w-full h-1.5 transition-colors duration-300" :class="teacher.kinerjas?.length ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : 'bg-slate-200 dark:bg-slate-700'"></div>
                    
                    <CardHeader class="pb-3 pt-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center border shadow-sm">
                                    <UserCircle class="w-6 h-6 text-slate-500" />
                                </div>
                                <div class="min-w-0">
                                    <CardTitle class="text-base line-clamp-1">{{ teacher.name }}</CardTitle>
                                    <CardDescription class="mt-0.5 text-xs truncate">{{ teacher.email }}</CardDescription>
                                    <span v-if="teacher.tipe_guru?.nama_tipe" class="inline-block text-[11px] text-muted-foreground mt-0.5 font-medium">
                                        {{ teacher.tipe_guru.nama_tipe }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    
                    <CardContent class="space-y-4">
                        <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-3 border border-slate-100 dark:border-slate-800 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-500">Status Kinerja</span>
                                <Badge variant="outline" :class="getStatusBadge(teacher.kinerjas?.[0]).variant" class="text-[11px] px-2 py-0.5">
                                    {{ getStatusBadge(teacher.kinerjas?.[0]).label }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Card Pintasan Cepat Google Drive Guru yang Diamati -->
                        <div class="rounded-xl p-3 border transition-all"
                            :class="teacher.drive_info?.status === 'tersedia' 
                                ? 'bg-indigo-50/70 border-indigo-200/80 dark:bg-indigo-950/30 dark:border-indigo-800/60' 
                                : (teacher.drive_info?.status === 'akses_dikunci' 
                                    ? 'bg-amber-50/60 border-amber-200/70 dark:bg-amber-950/20 dark:border-amber-800/50' 
                                    : 'bg-slate-50 border-slate-200/70 dark:bg-slate-900/40 dark:border-slate-800/60')">
                            
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                        :class="teacher.drive_info?.status === 'tersedia' 
                                            ? 'bg-indigo-600 text-white shadow-xs' 
                                            : (teacher.drive_info?.status === 'akses_dikunci' 
                                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' 
                                                : 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400')">
                                        <Folder class="w-4 h-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <p class="text-xs font-bold truncate text-slate-900 dark:text-slate-100">
                                                Drive Kinerja
                                            </p>
                                            <span v-if="teacher.drive_info?.status === 'tersedia'" class="w-2 h-2 rounded-full bg-emerald-500 shrink-0" title="Tautan Siap"></span>
                                        </div>
                                        <p class="text-[11px] text-muted-foreground truncate">
                                            <span v-if="teacher.drive_info?.status === 'tersedia'" class="text-indigo-600 dark:text-indigo-400 font-medium">
                                                Tautan Google Drive siap
                                            </span>
                                            <span v-else-if="teacher.drive_info?.status === 'akses_dikunci'" class="text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                                <Lock class="w-3 h-3" /> Akses observer dibatasi
                                            </span>
                                            <span v-else class="text-slate-400 italic">
                                                Belum diatur Wakasek
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <a 
                                        v-if="teacher.drive_info?.status === 'tersedia' && teacher.drive_info?.url" 
                                        :href="teacher.drive_info.url" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors"
                                        title="Buka Folder Google Drive Guru"
                                    >
                                        <span>Buka Drive</span>
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                    <Button
                                        v-else-if="teacher.drive_info?.status === 'akses_dikunci'"
                                        variant="outline"
                                        size="sm"
                                        class="h-7 text-[11px] px-2 text-amber-700 border-amber-300 bg-amber-50/50 cursor-not-allowed opacity-80"
                                        disabled
                                    >
                                        <Lock class="w-3 h-3 mr-1" />
                                        Terkunci
                                    </Button>
                                    <span 
                                        v-else 
                                        class="text-[11px] text-slate-400 italic px-2 py-1"
                                    >
                                        -
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <!-- Jika Tahap 2 Disetujui (Approved) & Tidak dalam status revisi tahap 2 -->
                            <template v-if="teacher.kinerjas?.[0]?.status_tahap2 === 'approved' && !['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(teacher.kinerjas?.[0]?.status_tahap2) && teacher.kinerjas?.[0]?.revisi_tahap !== 2">
                                <Link :href="`/kinerja/${teacher.id}/tahap-3`" class="block w-full">
                                    <Button class="w-full transition-all duration-300 font-medium text-xs h-9" 
                                        :class="teacher.kinerjas?.[0]?.status_tahap3 === 'menunggu_penilaian' || teacher.kinerjas?.[0]?.status_tahap3 === 'revisi_observer' ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-md' : (teacher.kinerjas?.[0]?.status_tahap3 === 'approved' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-emerald-700 hover:bg-emerald-800 text-white')">
                                        <ClipboardEdit class="w-3.5 h-3.5 mr-1.5" />
                                        {{ teacher.kinerjas?.[0]?.status_tahap3 === 'revisi_observer' ? 'Perbaiki Penilaian Tahap 3' : (teacher.kinerjas?.[0]?.status_tahap3 === 'menunggu_penilaian' ? 'Beri Penilaian Tahap 3' : (teacher.kinerjas?.[0]?.status_tahap3 === 'approved' ? 'Lihat Tahap 3 (Disetujui)' : 'Buka Tahap 3 (Refleksi)')) }}
                                    </Button>
                                </Link>
                                <div class="grid grid-cols-2 gap-2">
                                    <Link :href="`/kinerja/${teacher.id}/tahap-2`" class="block w-full">
                                        <Button variant="ghost" size="sm" class="w-full text-[11px] h-7 text-slate-500 hover:text-slate-800">
                                            Tahap 2 (ACC)
                                        </Button>
                                    </Link>
                                    <Link :href="`/kinerja/${teacher.id}/tahap-1`" class="block w-full">
                                        <Button variant="ghost" size="sm" class="w-full text-[11px] h-7 text-slate-500 hover:text-slate-800">
                                            Tahap 1 (ACC)
                                        </Button>
                                    </Link>
                                </div>
                            </template>
                            <!-- Jika Tahap 1 Disetujui (Approved) & Tidak dalam status revisi tahap 1 -->
                            <template v-else-if="teacher.kinerjas?.[0]?.status_tahap1 === 'approved' && !['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(teacher.kinerjas?.[0]?.status_tahap1) && teacher.kinerjas?.[0]?.revisi_tahap !== 1">
                                <Link :href="`/kinerja/${teacher.id}/tahap-2`" class="block w-full">
                                    <Button class="w-full transition-all duration-300 font-medium text-xs h-9" 
                                        :class="teacher.kinerjas?.[0]?.status_tahap2 === 'menunggu_penilaian' || teacher.kinerjas?.[0]?.status_tahap2 === 'revisi_observer' ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-md' : 'bg-indigo-600 hover:bg-indigo-700 text-white'">
                                        <ClipboardEdit class="w-3.5 h-3.5 mr-1.5" />
                                        {{ teacher.kinerjas?.[0]?.status_tahap2 === 'revisi_observer' ? 'Perbaiki Penilaian Tahap 2' : (teacher.kinerjas?.[0]?.status_tahap2 === 'menunggu_penilaian' ? 'Beri Penilaian Tahap 2' : 'Buka Tahap 2 (Tindak Lanjut)') }}
                                    </Button>
                                </Link>
                                <Link :href="`/kinerja/${teacher.id}/tahap-1`" class="block w-full">
                                    <Button variant="ghost" size="sm" class="w-full text-xs h-7 text-slate-500 hover:text-slate-800">
                                        {{ teacher.kinerjas?.[0]?.revisi_tahap === 2 ? 'Lihat Formulir Tahap 1 (Read-Only)' : 'Lihat Formulir Tahap 1' }}
                                    </Button>
                                </Link>
                            </template>
                            <!-- Jika Tahap 1 Belum Disetujui / Sedang Revisi Tahap 1 -->
                            <template v-else>
                                <Link :href="`/kinerja/${teacher.id}/tahap-1`" class="block w-full">
                                    <Button class="w-full transition-all duration-300 font-medium text-xs h-9" 
                                        :class="['revisi_observer', 'revisi_keduanya'].includes(teacher.kinerjas?.[0]?.status_tahap1) ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-md' : (teacher.kinerjas?.[0]?.status_tahap1 === 'menunggu_penilaian' ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-md' : 'bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 text-white')">
                                        <ClipboardEdit class="w-3.5 h-3.5 mr-1.5" />
                                        {{ ['revisi_observer', 'revisi_keduanya'].includes(teacher.kinerjas?.[0]?.status_tahap1) ? 'Perbaiki Penilaian Tahap 1' : (teacher.kinerjas?.[0]?.status_tahap1 === 'menunggu_penilaian' ? 'Beri Penilaian Observasi' : 'Lihat / Nilai Kinerja Tahap 1') }}
                                    </Button>
                                </Link>
                            </template>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State Observer -->
            <div v-else class="flex flex-col items-center justify-center py-16 px-4 border-2 border-dashed rounded-3xl bg-slate-50/50 dark:bg-slate-900/20 border-slate-200 dark:border-slate-800">
                <ClipboardEdit class="w-10 h-10 text-slate-400 mb-3" />
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Belum Ada Guru yang Ditugaskan</h3>
                <p class="text-center text-slate-500 dark:text-slate-400 max-w-sm text-xs leading-relaxed">
                    Saat ini belum ada guru yang dialokasikan kepada Anda untuk diobservasi.
                </p>
            </div>
        </div>
    </div>
</template>

