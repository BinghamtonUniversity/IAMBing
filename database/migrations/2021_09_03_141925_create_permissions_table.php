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
            $table->unsignedBigInteger('identity_id')->index();
            $table->enum('permission',[
                "view_identities",
                "manage_identities",
                "manage_identity_permissions",
                "merge_identities",
                "manage_identity_accounts",
                "override_identity_entitlements",
                "impersonate_identities",
                "view_groups",
                "manage_groups",
                "manage_systems",
                "manage_apis",
                "manage_entitlements",
                "view_jobs",
                "manage_jobs",
                "manage_systems_config",
                "view_logs",
                "manage_logs",
                "view_group_action_queue",
                "manage_group_action_queue",
                "view_reports",
                "manage_reports"
            ]);
            $table->foreign('identity_id')->references('id')->on('identities');
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
