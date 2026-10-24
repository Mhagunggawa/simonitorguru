import { ref, onMounted } from 'vue';
import Swal from 'sweetalert2';

const deferredPrompt = ref<any>(null);
const isInstalled = ref(false);
const isIos = ref(false);

if (typeof window !== 'undefined') {
    // Check if already in standalone mode
    if (
        window.matchMedia('(display-mode: standalone)').matches ||
        (window.navigator as any).standalone === true
    ) {
        isInstalled.value = true;
    }

    // Check if iOS
    const userAgent = window.navigator.userAgent.toLowerCase();
    isIos.value = /iphone|ipad|ipod/.test(userAgent);

    window.addEventListener('beforeinstallprompt', (e: Event) => {
        e.preventDefault();
        deferredPrompt.value = e;
    });

    window.addEventListener('appinstalled', () => {
        isInstalled.value = true;
        deferredPrompt.value = null;
        Swal.fire({
            icon: 'success',
            title: 'Berhasil Dipasang!',
            text: 'Aplikasi Simonitorguru berhasil dipasang di layar utama perangkat Anda.',
            timer: 3000,
            showConfirmButton: false,
        });
    });
}

export function usePwaInstall() {
    const installPwa = async () => {
        if (isInstalled.value) {
            Swal.fire({
                icon: 'info',
                title: 'Sudah Terpasang',
                text: 'Aplikasi Simonitorguru sudah terpasang di perangkat Anda.',
                confirmButtonColor: '#79AE6F',
            });
            return;
        }

        if (deferredPrompt.value) {
            deferredPrompt.value.prompt();
            const choiceResult = await deferredPrompt.value.userChoice;
            if (choiceResult.outcome === 'accepted') {
                deferredPrompt.value = null;
                isInstalled.value = true;
            }
            return;
        }

        if (isIos.value) {
            Swal.fire({
                title: 'Pasang di iPhone / iPad',
                html: `
                    <div style="text-align: left; font-size: 14px; line-height: 1.6;">
                        <p style="margin-bottom: 8px;">Ikuti langkah mudah berikut di browser Safari:</p>
                        <ol style="padding-left: 20px; margin-bottom: 12px;">
                            <li>Tekan tombol <b>Bagikan (Share)</b> <span style="font-size: 18px;">⎋</span> di bilah bawah browser Safari.</li>
                            <li>Gulir ke bawah dan pilih <b>"Tambahkan ke Layar Utama" (Add to Home Screen)</b>.</li>
                            <li>Tekan <b>"Tambah" (Add)</b> di pojok kanan atas.</li>
                        </ol>
                        <p style="color: #64748b; font-size: 12px;">Aplikasi akan langsung muncul di layar depan iPhone Anda!</p>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#79AE6F',
            });
            return;
        }

        // Fallback for Chrome / Edge / Desktop where prompt is in browser bar
        Swal.fire({
            title: 'Pasang Aplikasi di HP',
            html: `
                <div style="text-align: left; font-size: 14px; line-height: 1.6;">
                    <p style="margin-bottom: 8px;">Untuk memasang aplikasi Simonitorguru langsung di layar utama:</p>
                    <ol style="padding-left: 20px; margin-bottom: 12px;">
                        <li>Tekan menu <b>titik tiga (⋮)</b> di pojok kanan atas browser Chrome/Edge.</li>
                        <li>Pilih <b>"Pasang Aplikasi"</b> atau <b>"Tambahkan ke Layar Utama"</b>.</li>
                        <li>Konfirmasi pemasangan.</li>
                    </ol>
                    <p style="color: #64748b; font-size: 12px;">Aplikasi akan terpasang ringan tanpa memakan memori!</p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#79AE6F',
        });
    };

    return {
        deferredPrompt,
        isInstalled,
        isIos,
        installPwa,
    };
}
