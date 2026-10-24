<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    Button
} from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Camera,
    Clock,
    CheckCircle2,
    AlertCircle,
    RotateCcw
} from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';
import { ref, onBeforeUnmount } from 'vue';
import { compressCanvasToTargetSize } from '@/lib/imageCompressor';

const props = defineProps<{
    serverTime: string;
    isOpen: boolean;
    alreadySubmitted: boolean;
    currentDate: string;
    hasJadwal: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
            {
                title: 'Absensi GMS',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    foto_bukti: null as File | null,
});

/*
|--------------------------------------------------------------------------
| State Kamera
|--------------------------------------------------------------------------
*/

const videoElement = ref<HTMLVideoElement | null>(null);
const canvasElement = ref<HTMLCanvasElement | null>(null);

const imagePreview = ref<string | null>(null);
const photoSize = ref<string | null>(null);

const cameraActive = ref(false);
const isTakingPhoto = ref(false);

const cameraStream = ref<MediaStream | null>(null);

const startCamera = async () => {
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            Swal.fire(
                'Tidak Didukung',
                'Browser Anda tidak mendukung akses kamera.',
                'error'
            );

            return;
        }
        stopCamera();

        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: {
                    ideal: 'user'
                },
                width: {
                    ideal: 1280
                },
                height: {
                    ideal: 1920
                }
            },
            audio: false
        });

        cameraStream.value = stream;
        cameraActive.value = true;
        setTimeout(() => {
            if (videoElement.value) {
                videoElement.value.srcObject = stream;
            }
        }, 100);

    } catch (error) {
        console.error('Error kamera:', error);

        Swal.fire({
            title: 'Kamera Tidak Dapat Dibuka',
            text: 'Pastikan Anda memberikan izin akses kamera kepada browser.',
            icon: 'error',
            confirmButtonText: 'Mengerti'
        });
    }
};

const stopCamera = () => {
    if (cameraStream.value) {
        cameraStream.value.getTracks().forEach((track) => {
            track.stop();
        });

        cameraStream.value = null;
    }

    if (videoElement.value) {
        videoElement.value.srcObject = null;
    }

    cameraActive.value = false;
};

const takePhoto = async () => {
    if (!videoElement.value || !canvasElement.value) {
        return;
    }

    isTakingPhoto.value = true;

    try {
        const video = videoElement.value;
        const canvas = canvasElement.value;

        const width = video.videoWidth;
        const height = video.videoHeight;

        if (!width || !height) {
            Swal.fire(
                'Kamera Belum Siap',
                'Tunggu beberapa saat sampai kamera siap.',
                'warning'
            );

            isTakingPhoto.value = false;
            return;
        }

        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext('2d');

        if (!context) {
            isTakingPhoto.value = false;
            return;
        }
        // Mengambil foto sesuai orientasi asli kamera (tidak di-mirror)
        context.drawImage(
            video,
            0,
            0,
            width,
            height
        );

        // Kompresi foto ke target ~100KB agar ringan dan tidak membebani server
        const result = await compressCanvasToTargetSize(canvas, {
            targetSizeKB: 100,
            maxDimension: 960,
            fileName: `absensi-gms-${Date.now()}.jpg`
        });

        if (imagePreview.value) {
            URL.revokeObjectURL(imagePreview.value);
        }

        form.foto_bukti = result.file;
        imagePreview.value = result.previewUrl;
        photoSize.value = result.formattedSize;

        stopCamera();
    } catch (error) {
        console.error('Error proses foto:', error);
        Swal.fire(
            'Gagal',
            'Foto tidak dapat diproses.',
            'error'
        );
    } finally {
        isTakingPhoto.value = false;
    }
};

const retakePhoto = async () => {
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }

    imagePreview.value = null;
    photoSize.value = null;
    form.foto_bukti = null;

    await startCamera();
};

const submit = () => {
    if (!form.foto_bukti) {
        Swal.fire(
            'Error',
            'Silakan ambil foto bukti terlebih dahulu.',
            'error'
        );

        return;
    }

    form.post('/absensi-gms', {
        forceFormData: true,

        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Absensi GMS Anda telah dicatat.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                router.visit('/dashboard');
            });
        },

        onError: () => {
            Swal.fire(
                'Gagal!',
                'Terjadi kesalahan saat menyimpan data.',
                'error'
            );
        }
    });
};


onBeforeUnmount(() => {
    stopCamera();

    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }
});
</script>

<template>
    <Head title="Absensi GMS" />

    <div
        class="flex flex-col items-center justify-center p-4 md:p-8 flex-1 w-full bg-slate-50/50 dark:bg-transparent"
    >
        <Card class="w-full max-w-md shadow-lg border-primary/10">

            <!-- HEADER -->
            <CardHeader class="text-center pb-2">

                <div
                    class="mx-auto bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mb-4"
                >
                    <Camera class="w-8 h-8 text-primary" />
                </div>

                <CardTitle class="text-2xl font-bold">
                    Absensi GMS
                </CardTitle>

                <CardDescription class="text-base">
                    {{ currentDate }}
                </CardDescription>

            </CardHeader>


            <CardContent class="pt-6">
                <div
                    v-if="alreadySubmitted"
                    class="flex flex-col items-center justify-center space-y-4 py-8 text-center bg-green-50 dark:bg-green-950/20 rounded-xl border border-green-200 dark:border-green-900"
                >

                    <CheckCircle2 class="w-16 h-16 text-green-500" />

                    <div>
                        <h3
                            class="text-lg font-semibold text-green-700 dark:text-green-400"
                        >
                            Absensi Selesai
                        </h3>

                        <p
                            class="text-sm text-green-600 dark:text-green-500 mt-1"
                        >
                            Anda sudah melakukan absensi GMS hari ini.
                        </p>
                    </div>

                    <Button
                        as-child
                        variant="outline"
                        class="mt-4"
                    >
                        <Link href="/dashboard">
                            Kembali ke Dashboard
                        </Link>
                    </Button>

                </div>
                <div
                    v-else-if="!hasJadwal"
                    class="flex flex-col items-center justify-center space-y-4 py-8 text-center bg-slate-50 dark:bg-slate-900/20 rounded-xl border border-slate-200 dark:border-slate-800"
                >

                    <AlertCircle class="w-16 h-16 text-slate-500" />

                    <div>

                        <h3
                            class="text-lg font-semibold text-slate-700 dark:text-slate-400"
                        >
                            Tidak Ada Jadwal
                        </h3>

                        <p
                            class="text-sm text-slate-600 dark:text-slate-500 mt-1"
                        >
                            Anda tidak memiliki jadwal giliran Absensi GMS pada hari ini.
                        </p>

                    </div>

                    <Button
                        as-child
                        variant="outline"
                        class="mt-4"
                    >
                        <Link href="/dashboard">
                            Kembali ke Dashboard
                        </Link>
                    </Button>

                </div>
                <div
                    v-else-if="!isOpen"
                    class="flex flex-col items-center justify-center space-y-4 py-8 text-center bg-amber-50 dark:bg-amber-950/20 rounded-xl border border-amber-200 dark:border-amber-900"
                >

                    <Clock class="w-16 h-16 text-amber-500" />

                    <div>

                        <h3
                            class="text-lg font-semibold text-amber-700 dark:text-amber-400"
                        >
                            Sesi Ditutup
                        </h3>

                        <p
                            class="text-sm text-amber-600 dark:text-amber-500 mt-1"
                        >
                            Absensi GMS hanya dapat dilakukan antara pukul
                            07:15 - 08:00.
                        </p>

                        <p
                            class="text-xs font-mono text-amber-600/70 dark:text-amber-500/70 mt-2"
                        >
                            Waktu server saat ini:
                            {{ serverTime }}
                        </p>

                    </div>

                    <Button
                        as-child
                        variant="outline"
                        class="mt-4"
                    >
                        <Link href="/dashboard">
                            Kembali ke Dashboard
                        </Link>
                    </Button>

                </div>
                <form
                    v-else
                    @submit.prevent="submit"
                    class="space-y-6"
                >

                    <!-- INFO -->
                    <div
                        class="bg-blue-50 dark:bg-blue-950/30 p-4 rounded-lg flex gap-3 text-blue-800 dark:text-blue-300 items-start border border-blue-100 dark:border-blue-900"
                    >

                        <AlertCircle
                            class="w-5 h-5 flex-shrink-0 mt-0.5"
                        />

                        <div class="text-sm leading-relaxed">

                            <p class="font-medium mb-1">
                                Panduan Absensi GMS
                            </p>

                            <ul
                                class="list-disc list-inside space-y-1 ml-1 opacity-90"
                            >
                                <li>
                                    Pastikan wajah terlihat jelas.
                                </li>

                                <li>
                                    Gunakan seragam yang sesuai.
                                </li>

                                <li>
                                    Izinkan akses kamera jika diminta browser.
                                </li>
                            </ul>

                        </div>

                    </div>
                    <div class="space-y-4">
                        <Label class="text-base font-semibold">
                            Foto Bukti (Selfie)
                        </Label>
                        <div
                            class="relative w-full aspect-[3/4] bg-slate-100 dark:bg-slate-800 rounded-xl overflow-hidden border-2 border-dashed border-slate-300 dark:border-slate-600"
                        >
                            <video
                                v-if="cameraActive"
                                ref="videoElement"
                                autoplay
                                playsinline
                                muted
                                class="absolute inset-0 w-full h-full object-cover"
                            ></video>

                            <img
                                v-else-if="imagePreview"
                                :src="imagePreview"
                                class="absolute inset-0 w-full h-full object-cover"
                                alt="Preview Foto Absensi"
                            />

                            <!-- Badge Status & Ukuran Foto -->
                            <div
                                v-if="imagePreview && !cameraActive"
                                class="absolute top-3 right-3 bg-background/90 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 border border-emerald-500/20 z-10"
                            >
                                <CheckCircle2 class="w-4 h-4 text-emerald-500" />
                                Foto Tersimpan ({{ photoSize }})
                            </div>

                            <div
                                v-else
                                class="absolute inset-0 flex flex-col items-center justify-center text-slate-500 dark:text-slate-400"
                            >

                                <Camera class="w-16 h-16 mb-4" />

                                <p class="font-medium text-center px-4">
                                    Kamera belum dibuka
                                </p>

                                <p class="text-sm text-center mt-1 px-6">
                                    Tekan tombol di bawah untuk membuka kamera
                                </p>

                            </div>
                            <canvas
                                ref="canvasElement"
                                class="hidden"
                            ></canvas>

                        </div>

                        <div class="flex gap-3">
                            <Button
                                v-if="!cameraActive && !imagePreview"
                                type="button"
                                class="w-full h-12 rounded-xl"
                                @click="startCamera"
                            >
                                <Camera class="w-5 h-5 mr-2" />

                                Buka Kamera
                            </Button>

                            <Button
                                v-if="cameraActive"
                                type="button"
                                class="w-full h-12 rounded-xl"
                                :disabled="isTakingPhoto"
                                @click="takePhoto"
                            >
                                <Camera class="w-5 h-5 mr-2" />

                                {{
                                    isTakingPhoto
                                        ? 'Mengompres Foto...'
                                        : 'Ambil Foto'
                                }}
                            </Button>

                            <Button
                                v-if="imagePreview && !cameraActive"
                                type="button"
                                variant="outline"
                                class="w-full h-12 rounded-xl"
                                @click="retakePhoto"
                            >
                                <RotateCcw class="w-5 h-5 mr-2" />

                                Ambil Ulang
                            </Button>

                        </div>


                        <InputError
                            :message="form.errors.foto_bukti"
                        />

                    </div>
                    <Button
                        type="submit"
                        class="w-full text-lg h-12 rounded-xl"
                        :disabled="
                            form.processing ||
                            !form.foto_bukti ||
                            cameraActive
                        "
                    >
                        {{
                            form.processing
                                ? 'Mengirim...'
                                : 'Kirim Absensi GMS'
                        }}
                    </Button>

                </form>

            </CardContent>

        </Card>
    </div>
</template>