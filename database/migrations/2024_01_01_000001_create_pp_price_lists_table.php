<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pp_price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('filename')->nullable();
            $table->string('supplier')->default('');
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('items_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pp_price_lists');
    }
};
