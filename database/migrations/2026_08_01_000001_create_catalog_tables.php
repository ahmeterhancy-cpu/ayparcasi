<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('accent')->nullable(); // vurgu rengi (hex) — vitrinde kart tonlaması
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->text('care_notes')->nullable(); // bakım önerisi
            $table->text('contents')->nullable();   // buket içeriği

            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_at_price', 10, 2)->nullable();

            $table->boolean('track_stock')->default(true);
            $table->integer('stock')->default(0);
            // in_stock | low | made_to_order | out_of_stock — track_stock kapalıyken elle yönetilir
            $table->string('stock_status')->default('in_stock');

            $table->string('hero_image')->nullable();
            $table->json('gallery')->nullable();

            $table->string('badge')->nullable(); // "Çok satan", "Yeni"
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('same_day')->default(true); // aynı gün teslime uygun mu

            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('position')->default(0);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'position']);
            $table->index(['is_active', 'is_featured']);
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['category_id', 'product_id']);
        });

        // Boy seçenekleri: Küçük / Orta / Büyük — her biri kendi fiyatı ve stoğu
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable(); // "9 dal", "21 dal"
            $table->decimal('price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Sepete eklenebilir ekstralar: çikolata, vazo, balon, ayıcık
        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addons');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
