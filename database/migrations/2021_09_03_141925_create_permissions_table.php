<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('permission',[
                "view_users",
                "manage_users",
                "manage_user_permissions",
                "merge_users",
                "override_user_accounts",
                "override_user_entitlements",
                "manage_user_groups",
                "impersonate_users",
                "view_groups",
                "manage_groups",
                "manage_systems",
                "manage_apis",
                "manage_entitlements",
                "view_jobs",
                "manage_jobs",
                "manage_systems_config"
            ]);
            $table->foreign('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('permissions');
    }
}
