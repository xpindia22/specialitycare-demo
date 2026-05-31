<?php
namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ROLE_ADMIN        = 'admin';
    const ROLE_DOCTOR       = 'doctor';
    const ROLE_NURSE        = 'nurse';
    const ROLE_RECEPTIONIST = 'receptionist';
    const ROLE_LAB          = 'lab';
    const ROLE_PHARMACIST   = 'pharmacist';

    const ROLES = [
        self::ROLE_ADMIN        => 'Administrator',
        self::ROLE_DOCTOR       => 'Doctor',
        self::ROLE_NURSE        => 'Nurse',
        self::ROLE_RECEPTIONIST => 'Receptionist',
        self::ROLE_LAB          => 'Lab Technician',
        self::ROLE_PHARMACIST   => 'Pharmacist',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'has_system_access', 'hidden'];
    protected $hidden   = ['password', 'remember_token'];

    protected function casts(): array {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'has_system_access'  => 'boolean',
            'hidden'             => 'boolean',
        ];
    }

    /** Emails configured as hidden owner accounts (lowercased). */
    public static function hiddenEmails(): array
    {
        return (array) config('access.hidden_emails', []);
    }

    /**
     * Apply the hidden-account filter to a query of *users* (e.g. inside a
     * whereDoesntHave('user', ...) on Doctor/Staff). Matches rows flagged
     * `hidden` OR whose email is in OWNER_HIDDEN_EMAILS.
     */
    public static function scopeHiddenFilter($q): void
    {
        $emails = self::hiddenEmails();
        $q->where('hidden', true);
        if (!empty($emails)) {
            $q->orWhereIn(\Illuminate\Support\Facades\DB::raw('LOWER(email)'), $emails);
        }
    }

    /** True if this account should be hidden from listings. */
    public function isHidden(): bool
    {
        if (($this->attributes['hidden'] ?? false)) return true;
        return in_array(strtolower((string) $this->email), self::hiddenEmails(), true);
    }

    /**
     * Exclude hidden owner accounts from user/doctor/staff listings.
     * Hides rows flagged `hidden` OR whose email is in OWNER_HIDDEN_EMAILS.
     */
    public function scopeVisible($query)
    {
        $query->where(function ($q) {
            $q->where('hidden', false)->orWhereNull('hidden');
        });
        $emails = self::hiddenEmails();
        if (!empty($emails)) {
            $query->whereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(email)'), $emails);
        }
        return $query;
    }

    /** All roles this user holds (multi-role pivot). */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * True if any of this user's roles grants the given permission key.
     * Uses an in-process cache per request to avoid re-querying for every
     * sidebar item.
     */
    public function can($abilities, $arguments = []): bool
    {
        // Allow the standard Gate-style $user->can('something', ...) entrypoint
        // to be hijacked for our string permission keys, while still
        // delegating to Laravel's Gate when callers pass a model or array.
        if (is_string($abilities) && empty($arguments)) {
            return $this->hasPermission($abilities);
        }
        return parent::can($abilities, $arguments);
    }

    /** True if any of the user's roles grants the permission. */
    public function hasPermission(string $key): bool
    {
        foreach ($this->permissionSet() as $p) {
            if ($p === $key) return true;
        }
        return false;
    }

    /** True if the user has ANY of the given permission keys. */
    public function canAny($abilities, $arguments = []): bool
    {
        if (is_array($abilities) && empty($arguments) && $this->allStrings($abilities)) {
            $set = array_flip($this->permissionSet());
            foreach ($abilities as $k) if (isset($set[$k])) return true;
            return false;
        }
        return parent::canAny($abilities, $arguments);
    }

    private function allStrings(array $vals): bool
    {
        foreach ($vals as $v) if (!is_string($v)) return false;
        return true;
    }

    /**
     * The flattened permission set across all of the user's roles.
     * Cached per model instance so sidebar checks don't refetch.
     */
    public function permissionSet(): array
    {
        if (isset($this->attributes['_perm_cache'])) return $this->attributes['_perm_cache'];
        $perms = [];
        foreach ($this->roles as $r) {
            foreach ((array) $r->permissions as $p) $perms[$p] = true;
        }
        return $this->attributes['_perm_cache'] = array_keys($perms);
    }

    /**
     * Legacy role check — preserved so existing route middleware
     * (`role:admin,doctor`) and Blade `@if($u->isAdmin())` keep working.
     * Now sources from the pivot, falling back to the deprecated string
     * column for any user where the migration hasn't backfilled yet.
     */
    public function hasRole(string|array $roles): bool
    {
        $names = (array) $roles;
        $myRoleNames = $this->roles->pluck('name')->all();
        foreach ($names as $n) {
            if (in_array($n, $myRoleNames, true)) return true;
            if ($this->role === $n) return true; // back-compat
        }
        return false;
    }

    public function isAdmin():        bool { return $this->hasRole(self::ROLE_ADMIN); }

    /**
     * Clinic owner — the single user authorised for irreversible
     * destructive actions (delete patient, etc.). Identified by email so
     * adding new admin users doesn't accidentally grant destructive power.
     */
    public function isOwner(): bool
    {
        $owners = (array) config('access.owner_emails', []);
        return in_array(strtolower((string) $this->email), $owners, true);
    }

    public function isDoctor():       bool { return $this->hasRole(self::ROLE_DOCTOR); }
    public function isNurse():        bool { return $this->hasRole(self::ROLE_NURSE); }
    public function isReceptionist(): bool { return $this->hasRole(self::ROLE_RECEPTIONIST); }
    public function isLab():          bool { return $this->hasRole(self::ROLE_LAB); }
    public function isPharmacist():   bool { return $this->hasRole(self::ROLE_PHARMACIST); }

    public function getRoleLabelAttribute(): string {
        // Show all assigned role labels joined by " + " for display.
        if ($this->relationLoaded('roles') && $this->roles->isNotEmpty()) {
            return $this->roles->pluck('label')->implode(' + ');
        }
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }
}
