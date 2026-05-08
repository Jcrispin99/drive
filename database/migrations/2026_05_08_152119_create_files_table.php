<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('extension')->nullable();
            $table->string('mime_type');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('disk')->default('local');
            $table->timestamp('starred_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'folder_id']);
            $table->index(['user_id', 'last_accessed_at']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
