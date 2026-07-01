<?php

namespace App\Services;

use App\Models\WalletIdempotencyKey;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class IdempotencyService
{
    /** Sentinel owner for system-scoped idempotency (opening capital, master wallet ops). */
    public const SYSTEM_OWNER_ID = '00000000-0000-0000-0000-000000000001';

    /**
     * @return array<string, mixed>|null
     */
    public function findCached(int|string|null $ownerId, string $scope, ?string $idempotencyKey): ?array
    {
        if ($idempotencyKey === null || trim($idempotencyKey) === '') {
            return null;
        }

        $existing = WalletIdempotencyKey::query()
            ->where('customer_id', $this->normalizeOwnerId($ownerId))
            ->where('scope', $scope)
            ->where('idempotency_key', trim($idempotencyKey))
            ->first();

        return $existing?->response;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    public function remember(int|string|null $ownerId, string $scope, string $idempotencyKey, array $response): array
    {
        $idempotencyKey = trim($idempotencyKey);
        $ownerKey = $this->normalizeOwnerId($ownerId);

        try {
            WalletIdempotencyKey::create([
                'customer_id' => $ownerKey,
                'scope' => $scope,
                'idempotency_key' => $idempotencyKey,
                'status' => WalletIdempotencyKey::STATUS_COMPLETED,
                'response_code' => 200,
                'response' => $response,
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $existing = WalletIdempotencyKey::query()
                ->where('customer_id', $ownerKey)
                ->where('scope', $scope)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing->response;
            }

            throw $exception;
        }

        return $response;
    }

    /**
     * @template T of array
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function execute(int|string|null $ownerId, string $scope, ?string $idempotencyKey, Closure $callback): array
    {
        if ($idempotencyKey === null || trim($idempotencyKey) === '') {
            return $callback();
        }

        $idempotencyKey = trim($idempotencyKey);
        $ownerKey = $this->normalizeOwnerId($ownerId);

        $cached = $this->findCached($ownerKey, $scope, $idempotencyKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            return DB::transaction(function () use ($ownerKey, $scope, $idempotencyKey, $callback) {
                $existing = WalletIdempotencyKey::query()
                    ->where('customer_id', $ownerKey)
                    ->where('scope', $scope)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing->response;
                }

                $response = $callback();

                WalletIdempotencyKey::create([
                    'customer_id' => $ownerKey,
                    'scope' => $scope,
                    'idempotency_key' => $idempotencyKey,
                    'status' => WalletIdempotencyKey::STATUS_COMPLETED,
                    'response_code' => 200,
                    'response' => $response,
                ]);

                return $response;
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $existing = WalletIdempotencyKey::query()
                ->where('customer_id', $ownerKey)
                ->where('scope', $scope)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing->response;
            }

            throw $exception;
        }
    }

    /**
     * MySQL UNIQUE treats NULL as distinct per row — never persist a null owner key.
     */
    private function normalizeOwnerId(int|string|null $ownerId): string
    {
        if ($ownerId === null || $ownerId === '') {
            return self::SYSTEM_OWNER_ID;
        }

        return (string) $ownerId;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
