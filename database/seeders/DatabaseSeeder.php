<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Configuration::create(['name'=>'default_username_template','config'=>'{{first_name.0}}{{last_name.0}}{{last_name.1}}{{last_name.2}}{{last_name.3}}{{last_name.4}}{{last_name.5}}{{iterator}}']);
        \App\Models\Configuration::create(['name'=>'user_attributes','config'=>'[{name:"first_name",label:"First Name},{name:"last_name",label:"Last Name},{name:"email",label:"Default Email}]']);
        \App\Models\Configuration::create(['name'=>'user_unique_ids','config'=>'[{name:"bnumber",label:"BNumber}]']);
        \App\Models\User::create(['id'=>1,'attributes'=>['first_name'=>'Example','last_name'=>'User'],'default_username'=>'euser1']);
    }
}
