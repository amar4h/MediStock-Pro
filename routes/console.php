<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// ──────────────────────────────────────────────
// Scheduled Commands
// ──────────────────────────────────────────────

// Check for near-expiry batches and send alerts daily at 8:00 AM IST
Schedule::command('medistock:check-expiry')
    ->dailyAt('08:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Check for near-expiry and expired batches');

// Check for low stock items and send alerts daily at 8:30 AM IST
Schedule::command('medistock:check-low-stock')
    ->dailyAt('08:30')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Check for low stock items and send alerts');

// Clean up old invoice scan images based on retention policy
Schedule::command('medistock:cleanup-invoice-scans')
    ->dailyAt('02:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Clean up expired invoice scan images');

// Generate daily sales summary for each tenant
Schedule::command('medistock:daily-sales-summary')
    ->dailyAt('23:55')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Generate daily sales summary reports');

// Prune stale queue jobs
Schedule::command('queue:prune-batches --hours=48')
    ->daily()
    ->description('Prune old job batches');

Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->description('Prune failed jobs older than 7 days');

// Clear expired password reset tokens weekly
Schedule::command('auth:clear-resets')
    ->weekly()
    ->description('Clear expired password reset tokens');

// Prune old telescope/log entries (if applicable)
Schedule::command('cache:prune-stale-tags')
    ->hourly()
    ->description('Prune stale cache tags');
