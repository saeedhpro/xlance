<?php

use App\Models\Request;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default(Request::CREATED_STATUS);
            $table->string('type')->default(Request::EMPLOYER_TYPE);
            $table->longText('description');
            $table->unsignedInteger('delivery_date');
            $table->boolean('is_distinguished')->default(false);
            $table->boolean('is_sponsored')->default(false);
            $table->foreignId('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('to_id')->references('id')->on('users')->cascadeOnDelete();
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
        Schema::dropIfExists('requests');
    }
}
