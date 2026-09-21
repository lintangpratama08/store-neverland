<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->boolean('is_external')->default(false)->after('status');
            $table->string('contact_whatsapp', 32)->nullable()->after('is_external');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->json('variants')->nullable()->after('image_path');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->json('items')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('items');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('variants');
        });

        Schema::table('tournaments', function (Blueprint $table): void {
            $table->dropColumn(['is_external', 'contact_whatsapp']);
        });
    }
};
