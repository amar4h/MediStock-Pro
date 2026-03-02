<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\Manufacturer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $items = Item::with(['category:id,name', 'manufacturer:id,name'])
            ->withSum('batches as total_stock', 'stock_quantity')
            ->when($request->filled('search'), fn ($q) => $q->search($request->input('search')))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('name', $request->input('category'))))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->pluck('name');
        $manufacturers = Manufacturer::orderBy('name')->pluck('name');

        return view('pages.items.index', compact('items', 'categories', 'manufacturers'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->pluck('name');
        $manufacturers = Manufacturer::orderBy('name')->pluck('name');

        return view('pages.items.create', compact('categories', 'manufacturers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'composition'    => 'nullable|string|max:500',
            'category'       => 'required|string|max:255',
            'manufacturer'   => 'nullable|string|max:255',
            'hsn_code'       => 'nullable|string|max:20',
            'gst_percent'    => 'required|numeric|in:0,5,12,18,28',
            'margin_percent' => 'nullable|numeric|min:0|max:100',
            'barcode'        => ['nullable', 'string', 'max:100', Rule::unique('items', 'barcode')->where('tenant_id', $tenantId)],
            'unit'           => 'nullable|string|max:50',
            'schedule'       => 'nullable|string|max:10',
            'status'         => 'nullable|in:active,inactive',
        ]);

        $data = $this->resolveItemData($validated, $tenantId);
        Item::create($data);

        return redirect()->route('items.index')->with('success', 'Item created successfully.');
    }

    public function show(Item $item): View
    {
        $item->load([
            'category:id,name',
            'manufacturer:id,name',
            'batches' => fn ($q) => $q->where('stock_quantity', '>', 0)->orderBy('expiry_date'),
        ]);
        $item->loadSum('batches as total_stock', 'stock_quantity');

        return view('pages.items.show', compact('item'));
    }

    public function edit(Item $item): View
    {
        $item->load(['category:id,name', 'manufacturer:id,name']);
        $categories = Category::orderBy('name')->pluck('name');
        $manufacturers = Manufacturer::orderBy('name')->pluck('name');

        return view('pages.items.create', compact('item', 'categories', 'manufacturers'));
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'composition'    => 'nullable|string|max:500',
            'category'       => 'required|string|max:255',
            'manufacturer'   => 'nullable|string|max:255',
            'hsn_code'       => 'nullable|string|max:20',
            'gst_percent'    => 'required|numeric|in:0,5,12,18,28',
            'margin_percent' => 'nullable|numeric|min:0|max:100',
            'barcode'        => ['nullable', 'string', 'max:100', Rule::unique('items', 'barcode')->where('tenant_id', $tenantId)->ignore($item->id)],
            'unit'           => 'nullable|string|max:50',
            'schedule'       => 'nullable|string|max:10',
            'status'         => 'nullable|in:active,inactive',
        ]);

        $data = $this->resolveItemData($validated, $tenantId);
        $item->update($data);

        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }

    private function resolveItemData(array $validated, int $tenantId): array
    {
        $data = $validated;

        // Resolve category string → category_id
        if (! empty($data['category'])) {
            $category = Category::firstOrCreate(
                ['name' => $data['category'], 'tenant_id' => $tenantId],
                ['is_active' => true]
            );
            $data['category_id'] = $category->id;
        }
        unset($data['category']);

        // Resolve manufacturer string → manufacturer_id
        if (! empty($data['manufacturer'])) {
            $manufacturer = Manufacturer::firstOrCreate(
                ['name' => $data['manufacturer'], 'tenant_id' => $tenantId],
                ['is_active' => true]
            );
            $data['manufacturer_id'] = $manufacturer->id;
        }
        unset($data['manufacturer']);

        // Map margin_percent → default_margin
        if (isset($data['margin_percent'])) {
            $data['default_margin'] = $data['margin_percent'];
        }
        unset($data['margin_percent']);

        // Map status → is_active
        if (isset($data['status'])) {
            $data['is_active'] = $data['status'] === 'active';
        }
        unset($data['status']);

        return $data;
    }
}
