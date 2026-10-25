<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\GymShop;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Scopes\GymScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * پیشاندانی بەڕێوەبردنی کۆگا، ناساندنی کاڵاکان و کڕین
     */
    public function index(Request $request)
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);
        $shop = GymShop::where('gym_id', $activeGymId)->where('is_active', true)->first();

        $categories = ProductCategory::all();
        $brands = ProductBrand::all();
        $suppliers = Supplier::all();

        $shopId = $shop?->id ?? 0;
        $filter = $request->get('filter', 'all');

        // ژماردنی کاڵا کەمبووەوەکان و نەماوەکان بەپێی min_stock_alert و بڕی بەردەست
        $allVariants = ProductVariant::with(['batches' => function ($q) use ($shopId) {
            $q->where('shop_id', $shopId)->where('is_blocked', false);
        }])->where('is_active', true)->get();

        $lowStockVariantIds = [];
        $outOfStockVariantIds = [];

        foreach ($allVariants as $variant) {
            $avail = $variant->batches
                ->filter(fn($b) => !$b->expiry_date || $b->expiry_date->isFuture())
                ->sum('quantity_available');
            $threshold = $variant->min_stock_alert ?? 5;

            if ($avail <= 0) {
                $outOfStockVariantIds[] = $variant->id;
                $lowStockVariantIds[] = $variant->id;
            } elseif ($avail <= $threshold) {
                $lowStockVariantIds[] = $variant->id;
            }
        }

        $lowStockCount = count($lowStockVariantIds);
        $outOfStockCount = count($outOfStockVariantIds);

        // کاڵاکان بە فلتەری کۆگا
        $productQuery = Product::with(['brand', 'category', 'variants.batches' => function ($q) use ($shop) {
            if ($shop) {
                $q->where('shop_id', $shop->id);
            }
        }]);

        if ($filter === 'low_stock') {
            $productQuery->whereHas('variants', function ($q) use ($lowStockVariantIds) {
                $q->whereIn('id', $lowStockVariantIds);
            });
        } elseif ($filter === 'out_of_stock') {
            $productQuery->whereHas('variants', function ($q) use ($outOfStockVariantIds) {
                $q->whereIn('id', $outOfStockVariantIds);
            });
        }

        $products = $productQuery->latest()->paginate(10)->withQueryString();

        // سەرجەم وەجبەکانی کۆگا بەپێی بەرواری بەسەرچوون (FEFO)
        $batches = [];
        if ($shop) {
            $batches = InventoryBatch::with(['variant.product', 'supplier'])
                ->where('shop_id', $shop->id)
                ->orderBy('expiry_date', 'asc')
                ->paginate(15, ['*'], 'batches_page');
        }

        return view('inventory.index', compact(
            'activeGym',
            'shop',
            'categories',
            'brands',
            'suppliers',
            'products',
            'batches',
            'lowStockCount',
            'outOfStockCount',
            'filter'
        ));
    }

    /**
     * ناساندنی کاڵای نوێ لەگەڵ جۆرەکەی (Product & Variant)
     */
    public function storeProduct(Request $request)
    {
        $request->validate([
            'name_ku' => 'required|string|max:200',
            'name_ar' => 'required|string|max:200',
            'name_en' => 'required|string|max:200',
            'category_id' => 'nullable|exists:product_categories,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            // زانیاری جۆری کاڵا (Variant)
            'sku' => 'required|string|unique:product_variants,sku',
            'barcode' => 'required|string|unique:product_variants,barcode',
            'flavor' => 'nullable|string',
            'size' => 'nullable|string',
            'default_cost_price' => 'required|numeric|min:0',
            'default_sale_price' => 'required|numeric|min:0',
            'member_sale_price' => 'nullable|numeric|min:0',
            'min_stock_alert' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $product = Product::create([
                'name_ku' => $request->name_ku,
                'name_ar' => $request->name_ar,
                'name_en' => $request->name_en,
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'description' => $request->description,
                'ingredients' => $request->ingredients,
                'is_active' => true,
            ]);

            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $request->sku,
                'barcode' => $request->barcode,
                'flavor' => $request->flavor,
                'size' => $request->size,
                'default_cost_price' => $request->default_cost_price,
                'default_sale_price' => $request->default_sale_price,
                'member_sale_price' => $request->member_sale_price ?: $request->default_sale_price,
                'min_stock_alert' => $request->min_stock_alert ?? 5,
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'کاڵای نوێ و جۆرەکەی بە سەرکەوتوویی ناسێنرا.');
    }

    /**
     * تۆمارکردنی فاکتۆری کڕین و هاتنی وەجبەی نوێ بۆ کۆگا (Receive Stock / Purchase)
     */
    public function storeBatch(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:gym_shops,id',
            'variant_id' => 'required|exists:product_variants,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'batch_number' => 'required|string|max:100',
            'expiry_date' => 'required|date|after:today',
            'unit_cost_price' => 'required|numeric|min:0',
            'quantity_received' => 'required|integer|min:1',
            'invoice_reference' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $batch = InventoryBatch::create([
                'shop_id' => $request->shop_id,
                'variant_id' => $request->variant_id,
                'supplier_id' => $request->supplier_id,
                'batch_number' => $request->batch_number,
                'expiry_date' => $request->expiry_date,
                'unit_cost_price' => $request->unit_cost_price,
                'quantity_received' => $request->quantity_received,
                'quantity_available' => $request->quantity_received,
                'is_blocked' => false,
            ]);

            // تۆمارکردنی جووڵەی کڕین لە لاگی کۆگا
            StockMovement::create([
                'shop_id' => $request->shop_id,
                'batch_id' => $batch->id,
                'movement_type' => 'PURCHASE_RECEIPT',
                'quantity' => $request->quantity_received,
                'unit_cost' => $request->unit_cost_price,
                'reference_type' => 'purchase_invoice',
                'performed_by_user_id' => auth()->id() ?? 1,
                'notes' => 'کڕین لە دابینکەر - فاکتۆری #' . ($request->invoice_reference ?? 'N/A'),
            ]);
        });

        return back()->with('success', 'وەجبەی نوێی کاڵاکە لە دابینکەرەوە وەرگیرا و بەسەرکەوتوویی خرایە کۆگاوە.');
    }

    /**
     * زیادکردنی دابینکەری نوێ
     */
    public function storeSupplier(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
        ]);

        Supplier::create($request->all());

        return back()->with('success', 'دابینکەری نوێ بە سەرکەوتوویی زیادکرا.');
    }

    /**
     * زیادکردنی پۆلێنی نوێ (Product Category)
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name_ku' => 'required|string|max:100',
            'name_ar' => 'nullable|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:product_categories,id',
        ]);

        ProductCategory::create([
            'name_ku' => $request->name_ku,
            'name_ar' => $request->name_ar ?: $request->name_ku,
            'name_en' => $request->name_en ?: $request->name_ku,
            'parent_id' => $request->parent_id,
        ]);

        return back()->with('success', 'پۆلێنی نوێ (Category) بە سەرکەوتوویی زیادکرا.');
    }

    /**
     * زیادکردنی براندی نوێ (Product Brand)
     */
    public function storeBrand(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'country_of_origin' => 'nullable|string|max:100',
            'logo_url' => 'nullable|string|max:255',
        ]);

        ProductBrand::create([
            'name' => $request->name,
            'country_of_origin' => $request->country_of_origin,
            'logo_url' => $request->logo_url,
        ]);

        return back()->with('success', 'براندی نوێ (Brand) بە سەرکەوتوویی زیادکرا.');
    }
}
