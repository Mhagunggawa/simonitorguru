<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { 
    Plus, 
    Trash2, 
    ArrowRight, 
    ArrowLeft, 
    CheckCircle2, 
    Clock, 
    AlertCircle, 
    ShieldCheck, 
    UserCheck, 
    BookOpen, 
    Sparkles, 
    Target, 
    Calendar, 
    HeartHandshake, 
    Send,
    Award,
    XCircle,
    Lock,
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
    return arr;
};

// Initialize fixed 2-item arrays for categories
const getFixedTwoItems = (arr: any) => {
    return [
        arr?.[0] || '',
        arr?.[1] || ''
    ];
};

// Form state
const form = useForm({
    tempat_tahap2: props.kinerja?.tempat_tahap2 || '',
    tanggal_tahap2: props.kinerja?.tanggal_tahap2 || '',
    waktu_tahap2: props.kinerja?.waktu_tahap2 || '',
    refleksi_rgtk: props.kinerja?.refleksi_rgtk || '',
    refleksi_lain: props.kinerja?.refleksi_lain || '',
    tujuan_tindak_lanjut: getFixedTwoItems(props.kinerja?.tujuan_tindak_lanjut),
    upaya_tindak_lanjut: getFixedTwoItems(props.kinerja?.upaya_tindak_lanjut),
    kapan_tindak_lanjut: getFixedTwoItems(props.kinerja?.kapan_tindak_lanjut),
    kebutuhan_dukungan: props.kinerja?.kebutuhan_dukungan || '',
    pengembangan_kompetensi: ensureArray(props.kinerja?.pengembangan_kompetensi),
    tugas_tambahan: ensureArray(props.kinerja?.tugas_tambahan),
    catatan_kepsek_tahap2: props.kinerja?.catatan_kepsek_tahap2 || '',
    kesadaran_refleksi: props.kinerja?.kesadaran_refleksi ?? null,
});

// Role & Permission Computations
const userRole = computed(() => {
    const isGuru = props.userRoleInfo?.isGuru ?? false;
    const isObserver = props.userRoleInfo?.isObserver ?? false;
    const isAdmin = props.userRoleInfo?.isAdmin ?? false;
    return { isGuru, isObserver, isAdmin };
});

const currentStatus = computed(() => {
    return props.kinerja?.status_tahap2 || 'draft_guru';
});

// Stepper index: 1 (Refleksi Guru), 2 (Penilaian Observer), 3 (Verifikasi Wakasek), 4 (Approved/Selesai)
const currentStepNumber = computed(() => {
    if (currentStatus.value === 'approved') return 4;
    if (currentStatus.value === 'submitted') return 3;
    if (currentStatus.value === 'menunggu_penilaian') return 2;
    if (currentStatus.value === 'revisi_observer') return 2;
    return 1; // draft_guru, pending, revisi_guru, revisi_keduanya
});

const isTahap2Revisi = computed(() => {
    return ['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(currentStatus.value) ||
           (props.kinerja?.revisi_tahap === 2 && Boolean(props.kinerja?.revisi_target));
});

const isTahap2RevisiGuru = computed(() => {
    return ['revisi_guru', 'revisi_keduanya'].includes(currentStatus.value) ||
           (props.kinerja?.revisi_tahap === 2 && ['guru', 'keduanya'].includes(props.kinerja?.revisi_target || ''));
});

const isTahap2RevisiObserver = computed(() => {
    return currentStatus.value === 'revisi_observer' ||
           (props.kinerja?.revisi_tahap === 2 && props.kinerja?.revisi_target === 'observer');
});

const isTahap1SedangRevisi = computed(() => {
    return props.kinerja?.revisi_tahap === 1 || 
           ['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(props.kinerja?.status_tahap1) ||
           props.kinerja?.status_tahap1 !== 'approved';
});

// Editable Permissions
// Guru HANYA dapat mengedit Refleksi Tindak Lanjut (Refleksi, Tujuan, Upaya, Kapan, Kebutuhan Dukungan)
const canEditRefleksiGuru = computed(() => {
    if (props.isReview || isTahap1SedangRevisi.value || currentStatus.value === 'approved' || currentStatus.value === 'submitted') return false;
    
    // Saat revisi Tahap 2: HANYA jika revisi ditujukan ke guru/keduanya dan user adalah Guru atau Admin
    if (isTahap2Revisi.value) {
        return (userRole.value.isGuru || userRole.value.isAdmin) && isTahap2RevisiGuru.value;
    }
    
    // Alur reguler: Guru mengisi di Step 1 (draft_guru / pending)
    if ((userRole.value.isGuru || userRole.value.isAdmin) && (currentStatus.value === 'draft_guru' || currentStatus.value === 'pending')) {
        return true;
    }
    
    return false;
});

// Observer HANYA dapat mengedit Penilaian & Diskusi (Jadwal, Pengembangan Kompetensi, Tugas Tambahan, Catatan, Kesadaran Refleksi)
const canEditObserver = computed(() => {
    if (props.isReview || isTahap1SedangRevisi.value || currentStatus.value === 'approved' || currentStatus.value === 'submitted') return false;
    
    // Saat status revisi_keduanya: Observer BELUM dapat mengedit (harus menunggu Guru selesai merevisi)
    if (currentStatus.value === 'revisi_keduanya') return false;

    // Saat revisi Tahap 2: HANYA jika revisi ditujukan ke observer dan user adalah Observer atau Admin
    if (isTahap2Revisi.value) {
        return (userRole.value.isObserver || userRole.value.isAdmin) && isTahap2RevisiObserver.value;
    }
    
    // Alur reguler: Observer mengisi saat Langkah 2 (menunggu_penilaian)
    if ((userRole.value.isObserver || userRole.value.isAdmin) && currentStatus.value === 'menunggu_penilaian') {
        return true;
    }
    
    return false;
});

// Helper for dynamic rows in observer section
const addRow = (key: 'pengembangan_kompetensi' | 'tugas_tambahan') => {
    form[key].push('');
};

const removeRow = (key: 'pengembangan_kompetensi' | 'tugas_tambahan', index: number) => {
    if (form[key].length > 1) {
        form[key].splice(index, 1);
    }
};

// Submissions
const submitRefleksiGuru = () => {
    // Validasi Refleksi Kategori 1 & 2
    if (!form.refleksi_rgtk?.trim() && !form.refleksi_lain?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Refleksi Masih Kosong',
            text: 'Silakan isi setidaknya salah satu refleksi (Pilihan Belajar di RGTK atau Pilihan Belajar Lainnya).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Tujuan Tindak Lanjut (2 Kolom)
    if (!form.tujuan_tindak_lanjut[0]?.trim() || !form.tujuan_tindak_lanjut[1]?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Tujuan Tindak Lanjut Belum Lengkap',
            text: 'Harap lengkapi Tujuan Tindak Lanjut untuk Kategori 1 (RGTK) dan Kategori 2 (Pilihan Lainnya).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Upaya Tindak Lanjut (2 Kolom)
    if (!form.upaya_tindak_lanjut[0]?.trim() || !form.upaya_tindak_lanjut[1]?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Upaya Tindak Lanjut Belum Lengkap',
            text: 'Harap lengkapi Upaya Tindak Lanjut untuk Kategori 1 (RGTK) dan Kategori 2 (Pilihan Lainnya).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Kapan Tindak Lanjut (2 Kolom)
    if (!form.kapan_tindak_lanjut[0]?.trim() || !form.kapan_tindak_lanjut[1]?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Waktu Pelaksanaan Belum Lengkap',
            text: 'Harap lengkapi Waktu Pelaksanaan (Kapan) untuk Kategori 1 (RGTK) dan Kategori 2 (Pilihan Lainnya).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Kebutuhan Dukungan
    if (!form.kebutuhan_dukungan?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Kebutuhan Dukungan Belum Diisi',
            text: 'Silakan jelaskan kebutuhan dukungan (apa atau siapa) yang Anda butuhkan untuk tindak lanjut.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    Swal.fire({
        title: 'Kirim Refleksi ke Observer?',
        text: 'Setelah dikirim, observer akan menerima notifikasi email untuk melakukan penilaian dan diskusi tindak lanjut.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Kirim Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                action: 'simpan_refleksi_guru'
            })).post(`/kinerja/${props.guru.id}/tahap-2`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Refleksi Berhasil Diajukan!',
                        text: 'Refleksi tindak lanjut telah tersimpan dan email pemberitahuan telah dikirimkan ke observer untuk melakukan penilaian.',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

const submitPenilaianObserver = () => {
    // Validasi Identitas & Jadwal (wajib diisi observer)
    if (!form.tempat_tahap2?.trim() || !form.tanggal_tahap2 || !form.waktu_tahap2) {
        Swal.fire({
            icon: 'warning',
            title: 'Jadwal Pelaksanaan Belum Lengkap',
            text: 'Silakan isi Tempat, Hari/Tanggal, dan Waktu pelaksanaan diskusi tindak lanjut terlebih dahulu.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Validasi Kesadaran Refleksi (1, 2, 3)
    if (!form.kesadaran_refleksi) {
        Swal.fire({
            icon: 'warning',
            title: 'Penilaian Refleksi Belum Dipilih',
            text: 'Silakan pilih salah satu opsi penilaian kesadaran guru ketika berefleksi (1, 2, atau 3).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    Swal.fire({
        title: 'Kirim Penilaian ke Wakasek?',
        text: 'Setelah dikirim, wakasek kurikulum akan menerima notifikasi email untuk meninjau dan memberikan persetujuan (ACC).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Kirim ke Wakasek',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                action: 'simpan_penilaian_tahap2'
            })).post(`/kinerja/${props.guru.id}/tahap-2`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Penilaian Berhasil Dikirim!',
                        text: 'Penilaian tindak lanjut berhasil disimpan dan email notifikasi telah dikirimkan ke Wakasek untuk persetujuan (ACC).',
                        confirmButtonColor: '#4f46e5'
                    });
                }
            });
        }
    });
};

const approveDariReview = () => {
    Swal.fire({
        title: 'Setujui (ACC) Kinerja Tahap 2?',
        text: 'Apakah Anda yakin ingin menyetujui formulir tindak lanjut ini? Guru yang diobservasi akan mendapatkan notifikasi email bahwa Tahap 2 telah selesai.',
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
                        text: 'Formulir tindak lanjut (Tahap 2) telah disetujui dan email telah dikirimkan kepada guru.',
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
                    Silakan tentukan pihak yang diminta untuk merevisi formulir Tahap 2 dan sertakan catatan perbaikan:
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

            return { revisi_target: target, catatan_revisi: catatan.trim(), tahap: 2 };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            router.post(`/kinerja-approval/${props.kinerja.id}/reject`, result.value, {
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

const submitRevisiGuruTahap2 = () => {
    if (!form.refleksi_rgtk?.trim() && !form.refleksi_lain?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Refleksi Masih Kosong',
            text: 'Silakan isi setidaknya salah satu refleksi (Pilihan Belajar di RGTK atau Pilihan Belajar Lainnya).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    if (!form.tujuan_tindak_lanjut[0]?.trim() || !form.tujuan_tindak_lanjut[1]?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Tujuan Tindak Lanjut Belum Lengkap',
            text: 'Harap lengkapi Tujuan Tindak Lanjut untuk Kategori 1 (RGTK) dan Kategori 2 (Pilihan Lainnya).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    if (!form.upaya_tindak_lanjut[0]?.trim() || !form.upaya_tindak_lanjut[1]?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Upaya Tindak Lanjut Belum Lengkap',
            text: 'Harap lengkapi Upaya Tindak Lanjut untuk Kategori 1 (RGTK) dan Kategori 2 (Pilihan Lainnya).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    if (!form.kapan_tindak_lanjut[0]?.trim() || !form.kapan_tindak_lanjut[1]?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Waktu Pelaksanaan Belum Lengkap',
            text: 'Harap lengkapi Waktu Pelaksanaan untuk Kategori 1 (RGTK) dan Kategori 2 (Pilihan Lainnya).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    if (!form.kebutuhan_dukungan?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Kebutuhan Dukungan Belum Diisi',
            text: 'Harap isi bagian Kebutuhan Dukungan.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    const isKeduanya = currentStatus.value === 'revisi_keduanya' || props.kinerja?.revisi_target === 'keduanya';

    Swal.fire({
        title: 'Konfirmasi Perbaikan Refleksi',
        text: isKeduanya
            ? 'Apakah refleksi tindak lanjut sudah diperbaiki? Formulir akan diajukan kembali ke Observer untuk dinilai ulang.'
            : 'Apakah refleksi tindak lanjut sudah diperbaiki? Formulir akan diajukan kembali ke Wakasek untuk persetujuan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: isKeduanya ? 'Ya, Ajukan ke Observer' : 'Ya, Ajukan ke Wakasek',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                action: 'simpan_revisi_guru_tahap2'
            })).post(`/kinerja/${props.guru.id}/tahap-2`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Refleksi Berhasil Diajukan',
                        text: isKeduanya
                            ? 'Perbaikan refleksi tindak lanjut telah diajukan kembali ke Observer untuk dinilai ulang.'
                            : 'Perbaikan refleksi tindak lanjut telah diajukan kembali ke Wakasek untuk persetujuan (ACC).',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

const submitRevisiObserverTahap2 = () => {
    if (!form.tempat_tahap2?.trim() || !form.tanggal_tahap2 || !form.waktu_tahap2?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Jadwal Belum Lengkap',
            text: 'Harap lengkapi Tempat, Tanggal, dan Waktu observasi tindak lanjut.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    if (!form.kesadaran_refleksi) {
        Swal.fire({
            icon: 'warning',
            title: 'Penilaian Belum Dipilih',
            text: 'Harap pilih penilaian Kesadaran Refleksi (1, 2, atau 3).',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    Swal.fire({
        title: 'Kirim Perbaikan Penilaian?',
        text: 'Perbaikan penilaian tindak lanjut akan diajukan langsung kembali ke Wakasek untuk persetujuan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Ajukan ke Wakasek',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                action: 'simpan_revisi_observer_tahap2'
            })).post(`/kinerja/${props.guru.id}/tahap-2`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Penilaian Berhasil Diajukan',
                        text: 'Perbaikan penilaian tindak lanjut telah diajukan kembali ke Wakasek untuk persetujuan (ACC).',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head :title="`Tindak Lanjut Kinerja (Tahap 2) - ${guru.name}`" />

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
                    <CheckCircle2 class="w-3.5 h-3.5 mr-1 text-emerald-600" /> Tahap 2 Selesai & Disetujui
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
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-950 rounded-xl text-indigo-600 shrink-0">
                        <Target class="w-5 h-5 md:w-7 md:h-7" />
                    </div>
                    <span>Tindak Lanjut Observasi Kinerja Guru</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 text-xs md:text-sm">
                    Formulir Tindak Lanjut (Tahap 2) untuk <span class="font-semibold text-slate-800 dark:text-slate-200">{{ guru.name }}</span> &bull; Periode Januari - Desember {{ currentYear }}
                </p>
            </div>
        </div>

        <!-- Rejection / Revision Alert Banner if any -->
        <div v-if="isTahap2Revisi && kinerja?.catatan_revisi" class="p-4 rounded-2xl bg-rose-50/80 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 flex items-start gap-3.5 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <AlertCircle class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="font-bold text-sm text-rose-900 dark:text-rose-200">
                        Formulir Tahap 2 (Tindak Lanjut & Refleksi) Memerlukan Revisi
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

        <!-- Banner jika Tahap 1 sedang direvisi -> Tahap 2 dinonaktifkan (read-only / terkunci) -->
        <div v-if="isTahap1SedangRevisi" class="p-4 rounded-2xl bg-amber-50/90 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 flex items-start gap-3.5 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                <Lock class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="font-bold text-sm text-amber-900 dark:text-amber-200">
                        Formulir Tahap 2 Dikunci (Tahap 1 Sedang Memerlukan Revisi)
                    </h4>
                    <Badge variant="outline" class="bg-amber-100 text-amber-800 border-amber-300 text-[10px] font-bold uppercase tracking-wider">
                        Tahap 1 Belum Selesai / Disetujui
                    </Badge>
                </div>
                <p class="text-xs text-amber-800 dark:text-amber-300 mt-1.5 leading-relaxed font-medium">
                    Formulir Observasi Praktik (Tahap 1) saat ini sedang dalam proses perbaikan/revisi. Seluruh isian Formulir Tahap 2 ini dinonaktifkan (di-disable) total dan tidak dapat diubah sebelum perbaikan Tahap 1 diselesaikan serta disetujui (ACC) kembali oleh Wakasek.
                </p>
                <div class="mt-3">
                    <Link :href="`/kinerja/${guru.id}/tahap-1`">
                        <Button size="sm" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs gap-1.5 shadow-xs">
                            Buka Formulir Tahap 1 yang Sedang Direvisi <ArrowRight class="w-3.5 h-3.5" />
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stepper Progress Bar -->
        <Card class="border shadow-sm bg-gradient-to-r from-slate-50 to-indigo-50/30 dark:from-slate-900 dark:to-indigo-950/20">
            <CardContent class="p-4 md:p-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Step 1: Refleksi Guru -->
                    <div class="flex items-center gap-3 p-3 rounded-xl border transition-all"
                        :class="currentStepNumber >= 2 ? 'bg-emerald-50 border-emerald-300 text-emerald-900 dark:bg-emerald-950/30' : (currentStepNumber === 1 ? 'bg-white border-indigo-400 shadow-sm text-indigo-900 dark:bg-slate-900' : 'bg-slate-50/50 border-slate-200 text-slate-400 dark:bg-slate-900/50')">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shrink-0"
                            :class="currentStepNumber >= 2 ? 'bg-emerald-600 text-white' : (currentStepNumber === 1 ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-800')">
                            <CheckCircle2 v-if="currentStepNumber >= 2" class="w-4 h-4" />
                            <span v-else>1</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider">Langkah 1</p>
                            <p class="text-sm font-bold truncate">Refleksi Tindak Lanjut (Guru)</p>
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
                <h4 class="font-bold text-base">Tahap Refleksi Guru</h4>
                <p class="mt-0.5 leading-relaxed">
                    Guru mengisi refleksi pada 2 kategori pilihan belajar, menentukan tujuan tindak lanjut, upaya yang akan dilakukan, waktu pelaksanaan, serta kebutuhan dukungan. Setelah disimpan dan diajukan, Observer akan menerima email untuk melakukan penilaian.
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
                    Guru telah mengajukan refleksi tindak lanjut. Kini giliran Pengamat (Observer) untuk mengisi Jadwal Pelaksanaan, Catatan Pengembangan Kompetensi & Tugas Tambahan, serta menilai Kesadaran Refleksi Guru.
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
                    Observer telah menyelesaikan penilaian tindak lanjut. Formulir ini sedang menunggu review dan persetujuan (ACC) dari Wakasek Kurikulum.
                </p>
            </div>
        </div>

        <div v-else-if="currentStatus === 'approved'" class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/70 dark:bg-emerald-950/30 dark:border-emerald-800 flex items-start gap-3">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg text-emerald-700 dark:text-emerald-300 shrink-0">
                <Award class="w-5 h-5" />
            </div>
            <div class="text-sm text-emerald-950 dark:text-emerald-200">
                <h4 class="font-bold text-base">Formulir Tahap 2 Telah Disetujui (ACC)</h4>
                <p class="mt-0.5 leading-relaxed">
                    Tahap 2 telah resmi disetujui oleh Wakasek Kurikulum dan proses tindak lanjut observasi kinerja guru telah selesai.
                </p>
            </div>
        </div>

        <form @submit.prevent="" class="space-y-8">
            
            <!-- 1. IDENTITAS & JADWAL TINDAK LANJUT -->
            <Card class="border-t-4 border-t-blue-500 shadow-md">
                <CardHeader class="bg-blue-50/50 dark:bg-blue-900/10 border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Calendar class="w-5 h-5 text-blue-600" />
                            <CardTitle class="text-lg md:text-xl text-blue-700 dark:text-blue-400">1. Identitas & Jadwal Diskusi Tindak Lanjut</CardTitle>
                        </div>
                        <Badge variant="outline" class="border-blue-200 bg-blue-50 text-blue-700 text-xs font-normal">
                            Diisi oleh Observer
                        </Badge>
                    </div>
                    <CardDescription>
                        Jadwal pelaksanaan diskusi tindak lanjut hasil observasi kelas antara guru dan observer.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">Nama Guru yang Diobservasi</Label>
                        <div class="flex h-10 w-full items-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-100">
                            {{ guru.name }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">Nama Pengamat (Observer)</Label>
                        <div class="flex h-10 w-full items-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-100">
                            {{ kinerja?.observer?.name || guru.observer?.name || $page.props.auth.user.name }}
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
                            v-model="form.tempat_tahap2" 
                            placeholder="Contoh: Ruang Guru / Ruang Kepala Sekolah" 
                            :disabled="!canEditObserver"
                            :class="!canEditObserver ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                            required 
                        />
                    </div>
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">
                            Hari / Tanggal Diskusi <span v-if="canEditObserver" class="text-red-500">*</span>
                        </Label>
                        <Input 
                            type="date" 
                            v-model="form.tanggal_tahap2" 
                            :disabled="!canEditObserver"
                            :class="!canEditObserver ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                            required 
                        />
                    </div>
                    <div class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-semibold">
                            Waktu Pelaksanaan <span v-if="canEditObserver" class="text-red-500">*</span>
                        </Label>
                        <Input 
                            type="time" 
                            v-model="form.waktu_tahap2" 
                            :disabled="!canEditObserver"
                            :class="!canEditObserver ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                            required 
                        />
                    </div>
                    
                    <div class="col-span-1 md:col-span-2 space-y-2 mt-2 p-4 bg-blue-50/40 dark:bg-blue-950/20 rounded-xl border border-blue-100 dark:border-blue-900 text-xs md:text-sm text-slate-700 dark:text-slate-300">
                        <p class="font-bold text-blue-900 dark:text-blue-300 mb-1">Tujuan Tindak Lanjut:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Guru melakukan refleksi sebagai tanggapan terhadap catatan dan hasil penilaian observasi kinerja oleh pengamat / kepala sekolah (Formulir Tahap 1).</li>
                            <li>Guru dan pengamat menyepakati tindak lanjut serta dukungan yang dibutuhkan untuk meningkatkan praktik pembelajaran.</li>
                        </ul>
                    </div>
                </CardContent>
            </Card>

            <!-- 2. REFLEKSI GURU & RENCANA TINDAK LANJUT (2 KATEGORI) -->
            <Card class="border-t-4 border-t-emerald-500 shadow-md">
                <CardHeader class="bg-emerald-50/50 dark:bg-emerald-900/10 border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <BookOpen class="w-5 h-5 text-emerald-600" />
                            <CardTitle class="text-lg md:text-xl text-emerald-700 dark:text-emerald-400">2. Refleksi Guru & Rencana Tindak Lanjut</CardTitle>
                        </div>
                        <Badge variant="outline" class="border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-normal">
                            Diisi oleh Guru
                        </Badge>
                    </div>
                    <CardDescription>
                        Guru mengisi refleksi pembelajaran dan menetapkan tujuan, upaya, serta periode tindak lanjut untuk 2 kategori.
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-6 space-y-8">

                    <!-- KATEGORI 1: RGTK -->
                    <div class="p-5 rounded-2xl border-2 border-emerald-100 dark:border-emerald-950 bg-emerald-50/20 dark:bg-emerald-950/10 space-y-5">
                        <div class="flex items-center gap-2 border-b border-emerald-200/60 pb-3">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shrink-0">1</span>
                            <h3 class="font-bold text-base md:text-lg text-emerald-950 dark:text-emerald-200">
                                Kategori 1: Pilihan Belajar di RGTK (Platform Merdeka Mengajar / PMM)
                            </h3>
                        </div>

                        <!-- Refleksi RGTK Textarea -->
                        <div class="space-y-2">
                            <Label class="text-slate-800 dark:text-slate-200 font-semibold text-sm">
                                Refleksi Guru terhadap Pembelajaran di RGTK
                            </Label>
                            <Textarea 
                                v-model="form.refleksi_rgtk" 
                                placeholder="Tuliskan refleksi hasil pembelajaran Anda di RGTK (misal: apa tantangan yang dihadapi dan apa yang dipelajari)..." 
                                class="min-h-[90px]" 
                                :disabled="!canEditRefleksiGuru"
                                :class="!canEditRefleksiGuru ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                            />
                        </div>

                        <!-- 3 Fields: Tujuan, Upaya, Kapan (Index 0) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                            <!-- Tujuan Index 0 -->
                            <div class="space-y-2 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                                <Label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 uppercase tracking-wide">
                                    <Target class="w-3.5 h-3.5 text-emerald-600" />
                                    Tujuan Tindak Lanjut (Kategori 1)
                                </Label>
                                <Input 
                                    v-model="form.tujuan_tindak_lanjut[0]" 
                                    placeholder="Contoh: Menguasai diferensiasi pembelajaran..." 
                                    :disabled="!canEditRefleksiGuru"
                                    :class="!canEditRefleksiGuru ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                                />
                            </div>

                            <!-- Upaya Index 0 -->
                            <div class="space-y-2 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                                <Label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 uppercase tracking-wide">
                                    <Sparkles class="w-3.5 h-3.5 text-emerald-600" />
                                    Upaya Tindak Lanjut (Kategori 1)
                                </Label>
                                <Input 
                                    v-model="form.upaya_tindak_lanjut[0]" 
                                    placeholder="Contoh: Menyelesaikan topik di PMM dan praktik..." 
                                    :disabled="!canEditRefleksiGuru"
                                    :class="!canEditRefleksiGuru ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                                />
                            </div>

                            <!-- Kapan Index 0 -->
                            <div class="space-y-2 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                                <Label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 uppercase tracking-wide">
                                    <Clock class="w-3.5 h-3.5 text-emerald-600" />
                                    Kapan / Periode (Kategori 1)
                                </Label>
                                <Input 
                                    v-model="form.kapan_tindak_lanjut[0]" 
                                    placeholder="Contoh: Bulan Agustus - September" 
                                    :disabled="!canEditRefleksiGuru"
                                    :class="!canEditRefleksiGuru ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- KATEGORI 2: PILIHAN BELAJAR LAINNYA -->
                    <div class="p-5 rounded-2xl border-2 border-indigo-100 dark:border-indigo-950 bg-indigo-50/20 dark:bg-indigo-950/10 space-y-5">
                        <div class="flex items-center gap-2 border-b border-indigo-200/60 pb-3">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0">2</span>
                            <h3 class="font-bold text-base md:text-lg text-indigo-950 dark:text-indigo-200">
                                Kategori 2: Pilihan Belajar Lainnya (Komunitas Praktisi, MGMP, Webinar, dll.)
                            </h3>
                        </div>

                        <!-- Refleksi Lain Textarea -->
                        <div class="space-y-2">
                            <Label class="text-slate-800 dark:text-slate-200 font-semibold text-sm">
                                Refleksi Guru terhadap Pembelajaran Lainnya
                            </Label>
                            <Textarea 
                                v-model="form.refleksi_lain" 
                                placeholder="Tuliskan refleksi kegiatan belajar mandiri, diskusi rekan sejawat, atau webinar yang Anda ikuti..." 
                                class="min-h-[90px]" 
                                :disabled="!canEditRefleksiGuru"
                                :class="!canEditRefleksiGuru ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                            />
                        </div>

                        <!-- 3 Fields: Tujuan, Upaya, Kapan (Index 1) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                            <!-- Tujuan Index 1 -->
                            <div class="space-y-2 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                                <Label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 uppercase tracking-wide">
                                    <Target class="w-3.5 h-3.5 text-indigo-600" />
                                    Tujuan Tindak Lanjut (Kategori 2)
                                </Label>
                                <Input 
                                    v-model="form.tujuan_tindak_lanjut[1]" 
                                    placeholder="Contoh: Berbagi praktik baik di MGMP..." 
                                    :disabled="!canEditRefleksiGuru"
                                    :class="!canEditRefleksiGuru ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                                />
                            </div>

                            <!-- Upaya Index 1 -->
                            <div class="space-y-2 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                                <Label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 uppercase tracking-wide">
                                    <Sparkles class="w-3.5 h-3.5 text-indigo-600" />
                                    Upaya Tindak Lanjut (Kategori 2)
                                </Label>
                                <Input 
                                    v-model="form.upaya_tindak_lanjut[1]" 
                                    placeholder="Contoh: Mengikuti sesi diskusi mingguan..." 
                                    :disabled="!canEditRefleksiGuru"
                                    :class="!canEditRefleksiGuru ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                                />
                            </div>

                            <!-- Kapan Index 1 -->
                            <div class="space-y-2 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                                <Label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 uppercase tracking-wide">
                                    <Clock class="w-3.5 h-3.5 text-indigo-600" />
                                    Kapan / Periode (Kategori 2)
                                </Label>
                                <Input 
                                    v-model="form.kapan_tindak_lanjut[1]" 
                                    placeholder="Contoh: Bulan Oktober - November" 
                                    :disabled="!canEditRefleksiGuru"
                                    :class="!canEditRefleksiGuru ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- KEBUTUHAN DUKUNGAN -->
                    <div class="space-y-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <Label class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <HeartHandshake class="w-4 h-4 text-emerald-600" />
                            Kebutuhan Dukungan (Apa / Siapa yang dibutuhkan?) <span v-if="canEditRefleksiGuru" class="text-red-500">*</span>
                        </Label>
                        <p class="text-xs text-slate-500">
                            Jelaskan bentuk dukungan atau pihak yang Anda butuhkan (misal: Kepala Sekolah, Rekan Sejawat, Pengawas, atau Sarana IT/Alat Peraga).
                        </p>
                        <Textarea 
                            v-model="form.kebutuhan_dukungan" 
                            placeholder="Tuliskan bentuk dukungan dan siapa yang Anda butuhkan..." 
                            class="min-h-[90px]" 
                            :disabled="!canEditRefleksiGuru"
                            :class="!canEditRefleksiGuru ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                            required 
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- 3. CATATAN KEPALA SEKOLAH / OBSERVER & PENILAIAN REFLEKSI -->
            <Card class="border-t-4 border-t-purple-500 shadow-md">
                <CardHeader class="bg-purple-50/50 dark:bg-purple-900/10 border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <ShieldCheck class="w-5 h-5 text-purple-600" />
                            <CardTitle class="text-lg md:text-xl text-purple-700 dark:text-purple-400">3. Catatan Kepala Sekolah / Observer</CardTitle>
                        </div>
                        <Badge variant="outline" class="border-purple-200 bg-purple-50 text-purple-700 text-xs font-normal">
                            Diisi oleh Observer
                        </Badge>
                    </div>
                    <CardDescription>
                        Catatan umpan balik pembinaan dan penilaian kesadaran guru dalam merefleksikan praktik pembelajaran.
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-6 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Pengembangan Kompetensi -->
                        <div class="space-y-3">
                            <Label class="flex items-center justify-between font-semibold text-slate-800 dark:text-slate-200 text-sm">
                                <span>1. Pengembangan kompetensi apa yang sudah dilakukan guru?</span>
                                <Button 
                                    v-if="canEditObserver" 
                                    type="button" 
                                    variant="ghost" 
                                    size="sm" 
                                    @click="addRow('pengembangan_kompetensi')" 
                                    class="h-7 px-2 text-xs text-purple-600 hover:text-purple-700 hover:bg-purple-50"
                                >
                                    <Plus class="w-3.5 h-3.5 mr-1" /> Tambah Respon
                                </Button>
                            </Label>
                            <div v-for="(_, idx) in form.pengembangan_kompetensi" :key="'kompetensi-'+idx" class="flex gap-2">
                                <Input 
                                    v-model="form.pengembangan_kompetensi[idx]" 
                                    placeholder="Keterangan pengembangan kompetensi..." 
                                    :disabled="!canEditObserver"
                                    :class="!canEditObserver ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                                />
                                <Button 
                                    v-if="canEditObserver && form.pengembangan_kompetensi.length > 1" 
                                    type="button" 
                                    variant="ghost" 
                                    size="icon" 
                                    @click="removeRow('pengembangan_kompetensi', idx)" 
                                    class="shrink-0 text-red-400 hover:text-red-600 hover:bg-red-50"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Tugas Tambahan -->
                        <div class="space-y-3">
                            <Label class="flex items-center justify-between font-semibold text-slate-800 dark:text-slate-200 text-sm">
                                <span>2. Tugas tambahan apa yang di SK-kan & dilaksanakan maksimal?</span>
                                <Button 
                                    v-if="canEditObserver" 
                                    type="button" 
                                    variant="ghost" 
                                    size="sm" 
                                    @click="addRow('tugas_tambahan')" 
                                    class="h-7 px-2 text-xs text-purple-600 hover:text-purple-700 hover:bg-purple-50"
                                >
                                    <Plus class="w-3.5 h-3.5 mr-1" /> Tambah Respon
                                </Button>
                            </Label>
                            <div v-for="(_, idx) in form.tugas_tambahan" :key="'tugas-'+idx" class="flex gap-2">
                                <Input 
                                    v-model="form.tugas_tambahan[idx]" 
                                    placeholder="Keterangan tugas tambahan..." 
                                    :disabled="!canEditObserver"
                                    :class="!canEditObserver ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                                />
                                <Button 
                                    v-if="canEditObserver && form.tugas_tambahan.length > 1" 
                                    type="button" 
                                    variant="ghost" 
                                    size="icon" 
                                    @click="removeRow('tugas_tambahan', idx)" 
                                    class="shrink-0 text-red-400 hover:text-red-600 hover:bg-red-50"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="space-y-2 pt-2">
                        <Label class="font-semibold text-slate-800 dark:text-slate-200">
                            Catatan Lainnya dari Observer / Kepala Sekolah
                        </Label>
                        <Textarea 
                            v-model="form.catatan_kepsek_tahap2" 
                            placeholder="Catatan tambahan hasil kesepakatan diskusi..." 
                            class="min-h-[80px]" 
                            :disabled="!canEditObserver"
                            :class="!canEditObserver ? 'bg-slate-50 cursor-not-allowed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300' : ''"
                        />
                    </div>

                    <!-- PENILAIAN KESADARAN REFLEKSI GURU (RADIO BUTTONS) -->
                    <div class="space-y-4 pt-6 border-t border-slate-200 dark:border-slate-800">
                        <div>
                            <Label class="text-base font-bold text-slate-900 dark:text-white block">
                                Bagaimana kesadaran guru ketika berefleksi? <span v-if="canEditObserver" class="text-red-500">*</span>
                            </Label>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Pilih salah satu tingkat kesadaran guru berdasarkan hasil pengamatan dan diskusi tindak lanjut.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3">
                            <!-- Option 1: Pink / Rose -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.kesadaran_refleksi" 
                                    :value="1" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-pink-50 peer-checked:border-pink-500 border-slate-200 group-hover:border-pink-300 dark:peer-checked:bg-pink-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-pink-500 flex items-center justify-center">
                                        <div v-if="form.kesadaran_refleksi === 1" class="w-2.5 h-2.5 rounded-full bg-pink-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-pink-700 dark:group-hover:text-pink-300 transition-colors">
                                            1. Sadar tantangan dan penyebabnya berada di luar kendali diri
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Guru cenderung menyalahkan faktor luar atau kondisi lingkungan tanpa melihat peran tindakannya sendiri.
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 2: Kuning / Amber -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.kesadaran_refleksi" 
                                    :value="2" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-amber-50 peer-checked:border-amber-500 border-slate-200 group-hover:border-amber-300 dark:peer-checked:bg-amber-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-amber-500 flex items-center justify-center">
                                        <div v-if="form.kesadaran_refleksi === 2" class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-amber-700 dark:group-hover:text-amber-300 transition-colors">
                                            2. Sadar tantangan dan penyebabnya berada di dalam kendali diri
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Guru menyadari bahwa metode atau penanganan yang dilakukan berdampak langsung pada tantangan pembelajaran.
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 3: Hijau / Emerald -->
                            <label class="flex items-center cursor-pointer group" :class="!canEditObserver ? 'cursor-default pointer-events-none' : ''">
                                <input 
                                    type="radio" 
                                    v-model="form.kesadaran_refleksi" 
                                    :value="3" 
                                    :disabled="!canEditObserver"
                                    class="peer sr-only" 
                                />
                                <div class="flex items-center p-4 w-full rounded-xl border-2 transition-all peer-checked:bg-emerald-50 peer-checked:border-emerald-500 border-slate-200 group-hover:border-emerald-300 dark:peer-checked:bg-emerald-950/30 dark:border-slate-800">
                                    <div class="w-5 h-5 rounded-full border-2 mr-4 flex-shrink-0 border-slate-300 peer-checked:border-emerald-500 flex items-center justify-center">
                                        <div v-if="form.kesadaran_refleksi === 3" class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-100 font-semibold group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">
                                            3. Sadar tantangan dan penyebabnya berada di dalam kendali diri serta dampaknya
                                        </span>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Guru memahami secara mendalam dampak tindakannya terhadap hasil belajar siswa dan aktif mencari solusi perbaikan.
                                        </p>
                                    </div>
                                </div>
                            </label>
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
                    <!-- Wakasek/Admin Review Action (Langkah 3: ACC Tahap 2) -->
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
                                {{ form.processing ? 'Memproses...' : 'Setujui (ACC) Kinerja Tahap 2' }}
                            </Button>
                        </template>
                    </template>

                    <!-- Non-review Actions -->
                    <template v-else>
                        <!-- KASUS KETIKA TAHAP 1 SEDANG REVISI / BELUM APPROVED -> SEMUA TOMBOL SIMPAN TAHAP 2 TIDAK DITAMPILKAN -->
                        <template v-if="isTahap1SedangRevisi">
                            <span class="text-xs font-semibold text-amber-800 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 flex items-center justify-center gap-1.5 text-center">
                                <Lock class="w-4 h-4 text-amber-600 shrink-0" /> Formulir Tahap 2 Terkunci (Tahap 1 Belum Selesai / Sedang Direvisi)
                            </span>
                        </template>

                        <!-- KASUS REVISI TAHAP 2 -->
                        <template v-else-if="isTahap2Revisi">
                            <!-- Tombol Simpan Revisi Guru (Refleksi Tindak Lanjut) -->
                            <Button 
                                v-if="(userRole.isGuru || userRole.isAdmin) && isTahap2RevisiGuru" 
                                type="button" 
                                class="w-full sm:w-auto bg-rose-600 hover:bg-rose-700 text-white shadow-md gap-2 px-6" 
                                :disabled="form.processing"
                                @click="submitRevisiGuruTahap2"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                {{ form.processing ? 'Menyimpan Perbaikan...' : (currentStatus === 'revisi_keduanya' ? 'Simpan Perbaikan & Ajukan ke Observer' : 'Simpan Perbaikan Refleksi & Ajukan ke Wakasek') }}
                            </Button>

                            <!-- Tombol Simpan Revisi Observer (Penilaian & Diskusi Tindak Lanjut) -->
                            <Button 
                                v-if="(userRole.isObserver || userRole.isAdmin) && isTahap2RevisiObserver" 
                                type="button" 
                                class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white shadow-md gap-2 px-6" 
                                :disabled="form.processing"
                                @click="submitRevisiObserverTahap2"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                {{ form.processing ? 'Menyimpan Perbaikan...' : 'Simpan Perbaikan Penilaian & Ajukan ke Wakasek' }}
                            </Button>

                            <!-- Pesan Menunggu jika revisi ditujukan ke pihak lain -->
                            <span v-if="userRole.isGuru && !isTahap2RevisiGuru" class="text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 text-center">
                                Menunggu observer menyelesaikan perbaikan penilaian tindak lanjut.
                            </span>
                            <span v-else-if="userRole.isObserver && currentStatus === 'revisi_keduanya'" class="text-xs font-semibold text-rose-600 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200 text-center">
                                Menunggu guru menyelesaikan perbaikan refleksi sebelum Anda dapat menilai ulang.
                            </span>
                            <span v-else-if="userRole.isObserver && !isTahap2RevisiObserver" class="text-xs font-semibold text-rose-600 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200 text-center">
                                Menunggu guru menyelesaikan perbaikan refleksi tindak lanjut.
                            </span>
                        </template>

                        <!-- ALUR REGULER (LANGKAH 1: GURU SUBMIT REFLEKSI) -->
                        <template v-else-if="userRole.isGuru && (currentStatus === 'draft_guru' || currentStatus === 'pending')">
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

                        <!-- ALUR REGULER (LANGKAH 2: OBSERVER SUBMIT PENILAIAN) -->
                        <template v-else-if="userRole.isObserver && currentStatus === 'menunggu_penilaian'">
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
                            <span v-if="currentStatus === 'approved'" class="text-xs font-semibold text-emerald-600 flex items-center justify-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 text-center">
                                <CheckCircle2 class="w-4 h-4" /> Tahap 2 Telah Disetujui (ACC)
                            </span>
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
