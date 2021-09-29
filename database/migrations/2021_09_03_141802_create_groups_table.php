<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable()->default(null); 
            $table->string('affiliation')->nullable()->default(null);
            $table->unsignedInteger('order')->default(4294967295);
            $table->unsignedBigInteger('user_id')->index(); // Owner
            $table->enum('type',['manual','auto'])->default('manual');
            // Delete user if they are removed from this group (and are not a member of any other groups)
            $table->boolean('purge_user_on_remove')->default(false);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groups');
    }
}
