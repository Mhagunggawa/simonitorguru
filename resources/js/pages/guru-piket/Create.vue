<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import { Camera, RefreshCcw, CheckCircle } from '@lucide/vue';
import { ref, onBeforeUnmount, onMounted } from 'vue';
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
                title: 'Isi Laporan',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    jadwal_piket_id: props.jadwalPiket.id,
    jam_pelajaran_id: props.currentJam.id,
    jadwal_mapel_id: '',
    status_kehadiran: '',
    foto_dokumentasi: null as File | null,
    keterangan: '',
});

// Camera logic
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const photoUrl = ref<string | null>(null);
const photoSize = ref<string | null>(null);
const stream = ref<MediaStream | null>(null);
const isCameraActive = ref(false);
const isTakingPhoto = ref(false);

const startCamera = async () => {
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });
        if (videoRef.value) {
            videoRef.value.srcObject = stream.value;
        }
        isCameraActive.value = true;
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

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }
    isCameraActive.value = false;
};

const takePhoto = async () => {
    if (!videoRef.value || !canvasRef.value) return;

    isTakingPhoto.value = true;
    try {
        const video = videoRef.value;
        const canvas = canvasRef.value;

        // Set canvas dimensions to match video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // Draw frame to canvas
        const ctx = canvas.getContext('2d');
        if (ctx) {
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Kompresi foto ke target ~100KB agar ringan dan tidak membebani server
            const result = await compressCanvasToTargetSize(canvas, {
                targetSizeKB: 100,
                maxDimension: 960,
                fileName: `pelaporan_${Date.now()}.jpg`
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

const submit = () => {
    if (!form.jadwal_mapel_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Jadwal Belum Dipilih',
            text: 'Silakan pilih jadwal kelas yang dipantau terlebih dahulu.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    if (!form.status_kehadiran) {
        Swal.fire({
            icon: 'warning',
            title: 'Status Kehadiran Belum Dipilih',
            text: 'Silakan pilih status kehadiran guru mapel di dalam kelas.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    if (!props.isTerlambat && !form.foto_dokumentasi) {
        Swal.fire({
            icon: 'warning',
            title: 'Foto Dokumentasi Wajib',
            text: 'Anda wajib mengambil foto dokumentasi langsung di kelas sebelum menyimpan laporan.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    const selectedMapel = props.jadwalMapels.find(m => m.id.toString() === form.jadwal_mapel_id.toString());
    const namaKelas = selectedMapel?.kelas?.nama_kelas || '-';
    const namaMapel = selectedMapel?.mata_pelajaran?.nama_mapel || '-';
    const namaGuru = selectedMapel?.guru?.name || '-';
    const status = form.status_kehadiran;

    let statusBadgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300';
    if (status === 'Izin') {
        statusBadgeClass = 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-300';
    } else if (status === 'Sakit') {
        statusBadgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300';
    } else if (status === 'Alpha' || status === 'Kosong') {
        statusBadgeClass = 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-300';
    }

    Swal.fire({
        title: 'Konfirmasi Simpan Absensi',
        html: `
            <div class="text-left text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3.5 mt-2 space-y-2">
                <div>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Jadwal Kelas:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-100">${namaKelas} - ${namaMapel}</span>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Guru Mapel:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-100">${namaGuru}</span>
                </div>
                <div class="pt-1.5 flex items-center justify-between border-t border-slate-200 dark:border-slate-700">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Status Kehadiran:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${statusBadgeClass}">
                        ${status}
                    </span>
                </div>
            </div>
            <p class="mt-3.5 text-sm text-slate-600 dark:text-slate-300">Apakah Anda yakin ingin menyimpan laporan absensi guru ini?</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Simpan Laporan',
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
    <Head title="Isi Laporan Kehadiran" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:max-w-2xl mx-auto w-full">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">Formulir Pelaporan</h1>
            <Button variant="outline" as-child>
                <Link href="/pelaporan-piket">Cancel</Link>
            </Button>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow dark:border-sidebar-border p-6">
            <div class="mb-6 p-4 bg-muted/50 rounded-lg border border-border">
                <h3 class="font-semibold text-lg mb-2">Informasi Piket</h3>
                <ul class="text-sm space-y-1">
                    <li><span class="text-muted-foreground w-24 inline-block">Titik Lokasi</span> : <strong>{{ jadwalPiket.titik.nama_titik }}</strong></li>
                    <li><span class="text-muted-foreground w-24 inline-block">Waktu</span> : Jam ke-{{ currentJam.urutan_jam }} ({{ currentJam.waktu_mulai.substring(0,5) }} - {{ currentJam.waktu_selesai.substring(0,5) }})</li>
                </ul>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="jadwal_mapel_id">Jadwal Kelas yang Dipantau</Label>
                        <Select v-model="form.jadwal_mapel_id">
                            <SelectTrigger class="h-11 text-base">
                                <SelectValue placeholder="Pilih Jadwal Mapel" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="mapel in jadwalMapels" :key="mapel.id" :value="mapel.id.toString()">
                                    {{ mapel.kelas.nama_kelas }} - {{ mapel.mata_pelajaran.nama_mapel }} (Guru: {{ mapel.guru.name }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.jadwal_mapel_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="status_kehadiran">Status Kehadiran Guru Mapel</Label>
                        <Select v-model="form.status_kehadiran">
                            <SelectTrigger class="h-11 text-base">
                                <SelectValue placeholder="Pilih Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Hadir">Hadir</SelectItem>
                                <SelectItem value="Izin">Izin</SelectItem>
                                <SelectItem value="Sakit">Sakit</SelectItem>
                                <SelectItem value="Alpha">Alpha</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status_kehadiran" />
                    </div>

                    <div class="space-y-2" v-if="!isTerlambat">
                        <Label>Foto Bukti Dokumentasi</Label>
                        <div class="border-2 border-dashed border-border rounded-xl p-4 flex flex-col items-center justify-center bg-muted/20 relative overflow-hidden" :class="{'h-64': !photoUrl && !isCameraActive}">
                            
                            <!-- Camera View -->
                            <video 
                                v-show="isCameraActive" 
                                ref="videoRef" 
                                autoplay 
                                playsinline 
                                class="w-full max-h-[400px] object-contain bg-black rounded-lg"
                            ></video>
                            <canvas ref="canvasRef" class="hidden"></canvas>
                            
                            <!-- Preview Image -->
                            <img 
                                v-if="photoUrl" 
                                :src="photoUrl" 
                                class="w-full max-h-[400px] object-contain rounded-lg shadow-sm" 
                            />
                            
                            <!-- Initial State -->
                            <div v-if="!isCameraActive && !photoUrl" class="text-center">
                                <Camera class="w-12 h-12 text-muted-foreground mx-auto mb-2 opacity-50" />
                                <p class="text-sm text-muted-foreground mb-4">Wajib melampirkan foto langsung</p>
                                <Button type="button" @click="startCamera">
                                    <Camera class="w-4 h-4 mr-2" /> Buka Kamera
                                </Button>
                            </div>
                            
                            <!-- Camera Controls -->
                            <div v-if="isCameraActive" class="absolute bottom-4 left-0 right-0 flex justify-center gap-4 px-4">
                                <Button type="button" variant="destructive" @click="stopCamera" :disabled="isTakingPhoto">Batal</Button>
                                <Button type="button" @click="takePhoto" :disabled="isTakingPhoto" class="bg-primary hover:bg-primary/90 text-primary-foreground shadow-lg px-8">
                                    <Camera class="w-4 h-4 mr-2" /> {{ isTakingPhoto ? 'Mengompres Foto...' : 'Ambil Foto' }}
                                </Button>
                            </div>
                            
                            <!-- Retake Controls -->
                            <div v-if="photoUrl && !isCameraActive" class="absolute top-4 right-4 bg-background/80 backdrop-blur-sm p-1.5 rounded-lg shadow flex items-center gap-2">
                                <div class="flex items-center text-green-600 bg-green-100 dark:bg-green-900/30 px-3 py-1 rounded-md text-sm font-medium">
                                    <CheckCircle class="w-4 h-4 mr-1.5" /> Foto Tersimpan ({{ photoSize }})
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="startCamera">
                                    <RefreshCcw class="w-4 h-4 mr-2" /> Foto Ulang
                                </Button>
                            </div>
                        </div>
                        <InputError :message="form.errors.foto_dokumentasi" />
                    </div>

                    <div v-else class="space-y-2">
                        <div class="p-4 bg-yellow-50 text-yellow-800 rounded-lg border border-yellow-200">
                            <strong>Mode Terlambat:</strong> Anda tidak diwajibkan mengunggah foto dokumentasi karena pelaporan ini merupakan laporan susulan yang diizinkan oleh Wakasek.
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="keterangan">Keterangan (Opsional)</Label>
                        <Textarea 
                            id="keterangan" 
                            v-model="form.keterangan" 
                            placeholder="Tambahkan catatan jika diperlukan..." 
                            class="min-h-[100px]"
                        />
                        <InputError :message="form.errors.keterangan" />
                    </div>
                </div>

                <div class="flex justify-end gap-4 border-t border-border pt-4 mt-8">
                    <Button 
                        type="submit" 
                        :disabled="form.processing || (!isTerlambat && !form.foto_dokumentasi)" 
                        class="w-full sm:w-auto px-8 bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center justify-center gap-2"
                    >
                        <CheckCircle class="w-4 h-4" v-if="!form.processing" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Laporan' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
