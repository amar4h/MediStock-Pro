<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /**
     * Dashboard page.
     */
    public function dashboard(): View
    {
        return view('pages.dashboard');
    }

    /**
     * Items management page.
     */
    public function items(): View
    {
        return view('pages.items.index');
    }

    /**
     * Purchases management page.
     */
    public function purchases(): View
    {
        return view('pages.purchases.index');
    }

    /**
     * Sales / POS page.
     */
    public function sales(): View
    {
        return view('pages.sales.index');
    }

    /**
     * Inventory management page.
     */
    public function inventory(): View
    {
        return view('pages.inventory.index');
    }

    /**
     * Reports page.
     */
    public function reports(): View
    {
        return view('pages.reports.index');
    }

    /**
     * Settings page.
     */
    public function settings(): View
    {
        return view('pages.settings.index');
    }
}
