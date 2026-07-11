<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'referrer_id',
        'referred_id',
        'reward_triggered_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'referrer_id' => 'integer',
            'referred_id' => 'integer',
            'reward_triggered_at' => 'timestamp',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
