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
        Schema::create('enterprise_modules', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('module_uuid');
            $table->uuid('enterprise_uuid');
            $table->boolean('is_activated')->default(true);
            $table->timestamps();

            $table->foreign('module_uuid')->references('uuid')->on('modules');
            $table->foreign('enterprise_uuid')->references('uuid')->on('enterprises');
        });

        // Table des modules achetés
        Schema::create('enterprise_purchased_modules', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('enterprise_uuid');
            $table->uuid('module_uuid');
            $table->timestamp('purchased_at');
            $table->timestamps();

            $table->foreign('enterprise_uuid')->references('uuid')->on('enterprises');
            $table->foreign('module_uuid')->references('uuid')->on('modules');
            $table->unique(['enterprise_uuid', 'module_uuid']);
        });

        // Table pour l'abonnement premium
        Schema::create('enterprise_subscriptions', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('enterprise_uuid');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('enterprise_uuid')->references('uuid')->on('enterprises');
        });

        // Table des limites par module
        Schema::create('module_limits', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('module_uuid');
            $table->json('free_limit');
            $table->timestamps();

            $table->foreign('module_uuid')->references('uuid')->on('modules');
            $table->unique(['module_uuid']);
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->decimal('purchase_price', 10, 2)->nullable()->after('is_core');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enterprise_modules');
        Schema::dropIfExists('enterprise_purchased_modules');
        Schema::dropIfExists('enterprise_subscriptions');
        Schema::dropIfExists('module_limits');

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('purchase_price');
        });
    }
};
