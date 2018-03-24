<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class SystemtrackerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $columns =['lists','members'];
        foreach ($columns as $name)
        {
            DB::table('systemtracker')->insert([
                   'name'=>$name,
                    'isUpdated'=>0
            ]);
        }

    }
}
