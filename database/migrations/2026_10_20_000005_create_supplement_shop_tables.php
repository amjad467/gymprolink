<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. شۆپەکان لەناو هۆڵەکاندا (Gym Supplement Shops)
        Schema::create('gym_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('location_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. پۆلێن و براندەکانی تەواوکەری خۆراکی (Categories & Brands)
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_ku');
            $table->string('name_ar');
            $table->string('name_en');
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('product_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_of_origin')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });

        // 3. کاڵای بنەڕەتی (Master Product)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
            $table->string('name_ku');
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description')->nullable();
            $table->text('ingredients')->nullable(); // پێکهاتەکان
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. فرە-جۆری کاڵاکان بەپێی قەبارە، کێش و تام (Product Variants)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->unique()->index();
            $table->string('size')->nullable(); // e.g. 5 lbs, 2 kg
            $table->decimal('weight_grams', 10, 2)->nullable();
            $table->string('flavor')->nullable(); // e.g. Chocolate, Vanilla, Strawberry
            $table->decimal('default_cost_price', 12, 2)->default(0.00);
            $table->decimal('default_sale_price', 12, 2)->default(0.00);
            $table->decimal('member_sale_price', 12, 2)->nullable(); // نرخی تایبەت بۆ ئەندامانی هۆڵ
            $table->integer('min_stock_alert')->default(5);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. دابینکەرانی تەواوکەر (Suppliers)
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('current_balance', 14, 2)->default(0.00); // شایستەی دابینکەر
            $table->timestamps();
        });

        // 6. وەجبەکانی کۆگا و بەرواری بەسەرچوون بەپێی شۆپ (FEFO Batches)
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('gym_shops')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('batch_number');
            $table->date('expiry_date')->index();
            $table->decimal('unit_cost_price', 12, 2);
            $table->integer('quantity_received');
            $table->integer('quantity_available');
            $table->boolean('is_blocked')->default(false); // بلۆککردن ئەگەر کێشەی کوالیتی یان بەسەرچوونی هەبێت
            $table->string('block_reason')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'variant_id', 'expiry_date', 'quantity_available'], 'idx_fefo_batches');
        });

        // 7. مێژووی جووڵەکانی کۆگا (Stock Movements Audit Trail)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('gym_shops')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('inventory_batches')->cascadeOnDelete();
            $table->enum('movement_type', [
                'PURCHASE_RECEIPT', 'SALE', 'SALE_REFUND', 
                'TRANSFER_OUT', 'TRANSFER_IN', 'DAMAGE_WRITE_OFF', 
                'EXPIRED_WRITE_OFF', 'FREE_SAMPLE', 'MANUAL_ADJUSTMENT'
            ]);
            $table->integer('quantity'); // موجەب بۆ زیادبوون، سالب بۆ کەمبوونەوە
            $table->decimal('unit_cost', 12, 2);
            $table->string('reference_type')->nullable(); // e.g. pos_sale, transfer, purchase
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('performed_by_user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. گواستنەوەی نێوان شۆپەکان (Shop Transfers)
        Schema::create('shop_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_shop_id')->constrained('gym_shops')->cascadeOnDelete();
            $table->foreignId('destination_shop_id')->constrained('gym_shops')->cascadeOnDelete();
            $table->enum('status', ['PENDING', 'IN_TRANSIT', 'ACCEPTED', 'REJECTED'])->default('PENDING');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shop_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('shop_transfers')->cascadeOnDelete();
            $table->foreignId('source_batch_id')->constrained('inventory_batches')->cascadeOnDelete();
            $table->integer('quantity');
        });

        // 9. شفتی سندوقی شۆپ (Shop Cash Shifts)
        Schema::create('shop_cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('gym_shops')->cascadeOnDelete();
            $table->foreignId('cashier_user_id')->constrained('users');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 12, 2)->default(0.00);
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('actual_cash', 12, 2)->nullable();
            $table->decimal('cash_difference', 12, 2)->nullable(); // کورتێنان یان زیادە
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->text('closing_notes')->nullable();
            $table->timestamps();
        });

        // 10. فرۆشتنی POS (POS Sales Invoices)
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('gym_shops')->cascadeOnDelete();
            $table->foreignId('cash_shift_id')->constrained('shop_cash_shifts')->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users');
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->foreignId('discount_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('total_cogs', 12, 2); // تێچووی کۆی کاڵاکان بۆ ژماردنی قازانج
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('remaining_balance', 12, 2)->default(0.00);
            $table->enum('payment_method', ['CASH', 'CARD', 'SPLIT', 'MEMBER_CREDIT'])->default('CASH');
            $table->enum('payment_status', ['PAID', 'PARTIALLY_PAID', 'DUE', 'REFUNDED'])->default('PAID');
            $table->string('offline_client_uuid')->nullable()->unique(); // ڕێگری لە فرۆشتنی دووبارە لە Sync
            $table->boolean('is_offline_sync')->default(false);
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
        });

        // 11. بڕگەکانی فرۆشتن بەستراوە بە وەجبەی دیاریکراو (Sale Line Items)
        Schema::create('pos_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('inventory_batches')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_cost_price', 12, 2); // تێچوو بۆ ژماردنی دروستی COGS
            $table->decimal('unit_sale_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('line_total', 12, 2);
            $table->decimal('gross_profit', 12, 2); // (line_total - (unit_cost_price * quantity))
            $table->timestamps();
        });

        // 12. گەڕاندنەوەی فرۆشتن لە POS (Refunds)
        Schema::create('pos_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->string('refund_invoice_number')->unique();
            $table->foreignId('authorized_by_user_id')->constrained('users');
            $table->decimal('refund_amount', 12, 2);
            $table->text('reason');
            $table->timestamps();
        });

        Schema::create('pos_refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_refund_id')->constrained('pos_refunds')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('inventory_batches')->cascadeOnDelete();
            $table->integer('quantity');
            $table->boolean('restock_to_inventory')->default(true);
            $table->timestamps();
        });

        // 13. ئۆفەر و پاکێجەکانی شۆپ (Shop Promotions)
        Schema::create('shop_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('gym_shops')->nullOnDelete();
            $table->string('title');
            $table->enum('offer_type', ['PERCENTAGE', 'FIXED_AMOUNT', 'BUY_X_GET_Y', 'COMBO_BUNDLE']);
            $table->json('config');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->boolean('is_member_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_promotions');
        Schema::dropIfExists('pos_refund_items');
        Schema::dropIfExists('pos_refunds');
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('shop_cash_shifts');
        Schema::dropIfExists('shop_transfer_items');
        Schema::dropIfExists('shop_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_brands');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('gym_shops');
    }
};
