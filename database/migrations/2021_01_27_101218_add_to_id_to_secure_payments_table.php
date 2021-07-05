<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToIdToSecurePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('secure_payments', function (Blueprint $table) {
            $table->foreignId('to_id')->nullable()->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('secure_payments', function (Blueprint $table) {
            $table->dropForeign('secure_payments_to_id_foreign');
            $table->dropColumn('to_id');
        });
    }
}
