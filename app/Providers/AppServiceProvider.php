<?php

namespace App\Providers;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockDiscard;
use App\Observers\PurchaseObserver;
use App\Observers\SaleObserver;
use App\Observers\StockDiscardObserver;
use App\Services\InvoiceParser\FallbackInvoiceParser;
use App\Services\InvoiceParser\InvoiceParserInterface;
use App\Services\InvoiceParser\LLMInvoiceParser;
use App\Services\InvoiceParser\RegexInvoiceParser;
use App\Services\OCR\GoogleVisionOCRService;
use App\Services\OCR\OCRServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services and interface bindings.
     */
    public function register(): void
    {
        // ── OCR Service Binding ─────────────────────────────────────
        // Bind OCRServiceInterface to GoogleVisionOCRService.
        // To swap providers (e.g., AWS Textract), change this binding.
        $this->app->bind(OCRServiceInterface::class, GoogleVisionOCRService::class);

        // ── Invoice Parser Binding ──────────────────────────────────
        // Uses FallbackInvoiceParser which tries LLM first, then falls
        // back to regex parser. Can be configured via config to use
        // only one parser if desired.
        $this->app->bind(InvoiceParserInterface::class, function ($app) {
            $parserMode = config('medistock.invoice_parser', 'fallback');

            return match ($parserMode) {
                'llm'      => $app->make(LLMInvoiceParser::class),
                'regex'    => $app->make(RegexInvoiceParser::class),
                'fallback' => new FallbackInvoiceParser(
                    primary:  $app->make(LLMInvoiceParser::class),
                    fallback: $app->make(RegexInvoiceParser::class),
                ),
                default    => new FallbackInvoiceParser(
                    primary:  $app->make(LLMInvoiceParser::class),
                    fallback: $app->make(RegexInvoiceParser::class),
                ),
            };
        });
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        // ── Register Model Observers ────────────────────────────────
        Sale::observe(SaleObserver::class);
        Purchase::observe(PurchaseObserver::class);
        StockDiscard::observe(StockDiscardObserver::class);
    }
}
