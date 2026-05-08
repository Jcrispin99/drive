<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->morphs('shareable');
            $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with')->nullable()->constrained('users')->cascadeOnDelete();
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->string('token')->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['shared_with', 'shareable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
