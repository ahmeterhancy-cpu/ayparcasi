<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Buket", "Orkide", "Aranjman"

            // Yeni ürüne olduğu gibi kopyalanan alanlar
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->text('contents')->nullable();
            $table->text('care_notes')->nullable();
            $table->string('badge')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('track_stock')->default(true);
            $table->integer('stock')->default(0);
            $table->boolean('same_day')->default(true);

            // Kategori ve ek ürünler yalnızca varsayılan; ilişki kurmaya
            // gerek yok, uygulanırken hâlâ var olanlar süzülüyor.
            $table->json('category_ids')->nullable();
            $table->json('addon_ids')->nullable();

            // [{name, description, price, stock, is_default}]
            $table->json('variants')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_templates');
    }
};
