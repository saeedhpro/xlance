<?php

use App\Models\PaymentHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default(PaymentHistory::WITHDRAW_TYPE);
            $table->string('status')->default(PaymentHistory::CREATED_STATUS);
            $table->bigInteger('amount')->default(0);
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('history_id')->nullable();
            $table->timestamp('deleted_at');
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
        Schema::dropIfExists('payment_histories');
    }
}
