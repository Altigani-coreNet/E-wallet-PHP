<?php

namespace App\Services;

use App\Models\WalletIdempotencyKey;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class IdempotencyService
{
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

        $existing = WalletIdempotencyKey::query()
            ->where('customer_id', $ownerId)
            ->where('scope', $scope)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $existing->response;
        }

        try {
            return DB::transaction(function () use ($ownerId, $scope, $idempotencyKey, $callback) {
                $existing = WalletIdempotencyKey::query()
                    ->where('customer_id', $ownerId)
                    ->where('scope', $scope)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing->response;
                }

                $response = $callback();

                WalletIdempotencyKey::create([
                    'customer_id' => $ownerId,
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
                ->where('customer_id', $ownerId)
                ->where('scope', $scope)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing->response;
            }

            throw $exception;
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
