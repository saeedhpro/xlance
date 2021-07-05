<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionBgColorIconIdToProjectPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_properties', function (Blueprint $table) {
            $table->string('bg_color')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('icon_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_properties', function (Blueprint $table) {
            $table->dropColumn('bg_color');
            $table->dropColumn('description');
            $table->dropColumn('icon_id');
        });
    }
}
