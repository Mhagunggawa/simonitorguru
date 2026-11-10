<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { usePanduanModal } from '@/composables/usePanduanModal';
import { BookOpen, Download, ExternalLink, X, FileText, Info } from '@lucide/vue';

const { isPanduanOpen, closePanduan } = usePanduanModal();
const pdfUrl = '/Panduan_SI-Monitor_Guru_Final.pdf';

const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && isPanduanOpen.value) {
        closePanduan();
    }
};

watch(isPanduanOpen, (val) => {
    if (typeof document !== 'undefined') {
        if (val) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
});

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isPanduanOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-2 sm:p-4 backdrop-blur-xs"
                @click.self="closePanduan"
            >
                <div
                    class="relative flex h-[92vh] sm:h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card shadow-2xl text-card-foreground"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="panduan-modal-title"
                >
                    <!-- Header Modal -->
                    <div class="flex items-center justify-between border-b border-border bg-muted/40 px-4 py-3 sm:px-6 sm:py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                <BookOpen class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 id="panduan-modal-title" class="text-base sm:text-lg font-bold leading-tight text-foreground flex items-center gap-2">
                                    Panduan Penggunaan
                                    <span class="hidden sm:inline-block rounded-md bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">
                                        SI-Monitor Guru
                                    </span>
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    Petunjuk operasional sistem monitoring dan absensi sekolah
                                </p>
                            </div>
                        </div>

                        <!-- Header Action Buttons -->
                        <div class="flex items-center gap-2">
                            <!-- Tombol Buka Tab Baru -->
                            <a
                                :href="pdfUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-border bg-background hover:bg-muted text-foreground transition-colors cursor-pointer"
                                title="Buka PDF di tab baru"
                            >
                                <ExternalLink class="w-3.5 h-3.5" />
                                <span>Buka Tab Baru</span>
                            </a>

                            <!-- Tombol Unduh PDF -->
                            <a
                                :href="pdfUrl"
                                download="Panduan_SI-Monitor_Guru_Final.pdf"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-primary-foreground hover:bg-primary/90 transition-colors shadow-xs cursor-pointer"
                                title="Unduh file PDF panduan"
                            >
                                <Download class="w-3.5 h-3.5" />
                                <span class="hidden xs:inline">Unduh PDF</span>
                            </a>

                            <!-- Tombol Tutup -->
                            <button
                                type="button"
                                @click="closePanduan"
                                class="rounded-lg p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors cursor-pointer"
                                title="Tutup (Esc)"
                                aria-label="Tutup modal panduan"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Mobile notice strip -->
                    <div class="flex items-center justify-between gap-2 border-b border-border bg-blue-50/60 dark:bg-blue-950/20 px-4 py-1.5 text-[11px] text-blue-900 dark:text-blue-200 sm:hidden">
                        <div class="flex items-center gap-1.5">
                            <Info class="h-3.5 w-3.5 shrink-0 text-blue-600 dark:text-blue-400" />
                            <span>Gulir layar untuk membaca panduan</span>
                        </div>
                        <a
                            :href="pdfUrl"
                            target="_blank"
                            class="font-semibold underline shrink-0 hover:text-blue-700"
                        >
                            Buka Penuh
                        </a>
                    </div>

                    <!-- PDF Viewer Body -->
                    <div class="relative flex-1 w-full bg-slate-100 dark:bg-slate-900 overflow-hidden">
                        <iframe
                            :src="`${pdfUrl}#toolbar=1&navpanes=0&view=FitH`"
                            class="h-full w-full border-0"
                            title="Pratinjau Dokumen Panduan SI-Monitor Guru"
                        >
                            <!-- Fallback for browsers that don't support iframes for PDF -->
                            <div class="flex h-full flex-col items-center justify-center p-6 text-center">
                                <FileText class="h-12 w-12 text-muted-foreground mb-3" />
                                <h4 class="text-base font-semibold">Peramban Anda Tidak Mendukung Pratinjau PDF</h4>
                                <p class="text-sm text-muted-foreground mt-1 mb-4 max-w-md">
                                    Silakan unduh berkas panduan atau buka langsung di tab baru untuk membaca.
                                </p>
                                <div class="flex gap-2">
                                    <a
                                        :href="pdfUrl"
                                        download="Panduan_SI-Monitor_Guru_Final.pdf"
                                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground"
                                    >
                                        <Download class="h-4 w-4" />
                                        Unduh Berkas Panduan
                                    </a>
                                </div>
                            </div>
                        </iframe>
                    </div>

                    <!-- Footer Modal -->
                    <div class="flex items-center justify-between border-t border-border bg-muted/40 px-4 py-2.5 sm:px-6">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <FileText class="h-3.5 w-3.5" />
                            <span class="font-medium text-foreground">Panduan_SI-Monitor_Guru_Final.pdf</span>
                            <span class="hidden sm:inline">• Dokumen Resmi</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="closePanduan"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border border-border bg-background hover:bg-muted text-foreground transition-colors cursor-pointer"
                            >
                                Tutup
                            </button>
                            <a
                                :href="pdfUrl"
                                download="Panduan_SI-Monitor_Guru_Final.pdf"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-primary-foreground hover:bg-primary/90 transition-colors cursor-pointer"
                            >
                                <Download class="w-3.5 h-3.5" />
                                <span>Unduh</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
