<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Plus, Trash2, Video, ArrowRight, ArrowLeft, CheckCircle2, Clock, AlertCircle, ShieldCheck, UserCheck, XCircle, Lock, Folder, ExternalLink } from "@lucide/vue";
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

// Helper to initialize paired behavior rows
const initFokuses = () => {
    if (!props.kinerja?.fokuses?.length) {
        return [{
            nama_fokus: '',
            perilaku_dianjurkan: [''],
            perilaku_dihindari: [''],
            penilaians: [null],
            catatan: ''
        }];
    }
    return props.kinerja.fokuses.map((f: any) => {
        const d = Array.isArray(f.perilaku_dianjurkan) && f.perilaku_dianjurkan.length ? f.perilaku_dianjurkan : [''];
        const h = Array.isArray(f.perilaku_dihindari) && f.perilaku_dihindari.length ? f.perilaku_dihindari : [''];
        const count = Math.max(d.length, h.length, 1);
        
        const dianjurkan = [...d];
        while (dianjurkan.length < count) dianjurkan.push('');

        const dihindari = [...h];
        while (dihindari.length < count) dihindari.push('');

        let penilaians: (number | null)[] = [];
        if (Array.isArray(f.penilaians) && f.penilaians.length) {
            penilaians = [...f.penilaians];
        }
        while (penilaians.length < count) {
            penilaians.push(f.penilaian ?? null);
        }

        return {
            nama_fokus: f.nama_fokus || '',
            perilaku_dianjurkan: dianjurkan,
            perilaku_dihindari: dihindari,
            penilaians: penilaians,
            catatan: f.catatan || ''
        };
    });
};

// Form state
const form = useForm({
    tempat: props.kinerja?.tempat || '',
    tanggal: props.kinerja?.tanggal || '',
    waktu: props.kinerja?.waktu || '',
    fokuses: initFokuses(),
    rekomendasi: props.kinerja?.rekomendasi || '',
    video_link: props.kinerja?.video_link || '',
});

// Role & Permission Checks
const userRole = computed(() => {
    const isGuru = props.userRoleInfo?.isGuru ?? false;
    const isObserver = props.userRoleInfo?.isObserver ?? false;
    const isAdmin = props.userRoleInfo?.isAdmin ?? false;
    return { isGuru, isObserver, isAdmin };
});

const currentStatus = computed(() => props.kinerja?.status_tahap1 || 'draft_guru');

// Step index: 1 (Rencana & Video Guru), 2 (Penilaian Observer), 3 (Verifikasi ACC), 4 (Approved)
const currentStepNumber = computed(() => {
    if (!props.kinerja || currentStatus.value === 'draft_guru') return 1;
    if (currentStatus.value === 'revisi_keduanya') return 1;
    if (currentStatus.value === 'revisi_guru') return 1;
    if (currentStatus.value === 'menunggu_penilaian') return 2;
    if (currentStatus.value === 'revisi_observer') return 2;
    if (currentStatus.value === 'pending') return 3;
    if (currentStatus.value === 'approved') return 4;
    return 1;
});

const isTahap2SedangRevisi = computed(() => {
    return props.kinerja?.revisi_tahap === 2 || 
           ['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(props.kinerja?.status_tahap2);
});

// Status Revisi Tahap 1
const isTahap1Revisi = computed(() => {
    return ['revisi_guru', 'revisi_observer', 'revisi_keduanya'].includes(currentStatus.value) ||
           (props.kinerja?.revisi_tahap === 1 && Boolean(props.kinerja?.revisi_target));
});

const isTahap1RevisiGuru = computed(() => {
    return ['revisi_guru', 'revisi_keduanya'].includes(currentStatus.value) ||
           (props.kinerja?.revisi_tahap === 1 && ['guru', 'keduanya'].includes(props.kinerja?.revisi_target || ''));
});

const isTahap1RevisiObserver = computed(() => {
    return currentStatus.value === 'revisi_observer' ||
           (props.kinerja?.revisi_tahap === 1 && props.kinerja?.revisi_target === 'observer');
});

// Editable Permissions
// Guru HANYA dapat mengedit Rencana Hasil Kerja (Fokus & Indikator Perilaku)
const canEditRencana = computed(() => {
    if (props.isReview || isTahap2SedangRevisi.value || currentStatus.value === 'approved') return false;
    
    // Saat revisi Tahap 1: HANYA jika revisi ditujukan ke guru/keduanya dan user adalah Guru atau Admin
    if (isTahap1Revisi.value) {
        return (userRole.value.isGuru || userRole.value.isAdmin) && isTahap1RevisiGuru.value;
    }
    
    // Alur reguler: Guru mengisi di Step 1 (draft_guru atau form baru)
    if ((userRole.value.isGuru || userRole.value.isAdmin) && (currentStatus.value === 'draft_guru' || !props.kinerja)) {
        return true;
    }
    
    return false;
});

// Guru mengisi Link Drive Video rekaman observasi bersamaan dengan Rencana Hasil Kerja di Langkah 1
const canEditVideo = computed(() => {
    return canEditRencana.value;
});

// Jadwal dan tempat pelaksanaan HANYA diisi oleh Observer saat observasi (Langkah 2) atau saat revisi observer
const canEditJadwal = computed(() => {
    if (props.isReview || isTahap2SedangRevisi.value || currentStatus.value === 'approved') return false;
    
    // Saat status revisi_keduanya: Observer BELUM dapat mengedit (harus menunggu Guru selesai merevisi)
    if (currentStatus.value === 'revisi_keduanya') return false;

    // Saat revisi Tahap 1: HANYA jika revisi ditujukan ke observer dan user adalah Observer atau Admin
    if (isTahap1Revisi.value) {
        return (userRole.value.isObserver || userRole.value.isAdmin) && isTahap1RevisiObserver.value;
    }
    
    // Alur reguler: Observer mengisi saat observasi kelas (Langkah 2)
    if ((userRole.value.isObserver || userRole.value.isAdmin) && currentStatus.value === 'menunggu_penilaian') {
        return true;
    }
    
    return false;
});

// Penilaian dan catatan HANYA diisi oleh Observer (Langkah 2) atau saat revisi observer
const canEditPenilaian = computed(() => {
    if (props.isReview || isTahap2SedangRevisi.value || currentStatus.value === 'approved') return false;
    
    // Saat status revisi_keduanya: Observer BELUM dapat mengedit (harus menunggu Guru selesai merevisi)
    if (currentStatus.value === 'revisi_keduanya') return false;

    // Saat revisi Tahap 1: HANYA jika revisi ditujukan ke observer dan user adalah Observer atau Admin
    if (isTahap1Revisi.value) {
        return (userRole.value.isObserver || userRole.value.isAdmin) && isTahap1RevisiObserver.value;
    }
    
    // Alur reguler: Observer mengisi saat observasi kelas (Langkah 2)
    if ((userRole.value.isObserver || userRole.value.isAdmin) && currentStatus.value === 'menunggu_penilaian') {
        return true;
    }
    
    return false;
});

// Focus manipulation
const addFokus = () => {
    form.fokuses.push({
        nama_fokus: '',
        perilaku_dianjurkan: [''],
        perilaku_dihindari: [''],
        penilaians: [null],
        catatan: ''
    });
};

const removeFokus = (index: number) => {
    if (form.fokuses.length > 1) {
        form.fokuses.splice(index, 1);
    }
};

// Paired Behavior row manipulation (adds both dianjurkan & dihindari simultaneously)
const addPerilakuRow = (fokusIndex: number) => {
    form.fokuses[fokusIndex].perilaku_dianjurkan.push('');
    form.fokuses[fokusIndex].perilaku_dihindari.push('');
    form.fokuses[fokusIndex].penilaians.push(null);
};

const removePerilakuRow = (fokusIndex: number, rowIndex: number) => {
    if (form.fokuses[fokusIndex].perilaku_dianjurkan.length > 1) {
        form.fokuses[fokusIndex].perilaku_dianjurkan.splice(rowIndex, 1);
        form.fokuses[fokusIndex].perilaku_dihindari.splice(rowIndex, 1);
        form.fokuses[fokusIndex].penilaians.splice(rowIndex, 1);
    }
};

// Submissions
const submitRencana = () => {
    // Guru mengisi fokus perilaku, anjuran, dihindari, dan tautan video observasi
    for (let fIdx = 0; fIdx < form.fokuses.length; fIdx++) {
        const f = form.fokuses[fIdx];
        if (!f.nama_fokus?.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Fokus Perilaku Kosong',
                text: `Silakan isi nama fokus perilaku ke-${fIdx + 1}.`,
                confirmButtonColor: '#f59e0b'
            });
            return;
        }
        for (let rIdx = 0; rIdx < f.perilaku_dianjurkan.length; rIdx++) {
            if (!f.perilaku_dianjurkan[rIdx]?.trim() || !f.perilaku_dihindari[rIdx]?.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Indikator Belum Lengkap',
                    text: `Pastikan semua perilaku yang dianjurkan dan dihindari telah terisi (Fokus #${fIdx + 1}, Baris #${rIdx + 1}).`,
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
        }
    }

    if (!form.video_link?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Link Video Observasi Wajib Diisi',
            text: 'Silakan cantumkan tautan URL Google Drive atau YouTube rekaman video observasi pembelajaran Anda sebelum mengajukan ke observer.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    Swal.fire({
        title: 'Konfirmasi Pengajuan Rencana & Video',
        text: 'Apakah rencana kerja dan tautan video observasi Anda sudah benar dan siap dinilai oleh observer?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Ajukan ke Observer',
        cancelButtonText: 'Periksa Kembali'
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                action: 'simpan_rencana'
            })).post(`/kinerja/${props.guru.id}/tahap-1`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rencana & Video Berhasil Diajukan',
                        text: 'Rencana hasil kerja beserta tautan video observasi telah tersimpan dan diajukan ke observer untuk penilaian.',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

const submitPenilaian = () => {
    // Validasi bahwa observer telah mengisi tempat, tanggal, dan waktu observasi
    if (!form.tempat?.trim() || !form.tanggal || !form.waktu) {
        Swal.fire({
            icon: 'warning',
            title: 'Jadwal Observasi Belum Lengkap',
            text: 'Silakan isi Tempat, Hari/Tanggal, dan Waktu pelaksanaan observasi kelas terlebih dahulu.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    for (let fIdx = 0; fIdx < form.fokuses.length; fIdx++) {
        const f = form.fokuses[fIdx];
        for (let rIdx = 0; rIdx < f.perilaku_dianjurkan.length; rIdx++) {
            if (!f.penilaians[rIdx]) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Penilaian Belum Lengkap',
                    text: `Silakan tentukan penilaian observasi pada Fokus #${fIdx + 1}, Baris #${rIdx + 1}.`,
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
        }
        if (!f.catatan?.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Catatan Fokus Belum Terisi',
                text: `Silakan berikan catatan observasi pada Fokus #${fIdx + 1}.`,
                confirmButtonColor: '#f59e0b'
            });
            return;
        }
    }

    if (!form.rekomendasi?.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Rekomendasi Wajib Diisi',
            text: 'Silakan isi rekomendasi hasil observasi untuk guru sebelum menyimpan.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    Swal.fire({
        title: 'Konfirmasi Penilaian Observasi',
        text: 'Apakah penilaian dan catatan observasi telah lengkap? Formulir akan langsung diajukan ke Wakasek/Admin untuk verifikasi (ACC).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Simpan & Ajukan',
        cancelButtonText: 'Periksa Kembali'
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                action: 'simpan_penilaian'
            })).post(`/kinerja/${props.guru.id}/tahap-1`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Penilaian Observasi Disimpan',
                        text: 'Penilaian observasi kinerja telah berhasil disimpan dan diteruskan ke Wakasek/Admin untuk verifikasi (ACC).',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

const approveDariReview = () => {
    Swal.fire({
        title: 'Konfirmasi Persetujuan',
        text: 'Apakah Anda yakin ingin menyetujui (ACC) form observasi kinerja tahap 1 ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Setujui',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(`/kinerja-approval/${props.kinerja.id}/approve`);
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
                    Silakan tentukan pihak yang diminta untuk merevisi formulir Tahap 1 dan sertakan catatan perbaikan:
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

            return { revisi_target: target, catatan_revisi: catatan.trim(), tahap: 1 };
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

const submitRevisiGuru = () => {
    for (let f of form.fokuses) {
        if (!f.nama_fokus.trim()) {
            showAlert('Nama fokus perilaku tidak boleh kosong.', 'warning');
            return;
        }
    }
    if (!form.video_link?.trim()) {
        showAlert('Harap sertakan link drive video observasi pembelajaran.', 'warning');
        return;
    }

    const isKeduanya = currentStatus.value === 'revisi_keduanya' || props.kinerja?.revisi_target === 'keduanya';
    const confirmTitle = isKeduanya ? 'Kirim Perbaikan ke Observer?' : 'Konfirmasi Perbaikan';
    const confirmText = isKeduanya
        ? 'Rencana kerja dan link video akan diajukan ke Observer untuk dinilai kembali sebelum diajukan ke Wakasek.'
        : 'Apakah video observasi telah sesuai dan rencana kerja sudah diperbaiki? Formulir akan diajukan kembali ke Wakasek untuk verifikasi.';
    const confirmBtnText = isKeduanya ? 'Ya, Ajukan ke Observer' : 'Ya, Ajukan ke Wakasek';

    Swal.fire({
        title: confirmTitle,
        text: confirmText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: isKeduanya ? '#059669' : '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmBtnText,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                action: 'simpan_revisi_guru'
            })).post(`/kinerja/${props.guru.id}/tahap-1`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Perbaikan Berhasil Diajukan',
                        text: isKeduanya
                            ? 'Perbaikan rencana kerja dan link video telah diajukan ke Observer untuk dinilai kembali.'
                            : 'Perbaikan rencana kerja dan link video telah diajukan kembali ke Wakasek untuk verifikasi.',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

const submitRevisiObserver = () => {
    if (!form.tempat?.trim() || !form.tanggal || !form.waktu?.trim()) {
        showAlert('Harap lengkapi Tempat, Tanggal, dan Waktu observasi.', 'warning');
        return;
    }
    if (!form.rekomendasi?.trim()) {
        showAlert('Rekomendasi dari Observer tidak boleh kosong.', 'warning');
        return;
    }

    for (let i = 0; i < form.fokuses.length; i++) {
        const f = form.fokuses[i];
        if (!f.penilaians || f.penilaians.some((p: any) => p === null || p === '')) {
            showAlert(`Fokus ke-${i + 1} belum semua diberi penilaian observasi (1, 2, atau 3).`, 'warning');
            return;
        }
        if (!f.catatan?.trim()) {
            showAlert(`Catatan untuk Fokus ke-${i + 1} belum diisi.`, 'warning');
            return;
        }
    }

    Swal.fire({
        title: 'Kirim Perbaikan Penilaian?',
        text: 'Perbaikan penilaian dan catatan observasi akan diajukan langsung kembali ke Wakasek.',
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
                action: 'simpan_revisi_observer'
            })).post(`/kinerja/${props.guru.id}/tahap-1`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Penilaian Berhasil Diajukan',
                        text: 'Perbaikan penilaian observasi telah diajukan kembali ke Wakasek untuk verifikasi.',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

const handleLanjutTahap2 = () => {
    if (isTahap1Revisi.value) {
        const targetText = props.kinerja?.revisi_target === 'guru' 
            ? 'Guru yang Diamati' 
            : (props.kinerja?.revisi_target === 'observer' ? 'Observer / Pengamat' : 'Kedua Belah Pihak (Guru & Observer)');
        
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
                        <span style="font-weight: 600; color: #9f1239; display: block; font-size: 12px;">Catatan Peninjau (Wakasek):</span>
                        <span style="color: #be123c; font-style: italic;">"${props.kinerja?.catatan_revisi || 'Harap periksa isian formulir'}"</span>
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

    if (currentStatus.value === 'approved' || isTahap2SedangRevisi.value) {
        router.get(`/kinerja/${props.guru.id}/tahap-2`);
        return;
    }

    // Kasus belum diajukan / masih draft / menunggu observer / pending ACC
    let statusMsg = 'Formulir Observasi Kinerja Tahap 1 belum selesai atau belum disetujui (ACC).';
    if (currentStatus.value === 'draft_guru') {
        statusMsg = 'Guru belum menyelesaikan pengisian Rencana Hasil Kerja & Video Observasi Tahap 1.';
    } else if (currentStatus.value === 'menunggu_penilaian') {
        statusMsg = 'Sedang menunggu Observer memberikan penilaian observasi kelas.';
    } else if (currentStatus.value === 'pending') {
        statusMsg = 'Formulir Tahap 1 telah lengkap dan sedang dalam antrean verifikasi (ACC) oleh Wakasek/Admin.';
    }

    Swal.fire({
        icon: 'info',
        title: 'Tahap 1 Belum Disetujui',
        html: `
            <div style="text-align: left; font-size: 13px; color: #334155;">
                <p style="margin-bottom: 8px;">${statusMsg}</p>
                <p style="color: #64748b; font-size: 12px; margin: 0;">
                    Anda baru dapat melanjutkan ke Tahap 2 (Tindak Lanjut) setelah Formulir Tahap 1 resmi disetujui (ACC) oleh Wakasek / Admin.
                </p>
            </div>
        `,
        confirmButtonColor: '#6366f1',
        confirmButtonText: 'Mengerti'
    });
};

const showAlert = (message: string, type: 'info' | 'warning' = 'info') => {
    Swal.fire({
        icon: type,
        title: type === 'warning' ? 'Perhatian!' : 'Informasi',
        text: message,
        confirmButtonColor: type === 'warning' ? '#f59e0b' : '#3b82f6'
    });
};
</script>

<template>
    <Head :title="`Observasi Kinerja Tahap 1 - ${guru.name}`" />

    <div class="max-w-5xl mx-auto p-3 md:p-8 space-y-5 md:space-y-8 animate-in fade-in duration-300">
        <!-- Header & Status -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 md:gap-4 border-b pb-4 md:pb-6 dark:border-slate-800">
            <div>
                <h1 class="text-xl md:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Observasi Kinerja Guru (Tahap 1)
                </h1>
                <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Formulir B: Pelaksanaan Observasi Praktik Kinerja untuk guru <strong class="text-slate-800 dark:text-slate-200">{{ guru.name }}</strong>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 md:gap-3 w-full md:w-auto">
                <a v-if="kurikulumDriveUrl" :href="kurikulumDriveUrl" target="_blank" rel="noopener noreferrer" class="flex-1 sm:flex-none">
                    <Button variant="outline" class="w-full sm:w-auto gap-1.5 text-xs border-indigo-200 text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100 hover:text-indigo-800 dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300 shadow-xs">
                        <Folder class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                        <span>Drive Kinerja Guru</span>
                        <ExternalLink class="w-3.5 h-3.5" />
                    </Button>
                </a>

                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 px-3 md:px-4 py-2 rounded-xl border shadow-xs text-xs">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Status:</span>
                <Badge v-if="currentStatus === 'approved'" class="bg-emerald-100 text-emerald-800 border-emerald-300 hover:bg-emerald-100">
                    Disetujui (ACC)
                </Badge>
                <Badge v-else-if="currentStatus === 'pending'" class="bg-purple-100 text-purple-800 border-purple-300 hover:bg-purple-100">
                    Menunggu Verifikasi (ACC)
                </Badge>
                <Badge v-else-if="currentStatus === 'menunggu_penilaian'" class="bg-amber-100 text-amber-800 border-amber-300 hover:bg-amber-100">
                    Menunggu Penilaian Observer
                </Badge>
                <Badge v-else-if="currentStatus === 'revisi_guru'" class="bg-rose-100 text-rose-800 border-rose-300 hover:bg-rose-100">
                    Revisi Guru
                </Badge>
                <Badge v-else-if="currentStatus === 'revisi_observer'" class="bg-amber-100 text-amber-800 border-amber-300 hover:bg-amber-100">
                    Revisi Observer
                </Badge>
                <Badge v-else-if="currentStatus === 'revisi_keduanya'" class="bg-rose-100 text-rose-800 border-rose-300 hover:bg-rose-100">
                    Revisi Kedua Pihak
                </Badge>
                <Badge v-else class="bg-slate-100 text-slate-800 border-slate-300 hover:bg-slate-100">
                    Draft Rencana Kerja
                </Badge>
            </div>
        </div>
    </div>

        <!-- Rejection / Revision Alert Banner if any -->
        <div v-if="isTahap1Revisi && kinerja?.catatan_revisi" class="p-4 rounded-2xl bg-rose-50/80 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 flex items-start gap-3.5 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <AlertCircle class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="font-bold text-sm text-rose-900 dark:text-rose-200">
                        Formulir Tahap 1 (Observasi Praktik) Memerlukan Revisi
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

        <!-- Banner jika Tahap 2 sedang direvisi -> Tahap 1 dinonaktifkan (read-only) -->
        <div v-if="isTahap2SedangRevisi" class="p-4 rounded-2xl bg-amber-50/90 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 flex items-start gap-3.5 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                <Lock class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="font-bold text-sm text-amber-900 dark:text-amber-200">
                        Formulir Tahap 1 Dikunci (Mode Hanya-Lihat / Read-Only)
                    </h4>
                    <Badge variant="outline" class="bg-amber-100 text-amber-800 border-amber-300 text-[10px] font-bold uppercase tracking-wider">
                        Tahap 2 Sedang Direvisi
                    </Badge>
                </div>
                <p class="text-xs text-amber-800 dark:text-amber-300 mt-1.5 leading-relaxed font-medium">
                    Formulir Observasi Praktik (Tahap 1) telah selesai dan disetujui (ACC). Saat ini <strong>Formulir Tahap 2 (Tindak Lanjut & Refleksi) sedang memerlukan perbaikan/revisi</strong>. Seluruh isian Formulir Tahap 1 ini dinonaktifkan (di-disable) agar data yang telah disetujui tidak dapat diubah.
                </p>
                <div class="mt-3">
                    <Link :href="`/kinerja/${guru.id}/tahap-2`">
                        <Button size="sm" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs gap-1.5 shadow-xs">
                            Buka Formulir Tahap 2 yang Sedang Direvisi <ArrowRight class="w-3.5 h-3.5" />
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- 3-Step Visual Progress Stepper -->
        <div class="bg-white dark:bg-slate-950 p-4 md:p-6 rounded-2xl border shadow-sm space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Alur Tahap 1:</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <!-- Step 1 -->
                <div class="p-3 rounded-xl border flex items-center gap-3 transition-colors"
                    :class="currentStepNumber >= 2 ? 'bg-emerald-50/60 border-emerald-200 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-800 dark:text-emerald-300' : (currentStepNumber === 1 ? 'bg-blue-50/80 border-blue-300 text-blue-900 font-semibold ring-2 ring-blue-500/20' : 'bg-slate-50 text-slate-400')">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs shrink-0"
                        :class="currentStepNumber >= 2 ? 'bg-emerald-500 text-white' : 'bg-blue-600 text-white'">
                        1
                    </div>
                    <div>
                        <div class="font-bold">Rencana Kerja & Video</div>
                        <div class="text-[11px] opacity-80">Diisi oleh Guru</div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="p-3 rounded-xl border flex items-center gap-3 transition-colors"
                    :class="currentStepNumber >= 3 ? 'bg-emerald-50/60 border-emerald-200 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-800 dark:text-emerald-300' : (currentStepNumber === 2 ? 'bg-amber-50/80 border-amber-300 text-amber-900 font-semibold ring-2 ring-amber-500/20' : 'bg-slate-50 text-slate-400')">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs shrink-0"
                        :class="currentStepNumber >= 3 ? 'bg-emerald-500 text-white' : (currentStepNumber === 2 ? 'bg-amber-500 text-white' : 'bg-slate-300 text-slate-700')">
                        2
                    </div>
                    <div>
                        <div class="font-bold">Penilaian & Catatan</div>
                        <div class="text-[11px] opacity-80">Diisi oleh Observer</div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="p-3 rounded-xl border flex items-center gap-3 transition-colors"
                    :class="currentStepNumber === 4 ? 'bg-emerald-50/60 border-emerald-200 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-800 dark:text-emerald-300' : (currentStepNumber === 3 ? 'bg-purple-50/80 border-purple-300 text-purple-900 font-semibold ring-2 ring-purple-500/20' : 'bg-slate-50 text-slate-400')">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs shrink-0"
                        :class="currentStepNumber === 4 ? 'bg-emerald-500 text-white' : (currentStepNumber === 3 ? 'bg-purple-600 text-white' : 'bg-slate-300 text-slate-700')">
                        3
                    </div>
                    <div>
                        <div class="font-bold">Verifikasi (ACC)</div>
                        <div class="text-[11px] opacity-80">Wakasek / Admin</div>
                    </div>
                </div>
            </div>

            <!-- Role Guidance Alert Banner -->
            <div v-if="isTahap1Revisi" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-sm flex items-start gap-3 text-rose-900 dark:text-rose-200">
                <AlertCircle class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                <div>
                    <span class="font-semibold text-rose-800 dark:text-rose-300">Status Revisi / Perbaikan Aktif (Tahap 1 Memerlukan Perbaikan)</span>
                    <p class="mt-0.5 text-xs text-rose-700 dark:text-rose-300 leading-relaxed">
                        Pihak yang diminta merevisi: <strong>{{ kinerja.revisi_target === 'guru' ? 'Guru yang Diamati' : (kinerja.revisi_target === 'observer' ? 'Observer / Pengamat' : 'Kedua Belah Pihak (Guru & Observer)') }}</strong>. 
                        Catatan revisi: <em>"{{ kinerja.catatan_revisi }}"</em>.
                        Formulir Tahap 2 belum dapat dibuka sebelum Tahap 1 selesai diperbaiki dan disetujui (ACC).
                    </p>
                </div>
            </div>

            <div v-else-if="currentStepNumber === 1" class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 text-sm flex items-start gap-3 text-blue-900 dark:text-blue-200">
                <AlertCircle class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                <div>
                    <span class="font-semibold">Langkah 1: Pengisian Rencana Kerja & Bukti Video (Guru).</span>
                    <p class="mt-0.5 text-xs text-blue-800 dark:text-blue-300">
                        Guru mengisi Fokus Perilaku beserta indikator perilaku yang dianjurkan dan dihindari, serta menyertakan tautan rekaman video observasi kelas (Google Drive / YouTube). Setelah diajukan, observer akan melakukan penilaian observasi.
                    </p>
                </div>
            </div>

            <div v-else-if="currentStepNumber === 2" class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-sm flex items-start gap-3 text-amber-900 dark:text-amber-200">
                <AlertCircle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                <div>
                    <span class="font-semibold">Langkah 2: Penilaian Observasi Kinerja (Observer).</span>
                    <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-300">
                        Rencana hasil kerja dan video observasi guru telah siap. Observer mengisi Jadwal/Tempat Pelaksanaan Observasi, Penilaian Observasi untuk setiap baris indikator perilaku, Catatan, dan Rekomendasi. Setelah disimpan, formulir langsung diajukan ke Wakasek/Admin untuk verifikasi (ACC).
                    </p>
                </div>
            </div>

            <div v-else-if="currentStepNumber === 3" class="p-4 rounded-xl bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800 text-sm flex items-start gap-3 text-purple-900 dark:text-purple-200">
                <ShieldCheck class="w-5 h-5 text-purple-600 shrink-0 mt-0.5" />
                <div>
                    <span class="font-semibold">Langkah 3: Menunggu Persetujuan / ACC (Wakasek & Admin).</span>
                    <p class="mt-0.5 text-xs text-purple-800 dark:text-purple-300">
                        Seluruh isian observasi telah lengkap dan dinilai oleh observer. Sedang dalam proses verifikasi di menu Persetujuan Kinerja sebelum dapat melanjutkan ke Tahap 2.
                    </p>
                </div>
            </div>

            <div v-else-if="currentStepNumber === 4" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-sm flex items-start gap-3 text-emerald-900 dark:text-emerald-200">
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                <div>
                    <span class="font-semibold">Formulir Observasi Kinerja Tahap 1 Telah Disetujui (ACC).</span>
                    <p class="mt-0.5 text-xs text-emerald-800 dark:text-emerald-300">
                        Observasi kelas tahap 1 telah diverifikasi resmi. Anda dapat langsung melanjutkan ke Tindak Lanjut Observasi Kinerja (Tahap 2).
                    </p>
                </div>
            </div>
        </div>

        <!-- 1. Identitas & Jadwal Pelaksanaan -->
        <Card class="border-t-4 border-t-blue-500 shadow-md bg-white dark:bg-slate-950">
            <CardHeader class="bg-blue-50/50 dark:bg-blue-900/10 border-b pb-4">
                <CardTitle class="text-xl text-blue-700 dark:text-blue-400">Identitas & Jadwal Pelaksanaan</CardTitle>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                <div class="space-y-2">
                    <Label>Nama Guru</Label>
                    <div class="flex h-10 w-full items-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-100">
                        {{ guru.name }}
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Nama Pengamat (Observer)</Label>
                    <div class="flex h-10 w-full items-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-100">
                        {{ kinerja?.observer?.name || guru?.observer?.name || 'Belum Ditentukan' }}
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Periode</Label>
                    <div class="flex h-10 w-full items-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-100">
                        Januari - Desember {{ currentYear }}
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Tempat / Lokasi <span v-if="canEditJadwal" class="text-red-500">*</span></Label>
                    <Input 
                        v-model="form.tempat" 
                        placeholder="Contoh: Ruang Kelas X RPL 1" 
                        :disabled="!canEditJadwal"
                        :required="canEditJadwal" 
                    />
                </div>

                <div class="space-y-2">
                    <Label>Hari / Tanggal <span v-if="canEditJadwal" class="text-red-500">*</span></Label>
                    <Input 
                        type="date" 
                        v-model="form.tanggal" 
                        :disabled="!canEditJadwal"
                        :required="canEditJadwal" 
                    />
                </div>

                <div class="space-y-2">
                    <Label>Waktu <span v-if="canEditJadwal" class="text-red-500">*</span></Label>
                    <Input 
                        type="time" 
                        v-model="form.waktu" 
                        :disabled="!canEditJadwal"
                        :required="canEditJadwal" 
                    />
                </div>

                <!-- Notice if not editable by guru -->
                <div v-if="!canEditJadwal" class="col-span-1 md:col-span-2 text-xs text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 p-3 rounded-lg border border-amber-200 dark:border-amber-800 flex items-center gap-2">
                    <Clock class="w-4 h-4 shrink-0 text-amber-600" />
                    <span>Jadwal pelaksanaan (Tempat, Hari/Tanggal, dan Waktu) ditentukan dan diisi oleh Pengamat (Observer) saat observasi kelas dilaksanakan. Guru hanya mengisi Rencana Hasil Kerja di bawah.</span>
                </div>

                <div class="col-span-1 md:col-span-2 space-y-2 mt-2 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700">
                    <Label class="text-sm font-semibold mb-1 block">Tujuan Observasi:</Label>
                    <ul class="list-decimal pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                        <li>Guru menggunakan rubrik observasi kelas sebagai perilaku yang akan ditampilkan pada observasi kinerja.</li>
                        <li>Kepala sekolah / pengamat mencatat bukti perilaku dan penilaian efektivitas perilaku yang ditampilkan guru selama observasi kinerja beserta rekomendasi tindak lanjutnya.</li>
                    </ul>
                </div>
            </CardContent>
        </Card>

        <!-- 2. Rencana Hasil Kerja (Fokus Perilaku & Indikator Perilaku Berpasangan) -->
        <Card class="border-t-4 border-t-emerald-500 shadow-md bg-white dark:bg-slate-950">
            <CardHeader class="bg-emerald-50/50 dark:bg-emerald-900/10 border-b pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <CardTitle class="text-xl text-emerald-700 dark:text-emerald-400">Rencana Hasil Kerja</CardTitle>
                    <CardDescription>
                        Fokus perilaku serta penjabaran perilaku yang dianjurkan & dihindari beserta penilaian observasi per baris.
                    </CardDescription>
                </div>
                <Button 
                    v-if="canEditRencana" 
                    type="button" 
                    variant="outline" 
                    size="sm" 
                    @click="addFokus" 
                    class="border-emerald-200 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 shrink-0">
                    <Plus class="w-4 h-4 mr-1" /> Tambah Fokus Perilaku
                </Button>
            </CardHeader>

            <CardContent class="p-4 md:p-6 space-y-8">
                <div v-for="(fokus, fIndex) in form.fokuses" :key="fIndex" 
                    class="relative p-5 md:p-6 border-2 rounded-2xl bg-white dark:bg-slate-900/80 shadow-sm space-y-6"
                    :class="canEditPenilaian ? 'border-amber-300 dark:border-amber-800' : 'border-slate-200 dark:border-slate-800'">
                    
                    <!-- Number Badge -->
                    <div class="absolute -top-3.5 -left-3 w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-md">
                        {{ Number(fIndex) + 1 }}
                    </div>

                    <!-- Delete Fokus Button -->
                    <Button 
                        v-if="canEditRencana && form.fokuses.length > 1" 
                        type="button" 
                        variant="ghost" 
                        size="icon" 
                        @click="removeFokus(Number(fIndex))" 
                        class="absolute top-2 right-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50"
                        title="Hapus Fokus Ini">
                        <Trash2 class="w-4 h-4" />
                    </Button>

                    <!-- Nama Fokus Perilaku -->
                    <div class="space-y-2 pt-1">
                        <Label class="text-base font-semibold text-slate-800 dark:text-slate-200">
                            Fokus Perilaku <span class="text-red-500">*</span>
                        </Label>
                        <Textarea 
                            v-model="fokus.nama_fokus" 
                            placeholder="Contoh: Guru melakukan komunikasi positif untuk membangun suasana kelas yang kondusif..." 
                            :disabled="!canEditRencana"
                            class="min-h-[70px]"
                            required 
                        />
                    </div>

                    <!-- Indikator Perilaku Berpasangan (Dianjurkan & Dihindari) -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b pb-2">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Target / Indikator Perilaku</h4>
                                <p class="text-xs text-muted-foreground">Setiap baris berisi perilaku yang dianjurkan, perilaku yang dihindari, serta penilaian observasi.</p>
                            </div>
                            <Button 
                                v-if="canEditRencana" 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                @click="addPerilakuRow(Number(fIndex))" 
                                class="h-8 px-3 text-xs border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                                <Plus class="w-3.5 h-3.5 mr-1" /> Tambah Baris Perilaku
                            </Button>
                        </div>

                        <!-- Rows of Paired Behaviors & Observation Rating -->
                        <div class="space-y-4">
                            <div v-for="(dianjurkan, rIndex) in fokus.perilaku_dianjurkan" :key="'row-'+fIndex+'-'+rIndex" 
                                class="p-4 rounded-xl border bg-slate-50/70 dark:bg-slate-800/40 space-y-4">
                                
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60 dark:border-slate-700/60">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                            Indikator #{{ Number(rIndex) + 1 }}
                                        </span>
                                    </div>
                                    <Button 
                                        v-if="canEditRencana && fokus.perilaku_dianjurkan.length > 1" 
                                        type="button" 
                                        variant="ghost" 
                                        size="sm" 
                                        @click="removePerilakuRow(Number(fIndex), Number(rIndex))" 
                                        class="h-7 px-2 text-xs text-rose-500 hover:bg-rose-100/50">
                                        <Trash2 class="w-3.5 h-3.5 mr-1" /> Hapus Baris
                                    </Button>
                                </div>

                                <!-- Inputs: Dianjurkan & Dihindari (Berpasangan per baris) -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Perilaku Dianjurkan -->
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                                            Perilaku yang Dianjurkan
                                        </Label>
                                        <Textarea 
                                            v-model="fokus.perilaku_dianjurkan[rIndex]" 
                                            placeholder="Contoh: Guru memanggil murid dengan menyebut namanya..." 
                                            :disabled="!canEditRencana"
                                            class="min-h-[60px] text-sm bg-white dark:bg-slate-900" 
                                            required 
                                        />
                                    </div>

                                    <!-- Perilaku Dihindari -->
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-semibold text-rose-700 dark:text-rose-400 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
                                            Perilaku yang Dihindari
                                        </Label>
                                        <Textarea 
                                            v-model="fokus.perilaku_dihindari[rIndex]" 
                                            placeholder="Contoh: Guru mengabaikan pendapat atau pertanyaan murid..." 
                                            :disabled="!canEditRencana"
                                            class="min-h-[60px] text-sm bg-white dark:bg-slate-900" 
                                            required 
                                        />
                                    </div>
                                </div>

                                <!-- Penilaian Observasi untuk Baris Ini (Diisi oleh Observer) -->
                                <div class="pt-3 border-t border-slate-200/80 dark:border-slate-700/80 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                            <UserCheck class="w-3.5 h-3.5 text-blue-600" />
                                            Penilaian Observasi untuk Indikator #{{ Number(rIndex) + 1 }}:
                                        </Label>
                                        <span v-if="!canEditPenilaian && !fokus.penilaians[rIndex]" class="text-[11px] text-amber-600 italic">
                                            Menunggu penilaian observer
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        <!-- Option 1: Belum dilakukan -->
                                        <label class="cursor-pointer">
                                            <input 
                                                type="radio" 
                                                v-model="fokus.penilaians[rIndex]" 
                                                :value="1" 
                                                :disabled="!canEditPenilaian"
                                                class="peer sr-only" 
                                            />
                                            <div class="p-2.5 rounded-lg border text-center text-xs font-medium transition-all border-slate-200 dark:border-slate-700 peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-800 dark:peer-checked:bg-rose-950/40 dark:peer-checked:text-rose-200"
                                                :class="!canEditPenilaian ? 'cursor-not-allowed opacity-80' : 'hover:border-rose-300'">
                                                Belum dilakukan
                                            </div>
                                        </label>

                                        <!-- Option 2: Dilakukan tapi belum efektif -->
                                        <label class="cursor-pointer">
                                            <input 
                                                type="radio" 
                                                v-model="fokus.penilaians[rIndex]" 
                                                :value="2" 
                                                :disabled="!canEditPenilaian"
                                                class="peer sr-only" 
                                            />
                                            <div class="p-2.5 rounded-lg border text-center text-xs font-medium transition-all border-slate-200 dark:border-slate-700 peer-checked:bg-amber-50 peer-checked:border-amber-500 peer-checked:text-amber-800 dark:peer-checked:bg-amber-950/40 dark:peer-checked:text-amber-200"
                                                :class="!canEditPenilaian ? 'cursor-not-allowed opacity-80' : 'hover:border-amber-300'">
                                                Dilakukan tapi belum efektif
                                            </div>
                                        </label>

                                        <!-- Option 3: Dilakukan dan efektif -->
                                        <label class="cursor-pointer">
                                            <input 
                                                type="radio" 
                                                v-model="fokus.penilaians[rIndex]" 
                                                :value="3" 
                                                :disabled="!canEditPenilaian"
                                                class="peer sr-only" 
                                            />
                                            <div class="p-2.5 rounded-lg border text-center text-xs font-medium transition-all border-slate-200 dark:border-slate-700 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-800 dark:peer-checked:bg-emerald-950/40 dark:peer-checked:text-emerald-200"
                                                :class="!canEditPenilaian ? 'cursor-not-allowed opacity-80' : 'hover:border-emerald-300'">
                                                Dilakukan dan efektif
                                            </div>
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Catatan Fokus Perilaku (Observer) -->
                    <div class="space-y-2 pt-2 border-t">
                        <Label class="text-sm font-semibold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                            <span>Catatan Observer (Fokus #{{ Number(fIndex) + 1 }}) <span v-if="canEditPenilaian" class="text-red-500">*</span></span>
                            <span v-if="!canEditPenilaian && !fokus.catatan" class="text-xs text-amber-600 font-normal italic">
                                Belum ada catatan dari observer
                            </span>
                        </Label>
                        <Textarea 
                            v-model="fokus.catatan" 
                            placeholder="Tuliskan catatan dan bukti pengamatan yang tampak selama observasi kelas..." 
                            :disabled="!canEditPenilaian"
                            class="min-h-[80px]" 
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- 3. Rekomendasi (Observer) & Link Video (Guru) -->
        <Card class="border-t-4 border-t-indigo-500 shadow-md bg-white dark:bg-slate-950">
            <CardHeader class="bg-indigo-50/50 dark:bg-indigo-900/10 border-b pb-4">
                <CardTitle class="text-xl text-indigo-700 dark:text-indigo-400">Rekomendasi & Bukti Video Observasi</CardTitle>
            </CardHeader>
            <CardContent class="p-6 space-y-6">
                <!-- Rekomendasi (Diisi Observer) -->
                <div class="space-y-2">
                    <Label class="text-base font-semibold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                        <span>Rekomendasi Hasil Observasi (Observer) <span v-if="canEditPenilaian" class="text-red-500">*</span></span>
                        <span v-if="!canEditPenilaian && !form.rekomendasi" class="text-xs text-amber-600 font-normal italic">
                            Belum diisi oleh observer
                        </span>
                    </Label>
                    <p class="text-xs text-muted-foreground">Rekomendasi tindak lanjut bagi guru untuk meningkatkan efektivitas praktik pembelajaran.</p>
                    <Textarea 
                        v-model="form.rekomendasi" 
                        placeholder="Contoh: Disarankan untuk lebih memberikan kesempatan kepada murid yang pasif agar berpartisipasi aktif..." 
                        :disabled="!canEditPenilaian"
                        class="min-h-[100px]" 
                    />
                </div>

                <!-- Link Video (Diisi Guru di Langkah 1 bersamaan dengan Rencana Kerja) -->
                <div class="space-y-2 pt-4 border-t">
                    <Label class="text-base font-semibold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <Video class="w-4 h-4 text-indigo-500" />
                            Link Drive Video Observasi Pembelajaran <span v-if="canEditVideo" class="text-red-500">*</span>
                        </span>
                        <span v-if="!canEditVideo && !form.video_link" class="text-xs text-amber-600 font-normal italic">
                            Belum diisi oleh guru
                        </span>
                    </Label>
                    <p class="text-xs text-muted-foreground">Tautkan link Google Drive, YouTube, atau penyimpanan cloud rekaman observasi kelas. Pastikan akses tautan terbuka untuk ditinjau oleh observer dan wakasek.</p>
                    
                    <!-- Link Google Drive Resmi dari Kurikulum jika ada -->
                    <div v-if="kurikulumDriveUrl" class="p-3 rounded-xl bg-blue-50/80 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2 text-xs text-blue-900 dark:text-blue-300">
                            <Folder class="w-4 h-4 text-blue-600 shrink-0" />
                            <span><strong>Folder Drive Resmi Kurikulum</strong> tersedia untuk Anda. Unggah rekaman video ke folder ini.</span>
                        </div>
                        <a :href="kurikulumDriveUrl" target="_blank" rel="noopener noreferrer" class="shrink-0">
                            <Button type="button" size="sm" class="h-8 gap-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                                <span>Buka Google Drive</span>
                                <ExternalLink class="w-3.5 h-3.5" />
                            </Button>
                        </a>
                    </div>

                    <div class="flex gap-2">
                        <Input 
                            v-model="form.video_link" 
                            type="url" 
                            placeholder="https://drive.google.com/file/d/... atau https://youtube.com/..." 
                            :disabled="!canEditVideo"
                            class="flex-1"
                        />
                        <a v-if="form.video_link" :href="form.video_link" target="_blank" rel="noopener noreferrer">
                            <Button type="button" variant="outline" class="gap-1 text-xs shrink-0">
                                Buka Link <ArrowRight class="w-3.5 h-3.5" />
                            </Button>
                        </a>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- 4. Action Toolbar (Bawah) -->
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 bg-slate-50 dark:bg-slate-900 p-3.5 md:p-5 rounded-2xl border shadow-sm">
            <Link :href="isReview ? '/kinerja-approval' : '/kinerja'" class="w-full sm:w-auto">
                <Button type="button" variant="outline" class="w-full sm:w-auto gap-2">
                    <ArrowLeft class="w-4 h-4" /> Kembali
                </Button>
            </Link>
            
            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2 md:gap-3 w-full sm:w-auto">
                <!-- If Review Mode (Wakasek / Admin) -->
                <template v-if="isReview">
                    <Button 
                        v-if="kinerja?.status_tahap1 !== 'approved'" 
                        type="button" 
                        variant="outline"
                        class="border-rose-300 text-rose-600 hover:bg-rose-50 hover:text-rose-700 shadow-sm gap-2" 
                        @click="rejectDariReview"
                        :disabled="form.processing"
                    >
                        <XCircle class="w-4 h-4" />
                        Tolak & Minta Revisi
                    </Button>
                    <Button 
                        v-if="kinerja?.status_tahap1 !== 'approved'" 
                        type="button" 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-md gap-2" 
                        @click="approveDariReview"
                        :disabled="form.processing"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        Setujui (ACC) Sekarang
                    </Button>
                </template>

                <!-- Normal Workflow Actions -->
                <template v-else>
                    <!-- KASUS KETIKA TAHAP 1 SUDAH APPROVED ATAU TAHAP 2 SEDANG REVISI: SEMUA TOMBOL SIMPAN TAHAP 1 DITUTUP/DIKUNCI -->
                    <template v-if="isTahap2SedangRevisi || currentStatus === 'approved'">
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 flex items-center gap-1.5">
                            <CheckCircle2 class="w-4 h-4 text-emerald-600" /> Formulir Tahap 1 Telah Disetujui (ACC)
                        </span>
                    </template>

                    <!-- KASUS REVISI TAHAP 1 -->
                    <template v-else-if="isTahap1Revisi">
                        <!-- Tombol Simpan Revisi Guru (Refleksi / Rencana Kerja & Video) -->
                        <Button 
                            v-if="(userRole.isGuru || userRole.isAdmin) && isTahap1RevisiGuru" 
                            type="button" 
                            class="bg-rose-600 hover:bg-rose-700 text-white shadow-md gap-2" 
                            :disabled="form.processing"
                            @click="submitRevisiGuru"
                        >
                            <CheckCircle2 class="w-4 h-4" />
                            {{ form.processing ? 'Menyimpan Perbaikan...' : (currentStatus === 'revisi_keduanya' ? 'Simpan Perbaikan & Ajukan ke Observer' : 'Simpan Perbaikan & Ajukan ke Wakasek') }}
                        </Button>

                        <!-- Tombol Simpan Revisi Observer (Jadwal & Penilaian Observasi) -->
                        <Button 
                            v-if="(userRole.isObserver || userRole.isAdmin) && isTahap1RevisiObserver" 
                            type="button" 
                            class="bg-amber-600 hover:bg-amber-700 text-white shadow-md gap-2" 
                            :disabled="form.processing"
                            @click="submitRevisiObserver"
                        >
                            <CheckCircle2 class="w-4 h-4" />
                            {{ form.processing ? 'Menyimpan Perbaikan...' : 'Simpan Perbaikan Penilaian & Ajukan ke Wakasek' }}
                        </Button>

                        <!-- Pesan Menunggu jika revisi ditujukan ke pihak lain -->
                        <span v-if="userRole.isObserver && currentStatus === 'revisi_keduanya'" class="text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 flex items-center gap-1.5">
                            <Clock class="w-4 h-4 text-amber-600" /> Menunggu guru menyelesaikan perbaikan rencana kerja & video terlebih dahulu.
                        </span>
                        <span v-else-if="userRole.isGuru && currentStatus === 'revisi_observer'" class="text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 flex items-center gap-1.5">
                            <Clock class="w-4 h-4 text-amber-600" /> Menunggu observer menyelesaikan penilaian observasi.
                        </span>
                        <span v-else-if="userRole.isObserver && currentStatus === 'revisi_guru'" class="text-xs font-semibold text-rose-600 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200 flex items-center gap-1.5">
                            <Clock class="w-4 h-4 text-rose-600" /> Menunggu guru menyelesaikan perbaikan rencana kerja / link drive video.
                        </span>
                    </template>

                    <!-- ALUR REGULER (DRAFT / PENILAIAN) -->
                    <template v-else>
                        <!-- Step 1 Guru Action: Simpan Rencana Kerja & Video -->
                        <Button 
                            v-if="userRole.isGuru && (currentStatus === 'draft_guru' || !kinerja)" 
                            type="button" 
                            class="bg-blue-600 hover:bg-blue-700 text-white shadow-md gap-2" 
                            :disabled="form.processing"
                            @click="submitRencana"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan & Ajukan ke Observer' }}
                        </Button>

                        <!-- Step 2 Observer Action: Simpan Penilaian Observasi & Ajukan ke Wakasek -->
                        <Button 
                            v-if="userRole.isObserver && currentStatus === 'menunggu_penilaian'" 
                            type="button" 
                            class="bg-amber-600 hover:bg-amber-700 text-white shadow-md gap-2" 
                            :disabled="form.processing"
                            @click="submitPenilaian"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Penilaian Observasi & Ajukan ke Wakasek' }}
                        </Button>
                    </template>

                    <!-- Step 4/5 Proceed to Tahap 2 button -->
                    <Button 
                        type="button" 
                        variant="secondary" 
                        class="gap-2 transition-all font-medium" 
                        :class="isTahap2SedangRevisi
                            ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-md'
                            : (currentStatus === 'approved' 
                                ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-md' 
                                : (isTahap1Revisi 
                                    ? 'bg-rose-100 hover:bg-rose-200 text-rose-700 border border-rose-300 shadow-xs' 
                                    : 'bg-slate-100 hover:bg-slate-200 text-slate-500 opacity-90'))" 
                        @click="handleLanjutTahap2"
                    >
                        <template v-if="isTahap2SedangRevisi">
                            <AlertCircle class="w-4 h-4 text-white" />
                            Menuju Tahap 2 (Perlu Revisi) <ArrowRight class="w-4 h-4" />
                        </template>
                        <template v-else-if="isTahap1Revisi">
                            <AlertCircle class="w-4 h-4 text-rose-600" />
                            Tahap 1 Memerlukan Revisi
                        </template>
                        <template v-else-if="currentStatus === 'approved'">
                            Lanjut Tahap 2 <ArrowRight class="w-4 h-4" />
                        </template>
                        <template v-else>
                            Tahap 2 (Terkunci) <Clock class="w-4 h-4" />
                        </template>
                    </Button>
                </template>
            </div>
        </div>

    </div>
</template>