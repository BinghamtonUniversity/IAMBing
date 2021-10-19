<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('system_id')->index();
            $table->string('account_id')->index();
            $table->enum('status',[
                'active',
                'disabled',
                'deleted',
            ])->default('active');
            $table->boolean('override')->default(false);
            $table->string('override_description', 100)->nullable()->default(null);
            $table->unsignedBigInteger('override_user_id')->nullable()->default(null)->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('system_id')->references('id')->on('systems');
            $table->unique(['user_id','system_id','account_id','deleted_at']);
            $table->foreign('override_user_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
}
