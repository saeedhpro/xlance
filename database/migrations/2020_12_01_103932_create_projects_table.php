<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default(Project::CREATED_STATUS);
            $table->longText('description');
            $table->unsignedBigInteger('min_price')->default(0);
            $table->unsignedBigInteger('max_price')->default(0);
            $table->foreignId('employer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->nullable()->references('id')->on('users')->cascadeOnDelete();
            $table->softDeletes();
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
        Schema::dropIfExists('projects');
    }
}
