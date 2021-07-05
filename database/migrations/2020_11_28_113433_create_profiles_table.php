<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('position', 250)->nullable();
            $table->unsignedTinyInteger('gender')->nullable();
            $table->boolean('marital_status')->nullable();
            $table->timestamp('birth_date')->nullable();
            $table->longText('languages')->nullable();
            $table->unsignedBigInteger('avatar_id')->nullable();
            $table->unsignedBigInteger('bg_id')->nullable();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profiles');
    }
}
