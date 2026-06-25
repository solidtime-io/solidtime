<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('member_id');
            $table->string('type');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('half_day')->default(false);
            $table->integer('days_count');
            $table->string('status')->default('pending');
            $table->text('private_note')->nullable();
            $table->text('public_note')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('members')->nullOnDelete();

            $table->index(['organization_id', 'member_id']);
            $table->index(['organization_id', 'status']);
            $table->index(['member_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_requests');
    }
};
