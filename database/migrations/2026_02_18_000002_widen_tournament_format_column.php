<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('format', 30)->change();
        });

        // Update legacy format values in existing tournament rows
        DB::table('tournaments')->where('format', 'singles')->update(['format' => 'open_singles']);
        DB::table('tournaments')->where('format', 'doubles')->update(['format' => 'open_doubles']);
    }

    public function down(): void
    {
        DB::table('tournaments')->where('format', 'open_singles')->update(['format' => 'singles']);
        DB::table('tournaments')->where('format', 'open_doubles')->update(['format' => 'doubles']);

        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('format', 20)->change();
        });
    }
};
