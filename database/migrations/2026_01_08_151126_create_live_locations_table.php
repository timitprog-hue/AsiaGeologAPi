<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('live_locations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
        $table->decimal('latitude', 10, 7);
        $table->decimal('longitude', 10, 7);
        $table->float('accuracy_m')->nullable();
        $table->timestamp('captured_at')->nullable();
        $table->timestamps(); // updated_at dipakai buat status LIVE
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_locations');
    }
};
