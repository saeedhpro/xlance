<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisputeMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->longText('body');
            $table->foreignId('dispute_id')->references('id')->on('disputes')->cascadeOnDelete();
            $table->foreignId('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamp('deleted_at')->nullable();
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
        Schema::dropIfExists('dispute_messages');
    }
}
