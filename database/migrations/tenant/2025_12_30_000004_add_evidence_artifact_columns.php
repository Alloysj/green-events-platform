<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence_artifacts', function (Blueprint $table) {
            $table->string('file_name')->nullable();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('evidence_artifacts', function (Blueprint $table) {
            $table->dropColumn([
                'file_name',
                'storage_disk',
                'storage_path',
                'mime_type',
                'file_size',
                'uploaded_by',
                'metadata',
            ]);
        });
    }
};
