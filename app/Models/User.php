<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'activo',
        'telefono',
        'avatar',
    ];

    /** URL del avatar o iniciales como fallback */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && \Storage::disk('public')->exists('avatars/' . $this->avatar)) {
            return asset('storage/avatars/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name)
             . '&background=1a2035&color=fff&size=128&bold=true';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'activo'            => 'boolean',
    ];

    /* ---- Helpers de rol ---- */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupervisor(): bool
    {
        return in_array($this->role, ['admin', 'supervisor']);
    }

    public function getRoleBadgeAttribute(): string
    {
        return match ($this->role) {
            'admin'         => 'danger',
            'supervisor'    => 'warning',
            'recepcionista' => 'info',
            default         => 'secondary',
        };
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin'         => 'Administrador',
            'supervisor'    => 'Supervisor',
            'recepcionista' => 'Recepcionista',
            default         => ucfirst($this->role),
        };
    }

    /* ---- Relaciones ---- */

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'user_id');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'user_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'user_id');
    }
}
