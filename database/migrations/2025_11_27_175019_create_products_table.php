<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('high_quality_image');
            $table->string('low_quality_image');
            $table->unsignedInteger('price')->default(0);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('purchased')->default(0);
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->index(['category_id']);
            $table->boolean('is_active')->default(true);
            $table->index(['is_active']);
            $table->boolean('is_3d')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
