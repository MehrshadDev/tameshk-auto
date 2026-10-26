<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->foreignId('target_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->string('source_handle')->nullable();
            $table->string('target_handle')->nullable();
            $table->timestamps();

            $table->index('workflow_id');
            $table->index(['source_node_id', 'target_node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edges');
    }
};
