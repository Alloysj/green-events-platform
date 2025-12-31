<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('status')->default('pending')->index();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->dropColumn([
                'report_id',
                'type',
                'status',
                'storage_disk',
                'storage_path',
                'mime_type',
                'error_message',
                'created_by',
                'metadata',
            ]);
        });
    }
};
