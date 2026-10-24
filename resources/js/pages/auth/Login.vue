<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Selamat Datang',
        description: 'Silakan masuk menggunakan email dan password Anda.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5 mt-2"
    >
        <div class="grid gap-5">
            <!-- Email Input -->
            <div class="grid gap-2">
                <Label for="email" class="text-sm font-semibold text-slate-700">Alamat Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="nama@email.com"
                    class="h-12 rounded-xl border-slate-200 focus:border-[#79AE6F] focus:ring focus:ring-[#79AE6F]/20 transition-all bg-slate-50/50 focus:bg-white px-4"
                />
                <InputError :message="errors.email" />
            </div>

            <!-- Password Input -->
            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-sm font-semibold text-slate-700">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm font-medium text-[#79AE6F] hover:text-[#5C8B53] transition-colors"
                        :tabindex="5"
                    >
                        Lupa password?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Masukkan password"
                    class="h-12 rounded-xl border-slate-200 focus:border-[#79AE6F] focus:ring focus:ring-[#79AE6F]/20 transition-all bg-slate-50/50 focus:bg-white px-4"
                />
                <InputError :message="errors.password" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between mt-1">
                <Label for="remember" class="flex items-center space-x-3 cursor-pointer group">
                    <Checkbox id="remember" name="remember" :tabindex="3" class="data-[state=checked]:bg-[#79AE6F] data-[state=checked]:border-[#79AE6F] rounded text-white" />
                    <span class="text-sm font-medium text-slate-600 group-hover:text-slate-900 transition-colors">Ingat saya</span>
                </Label>
            </div>

            <!-- Submit Button -->
            <Button
                type="submit"
                class="mt-2 h-12 w-full rounded-xl bg-[#79AE6F] hover:bg-[#629458] text-white font-bold text-[15px] shadow-[0_6px_20px_rgb(121,174,111,0.25)] transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                <span v-else>Masuk ke Sistem</span>
            </Button>
        </div>
    </Form>
</template>
