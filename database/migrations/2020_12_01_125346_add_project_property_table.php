<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectPropertyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_project_property', function (Blueprint $table) {
            $table->foreignId('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreignId('project_property_id')->references('id')->on('project_properties')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_project_property');
    }
}
