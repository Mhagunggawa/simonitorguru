<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';
import { usePwaInstall } from '@/composables/usePwaInstall';

const { installPwa } = usePwaInstall();
</script>

<template>
    <Head title="Welcome" />

    <div class="relative min-h-screen bg-white overflow-hidden selection:bg-[#79AE6F] selection:text-white">
        <!-- Pola titik-titik (Dot pattern) background -->
        <div class="absolute inset-0 z-0 opacity-[0.15] pointer-events-none" style="background-image: radial-gradient(#79AE6F 2px, transparent 2px); background-size: 32px 32px;"></div>
        
        <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 min-h-screen flex flex-col lg:flex-row items-center justify-between gap-12 lg:gap-16 pt-16 lg:pt-0">
            
            <!-- Kiri: Konten Teks -->
            <div class="w-full lg:w-1/2 animate-in fade-in slide-in-from-left-8 duration-1000 ease-out text-center lg:text-left flex flex-col items-center lg:items-start z-20">
                <div class="mb-6 inline-flex items-center justify-center rounded-full bg-[#79AE6F]/10 px-4 py-2 border border-[#79AE6F]/20 shadow-sm">
                    <span class="text-sm font-bold text-[#79AE6F] tracking-wider uppercase">Sistem Informasi Monitoring Guru</span>
                </div>
                
                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-gray-900 mb-6 leading-[1.1] flex flex-wrap items-center justify-center lg:justify-start gap-3 sm:gap-4">
                    <span>{{ ($page.props as any).settings?.nama_aplikasi || 'Simonitorguru' }}</span>
                    <img 
                        v-if="($page.props as any).settings?.logo_aplikasi" 
                        :src="($page.props as any).settings.logo_aplikasi" 
                        alt="Logo Aplikasi" 
                        class="inline-block h-[1em] w-auto object-contain drop-shadow-sm" 
                    />
                </h1>
                
                <p class="text-lg sm:text-xl text-gray-600 mb-10 leading-relaxed max-w-xl">
                    Platform untuk mendukung pemantauan dan pengelolaan kedisiplinan di <span class="text-[#79AE6F] font-bold">SMKN 1 Gorontalo</span> secara digital dengan lebih efisien dan terukur.
                </p>
                
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
                    <Link
                        v-if="($page.props as any).auth?.user"
                        :href="dashboard()"
                        class="px-8 py-4 bg-[#79AE6F] text-white rounded-xl font-bold text-lg shadow-[0_8px_30px_rgb(121,174,111,0.3)] hover:shadow-[0_8px_30px_rgb(121,174,111,0.5)] hover:bg-[#629458] transition-all duration-300 ease-in-out transform hover:-translate-y-1 w-full sm:w-auto inline-flex items-center justify-center gap-3"
                    >
                        Masuk ke Dashboard
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="px-8 py-4 bg-[#79AE6F] text-white rounded-xl font-bold text-lg shadow-[0_8px_30px_rgb(121,174,111,0.3)] hover:shadow-[0_8px_30px_rgb(121,174,111,0.5)] hover:bg-[#629458] transition-all duration-300 ease-in-out transform hover:-translate-y-1 w-full sm:w-auto inline-flex items-center justify-center gap-3"
                    >
                        Login ke Sistem
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                    </Link>

                    <button
                        type="button"
                        @click="installPwa"
                        class="px-6 py-4 bg-white text-[#79AE6F] border-2 border-[#79AE6F]/40 hover:border-[#79AE6F] rounded-xl font-bold text-lg shadow-sm hover:shadow-[0_8px_25px_rgb(121,174,111,0.2)] hover:bg-[#79AE6F]/5 transition-all duration-300 ease-in-out transform hover:-translate-y-1 w-full sm:w-auto inline-flex items-center justify-center gap-3 cursor-pointer"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                        Install Aplikasi
                    </button>
                </div>
            </div>

            <!-- Kanan: Visual Ilustrasi Geometris / Teknologi -->
            <div class="w-full lg:w-1/2 pb-16 lg:pb-0 relative animate-in fade-in slide-in-from-right-8 duration-1000 ease-out delay-200">
                <div class="relative w-full max-w-lg mx-auto aspect-square flex items-center justify-center group cursor-default">
                    
                    <!-- Latar Belakang Gradasi / Glow Effect -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-[#79AE6F]/20 to-[#79AE6F]/5 rounded-full blur-3xl opacity-70 group-hover:opacity-100 transition-opacity duration-700"></div>

                    <!-- Elemen Geometris Abstrak Melayang -->
                    <div class="absolute top-8 right-8 w-24 h-24 bg-gradient-to-br from-[#79AE6F]/20 to-transparent rounded-full border border-[#79AE6F]/30 backdrop-blur-sm animate-bounce" style="animation-duration: 4s;"></div>
                    <div class="absolute bottom-12 left-6 w-32 h-32 bg-white/60 rounded-2xl border border-[#79AE6F]/20 backdrop-blur-md rotate-12 group-hover:rotate-[24deg] transition-transform duration-700 shadow-xl"></div>
                    <div class="absolute top-1/4 left-10 w-12 h-12 bg-[#79AE6F] rounded-lg rotate-45 opacity-20 group-hover:scale-125 transition-transform duration-500"></div>

                    <!-- Kartu Ilustrasi Utama (Dashboard Mockup) -->
                    <div class="relative z-10 w-[85%] h-[85%] bg-white/80 backdrop-blur-2xl rounded-[2rem] border border-white shadow-[0_20px_60px_rgba(0,0,0,0.08)] overflow-hidden flex flex-col p-8 transition-transform duration-700 group-hover:-translate-y-4 group-hover:shadow-[0_30px_60px_rgba(121,174,111,0.15)]">
                        
                        <!-- Header Mockup -->
                        <div class="flex items-center gap-2 mb-8">
                            <div class="w-3.5 h-3.5 rounded-full bg-[#ff5f56]"></div>
                            <div class="w-3.5 h-3.5 rounded-full bg-[#ffbd2e]"></div>
                            <div class="w-3.5 h-3.5 rounded-full bg-[#27c93f]"></div>
                        </div>

                        <!-- Konten Dashboard Mockup -->
                        <div class="flex-1 flex flex-col gap-6">
                            <!-- Skeleton Header -->
                            <div class="w-3/4 h-8 bg-gray-100 rounded-lg overflow-hidden relative">
                                <div class="absolute inset-0 bg-gradient-to-r from-[#79AE6F]/40 to-[#79AE6F]/10 w-1/3 rounded-r-lg group-hover:w-full transition-all duration-1000 ease-out"></div>
                            </div>
                            
                            <!-- Skeleton Grafik Bar (Analisis Data) -->
                            <div class="flex-1 flex items-end gap-3 pb-2 border-b border-gray-100">
                                <div class="w-full bg-[#79AE6F]/20 h-[30%] rounded-t-md group-hover:h-[45%] transition-all duration-500 delay-100"></div>
                                <div class="w-full bg-[#79AE6F]/40 h-[50%] rounded-t-md group-hover:h-[65%] transition-all duration-500 delay-200"></div>
                                <div class="w-full bg-[#79AE6F]/60 h-[40%] rounded-t-md group-hover:h-[55%] transition-all duration-500 delay-300"></div>
                                <div class="w-full bg-[#79AE6F] h-[80%] rounded-t-md group-hover:h-[95%] transition-all duration-500 delay-400"></div>
                                <div class="w-full bg-[#79AE6F]/80 h-[60%] rounded-t-md group-hover:h-[75%] transition-all duration-500 delay-500"></div>
                            </div>

                            <!-- Skeleton List/Aktivitas -->
                            <div class="space-y-4 mt-2">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-[#79AE6F]/10 flex items-center justify-center shrink-0">
                                        <div class="w-4 h-4 rounded-full bg-[#79AE6F]"></div>
                                    </div>
                                    <div class="w-full space-y-2">
                                        <div class="h-3 bg-gray-200 rounded-full w-2/3"></div>
                                        <div class="h-2 bg-gray-100 rounded-full w-1/3"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-[#79AE6F]/10 flex items-center justify-center shrink-0">
                                        <div class="w-4 h-4 rounded-full bg-[#79AE6F] opacity-50"></div>
                                    </div>
                                    <div class="w-full space-y-2">
                                        <div class="h-3 bg-gray-200 rounded-full w-1/2"></div>
                                        <div class="h-2 bg-gray-100 rounded-full w-1/4"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Overlay Highlight Efek -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/20 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
