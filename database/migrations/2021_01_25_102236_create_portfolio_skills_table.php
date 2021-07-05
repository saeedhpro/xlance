<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortfolioSkillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portfolio_skill', function (Blueprint $table) {
            $table->foreignId('portfolio_id')->references('id')->on('portfolios')->cascadeOnDelete();
            $table->foreignId('skill_id')->references('id')->on('skills')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('portfolio_skill', function (Blueprint $table) {
            $table->dropForeign('portfolio_skills_portfolio_id_foreign');
            $table->dropForeign('portfolio_skills_skill_id_foreign');
            $table->dropColumn('skill_id');
            $table->dropColumn('portfolio_id');
        });
    }
}
