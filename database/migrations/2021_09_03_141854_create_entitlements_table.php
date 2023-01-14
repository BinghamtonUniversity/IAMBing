<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntitlementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_id')->index();
            $table->string('name');
            $table->string('subsystem')->nullable()->default(null);
            $table->boolean('override_add')->default(false);
            $table->boolean('end_user_visible')->default(true);
            $table->boolean('require_prerequisite')->default(false);
            $table->json('prerequisites');
            $table->timestamps();
            $table->foreign('system_id')->references('id')->on('systems');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entitlements');
    }
}
