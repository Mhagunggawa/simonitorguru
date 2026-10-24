import os

controller_path = "app/Http/Controllers/KinerjaController.php"
vue_path = "resources/js/pages/Kinerja/FormTahap1.vue"
vue_index_path = "resources/js/pages/Kinerja/Index.vue"

# 1. Update Controller
controller_code = """<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\\User;
use App\Models\\Kinerja;
use App\Models\\KinerjaFokus;
use Inertia\\Inertia;
use Illuminate\\Support\\Facades\\Auth;
use Illuminate\\Support\\Carbon;

class KinerjaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->is_observer) {
            abort(403, 'Anda bukan observer.');
        }

        // Ambil guru yang ditugaskan ke observer ini
        $teachers = User::where('observer_id', $user->id)
            ->with(['kinerjas' => function($q) use ($user) {
                $q->where('observer_id', $user->id);
            }])->get();

        return Inertia::render('Kinerja/Index', [
            'teachers' => $teachers
        ]);
    }

    public function createTahap1(User $guru)
    {
        $observer = Auth::user();
        if (!$observer->is_observer || $guru->observer_id !== $observer->id) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $kinerja = Kinerja::with('fokuses')->where('guru_id', $guru->id)->where('observer_id', $observer->id)->first();

        return Inertia::render('Kinerja/FormTahap1', [
            'guru' => $guru,
            'kinerja' => $kinerja,
            'currentYear' => Carbon::now()->year
        ]);
    }

    public function storeTahap1(Request $request, User $guru)
    {
        $observer = Auth::user();
        if (!$observer->is_observer || $guru->observer_id !== $observer->id) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $validated = $request->validate([
            'tempat' => 'required|string',
            'tanggal' => 'required|date',
            'waktu' => 'required|string',
            'rekomendasi' => 'nullable|string',
            'video_link' => 'nullable|url',
            'fokuses' => 'array|min:1',
            'fokuses.*.nama_fokus' => 'required|string',
            'fokuses.*.perilaku_dianjurkan' => 'array',
            'fokuses.*.perilaku_dihindari' => 'array',
            'fokuses.*.penilaian' => 'nullable|integer|in:1,2,3',
            'fokuses.*.catatan' => 'nullable|string',
        ]);

        $kinerja = Kinerja::updateOrCreate(
            ['guru_id' => $guru->id, 'observer_id' => $observer->id],
            [
                'tempat' => $validated['tempat'],
                'periode' => 'Januari-Desember ' . Carbon::now()->year,
                'tanggal' => $validated['tanggal'],
                'waktu' => $validated['waktu'],
                'rekomendasi' => $validated['rekomendasi'] ?? null,
                'video_link' => $validated['video_link'] ?? null,
                'tahap' => 1
            ]
        );

        // Hapus fokus lama, insert yang baru
        $kinerja->fokuses()->delete();

        foreach ($validated['fokuses'] as $f) {
            $kinerja->fokuses()->create([
                'nama_fokus' => $f['nama_fokus'],
                'perilaku_dianjurkan' => $f['perilaku_dianjurkan'],
                'perilaku_dihindari' => $f['perilaku_dihindari'],
                'penilaian' => $f['penilaian'] ?? null,
                'catatan' => $f['catatan'] ?? null,
            ]);
        }

        return redirect()->route('kinerja.index')->with('success', 'Penilaian Kinerja Tahap 1 berhasil disimpan.');
    }
}
"""
with open(controller_path, "w") as f:
    f.write(controller_code)

# 2. Create Vue Index
os.makedirs("resources/js/pages/Kinerja", exist_ok=True)
vue_index_code = """<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineProps<{
    teachers: any[];
}>();
</script>

<template>
    <AppLayout>
        <Head title="Observasi Kinerja" />
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Observasi Kinerja</h1>
                <p class="text-muted-foreground mt-1">Daftar guru yang Anda amati.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card v-for="teacher in teachers" :key="teacher.id">
                    <CardHeader>
                        <CardTitle>{{ teacher.name }}</CardTitle>
                        <CardDescription>Tahap saat ini: {{ teacher.kinerjas?.length ? 'Tahap ' + teacher.kinerjas[0].tahap : 'Belum dimulai' }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Link :href="`/kinerja/${teacher.id}/tahap-1`">
                            <Button class="w-full">Isi Kinerja Tahap 1</Button>
                        </Link>
                    </CardContent>
                </Card>
                <div v-if="teachers.length === 0" class="col-span-full text-center p-12 border border-dashed rounded-lg text-muted-foreground">
                    Belum ada guru yang ditugaskan kepada Anda.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
"""
with open(vue_index_path, "w") as f:
    f.write(vue_index_code)

print("Kinerja scaffold ready.")
