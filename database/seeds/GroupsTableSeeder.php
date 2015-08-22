<?php

use Illuminate\Database\Seeder;
 
class GroupsTableSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('groups')->delete();
 
        $groups = array(
            ['id' => 1, 'name' => 'administrator'],
            ['id' => 2, 'name' => 'moderator'],
            ['id' => 3, 'name' => 'registered'],
        );
 
        DB::table('groups')->insert($groups);
    }
 
}