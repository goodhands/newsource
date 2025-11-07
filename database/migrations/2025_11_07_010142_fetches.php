<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fetches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained();
            $table->integer('pages_fetched');
            $table->integer('articles_fetched');
            $table->integer('total_pages_available');
            $table->integer('http_status_code');
            $table->string('error_message')->nullable();
            $table->tinyInteger('was_rate_limited');
            $table->integer('retry_after_seconds')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fetches');
    }
};
