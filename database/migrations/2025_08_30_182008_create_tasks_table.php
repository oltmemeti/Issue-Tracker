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
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_story_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->text('acceptance_criteria')->nullable();
    $table->integer('story_points')->default(1);
    $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
    $table->string('status')->default('todo'); // todo, in_progress, qa, done
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
