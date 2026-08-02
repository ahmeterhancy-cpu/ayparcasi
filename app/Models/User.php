<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    /** Yönetim paneline yalnızca ekip girer; müşteriler giremez. */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Yönetici ya da çalışan — kısacası dükkânın ekibi. */
    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    // --- İlişkiler --------------------------------------------------------

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->orderBy('title');
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    /** Sipariş formunu ön-doldurmak için kullanılan varsayılan adres. */
    public function defaultAddress(): ?Address
    {
        return $this->addresses()->first();
    }

    public function getFirstNameAttribute(): string
    {
        return explode(' ', trim($this->name))[0] ?? $this->name;
    }
}
