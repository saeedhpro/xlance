<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('reciever_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('project_id')->references('id')->on('projects')->cascadeOnDelete();
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
        Schema::dropIfExists('project_chats');
    }
}
