<?php

namespace App\Services\InvoiceParser;

/**
 * Exception thrown when invoice text parsing fails.
 *
 * This can be due to:
 * - Unrecognizable invoice format
 * - LLM API failure
 * - JSON parsing failure from LLM response
 * - No items could be extracted
 */
class ParsingException extends \RuntimeException
{
    /**
     * Create an exception for LLM API failure.
     */
    public static function llmFailure(string $message, ?\Throwable $previous = null): self
    {
        return new self("LLM parsing failed: {$message}", 0, $previous);
    }

    /**
     * Create an exception for invalid JSON response.
     */
    public static function invalidJson(string $rawResponse): self
    {
        $preview = mb_substr($rawResponse, 0, 200);
        return new self("Failed to parse LLM JSON response. Preview: {$preview}");
    }

    /**
     * Create an exception for no items extracted.
     */
    public static function noItemsExtracted(): self
    {
        return new self('No line items could be extracted from the invoice text.');
    }

    /**
     * Create an exception for regex parsing failure.
     */
    public static function regexFailure(string $reason): self
    {
        return new self("Regex parsing failed: {$reason}");
    }
}
