<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuration;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $default_username_template = new Configuration(['name'=>'default_username_template','config'=>'{{first_name.0}}{{last_name.0}}{{last_name.1}}{{last_name.2}}{{last_name.3}}{{last_name.4}}{{last_name.5}}{{last_name.6}}{{last_name.7}}{{last_name.8}}{{last_name.9}}{{last_name.10}}{{#iterator}}{{iterator}}{{/iterator}}']);
        $default_username_template->save();
        $user_attributes = new Configuration(['name'=>'user_attributes','config'=>[['name'=>"nickname",'label'=>'Nickname'],['name'=>'email','label'=>'Default Email']]]);
        $user_attributes->save();
        $user_unique_ids = new Configuration(['name'=>'user_unique_ids','config'=>[['name'=>'bnumber','label'=>'BNumber']]]);
        $user_unique_ids->save();
        $affiliations = new Configuration(['name'=>'affiliations','config'=>['faculty','staff','student','employee','member','affiliate','alum','library-walk-in','applicant']]);
        $affiliations->save();
        $exampe_user = new User(['first_name'=>'Example','last_name'=>'User','attributes'=>['nickname'=>'Dude'],'default_username'=>'euser1']);
        $exampe_user->save();
    }
}
