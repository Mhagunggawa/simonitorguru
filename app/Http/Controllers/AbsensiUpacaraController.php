<?php

namespace App\Http\Controllers;

use App\Models\AbsensiUpacara;
use App\Models\PegawaiAdministrasi;
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

        // Get all pegawai administrasi
        $pegawais = PegawaiAdministrasi::orderBy('nama', 'asc')->get();

        // Get attendance for the selected date
        $attendancesTeachers = AbsensiUpacara::where('tanggal', $date)->whereNotNull('user_id')->get()->keyBy('user_id');
        $attendancesPegawai = AbsensiUpacara::where('tanggal', $date)->whereNotNull('pegawai_administrasi_id')->get()->keyBy('pegawai_administrasi_id');

        // Map teachers with their attendance status
        $dataTeachers = $teachers->map(function ($teacher) use ($attendancesTeachers) {
            $existing = isset($attendancesTeachers[$teacher->id]);
            $att = $existing ? $attendancesTeachers[$teacher->id] : null;
            $isHadir = $att ? (bool)$att->is_hadir : false;
            $status = $att ? $att->status : null;
            
            if ($isHadir && empty($status)) {
                $status = 'Hadir';
            }

            return [
                'id' => $teacher->id,
                'user_id' => $teacher->id,
                'pegawai_administrasi_id' => null,
                'id_ref' => 'guru-' . $teacher->id,
                'type' => 'guru',
                'type_label' => $teacher->role === 'wakasek' ? 'Wakasek' : 'Guru',
                'name' => $teacher->name,
                'is_hadir' => $status === 'Hadir',
                'is_tidak_hadir' => in_array($status, ['Alpha', 'Sakit', 'Izin']),
                'status' => $status,
                'keterangan' => $att?->keterangan ?? '',
                'is_already_saved' => $existing && !empty($status),
            ];
        });

        // Map pegawai administrasi with their attendance status (placed below teachers)
        $dataPegawai = $pegawais->map(function ($pegawai) use ($attendancesPegawai) {
            $existing = isset($attendancesPegawai[$pegawai->id]);
            $att = $existing ? $attendancesPegawai[$pegawai->id] : null;
            $isHadir = $att ? (bool)$att->is_hadir : false;
            $status = $att ? $att->status : null;
            
            if ($isHadir && empty($status)) {
                $status = 'Hadir';
            }

            return [
                'id' => $pegawai->id,
                'user_id' => null,
                'pegawai_administrasi_id' => $pegawai->id,
                'id_ref' => 'pegawai-' . $pegawai->id,
                'type' => 'pegawai',
                'type_label' => 'Pegawai Administrasi',
                'name' => $pegawai->nama,
                'is_hadir' => $status === 'Hadir',
                'is_tidak_hadir' => in_array($status, ['Alpha', 'Sakit', 'Izin']),
                'status' => $status,
                'keterangan' => $att?->keterangan ?? '',
                'is_already_saved' => $existing && !empty($status),
            ];
        });

        // Combine: Guru list first, then Pegawai Administrasi below
        $allParticipants = $dataTeachers->concat($dataPegawai)->values();

        return Inertia::render('AbsensiUpacara/Index', [
            'date' => $date,
            'teachers' => $allParticipants,
            'totalTeachers' => $teachers->count(),
            'totalPegawai' => $pegawais->count(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'nullable|exists:users,id',
            'attendances.*.pegawai_administrasi_id' => 'nullable|exists:pegawai_administrasis,id',
            'attendances.*.status' => 'nullable|string|in:Hadir,Alpha,Sakit,Izin',
            'attendances.*.keterangan' => 'nullable|string',
        ]);

        $date = $request->date;
        $attendances = $request->attendances;

        DB::transaction(function () use ($date, $attendances) {
            foreach ($attendances as $attendance) {
                $status = $attendance['status'] ?? null;
                $userId = $attendance['user_id'] ?? null;
                $pegawaiId = $attendance['pegawai_administrasi_id'] ?? null;

                if ($userId) {
                    if ($status) {
                        AbsensiUpacara::updateOrCreate(
                            [
                                'tanggal' => $date,
                                'user_id' => $userId,
                            ],
                            [
                                'is_hadir' => $status === 'Hadir',
                                'status' => $status,
                                'keterangan' => $attendance['keterangan'] ?? null,
                            ]
                        );
                    } else {
                        AbsensiUpacara::where('tanggal', $date)
                            ->where('user_id', $userId)
                            ->delete();
                    }
                } elseif ($pegawaiId) {
                    if ($status) {
                        AbsensiUpacara::updateOrCreate(
                            [
                                'tanggal' => $date,
                                'pegawai_administrasi_id' => $pegawaiId,
                            ],
                            [
                                'is_hadir' => $status === 'Hadir',
                                'status' => $status,
                                'keterangan' => $attendance['keterangan'] ?? null,
                            ]
                        );
                    } else {
                        AbsensiUpacara::where('tanggal', $date)
                            ->where('pegawai_administrasi_id', $pegawaiId)
                            ->delete();
                    }
                }
            }
        });

        return back()->with('success', 'Absensi upacara berhasil disimpan.');
    }
}
