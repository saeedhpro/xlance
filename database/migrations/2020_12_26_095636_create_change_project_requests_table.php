<?php

use App\Models\ChangeProjectRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChangeProjectRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('change_project_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('new_price')->default(0);
            $table->foreignId('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->string('status')->default(ChangeProjectRequest::CREATED_STATUS);
            $table->string('type')->default(ChangeProjectRequest::FREELANCER_TYPE);
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
        Schema::dropIfExists('change_project_requests');
    }
}
