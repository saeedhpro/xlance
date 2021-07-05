<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShebaAndNationalCardIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('national_card_id')->nullable();
            $table->string('sheba')->nullable();
            $table->boolean('sheba_accepted')->default(false);
            $table->boolean('national_card_accepted')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('national_card_id');
            $table->dropColumn('sheba');
            $table->dropColumn('national_card_accepted');
            $table->dropColumn('sheba_accepted');
        });
    }
}
