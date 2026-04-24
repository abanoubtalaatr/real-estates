<?php

namespace App\Models;

use App\Enums\ListingType;
use App\Enums\PropertyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Property extends Model
{
    protected $fillable = [
        'category_id',
        'assigned_agent_id',
        'title',
        'slug',
        'description',
        'price',
        'listing_type',
        'bedrooms',
        'bathrooms',
        'kitchens',
        'status',
        'is_featured',
        'sales_count',
        'latitude',
        'longitude',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'listing_type' => ListingType::class,
            'status' => PropertyStatus::class,
            'is_featured' => 'boolean',
            'sales_count' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'kitchens' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Property $property): void {
            if (empty($property->slug)) {
                $base = Str::slug($property->title);
                $property->slug = $base.'-'.Str::lower(Str::random(6));
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PropertyStatus::Published);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'assigned_agent_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PropertyReview::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
