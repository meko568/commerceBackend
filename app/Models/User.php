<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Cacheable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get user profile data (cached)
     */
    public function getCachedProfile()
    {
        return Cache::remember($this->getCacheKey('profile'), 3600, function () {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'city' => $this->city,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        });
    }

    /**
     * Get user statistics (cached)
     */
    public function getCachedStatistics()
    {
        return Cache::remember($this->getCacheKey('statistics'), 1800, function () {
            return [
                'total_orders' => \App\Models\Order::whereJsonContains('user_info->email', $this->email)->count(),
                'total_spent' => \App\Models\Order::whereJsonContains('user_info->email', $this->email)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount'),
                'last_order' => \App\Models\Order::whereJsonContains('user_info->email', $this->email)
                    ->orderBy('created_at', 'desc')
                    ->first(),
            ];
        });
    }

    /**
     * Forget all user-related caches
     */
    protected function forgetAllCaches(): void
    {
        parent::forgetAllCaches();
        
        // Forget user-specific caches
        Cache::forget($this->getCacheKey('profile'));
        Cache::forget($this->getCacheKey('statistics'));
    }

    /**
     * Get user by email with caching
     */
    public static function getCachedByEmail($email)
    {
        return Cache::remember(static::getCollectionCacheKey("email_{$email}"), 3600, function () use ($email) {
            return static::where('email', $email)->first();
        });
    }

    /**
     * Get user by ID with caching
     */
    public static function getCachedUser($id)
    {
        return Cache::remember(static::getCollectionCacheKey("item_{$id}"), 3600, function () use ($id) {
            return static::find($id);
        });
    }

    /**
     * Boot the trait
     */
    protected static function bootCacheable()
    {
        parent::bootCacheable();
        
        static::updated(function (User $user) {
            // Forget email-based cache when email is updated
            if ($user->wasChanged('email')) {
                Cache::forget(static::getCollectionCacheKey("email_{$user->getOriginal('email')}"));
            }
        });
    }
}
