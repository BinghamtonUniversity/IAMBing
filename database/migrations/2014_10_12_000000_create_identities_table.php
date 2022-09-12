<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIdentitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identities', function (Blueprint $table) {
            $table->id();
            $table->string('iamid')->nullable()->default(null)->index();
            $table->enum('type', ['person', 'organization','service'])->nullable()->default('person')->index();
            $table->boolean('sponsored')->default(false);
            $table->string('first_name')->nullable()->default(null)->index();
            $table->string('last_name')->nullable()->default(null)->index();
            $table->string('default_username')->nullable()->default(null)->unique()->index();
            $table->string('default_email')->nullable()->default(null)->index();
            $table->unsignedBigInteger('sponsor_identity_id')->default(null)->nullable()->index();
            $table->rememberToken();
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
        Schema::dropIfExists('identities');
    }
}
