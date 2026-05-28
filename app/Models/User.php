<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasRoles, Notifiable;

    public const ACCOUNT_OWNER_ROLES = [
        'jefe_cuenta',
        'jefe_atc',
        'jefe atc',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_owner_id',
        'application_theme_id',
        'theme_mode',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function recruiterProfile(): HasOne
    {
        return $this->hasOne(RecruiterProfile::class);
    }

    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(self::class, 'account_owner_id');
    }

    public function accountUsers(): HasMany
    {
        return $this->hasMany(self::class, 'account_owner_id');
    }

    /**
     * @return array<int, int>
     */
    public function visibleCvUserIds(): array
    {
        if ($this->hasAnyRole(['admin', 'administrator'])) {
            return self::query()->pluck('id')->all();
        }

        if ($this->isAccountOwner()) {
            return $this->accountUsers()
                ->pluck('id')
                ->push($this->id)
                ->unique()
                ->values()
                ->all();
        }

        return [$this->id];
    }

    /**
     * @return array<int, int>
     */
    public function visibleRecruiterUserIds(): array
    {
        return $this->visibleCvUserIds();
    }

    public function isAccountOwner(): bool
    {
        return $this->hasAnyRole(self::ACCOUNT_OWNER_ROLES);
    }

    public function canViewCvOwner(User|int|null $owner): bool
    {
        $ownerId = $owner instanceof self ? $owner->id : $owner;

        return $ownerId !== null && in_array((int) $ownerId, $this->visibleCvUserIds(), true);
    }

    public function canViewRecruiterOwner(User|int|null $owner): bool
    {
        $ownerId = $owner instanceof self ? $owner->id : $owner;

        return $ownerId !== null && in_array((int) $ownerId, $this->visibleRecruiterUserIds(), true);
    }

    public function talents(): HasMany
    {
        return $this->hasMany(Talent::class, 'recruiter_id');
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class, 'recruiter_id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'recruiter_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'recruiter_id');
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'recruiter_id');
    }

    public function cvProfiles(): HasMany
    {
        return $this->hasMany(CvProfile::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function cvUsageSubscription(): HasOne
    {
        return $this->hasOne(CvUsageSubscription::class);
    }

    public function cvUsageEvents(): HasMany
    {
        return $this->hasMany(CvUsageEvent::class);
    }

    public function applicationTheme(): BelongsTo
    {
        return $this->belongsTo(ApplicationTheme::class);
    }
}
