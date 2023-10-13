<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Supplier extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['name', 'country_id', 'email', 'emails', 'description', 'address', 'phone1', 'phone2'];

    protected $casts = [
        'emails' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function mainCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->whereNull('parent_id');
    }

    public function subCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->whereNotNull('parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
