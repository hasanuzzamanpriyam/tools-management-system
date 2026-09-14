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
        Schema::create('device_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_token_id')->constrained()->onDelete('cascade');
            $table->string('device_fingerprint');
            $table->timestamp('activated_at');
            $table->unique(['license_token_id', 'device_fingerprint']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_activations');
    }
};
