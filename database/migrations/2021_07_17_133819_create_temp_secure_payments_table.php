<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempSecurePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_secure_payments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('price')->default(0);
            $table->foreignId('request_id')->references('id')->on('requests')->cascadeOnDelete();
            $table->foreignId('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('to_id')->references('id')->on('users')->cascadeOnDelete();
            $table->boolean('is_first')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp_secure_payments');
    }
}
