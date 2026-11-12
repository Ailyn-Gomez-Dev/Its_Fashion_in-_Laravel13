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
        if (Schema::hasTable('productos') && !Schema::hasColumn('productos', 'imagen')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->string('imagen', 500)->nullable()->after('categoria_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('productos') && Schema::hasColumn('productos', 'imagen')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('imagen');
            });
        }
    }
};
