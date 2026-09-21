<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_listings', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('rank', 80)->nullable();
            $table->unsignedBigInteger('price')->nullable();
            $table->string('owner_nickname', 80)->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 30)->default('available');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_listings');
    }
};
