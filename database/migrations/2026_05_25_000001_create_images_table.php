<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable');
            $table->string('original_name');
            $table->string('unique_name')->unique();
            $table->string('path');
            $table->unsignedInteger('order')->default(0);
            $table->string('section')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index(['imageable_type', 'imageable_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
