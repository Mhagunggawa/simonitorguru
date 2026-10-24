<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage<any>();

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-slate-50/80 dark:bg-slate-900 p-6 md:p-10 relative overflow-hidden"
    >
        <!-- Decorative subtle background glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-[#79AE6F] rounded-full mix-blend-multiply filter blur-[120px] opacity-[0.08] pointer-events-none"></div>

        <div class="w-full max-w-[420px] relative z-10">
            <div class="flex flex-col gap-8 bg-white dark:bg-slate-950 px-8 py-10 sm:px-10 rounded-[1.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 dark:border-slate-800">
                <div class="flex flex-col items-center gap-5">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-4 font-medium"
                    >
                        <div
                            class="flex h-[72px] w-[72px] items-center justify-center rounded-2xl bg-white dark:bg-slate-800 p-2 overflow-hidden shadow-[0_4px_20px_rgb(121,174,111,0.15)] border border-[#79AE6F]/10 transition-transform duration-300 hover:scale-105"
                        >
                            <img v-if="page.props.settings?.logo_aplikasi" :src="page.props.settings.logo_aplikasi" class="w-full h-full object-contain" />
                            <AppLogoIcon v-else class="size-10 fill-current text-[#79AE6F] dark:text-[#79AE6F]" />
                        </div>
                        <span class="text-[22px] font-extrabold text-slate-800 dark:text-slate-100 uppercase tracking-tight">
                            {{ page.props.settings?.nama_aplikasi}}
                        </span>
                    </Link>
                    <div class="space-y-1.5 text-center">
                        <h1 class="text-xl font-bold text-slate-800">{{ title }}</h1>
                        <p class="text-center text-sm font-medium text-slate-500">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
