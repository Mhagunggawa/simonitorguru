<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { 
    Camera, 
    RefreshCcw, 
    CheckCircle, 
    CheckCircle2, 
    Lock, 
    ShieldCheck, 
    SwitchCamera, 
    RectangleVertical, 
    RectangleHorizontal, 
    FlipHorizontal,
    CheckCheck,
    GraduationCap,
    BookOpen,
    UserCheck,
    AlertCircle,
    Info
} from '@lucide/vue';
import { ref, computed, onBeforeUnmount, onMounted } from 'vue';
import Swal from 'sweetalert2';
import { compressCanvasToTargetSize } from '@/lib/imageCompressor';

const props = defineProps<{
    jadwalPiket: {
        id: number;
        tanggal_piket: string;
        titik: { nama_titik: string };
    };
    jadwalMapels: Array<{
        id: number;
        kelas: { nama_kelas: string };
        mata_pelajaran: { nama_mapel: string };
        guru: { name: string };
    }>;
    totalClasses: number;
    currentJam: { id: number; urutan_jam: number; waktu_mulai: string; waktu_selesai: string };
    isTerlambat: boolean;
    existingPhotoUrl?: string | null;
    existingPhotoNote?: string | null;
}>();

onMounted(() => {
    if (props.jadwalMapels.length === 0) {
        let textMsg = 'Semua kelas pada shift ini sudah dilaporkan!';
        if (props.totalClasses === 0) {
            textMsg = 'Tidak ada jadwal mata pelajaran di kelas manapun untuk titik lokasi dan jam ini.';
        }
        Swal.fire({
            title: props.totalClasses === 0 ? 'Tidak Ada Jadwal' : 'Selesai!',
            text: textMsg,
            icon: 'info',
            confirmButtonText: 'Kembali',
            allowOutsideClick: false
        }).then(() => {
            router.visit('/pelaporan-piket');
        });
    }
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tugas Piket Hari Ini',
                href: '/pelaporan-piket',
            },
            {
                title: 'Isi Laporan Shift',
                href: '#',
            },
        ],
    },
});

interface ReportItem {
    jadwal_mapel_id: number;
    kelas_nama: string;
    mapel_nama: string;
    guru_nama: string;
    status_kehadiran: 'Hadir' | 'Izin' | 'Sakit' | 'Alpha' | '';
    keterangan: string;
}

const form = useForm({
    jadwal_piket_id: props.jadwalPiket.id,
    jam_pelajaran_id: props.currentJam.id,
    reports: props.jadwalMapels.map((mapel): ReportItem => ({
        jadwal_mapel_id: mapel.id,
        kelas_nama: mapel.kelas?.nama_kelas || 'Kelas',
        mapel_nama: mapel.mata_pelajaran?.nama_mapel || 'Mapel',
        guru_nama: mapel.guru?.name || 'Guru Pengampu',
        status_kehadiran: 'Hadir', // Default Hadir agar guru piket cepat melapor
        keterangan: '',
    })),
    foto_dokumentasi: null as File | null,
});

const statusOptions = [
    { 
        value: 'Hadir', 
        label: 'Hadir', 
        activeClass: 'bg-emerald-600 text-white border-emerald-600 shadow-sm ring-2 ring-emerald-500/30 font-bold',
        inactiveClass: 'bg-emerald-50/70 text-emerald-700 border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-800/60 dark:hover:bg-emerald-900/40',
    },
    { 
        value: 'Izin', 
        label: 'Izin', 
        activeClass: 'bg-blue-600 text-white border-blue-600 shadow-sm ring-2 ring-blue-500/30 font-bold',
        inactiveClass: 'bg-blue-50/70 text-blue-700 border-blue-200 hover:bg-blue-100 dark:bg-blue-950/30 dark:text-blue-300 dark:border-blue-800/60 dark:hover:bg-blue-900/40',
    },
    { 
        value: 'Sakit', 
        label: 'Sakit', 
        activeClass: 'bg-amber-600 text-white border-amber-600 shadow-sm ring-2 ring-amber-500/30 font-bold',
        inactiveClass: 'bg-amber-50/70 text-amber-700 border-amber-200 hover:bg-amber-100 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800/60 dark:hover:bg-amber-900/40',
    },
    { 
        value: 'Alpha', 
        label: 'Alpha', 
        activeClass: 'bg-rose-600 text-white border-rose-600 shadow-sm ring-2 ring-rose-500/30 font-bold',
        inactiveClass: 'bg-rose-50/70 text-rose-700 border-rose-200 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-300 dark:border-rose-800/60 dark:hover:bg-rose-900/40',
    },
] as const;

const markAllAsHadir = () => {
    form.reports.forEach(report => {
        report.status_kehadiran = 'Hadir';
    });
};

const filledCount = computed(() => {
    return form.reports.filter(r => !!r.status_kehadiran).length;
});

// Camera logic
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const photoUrl = ref<string | null>(null);
const photoSize = ref<string | null>(null);
const stream = ref<MediaStream | null>(null);
const isCameraActive = ref(false);
const isTakingPhoto = ref(false);
const currentFacingMode = ref<'environment' | 'user'>('environment');
const cameraOrientation = ref<'portrait' | 'landscape'>('portrait');
const isMirrored = ref(false);

const toggleMirror = () => {
    isMirrored.value = !isMirrored.value;
};

const startCamera = async () => {
    try {
        stopCamera();

        const isPortrait = cameraOrientation.value === 'portrait';
        let newStream: MediaStream;
        try {
            newStream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    facingMode: { ideal: currentFacingMode.value },
                    width: { ideal: isPortrait ? 1080 : 1920 },
                    height: { ideal: isPortrait ? 1920 : 1080 }
                },
                audio: false
            });
        } catch (e) {
            newStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });
        }

        stream.value = newStream;
        isCameraActive.value = true;

        setTimeout(() => {
            if (videoRef.value) {
                videoRef.value.srcObject = newStream;
            }
        }, 100);

        if (photoUrl.value) {
            URL.revokeObjectURL(photoUrl.value);
            photoUrl.value = null;
        }
        photoSize.value = null;
        form.foto_dokumentasi = null;
    } catch (err) {
        console.error("Error accessing camera:", err);
        Swal.fire({
            icon: 'error',
            title: 'Akses Kamera Gagal',
            text: 'Tidak dapat mengakses kamera. Pastikan Anda telah memberikan izin akses kamera pada perangkat atau browser Anda.',
            confirmButtonColor: '#ef4444'
        });
    }
};

const switchCamera = async () => {
    currentFacingMode.value = currentFacingMode.value === 'environment' ? 'user' : 'environment';
    await startCamera();
};

const toggleOrientation = async () => {
    cameraOrientation.value = cameraOrientation.value === 'portrait' ? 'landscape' : 'portrait';
    if (isCameraActive.value) {
        await startCamera();
    }
};

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }
    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
    isCameraActive.value = false;
};

const takePhoto = async () => {
    if (!videoRef.value || !canvasRef.value) return;

    isTakingPhoto.value = true;
    try {
        const video = videoRef.value;
        const canvas = canvasRef.value;

        const width = video.videoWidth;
        const height = video.videoHeight;

        if (!width || !height) {
            isTakingPhoto.value = false;
            return;
        }

        // Full uncropped view
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        if (ctx) {
            if (isMirrored.value && currentFacingMode.value === 'user') {
                ctx.save();
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(video, 0, 0, width, height);
                ctx.restore();
            } else {
                ctx.drawImage(video, 0, 0, width, height);
            }

            // Kompresi foto ke target ~100KB
            const result = await compressCanvasToTargetSize(canvas, {
                targetSizeKB: 100,
                maxDimension: 960,
                fileName: `pelaporan_shift_${Date.now()}.jpg`
            });

            if (photoUrl.value) {
                URL.revokeObjectURL(photoUrl.value);
            }

            form.foto_dokumentasi = result.file;
            photoUrl.value = result.previewUrl;
            photoSize.value = result.formattedSize;
            stopCamera();
        }
    } catch (err) {
        console.error("Error memproses foto:", err);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Memproses Foto',
            text: 'Gagal memproses foto. Silakan coba kembali.',
            confirmButtonColor: '#ef4444'
        });
    } finally {
        isTakingPhoto.value = false;
    }
};

onBeforeUnmount(() => {
    stopCamera();
    if (photoUrl.value) {
        URL.revokeObjectURL(photoUrl.value);
    }
});

const getStatusBadgeStyle = (status: string) => {
    switch (status) {
        case 'Hadir': return 'background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;';
        case 'Izin': return 'background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;';
        case 'Sakit': return 'background:#fef3c7;color:#92400e;border:1px solid #fde68a;';
        case 'Alpha': return 'background:#ffe4e6;color:#9f1239;border:1px solid #fecdd3;';
        default: return 'background:#f3f4f6;color:#374151;';
    }
};

const submit = () => {
    // 1. Validasi: semua kelas harus memiliki status kehadiran
    const unfilledIndex = form.reports.findIndex(r => !r.status_kehadiran);
    if (unfilledIndex !== -1) {
        const missingClass = form.reports[unfilledIndex].kelas_nama;
        Swal.fire({
            icon: 'warning',
            title: 'Status Belum Lengkap',
            text: `Silakan pilih status kehadiran untuk kelas ${missingClass}.`,
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // 2. Validasi: foto dokumentasi wajib bila bukan mode terlambat dan belum ada foto sebelumnya
    if (!props.isTerlambat && !props.existingPhotoUrl && !form.foto_dokumentasi) {
        Swal.fire({
            icon: 'warning',
            title: 'Foto Dokumentasi Wajib',
            text: 'Anda wajib mengambil foto dokumentasi langsung di titik piket sebelum menyimpan seluruh laporan.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Bangun daftar HTML ringkasan kelas
    const classListHtml = form.reports.map((r, i) => `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-radius:8px;background:${i % 2 === 0 ? '#f8fafc' : '#ffffff'};border:1px solid #e2e8f0;margin-bottom:6px;">
            <div style="text-align:left;line-height:1.3;">
                <div style="font-weight:700;color:#1e293b;font-size:13px;">${r.kelas_nama} <span style="font-weight:400;color:#64748b;">— ${r.mapel_nama}</span></div>
                <div style="font-size:11px;color:#64748b;">Guru: ${r.guru_nama}${r.keterangan ? ` <span style="color:#0284c7;font-style:italic;">(${r.keterangan})</span>` : ''}</div>
            </div>
            <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:9999px;white-space:nowrap;${getStatusBadgeStyle(r.status_kehadiran)}">
                ${r.status_kehadiran}
            </span>
        </div>
    `).join('');

    Swal.fire({
        title: 'Konfirmasi Simpan Laporan',
        html: `
            <div style="text-align:left;margin-top:8px;">
                <p style="font-size:13px;color:#475569;margin-bottom:8px;">
                    Anda akan menyimpan laporan serentak untuk <strong>${form.reports.length} kelas</strong> pada jam ke-<strong>${props.currentJam.urutan_jam}</strong>:
                </p>
                <div style="max-height:220px;overflow-y:auto;padding-right:4px;">
                    ${classListHtml}
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Ya, Simpan ${form.reports.length} Kelas`,
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            form.post('/pelaporan-piket', {
                forceFormData: true,
            });
        }
    });
};
</script>

<template>
    <Head title="Isi Laporan Kehadiran Shift Piket" />

    <div class="flex h-full flex-1 flex-col gap-5 rounded-xl p-3 sm:p-4 md:max-w-3xl mx-auto w-full">
        <!-- Header Page -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Pelaporan Kehadiran Kelas</h1>
                <p class="text-xs sm:text-sm text-muted-foreground">Isi status seluruh kelas pada shift jam ini sekaligus</p>
            </div>
            <Button variant="outline" as-child size="sm">
                <Link href="/pelaporan-piket">Kembali</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm dark:border-sidebar-border p-4 sm:p-6">
            <!-- Informasi Piket Header Bar -->
            <div class="mb-6 p-4 bg-muted/40 rounded-xl border border-border flex flex-wrap items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-primary/10 text-primary font-bold text-sm">
                            {{ currentJam.urutan_jam }}
                        </span>
                        <h3 class="font-bold text-base sm:text-lg">Jam ke-{{ currentJam.urutan_jam }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-secondary text-secondary-foreground font-medium">
                            {{ currentJam.waktu_mulai.substring(0,5) }} - {{ currentJam.waktu_selesai.substring(0,5) }} WIB
                        </span>
                    </div>
                    <div class="text-xs sm:text-sm text-muted-foreground">
                        Titik Pantau: <strong class="text-foreground">{{ jadwalPiket.titik.nama_titik }}</strong>
                    </div>
                </div>

                <div class="text-right">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                        <UserCheck class="w-3.5 h-3.5" />
                        {{ form.reports.length }} Kelas Dipantau
                    </span>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECTION 1: DAFTAR KELAS & STATUS KEHADIRAN (BATCH VIEW) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between flex-wrap gap-2 pb-1 border-b border-border/60">
                        <div class="flex items-center gap-2">
                            <Label class="text-base font-bold text-foreground">Daftar Kelas Dipantau</Label>
                            <span class="text-xs font-medium text-muted-foreground bg-muted px-2 py-0.5 rounded">
                                {{ filledCount }}/{{ form.reports.length }} Selesai
                            </span>
                        </div>
                        <Button 
                            type="button" 
                            variant="secondary" 
                            size="sm" 
                            @click="markAllAsHadir"
                            class="h-8 text-xs font-medium gap-1.5 cursor-pointer bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800"
                            title="Tandai semua kelas sebagai 'Hadir' dalam 1 klik"
                        >
                            <CheckCheck class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                            <span>Tandai Semua Hadir</span>
                        </Button>
                    </div>

                    <!-- Alert / Panduan Ringkas -->
                    <div class="p-3 bg-blue-50/70 dark:bg-blue-950/30 text-blue-900 dark:text-blue-200 rounded-lg border border-blue-200/80 dark:border-blue-800/50 flex items-start gap-2.5 text-xs">
                        <Info class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                        <p class="leading-relaxed">
                            Default kehadiran otomatis diatur ke <strong>Hadir</strong>. Anda cukup klik <strong>Izin, Sakit,</strong> atau <strong>Alpha</strong> bila guru bersangkutan tidak hadir, serta berikan keterangan bila ada tugas.
                        </p>
                    </div>

                    <!-- Cards Kelas Loop -->
                    <div class="space-y-3 pt-1">
                        <div 
                            v-for="(report, index) in form.reports" 
                            :key="report.jadwal_mapel_id"
                            class="rounded-xl border border-border/80 bg-background/60 hover:bg-background/90 transition-all p-3.5 sm:p-4 shadow-2xs space-y-3"
                            :class="[
                                report.status_kehadiran === 'Hadir' ? 'border-emerald-200/70 dark:border-emerald-900/40' : 
                                report.status_kehadiran ? 'border-primary/40' : 'border-border'
                            ]"
                        >
                            <!-- Kelas Info Row -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1 font-bold text-sm sm:text-base text-foreground bg-primary/10 text-primary px-2.5 py-0.5 rounded-md">
                                            <GraduationCap class="w-3.5 h-3.5" />
                                            {{ report.kelas_nama }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-xs sm:text-sm font-medium text-foreground">
                                            <BookOpen class="w-3.5 h-3.5 text-muted-foreground" />
                                            {{ report.mapel_nama }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-muted-foreground flex items-center gap-1.5 pl-0.5">
                                        <span class="font-medium text-foreground/80">Guru: {{ report.guru_nama }}</span>
                                    </div>
                                </div>

                                <div class="shrink-0 text-right">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded" :class="[
                                        report.status_kehadiran === 'Hadir' ? 'text-emerald-700 bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300' :
                                        report.status_kehadiran === 'Izin' ? 'text-blue-700 bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300' :
                                        report.status_kehadiran === 'Sakit' ? 'text-amber-700 bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300' :
                                        report.status_kehadiran === 'Alpha' ? 'text-rose-700 bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300' :
                                        'text-muted-foreground bg-muted'
                                    ]">
                                        {{ report.status_kehadiran || 'Belum dipilih' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Status Kehadiran Quick Pill Selection -->
                            <div>
                                <label class="text-[11px] font-medium text-muted-foreground block mb-1.5">Status Kehadiran:</label>
                                <div class="grid grid-cols-4 gap-1.5 sm:gap-2">
                                    <button 
                                        v-for="opt in statusOptions"
                                        :key="opt.value"
                                        type="button"
                                        @click="report.status_kehadiran = opt.value"
                                        class="py-1.5 px-1 sm:px-2 rounded-lg text-xs transition-all border flex items-center justify-center cursor-pointer select-none"
                                        :class="report.status_kehadiran === opt.value ? opt.activeClass : opt.inactiveClass"
                                    >
                                        {{ opt.label }}
                                    </button>
                                </div>
                                <InputError :message="form.errors[`reports.${index}.status_kehadiran` as any]" />
                            </div>

                            <!-- Catatan Khusus Per Kelas -->
                            <div class="pt-1">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        v-model="report.keterangan" 
                                        placeholder="Catatan kelas ini (opsional: tugas daring, guru terlambat, dll)..." 
                                        class="w-full text-xs h-8 px-2.5 rounded-lg border border-input bg-muted/20 focus:bg-background focus:ring-1 focus:ring-ring text-foreground placeholder:text-muted-foreground/70"
                                        maxlength="500"
                                    />
                                </div>
                                <InputError :message="form.errors[`reports.${index}.keterangan` as any]" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: FOTO DOKUMENTASI (1X PER SHIFT) -->
                <div class="pt-2 border-t border-border/80">
                    <!-- Existing Photo from previous class report (Locked 1x photo) -->
                    <div class="space-y-3" v-if="existingPhotoUrl">
                        <Label class="text-base font-bold">Foto Bukti Dokumentasi</Label>
                        <div class="p-3.5 bg-blue-50 dark:bg-blue-950/40 text-blue-900 dark:text-blue-200 rounded-xl border border-blue-200 dark:border-blue-800/60 flex items-start gap-3 shadow-xs">
                            <ShieldCheck class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                            <div class="text-sm">
                                <div class="font-semibold text-blue-900 dark:text-blue-100 flex items-center gap-1.5">
                                    <Lock class="w-4 h-4 text-blue-600 dark:text-blue-400 inline" />
                                    Foto Dokumentasi Terlampir
                                </div>
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5 leading-relaxed">
                                    Foto dokumentasi diambil dari pelaporan pertama di jadwal mapel Anda pada hari ini.
                                </p>
                                <p v-if="existingPhotoNote" class="text-xs font-medium text-blue-800 dark:text-blue-200 mt-1.5 flex items-center gap-1 bg-blue-100/70 dark:bg-blue-900/50 px-2 py-0.5 rounded w-fit">
                                    {{ existingPhotoNote }}
                                </p>
                            </div>
                        </div>

                        <div class="border border-border/80 rounded-xl p-3 bg-muted/20 relative overflow-hidden flex flex-col items-center">
                            <img 
                                :src="existingPhotoUrl" 
                                alt="Foto Dokumentasi Sebelumnya" 
                                class="w-full max-h-[380px] object-contain rounded-lg shadow-sm"
                            />
                            <div class="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                <CheckCircle class="w-3.5 h-3.5" /> Foto Dokumentasi Aktif
                            </div>
                        </div>
                    </div>

                    <!-- First report camera capture (on time) -->
                    <div class="space-y-2" v-else-if="!isTerlambat">
                        <div class="flex items-center justify-between">
                            <Label class="text-base font-bold">Foto Bukti Dokumentasi</Label>
                            <span class="text-xs text-muted-foreground font-medium">1 Foto untuk seluruh kelas di shift ini</span>
                        </div>
                        <div 
                            class="border-2 border-dashed border-border rounded-xl flex flex-col items-center justify-center bg-slate-950 relative overflow-hidden transition-all duration-300 w-full" 
                            :class="[
                                cameraOrientation === 'portrait' ? 'h-[460px] sm:h-[520px] w-full' : 'aspect-[16/9] sm:aspect-[4/3] min-h-[300px] w-full',
                                {'min-h-[300px]': !photoUrl && !isCameraActive}
                            ]"
                        >
                            <!-- Mode Orientation Toggle (Top-Left) -->
                            <div v-if="!photoUrl" class="absolute top-3 left-3 z-20">
                                <Button 
                                    type="button" 
                                    variant="secondary" 
                                    size="sm"
                                    @click="toggleOrientation" 
                                    :disabled="isTakingPhoto"
                                    class="bg-black/60 hover:bg-black/80 text-white border border-white/20 backdrop-blur-md shadow-lg rounded-full px-3 py-1.5 h-auto text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                                    :title="cameraOrientation === 'portrait' ? 'Ubah ke Mode Landscape' : 'Ubah ke Mode Potrait'"
                                >
                                    <RectangleVertical v-if="cameraOrientation === 'portrait'" class="w-4 h-4 text-amber-400" />
                                    <RectangleHorizontal v-else class="w-4 h-4 text-cyan-400" />
                                    <span>{{ cameraOrientation === 'portrait' ? 'Potrait' : 'Landscape' }}</span>
                                </Button>
                            </div>

                            <!-- Floating Switch Camera & Mirror Controls (Top-Right) -->
                            <div v-if="isCameraActive" class="absolute top-3 right-3 z-20 flex items-center gap-1.5">
                                <Button 
                                    v-if="currentFacingMode === 'user'"
                                    type="button" 
                                    variant="secondary" 
                                    size="sm"
                                    @click="toggleMirror" 
                                    :disabled="isTakingPhoto"
                                    class="bg-black/60 hover:bg-black/80 text-white border border-white/20 backdrop-blur-md shadow-lg rounded-full px-2.5 py-1.5 h-auto text-xs font-semibold flex items-center gap-1 transition-all cursor-pointer"
                                    :title="isMirrored ? 'Mode Cermin Aktif (klik untuk matikan)' : 'Mode Normal / Tidak Bermirror (klik untuk cermin)'"
                                >
                                    <FlipHorizontal class="w-3.5 h-3.5 text-violet-400" />
                                    <span>{{ isMirrored ? 'Cermin' : 'Normal' }}</span>
                                </Button>

                                <Button 
                                    type="button" 
                                    variant="secondary" 
                                    size="sm"
                                    @click="switchCamera" 
                                    :disabled="isTakingPhoto"
                                    class="bg-black/60 hover:bg-black/80 text-white border border-white/20 backdrop-blur-md shadow-lg rounded-full px-3 py-1.5 h-auto text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                                    title="Ganti Kamera Depan / Belakang"
                                >
                                    <SwitchCamera class="w-4 h-4 text-emerald-400" />
                                    <span>{{ currentFacingMode === 'environment' ? 'Belakang' : 'Depan' }}</span>
                                </Button>
                            </div>

                            <!-- Camera View -->
                            <video 
                                v-show="isCameraActive" 
                                ref="videoRef" 
                                autoplay 
                                playsinline 
                                class="w-full h-full object-contain bg-black transition-transform duration-200"
                                :class="{'scale-x-[-1]': isMirrored && currentFacingMode === 'user'}"
                            ></video>
                            <canvas ref="canvasRef" class="hidden"></canvas>
                            
                            <!-- Preview Image -->
                            <img 
                                v-if="photoUrl" 
                                :src="photoUrl" 
                                class="w-full h-full object-contain bg-black rounded-lg shadow-sm" 
                            />
                            
                            <!-- Initial State -->
                            <div v-if="!isCameraActive && !photoUrl" class="text-center p-6">
                                <Camera class="w-12 h-12 text-muted-foreground mx-auto mb-2 opacity-50" />
                                <p class="text-sm text-muted-foreground mb-4">Ambil foto situasi titik piket atau kelas saat ini</p>
                                <Button type="button" @click="startCamera" class="cursor-pointer">
                                    <Camera class="w-4 h-4 mr-2" /> Buka Kamera
                                </Button>
                            </div>
                            
                            <!-- Camera Controls -->
                            <div v-if="isCameraActive" class="absolute bottom-4 left-0 right-0 flex flex-wrap justify-center items-center gap-2 px-3 z-20">
                                <Button type="button" variant="destructive" size="sm" @click="stopCamera" :disabled="isTakingPhoto" class="h-9 px-3">Batal</Button>
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    size="sm" 
                                    @click="switchCamera" 
                                    :disabled="isTakingPhoto"
                                    class="bg-background/90 backdrop-blur-md border border-border shadow-md h-9 px-3"
                                    title="Ganti Kamera Depan / Belakang"
                                >
                                    <SwitchCamera class="w-4 h-4 mr-1 text-primary" />
                                    <span>Ganti Kamera</span>
                                </Button>
                                <Button type="button" size="sm" @click="takePhoto" :disabled="isTakingPhoto" class="bg-primary hover:bg-primary/90 text-primary-foreground shadow-lg h-9 px-5">
                                    <Camera class="w-4 h-4 mr-1.5" /> {{ isTakingPhoto ? 'Mengompres...' : 'Ambil Foto' }}
                                </Button>
                            </div>
                            
                            <!-- Retake Controls -->
                            <div v-if="photoUrl && !isCameraActive" class="absolute top-3 right-3 bg-background/90 backdrop-blur-sm p-1.5 rounded-lg shadow flex items-center gap-2 z-20">
                                <div class="flex items-center text-green-600 bg-green-100 dark:bg-green-900/30 px-2.5 py-1 rounded-md text-xs font-medium">
                                    <CheckCircle class="w-3.5 h-3.5 mr-1" /> {{ photoSize }}
                                </div>
                                <Button type="button" variant="outline" size="sm" class="h-7 text-xs px-2.5 cursor-pointer" @click="startCamera">
                                    <RefreshCcw class="w-3.5 h-3.5 mr-1" /> Foto Ulang
                                </Button>
                            </div>
                        </div>
                        <InputError :message="form.errors.foto_dokumentasi" />
                    </div>

                    <!-- Late report mode -->
                    <div v-else class="space-y-2">
                        <div class="p-4 bg-yellow-50 text-yellow-800 rounded-lg border border-yellow-200 text-sm">
                            <strong>Mode Terlambat:</strong> Anda tidak diwajibkan mengunggah foto dokumentasi karena pelaporan ini merupakan laporan susulan yang diizinkan oleh Wakasek.
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON BAR -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-border pt-4 mt-6">
                    <div class="text-xs text-muted-foreground text-center sm:text-left">
                        Total <strong>{{ form.reports.length }}</strong> kelas akan disimpan dalam satu laporan shift.
                    </div>
                    <Button 
                        type="submit" 
                        :disabled="form.processing || (!isTerlambat && !existingPhotoUrl && !form.foto_dokumentasi)" 
                        class="w-full sm:w-auto px-7 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center justify-center gap-2 font-semibold text-sm cursor-pointer disabled:opacity-50"
                    >
                        <CheckCircle2 class="w-4 h-4" v-if="!form.processing" />
                        {{ form.processing ? 'Menyimpan Semua...' : `Simpan Semua Laporan (${form.reports.length} Kelas)` }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
