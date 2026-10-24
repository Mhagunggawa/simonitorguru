<?php

namespace App\Models;

use App\Traits\LogsActivity;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'tipe_guru_id', 'status_pegawai', 'is_observer', 'observer_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    use LogsActivity;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_observer' => 'boolean',
        ];
    }

    public function tipeGuru() { return $this->belongsTo(TipeGuru::class, 'tipe_guru_id'); }
    public function jadwalMapels() { return $this->hasMany(JadwalMapel::class, 'guru_id'); }
    public function jadwalPikets() { return $this->hasMany(JadwalPiket::class, 'guru_id'); }
    public function jadwalGms() { return $this->hasMany(JadwalGms::class); }
    public function observer() { return $this->belongsTo(User::class, 'observer_id'); }
    public function kinerjas() { return $this->hasMany(Kinerja::class, 'guru_id'); }
    public function observedTeachers() { return $this->hasMany(User::class, 'observer_id'); }
    public function guruDrive() { return $this->hasOne(GuruDrive::class, 'guru_id'); }
}
