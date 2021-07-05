<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCityIdAndCountryIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->references('id')->on('cities')->cascadeOnDelete();
            $table->foreignId('country_id')->nullable()->references('id')->on('countries')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_city_id_foreign');
            $table->dropColumn('city_id');
            $table->dropForeign('users_country_id_foreign');
            $table->dropColumn('country_id');
        });
    }
}
