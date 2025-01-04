<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            [
                'id' => 1,
                'text' => 'Company Name',
                'value' => 'Paltel',
            ],
            [
                'id' => 2,
                'text' => 'Company Email',
                'value' => 'no-replay@paltel.ps',
            ],
            [
                'id' => 3,
                'text' => 'Company Phone',
                'value' => '+970 595 567064',
            ],
            [
                'id' => 4,
                'text' => 'Company Address',
                'value' => 'Palestine - Gaza',
            ],
            [
                'id' => 5,
                'text' => 'Api Key',
                'value' => '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',
            ],
        ];

        DB::table('settings')->insert($settings);
    }
}
