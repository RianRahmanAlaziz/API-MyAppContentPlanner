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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();

            $table->string('platform');      // ig, tiktok, youtube
            $table->string('content_type');  // reel, carousel, story, video, shorts, long

            $table->string('title');
            $table->string('hook')->nullable();

            $table->longText('script')->nullable();
            $table->longText('caption')->nullable();
            $table->longText('hashtags')->nullable();

            $table->string('status'); // idea, brief, production, review, scheduled, published
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('due_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->string('priority')->default('med'); // low/med/high
            $table->json('tags')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'platform']);
            $table->index(['workspace_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
