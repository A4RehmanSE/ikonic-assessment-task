<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('orders', 'external_order_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('external_order_id')->nullable()->after('commission_owed');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('orders', 'external_order_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('external_order_id');
            });
        }
    }
};
