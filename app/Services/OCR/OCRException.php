<?php

namespace App\Services\OCR;

/**
 * Exception thrown when OCR processing fails.
 *
 * This can be due to:
 * - API unavailability or timeout
 * - Invalid image format
 * - Circuit breaker open (too many recent failures)
 * - API key issues
 */
class OCRException extends \RuntimeException
{
    /**
     * Create an exception for API failure.
     */
    public static function apiFailure(string $message, int $statusCode = 0, ?\Throwable $previous = null): self
    {
        return new self(
            "OCR API failure: {$message} (HTTP {$statusCode})",
            $statusCode,
            $previous
        );
    }

    /**
     * Create an exception for circuit breaker being open.
     */
    public static function circuitOpen(int $cooldownMinutes): self
    {
        return new self(
            "OCR service temporarily unavailable. Circuit breaker open — retry after {$cooldownMinutes} minutes."
        );
    }

    /**
     * Create an exception for invalid image.
     */
    public static function invalidImage(string $reason): self
    {
        return new self("Invalid image for OCR: {$reason}");
    }
}
