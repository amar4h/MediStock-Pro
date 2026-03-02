<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a tenant or user exceeds a rate limit.
 *
 * Used for invoice scan daily/monthly limits, API throttling,
 * or any other rate-limited operation in the application.
 */
class RateLimitException extends RuntimeException
{
    /**
     * The name of the limit that was exceeded (e.g. "daily_invoice_scans").
     */
    protected string $limiterKey;

    /**
     * The maximum allowed attempts.
     */
    protected int $maxAttempts;

    /**
     * The number of seconds until the limit resets.
     */
    protected int $retryAfterSeconds;

    /**
     * Create a new RateLimitException instance.
     *
     * @param  string          $message           Human-readable error description.
     * @param  string          $limiterKey         Identifier for the rate limit that was hit.
     * @param  int             $maxAttempts        The maximum number of attempts allowed.
     * @param  int             $retryAfterSeconds  Seconds until the rate limit resets.
     * @param  int             $code              Internal error code.
     * @param  Throwable|null  $previous          Previous exception for chaining.
     */
    public function __construct(
        string $message = 'Rate limit exceeded. Please try again later.',
        string $limiterKey = '',
        int $maxAttempts = 0,
        int $retryAfterSeconds = 0,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->limiterKey        = $limiterKey;
        $this->maxAttempts       = $maxAttempts;
        $this->retryAfterSeconds = $retryAfterSeconds;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the limiter key that was exceeded.
     */
    public function getLimiterKey(): string
    {
        return $this->limiterKey;
    }

    /**
     * Get the maximum number of attempts allowed.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Get the number of seconds until the rate limit resets.
     */
    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }

    // ── Named Constructors ─────────────────────────────────────────

    /**
     * The daily invoice scan limit has been reached.
     */
    public static function dailyScanLimitExceeded(int $limit): static
    {
        return new static(
            message: "Daily invoice scan limit of {$limit} has been reached. Please try again tomorrow.",
            limiterKey: 'daily_invoice_scans',
            maxAttempts: $limit,
            retryAfterSeconds: self::secondsUntilEndOfDay(),
        );
    }

    /**
     * The monthly invoice scan limit has been reached.
     */
    public static function monthlyScanLimitExceeded(int $limit): static
    {
        return new static(
            message: "Monthly invoice scan limit of {$limit} has been reached. Please upgrade your plan or wait until next month.",
            limiterKey: 'monthly_invoice_scans',
            maxAttempts: $limit,
            retryAfterSeconds: self::secondsUntilEndOfMonth(),
        );
    }

    /**
     * A generic rate limit has been exceeded.
     */
    public static function tooManyAttempts(string $limiterKey, int $maxAttempts, int $retryAfterSeconds): static
    {
        return new static(
            message: "Too many attempts. Please try again in " . self::humanReadableDuration($retryAfterSeconds) . ".",
            limiterKey: $limiterKey,
            maxAttempts: $maxAttempts,
            retryAfterSeconds: $retryAfterSeconds,
        );
    }

    // ── Internal Helpers ───────────────────────────────────────────

    /**
     * Calculate seconds remaining until end of the current day.
     */
    private static function secondsUntilEndOfDay(): int
    {
        return now()->endOfDay()->diffInSeconds(now());
    }

    /**
     * Calculate seconds remaining until end of the current month.
     */
    private static function secondsUntilEndOfMonth(): int
    {
        return now()->endOfMonth()->endOfDay()->diffInSeconds(now());
    }

    /**
     * Convert seconds into a human-readable duration string.
     */
    private static function humanReadableDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} second" . ($seconds !== 1 ? 's' : '');
        }

        $minutes = (int) ceil($seconds / 60);

        if ($minutes < 60) {
            return "{$minutes} minute" . ($minutes !== 1 ? 's' : '');
        }

        $hours = (int) ceil($minutes / 60);

        return "{$hours} hour" . ($hours !== 1 ? 's' : '');
    }
}
