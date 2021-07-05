<?php

use App\Models\Dispute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisputesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default(Dispute::CREATED_STATUS);
            $table->foreignId('freelancer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('employer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('project_id')->references('id')->on('projects')->cascadeOnDelete();
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
        Schema::dropIfExists('disputes');
    }
}
