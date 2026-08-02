<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addon_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained()->cascadeOnDelete();
            $table->unique(['product_id', 'addon_id']);
        });

        // Ek ürünler bugüne kadar global çalışıyordu: aktif olan her ek ürün
        // her ürün sayfasında görünüyordu. Boş bir tabloyla açılırsak vitrinde
        // "Yanına ekleyin" bölümü sessizce kaybolur — o yüzden mevcut davranış
        // birebir aktarılıyor, dükkân sahibi istemediğini panelden ayıklar.
        $addonIds = DB::table('addons')->pluck('id');
        $productIds = DB::table('products')->pluck('id');

        if ($addonIds->isEmpty() || $productIds->isEmpty()) {
            return;
        }

        $rows = [];

        foreach ($productIds as $productId) {
            foreach ($addonIds as $addonId) {
                $rows[] = ['product_id' => $productId, 'addon_id' => $addonId];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('addon_product')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_product');
    }
};
