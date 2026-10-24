<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {computed} from 'vue';

interface AppSettings {
    logo_aplikasi: string;
    nama_aplikasi: string;
} 
const name = usePage().props.name;
const settings = computed(() => usePage().props.settings as AppSettings); ;
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md text-sidebar-primary-foreground"
    >
        <img :src="settings.logo_aplikasi" alt="Logo Aplikasi" class="h-20 w-20 object-contain "  />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">
            {{ settings.nama_aplikasi }}
        </span>
    </div>
</template>
