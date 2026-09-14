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
        Schema::create('license_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->string('token_hash')->unique();
            $table->integer('device_limit');
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_tokens');
    }
};
