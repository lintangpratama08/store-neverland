<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_listing_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_listing_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['account_listing_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_listing_images');
    }
};
