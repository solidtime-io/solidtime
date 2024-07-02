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
        Schema::table('time_entries', function (Blueprint $table): void {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->dropForeign(['client_id']);
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
        Schema::table('project_members', function (Blueprint $table): void {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
        Schema::table('organization_invitations', function (Blueprint $table): void {
            $table->dropForeign(['organization_id']);
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table): void {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->dropForeign(['client_id']);
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
        Schema::table('project_members', function (Blueprint $table): void {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
        Schema::table('organization_invitations', function (Blueprint $table): void {
            $table->dropForeign(['organization_id']);
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
