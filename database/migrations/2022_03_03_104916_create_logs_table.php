<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->enum('action',['add','delete','update','restore','disable'])->index();
            $table->unsignedBigInteger('identity_id')->nullable(false)->index();
            $table->unsignedBigInteger('actor_identity_id')->nullable()->index();
            $table->enum('type',['group','entitlement','account'])->index();
            $table->integer('type_id')->index();
            $table->string('data',255)->nullable()->index();
            // $table->foreign('actor_identity_id')->references('id')->on('identities');
//            $table->foreign('identity_id')->references('id')->on('identities');
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
        Schema::dropIfExists('logs');
    }
};
