<?php

namespace Karabin\Fabriq\Database\Seeders;

use Illuminate\Database\Seeder;
use Karabin\Fabriq\Models\Locale;

class LocaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Locale::query()->updateOrCreate(['iso_code' => 'sv'], [
            'name' => 'Swedish',
            'native' => 'Svenska',
            'regional' => 'se_SV',
            'enabled' => true,
            'sort_index' => 10,
        ]);
    }
}
