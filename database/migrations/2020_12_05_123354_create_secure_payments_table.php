<?php

use App\Models\SecurePayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSecurePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('secure_payments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default(SecurePayment::CREATED_STATUS);
            $table->unsignedBigInteger('price')->default(0);
            $table->foreignId('request_id')->references('id')->on('requests')->cascadeOnDelete();
            $table->foreignId('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
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
        Schema::dropIfExists('secure_payments');
    }
}
