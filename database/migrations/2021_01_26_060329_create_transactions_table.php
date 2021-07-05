<?php

use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->references('id')->on('projects')->nullOnDelete();
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('status')->default(Transaction::CREATED_STATUS);
            $table->string('type')->default(Transaction::PROJECT_TYPE);
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
        Schema::dropIfExists('orders');
    }
}
