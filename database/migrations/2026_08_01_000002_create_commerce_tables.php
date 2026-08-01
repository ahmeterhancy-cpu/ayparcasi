<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Girne, Lefkoşa, Mağusa...
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('free_over', 10, 2)->nullable(); // bu tutarın üstü ücretsiz
            $table->boolean('same_day')->default(true);
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_slots', function (Blueprint $table) {
            $table->id();
            $table->string('label');                 // "09:00 – 12:00"
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percent'); // percent | fixed
            $table->decimal('value', 10, 2);
            $table->decimal('min_total', 10, 2)->nullable();
            $table->boolean('free_delivery')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();

            // pending | confirmed | preparing | on_the_way | delivered | cancelled
            $table->string('status')->default('pending');
            // unpaid | paid | failed | refunded
            $table->string('payment_status')->default('unpaid');
            // tiko | cash | transfer | whatsapp
            $table->string('payment_method')->default('tiko');

            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();

            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->foreignId('delivery_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('delivery_zone_name')->nullable();
            $table->text('delivery_address')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('delivery_slot')->nullable();

            $table->text('card_message')->nullable();
            $table->string('card_sender')->nullable();
            $table->boolean('hide_sender')->default(false);
            $table->text('note')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 8)->default('TL');

            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable();

            $table->string('payment_reference')->nullable();
            $table->json('payment_payload')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->string('ip')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('delivery_date');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('variant_name')->nullable();
            $table->string('image')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('line_total', 10, 2);
            $table->json('addons')->nullable();
            $table->timestamps();
        });

        // "WhatsApp'tan stok bilgisi al" tıklamaları — hangi ürün ilgi görüyor
        Schema::create('stock_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('source')->default('product'); // product | listing
            $table->string('ip')->nullable();
            $table->boolean('handled')->default(false);
            $table->timestamps();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('stock_inquiries');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('delivery_slots');
        Schema::dropIfExists('delivery_zones');
    }
};
