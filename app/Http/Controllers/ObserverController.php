<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;

class ObserverController extends Controller
{
    public function index()
    {
        // Get all users who are guru, wakasek or kepsek
        $teachers = User::whereIn('role', ['guru', 'wakasek', 'kepsek'])
            ->orderBy('name')
            ->get();

        $observers = $teachers->where('is,_observer', true)->values();
        
        return Inertia::render('Observer/Index', [
            'teachers' => $teachers,
            'observers' => $observers,
        ]);
    }

    public function toggleObserver(Request $request, User $user)
    {
        \Illuminate\Support\Facades\Log::info('toggleObserver payload:', $request->all());
        
        $request->validate([
            'is_observer' => 'required'
        ]);

        $isObserver = filter_var($request->is_observer, FILTER_VALIDATE_BOOLEAN);
        $user->is_observer = $isObserver;
        
        // Jika status observer dicabut, maka hapus semua guru yang di amatinya
        if (!$isObserver) {
            User::where('observer_id', $user->id)->update(['observer_id' => null]);
        }
        
        $user->save();
        
        \Illuminate\Support\Facades\Log::info('User observer status saved:', ['id' => $user->id, 'is_observer' => $user->is_observer]);

        return redirect()->back()->with('success', 'Status observer berhasil diperbarui.');
    }

    public function assignTeachers(Request $request, User $user)
    {
        \Illuminate\Support\Facades\Log::info('assignTeachers payload:', ['user_id' => $user->id, 'payload' => $request->all()]);

        $request->validate([
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id'
        ]);

        // Pastikan user ini adalah observer
        if (!$user->is_observer) {
            return redirect()->back()->with('error', 'User ini bukan observer.');
        }

        // Hapus penugasan sebelumnya untuk observer ini
        User::where('observer_id', $user->id)->update(['observer_id' => null]);

        // Assign guru-guru baru ke observer ini (kecualikan kepsek dan diri sendiri)
        if ($request->has('teacher_ids') && is_array($request->teacher_ids) && count($request->teacher_ids) > 0) {
            $validTeacherIds = User::whereIn('id', $request->teacher_ids)
                ->where('role', '!=', 'kepsek')
                ->where('id', '!=', $user->id)
                ->pluck('id');

            User::whereIn('id', $validTeacherIds)->update(['observer_id' => $user->id]);
        }

        return redirect()->back()->with('success', 'Anggota observasi berhasil diperbarui.');
    }
}
