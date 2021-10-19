<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserEntitlementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_entitlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('entitlement_id')->index();
            $table->enum('type', ['add','remove'])->default('add');
            $table->boolean('override')->default(false);
            $table->string('override_description', 100)->nullable()->default(null);
            $table->unsignedBigInteger('override_user_id')->nullable()->default(null)->index();
            $table->unique(['user_id','entitlement_id']);
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('override_user_id')->references('id')->on('users');
            $table->foreign('entitlement_id')->references('id')->on('entitlements');
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
        Schema::dropIfExists('user_entitlements');
    }
}
