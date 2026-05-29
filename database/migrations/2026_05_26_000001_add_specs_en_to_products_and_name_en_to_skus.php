<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('specs_en')->nullable()->after('specs');
        });

        Schema::table('product_skus', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('specs_en');
        });

        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropColumn('name_en');
        });
    }
};
