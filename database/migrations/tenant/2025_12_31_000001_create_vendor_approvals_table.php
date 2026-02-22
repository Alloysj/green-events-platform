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
        Schema::create('vendor_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('status'); // approved, revoked, pending
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for common lookup patterns (Postgres)
            $table->index('status');
            $table->index('expires_at');
            $table->index(['vendor_id', 'status'], 'vendor_approvals_vendor_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_approvals');
    }
};