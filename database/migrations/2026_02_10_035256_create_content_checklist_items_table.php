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
        Schema::create('content_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();

            $table->string('label');          // contoh: "Script", "Shoot", "Edit"
            $table->boolean('is_done')->default(false);

            $table->unsignedInteger('sort_order')->default(0); // biar bisa urut
            $table->timestamps();

            $table->index(['content_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_checklist_items');
    }
};
