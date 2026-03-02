<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when an invoice scan operation fails.
 *
 * Covers OCR failures, image processing errors, parsing failures,
 * and any other issue in the invoice scanning pipeline.
 */
class InvoiceScanException extends RuntimeException
{
    /**
     * The HTTP status code to return when this exception reaches the handler.
     */
    protected int $httpStatusCode;

    /**
     * Create a new InvoiceScanException instance.
     *
     * @param  string          $message        Human-readable error description.
     * @param  int             $httpStatusCode  HTTP status code (defaults to 422 Unprocessable Entity).
     * @param  int             $code           Internal error code for programmatic handling.
     * @param  Throwable|null  $previous       Previous exception for chaining.
     */
    public function __construct(
        string $message = 'Invoice scan processing failed.',
        int $httpStatusCode = 422,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->httpStatusCode = $httpStatusCode;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP status code associated with this exception.
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Set the HTTP status code.
     */
    public function setHttpStatusCode(int $httpStatusCode): static
    {
        $this->httpStatusCode = $httpStatusCode;

        return $this;
    }

    // ── Named Constructors ─────────────────────────────────────────

    /**
     * The uploaded image could not be processed.
     */
    public static function imageProcessingFailed(string $reason = '', ?Throwable $previous = null): static
    {
        $message = 'Failed to process the uploaded invoice image.';
        if ($reason) {
            $message .= " {$reason}";
        }

        return new static($message, 422, 0, $previous);
    }

    /**
     * OCR extraction failed or returned unusable results.
     */
    public static function ocrFailed(string $reason = '', ?Throwable $previous = null): static
    {
        $message = 'OCR text extraction failed.';
        if ($reason) {
            $message .= " {$reason}";
        }

        return new static($message, 502, 0, $previous);
    }

    /**
     * The parsed invoice data could not be validated or is incomplete.
     */
    public static function parsingFailed(string $reason = '', ?Throwable $previous = null): static
    {
        $message = 'Invoice data could not be parsed from the scan.';
        if ($reason) {
            $message .= " {$reason}";
        }

        return new static($message, 422, 0, $previous);
    }

    /**
     * The external service (LLM or Vision API) is unavailable.
     */
    public static function serviceUnavailable(string $service = '', ?Throwable $previous = null): static
    {
        $message = 'Invoice scan service is temporarily unavailable.';
        if ($service) {
            $message .= " ({$service})";
        }

        return new static($message, 503, 0, $previous);
    }
}
