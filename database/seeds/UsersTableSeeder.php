<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        DB::table('users')->delete();

        $users = array(
            [
                'id' => 1,
                'username' => 'Baasify',
                'email' => 'user@baasify.org',
                'password' => bcrypt('baasify'),
                'active' => '1',
                'created_at' => '0000-00-00 00:00:00.000000',
                'updated_at' => '0000-00-00 00:00:00.000000',
                'group_id' => '1'
            ]
        );

        DB::table('users')->insert($users);
    }

}