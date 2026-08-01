<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            // hero | strip (üst duyuru şeridi) | promo (ara kampanya bloğu)
            $table->string('placement')->default('promo');
            $table->string('eyebrow')->nullable();
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->string('image')->nullable();
            $table->string('link')->nullable();
            $table->string('cta_label')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('settings');
    }
};
