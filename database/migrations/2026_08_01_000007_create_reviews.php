<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Hesap silinse de yorum kalır; bu yüzden ad ayrıca saklanır.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Yorum hakkını veren sipariş — sonradan denetlenebilsin diye tutulur.
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body');

            // pending | approved | rejected
            $table->string('status')->default('pending');

            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            // Bir müşteri bir ürüne bir kez puan verir.
            $table->unique(['product_id', 'user_id']);
            $table->index(['product_id', 'status']);
        });

        // Tohum verisiyle üretilmiş sahte puanları sıfırla. Bundan sonra
        // products.rating / review_count yalnızca onaylı yorumlardan hesaplanır
        // (bkz. App\Models\Review::syncProduct).
        DB::table('products')->update(['rating' => null, 'review_count' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
