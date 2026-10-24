<?php

namespace App\Http\Controllers;

use App\Models\AbsensiUpacara;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AbsensiUpacaraController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        // Get all users with role 'guru' or 'wakasek'
        $teachers = User::whereIn('role', ['guru', 'wakasek'])->orderBy('name', 'asc')->get();

        // Get attendance for the selected date
        $attendances = AbsensiUpacara::where('tanggal', $date)->get()->keyBy('user_id');

        // Map teachers with their attendance status
        $data = $teachers->map(function ($teacher) use ($attendances) {
            $existing = isset($attendances[$teacher->id]);
            $att = $existing ? $attendances[$teacher->id] : null;
            $isHadir = $att ? (bool)$att->is_hadir : false;
            $status = $att ? $att->status : null;
            
            // Backwards compatibility: if is_hadir was true but status was not set
            if ($isHadir && empty($status)) {
                $status = 'Hadir';
            }

            return [
                'user_id' => $teacher->id,
                'name' => $teacher->name,
                'is_hadir' => $status === 'Hadir',
                'is_tidak_hadir' => in_array($status, ['Alpha', 'Sakit', 'Izin']),
                'status' => $status,
                'keterangan' => $att?->keterangan ?? '',
                'is_already_saved' => $existing && !empty($status),
            ];
        });

        return Inertia::render('AbsensiUpacara/Index', [
            'date' => $date,
            'teachers' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.status' => 'nullable|string|in:Hadir,Alpha,Sakit,Izin',
            'attendances.*.keterangan' => 'nullable|string',
        ]);

        $date = $request->date;
        $attendances = $request->attendances;

        DB::transaction(function () use ($date, $attendances) {
            foreach ($attendances as $attendance) {
                $status = $attendance['status'] ?? null;
                
                if ($status) {
                    AbsensiUpacara::updateOrCreate(
                        [
                            'tanggal' => $date,
                            'user_id' => $attendance['user_id'],
                        ],
                        [
                            'is_hadir' => $status === 'Hadir',
                            'status' => $status,
                            'keterangan' => $attendance['keterangan'] ?? null,
                        ]
                    );
                } else {
                    // If status is empty or cleared, remove record if it existed
                    AbsensiUpacara::where('tanggal', $date)
                        ->where('user_id', $attendance['user_id'])
                        ->delete();
                }
            }
        });

        return back()->with('success', 'Absensi upacara berhasil disimpan.');
    }
}
