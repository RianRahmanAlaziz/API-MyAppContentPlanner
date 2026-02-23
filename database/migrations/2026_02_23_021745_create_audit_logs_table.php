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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event', 120);          // e.g. content.created, membership.role_changed
            $table->string('entity_type', 50);     // content, workspace, user, membership
            $table->unsignedBigInteger('entity_id')->nullable(); // target id

            $table->string('message')->nullable(); // ringkas untuk UI
            $table->json('before')->nullable();    // snapshot sebelum
            $table->json('after')->nullable();     // snapshot sesudah
            $table->json('meta')->nullable();      // ip, ua, payload, extra
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
