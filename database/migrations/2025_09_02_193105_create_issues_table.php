<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();

            // optional link to a user story
            $table->foreignId('user_story_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // bug / feature / improvement
            $table->enum('type', ['bug','feature','improvement'])->default('bug');

            // low / medium / high
            $table->enum('priority', ['low','medium','high'])->default('medium');

            // open / in_progress / resolved
            $table->enum('status', ['open','in_progress','resolved'])->default('open');

            // who is assigned
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // link to a task (optional)
            $table->foreignId('task_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // optional deadline if you want
            $table->date('due_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};

