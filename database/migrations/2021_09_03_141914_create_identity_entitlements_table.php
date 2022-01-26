<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentityEntitlementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identity_entitlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id')->index();
            $table->unsignedBigInteger('entitlement_id')->index();
            $table->enum('type', ['add','remove'])->default('add');
            $table->boolean('override')->default(false);
            $table->string('override_description', 100)->nullable()->default(null);
            $table->unsignedBigInteger('override_identity_id')->nullable()->default(null)->index();
            $table->unique(['identity_id','entitlement_id']);
            $table->foreign('identity_id')->references('id')->on('identities');
            $table->foreign('override_identity_id')->references('id')->on('identities');
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
        Schema::dropIfExists('identity_entitlements');
    }
}
