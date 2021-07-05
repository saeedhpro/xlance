<?php

use App\Models\AcceptFreelancerRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcceptFreelancerRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accept_freelancer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreignId('request_id')->references('id')->on('requests')->cascadeOnDelete();
            $table->string('status')->default(AcceptFreelancerRequest::CREATED_STATUS);
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
        Schema::dropIfExists('accept_freelancer_requests');
    }
}
