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
            $table->string('slug');
            $table->text('description')->nullable()->default(null);
            $table->string('affiliation')->nullable()->default(null);
            $table->unsignedInteger('order')->default(4294967295);
            $table->enum('type',['manual','auto'])->default('auto');
            $table->boolean('delay_add')->default(false);
            $table->string('add_priority')->default('default');
            $table->unsignedInteger('delay_add_days')->nullable()->default(null);
            $table->boolean('delay_remove')->default(false);
            $table->string('remove_priority')->default('default');
            $table->unsignedInteger('delay_remove_days')->nullable()->default(null);
            $table->boolean('delay_remove_notify')->default(false);
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
        Schema::dropIfExists('groups');
    }
}
