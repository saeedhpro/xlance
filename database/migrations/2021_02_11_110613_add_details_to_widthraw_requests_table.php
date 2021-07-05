<?php

use App\Models\WithdrawRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToWidthrawRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('widthraw_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->default(0);
            $table->foreignId('user_id')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->string('status')->default(WithdrawRequest::CREATED_STATUS);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('widthraw_requests', function (Blueprint $table) {
            $table->dropForeign('widthraw_requests_user_id_foreign');
            $table->dropColumn('user_id');
            $table->dropColumn('status');
            $table->dropColumn('amount');
        });
    }
}
