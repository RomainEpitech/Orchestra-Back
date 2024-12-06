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
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('enterprise_uuid');
            $table->string('name');
            $table->json('authority')->nullable();
            $table->string('color_hex', 7);
            $table->timestamps();

            $table->foreign('enterprise_uuid')
                ->references('uuid')
                ->on('enterprises')
                ->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['id', 'name', 'remember_token']);
            $table->timestamp('email_verified_at')->nullable()->change();

            $table->uuid('uuid')->primary()->first();
            $table->binary('profile_picture')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->boolean('status')->default(true);
            $table->uuid('role_uuid');
            $table->uuid('enterprise_uuid');
            $table->integer('leave_days')->default(0);
            $table->timestamp('joined_at')->nullable();  // Modifié ici

            $table->foreign('role_uuid')
                ->references('uuid')
                ->on('roles')
                ->onDelete('restrict');

            $table->foreign('enterprise_uuid')
                ->references('uuid')
                ->on('enterprises')
                ->onDelete('cascade');

            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_uuid']);
            $table->dropForeign(['enterprise_uuid']);

            $table->dropColumn([
                'uuid',
                'profile_picture',
                'first_name',
                'last_name',
                'status',
                'role_uuid',
                'enterprise_uuid',
                'leave_days',
                'joined_at'
            ]);

            $table->id();
            $table->string('name');
            $table->rememberToken();
        });
    }
};