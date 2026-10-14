<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pp_price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('pp_price_lists')->cascadeOnDelete();
            $table->string('article');
            $table->string('article_normalized')->comment('Uppercased, stripped of spaces/dashes — used for matching');
            $table->string('brand')->default('');
            $table->string('brand_normalized')->default('');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('description')->default('');
            $table->string('availability')->default('');
            $table->timestamps();

            $table->index('article_normalized');
            $table->index(['article_normalized', 'brand_normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pp_price_list_items');
    }
};
