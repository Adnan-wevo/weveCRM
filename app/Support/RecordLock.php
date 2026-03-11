<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class RecordLock
{
    /** Lock TTL in seconds (5 minutes). */
    private const TTL = 300;

    public static function cacheKey(string $model, string $id): string
    {
        return "record_lock:{$model}:{$id}";
    }

    /**
     * Try to acquire a lock.
     * Returns true if the lock was acquired (or already held by the same user).
     * Returns false if the record is locked by someone else.
     */
    public static function acquire(string $model, string $id, string $userId, string $userName): bool
    {
        $key = self::cacheKey($model, $id);
        $existing = Cache::get($key);

        if ($existing !== null && $existing['user_id'] !== $userId) {
            return false;
        }

        Cache::put($key, [
            'user_id' => $userId,
            'user_name' => $userName,
        ], self::TTL);

        return true;
    }

    /**
     * Release a lock, but only if it belongs to the given user.
     */
    public static function release(string $model, string $id, string $userId): void
    {
        $key = self::cacheKey($model, $id);
        $existing = Cache::get($key);

        if ($existing !== null && $existing['user_id'] === $userId) {
            Cache::forget($key);
        }
    }

    /**
     * Check whether a record is locked by someone other than $userId.
     */
    public static function isLockedByOther(string $model, string $id, string $userId): bool
    {
        $lock = self::lockedBy($model, $id);

        return $lock !== null && $lock['user_id'] !== $userId;
    }

    /**
     * Return lock details, or null if not locked.
     *
     * @return array{user_id: string, user_name: string}|null
     */
    public static function lockedBy(string $model, string $id): ?array
    {
        return Cache::get(self::cacheKey($model, $id));
    }

    /**
     * Return a map of id => lock_info for all IDs currently locked by someone other than $userId.
     *
     * @param  array<int, string>  $ids
     * @return array<string, array{user_id: string, user_name: string}>
     */
    public static function getLockedByOthers(string $model, array $ids, string $userId): array
    {
        $locked = [];

        foreach ($ids as $id) {
            $lock = Cache::get(self::cacheKey($model, (string) $id));

            if ($lock !== null && $lock['user_id'] !== $userId) {
                $locked[(string) $id] = $lock;
            }
        }

        return $locked;
    }
}
