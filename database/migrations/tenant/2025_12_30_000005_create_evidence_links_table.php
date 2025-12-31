<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_artifact_id')
                ->constrained('evidence_artifacts')
                ->cascadeOnDelete();
            $table->string('linkable_type');
            $table->unsignedBigInteger('linkable_id');
            $table->string('report_section_tag')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_links');
    }
};
