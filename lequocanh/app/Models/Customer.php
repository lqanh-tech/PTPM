<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Customer model representing registered users.
 * Maps to 'user' table in legacy database.
 *
 * @property int $iduser
 * @property string $username
 * @property string $hoten
 * @property string $email
 * @property string $sodienthoai
 * @property string $diachi
 * @property string $avatar_url
 * @property string $auth_provider
 * @property string $google_id
 * @property string $facebook_id
 * @property string $password
 */
class Customer extends BaseModel
{
    protected static $table = 'user';
    protected static $primaryKey = 'iduser';
    protected static $timestamps = false;
    protected static $fillable = [
        'username',
        'hoten',
        'email',
        'sodienthoai',
        'diachi',
        'avatar_url',
        'auth_provider',
        'google_id',
        'facebook_id',
    ];
    protected static $hidden = [
        'password',
    ];

    /**
     * Find customer by email.
     */
    public static function findByEmail(string $email): ?self
    {
        $results = self::where('email', '=', $email);
        return $results[0] ?? null;
    }

    /**
     * Find customer by username.
     */
    public static function findByUsername(string $username): ?self
    {
        $results = self::where('username', '=', $username);
        return $results[0] ?? null;
    }

    /**
     * Find customer by OAuth provider ID.
     */
    public static function findByProvider(string $provider, string $providerId): ?self
    {
        $field = $provider . '_id';
        $results = self::where($field, '=', $providerId);
        return $results[0] ?? null;
    }

    /**
     * Get customer's orders.
     */
    public function orders(): array
    {
        return Order::findByCustomer((int)$this->getKey());
    }

    /**
     * Get display name (hoten or username).
     */
    public function getDisplayName(): string
    {
        return $this->hoten ?: $this->username;
    }

    /**
     * Check if customer has password set.
     */
    public function hasPassword(): bool
    {
        return !empty($this->password);
    }
}
