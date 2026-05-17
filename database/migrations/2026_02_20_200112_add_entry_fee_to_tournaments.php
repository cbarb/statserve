<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->unsignedInteger('entry_fee')->nullable()->after('max_rating');
            $table->unsignedTinyInteger('platform_fee_percent')->default(10)->after('entry_fee');
        });

        Schema::table('tournament_entries', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->after('status');
            $table->unsignedInteger('amount_paid')->nullable()->after('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['entry_fee', 'platform_fee_percent']);
        });

        Schema::table('tournament_entries', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_intent_id', 'amount_paid']);
        });
    }
};
