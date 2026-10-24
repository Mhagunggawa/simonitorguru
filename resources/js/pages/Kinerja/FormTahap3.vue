<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { 
    Plus, 
    Trash2, 
    ArrowLeft, 
    ArrowRight,
    CheckCircle2, 
    Clock, 
    AlertCircle, 
    ShieldCheck, 
    UserCheck, 
    BookOpen, 
    Sparkles, 
    Target, 
    Calendar, 
    Send,
    Award, 
    XCircle, 
    Lock, 
    Save, 
    Folder,
    ExternalLink
} from "@lucide/vue";
import Swal from 'sweetalert2';

const props = defineProps<{
    guru: any;
    kinerja: any;
    currentYear: number;
    kurikulumDriveUrl?: string | null;
    isReview?: boolean;
    userRoleInfo?: {
        isGuru: boolean;
        isObserver: boolean;
        isAdmin: boolean;
        currentUserId: number;
    };
}>();

// Ensure arrays have at least one empty string if empty
const ensureArray = (arr: any) => {
    if (!arr || !Array.isArray(arr) || arr.length === 0) return [''];
    return [...arr];
};

// Ensure exactly 3 items for tantangan
const ensureThreeArray = (arr: any) => {
    if (!arr || !Array.isArray(arr)) return ['', '', ''];
    const res = [...arr];
    while (res.length < 3) res.push('');
    return res.slice(0, 3);
};

// Form state
const form = useForm({
    tempat_tahap3: props.kinerja?.tempat_tahap3 || '',
    tanggal_tahap3: props.kinerja?.tanggal_tahap3 || '',
    waktu_tahap3: props.kinerja?.waktu_tahap3 || '',
    tantangan_perubahan: ensureThreeArray(props.kinerja?.tantangan_perubahan),
    kesadaran_refleksi_tahap3: props.kinerja?.kesadaran_refleksi_tahap3 ? Number(props.kinerja.kesadaran_refleksi_tahap3) : null,
    catatan_refleksi_tahap3: props.kinerja?.catatan_refleksi_tahap3 || '',
    inspirasi_didapat: ensureArray(props.kinerja?.inspirasi_didapat),
    perubahan_praktik_tahap3: props.kinerja?.perubahan_praktik_tahap3 || '',
    upaya_kompetensi_tahap3: props.kinerja?.upaya_kompetensi_tahap3 ? Number(props.kinerja.upaya_kompetensi_tahap3) : null,
    catatan_upaya_tahap3: props.kinerja?.catatan_upaya_tahap3 || '',
    perubahan_praktik_kinerja_tahap3: props.kinerja?.perubahan_praktik_kinerja_tahap3 || '',
    rencana_mengatasi_tantangan: ensureArray(props.kinerja?.rencana_mengatasi_tantangan),
    peningkatan_kinerja_tahap3: props.kinerja?.peningkatan_kinerja_tahap3 ? Number(props.kinerja.peningkatan_kinerja_tahap3) : null,
    catatan_peningkatan_tahap3: props.kinerja?.catatan_peningkatan_tahap3 || props.kinerja?.catatan_observer_tahap3 || '',
    catatan_observer_tahap3: props.kinerja?.catatan_peningkatan_tahap3 || props.kinerja?.catatan_observer_tahap3 || '',
});

// Role & Permission Computations
const userRole = computed(() => {
    const isGuru = props.userRoleInfo?.isGuru ?? false;
    const isObserver = props.userRoleInfo?.isObserver ?? false;
    const isAdmin = props.userRoleInfo?.isAdmin ?? false;
    return { isGuru, isObserver, isAdmin };
});

const currentStatus = computed(() => {
    return props.kinerja?.status_tahap3 || 'draft_guru';
});

// Stepper index: 1 (Refleksi Guru), 2 (Penilaian Observer), 3 (Verifikasi Wakasek), 4 (Approved/Selesai)
const currentStepNumber = computed(() => {
    if (currentStatus.value === 'approved') return 4;
    if (currentStatus.value === 'submitted') return 3;
    if (currentStatus.value === 'menunggu_penilaian') return 2;
    if (currentStatus.value === 'revisi_observer') return 2;
    return 1; // draft_guru, pending, revisi_guru, revisi_keduanya
});

const isTahap3Revisi = computed(() => {
    return ['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(currentStatus.value) ||
           (props.kinerja?.revisi_tahap === 3 && Boolean(props.kinerja?.revisi_target));
});

const isTahap3RevisiGuru = computed(() => {
    return ['revisi_guru', 'revisi_keduanya'].includes(currentStatus.value) ||
           (props.kinerja?.revisi_tahap === 3 && ['guru', 'keduanya'].includes(props.kinerja?.revisi_target || ''));
});

const isTahap3RevisiObserver = computed(() => {
    return currentStatus.value === 'revisi_observer' ||
           (props.kinerja?.revisi_tahap === 3 && props.kinerja?.revisi_target === 'observer');
});

// Kunci Tahap 3 jika Tahap 2 belum selesai atau sedang direvisi
const isTahap2SedangRevisi = computed(() => {
    return props.kinerja?.revisi_tahap === 2 || 
           ['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(props.kinerja?.status_tahap2) ||
           props.kinerja?.status_tahap2 !== 'approved';
});

// Editable Permissions
const canEditRefleksiGuru = computed(() => {
    if (props.isReview || isTahap2SedangRevisi.value || currentStatus.value === 'approved' || currentStatus.value === 'submitted') return false;
    
    // Saat revisi Tahap 3: HANYA jika revisi ditujukan ke guru/keduanya dan user adalah Guru atau Admin
    if (isTahap3Revisi.value) {
        return (userRole.value.isGuru || userRole.value.isAdmin) && isTahap3RevisiGuru.value;
    }
    
    // Alur reguler: Guru mengisi di Step 1
    if ((userRole.value.isGuru || userRole.value.isAdmin) && (currentStatus.value === 'draft_guru' || currentStatus.value === 'belum_mulai' || currentStatus.value === 'pending')) {
        return true;
    }
    
    return false;
});

const canEditObserver = computed(() => {
    if (props.isReview || isTahap2SedangRevisi.value || currentStatus.value === 'approved' || currentStatus.value === 'submitted') return false;
    
    // Saat status revisi_keduanya: Observer BELUM dapat mengedit (harus menunggu Guru selesai merevisi)
    if (currentStatus.value === 'revisi_keduanya') return false;

    // Saat revisi Tahap 3: HANYA jika revisi ditujukan ke observer dan user adalah Observer atau Admin
    if (isTahap3Revisi.value) {
        return (userRole.value.isObserver || userRole.value.isAdmin) && isTahap3RevisiObserver.value;
    }
    
    // Alur reguler: Observer mengisi saat Langkah 2 (menunggu_penilaian)
    if ((userRole.value.isObserver || userRole.value.isAdmin) && currentStatus.value === 'menunggu_penilaian') {
        return true;
    }
    
    return false;
});

// Helper for dynamic rows in Guru sections (hanya untuk inspirasi dan rencana)
const addArrayItem = (key: 'inspirasi_didapat' | 'rencana_mengatasi_tantangan') => {
    form[key].push('');
};

const removeArrayItem = (key: 'inspirasi_didapat' | 'rencana_mengatasi_tantangan', index: number) => {
    if (form[key].length > 1) {
        form[key].splice(index, 1);
    }
};

// 1. Draf Guru
const simpanDraftGuru = () => {
    form.transform((data) => ({
        ...data,
        action: 'draft_guru'
    })).post(`/kinerja/${props.guru.id}/tahap-3`, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Draf Tersimpan!',
                text: 'Isian refleksi Anda berhasil disimpan sebagai draf sementara.',
                confirmButtonColor: '#10b981'
            });
        }
    });
};

// 2. Submit Guru ke Observer
const submitRefleksiGuru = () => {
    // Validasi 3 Tantangan Paling Sulit
    if (!form.tantangan_perubahan.some(t => t.trim().length > 0)) {
        Swal.fire({
            icon: 'warning',
            title: 'Tantangan Masih Kosong',
            text: 'Harap isi setidaknya satu dari 3 tantangan paling sulit yang dihadapi dalam melakukan perubahan.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Inspirasi Baru
    if (!form.inspirasi_didapat.some(i => i.trim().length > 0)) {
        Swal.fire({
            icon: 'warning',
            title: 'Inspirasi Masih Kosong',
            text: 'Harap isi setidaknya satu inspirasi baru yang Anda dapatkan dari upaya tindak lanjut.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Perubahan Praktik (Bagian 2)
    if (!form.perubahan_praktik_tahap3.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Perubahan Praktik (Bagian 2) Belum Diisi',
            text: 'Harap jelaskan perubahan praktik guru di ruang kelas/satuan pendidikan pada Bagian 2 (Upaya Mempelajari).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Perubahan Praktik Kinerja (Bagian 3)
    if (!form.perubahan_praktik_kinerja_tahap3.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Perubahan Praktik (Bagian 3) Belum Diisi',
            text: 'Harap jelaskan perubahan praktik guru di ruang kelas/satuan pendidikan pada Bagian 3 (Peningkatan Kinerja).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Rencana Mengatasi Tantangan
    if (!form.rencana_mengatasi_tantangan.some(r => r.trim().length > 0)) {
        Swal.fire({
            icon: 'warning',
            title: 'Rencana Mengatasi Tantangan Belum Diisi',
            text: 'Harap isi setidaknya satu rencana guru dalam mengatasi tantangan agar bisa memastikan perubahan terjadi.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    const isRevisi = isTahap3RevisiGuru.value;
    const isKeduanya = currentStatus.value === 'revisi_keduanya' || props.kinerja?.revisi_target === 'keduanya';
    const actionName = isRevisi ? 'simpan_revisi_guru_tahap3' : 'simpan_refleksi_guru';
    
    let confirmTitle = 'Kirim Refleksi ke Observer?';
    let confirmDesc = 'Setelah dikirim, Observer akan dapat meninjau refleksi dan melakukan penilaian.';
    let confirmBtn = 'Ya, Kirim Sekarang';

    if (isRevisi) {
        if (isKeduanya) {
            confirmTitle = 'Kirim Perbaikan ke Observer?';
            confirmDesc = 'Perbaikan refleksi Anda akan diajukan kembali ke Observer untuk dinilai ulang.';
            confirmBtn = 'Ya, Ajukan ke Observer';
        } else {
            confirmTitle = 'Kirim Perbaikan ke Wakasek?';
            confirmDesc = 'Perbaikan refleksi Anda akan dikirimkan kembali ke Wakasek untuk persetujuan (ACC).';
            confirmBtn = 'Ya, Ajukan ke Wakasek';
        }
    }

    Swal.fire({
        title: confirmTitle,
        text: confirmDesc,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmBtn,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                action: actionName
            })).post(`/kinerja/${props.guru.id}/tahap-3`, {
                preserveScroll: true,
                onSuccess: () => {
                    let successTitle = 'Berhasil Dikirim!';
                    let successDesc = 'Refleksi Tahap 3 berhasil dikirimkan ke Observer.';
                    if (isRevisi) {
                        successTitle = 'Perbaikan Berhasil Diajukan';
                        successDesc = isKeduanya
                            ? 'Perbaikan refleksi Tahap 3 telah diajukan kembali ke Observer untuk dinilai ulang.'
                            : 'Perbaikan refleksi Tahap 3 telah diajukan kembali ke Wakasek untuk persetujuan (ACC).';
                    }

                    Swal.fire({
                        icon: 'success',
                        title: successTitle,
                        text: successDesc,
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

// 3. Submit Observer ke Wakasek
const submitPenilaianObserver = () => {
    // Validasi Jadwal
    if (!form.tempat_tahap3?.trim() || !form.tanggal_tahap3 || !form.waktu_tahap3) {
        Swal.fire({
            icon: 'warning',
            title: 'Jadwal Pelaksanaan Belum Lengkap',
            text: 'Silakan isi Tempat, Hari/Tanggal, dan Waktu pelaksanaan terlebih dahulu pada Identitas & Jadwal.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Pertanyaan 1 (Kesadaran Refleksi)
    if (!form.kesadaran_refleksi_tahap3) {
        Swal.fire({
            icon: 'warning',
            title: 'Penilaian Upaya Refleksi Belum Dipilih',
            text: 'Silakan pilih penilaian kesadaran guru ketika berefleksi pada Bagian 1.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Pertanyaan 2 (Upaya Kompetensi)
    if (!form.upaya_kompetensi_tahap3) {
        Swal.fire({
            icon: 'warning',
            title: 'Penilaian Upaya Mempelajari Belum Dipilih',
            text: 'Silakan pilih penilaian upaya guru mempelajari dan menguasai kompetensi pada Bagian 2.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Pertanyaan 3 (Peningkatan Kinerja)
    if (!form.peningkatan_kinerja_tahap3) {
        Swal.fire({
            icon: 'warning',
            title: 'Penilaian Peningkatan Kinerja Belum Dipilih',
            text: 'Silakan pilih penilaian peningkatan kinerja yang ditunjukkan guru pada Bagian 3.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    const isRevisi = isTahap3RevisiObserver.value;
    const actionName = isRevisi ? 'simpan_revisi_observer_tahap3' : 'simpan_penilaian_tahap3';

    Swal.fire({
        title: 'Kirim Penilaian ke Wakasek?',
        text: 'Setelah dikirim, Wakasek Kurikulum akan menerima notifikasi untuk meninjau dan memberikan persetujuan (ACC).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Kirim ke Wakasek',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                action: actionName
            })).post(`/kinerja/${props.guru.id}/tahap-3`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Penilaian Berhasil Dikirim!',
                        text: 'Penilaian Tahap 3 berhasil disimpan dan diajukan ke Wakasek untuk persetujuan (ACC).',
                        confirmButtonColor: '#059669'
                    });
                }
            });
        }
    });
};

// 4. Review Actions (Wakasek / Admin)
const approveDariReview = () => {
    Swal.fire({
        title: 'Setujui (ACC) Kinerja Tahap 3?',
        text: 'Apakah Anda yakin ingin menyetujui formulir refleksi tindak lanjut ini? Seluruh siklus kinerja guru akan selesai penuh.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Setujui Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(`/kinerja-approval/${props.kinerja.id}/approve`, {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disetujui!',
                        text: 'Formulir Tahap 3 telah disetujui (ACC) dan notifikasi email telah dikirimkan kepada guru.',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

const rejectDariReview = () => {
    if (!props.kinerja?.id) return;
    
    Swal.fire({
        title: 'Tolak & Minta Revisi Form Kinerja',
        html: `
            <div style="text-align: left; font-size: 13px; color: #334155; margin-top: 8px;">
                <p style="margin-bottom: 12px; font-size: 13px; color: #64748b; line-height: 1.5;">
                    Silakan tentukan pihak yang diminta untuk merevisi formulir Tahap 3 dan sertakan catatan perbaikan:
                </p>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1e293b;">
                    Pihak yang Harus Merevisi:
                </label>
                <select id="swal-revisi-target" style="width: 100%; margin: 0 0 16px 0; height: 38px; font-size: 13px; padding: 6px 12px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; background-color: #fff; color: #1e293b;">
                    <option value="guru">Guru yang Diamati (${props.guru?.name || 'Guru'})</option>
                    <option value="observer">Observer / Pengamat (${props.kinerja?.observer?.name || 'Observer'})</option>
                    <option value="keduanya">Keduanya (Guru & Observer Perlu Diskusi)</option>
                </select>

                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1e293b;">
                    Catatan Masukan / Alasan Revisi:
                </label>
                <textarea id="swal-catatan-revisi" rows="3" placeholder="Tuliskan catatan perbaikan yang harus dilakukan..." style="width: 100%; font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; background-color: #fff; color: #1e293b; resize: vertical;"></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Kirim Permintaan Revisi',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const target = (document.getElementById('swal-revisi-target') as HTMLSelectElement).value;
            const catatan = (document.getElementById('swal-catatan-revisi') as HTMLTextAreaElement).value;
            if (!catatan || catatan.trim().length < 3) {
                Swal.showValidationMessage('Catatan revisi wajib diisi minimal 3 karakter.');
                return false;
            }
            return { revisi_target: target, catatan_revisi: catatan };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            form.transform((data) => ({
                ...data,
                revisi_target: result.value.revisi_target,
                catatan_revisi: result.value.catatan_revisi,
                tahap: 3
            })).post(`/kinerja-approval/${props.kinerja.id}/reject`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'info',
                        title: 'Permintaan Revisi Terkirim',
                        text: 'Pemberitahuan revisi formulir Tahap 3 telah dikirimkan ke pihak terkait.',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head :title="`Refleksi Tindak Lanjut Tahap 3 - ${guru.name}`" />

    <div class="max-w-6xl mx-auto p-3 md:p-8 space-y-5 md:space-y-8 animate-in fade-in duration-300">
        <!-- Top Nav & Breadcrumb -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b pb-4 dark:border-slate-800 gap-3">
            <Link :href="isReview ? '/kinerja-approval' : '/kinerja'">
                <Button variant="ghost" size="sm" class="gap-2 text-slate-600 hover:text-slate-900 dark:text-slate-400">
                    <ArrowLeft class="w-4 h-4" /> Kembali ke {{ isReview ? 'Daftar Persetujuan' : 'Observasi Kinerja' }}
                </Button>
            </Link>

            <!-- Status Indicator Badge & Drive Shortcut -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <a v-if="kurikulumDriveUrl" :href="kurikulumDriveUrl" target="_blank" rel="noopener noreferrer">
                    <Button variant="outline" size="sm" class="gap-1.5 text-xs border-indigo-200 text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100 hover:text-indigo-800 dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300 shadow-xs">
                        <Folder class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                        <span>Drive Kinerja Guru</span>
                        <ExternalLink class="w-3 h-3" />
                    </Button>
                </a>

                <div class="flex flex-wrap items-center gap-2">
                    <Badge v-if="currentStatus === 'approved'" class="bg-emerald-100 text-emerald-800 border-emerald-300 px-3 py-1 font-medium text-xs">
                        <CheckCircle2 class="w-3.5 h-3.5 mr-1 text-emerald-600" /> Tahap 3 Selesai & Disetujui
                    </Badge>
                    <Badge v-else-if="currentStatus === 'submitted'" class="bg-purple-100 text-purple-800 border-purple-300 px-3 py-1 font-medium text-xs">
                        <Clock class="w-3.5 h-3.5 mr-1 text-purple-600" /> Menunggu Persetujuan Wakasek
                    </Badge>
                    <Badge v-else-if="currentStatus === 'menunggu_penilaian'" class="bg-amber-100 text-amber-800 border-amber-300 px-3 py-1 font-medium text-xs">
                        <Clock class="w-3.5 h-3.5 mr-1 text-amber-600" /> Menunggu Penilaian Observer
                    </Badge>
                    <Badge v-else-if="currentStatus === 'revisi_guru'" class="bg-rose-100 text-rose-800 border-rose-300 px-3 py-1 font-medium text-xs">
                        <AlertCircle class="w-3.5 h-3.5 mr-1 text-rose-600" /> Revisi Guru
                    </Badge>
                    <Badge v-else-if="currentStatus === 'revisi_observer'" class="bg-amber-100 text-amber-800 border-amber-300 px-3 py-1 font-medium text-xs">
                        <AlertCircle class="w-3.5 h-3.5 mr-1 text-amber-600" /> Revisi Observer
                    </Badge>
                    <Badge v-else-if="currentStatus === 'revisi_keduanya'" class="bg-rose-100 text-rose-800 border-rose-300 px-3 py-1 font-medium text-xs">
                        <AlertCircle class="w-3.5 h-3.5 mr-1 text-rose-600" /> Revisi Kedua Pihak
                    </Badge>
                    <Badge v-else class="bg-slate-100 text-slate-800 border-slate-300 px-3 py-1 font-medium text-xs">
                        <Clock class="w-3.5 h-3.5 mr-1 text-slate-600" /> Pengisian Refleksi Guru
                    </Badge>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-xl md:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5 md:gap-3">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-950 rounded-xl text-emerald-600 shrink-0">
                        <Target class="w-5 h-5 md:w-7 md:h-7" />
                    </div>
                    <span>Refleksi Tindak Lanjut Observasi Kinerja Guru</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 text-xs md:text-sm">
                    Formulir Refleksi Tindak Lanjut (Tahap 3) untuk <span class="font-semibold text-slate-800 dark:text-slate-200">{{ guru.name }}</span> &bull; Periode Januari - Desember {{ currentYear }}
                </p>
            </div>
        </div>

        <!-- Rejection / Revision Alert Banner if any -->
        <div v-if="isTahap3Revisi && kinerja?.catatan_revisi" class="p-4 rounded-2xl bg-rose-50/80 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 flex items-start gap-3.5 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <AlertCircle class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="font-bold text-sm text-rose-900 dark:text-rose-200">
                        Formulir Tahap 3 (Refleksi Tindak Lanjut) Memerlukan Revisi
                    </h4>
                    <Badge variant="outline" class="bg-rose-100 text-rose-800 border-rose-300 text-[10px] font-bold uppercase tracking-wider">
                        Target: {{ kinerja.revisi_target === 'guru' ? 'Guru yang Diamati' : (kinerja.revisi_target === 'observer' ? 'Observer / Pengamat' : 'Keduanya (Guru & Observer)') }}
                    </Badge>
                </div>
                <p class="text-xs text-rose-700 dark:text-rose-300 mt-1.5 leading-relaxed font-medium">
                    Catatan dari Peninjau: "{{ kinerja.catatan_revisi }}"
                </p>
                <p class="text-[11px] text-rose-500 mt-1">
                    Silakan lakukan perbaikan yang diperlukan dan simpan kembali formulir untuk diajukan ulang.
                </p>
            </div>
        </div>

        <!-- Banner jika Tahap 2 belum selesai atau sedang direvisi -> Tahap 3 dinonaktifkan (read-only / terkunci) -->
        <div v-if="isTahap2SedangRevisi" class="p-4 rounded-2xl bg-amber-50/90 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 flex items-start gap-3.5 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                <Lock class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="font-bold text-sm text-amber-900 dark:text-amber-200">
                        Formulir Tahap 3 Dikunci (Tahap 2 Belum Selesai / Sedang Direvisi)
                    </h4>
                    <Badge variant="outline" class="bg-amber-100 text-amber-800 border-amber-300 text-[10px] font-bold uppercase tracking-wider">
                        Tahap 2 Belum Disetujui
                    </Badge>
                </div>
                <p class="text-xs text-amber-800 dark:text-amber-300 mt-1.5 leading-relaxed font-medium">
                    Formulir Tindak Lanjut Observasi (Tahap 2) saat ini belum selesai atau sedang dalam proses perbaikan/revisi. Seluruh isian Formulir Tahap 3 ini dinonaktifkan total dan tidak dapat diubah sebelum perbaikan Tahap 2 diselesaikan serta disetujui (ACC) kembali oleh Wakasek.
                </p>
                <div class="mt-3">
                    <Link :href="`/kinerja/${guru.id}/tahap-2`">
                        <Button size="sm" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs gap-1.5 shadow-xs">
                            Buka Formulir Tahap 2 <ArrowRight class="w-3.5 h-3.5" />
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stepper Progress Bar (Identik dengan FormTahap2) -->
        <Card class="border shadow-sm bg-gradient-to-r from-slate-50 to-emerald-50/30 dark:from-slate-900 dark:to-emerald-950/20">
            <CardContent class="p-4 md:p-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Step 1: Refleksi Pasca TL (Guru) -->
                    <div class="flex items-center gap-3 p-3 rounded-xl border transition-all"
                        :class="currentStepNumber >= 2 ? 'bg-emerald-50 border-emerald-300 text-emerald-900 dark:bg-emerald-950/30' : (currentStepNumber === 1 ? 'bg-white border-emerald-500 shadow-sm text-emerald-900 dark:bg-slate-900' : 'bg-slate-50/50 border-slate-200 text-slate-400 dark:bg-slate-900/50')">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shrink-0"
                            :class="currentStepNumber >= 2 ? 'bg-emerald-600 text-white' : (currentStepNumber === 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-800')">
                            <CheckCircle2 v-if="currentStepNumber >= 2" class="w-4 h-4" />
                            <span v-else>1</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider">Langkah 1</p>
                            <p class="text-sm font-bold truncate">Refleksi Pasca TL (Guru)</p>
                        </div>
                    </div>

                    <!-- Step 2: Penilaian Observer -->
                    <div class="flex items-center gap-3 p-3 rounded-xl border transition-all"
                        :class="currentStepNumber >= 3 ? 'bg-emerald-50 border-emerald-300 text-emerald-900 dark:bg-emerald-950/30' : (currentStepNumber === 2 ? 'bg-white border-amber-400 shadow-sm text-amber-900 dark:bg-slate-900' : 'bg-slate-50/50 border-slate-200 text-slate-400 dark:bg-slate-900/50')">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shrink-0"
                            :class="currentStepNumber >= 3 ? 'bg-emerald-600 text-white' : (currentStepNumber === 2 ? 'bg-amber-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-800')">
                            <CheckCircle2 v-if="currentStepNumber >= 3" class="w-4 h-4" />
                            <span v-else>2</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider">Langkah 2</p>
                            <p class="text-sm font-bold truncate">Penilaian & Catatan (Observer)</p>
                        </div>
                    </div>

                    <!-- Step 3: Verifikasi Wakasek -->
                    <div class="flex items-center gap-3 p-3 rounded-xl border transition-all"
                        :class="currentStepNumber >= 4 ? 'bg-emerald-50 border-emerald-300 text-emerald-900 dark:bg-emerald-950/30' : (currentStepNumber === 3 ? 'bg-white border-purple-400 shadow-sm text-purple-900 dark:bg-slate-900' : 'bg-slate-50/50 border-slate-200 text-slate-400 dark:bg-slate-900/50')">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shrink-0"
                            :class="currentStepNumber >= 4 ? 'bg-emerald-600 text-white' : (currentStepNumber === 3 ? 'bg-purple-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-800')">
                            <CheckCircle2 v-if="currentStepNumber >= 4" class="w-4 h-4" />
                            <span v-else>3</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider">Langkah 3</p>
                            <p class="text-sm font-bold truncate">Verifikasi ACC (Wakasek)</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Current Status Context Alert Banner -->
        <div v-if="currentStatus === 'draft_guru' || currentStatus === 'pending'" class="p-4 rounded-xl border border-indigo-200 bg-indigo-50/70 dark:bg-indigo-950/30 dark:border-indigo-800 flex items-start gap-3">
            <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg text-indigo-700 dark:text-indigo-300 shrink-0">
                <BookOpen class="w-5 h-5" />
            </div>
            <div class="text-sm text-indigo-950 dark:text-indigo-200">
                <h4 class="font-bold text-base">Tahap Refleksi Pasca Tindak Lanjut Guru</h4>
                <p class="mt-0.5 leading-relaxed">
                    Guru mengisi refleksi mengenai tantangan yang dihadapi saat perubahan, inspirasi yang didapatkan, perubahan praktik di ruang kelas / satuan pendidikan, serta rencana mengatasi tantangan. Setelah disimpan dan diajukan, Observer akan menerima email untuk melakukan penilaian.
                </p>
            </div>
        </div>

        <div v-else-if="currentStatus === 'menunggu_penilaian'" class="p-4 rounded-xl border border-amber-200 bg-amber-50/70 dark:bg-amber-950/30 dark:border-amber-800 flex items-start gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900 rounded-lg text-amber-700 dark:text-amber-300 shrink-0">
                <UserCheck class="w-5 h-5" />
            </div>
            <div class="text-sm text-amber-950 dark:text-amber-200">
                <h4 class="font-bold text-base">Menunggu Penilaian Observer</h4>
                <p class="mt-0.5 leading-relaxed">
                    Guru telah mengajukan refleksi tindak lanjut. Kini giliran Pengamat (Observer) untuk mengisi Jadwal Pelaksanaan, menilai Kesadaran Guru ketika berefleksi, Upaya Mempelajari Kompetensi, Peningkatan Kinerja, dan Catatan Akhir Pengamat.
                </p>
            </div>
        </div>

        <div v-else-if="currentStatus === 'submitted'" class="p-4 rounded-xl border border-purple-200 bg-purple-50/70 dark:bg-purple-950/30 dark:border-purple-800 flex items-start gap-3">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg text-purple-700 dark:text-purple-300 shrink-0">
                <ShieldCheck class="w-5 h-5" />
            </div>
            <div class="text-sm text-purple-950 dark:text-purple-200">
                <h4 class="font-bold text-base">Menunggu Verifikasi & Persetujuan Wakasek</h4>
                <p class="mt-0.5 leading-relaxed">
                    Observer telah menyelesaikan penilaian refleksi tindak lanjut. Formulir ini sedang menunggu review dan persetujuan (ACC) dari Wakasek Kurikulum.
                </p>
            </div>
        </div>

        <div v-else-if="currentStatus === 'approved'" class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/70 dark:bg-emerald-950/30 dark:border-emerald-800 flex items-start gap-3">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg text-emerald-700 dark:text-emerald-300 shrink-0">
                <Award class="w-5 h-5" />
            </div>
            <div class="text-sm text-emerald-950 dark:text-emerald-200">
                <h4 class="font-bold text-base">Formulir Tahap 3 Telah Disetujui (ACC)</h4>
                <p class="mt-0.5 leading-relaxed">
                    Tahap 3 telah resmi disetujui oleh Wakasek Kurikulum dan seluruh siklus evaluasi kinerja guru tahun ini telah selesai secara paripurna.
                </p>
            </div>
        </div>

        <form @submit.prevent="" class="space-y-8">
            <!-- IDENTITAS & JADWAL PELAKSANAAN REFLEKSI -->
            <Card class="border-t-4 border-t-slate-500 shadow-md">
                <CardHeader class="bg-slate-50/70 dark:bg-slate-800/20 border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Calendar class="w-5 h-5 text-slate-600 dark:text-slate-400" />
                            <CardTitle class="text-lg md:text-xl text-slate-800 dark:text-slate-200">Identitas & Jadwal Diskusi Refleksi Tindak Lanjut</CardTitle>
                        </div>
                        <Badge variant="outline" class="border-slate-300 bg-slate-100 text-slate-700 text-xs font-normal dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            Diisi oleh Observer
                        </Badge>
                    </div>
                    <CardDescription>
                        Jadwal pelaksanaan diskusi refleksi hasil tindak lanjut observasi kelas antara guru dan observer.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">Nama Guru yang Diobservasi</Label>
                        <div class="flex h-10 w-full items-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-100">
                            {{ guru.name }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">Nama Pengamat (Observer)</Label>
                        <div class="flex h-10 w-full items-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-100">
                            {{ kinerja?.observer?.name || guru.observer?.name || $page.props.auth?.user?.name }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">Periode</Label>
                        <div class="flex h-10 w-full items-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-100">
                            Januari - Desember {{ currentYear }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">
                            Tempat / Lokasi Diskusi <span v-if="canEditObserver" class="text-red-500">*</span>
                        </Label>
                        <Input 
                            id="tempat_tahap3"
                            v-model="form.tempat_tahap3"
                            placeholder="Contoh: Ruang Guru / Kelas XI RPL" 
                            :disabled="!canEditObserver"
                            class="bg-white dark:bg-slate-900"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">
                            Hari / Tanggal Diskusi <span v-if="canEditObserver" class="text-red-500">*</span>
                        </Label>
                        <Input 
                            id="tanggal_tahap3"
                            type="date" 
                            v-model="form.tanggal_tahap3" 
                            :disabled="!canEditObserver"
                            class="bg-white dark:bg-slate-900"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">
                            Waktu Pelaksanaan (WITA) <span v-if="canEditObserver" class="text-red-500">*</span>
                        </Label>
                        <Input 
                            id="waktu_tahap3"
                            type="time" 
                            v-model="form.waktu_tahap3" 
                            :disabled="!canEditObserver"
                            class="bg-white dark:bg-slate-900"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- 1. ISI PENILAIAN UPAYA REFLEKSI -->
            <Card class="border-t-4 border-t-emerald-500 shadow-md">
                <CardHeader class="bg-emerald-50/50 dark:bg-emerald-900/10 border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Sparkles class="w-5 h-5 text-emerald-600" />
                            <CardTitle class="text-lg md:text-xl text-emerald-700 dark:text-emerald-400">1. Isi Penilaian Upaya Refleksi</CardTitle>
                        </div>
                        <Badge variant="outline" class="border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-normal">
                            Diisi oleh Guru & Observer
                        </Badge>
                    </div>
                    <CardDescription>
                        Pengisian 3 tantangan perubahan oleh Guru, serta penilaian kesadaran refleksi & catatan observasi oleh Observer.
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-6 space-y-8">
                    <!-- Bagian A: 3 tantangan paling sulit yang guru hadapi dalam melakukan perubahan (Guru) -->
                    <div class="space-y-4">
                        <div class="border-b pb-3 dark:border-slate-800">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                3 tantangan paling sulit yang guru hadapi dalam melakukan perubahan
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Tuliskan 3 tantangan atau kesulitan konkret paling berat yang Anda hadapi dalam melakukan perubahan pembelajaran (Diisi oleh Guru).
                            </p>
                        </div>

                        <!-- Tepat 3 jawaban (fitur penambahan & penghapusan kolom ditiadakan) -->
                        <div class="space-y-3">
                            <div
                                v-for="(tantangan, tIdx) in form.tantangan_perubahan"
                                :key="tIdx"
                                class="flex items-center gap-3"
                            >
                                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-xs font-mono font-bold shrink-0">
                                    #{{ tIdx + 1 }}
                                </span>
                                <Input
                                    v-model="form.tantangan_perubahan[tIdx]"
                                    :placeholder="`Tantangan ke-${tIdx + 1} yang dihadapi dalam melakukan perubahan...`"
                                    :disabled="!canEditRefleksiGuru"
                                    class="bg-white dark:bg-slate-900 text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Bagian B: Penilaian Observer & Catatan Upaya Refleksi -->
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 space-y-5">
                        <div class="border-b pb-3 dark:border-slate-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    Penilaian Observer: Bagaimana Kesadaran Guru Ketika Berefleksi?
                                </h3>
                                <Badge variant="outline" class="border-blue-200 bg-blue-50 text-blue-700 text-xs font-normal">
                                    Diisi oleh Observer
                                </Badge>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Pilih salah satu tingkat kesadaran guru berdasarkan hasil refleksi 3 tantangan yang dihadapi:
                            </p>
                        </div>

                        <!-- Radio Card Options -->
                        <div class="flex flex-col gap-3">
                            <!-- Option 1: Pink / Rose -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.kesadaran_refleksi_tahap3" 
                                    :value="1" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-pink-50 peer-checked:border-pink-500 border-slate-200 group-hover:border-pink-300 dark:peer-checked:bg-pink-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-pink-500 flex items-center justify-center">
                                        <div v-if="form.kesadaran_refleksi_tahap3 === 1" class="w-2.5 h-2.5 rounded-full bg-pink-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-pink-700 dark:group-hover:text-pink-300 transition-colors">
                                            Sadar tantangan dan penyebabnya berada di luar kendali diri
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Guru menyadari adanya kesulitan/tantangan namun menganggap faktor penyebab utama berasal dari luar kendali dirinya.
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 2: Kuning / Amber -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.kesadaran_refleksi_tahap3" 
                                    :value="2" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-amber-50 peer-checked:border-amber-500 border-slate-200 group-hover:border-amber-300 dark:peer-checked:bg-amber-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-amber-500 flex items-center justify-center">
                                        <div v-if="form.kesadaran_refleksi_tahap3 === 2" class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-amber-700 dark:group-hover:text-amber-300 transition-colors">
                                            Sadar tantangan dan penyebabnya berada di dalam kendali diri
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Guru menyadari tantangan yang dihadapi dan memahami faktor penyebab berada dalam kendali diri untuk diperbaiki.
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 3: Hijau / Emerald -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.kesadaran_refleksi_tahap3" 
                                    :value="3" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-emerald-50 peer-checked:border-emerald-500 border-slate-200 group-hover:border-emerald-300 dark:peer-checked:bg-emerald-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-emerald-500 flex items-center justify-center">
                                        <div v-if="form.kesadaran_refleksi_tahap3 === 3" class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">
                                            Sadar tantangan dan penyebabnya berada di dalam kendali diri serta dampaknya
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Guru menyadari tantangan dan penyebab di dalam kendali diri, serta memahami secara mendalam dampaknya bagi peserta didik.
                                        </p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Catatan Observer Bagian 1 -->
                        <div class="pt-3 border-t dark:border-slate-800 space-y-2">
                            <Label for="catatan_refleksi_tahap3" class="text-slate-700 dark:text-slate-300 font-semibold text-sm">
                                Catatan Observer (Upaya Refleksi)
                            </Label>
                            <p class="text-xs text-slate-500">
                                Tuliskan catatan apresiasi, masukan, atau ulasan observer mengenai upaya refleksi guru di sini:
                            </p>
                            <Textarea
                                id="catatan_refleksi_tahap3"
                                v-model="form.catatan_refleksi_tahap3"
                                rows="3"
                                placeholder="Tuliskan catatan observer mengenai upaya refleksi guru..."
                                :disabled="!canEditObserver"
                                class="bg-white dark:bg-slate-900 text-sm leading-relaxed"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- 2. ISI PENILAIAN UPAYA MEMPELAJARI -->
            <Card class="border-t-4 border-t-blue-500 shadow-md">
                <CardHeader class="bg-blue-50/50 dark:bg-blue-900/10 border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <BookOpen class="w-5 h-5 text-blue-600" />
                            <CardTitle class="text-lg md:text-xl text-blue-700 dark:text-blue-400">2. Isi Penilaian Upaya Mempelajari</CardTitle>
                        </div>
                        <Badge variant="outline" class="border-blue-200 bg-blue-50 text-blue-700 text-xs font-normal">
                            Diisi oleh Guru & Observer
                        </Badge>
                    </div>
                    <CardDescription>
                        Inspirasi baru & perubahan praktik oleh Guru, serta penilaian upaya mempelajari kompetensi & catatan observasi oleh Observer.
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-6 space-y-8">
                    <!-- Bagian A: Inspirasi baru yang guru dapatkan dari upaya tindak lanjut (Guru) -->
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-3 dark:border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    Inspirasi baru yang guru dapatkan dari upaya tindak lanjut
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Catat pemahaman baru, ide pembelajaran, atau wawasan yang diperoleh dari hasil upaya tindak lanjut (Diisi oleh Guru).
                                </p>
                            </div>
                            <Button
                                v-if="canEditRefleksiGuru"
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="addArrayItem('inspirasi_didapat')"
                                class="gap-1.5 text-xs border-blue-300 text-blue-700 dark:border-blue-800 dark:text-blue-300 hover:bg-blue-50 shadow-xs shrink-0 self-start sm:self-auto"
                            >
                                <Plus class="w-3.5 h-3.5" /> Tambah Kolom Inspirasi
                            </Button>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="(inspirasi, iIdx) in form.inspirasi_didapat"
                                :key="iIdx"
                                class="flex items-center gap-3"
                            >
                                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-xs font-mono font-bold shrink-0">
                                    #{{ iIdx + 1 }}
                                </span>
                                <Input
                                    v-model="form.inspirasi_didapat[iIdx]"
                                    placeholder="Tuliskan poin inspirasi baru yang didapatkan..."
                                    :disabled="!canEditRefleksiGuru"
                                    class="bg-white dark:bg-slate-900 text-sm"
                                />
                                <Button
                                    v-if="canEditRefleksiGuru && form.inspirasi_didapat.length > 1"
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    @click="removeArrayItem('inspirasi_didapat', iIdx)"
                                    class="h-9 w-9 text-rose-500 hover:bg-rose-50 hover:text-rose-700 shrink-0"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian B: Berdasarkan inspirasi yang didapatkan, perubahan praktik guru di ruang kelas/satuan pendidikan (Guru) -->
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Berdasarkan inspirasi yang didapatkan, perubahan praktik guru di ruang kelas/satuan pendidikan
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Ceritakan secara terperinci apa perubahan praktik nyata yang telah Anda terapkan di ruang kelas atau satuan pendidikan (Diisi oleh Guru).
                            </p>
                        </div>
                        <Textarea
                            id="perubahan_praktik_tahap3"
                            v-model="form.perubahan_praktik_tahap3"
                            rows="4"
                            placeholder="Tuliskan secara terperinci perubahan praktik dalam proses pembelajaran, strategi mengajar, atau interaksi dengan peserta didik..."
                            :disabled="!canEditRefleksiGuru"
                            class="bg-white dark:bg-slate-900 text-sm leading-relaxed"
                        />
                    </div>

                    <!-- Bagian C: Penilaian Observer & Catatan Upaya Mempelajari -->
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 space-y-5">
                        <div class="border-b pb-3 dark:border-slate-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    Penilaian Observer: Bagaimana upaya Guru mempelajari dan menguasai kompetensi yang dibutuhkan untuk peningkatan kinerja berdasarkan Rating Observasi Praktik Kinerja dan hasil refleksi?
                                </h3>
                                <Badge variant="outline" class="border-blue-200 bg-blue-50 text-blue-700 text-xs font-normal shrink-0">
                                    Diisi oleh Observer
                                </Badge>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Berdasarkan Rating Observasi Praktik Kinerja dan hasil refleksi guru, pilih capaian upaya guru:
                            </p>
                        </div>

                        <!-- Radio Card Options -->
                        <div class="flex flex-col gap-3">
                            <!-- Option 1: Pink / Rose -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.upaya_kompetensi_tahap3" 
                                    :value="1" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-pink-50 peer-checked:border-pink-500 border-slate-200 group-hover:border-pink-300 dark:peer-checked:bg-pink-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-pink-500 flex items-center justify-center">
                                        <div v-if="form.upaya_kompetensi_tahap3 === 1" class="w-2.5 h-2.5 rounded-full bg-pink-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-pink-700 dark:group-hover:text-pink-300 transition-colors">
                                            1. Melakukan Upaya Tindak Lanjut
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Guru telah berupaya melakukan pembelajaran dan langkah tindak lanjut sesuai rencana yang disepakati.
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 2: Kuning / Amber -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.upaya_kompetensi_tahap3" 
                                    :value="2" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-amber-50 peer-checked:border-amber-500 border-slate-200 group-hover:border-amber-300 dark:peer-checked:bg-amber-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-amber-500 flex items-center justify-center">
                                        <div v-if="form.upaya_kompetensi_tahap3 === 2" class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-amber-700 dark:group-hover:text-amber-300 transition-colors">
                                            2. Mempraktikkan Inspirasi
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Guru mempraktikkan ide dan inspirasi baru dari hasil tindak lanjut secara aktif di lingkungan pembelajaran.
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 3: Hijau / Emerald -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.upaya_kompetensi_tahap3" 
                                    :value="3" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-emerald-50 peer-checked:border-emerald-500 border-slate-200 group-hover:border-emerald-300 dark:peer-checked:bg-emerald-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-emerald-500 flex items-center justify-center">
                                        <div v-if="form.upaya_kompetensi_tahap3 === 3" class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">
                                            3. Membagikan Praktik Baik
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Guru tidak hanya mempraktikkan inspirasi, tetapi juga aktif membagikan praktik baik hasil inspirasi kepada rekan sejawat.
                                        </p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Catatan Observer Bagian 2 -->
                        <div class="pt-3 border-t dark:border-slate-800 space-y-2">
                            <Label for="catatan_upaya_tahap3" class="text-slate-700 dark:text-slate-300 font-semibold text-sm">
                                Catatan Observer (Upaya Mempelajari)
                            </Label>
                            <p class="text-xs text-slate-500">
                                Tuliskan catatan apresiasi, masukan, atau rekomendasi observer terkait upaya guru mempelajari kompetensi di sini:
                            </p>
                            <Textarea
                                id="catatan_upaya_tahap3"
                                v-model="form.catatan_upaya_tahap3"
                                rows="3"
                                placeholder="Tuliskan catatan observer mengenai upaya guru mempelajari kompetensi..."
                                :disabled="!canEditObserver"
                                class="bg-white dark:bg-slate-900 text-sm leading-relaxed"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- 3. ISI PENILAIAN PENINGKATAN KINERJA -->
            <Card class="border-t-4 border-t-purple-500 shadow-md">
                <CardHeader class="bg-purple-50/50 dark:bg-purple-900/10 border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Target class="w-5 h-5 text-purple-600" />
                            <CardTitle class="text-lg md:text-xl text-purple-700 dark:text-purple-400">3. Isi Penilaian Peningkatan Kinerja</CardTitle>
                        </div>
                        <Badge variant="outline" class="border-purple-200 bg-purple-50 text-purple-700 text-xs font-normal">
                            Diisi oleh Guru & Observer
                        </Badge>
                    </div>
                    <CardDescription>
                        Perubahan praktik & rencana mengatasi tantangan oleh Guru, serta evaluasi peningkatan kinerja & catatan observasi oleh Observer.
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-6 space-y-8">
                    <!-- Bagian A: Berdasarkan inspirasi yang didapatkan, perubahan praktik guru di ruang kelas/satuan pendidikan (Guru) -->
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                Berdasarkan inspirasi yang didapatkan, perubahan praktik guru di ruang kelas/satuan pendidikan
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Ceritakan perubahan praktik nyata yang telah Anda wujudkan di ruang kelas atau satuan pendidikan dalam kaitannya dengan peningkatan kinerja (Diisi oleh Guru).
                            </p>
                        </div>
                        <Textarea
                            id="perubahan_praktik_kinerja_tahap3"
                            v-model="form.perubahan_praktik_kinerja_tahap3"
                            rows="4"
                            placeholder="Tuliskan bagaimana perubahan praktik di ruang kelas/satuan pendidikan telah memberikan dampak terhadap peningkatan kinerja..."
                            :disabled="!canEditRefleksiGuru"
                            class="bg-white dark:bg-slate-900 text-sm leading-relaxed"
                        />
                    </div>

                    <!-- Bagian B: Rencana guru dalam mengatasi tantangan agar bisa memastikan perubahan terjadi (Guru) -->
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-3 dark:border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                    Rencana guru dalam mengatasi tantangan agar bisa memastikan perubahan terjadi
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Langkah antisipasi atau tindakan terencana agar bisa memastikan perubahan terjadi secara berkelanjutan (Diisi oleh Guru).
                                </p>
                            </div>
                            <Button
                                v-if="canEditRefleksiGuru"
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="addArrayItem('rencana_mengatasi_tantangan')"
                                class="gap-1.5 text-xs border-purple-300 text-purple-700 dark:border-purple-800 dark:text-purple-300 hover:bg-purple-50 shadow-xs shrink-0 self-start sm:self-auto"
                            >
                                <Plus class="w-3.5 h-3.5" /> Tambah Kolom Rencana
                            </Button>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="(rencana, rIdx) in form.rencana_mengatasi_tantangan"
                                :key="rIdx"
                                class="flex items-center gap-3"
                            >
                                <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 flex items-center justify-center text-xs font-mono font-bold shrink-0">
                                    #{{ rIdx + 1 }}
                                </span>
                                <Input
                                    v-model="form.rencana_mengatasi_tantangan[rIdx]"
                                    placeholder="Tuliskan rencana aksi dalam mengatasi tantangan agar memastikan perubahan terjadi..."
                                    :disabled="!canEditRefleksiGuru"
                                    class="bg-white dark:bg-slate-900 text-sm"
                                />
                                <Button
                                    v-if="canEditRefleksiGuru && form.rencana_mengatasi_tantangan.length > 1"
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    @click="removeArrayItem('rencana_mengatasi_tantangan', rIdx)"
                                    class="h-9 w-9 text-rose-500 hover:bg-rose-50 hover:text-rose-700 shrink-0"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian C: Penilaian Observer & Catatan Peningkatan Kinerja -->
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 space-y-5">
                        <div class="border-b pb-3 dark:border-slate-800">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    Penilaian Observer: Bagaimana peningkatan kinerja yang telah ditunjukkan oleh guru dan rencana perbaikan berkelanjutannya?
                                </h3>
                                <Badge variant="outline" class="border-blue-200 bg-blue-50 text-blue-700 text-xs font-normal shrink-0">
                                    Diisi oleh Observer
                                </Badge>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Evaluasi hasil peningkatan kualitas kinerja guru dan respons peserta didik/rekan sejawat terhadap perubahan praktik:
                            </p>
                        </div>

                        <!-- Radio Card Options -->
                        <div class="flex flex-col gap-3">
                            <!-- Option 1: Pink / Rose -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.peningkatan_kinerja_tahap3" 
                                    :value="1" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-pink-50 peer-checked:border-pink-500 border-slate-200 group-hover:border-pink-300 dark:peer-checked:bg-pink-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-pink-500 flex items-center justify-center">
                                        <div v-if="form.peningkatan_kinerja_tahap3 === 1" class="w-2.5 h-2.5 rounded-full bg-pink-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-pink-700 dark:group-hover:text-pink-300 transition-colors">
                                            1. Ada Upaya Melakukan Peningkatan
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Terlihat ada ikhtiar dan upaya nyata dalam melakukan peningkatan mutu kinerja pembelajaran.
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 2: Kuning / Amber -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.peningkatan_kinerja_tahap3" 
                                    :value="2" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-amber-50 peer-checked:border-amber-500 border-slate-200 group-hover:border-amber-300 dark:peer-checked:bg-amber-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-amber-500 flex items-center justify-center">
                                        <div v-if="form.peningkatan_kinerja_tahap3 === 2" class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-amber-700 dark:group-hover:text-amber-300 transition-colors">
                                            2. Sejalan Ekspektasi Atasan
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Upaya melakukan peningkatan kinerja berjalan efektif dan sejalan dengan target/ekspektasi atasan.
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 3: Hijau / Emerald -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.peningkatan_kinerja_tahap3" 
                                    :value="3" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-emerald-50 peer-checked:border-emerald-500 border-slate-200 group-hover:border-emerald-300 dark:peer-checked:bg-emerald-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-emerald-500 flex items-center justify-center">
                                        <div v-if="form.peningkatan_kinerja_tahap3 === 3" class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">
                                            3. Respon Positif Peserta Didik
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Upaya peningkatan kinerja sangat berhasil dan mendapatkan antusiasme serta respon positif dari peserta didik.
                                        </p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Catatan Observer Bagian 3 -->
                        <div class="pt-3 border-t dark:border-slate-800 space-y-2">
                            <Label for="catatan_peningkatan_tahap3" class="text-slate-700 dark:text-slate-300 font-semibold text-sm">
                                Catatan Observer (Peningkatan Kinerja)
                            </Label>
                            <p class="text-xs text-slate-500">
                                Tuliskan catatan evaluasi, apresiasi, serta rekomendasi perbaikan berkelanjutan untuk guru di sini:
                            </p>
                            <Textarea
                                id="catatan_peningkatan_tahap3"
                                v-model="form.catatan_peningkatan_tahap3"
                                rows="3"
                                placeholder="Tuliskan catatan observer mengenai peningkatan kinerja guru..."
                                :disabled="!canEditObserver"
                                class="bg-white dark:bg-slate-900 text-sm leading-relaxed"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- ACTION FOOTER TOOLBAR -->
            <div class="sticky bottom-4 z-20 flex flex-col sm:flex-row justify-between items-stretch sm:items-center bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-3.5 sm:p-4 rounded-2xl border shadow-lg gap-3 sm:gap-4">
                <Link :href="isReview ? '/kinerja-approval' : '/kinerja'" class="w-full sm:w-auto">
                    <Button type="button" variant="outline" class="w-full sm:w-auto gap-2">
                        <ArrowLeft class="w-4 h-4" /> Kembali
                    </Button>
                </Link>
                
                <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 sm:gap-3 w-full sm:w-auto">
                    <!-- Wakasek/Admin Review Action (ACC Tahap 3) -->
                    <template v-if="isReview">
                        <template v-if="currentStatus === 'submitted'">
                            <Button 
                                type="button" 
                                variant="outline"
                                class="w-full sm:w-auto border-rose-300 text-rose-600 hover:bg-rose-50 hover:text-rose-700 shadow-sm gap-2 px-5" 
                                :disabled="form.processing"
                                @click="rejectDariReview"
                            >
                                <XCircle class="w-4 h-4" />
                                Tolak & Minta Revisi
                            </Button>
                            <Button 
                                type="button" 
                                class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white shadow-md gap-2 px-6" 
                                :disabled="form.processing"
                                @click="approveDariReview"
                            >
                                <ShieldCheck class="w-4 h-4" />
                                {{ form.processing ? 'Memproses...' : 'Setujui (ACC) Kinerja Tahap 3' }}
                            </Button>
                        </template>
                    </template>

                    <!-- Non-review Actions -->
                    <template v-else>
                        <!-- KASUS KETIKA TAHAP 2 SEDANG REVISI / BELUM APPROVED -> TERKUNCI -->
                        <template v-if="isTahap2SedangRevisi">
                            <span class="text-xs font-semibold text-amber-800 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 flex items-center justify-center gap-1.5 text-center">
                                <Lock class="w-4 h-4 text-amber-600 shrink-0" /> Formulir Tahap 3 Terkunci (Tahap 2 Belum Selesai / Sedang Direvisi)
                            </span>
                        </template>

                        <!-- KASUS REVISI TAHAP 3 -->
                        <template v-else-if="isTahap3Revisi">
                            <!-- Tombol Simpan Revisi Guru -->
                            <Button 
                                v-if="(userRole.isGuru || userRole.isAdmin) && isTahap3RevisiGuru" 
                                type="button" 
                                class="w-full sm:w-auto bg-rose-600 hover:bg-rose-700 text-white shadow-md gap-2 px-6" 
                                :disabled="form.processing"
                                @click="submitRefleksiGuru"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                {{ form.processing ? 'Menyimpan Perbaikan...' : (currentStatus === 'revisi_keduanya' ? 'Simpan Perbaikan & Ajukan ke Observer' : 'Simpan Perbaikan Refleksi & Ajukan ke Wakasek') }}
                            </Button>

                            <!-- Tombol Simpan Revisi Observer -->
                            <Button 
                                v-if="(userRole.isObserver || userRole.isAdmin) && isTahap3RevisiObserver" 
                                type="button" 
                                class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white shadow-md gap-2 px-6" 
                                :disabled="form.processing"
                                @click="submitPenilaianObserver"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                {{ form.processing ? 'Menyimpan Penilaian...' : 'Simpan Perbaikan Penilaian & Ajukan ke Wakasek' }}
                            </Button>

                            <!-- Pesan Menunggu jika revisi ditujukan ke pihak lain -->
                            <span v-if="userRole.isGuru && !isTahap3RevisiGuru" class="text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 text-center">
                                Menunggu observer menyelesaikan perbaikan penilaian pasca tindak lanjut.
                            </span>
                            <span v-else-if="userRole.isObserver && currentStatus === 'revisi_keduanya'" class="text-xs font-semibold text-rose-600 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200 text-center">
                                Menunggu guru menyelesaikan perbaikan refleksi sebelum Anda dapat menilai ulang.
                            </span>
                            <span v-else-if="userRole.isObserver && !isTahap3RevisiObserver" class="text-xs font-semibold text-rose-600 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200 text-center">
                                Menunggu guru menyelesaikan perbaikan refleksi pasca tindak lanjut.
                            </span>
                        </template>

                        <!-- ALUR REGULER (LANGKAH 1: GURU) -->
                        <template v-else-if="currentStepNumber === 1 && (userRole.isGuru || userRole.isAdmin)">
                            <Button 
                                type="button" 
                                variant="outline" 
                                class="w-full sm:w-auto border-slate-300 dark:border-slate-700 shadow-sm gap-2" 
                                :disabled="form.processing"
                                @click="simpanDraftGuru"
                            >
                                <Save class="w-4 h-4" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Draf' }}
                            </Button>
                            <Button 
                                type="button" 
                                class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white shadow-md gap-2 px-6" 
                                :disabled="form.processing"
                                @click="submitRefleksiGuru"
                            >
                                <Send class="w-4 h-4" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan & Ajukan ke Observer' }}
                            </Button>
                        </template>

                        <!-- ALUR REGULER (LANGKAH 2: OBSERVER) -->
                        <template v-else-if="currentStepNumber === 2 && (userRole.isObserver || userRole.isAdmin)">
                            <Button 
                                type="button" 
                                class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white shadow-md gap-2 px-6" 
                                :disabled="form.processing"
                                @click="submitPenilaianObserver"
                            >
                                <UserCheck class="w-4 h-4" />
                                {{ form.processing ? 'Mengirim Penilaian...' : 'Simpan Penilaian & Ajukan ke Wakasek' }}
                            </Button>
                        </template>

                        <!-- Status Info / Readonly -->
                        <template v-else>
                            <div v-if="currentStatus === 'approved'" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
                                <span class="text-xs font-semibold text-emerald-600 flex items-center justify-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 text-center">
                                    <CheckCircle2 class="w-4 h-4" /> Tahap 3 Telah Disetujui (ACC)
                                </span>
                                <Link :href="`/laporan-kinerja-tahunan`" class="w-full sm:w-auto">
                                    <Button size="sm" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white text-xs gap-1.5 shadow-xs">
                                        <Award class="w-3.5 h-3.5" /> Buka Laporan Kinerja Tahunan
                                    </Button>
                                </Link>
                            </div>
                            <span v-else-if="currentStatus === 'submitted'" class="text-xs font-semibold text-purple-600 flex items-center justify-center gap-1.5 bg-purple-50 px-3 py-1.5 rounded-lg border border-purple-200 text-center">
                                <Clock class="w-4 h-4" /> Menunggu Verifikasi (ACC) Wakasek
                            </span>
                            <span v-else-if="currentStatus === 'menunggu_penilaian'" class="text-xs font-semibold text-amber-600 flex items-center justify-center gap-1.5 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 text-center">
                                <Clock class="w-4 h-4" /> Menunggu Observer Mengisi Penilaian
                            </span>
                        </template>
                    </template>
                </div>
            </div>
        </form>
    </div>
</template>
