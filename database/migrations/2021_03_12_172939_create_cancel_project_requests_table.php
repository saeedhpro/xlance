<?php

use App\Models\CancelProjectRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCancelProjectRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cancel_project_requests', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('status')->default(CancelProjectRequest::CREATED_STATUS);
            $table->foreignId('freelancer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('employer_id')->references('id')->on('users')->cascadeOnDelete();
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
        Schema::dropIfExists('cancel_project_requests');
    }
}
