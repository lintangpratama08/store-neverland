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
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 24);
            $table->unsignedBigInteger('source_id');
            $table->string('source_name', 120);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('amount')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sold_at')->useCurrent();
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
            $table->index(['sold_at', 'source_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
