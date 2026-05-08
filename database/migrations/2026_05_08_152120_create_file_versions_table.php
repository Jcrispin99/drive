<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('disk')->default('local');
            $table->string('storage_path');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['file_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_versions');
    }
};
