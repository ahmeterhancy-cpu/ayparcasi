<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // İndirim penceresi — dışındayken üstü çizili fiyattan satılır
            $table->timestamp('sale_starts_at')->nullable()->after('compare_at_price');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedInteger('per_user_limit')->nullable()->after('usage_limit');
            // all | products | categories
            $table->string('applies_to')->default('all')->after('type');
            $table->boolean('exclude_sale_items')->default(false)->after('free_delivery');
            $table->text('allowed_emails')->nullable()->after('exclude_sale_items');
        });

        Schema::create('coupon_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['coupon_id', 'product_id']);
        });

        Schema::create('category_coupon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unique(['coupon_id', 'category_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('refunded_total', 10, 2)->default(0)->after('total');
            // Stok bu sipariş için düşüldü mü — çift düşme/çift iade olmasın
            $table->boolean('stock_reserved')->default(false)->after('refunded_total');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // iadeyi yapan ekip üyesi
            $table->decimal('amount', 10, 2);
            $table->string('reason')->nullable();
            $table->boolean('restocked')->default(false);
            $table->timestamps();
        });

        // Mevcut siparişlerin stoğu zaten düşülmüştü
        DB::table('orders')->update(['stock_reserved' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['refunded_total', 'stock_reserved']);
        });

        Schema::dropIfExists('category_coupon');
        Schema::dropIfExists('coupon_product');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['per_user_limit', 'applies_to', 'exclude_sale_items', 'allowed_emails']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sale_starts_at', 'sale_ends_at']);
        });
    }
};
