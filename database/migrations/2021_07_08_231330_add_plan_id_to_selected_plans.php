<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlanIdToSelectedPlans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('selected_plans', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('title')->nullable()
                ->references('id')->on('request_packages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('selected_plans', function (Blueprint $table) {
            $table->dropForeign('selected_plans_plan_id_foreign');
            $table->dropColumn('plan_id');
        });
    }
}
