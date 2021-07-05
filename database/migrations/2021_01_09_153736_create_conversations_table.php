<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Conversation;

class CreateConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default(Conversation::OPEN_STATUS);
            $table->string('type')->default(Conversation::DIRECT_TYPE);
            $table->foreignId('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('conversationable_id');
            $table->string('conversationable_type');
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
        Schema::dropIfExists('conversations');
    }
}
