<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->string('category')->index();
            $table->date('spend_date')->index();
            $table->unsignedTinyInteger('month')->index();
            $table->unsignedSmallInteger('year')->index();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
