<?php

declare(strict_types=1);

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
        Schema::create('goals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            // personal: a goal a member set for themselves, organization: a goal the organization set (team goals extension)
            $table->string('type');
            $table->string('comparison');
            $table->unsignedBigInteger('target_seconds');
            $table->string('period');
            $table->jsonb('filters');
            // Timezone and week start define the periods of the goal, they are pinned on the goal like on a report
            $table->string('timezone');
            $table->string('week_start');
            $table->dateTime('archived_at')->nullable();
            $table->uuid('organization_id');
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            // Member whose time entries count towards the goal, null means every member of the organization
            // (organization goals only). Personal goals always have a member.
            $table->uuid('member_id')->nullable();
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
            $table->index(['organization_id', 'member_id']);
            $table->index(['organization_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
