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
       Schema::create('sales_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->dateTime('captured_at'); // waktu device
    $table->decimal('latitude', 10, 7);
    $table->decimal('longitude', 10, 7);
    $table->float('accuracy_m')->nullable();
    $table->text('address')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'captured_at']);
    $table->index(['captured_at']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_reports');
    }
};
