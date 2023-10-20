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
            $table->unsignedBigInteger('identity_id')->index();
            $table->unsignedBigInteger('system_id')->index();
            $table->string('account_id')->index();
            $table->enum('status',[
                'active',
                'disabled',
                'deleted',
                'sync_error',
            ])->default('active');
            $table->json('account_attributes');
            $table->foreign('identity_id')->references('id')->on('identities');
            $table->foreign('system_id')->references('id')->on('systems');
            $table->unique(['identity_id','system_id','account_id']);
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
        Schema::dropIfExists('accounts');
    }
}
