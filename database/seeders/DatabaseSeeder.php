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
        // \App\Models\User::factory(10)->create();
        \App\Models\User::create(['first_name'=>'Example','last_name'=>'User', 'default_username'=>'euser1']);
        
        \App\Models\Configuration::create(['name'=>'default_username_template','config'=>'{{first_name.0}}{{last_name.0}}{{last_name.1}}{{last_name.2}}{{last_name.3}}{{last_name.4}}{{last_name.5}}{{iterator}}']);
    }
}
