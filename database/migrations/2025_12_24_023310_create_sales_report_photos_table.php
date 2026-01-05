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
        Schema::create('sales_report_photos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sales_report_id')->constrained('sales_reports')->cascadeOnDelete();
    $table->string('file_path');
    $table->text('file_url')->nullable();
    $table->string('mime_type')->nullable();
    $table->unsignedBigInteger('size_bytes')->nullable();
    $table->unsignedInteger('width')->nullable();
    $table->unsignedInteger('height')->nullable();
    $table->char('sha256', 64)->nullable();
    $table->timestamps();

    $table->index(['sales_report_id']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_report_photos');
    }
};
