<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'bio',
        'license_number',
        'company',
    ];

    protected static function booted(): void
    {
        static::created(function (Agent $agent): void {
            $agent->user?->update(['role' => UserRole::Agent]);
        });

        static::deleted(function (Agent $agent): void {
            $agent->user?->update(['role' => UserRole::User]);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'assigned_agent_id');
    }
}
