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
        Schema::create('vendor_compliance_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained('vendors')->cascadeOnDelete();
            $table->string('packaging_policy')->nullable();
            $table->boolean('reusable_options')->default(false);
            $table->text('reusable_options_notes')->nullable();
            $table->string('waste_handling_capability')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            // Use JSONB for Postgres and add GIN indexes for fast querying
            $table->jsonb('certifications')->nullable();
            $table->jsonb('additional_metadata')->nullable();
            $table->timestamps();

            $table->index('certifications', 'vendor_compliance_profiles_certifications_gin', 'gin');
            $table->index('additional_metadata', 'vendor_compliance_profiles_additional_metadata_gin', 'gin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_compliance_profiles');
    }
};