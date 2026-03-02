<?php

namespace App\Services\OCR;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Cloud Vision OCR service using the REST API (no gRPC dependency).
 *
 * Implements circuit breaker pattern: after 3 consecutive failures,
 * the service is "opened" and skips API calls for 5 minutes.
 */
class GoogleVisionOCRService implements OCRServiceInterface
{
    private const API_ENDPOINT = 'https://vision.googleapis.com/v1/images:annotate';
    private const TIMEOUT_SECONDS = 30;

    /**
     * Circuit breaker settings.
     */
    private const CIRCUIT_FAILURE_THRESHOLD = 3;
    private const CIRCUIT_COOLDOWN_MINUTES = 5;
    private const CACHE_KEY_FAILURES = 'ocr_circuit_failures';
    private const CACHE_KEY_OPEN_UNTIL = 'ocr_circuit_open_until';

    /**
     * Detect text in a document image using Google Cloud Vision DOCUMENT_TEXT_DETECTION.
     *
     * @param  string  $imageContent  Raw binary image content
     * @return OCRResult
     *
     * @throws OCRException If the service is unavailable or the API call fails
     */
    public function detectDocumentText(string $imageContent): OCRResult
    {
        // Check circuit breaker state
        $this->checkCircuitBreaker();

        $apiKey = config('services.google_vision.api_key');

        if (empty($apiKey)) {
            throw OCRException::apiFailure('Google Vision API key not configured', 0);
        }

        $requestBody = [
            'requests' => [
                [
                    'image' => [
                        'content' => base64_encode($imageContent),
                    ],
                    'features' => [
                        [
                            'type'       => 'DOCUMENT_TEXT_DETECTION',
                            'maxResults' => 1,
                        ],
                    ],
                    'imageContext' => [
                        'languageHints' => ['en', 'hi'],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(self::API_ENDPOINT . '?key=' . $apiKey, $requestBody);

            if ($response->failed()) {
                $this->recordFailure();

                $errorMessage = $response->json('error.message', 'Unknown API error');
                throw OCRException::apiFailure($errorMessage, $response->status());
            }

            // Reset failure counter on success
            $this->resetCircuitBreaker();

            return $this->parseResponse($response->json());

        } catch (OCRException $e) {
            throw $e;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->recordFailure();
            throw OCRException::apiFailure('Connection timeout', 0, $e);
        } catch (\Throwable $e) {
            $this->recordFailure();
            Log::error('Google Vision OCR unexpected error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw OCRException::apiFailure($e->getMessage(), 0, $e);
        }
    }

    /**
     * Parse the Google Vision API response into an OCRResult.
     */
    private function parseResponse(array $responseData): OCRResult
    {
        $responses = $responseData['responses'] ?? [];

        if (empty($responses)) {
            return new OCRResult('', 0.0, []);
        }

        $firstResponse = $responses[0];

        // Check for errors in the response
        if (isset($firstResponse['error'])) {
            $errorMessage = $firstResponse['error']['message'] ?? 'Unknown error in Vision API response';
            throw OCRException::apiFailure($errorMessage);
        }

        $fullTextAnnotation = $firstResponse['fullTextAnnotation'] ?? null;
        $textAnnotations = $firstResponse['textAnnotations'] ?? [];

        // Extract full text
        $fullText = '';
        if ($fullTextAnnotation) {
            $fullText = $fullTextAnnotation['text'] ?? '';
        } elseif (! empty($textAnnotations)) {
            // Fall back to the first textAnnotation which contains the full text
            $fullText = $textAnnotations[0]['description'] ?? '';
        }

        // Extract blocks and calculate average confidence
        $blocks = [];
        $totalConfidence = 0;
        $blockCount = 0;

        if ($fullTextAnnotation && isset($fullTextAnnotation['pages'])) {
            foreach ($fullTextAnnotation['pages'] as $page) {
                foreach ($page['blocks'] ?? [] as $block) {
                    $blockText = $this->extractBlockText($block);
                    $blockConfidence = $block['confidence'] ?? 0;

                    $blocks[] = [
                        'text'        => $blockText,
                        'confidence'  => (float) $blockConfidence,
                        'boundingBox' => $block['boundingBox'] ?? null,
                        'blockType'   => $block['blockType'] ?? 'TEXT',
                    ];

                    $totalConfidence += (float) $blockConfidence;
                    $blockCount++;
                }
            }
        }

        $averageConfidence = $blockCount > 0 ? $totalConfidence / $blockCount : 0;

        return new OCRResult($fullText, round($averageConfidence, 4), $blocks);
    }

    /**
     * Extract text from a Vision API block structure.
     */
    private function extractBlockText(array $block): string
    {
        $text = '';

        foreach ($block['paragraphs'] ?? [] as $paragraph) {
            foreach ($paragraph['words'] ?? [] as $word) {
                $wordText = '';
                foreach ($word['symbols'] ?? [] as $symbol) {
                    $wordText .= $symbol['text'] ?? '';

                    // Add space/newline based on detected break
                    $detectedBreak = $symbol['property']['detectedBreak'] ?? null;
                    if ($detectedBreak) {
                        $breakType = $detectedBreak['type'] ?? '';
                        if (in_array($breakType, ['SPACE', 'SURE_SPACE'])) {
                            $wordText .= ' ';
                        } elseif (in_array($breakType, ['EOL_SURE_SPACE', 'LINE_BREAK'])) {
                            $wordText .= "\n";
                        }
                    }
                }
                $text .= $wordText;
            }
        }

        return trim($text);
    }

    /**
     * Check if the circuit breaker is open (too many recent failures).
     *
     * @throws OCRException If the circuit is open
     */
    private function checkCircuitBreaker(): void
    {
        $openUntil = Cache::get(self::CACHE_KEY_OPEN_UNTIL);

        if ($openUntil && now()->lt($openUntil)) {
            throw OCRException::circuitOpen(self::CIRCUIT_COOLDOWN_MINUTES);
        }

        // If the cooldown has passed, reset the circuit
        if ($openUntil) {
            $this->resetCircuitBreaker();
        }
    }

    /**
     * Record a failure and potentially open the circuit breaker.
     */
    private function recordFailure(): void
    {
        $failures = (int) Cache::get(self::CACHE_KEY_FAILURES, 0);
        $failures++;

        Cache::put(self::CACHE_KEY_FAILURES, $failures, now()->addMinutes(self::CIRCUIT_COOLDOWN_MINUTES));

        if ($failures >= self::CIRCUIT_FAILURE_THRESHOLD) {
            Cache::put(
                self::CACHE_KEY_OPEN_UNTIL,
                now()->addMinutes(self::CIRCUIT_COOLDOWN_MINUTES),
                now()->addMinutes(self::CIRCUIT_COOLDOWN_MINUTES)
            );

            Log::warning('OCR circuit breaker OPENED after {failures} consecutive failures.', [
                'failures'        => $failures,
                'cooldown_minutes' => self::CIRCUIT_COOLDOWN_MINUTES,
            ]);
        }
    }

    /**
     * Reset the circuit breaker after a successful call.
     */
    private function resetCircuitBreaker(): void
    {
        Cache::forget(self::CACHE_KEY_FAILURES);
        Cache::forget(self::CACHE_KEY_OPEN_UNTIL);
    }
}
