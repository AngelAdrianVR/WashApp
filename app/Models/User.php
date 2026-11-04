<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use InteractsWithMedia;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Rol del usuario (client, employee, admin)
        'phone_number',
        'is_active',
        'points',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'avatar_url',
    ];

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
            'is_active' => 'boolean',
            'points' => 'integer',
        ];
    }

    /**
     * La foto de perfil se gestiona con Spatie Media Library (colección "avatar").
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    /**
     * URL de la foto de perfil subida por el usuario.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('avatar');

        return $url !== '' ? $url : null;
    }

    /**
     * Jetstream muestra `profile_photo_url`: damos prioridad a la foto de Media Library.
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->avatar_url) {
                return $this->avatar_url;
            }

            return $this->profile_photo_path
                ? Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path)
                : $this->defaultProfilePhotoUrl();
        });
    }

    /**
     * Obtiene las reservas hechas por el usuario como cliente.
     */
    public function bookingsAsClient(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    /**
     * Obtiene las reservas asignadas al usuario como empleado.
     */
    public function bookingsAsEmployee(): HasMany
    {
        return $this->hasMany(Booking::class, 'employee_id');
    }

    /**
     * Obtiene las recompensas ganadas por el usuario.
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(UserReward::class);
    }

    /**
     * Notificaciones internas del panel.
     */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * Vehículos registrados por el cliente.
     */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }
}
