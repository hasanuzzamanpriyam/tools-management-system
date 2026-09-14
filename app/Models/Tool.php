<?php

namespace App\Models;

use Database\Factories\ToolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'type', 'pricing_model', 'price', 'device_limit', 'referral_credits', 'icon', 'is_active'])]
class Tool extends Model
{
    /** @use HasFactory<ToolFactory> */
    use HasFactory;

    public const TYPE_EXTENSION = 'extension';

    public const TYPE_DESKTOP = 'desktop';

    public const PRICING_ONE_TIME = 'one_time';

    public const PRICING_SUBSCRIPTION = 'subscription';

    public function files(): HasMany
    {
        return $this->hasMany(ToolFile::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'device_limit' => 'integer',
            'referral_credits' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
