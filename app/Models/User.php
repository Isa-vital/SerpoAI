<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'language_code',
        'language',
        'is_active',
        'notifications_enabled',
        'preferences',
        'last_interaction_at',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'is_active' => 'boolean',
        'notifications_enabled' => 'boolean',
        'preferences' => 'array',
        'last_interaction_at' => 'datetime',
    ];

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function logs()
    {
        return $this->hasMany(BotLog::class);
    }

    public function wallets()
    {
        return $this->hasMany(UserWallet::class);
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function userAlerts()
    {
        return $this->hasMany(UserAlert::class);
    }

    public function scanHistory()
    {
        return $this->hasMany(ScanHistory::class);
    }

    public function premiumSubscription()
    {
        return $this->hasOne(PremiumSubscription::class);
    }

    public function watchlistItems()
    {
        return $this->hasMany(WatchlistItem::class);
    }

    public function portfolioPositions()
    {
        return $this->hasMany(PortfolioPosition::class);
    }
}
