<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a sale or stock operation requires more
 * quantity than is currently available for an item.
 */
class InsufficientStockException extends RuntimeException
{
    /**
     * The ID of the item that has insufficient stock.
     */
    protected int $itemId;

    /**
     * The quantity that was requested.
     */
    protected int $requested;

    /**
     * The quantity currently available.
     */
    protected int $available;

    /**
     * Create a new InsufficientStockException instance.
     *
     * @param  int             $itemId     The item ID with insufficient stock.
     * @param  int             $requested  The quantity that was requested.
     * @param  int             $available  The quantity currently available.
     * @param  string          $message    Custom message (auto-generated if empty).
     * @param  int             $code       Internal error code.
     * @param  Throwable|null  $previous   Previous exception for chaining.
     */
    public function __construct(
        int $itemId,
        int $requested,
        int $available,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->itemId    = $itemId;
        $this->requested = $requested;
        $this->available = $available;

        if ($message === '') {
            $message = "Insufficient stock for item #{$itemId}. Requested: {$requested}, available: {$available}.";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the ID of the item that has insufficient stock.
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * Get the quantity that was requested.
     */
    public function getRequested(): int
    {
        return $this->requested;
    }

    /**
     * Get the quantity currently available.
     */
    public function getAvailable(): int
    {
        return $this->available;
    }

    /**
     * Get the shortage amount (requested minus available).
     */
    public function getShortage(): int
    {
        return max(0, $this->requested - $this->available);
    }

    /**
     * Convert the exception details to an array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'error'     => 'insufficient_stock',
            'message'   => $this->getMessage(),
            'item_id'   => $this->itemId,
            'requested' => $this->requested,
            'available' => $this->available,
            'shortage'  => $this->getShortage(),
        ];
    }
}
