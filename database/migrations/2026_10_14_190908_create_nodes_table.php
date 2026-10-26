<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->float('position_x')->default(0);
            $table->float('position_y')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index('workflow_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
