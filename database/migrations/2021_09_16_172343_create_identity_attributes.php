<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentityAttributes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identity_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id')->index();
            $table->string('name')->index();
            $table->string('value',512)->nullable()->default(null)->index();
            $table->boolean('array')->default(false);
            $table->timestamps();
            $table->index(['identity_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('identity_attributes');
    }
}
